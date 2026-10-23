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
     */
    public function transition(
        PolicyRequest $request,
        string $toState,
        string $trigger,
        array $metadata = []
    ): PolicyRequest {
        return DB::transaction(function () use ($request, $toState, $trigger, $metadata) {
            if ($request->exists) {
                // Lock request record for update
                $lockedRequest = PolicyRequest::where('id', $request->id)->lockForUpdate()->first();
                $fromState = $lockedRequest->status;

                // If target is identical, idempotent no-op
                if ($fromState === $toState) {
                    $request->status = $toState;
                    return $request;
                }

                if (!$this->canTransition($fromState, $toState)) {
                    $reason = ($fromState === 'ISSUED')
                        ? "Policies in ISSUED state are immutable and cannot be altered."
                        : "Transition not permitted by state machine workflow.";
                    throw new IllegalStateTransitionException($fromState, $toState, $reason);
                }

                $lockedRequest->status = $toState;
                $lockedRequest->save();
                $request->status = $toState;
            } else {
                $fromState = $request->status;
                if ($fromState !== $toState && !$this->canTransition($fromState, $toState)) {
                    throw new IllegalStateTransitionException($fromState, $toState, "Transition not permitted.");
                }
                $request->status = $toState;
                $request->save();
            }

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
