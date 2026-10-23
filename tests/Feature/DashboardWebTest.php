<?php

namespace Tests\Feature;

use App\Models\PolicyRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Operational Dashboard');
        $response->assertSee('PolicyFlow');
    }

    public function test_create_page_renders_successfully(): void
    {
        $response = $this->get('/policies/create');
        $response->assertStatus(200);
        $response->assertSee('Create Policy Request Intake');
        $response->assertSee('Live Pricing Engine');
    }

    public function test_policy_detail_page_renders_successfully(): void
    {
        $policy = PolicyRequest::create([
            'client_request_id'  => 'WEB-TEST-001',
            'agent_id'           => 'AGENT-01',
            'customer_name'      => 'Alice Web',
            'date_of_birth'      => '1995-05-15',
            'destination'        => 'DOMESTIC',
            'trip_start'         => '2026-07-01',
            'trip_end'           => '2026-07-05',
            'plan_code'          => 'BASIC',
            'sum_insured'        => 100000,
            'travel_days'        => 5,
            'calculated_premium' => 60.00,
            'status'             => 'VALIDATED',
        ]);

        $response = $this->get("/policies/{$policy->id}");
        $response->assertStatus(200);
        $response->assertSee('WEB-TEST-001');
        $response->assertSee('State Machine Audit Trail');
    }

    public function test_sandbox_page_renders_successfully(): void
    {
        $response = $this->get('/sandbox');
        $response->assertStatus(200);
        $response->assertSee('Reliability Sandbox');
        $response->assertSee('Reliability Case 1');
        $response->assertSee('Reliability Case 2');
        $response->assertSee('Reliability Case 3');
        $response->assertSee('Reliability Case 4');
    }

    public function test_store_policy_from_web_form_redirects_to_show(): void
    {
        $response = $this->post('/policies', [
            'client_request_id' => 'FORM-REQ-001',
            'agent_id'          => 'AGENT-17',
            'customer_name'     => 'Form Customer',
            'date_of_birth'     => '1998-04-10',
            'destination'       => 'INTERNATIONAL',
            'trip_start'        => '2026-11-10',
            'trip_end'          => '2026-11-14',
            'plan_code'         => 'PLUS',
            'sum_insured'       => 300000,
        ]);

        $policy = PolicyRequest::where('client_request_id', 'FORM-REQ-001')->first();
        $this->assertNotNull($policy);
        $response->assertRedirect(route('policies.show', $policy->id));
    }
}
