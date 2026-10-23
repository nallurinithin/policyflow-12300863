<?php

namespace Tests\Unit;

use App\Services\PremiumCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PremiumCalculatorTest extends TestCase
{
    private PremiumCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new PremiumCalculator();
    }

    /**
     * Test the exact calculation from the assignment specification:
     * - Plan: PLUS (base 20)
     * - Travel days: 2026-11-10 to 2026-11-14 = 5 days
     * - DOB: 1998-04-10 (age 28 on trip start) -> age multiplier 1.00
     * - Destination: INTERNATIONAL -> 1.80
     * - Coverage: 300,000 -> 1.25
     * - Expected: 20 * 5 * 1.00 * 1.80 * 1.25 = ₹225.00
     */
    public function test_calculates_assignment_example_correctly(): void
    {
        $result = $this->calculator->calculate(
            planCode: 'PLUS',
            sumInsured: 300000,
            destination: 'INTERNATIONAL',
            dateOfBirth: '1998-04-10',
            tripStart: '2026-11-10',
            tripEnd: '2026-11-14'
        );

        $this->assertSame(5, $result['travel_days']);
        $this->assertSame(28, $result['age']);
        $this->assertSame('20.00', $result['base_rate']);
        $this->assertSame('1.00', $result['age_multiplier']);
        $this->assertSame('1.80', $result['destination_multiplier']);
        $this->assertSame('1.25', $result['coverage_multiplier']);
        $this->assertSame('225.00', $result['calculated_premium']);
    }

    public function test_calculates_basic_domestic_senior(): void
    {
        // Age 65 on trip start -> 1.50 multiplier
        // BASIC plan -> 12.00
        // DOMESTIC -> 1.00
        // Coverage: 100000 -> 1.00
        // Days: 10
        // 12 * 10 * 1.50 * 1.00 * 1.00 = 180.00
        $result = $this->calculator->calculate(
            planCode: 'BASIC',
            sumInsured: 100000,
            destination: 'DOMESTIC',
            dateOfBirth: '1961-01-01',
            tripStart: '2026-06-01',
            tripEnd: '2026-06-10'
        );

        $this->assertSame(10, $result['travel_days']);
        $this->assertSame(65, $result['age']);
        $this->assertSame('1.50', $result['age_multiplier']);
        $this->assertSame('180.00', $result['calculated_premium']);
    }

    public function test_calculates_premium_plan_max_coverage_elderly(): void
    {
        // Age 75 on trip start -> 2.00 multiplier
        // PREMIUM plan -> 30.00
        // INTERNATIONAL -> 1.80
        // Coverage: 500000 -> 1.50
        // Days: 1 (single day trip)
        // 30 * 1 * 2.00 * 1.80 * 1.50 = 162.00
        $result = $this->calculator->calculate(
            planCode: 'PREMIUM',
            sumInsured: 500000,
            destination: 'INTERNATIONAL',
            dateOfBirth: '1951-01-01',
            tripStart: '2026-06-01',
            tripEnd: '2026-06-01'
        );

        $this->assertSame(1, $result['travel_days']);
        $this->assertSame('162.00', $result['calculated_premium']);
    }

    public function test_rejects_unsupported_plan_code(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unsupported plan_code 'ULTRA'");

        $this->calculator->calculate(
            planCode: 'ULTRA',
            sumInsured: 100000,
            destination: 'DOMESTIC',
            dateOfBirth: '1990-01-01',
            tripStart: '2026-06-01',
            tripEnd: '2026-06-05'
        );
    }

    public function test_rejects_unsupported_destination(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unsupported destination 'MARS'");

        $this->calculator->calculate(
            planCode: 'BASIC',
            sumInsured: 100000,
            destination: 'MARS',
            dateOfBirth: '1990-01-01',
            tripStart: '2026-06-01',
            tripEnd: '2026-06-05'
        );
    }

    public function test_rejects_unsupported_sum_insured(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unsupported sum_insured '250000'");

        $this->calculator->calculate(
            planCode: 'BASIC',
            sumInsured: 250000,
            destination: 'DOMESTIC',
            dateOfBirth: '1990-01-01',
            tripStart: '2026-06-01',
            tripEnd: '2026-06-05'
        );
    }

    public function test_rejects_trip_end_before_trip_start(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->calculate(
            planCode: 'BASIC',
            sumInsured: 100000,
            destination: 'DOMESTIC',
            dateOfBirth: '1990-01-01',
            tripStart: '2026-06-10',
            tripEnd: '2026-06-05'
        );
    }
}
