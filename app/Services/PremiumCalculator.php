<?php

namespace App\Services;

use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Deterministic premium calculator using bcmath for decimal-safe arithmetic.
 *
 * Formula: premium = base_daily_rate * travel_days * age_multiplier * destination_multiplier * coverage_multiplier
 *
 * All calculations use string-based bcmath functions to avoid floating-point
 * precision errors that could cause discrepancies in financial computations.
 */
class PremiumCalculator
{
    /**
     * Base premium per travel day by plan code.
     */
    public const BASE_RATES = [
        'BASIC'   => '12.00',
        'PLUS'    => '20.00',
        'PREMIUM' => '30.00',
    ];

    /**
     * Age brackets with upper bound and multiplier.
     * Evaluated on trip_start. First matching bracket wins.
     */
    public const AGE_BRACKETS = [
        ['min' => 0,  'max' => 17,  'multiplier' => '1.10'],
        ['min' => 18, 'max' => 60,  'multiplier' => '1.00'],
        ['min' => 61, 'max' => 70,  'multiplier' => '1.50'],
        ['min' => 71, 'max' => 999, 'multiplier' => '2.00'],
    ];

    /**
     * Destination multipliers.
     */
    public const DESTINATION_MULTIPLIERS = [
        'DOMESTIC'      => '1.00',
        'INTERNATIONAL' => '1.80',
    ];

    /**
     * Coverage (sum_insured) multipliers.
     */
    public const COVERAGE_MULTIPLIERS = [
        '100000' => '1.00',
        '300000' => '1.25',
        '500000' => '1.50',
    ];

    /**
     * Supported plan codes.
     */
    public static function supportedPlans(): array
    {
        return array_keys(self::BASE_RATES);
    }

    /**
     * Supported destinations.
     */
    public static function supportedDestinations(): array
    {
        return array_keys(self::DESTINATION_MULTIPLIERS);
    }

    /**
     * Supported coverage amounts.
     */
    public static function supportedCoverages(): array
    {
        return array_map('intval', array_keys(self::COVERAGE_MULTIPLIERS));
    }

    /**
     * Calculate travel days inclusive: trip_end - trip_start + 1. Minimum 1.
     */
    public function calculateTravelDays(string|Carbon $tripStart, string|Carbon $tripEnd): int
    {
        $start = $tripStart instanceof Carbon ? $tripStart->copy()->startOfDay() : Carbon::parse($tripStart)->startOfDay();
        $end = $tripEnd instanceof Carbon ? $tripEnd->copy()->startOfDay() : Carbon::parse($tripEnd)->startOfDay();

        if ($end->lt($start)) {
            throw new InvalidArgumentException("trip_end ({$end->toDateString()}) cannot be before trip_start ({$start->toDateString()}).");
        }

        $days = (int) $start->diffInDays($end) + 1;
        return max(1, $days);
    }

    /**
     * Calculate age on trip_start date.
     */
    public function calculateAge(string|Carbon $dob, string|Carbon $tripStart): int
    {
        $birthDate = $dob instanceof Carbon ? $dob->copy()->startOfDay() : Carbon::parse($dob)->startOfDay();
        $startDate = $tripStart instanceof Carbon ? $tripStart->copy()->startOfDay() : Carbon::parse($tripStart)->startOfDay();

        if ($startDate->lt($birthDate)) {
            throw new InvalidArgumentException("trip_start cannot be before date_of_birth.");
        }

        return (int) $birthDate->diffInYears($startDate);
    }

    /**
     * Get age multiplier based on age on trip_start.
     */
    public function getAgeMultiplier(int $age): string
    {
        foreach (self::AGE_BRACKETS as $bracket) {
            if ($age >= $bracket['min'] && $age <= $bracket['max']) {
                return $bracket['multiplier'];
            }
        }

        return '2.00';
    }

    /**
     * Calculate premium and return complete calculation breakdown.
     *
     * @return array{
     *     travel_days: int,
     *     age: int,
     *     base_rate: string,
     *     age_multiplier: string,
     *     destination_multiplier: string,
     *     coverage_multiplier: string,
     *     calculated_premium: string
     * }
     */
    public function calculate(
        string $planCode,
        string|int|float $sumInsured,
        string $destination,
        string|Carbon $dateOfBirth,
        string|Carbon $tripStart,
        string|Carbon $tripEnd
    ): array {
        // Validate plan
        if (!isset(self::BASE_RATES[$planCode])) {
            throw new InvalidArgumentException("Unsupported plan_code '{$planCode}'. Supported plans: " . implode(', ', self::supportedPlans()));
        }

        // Validate destination
        if (!isset(self::DESTINATION_MULTIPLIERS[$destination])) {
            throw new InvalidArgumentException("Unsupported destination '{$destination}'. Supported destinations: " . implode(', ', self::supportedDestinations()));
        }

        // Validate coverage
        $sumInsuredInt = (int) $sumInsured;
        $coverageKey = (string) $sumInsuredInt;
        if (!isset(self::COVERAGE_MULTIPLIERS[$coverageKey])) {
            throw new InvalidArgumentException("Unsupported sum_insured '{$sumInsured}'. Supported values: " . implode(', ', self::supportedCoverages()));
        }

        $travelDays = $this->calculateTravelDays($tripStart, $tripEnd);
        $age = $this->calculateAge($dateOfBirth, $tripStart);

        $baseRate = self::BASE_RATES[$planCode];
        $ageMultiplier = $this->getAgeMultiplier($age);
        $destinationMultiplier = self::DESTINATION_MULTIPLIERS[$destination];
        $coverageMultiplier = self::COVERAGE_MULTIPLIERS[$coverageKey];

        // Decimal-safe multiplication using bcmath with scale 4
        // premium = base_daily_rate * travel_days * age_multiplier * destination_multiplier * coverage_multiplier
        $step1 = bcmul($baseRate, (string) $travelDays, 4);
        $step2 = bcmul($step1, $ageMultiplier, 4);
        $step3 = bcmul($step2, $destinationMultiplier, 4);
        $rawPremium = bcmul($step3, $coverageMultiplier, 4);

        // Standard half-up rounding to 2 decimal places using bcmath
        $roundedPremium = $this->bcRound($rawPremium, 2);

        return [
            'travel_days'            => $travelDays,
            'age'                    => $age,
            'base_rate'              => $baseRate,
            'age_multiplier'         => $ageMultiplier,
            'destination_multiplier' => $destinationMultiplier,
            'coverage_multiplier'    => $coverageMultiplier,
            'calculated_premium'     => $roundedPremium,
        ];
    }

    /**
     * Decimal-safe rounding for bcmath strings (half-up).
     */
    private function bcRound(string $number, int $precision = 2): string
    {
        if (!str_contains($number, '.')) {
            return number_format((float) $number, $precision, '.', '');
        }

        $offset = '0.' . str_repeat('0', $precision) . '5';
        if (str_starts_with($number, '-')) {
            $offset = '-' . $offset;
        }

        $adjusted = bcadd($number, $offset, $precision + 1);
        return bcadd($adjusted, '0', $precision);
    }
}
