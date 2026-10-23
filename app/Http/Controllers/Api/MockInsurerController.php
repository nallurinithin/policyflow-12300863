<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MockInsurerPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MockInsurerController extends Controller
{
    /**
     * Submit policy to simulated insurer.
     *
     * Supported scenarios:
     * - SUCCESS: Generates policy_number and marks ISSUED.
     * - TEMPORARY_FAILURE: Simulates retriable upstream gateway error (HTTP 503).
     * - TIMEOUT_AFTER_ISSUE: Issues policy internally, but times out client call (HTTP 504).
     * - DELAYED_CALLBACK: Returns PENDING (HTTP 202); policy issued via later callback.
     */
    public function submit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_request_id' => 'required|string',
            'agent_id'          => 'required|string',
            'customer_name'     => 'required|string',
            'calculated_premium'=> 'required|numeric',
            'scenario'          => 'nullable|string|in:SUCCESS,TEMPORARY_FAILURE,TIMEOUT_AFTER_ISSUE,DELAYED_CALLBACK,DUPLICATE_CALLBACK',
        ]);

        $scenario = $validated['scenario'] ?? 'SUCCESS';
        $clientRequestId = $validated['client_request_id'];

        // Check if provider already has a policy for this client_request_id (idempotency at provider)
        $existing = MockInsurerPolicy::where('client_request_id', $clientRequestId)->first();
        if ($existing && $existing->status === 'ISSUED') {
            return response()->json([
                'status'             => 'ISSUED',
                'policy_number'      => $existing->policy_number,
                'provider_reference' => $existing->provider_reference,
                'message'            => 'Policy already exists with insurer (idempotent submission).',
            ], 200);
        }

        $providerReference = 'PRV-' . strtoupper(Str::random(10));

        switch ($scenario) {
            case 'SUCCESS':
                $policyNumber = 'POL-INS-' . date('Ymd') . '-' . strtoupper(Str::random(6));

                MockInsurerPolicy::create([
                    'provider_reference' => $providerReference,
                    'client_request_id'  => $clientRequestId,
                    'policy_number'      => $policyNumber,
                    'status'             => 'ISSUED',
                    'scenario'           => $scenario,
                    'payload'            => $validated,
                ]);

                return response()->json([
                    'status'             => 'ISSUED',
                    'policy_number'      => $policyNumber,
                    'provider_reference' => $providerReference,
                    'message'            => 'Policy successfully issued by insurer.',
                ], 200);

            case 'TEMPORARY_FAILURE':
                MockInsurerPolicy::create([
                    'provider_reference' => $providerReference,
                    'client_request_id'  => $clientRequestId,
                    'status'             => 'FAILED',
                    'scenario'           => $scenario,
                    'payload'            => $validated,
                ]);

                return response()->json([
                    'status'             => 'FAILED',
                    'error_code'         => 'ERR_PROVIDER_UNAVAILABLE',
                    'error_message'      => 'Insurer upstream system unavailable (temporary failure).',
                    'retriable'          => true,
                    'provider_reference' => $providerReference,
                ], 503);

            case 'TIMEOUT_AFTER_ISSUE':
                // Policy was created and issued internally at provider, but connection drops
                $policyNumber = 'POL-INS-' . date('Ymd') . '-' . strtoupper(Str::random(6));

                MockInsurerPolicy::create([
                    'provider_reference' => $providerReference,
                    'client_request_id'  => $clientRequestId,
                    'policy_number'      => $policyNumber,
                    'status'             => 'ISSUED',
                    'scenario'           => $scenario,
                    'payload'            => $validated,
                ]);

                return response()->json([
                    'status'             => 'TIMEOUT',
                    'error_code'         => 'ERR_CONNECTION_TIMED_OUT',
                    'error_message'      => 'HTTP request timed out waiting for insurer response.',
                    'provider_reference' => $providerReference,
                ], 504);

            case 'DELAYED_CALLBACK':
            case 'DUPLICATE_CALLBACK':
                // Provider accepts the request, returns PENDING status
                MockInsurerPolicy::create([
                    'provider_reference' => $providerReference,
                    'client_request_id'  => $clientRequestId,
                    'status'             => 'PENDING',
                    'scenario'           => $scenario,
                    'payload'            => $validated,
                ]);

                return response()->json([
                    'status'             => 'PENDING',
                    'provider_reference' => $providerReference,
                    'message'            => 'Request accepted by insurer; policy issuance will be confirmed via callback.',
                ], 202);

            default:
                return response()->json(['error' => 'Unknown scenario'], 400);
        }
    }

    /**
     * Provider status lookup endpoint for reconciliation.
     *
     * Used when a timeout or delayed response occurred to verify whether
     * the insurer actually created the policy before attempting any re-issuance.
     */
    public function getStatus(string $reference): JsonResponse
    {
        $policy = MockInsurerPolicy::where('provider_reference', $reference)
            ->orWhere('client_request_id', $reference)
            ->first();

        if (!$policy) {
            return response()->json([
                'success' => false,
                'message' => "No provider record found for reference '{$reference}'.",
            ], 404);
        }

        return response()->json([
            'success'            => true,
            'provider_reference' => $policy->provider_reference,
            'client_request_id'  => $policy->client_request_id,
            'status'             => $policy->status,
            'policy_number'      => $policy->policy_number,
            'scenario'           => $policy->scenario,
            'created_at'         => $policy->created_at?->toIso8601String(),
        ], 200);
    }

    /**
     * Helper to trigger a simulated asynchronous callback from the insurer.
     * Can simulate single or duplicate callbacks (same event_id delivered multiple times).
     */
    public function triggerCallback(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider_reference' => 'required|string',
            'event_type'         => 'nullable|string|in:POLICY_ISSUED,POLICY_REJECTED',
            'duplicate_count'    => 'nullable|integer|min:1|max:5',
        ]);

        $reference = $validated['provider_reference'];
        $policy = MockInsurerPolicy::where('provider_reference', $reference)->first();

        if (!$policy) {
            return response()->json(['error' => 'Provider reference not found'], 404);
        }

        $eventType = $validated['event_type'] ?? 'POLICY_ISSUED';
        $policyNumber = $policy->policy_number ?? ('POL-INS-' . date('Ymd') . '-' . strtoupper(Str::random(6)));

        if ($eventType === 'POLICY_ISSUED' && empty($policy->policy_number)) {
            $policy->update([
                'status'        => 'ISSUED',
                'policy_number' => $policyNumber,
            ]);
        }

        // Shared event ID across duplicate emissions to test idempotency
        $eventId = 'EVT-' . strtoupper(Str::random(12));
        $duplicateCount = (int) ($validated['duplicate_count'] ?? 1);

        $results = [];
        $callbackController = app(CallbackController::class);

        for ($i = 1; $i <= $duplicateCount; $i++) {
            $callbackRequest = Request::create('/api/mock-insurer/callback', 'POST', [
                'event_id'           => $eventId,
                'event_type'         => $eventType,
                'provider_reference' => $reference,
                'policy_number'      => $policyNumber,
                'payload'            => [
                    'attempt'     => $i,
                    'emitted_at'  => now()->toIso8601String(),
                ],
            ]);

            $callbackResponse = $callbackController->handle($callbackRequest);
            $results[] = [
                'delivery_attempt' => $i,
                'status_code'      => $callbackResponse->getStatusCode(),
                'response'         => json_decode($callbackResponse->getContent(), true),
            ];
        }

        return response()->json([
            'success'         => true,
            'event_id'        => $eventId,
            'total_delivered' => $duplicateCount,
            'deliveries'      => $results,
        ], 200);
    }
}
