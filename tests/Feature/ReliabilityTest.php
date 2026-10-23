<?php

namespace Tests\Feature;

use App\Models\PolicyRequest;
use App\Models\ProviderEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReliabilityTest extends TestCase
{
    use RefreshDatabase;

    private array $basePayload = [
        'client_request_id' => 'RELIABILITY-REQ-001',
        'agent_id'          => 'AGENT-SAFE-01',
        'customer_name'     => 'Jane Reliability',
        'date_of_birth'     => '1992-03-14',
        'destination'       => 'DOMESTIC',
        'trip_start'        => '2026-10-01',
        'trip_end'          => '2026-10-07',
        'plan_code'         => 'BASIC',
        'sum_insured'       => 100000,
    ];

    /**
     * Reliability Case 1: Duplicate intake
     * The same client_request_id is submitted repeatedly.
     * System must return 201 on first creation, 200 on repeated submissions,
     * and persist exactly ONE logical record.
     */
    public function test_case_1_duplicate_intake_creates_exactly_one_record(): void
    {
        // First submission -> 201 Created
        $res1 = $this->postJson('/api/policy-requests', $this->basePayload);
        $res1->assertStatus(201);
        $id1 = $res1->json('data.id');

        // Repeated submissions (e.g. 5 duplicate attempts)
        for ($i = 0; $i < 4; $i++) {
            $dupRes = $this->postJson('/api/policy-requests', $this->basePayload);
            $dupRes->assertStatus(200);
            $this->assertSame($id1, $dupRes->json('data.id'));
            $this->assertTrue($dupRes->json('is_duplicate'));
        }

        // Database must have exactly one record
        $count = PolicyRequest::where('agent_id', 'AGENT-SAFE-01')
            ->where('client_request_id', 'RELIABILITY-REQ-001')
            ->count();

        $this->assertSame(1, $count);
    }

    /**
     * Reliability Case 2: Timeout after success
     * The insurer issued a policy internally, but the HTTP client timed out.
     * Before re-submitting, reconciliation recovers the issued policy without duplicate issuance.
     */
    public function test_case_2_timeout_after_success_resolved_via_reconciliation(): void
    {
        // 1. Create validated request
        $intake = $this->postJson('/api/policy-requests', $this->basePayload);
        $id = $intake->json('data.id');
        $policy = PolicyRequest::find($id);

        // 2. Submit with TIMEOUT_AFTER_ISSUE scenario
        $submitRes = $this->postJson("/api/policy-requests/{$id}/submit", [
            'scenario' => 'TIMEOUT_AFTER_ISSUE',
        ]);

        $submitRes->assertStatus(200);
        $policy->refresh();

        // State is PENDING_CONFIRMATION
        $this->assertSame('PENDING_CONFIRMATION', $policy->status);
        $this->assertNotEmpty($policy->provider_reference);

        // 3. Instead of blindly re-submitting, reconcile with provider status!
        $reconcileRes = $this->postJson("/api/policy-requests/{$id}/reconcile");
        $reconcileRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'reconciled' => true,
                    'status'     => 'ISSUED',
                ],
            ]);

        $policy->refresh();
        $this->assertSame('ISSUED', $policy->status);
        $this->assertNotEmpty($policy->policy_number);

        // Verify that subsequent submit is safely rejected
        $repeatSubmit = $this->postJson("/api/policy-requests/{$id}/submit", ['scenario' => 'SUCCESS']);
        $repeatSubmit->assertStatus(200)
            ->assertJson([
                'data' => [
                    'already_issued' => true,
                ],
            ]);
    }

    /**
     * Reliability Case 3: Duplicate callback
     * The same provider event is delivered multiple times; processing must be idempotent.
     */
    public function test_case_3_duplicate_callback_processed_idempotently(): void
    {
        // 1. Create policy and submit with DELAYED_CALLBACK
        $intake = $this->postJson('/api/policy-requests', $this->basePayload);
        $id = $intake->json('data.id');
        $policy = PolicyRequest::find($id);

        $this->postJson("/api/policy-requests/{$id}/submit", ['scenario' => 'DELAYED_CALLBACK']);
        $policy->refresh();
        $providerRef = $policy->provider_reference;

        $eventId = 'EVENT-IDEMPOTENT-001';
        $policyNumber = 'POL-INS-CALLBACK-777';

        // 2. First callback delivery
        $cb1 = $this->postJson('/api/mock-insurer/callback', [
            'event_id'           => $eventId,
            'event_type'         => 'POLICY_ISSUED',
            'provider_reference' => $providerRef,
            'policy_number'      => $policyNumber,
        ]);

        $cb1->assertStatus(200)
            ->assertJson([
                'success'      => true,
                'is_duplicate' => false,
            ]);

        $policy->refresh();
        $this->assertSame('ISSUED', $policy->status);
        $this->assertSame($policyNumber, $policy->policy_number);

        // 3. Second callback delivery (duplicate event)
        $cb2 = $this->postJson('/api/mock-insurer/callback', [
            'event_id'           => $eventId,
            'event_type'         => 'POLICY_ISSUED',
            'provider_reference' => $providerRef,
            'policy_number'      => $policyNumber,
        ]);

        $cb2->assertStatus(200)
            ->assertJson([
                'success'      => true,
                'is_duplicate' => true,
            ]);

        // 4. Third callback delivery (duplicate event)
        $cb3 = $this->postJson('/api/mock-insurer/callback', [
            'event_id'           => $eventId,
            'event_type'         => 'POLICY_ISSUED',
            'provider_reference' => $providerRef,
            'policy_number'      => $policyNumber,
        ]);

        $cb3->assertStatus(200)
            ->assertJson([
                'is_duplicate' => true,
            ]);

        // Ensure exactly ONE provider event record exists in database
        $this->assertSame(1, ProviderEvent::where('event_id', $eventId)->count());

        // Policy state remains ISSUED and is never duplicated or corrupted
        $this->assertSame('ISSUED', $policy->fresh()->status);
        $this->assertSame($policyNumber, $policy->fresh()->policy_number);
    }

    /**
     * Reliability Case 4: Retry after temporary failure
     * Retries must not produce duplicate policies or lose original accepted premium.
     */
    public function test_case_4_retry_after_temporary_failure_preserves_premium_and_attempts(): void
    {
        $intake = $this->postJson('/api/policy-requests', $this->basePayload);
        $id = $intake->json('data.id');
        $policy = PolicyRequest::find($id);

        $originalPremium = $policy->calculated_premium;

        // Attempt 1: TEMPORARY_FAILURE
        $this->postJson("/api/policy-requests/{$id}/submit", ['scenario' => 'TEMPORARY_FAILURE']);
        $policy->refresh();
        $this->assertSame('FAILED', $policy->status);
        $this->assertSame(1, $policy->getLatestAttemptNumber());
        $this->assertEquals($originalPremium, $policy->calculated_premium);

        // Attempt 2: TEMPORARY_FAILURE again
        $this->postJson("/api/policy-requests/{$id}/submit", ['scenario' => 'TEMPORARY_FAILURE']);
        $policy->refresh();
        $this->assertSame('FAILED', $policy->status);
        $this->assertSame(2, $policy->getLatestAttemptNumber());
        $this->assertEquals($originalPremium, $policy->calculated_premium);

        // Attempt 3: SUCCESS
        $this->postJson("/api/policy-requests/{$id}/submit", ['scenario' => 'SUCCESS']);
        $policy->refresh();
        $this->assertSame('ISSUED', $policy->status);
        $this->assertSame(3, $policy->getLatestAttemptNumber());
        $this->assertEquals($originalPremium, $policy->calculated_premium);
        $this->assertNotEmpty($policy->policy_number);

        // Exactly one policy record exists in database
        $this->assertSame(1, PolicyRequest::where('client_request_id', $this->basePayload['client_request_id'])->count());
    }
}
