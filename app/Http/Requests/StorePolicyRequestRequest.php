<?php

namespace App\Http\Requests;

use App\Services\PremiumCalculator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StorePolicyRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_request_id' => ['required', 'string', 'max:100'],
            'agent_id'          => ['required', 'string', 'max:100'],
            'customer_name'     => ['required', 'string', 'max:255'],
            'date_of_birth'     => ['required', 'date', 'before:today'],
            'destination'       => ['required', 'string', Rule::in(PremiumCalculator::supportedDestinations())],
            'trip_start'        => ['required', 'date'],
            'trip_end'          => ['required', 'date', 'after_or_equal:trip_start'],
            'plan_code'         => ['required', 'string', Rule::in(PremiumCalculator::supportedPlans())],
            'sum_insured'       => ['required', 'numeric', Rule::in(PremiumCalculator::supportedCoverages())],
        ];
    }

    public function messages(): array
    {
        return [
            'client_request_id.required' => 'client_request_id is required.',
            'agent_id.required'          => 'agent_id is required.',
            'customer_name.required'     => 'customer_name is required.',
            'date_of_birth.required'     => 'date_of_birth is required.',
            'date_of_birth.before'       => 'date_of_birth must be a date in the past.',
            'destination.in'             => 'destination must be one of: ' . implode(', ', PremiumCalculator::supportedDestinations()) . '.',
            'trip_start.required'        => 'trip_start is required.',
            'trip_end.required'          => 'trip_end is required.',
            'trip_end.after_or_equal'    => 'trip_end must be greater than or equal to trip_start.',
            'plan_code.in'               => 'plan_code must be one of: ' . implode(', ', PremiumCalculator::supportedPlans()) . '.',
            'sum_insured.in'             => 'sum_insured must be one of: ' . implode(', ', PremiumCalculator::supportedCoverages()) . '.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed for policy request intake.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
