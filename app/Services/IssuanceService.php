<?php

namespace App\Services;

use App\Exceptions\IllegalStateTransitionException;
use App\Http\Controllers\Api\MockInsurerController;
use App\Models\IssuanceAttempt;
use App\Models\MockInsurerPolicy;
use App\Models\PolicyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class IssuanceService
{
    public function __construct(
        protected PolicyStateMachine $stateMachine,
        protected MockInsurerController $mockInsurer
    ) {}

    /**
     * Submit or retry policy request issuance to the external insurer.
     *
     * Fault-tolerant:
     * 1. If policy is already ISSUED, immediately returns existing policy (never re-issues).
     * 2. If policy is in PENDING_CONFIRMATION, reconciles with insurer before attempting any re-issuance.
     * 3. Records attempt details and enforces all state transitions atomically.
     */
    public function submit(PolicyRequest $policyRequest, string $scenario = 'SUCCESS'): array
    {
        // 1. Guard against re-issuing an already issued policy
        if ($policyRequest->status === 'ISSUED') {
            return [
                'status'             => 'ISSUED',
                'policy_number'      => $policyRequest->policy_number,
                'provider_reference' => $policyRequest->provider_reference,
                'already_issued'     => true,
                'message'            => 'Policy has already been issued. Re-submission rejected to prevent duplicate issuance.',
            ];
        }

        // 2. If in PENDING_CONFIRMATION or NEEDS_RECONCILIATION, reconcile first!
        // This solves Reliability Case 2 (Timeout after success): do not blindly issue again.
        if (in_array($policyRequest->status, ['PENDING_CONFIRMATION', 'NEEDS_RECONCILIATION'])) {
            $reconciliation = $this->reconcile($policyRequest);
            if ($reconciliation['status'] === 'ISSUED') {
                return $reconciliation;
            }
        }

        // 3. Verify valid state for submission
        if (!in_array($policyRequest->status, ['VALIDATED', 'FAILED', 'NEEDS_RECONCILIATION'])) {
            throw new IllegalStateTransitionException(
                $policyRequest->status,
                'SUBMITTED',
                "Policy request cannot be submitted from current state."
            );
        }

        $attemptNumber = $policyRequest->getLatestAttemptNumber() + 1;

        // 4. Transition to SUBMITTED
        $this->stateMachine->transition(
            $policyRequest,
            'SUBMITTED',
            'SUBMIT_ATTEMPT_' . $attemptNumber,
            [
                'attempt'  => $attemptNumber,
                'scenario' => $scenario,
            ]
        );

        // 5. Invoke Mock Insurer
        $simulatedRequest = Request::create('/api/mock-insurer/submit', 'POST', [
            'client_request_id'  => $policyRequest->client_request_id,
            'agent_id'           => $policyRequest->agent_id,
            'customer_name'      => $policyRequest->customer_name,
            'calculated_premium' => (float) $policyRequest->calculated_premium,
            'scenario'           => $scenario,
        ]);

        $response = $this->mockInsurer->submit($simulatedRequest);
        $statusCode = $response->getStatusCode();
        $responseData = json_decode($response->getContent(), true);

        // 6. Handle response based on insurer result
        return DB::transaction(function () use ($policyRequest, $attemptNumber, $scenario, $statusCode, $responseData) {
            $providerRef = $responseData['provider_reference'] ?? null;

            if ($providerRef) {
                $policyRequest->provider_reference = $providerRef;
                $policyRequest->save();
            }

            // Create issuance attempt record
            $attempt = IssuanceAttempt::create([
                'policy_request_id'  => $policyRequest->id,
                'attempt_number'     => $attemptNumber,
                'scenario'           => $scenario,
                'provider_reference' => $providerRef,
                'response_status'    => (string) $statusCode,
                'response_payload'   => json_encode($responseData),
                'error_details'      => ($statusCode >= 400) ? json_encode($responseData) : null,
            ]);

            // Scenario SUCCESS: Insurer issued policy immediately
            if ($statusCode === 200 && ($responseData['status'] ?? '') === 'ISSUED') {
                $policyNumber = $responseData['policy_number'];
                $policyRequest->policy_number = $policyNumber;
                $policyRequest->save();

                $this->stateMachine->transition(
                    $policyRequest,
                    'ISSUED',
                    'ISSUANCE_SUCCESS',
                    [
                        'attempt_number'     => $attemptNumber,
                        'policy_number'      => $policyNumber,
                        'provider_reference' => $providerRef,
                    ]
                );

                return [
                    'status'             => 'ISSUED',
                    'policy_number'      => $policyNumber,
                    'provider_reference' => $providerRef,
                    'attempt_number'     => $attemptNumber,
                    'message'            => 'Policy successfully issued by insurer.',
                ];
            }

            // Scenario DELAYED_CALLBACK: Insurer accepted and returned PENDING
            if ($statusCode === 202 || ($responseData['status'] ?? '') === 'PENDING') {
                $this->stateMachine->transition(
                    $policyRequest,
                    'PENDING_CONFIRMATION',
                    'INSURER_PENDING',
                    [
                        'attempt_number'     => $attemptNumber,
                        'provider_reference' => $providerRef,
                    ]
                );

                return [
                    'status'             => 'PENDING_CONFIRMATION',
                    'provider_reference' => $providerRef,
                    'attempt_number'     => $attemptNumber,
                    'message'            => 'Submission accepted; awaiting provider callback confirmation.',
                ];
            }

            // Scenario TIMEOUT_AFTER_ISSUE: Request timed out at network layer
            if ($statusCode === 504 || ($responseData['status'] ?? '') === 'TIMEOUT') {
                $this->stateMachine->transition(
                    $policyRequest,
                    'PENDING_CONFIRMATION',
                    'INSURER_TIMEOUT',
                    [
                        'attempt_number'     => $attemptNumber,
                        'provider_reference' => $providerRef,
                        'note'               => 'Insurer call timed out. Reconciliation required before re-attempt.',
                    ]
                );

                return [
                    'status'             => 'PENDING_CONFIRMATION',
                    'provider_reference' => $providerRef,
                    'attempt_number'     => $attemptNumber,
                    'retriable'          => true,
                    'message'            => 'Call timed out waiting for insurer. Marked PENDING_CONFIRMATION for reconciliation.',
                ];
            }

            // Scenario TEMPORARY_FAILURE: Retriable upstream failure
            $this->stateMachine->transition(
                $policyRequest,
                'FAILED',
                'ISSUANCE_FAILURE',
                [
                    'attempt_number'     => $attemptNumber,
                    'provider_reference' => $providerRef,
                    'error'              => $responseData['error_message'] ?? 'Insurer temporary failure',
                ]
            );

            return [
                'status'             => 'FAILED',
                'provider_reference' => $providerRef,
                'attempt_number'     => $attemptNumber,
                'retriable'          => true,
                'error'              => $responseData['error_message'] ?? 'Insurer temporary failure',
            ];
        });
    }

    /**
     * Reconcile policy request with the insurer status lookup API.
     *
     * Used to resolve timeouts, pending states, or stale requests safely.
     */
    public function reconcile(PolicyRequest $policyRequest): array
    {
        $reference = $policyRequest->provider_reference ?: $policyRequest->client_request_id;
        $lookupResponse = $this->mockInsurer->getStatus($reference);

        if ($lookupResponse->getStatusCode() !== 200) {
            return [
                'reconciled' => false,
                'status'     => $policyRequest->status,
                'message'    => 'Unable to find provider status for reference ' . $reference,
            ];
        }

        $providerData = json_decode($lookupResponse->getContent(), true);
        $providerStatus = $providerData['status'] ?? null;

        if ($providerStatus === 'ISSUED') {
            $policyNumber = $providerData['policy_number'];
            $policyRequest->policy_number = $policyNumber;
            if (empty($policyRequest->provider_reference) && !empty($providerData['provider_reference'])) {
                $policyRequest->provider_reference = $providerData['provider_reference'];
            }
            $policyRequest->save();

            if ($policyRequest->status !== 'ISSUED') {
                $this->stateMachine->transition(
                    $policyRequest,
                    'ISSUED',
                    'RECONCILIATION_CONFIRMED',
                    [
                        'provider_status' => 'ISSUED',
                        'policy_number'   => $policyNumber,
                    ]
                );
            }

            return [
                'reconciled'         => true,
                'status'             => 'ISSUED',
                'policy_number'      => $policyNumber,
                'provider_reference' => $policyRequest->provider_reference,
                'message'            => 'Reconciliation confirmed policy was already issued by provider.',
            ];
        }

        if ($providerStatus === 'FAILED') {
            if ($policyRequest->status !== 'FAILED') {
                $this->stateMachine->transition(
                    $policyRequest,
                    'FAILED',
                    'RECONCILIATION_FAILED',
                    ['provider_status' => 'FAILED']
                );
            }

            return [
                'reconciled' => true,
                'status'     => 'FAILED',
                'message'    => 'Reconciliation confirmed provider recorded a failure.',
            ];
        }

        return [
            'reconciled' => true,
            'status'     => $policyRequest->status,
            'message'    => "Provider status is currently '{$providerStatus}'. No state change needed.",
        ];
    }
}
