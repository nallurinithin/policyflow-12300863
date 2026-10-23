<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\IllegalStateTransitionException;
use App\Http\Controllers\Controller;
use App\Models\PolicyRequest;
use App\Services\IssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IssuanceController extends Controller
{
    public function __construct(
        protected IssuanceService $issuanceService
    ) {}

    /**
     * Start or safely resume issuance for a policy request.
     */
    public function submit(Request $request, string $id): JsonResponse
    {
        $policyRequest = PolicyRequest::where('id', $id)->orWhere('uuid', $id)->first();

        if (!$policyRequest) {
            return response()->json([
                'success' => false,
                'message' => "Policy request '{$id}' not found.",
            ], 404);
        }

        $scenario = $request->input('scenario', 'SUCCESS');

        try {
            $result = $this->issuanceService->submit($policyRequest, $scenario);

            return response()->json([
                'success' => true,
                'data'    => $result,
                'policy'  => [
                    'id'                 => $policyRequest->id,
                    'uuid'               => $policyRequest->uuid,
                    'client_request_id'  => $policyRequest->client_request_id,
                    'status'             => $policyRequest->fresh()->status,
                    'policy_number'      => $policyRequest->fresh()->policy_number,
                    'provider_reference' => $policyRequest->fresh()->provider_reference,
                ],
            ], 200);

        } catch (IllegalStateTransitionException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'status'  => $policyRequest->status,
            ], 422);
        }
    }

    /**
     * Reconcile policy request with the insurer status API.
     */
    public function reconcile(string $id): JsonResponse
    {
        $policyRequest = PolicyRequest::where('id', $id)->orWhere('uuid', $id)->first();

        if (!$policyRequest) {
            return response()->json([
                'success' => false,
                'message' => "Policy request '{$id}' not found.",
            ], 404);
        }

        $result = $this->issuanceService->reconcile($policyRequest);

        return response()->json([
            'success' => true,
            'data'    => $result,
            'policy'  => [
                'id'                 => $policyRequest->id,
                'uuid'               => $policyRequest->uuid,
                'status'             => $policyRequest->fresh()->status,
                'policy_number'      => $policyRequest->fresh()->policy_number,
                'provider_reference' => $policyRequest->fresh()->provider_reference,
            ],
        ], 200);
    }
}
