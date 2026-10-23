<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePolicyRequestRequest;
use App\Models\PolicyRequest;
use App\Services\PolicyStateMachine;
use App\Services\PremiumCalculator;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PolicyRequestController extends Controller
{
    public function __construct(
        protected PremiumCalculator $calculator,
        protected PolicyStateMachine $stateMachine
    ) {}

    /**
     * Intake endpoint: Create a new policy request or return existing if duplicate.
     *
     * Returns HTTP 201 for newly created requests.
     * Returns HTTP 200 for idempotent re-submissions (same agent_id + client_request_id).
     */
    public function store(StorePolicyRequestRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $agentId = $validated['agent_id'];
        $clientRequestId = $validated['client_request_id'];

        // 1. Idempotency pre-check
        $existing = PolicyRequest::where('agent_id', $agentId)
            ->where('client_request_id', $clientRequestId)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Policy request already exists (idempotent response).',
                'is_duplicate' => true,
                'data' => $this->formatPolicyRequest($existing),
            ], 200);
        }

        // 2. Calculate deterministic premium
        $calculation = $this->calculator->calculate(
            planCode: $validated['plan_code'],
            sumInsured: $validated['sum_insured'],
            destination: $validated['destination'],
            dateOfBirth: $validated['date_of_birth'],
            tripStart: $validated['trip_start'],
            tripEnd: $validated['trip_end']
        );

        // 3. Atomically persist record and transition state
        try {
            $policyRequest = DB::transaction(function () use ($validated, $calculation) {
                $record = PolicyRequest::create([
                    'client_request_id' => $validated['client_request_id'],
                    'agent_id'          => $validated['agent_id'],
                    'customer_name'     => $validated['customer_name'],
                    'date_of_birth'     => $validated['date_of_birth'],
                    'destination'       => $validated['destination'],
                    'trip_start'        => $validated['trip_start'],
                    'trip_end'          => $validated['trip_end'],
                    'plan_code'         => $validated['plan_code'],
                    'sum_insured'       => $validated['sum_insured'],
                    'travel_days'       => $calculation['travel_days'],
                    'calculated_premium'=> $calculation['calculated_premium'],
                    'status'            => 'RECEIVED',
                ]);

                // Transition: RECEIVED -> VALIDATED
                $this->stateMachine->transition(
                    $record,
                    'VALIDATED',
                    'API_INTAKE',
                    [
                        'pricing_breakdown' => $calculation,
                    ]
                );

                return $record;
            });

            return response()->json([
                'success' => true,
                'message' => 'Policy request created and validated successfully.',
                'is_duplicate' => false,
                'data' => $this->formatPolicyRequest($policyRequest->fresh(), $calculation),
            ], 201);

        } catch (QueryException $e) {
            // Handle race condition: another concurrent process inserted between our pre-check and create
            if ($e->errorInfo[1] == 1062 || str_contains($e->getMessage(), 'Duplicate entry') || $e->getCode() == 23000) {
                $duplicate = PolicyRequest::where('agent_id', $agentId)
                    ->where('client_request_id', $clientRequestId)
                    ->first();

                if ($duplicate) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Policy request already exists (concurrent duplicate resolved).',
                        'is_duplicate' => true,
                        'data' => $this->formatPolicyRequest($duplicate),
                    ], 200);
                }
            }

            throw $e;
        }
    }

    /**
     * Retrieve status, premium, identifiers, and complete audit trail.
     */
    public function show(string $id): JsonResponse
    {
        $policyRequest = PolicyRequest::with([
            'stateHistory',
            'issuanceAttempts',
            'providerEvents',
        ])
        ->where('id', $id)
        ->orWhere('uuid', $id)
        ->first();

        if (!$policyRequest) {
            return response()->json([
                'success' => false,
                'message' => "Policy request '{$id}' not found.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatPolicyRequest($policyRequest, null, true),
        ], 200);
    }

    /**
     * List policy requests with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PolicyRequest::query();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('agent_id')) {
            $query->where('agent_id', $request->query('agent_id'));
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
                  ->orWhere('uuid', 'like', "%{$search}%");
            });
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 15)));
        $paginated = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => array_map(fn($item) => $this->formatPolicyRequest($item), $paginated->items()),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'last_page'    => $paginated->lastPage(),
            ],
        ], 200);
    }

    /**
     * Format a PolicyRequest model for JSON response.
     */
    private function formatPolicyRequest(PolicyRequest $policy, ?array $pricing = null, bool $includeDetails = false): array
    {
        $data = [
            'id'                 => $policy->id,
            'uuid'               => $policy->uuid,
            'client_request_id'  => $policy->client_request_id,
            'agent_id'           => $policy->agent_id,
            'customer_name'      => $policy->customer_name,
            'date_of_birth'      => $policy->date_of_birth?->toDateString(),
            'destination'        => $policy->destination,
            'trip_start'         => $policy->trip_start?->toDateString(),
            'trip_end'           => $policy->trip_end?->toDateString(),
            'travel_days'        => $policy->travel_days,
            'plan_code'          => $policy->plan_code,
            'sum_insured'        => (float) $policy->sum_insured,
            'calculated_premium' => (float) $policy->calculated_premium,
            'status'             => $policy->status,
            'policy_number'      => $policy->policy_number,
            'provider_reference' => $policy->provider_reference,
            'created_at'         => $policy->created_at?->toIso8601String(),
            'updated_at'         => $policy->updated_at?->toIso8601String(),
        ];

        if ($pricing) {
            $data['pricing_breakdown'] = $pricing;
        }

        if ($includeDetails) {
            $data['state_history'] = $policy->stateHistory->map(fn($h) => [
                'id'         => $h->id,
                'from_state' => $h->from_state,
                'to_state'   => $h->to_state,
                'trigger'    => $h->trigger,
                'metadata'   => is_string($h->metadata) ? json_decode($h->metadata, true) : $h->metadata,
                'created_at' => $h->created_at?->toIso8601String(),
            ]);

            $data['issuance_attempts'] = $policy->issuanceAttempts->map(fn($a) => [
                'attempt_number'     => $a->attempt_number,
                'scenario'           => $a->scenario,
                'provider_reference' => $a->provider_reference,
                'response_status'    => $a->response_status,
                'error_details'      => is_string($a->error_details) ? json_decode($a->error_details, true) : $a->error_details,
                'created_at'         => $a->created_at?->toIso8601String(),
            ]);

            $data['provider_events'] = $policy->providerEvents->map(fn($e) => [
                'event_id'           => $e->event_id,
                'event_type'         => $e->event_type,
                'provider_reference' => $e->provider_reference,
                'processed'          => $e->processed,
                'processed_at'       => $e->processed_at?->toIso8601String(),
                'created_at'         => $e->created_at?->toIso8601String(),
            ]);
        }

        return $data;
    }
}
