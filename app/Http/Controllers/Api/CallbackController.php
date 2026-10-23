<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PolicyRequest;
use App\Models\ProviderEvent;
use App\Services\PolicyStateMachine;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CallbackController extends Controller
{
    public function __construct(
        protected PolicyStateMachine $stateMachine
    ) {}

    /**
     * Handle asynchronous callbacks from the insurer.
     *
     * Idempotent: Repeated deliveries of the same event_id will NOT re-process
     * or cause duplicate state transitions.
     */
    public function handle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_id'           => 'required|string|max:100',
            'event_type'         => 'required|string|in:POLICY_ISSUED,POLICY_REJECTED',
            'provider_reference' => 'required|string|max:100',
            'policy_number'      => 'nullable|string|max:100',
            'payload'            => 'nullable|array',
        ]);

        $eventId = $validated['event_id'];
        $providerRef = $validated['provider_reference'];
        $eventType = $validated['event_type'];

        // 1. Idempotency Check: Have we already seen this event_id?
        $existingEvent = ProviderEvent::where('event_id', $eventId)->first();
        if ($existingEvent) {
            return response()->json([
                'success'           => true,
                'is_duplicate'      => true,
                'message'           => 'Event already processed (idempotent callback acknowledged).',
                'event_id'          => $eventId,
                'processed_at'      => $existingEvent->processed_at?->toIso8601String(),
            ], 200);
        }

        // 2. Find matching PolicyRequest by provider_reference
        $policyRequest = PolicyRequest::where('provider_reference', $providerRef)->first();

        try {
            DB::transaction(function () use ($validated, $eventId, $providerRef, $eventType, $policyRequest) {
                // Record the incoming event
                $providerEvent = ProviderEvent::create([
                    'event_id'           => $eventId,
                    'policy_request_id'  => $policyRequest?->id,
                    'event_type'         => $eventType,
                    'provider_reference' => $providerRef,
                    'policy_number'      => $validated['policy_number'] ?? null,
                    'payload'            => !empty($validated['payload']) ? json_encode($validated['payload']) : null,
                    'processed'          => false,
                ]);

                if ($policyRequest) {
                    // Pessimistically lock policy request for state update
                    $locked = PolicyRequest::where('id', $policyRequest->id)->lockForUpdate()->first();

                    if ($eventType === 'POLICY_ISSUED') {
                        if ($locked->status !== 'ISSUED') {
                            $locked->policy_number = $validated['policy_number'] ?? ('POL-INS-' . date('Ymd') . '-' . strtoupper(substr(md5($eventId), 0, 6)));
                            $locked->save();

                            // Transition to ISSUED
                            $this->stateMachine->transition(
                                $locked,
                                'ISSUED',
                                'PROVIDER_CALLBACK',
                                [
                                    'event_id'           => $eventId,
                                    'provider_reference' => $providerRef,
                                    'policy_number'      => $locked->policy_number,
                                ]
                            );
                        }
                    } elseif ($eventType === 'POLICY_REJECTED') {
                        if ($locked->status !== 'ISSUED') {
                            $this->stateMachine->transition(
                                $locked,
                                'FAILED',
                                'PROVIDER_CALLBACK',
                                [
                                    'event_id'           => $eventId,
                                    'provider_reference' => $providerRef,
                                    'reason'             => 'Provider rejected policy issuance',
                                ]
                            );
                        }
                    }

                    $providerEvent->update([
                        'processed'    => true,
                        'processed_at' => now(),
                    ]);
                }
            });

            return response()->json([
                'success'      => true,
                'is_duplicate' => false,
                'message'      => 'Callback processed successfully.',
                'event_id'     => $eventId,
                'event_type'   => $eventType,
            ], 200);

        } catch (QueryException $e) {
            // Concurrent race on event_id unique constraint
            if ($e->errorInfo[1] == 1062 || str_contains($e->getMessage(), 'Duplicate entry') || $e->getCode() == 23000) {
                return response()->json([
                    'success'      => true,
                    'is_duplicate' => true,
                    'message'      => 'Concurrent duplicate callback detected and safely acknowledged.',
                    'event_id'     => $eventId,
                ], 200);
            }

            throw $e;
        }
    }
}
