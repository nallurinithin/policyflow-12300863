<?php

namespace Tests\Feature;

use App\Models\PolicyRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyRequestIntakeTest extends TestCase
{
    use RefreshDatabase;

    private array $validPayload = [
        'client_request_id' => 'AGT-2026-0001',
        'agent_id'          => 'AGENT-17',
        'customer_name'     => 'Sample Customer',
        'date_of_birth'     => '1998-04-10',
        'destination'       => 'INTERNATIONAL',
        'trip_start'        => '2026-11-10',
        'trip_end'          => '2026-11-14',
        'plan_code'         => 'PLUS',
        'sum_insured'       => 300000,
    ];

    public function test_intake_creates_new_policy_request_with_correct_premium(): void
    {
        $response = $this->postJson('/api/policy-requests', $this->validPayload);

        $response->assertStatus(201)
            ->assertJson([
                'success'      => true,
                'is_duplicate' => false,
                'data'         => [
                    'client_request_id'  => 'AGT-2026-0001',
                    'agent_id'           => 'AGENT-17',
                    'customer_name'      => 'Sample Customer',
                    'status'             => 'VALIDATED',
                    'travel_days'        => 5,
                    'calculated_premium' => 225.00,
                    'destination'        => 'INTERNATIONAL',
                    'plan_code'          => 'PLUS',
                    'sum_insured'        => 300000,
                ],
            ]);

        $this->assertDatabaseHas('policy_requests', [
            'client_request_id'  => 'AGT-2026-0001',
            'agent_id'           => 'AGENT-17',
            'status'             => 'VALIDATED',
            'calculated_premium' => 225.00,
        ]);

        $this->assertDatabaseHas('policy_state_history', [
            'from_state' => 'RECEIVED',
            'to_state'   => 'VALIDATED',
            'trigger'    => 'API_INTAKE',
        ]);
    }

    public function test_repeated_submission_returns_200_and_does_not_duplicate(): void
    {
        // First submission -> 201 Created
        $response1 = $this->postJson('/api/policy-requests', $this->validPayload);
        $response1->assertStatus(201);

        $initialId = $response1->json('data.id');

        // Repeated submission with same agent_id + client_request_id -> 200 OK
        $response2 = $this->postJson('/api/policy-requests', $this->validPayload);
        $response2->assertStatus(200)
            ->assertJson([
                'success'      => true,
                'is_duplicate' => true,
                'data'         => [
                    'id'                => $initialId,
                    'client_request_id' => 'AGT-2026-0001',
                    'agent_id'          => 'AGENT-17',
                ],
            ]);

        // Ensure exactly ONE record exists in the database
        $this->assertSame(1, PolicyRequest::where('agent_id', 'AGENT-17')->where('client_request_id', 'AGT-2026-0001')->count());
    }

    public function test_validation_fails_when_trip_end_before_trip_start(): void
    {
        $payload = array_merge($this->validPayload, [
            'trip_start' => '2026-11-15',
            'trip_end'   => '2026-11-10',
        ]);

        $response = $this->postJson('/api/policy-requests', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['trip_end']);
    }

    public function test_validation_fails_for_unsupported_plan(): void
    {
        $payload = array_merge($this->validPayload, [
            'plan_code' => 'PLATINUM',
        ]);

        $response = $this->postJson('/api/policy-requests', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plan_code']);
    }

    public function test_validation_fails_for_unsupported_coverage(): void
    {
        $payload = array_merge($this->validPayload, [
            'sum_insured' => 200000,
        ]);

        $response = $this->postJson('/api/policy-requests', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sum_insured']);
    }

    public function test_validation_fails_for_future_date_of_birth(): void
    {
        $payload = array_merge($this->validPayload, [
            'date_of_birth' => '2099-01-01',
        ]);

        $response = $this->postJson('/api/policy-requests', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date_of_birth']);
    }

    public function test_can_retrieve_policy_request_by_id_with_audit_trail(): void
    {
        $createResponse = $this->postJson('/api/policy-requests', $this->validPayload);
        $id = $createResponse->json('data.id');

        $response = $this->getJson("/api/policy-requests/{$id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'id'                => $id,
                    'client_request_id' => 'AGT-2026-0001',
                    'status'            => 'VALIDATED',
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'state_history',
                    'issuance_attempts',
                    'provider_events',
                ],
            ]);
    }

    public function test_can_retrieve_policy_request_by_uuid(): void
    {
        $createResponse = $this->postJson('/api/policy-requests', $this->validPayload);
        $uuid = $createResponse->json('data.uuid');

        $response = $this->getJson("/api/policy-requests/{$uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'uuid' => $uuid,
                ],
            ]);
    }

    public function test_returns_404_for_nonexistent_policy(): void
    {
        $response = $this->getJson('/api/policy-requests/non-existent-id');
        $response->assertStatus(404);
    }

    public function test_can_list_and_filter_policy_requests(): void
    {
        // Seed 2 requests
        $this->postJson('/api/policy-requests', $this->validPayload);
        $this->postJson('/api/policy-requests', array_merge($this->validPayload, [
            'client_request_id' => 'AGT-2026-0002',
            'plan_code'         => 'BASIC',
            'sum_insured'       => 100000,
        ]));

        // List all
        $response = $this->getJson('/api/policy-requests');
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));

        // Filter by plan_code
        $filterResponse = $this->getJson('/api/policy-requests?plan_code=BASIC');
        $filterResponse->assertStatus(200);
        $this->assertCount(1, $filterResponse->json('data'));
        $this->assertSame('BASIC', $filterResponse->json('data.0.plan_code'));
    }
}
