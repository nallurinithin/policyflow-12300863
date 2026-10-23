<?php

namespace Tests\Unit;

use App\Exceptions\IllegalStateTransitionException;
use App\Models\PolicyRequest;
use App\Services\PolicyStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private PolicyStateMachine $stateMachine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stateMachine = new PolicyStateMachine();
    }

    public function test_allows_valid_transitions(): void
    {
        $this->assertTrue($this->stateMachine->canTransition('RECEIVED', 'VALIDATED'));
        $this->assertTrue($this->stateMachine->canTransition('VALIDATED', 'SUBMITTED'));
        $this->assertTrue($this->stateMachine->canTransition('SUBMITTED', 'ISSUED'));
        $this->assertTrue($this->stateMachine->canTransition('SUBMITTED', 'PENDING_CONFIRMATION'));
        $this->assertTrue($this->stateMachine->canTransition('SUBMITTED', 'FAILED'));
        $this->assertTrue($this->stateMachine->canTransition('PENDING_CONFIRMATION', 'ISSUED'));
        $this->assertTrue($this->stateMachine->canTransition('PENDING_CONFIRMATION', 'NEEDS_RECONCILIATION'));
        $this->assertTrue($this->stateMachine->canTransition('FAILED', 'SUBMITTED'));
        $this->assertTrue($this->stateMachine->canTransition('NEEDS_RECONCILIATION', 'ISSUED'));
    }

    public function test_rejects_issued_to_any_other_state(): void
    {
        $this->assertFalse($this->stateMachine->canTransition('ISSUED', 'RECEIVED'));
        $this->assertFalse($this->stateMachine->canTransition('ISSUED', 'VALIDATED'));
        $this->assertFalse($this->stateMachine->canTransition('ISSUED', 'SUBMITTED'));
        $this->assertFalse($this->stateMachine->canTransition('ISSUED', 'FAILED'));
        $this->assertFalse($this->stateMachine->canTransition('ISSUED', 'PENDING_CONFIRMATION'));
        $this->assertFalse($this->stateMachine->canTransition('ISSUED', 'NEEDS_RECONCILIATION'));
    }

    public function test_transition_persists_state_and_creates_audit_record(): void
    {
        $request = PolicyRequest::create([
            'client_request_id' => 'REQ-TEST-001',
            'agent_id'          => 'AGENT-01',
            'customer_name'     => 'Alice Smith',
            'date_of_birth'     => '1995-05-15',
            'destination'       => 'DOMESTIC',
            'trip_start'        => '2026-07-01',
            'trip_end'          => '2026-07-05',
            'plan_code'         => 'BASIC',
            'sum_insured'       => 100000,
            'travel_days'       => 5,
            'status'            => 'RECEIVED',
        ]);

        $this->stateMachine->transition($request, 'VALIDATED', 'API_VALIDATION', ['info' => 'test']);

        $request->refresh();
        $this->assertSame('VALIDATED', $request->status);

        $this->assertDatabaseHas('policy_state_history', [
            'policy_request_id' => $request->id,
            'from_state'        => 'RECEIVED',
            'to_state'          => 'VALIDATED',
            'trigger'           => 'API_VALIDATION',
        ]);
    }

    public function test_throws_exception_on_illegal_transition(): void
    {
        $request = PolicyRequest::create([
            'client_request_id' => 'REQ-TEST-002',
            'agent_id'          => 'AGENT-01',
            'customer_name'     => 'Bob Jones',
            'date_of_birth'     => '1990-01-01',
            'destination'       => 'DOMESTIC',
            'trip_start'        => '2026-07-01',
            'trip_end'          => '2026-07-05',
            'plan_code'         => 'BASIC',
            'sum_insured'       => 100000,
            'travel_days'       => 5,
            'status'            => 'ISSUED',
        ]);

        $this->expectException(IllegalStateTransitionException::class);
        $this->stateMachine->transition($request, 'SUBMITTED', 'RETRY');
    }
}
