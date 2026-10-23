<?php

namespace Database\Seeders;

use App\Models\PolicyRequest;
use App\Services\IssuanceService;
use App\Services\PolicyStateMachine;
use App\Services\PremiumCalculator;
use Illuminate\Database\Seeder;

class PolicyFlowSeeder extends Seeder
{
    public function run(): void
    {
        $calculator = app(PremiumCalculator::class);
        $stateMachine = app(PolicyStateMachine::class);
        $issuanceService = app(IssuanceService::class);

        // 1. Assignment Spec Example — Successfully Issued Policy
        $exampleCalc = $calculator->calculate('PLUS', 300000, 'INTERNATIONAL', '1998-04-10', '2026-11-10', '2026-11-14');
        $policy1 = PolicyRequest::create([
            'client_request_id'  => 'AGT-2026-0001',
            'agent_id'           => 'AGENT-17',
            'customer_name'      => 'Sample Customer',
            'date_of_birth'      => '1998-04-10',
            'destination'        => 'INTERNATIONAL',
            'trip_start'         => '2026-11-10',
            'trip_end'           => '2026-11-14',
            'plan_code'          => 'PLUS',
            'sum_insured'        => 300000,
            'travel_days'        => $exampleCalc['travel_days'],
            'calculated_premium' => $exampleCalc['calculated_premium'], // 225.00
            'status'             => 'RECEIVED',
        ]);
        $stateMachine->transition($policy1, 'VALIDATED', 'SEEDER_INTAKE', ['pricing' => $exampleCalc]);
        $issuanceService->submit($policy1, 'SUCCESS');

        // 2. Ready to Submit — VALIDATED
        $calc2 = $calculator->calculate('BASIC', 100000, 'DOMESTIC', '1985-02-14', '2026-12-01', '2026-12-10');
        $policy2 = PolicyRequest::create([
            'client_request_id'  => 'AGT-2026-0002',
            'agent_id'           => 'AGENT-09',
            'customer_name'      => 'Rahul Sharma',
            'date_of_birth'      => '1985-02-14',
            'destination'        => 'DOMESTIC',
            'trip_start'         => '2026-12-01',
            'trip_end'           => '2026-12-10',
            'plan_code'          => 'BASIC',
            'sum_insured'        => 100000,
            'travel_days'        => $calc2['travel_days'],
            'calculated_premium' => $calc2['calculated_premium'],
            'status'             => 'RECEIVED',
        ]);
        $stateMachine->transition($policy2, 'VALIDATED', 'SEEDER_INTAKE', ['pricing' => $calc2]);

        // 3. Pending Confirmation — DELAYED_CALLBACK
        $calc3 = $calculator->calculate('PREMIUM', 500000, 'INTERNATIONAL', '1970-08-22', '2026-11-01', '2026-11-07');
        $policy3 = PolicyRequest::create([
            'client_request_id'  => 'AGT-2026-0003',
            'agent_id'           => 'AGENT-24',
            'customer_name'      => 'Priya Patel',
            'date_of_birth'      => '1970-08-22',
            'destination'        => 'INTERNATIONAL',
            'trip_start'         => '2026-11-01',
            'trip_end'           => '2026-11-07',
            'plan_code'          => 'PREMIUM',
            'sum_insured'        => 500000,
            'travel_days'        => $calc3['travel_days'],
            'calculated_premium' => $calc3['calculated_premium'],
            'status'             => 'RECEIVED',
        ]);
        $stateMachine->transition($policy3, 'VALIDATED', 'SEEDER_INTAKE', ['pricing' => $calc3]);
        $issuanceService->submit($policy3, 'DELAYED_CALLBACK');

        // 4. Temporary Failure — FAILED (Ready for Retry)
        $calc4 = $calculator->calculate('PLUS', 300000, 'DOMESTIC', '1992-11-30', '2026-12-15', '2026-12-20');
        $policy4 = PolicyRequest::create([
            'client_request_id'  => 'AGT-2026-0004',
            'agent_id'           => 'AGENT-17',
            'customer_name'      => 'David Miller',
            'date_of_birth'      => '1992-11-30',
            'destination'        => 'DOMESTIC',
            'trip_start'         => '2026-12-15',
            'trip_end'           => '2026-12-20',
            'plan_code'          => 'PLUS',
            'sum_insured'        => 300000,
            'travel_days'        => $calc4['travel_days'],
            'calculated_premium' => $calc4['calculated_premium'],
            'status'             => 'RECEIVED',
        ]);
        $stateMachine->transition($policy4, 'VALIDATED', 'SEEDER_INTAKE', ['pricing' => $calc4]);
        $issuanceService->submit($policy4, 'TEMPORARY_FAILURE');
    }
}
