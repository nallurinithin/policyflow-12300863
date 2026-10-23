<?php

namespace Tests\Feature;

use App\Models\PolicyRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssuanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function createValidatedPolicy(string $clientRequestId = 'AGT-TEST-001'): PolicyRequest
    {
        $response = $this->postJson('/api/policy-requests', [
            'client_request_id' => $clientRequestId,
            'agent_id'          => 'AGENT-01',
            'customer_name'     => 'Alice Wonderland',
            'date_of_birth'     => '1995-05-15',
            'destination'       => 'INTERNATIONAL',
            'trip_start'        => '2026-11-10',
            'trip_end'          => '2026-11-14',
            'plan_code'         => 'PLUS',
            'sum_insured'       => 300000,
        ]);

        $response->assertStatus(201);
        return PolicyRequest::find($response->json('data.id'));
    }

    public function test_successful_issuance_transitions_to_issued_with_policy_number(): void
    {
        $policy = $this->createValidatedPolicy('REQ-SUCCESS-01');

        $response = $this->postJson("/api/policy-requests/{$policy->id}/submit", [
            'scenario' => 'SUCCESS',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'status' => 'ISSUED',
                ],
                'policy'  => [
                    'status' => 'ISSUED',
                ],
            ]);

        $policy->refresh();
        $this->assertSame('ISSUED', $policy->status);
        $this->assertNotEmpty($policy->policy_number);
        $this->assertStringStartsWith('POL-INS-', $policy->policy_number);
        $this->assertNotEmpty($policy->provider_reference);

        // Verify state history
        $this->assertDatabaseHas('policy_state_history', [
            'policy_request_id' => $policy->id,
            'from_state'        => 'SUBMITTED',
            'to_state'          => 'ISSUED',
            'trigger'           => 'ISSUANCE_SUCCESS',
        ]);

        // Verify issuance attempt logged
        $this->assertDatabaseHas('issuance_attempts', [
            'policy_request_id' => $policy->id,
            'attempt_number'    => 1,
            'scenario'          => 'SUCCESS',
            'response_status'   => '200',
        ]);
    }

    public function test_cannot_re_submit_already_issued_policy(): void
    {
        $policy = $this->createValidatedPolicy('REQ-ONCE-01');

        // First submit: succeeds and becomes ISSUED
        $this->postJson("/api/policy-requests/{$policy->id}/submit", ['scenario' => 'SUCCESS']);
        $originalPolicyNumber = $policy->fresh()->policy_number;

        // Second submit: must NOT generate a new policy number or change state
        $secondResponse = $this->postJson("/api/policy-requests/{$policy->id}/submit", ['scenario' => 'SUCCESS']);

        $secondResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'already_issued' => true,
                    'policy_number'  => $originalPolicyNumber,
                ],
            ]);

        $this->assertSame($originalPolicyNumber, $policy->fresh()->policy_number);
    }

    public function test_temporary_failure_allows_safe_retry_without_losing_premium(): void
    {
        $policy = $this->createValidatedPolicy('REQ-RETRY-01');
        $originalPremium = $policy->calculated_premium;

        // First attempt: TEMPORARY_FAILURE
        $res1 = $this->postJson("/api/policy-requests/{$policy->id}/submit", [
            'scenario' => 'TEMPORARY_FAILURE',
        ]);

        $res1->assertStatus(200);
        $policy->refresh();
        $this->assertSame('FAILED', $policy->status);
        $this->assertSame(1, $policy->getLatestAttemptNumber());

        // Ensure calculated premium is untouched
        $this->assertEquals($originalPremium, $policy->calculated_premium);

        // Second attempt (retry): SUCCESS
        $res2 = $this->postJson("/api/policy-requests/{$policy->id}/submit", [
            'scenario' => 'SUCCESS',
        ]);

        $res2->assertStatus(200);
        $policy->refresh();
        $this->assertSame('ISSUED', $policy->status);
        $this->assertSame(2, $policy->getLatestAttemptNumber());
        $this->assertEquals($originalPremium, $policy->calculated_premium);
        $this->assertNotEmpty($policy->policy_number);
    }

    public function test_delayed_callback_flow(): void
    {
        $policy = $this->createValidatedPolicy('REQ-DELAYED-01');

        // Submit with DELAYED_CALLBACK -> becomes PENDING_CONFIRMATION
        $submitResponse = $this->postJson("/api/policy-requests/{$policy->id}/submit", [
            'scenario' => 'DELAYED_CALLBACK',
        ]);

        $submitResponse->assertStatus(200);
        $policy->refresh();
        $this->assertSame('PENDING_CONFIRMATION', $policy->status);
        $this->assertNotEmpty($policy->provider_reference);

        // Simulate insurer sending the callback later
        $callbackResponse = $this->postJson('/api/mock-insurer/callback', [
            'event_id'           => 'EVT-CALLBACK-001',
            'event_type'         => 'POLICY_ISSUED',
            'provider_reference' => $policy->provider_reference,
            'policy_number'      => 'POL-INS-DELAYED-999',
        ]);

        $callbackResponse->assertStatus(200)
            ->assertJson([
                'success'      => true,
                'is_duplicate' => false,
            ]);

        $policy->refresh();
        $this->assertSame('ISSUED', $policy->status);
        $this->assertSame('POL-INS-DELAYED-999', $policy->policy_number);
    }
}
