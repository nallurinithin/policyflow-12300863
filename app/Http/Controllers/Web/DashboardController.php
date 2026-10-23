<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\IllegalStateTransitionException;
use App\Http\Controllers\Controller;
use App\Models\PolicyRequest;
use App\Models\ProviderEvent;
use App\Services\IssuanceService;
use App\Services\PolicyStateMachine;
use App\Services\PremiumCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected PremiumCalculator $calculator,
        protected PolicyStateMachine $stateMachine,
        protected IssuanceService $issuanceService
    ) {}

    /**
     * Operational Dashboard: metrics & filtered policy requests table.
     */
    public function index(Request $request): View
    {
        $query = PolicyRequest::query();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('plan_code')) {
            $query->where('plan_code', $request->query('plan_code'));
        }

        if ($request->filled('destination')) {
            $query->where('destination', $request->query('destination'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('client_request_id', 'like', "%{$search}%")
                  ->orWhere('policy_number', 'like', "%{$search}%")
                  ->orWhere('agent_id', 'like', "%{$search}%");
            });
        }

        $policies = $query->orderByDesc('created_at')->paginate(12)->withQueryString();

        // Operational metrics
        $metrics = [
            'total'                 => PolicyRequest::count(),
            'issued'                => PolicyRequest::where('status', 'ISSUED')->count(),
            'pending_confirmation'  => PolicyRequest::whereIn('status', ['PENDING_CONFIRMATION', 'NEEDS_RECONCILIATION'])->count(),
            'failed'                => PolicyRequest::where('status', 'FAILED')->count(),
            'validated'             => PolicyRequest::where('status', 'VALIDATED')->count(),
            'total_premium_issued'  => PolicyRequest::where('status', 'ISSUED')->sum('calculated_premium'),
        ];

        return view('dashboard.index', compact('policies', 'metrics'));
    }

    /**
     * Show form to create new policy request.
     */
    public function create(): View
    {
        return view('dashboard.create', [
            'plans'        => PremiumCalculator::supportedPlans(),
            'destinations' => PremiumCalculator::supportedDestinations(),
            'coverages'    => PremiumCalculator::supportedCoverages(),
        ]);
    }

    /**
     * Store new policy request from web form.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_request_id' => 'required|string|max:100',
            'agent_id'          => 'required|string|max:100',
            'customer_name'     => 'required|string|max:255',
            'date_of_birth'     => 'required|date|before:today',
            'destination'       => 'required|in:DOMESTIC,INTERNATIONAL',
            'trip_start'        => 'required|date',
            'trip_end'          => 'required|date|after_or_equal:trip_start',
            'plan_code'         => 'required|in:BASIC,PLUS,PREMIUM',
            'sum_insured'       => 'required|numeric|in:100000,300000,500000',
        ]);

        // Check idempotency
        $existing = PolicyRequest::where('agent_id', $validated['agent_id'])
            ->where('client_request_id', $validated['client_request_id'])
            ->first();

        if ($existing) {
            return redirect()->route('policies.show', $existing->id)
                ->with('info', "Policy request with client ID '{$existing->client_request_id}' already exists (idempotent lookup).");
        }

        // Calculate premium
        $calculation = $this->calculator->calculate(
            planCode: $validated['plan_code'],
            sumInsured: $validated['sum_insured'],
            destination: $validated['destination'],
            dateOfBirth: $validated['date_of_birth'],
            tripStart: $validated['trip_start'],
            tripEnd: $validated['trip_end']
        );

        $policy = PolicyRequest::create([
            'client_request_id'  => $validated['client_request_id'],
            'agent_id'           => $validated['agent_id'],
            'customer_name'      => $validated['customer_name'],
            'date_of_birth'      => $validated['date_of_birth'],
            'destination'        => $validated['destination'],
            'trip_start'         => $validated['trip_start'],
            'trip_end'           => $validated['trip_end'],
            'plan_code'          => $validated['plan_code'],
            'sum_insured'        => $validated['sum_insured'],
            'travel_days'        => $calculation['travel_days'],
            'calculated_premium' => $calculation['calculated_premium'],
            'status'             => 'RECEIVED',
        ]);

        $this->stateMachine->transition(
            $policy,
            'VALIDATED',
            'WEB_INTAKE',
            ['pricing_breakdown' => $calculation]
        );

        return redirect()->route('policies.show', $policy->id)
            ->with('success', "Policy request created and validated. Premium calculated: ₹{$calculation['calculated_premium']}");
    }

    /**
     * Show policy detail with comprehensive audit trail.
     */
    public function show(string $id): View
    {
        $policy = PolicyRequest::with([
            'stateHistory',
            'issuanceAttempts',
            'providerEvents',
        ])
        ->where('id', $id)
        ->orWhere('uuid', $id)
        ->firstOrFail();

        return view('dashboard.show', compact('policy'));
    }

    /**
     * Submit policy to insurer via web UI.
     */
    public function submit(Request $request, string $id): RedirectResponse
    {
        $policy = PolicyRequest::where('id', $id)->orWhere('uuid', $id)->firstOrFail();
        $scenario = $request->input('scenario', 'SUCCESS');

        try {
            $result = $this->issuanceService->submit($policy, $scenario);

            if (($result['status'] ?? '') === 'ISSUED') {
                $msg = "Policy successfully ISSUED by insurer! Policy Number: {$result['policy_number']}";
                return redirect()->route('policies.show', $policy->id)->with('success', $msg);
            } elseif (($result['status'] ?? '') === 'PENDING_CONFIRMATION') {
                $msg = "Submission received by insurer. State is PENDING_CONFIRMATION (Scenario: {$scenario}).";
                return redirect()->route('policies.show', $policy->id)->with('warning', $msg);
            } else {
                $msg = "Submission failed with retriable insurer error (Scenario: {$scenario}).";
                return redirect()->route('policies.show', $policy->id)->with('error', $msg);
            }
        } catch (IllegalStateTransitionException $e) {
            return redirect()->route('policies.show', $policy->id)->with('error', $e->getMessage());
        }
    }

    /**
     * Reconcile policy with insurer.
     */
    public function reconcile(string $id): RedirectResponse
    {
        $policy = PolicyRequest::where('id', $id)->orWhere('uuid', $id)->firstOrFail();
        $result = $this->issuanceService->reconcile($policy);

        if ($result['status'] === 'ISSUED') {
            return redirect()->route('policies.show', $policy->id)
                ->with('success', "Reconciliation confirmed! Policy was found issued with provider (Policy #: {$result['policy_number']}).");
        }

        return redirect()->route('policies.show', $policy->id)
            ->with('info', "Reconciliation executed: " . ($result['message'] ?? 'Status checked.'));
    }

    /**
     * Interactive Reliability Sandbox view.
     */
    public function sandbox(): View
    {
        $recentPolicies = PolicyRequest::orderByDesc('created_at')->limit(10)->get();
        $recentEvents = ProviderEvent::orderByDesc('created_at')->limit(10)->get();

        return view('dashboard.sandbox', compact('recentPolicies', 'recentEvents'));
    }
}
