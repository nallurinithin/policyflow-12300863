<?php

namespace App\Services;

use App\Exceptions\IllegalStateTransitionException;
use App\Models\PolicyRequest;
use App\Models\PolicyStateHistory;
use Illuminate\Support\Facades\DB;

class PolicyStateMachine
{
    /**
     * Complete transition matrix.
     * key = from_state, value = array of allowed to_states.
     */
    public const TRANSITIONS = [
        'RECEIVED'             => ['VALIDATED', 'FAILED'],
        'VALIDATED'            => ['SUBMITTED'],
        'SUBMITTED'            => ['PENDING_CONFIRMATION', 'ISSUED', 'FAILED'],
        'PENDING_CONFIRMATION' => ['ISSUED', 'FAILED', 'NEEDS_RECONCILIATION'],
        'FAILED'               => ['SUBMITTED'],
        'NEEDS_RECONCILIATION' => ['ISSUED', 'FAILED', 'SUBMITTED'],
        'ISSUED'               => [], // Terminal state. Never allows any transition.
    ];

    /**
     * Check if a transition is valid.
     */
    public function canTransition(string $fromState, string $toState): bool
    {
        $allowed = self::TRANSITIONS[$fromState] ?? [];
        return in_array($toState, $allowed, true);
    }

    /**
     * Transition a policy request to a new state and record the audit history.
     *
     * @param PolicyRequest $request The policy request to transition
     * @param string $toState Target state
     * @param string $trigger What initiated this transition (e.g., 'API_INTAKE', 'INSURER_CALLBACK', 'RETRY')
     * @param array $metadata Additional context to record in audit log
     * @return PolicyRequest
     * @throws IllegalStateTransitionException
     */
    public function transition(
        PolicyRequest $request,
        string $toState,
        string $trigger,
        array $metadata = []
    ): PolicyRequest {
        $fromState = $request->status;

        // If target is identical, it's a no-op idempotent call
        if ($fromState === $toState) {
            return $request;
        }

        if (!$this->canTransition($fromState, $toState)) {
            $reason = ($fromState === 'ISSUED')
                ? "Policies in ISSUED state are immutable and cannot be altered."
                : "Transition not permitted by state machine workflow.";
            throw new IllegalStateTransitionException($fromState, $toState, $reason);
        }

        return DB::transaction(function () use ($request, $fromState, $toState, $trigger, $metadata) {
            // Lock request record for update if persisted
            if ($request->exists) {
                $lockedRequest = PolicyRequest::where('id', $request->id)->lockForUpdate()->first();
                $fromState = $lockedRequest->status;

                // Re-verify after acquiring pessimistic lock
                if ($fromState === $toState) {
                    return $lockedRequest;
                }
                if (!$this->canTransition($fromState, $toState)) {
                    throw new IllegalStateTransitionException($fromState, $toState, "Transition state changed concurrently.");
                }
                $request = $lockedRequest;
            }

            $request->status = $toState;
            $request->save();

            PolicyStateHistory::create([
                'policy_request_id' => $request->id,
                'from_state'        => $fromState,
                'to_state'          => $toState,
                'trigger'           => $trigger,
                'metadata'          => !empty($metadata) ? json_encode($metadata) : null,
                'created_at'        => now(),
            ]);

            return $request;
        });
    }
}
