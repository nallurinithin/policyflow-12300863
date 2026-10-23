@extends('layouts.app')

@section('title', 'New Policy Intake — PolicyFlow')

@section('styles')
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1.75rem;
    }

    .page-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-heading);
        letter-spacing: -0.02em;
    }

    .page-desc {
        font-size: 0.875rem;
        color: var(--text-muted);
        margin-top: 0.2rem;
    }

    .form-layout {
        display: grid;
        grid-template-columns: 1.35fr 0.95fr;
        gap: 1.5rem;
        align-items: start;
    }

    @media (max-width: 960px) {
        .form-layout {
            grid-template-columns: 1fr;
        }
    }

    .form-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .input-with-button {
        display: flex;
        gap: 0.5rem;
    }

    .field-error {
        color: #dc2626;
        font-size: 0.75rem;
        font-weight: 500;
        margin-top: 0.25rem;
    }

    /* Receipt Quote Card */
    .receipt-card {
        background-color: #ffffff;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-sm);
        position: sticky;
        top: 84px;
        overflow: hidden;
    }

    .receipt-header {
        padding: 1.25rem 1.5rem;
        background-color: #fafbfc;
        border-bottom: 1px solid var(--border);
    }

    .receipt-title {
        font-size: 0.9375rem;
        font-weight: 600;
        color: var(--text-heading);
    }

    .receipt-desc {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 0.15rem;
    }

    .receipt-body {
        padding: 1.25rem 1.5rem;
    }

    .receipt-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        font-size: 0.8125rem;
        color: var(--text-body);
        border-bottom: 1px solid #f8fafc;
    }

    .receipt-row-label {
        color: var(--text-muted);
    }

    .receipt-row-value {
        font-weight: 500;
        color: var(--text-heading);
    }

    .receipt-total {
        margin-top: 1.25rem;
        padding-top: 1.25rem;
        border-top: 2px dashed var(--border);
    }

    .total-label {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--text-muted);
        letter-spacing: 0.05em;
    }

    .total-price {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-heading);
        letter-spacing: -0.03em;
        margin-top: 0.2rem;
    }

    .formula-pill {
        display: inline-block;
        background-color: var(--bg-subtle);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 0.4rem 0.65rem;
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 0.75rem;
        word-break: break-all;
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <div>
        <a href="{{ route('dashboard') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.8125rem;">← Back to policies</a>
        <h1 class="page-title" style="margin-top: 0.25rem;">Create Policy Request Intake</h1>
        <p class="page-desc">Validate input fields, compute deterministic premium, and initialize policy request state.</p>
    </div>
    <button type="button" class="btn btn-outline btn-sm" id="btnPrefillExample">
        Prefill Assignment Spec Example (₹225.00)
    </button>
</div>

<div class="form-layout">
    <!-- Form Card -->
    <div class="card card-padded">
        <form method="POST" action="{{ route('policies.store') }}" id="policyForm">
            @csrf

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="client_request_id" class="form-label">Client Request ID *</label>
                    <div class="input-with-button">
                        <input type="text" id="client_request_id" name="client_request_id" class="form-input mono" value="{{ old('client_request_id', 'AGT-' . date('Y') . '-' . rand(1000, 9999)) }}" required>
                        <button type="button" class="btn btn-outline btn-sm" id="btnGenId">Gen</button>
                    </div>
                    <div class="form-help">Unique idempotency key from agent system.</div>
                    @error('client_request_id') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label for="agent_id" class="form-label">Agent ID *</label>
                    <input type="text" id="agent_id" name="agent_id" class="form-input mono" value="{{ old('agent_id', 'AGENT-17') }}" required>
                    <div class="form-help">Authorized distributor identifier.</div>
                    @error('agent_id') <div class="field-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="customer_name" class="form-label">Customer Name *</label>
                    <input type="text" id="customer_name" name="customer_name" class="form-input" value="{{ old('customer_name', 'Sample Customer') }}" required>
                    @error('customer_name') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label for="date_of_birth" class="form-label">Date of Birth *</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" class="form-input" value="{{ old('date_of_birth', '1998-04-10') }}" required>
                    <div class="form-help">Used to evaluate age multiplier on trip start.</div>
                    @error('date_of_birth') <div class="field-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="trip_start" class="form-label">Trip Start Date *</label>
                    <input type="date" id="trip_start" name="trip_start" class="form-input" value="{{ old('trip_start', '2026-11-10') }}" required>
                    @error('trip_start') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label for="trip_end" class="form-label">Trip End Date (Inclusive) *</label>
                    <input type="date" id="trip_end" name="trip_end" class="form-input" value="{{ old('trip_end', '2026-11-14') }}" required>
                    <div class="form-help">Must be greater than or equal to start date.</div>
                    @error('trip_end') <div class="field-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="plan_code" class="form-label">Coverage Plan *</label>
                    <select id="plan_code" name="plan_code" class="form-select" required>
                        <option value="BASIC" {{ old('plan_code') === 'BASIC' ? 'selected' : '' }}>BASIC (₹12.00 / day)</option>
                        <option value="PLUS" {{ old('plan_code', 'PLUS') === 'PLUS' ? 'selected' : '' }}>PLUS (₹20.00 / day)</option>
                        <option value="PREMIUM" {{ old('plan_code') === 'PREMIUM' ? 'selected' : '' }}>PREMIUM (₹30.00 / day)</option>
                    </select>
                    @error('plan_code') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label for="destination" class="form-label">Destination *</label>
                    <select id="destination" name="destination" class="form-select" required>
                        <option value="DOMESTIC" {{ old('destination') === 'DOMESTIC' ? 'selected' : '' }}>DOMESTIC (1.00x)</option>
                        <option value="INTERNATIONAL" {{ old('destination', 'INTERNATIONAL') === 'INTERNATIONAL' ? 'selected' : '' }}>INTERNATIONAL (1.80x)</option>
                    </select>
                    @error('destination') <div class="field-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="sum_insured" class="form-label">Sum Insured (Coverage Amount) *</label>
                <select id="sum_insured" name="sum_insured" class="form-select" required>
                    <option value="100000" {{ old('sum_insured') == 100000 ? 'selected' : '' }}>₹100,000 (1.00x multiplier)</option>
                    <option value="300000" {{ old('sum_insured', 300000) == 300000 ? 'selected' : '' }}>₹300,000 (1.25x multiplier)</option>
                    <option value="500000" {{ old('sum_insured') == 500000 ? 'selected' : '' }}>₹500,000 (1.50x multiplier)</option>
                </select>
                @error('sum_insured') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 0.75rem;">
                <a href="{{ route('dashboard') }}" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    Create & Validate Policy Request
                </button>
            </div>
        </form>
    </div>

    <!-- Live Pricing Breakdown Receipt Card -->
    <div class="receipt-card">
        <div class="receipt-header">
            <div class="receipt-title">Live Pricing Engine Preview</div>
            <div class="receipt-desc">Deterministic pricing computed via bcmath arithmetic.</div>
        </div>

        <div class="receipt-body">
            <div class="receipt-row">
                <span class="receipt-row-label">Travel Duration (Inclusive)</span>
                <span class="receipt-row-value mono" id="calcDays">5 days</span>
            </div>
            <div class="receipt-row">
                <span class="receipt-row-label">Customer Age (Trip Start)</span>
                <span class="receipt-row-value mono" id="calcAge">28 yrs</span>
            </div>
            <div class="receipt-row">
                <span class="receipt-row-label">Base Rate</span>
                <span class="receipt-row-value mono" id="calcBaseRate">₹20.00 / day</span>
            </div>
            <div class="receipt-row">
                <span class="receipt-row-label">Age Multiplier</span>
                <span class="receipt-row-value mono" id="calcAgeMult">1.00x</span>
            </div>
            <div class="receipt-row">
                <span class="receipt-row-label">Destination Multiplier</span>
                <span class="receipt-row-value mono" id="calcDestMult">1.80x</span>
            </div>
            <div class="receipt-row">
                <span class="receipt-row-label">Coverage Multiplier</span>
                <span class="receipt-row-value mono" id="calcCovMult">1.25x</span>
            </div>

            <div class="receipt-total">
                <div class="total-label">Calculated Final Premium</div>
                <div class="total-price mono" id="calcTotal">₹225.00</div>
                <div class="formula-pill mono" id="calcFormula">
                    20 × 5 × 1.00 × 1.80 × 1.25 = ₹225.00
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const baseRates = {
        'BASIC': 12,
        'PLUS': 20,
        'PREMIUM': 30
    };

    const destMultipliers = {
        'DOMESTIC': 1.00,
        'INTERNATIONAL': 1.80
    };

    const coverageMultipliers = {
        '100000': 1.00,
        '300000': 1.25,
        '500000': 1.50
    };

    function calculateAge(dobStr, startStr) {
        if (!dobStr || !startStr) return 0;
        const dob = new Date(dobStr);
        const start = new Date(startStr);
        let age = start.getFullYear() - dob.getFullYear();
        const m = start.getMonth() - dob.getMonth();
        if (m < 0 || (m === 0 && start.getDate() < dob.getDate())) {
            age--;
        }
        return Math.max(0, age);
    }

    function getAgeMultiplier(age) {
        if (age <= 17) return 1.10;
        if (age <= 60) return 1.00;
        if (age <= 70) return 1.50;
        return 2.00;
    }

    function calculateDays(startStr, endStr) {
        if (!startStr || !endStr) return 1;
        const start = new Date(startStr);
        const end = new Date(endStr);
        const diffTime = end - start;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        return Math.max(1, diffDays);
    }

    function updateLivePricing() {
        const plan = document.getElementById('plan_code').value;
        const dest = document.getElementById('destination').value;
        const sumInsured = document.getElementById('sum_insured').value;
        const dob = document.getElementById('date_of_birth').value;
        const tripStart = document.getElementById('trip_start').value;
        const tripEnd = document.getElementById('trip_end').value;

        const days = calculateDays(tripStart, tripEnd);
        const age = calculateAge(dob, tripStart);

        const baseRate = baseRates[plan] || 20;
        const ageMult = getAgeMultiplier(age);
        const destMult = destMultipliers[dest] || 1.00;
        const covMult = coverageMultipliers[sumInsured] || 1.00;

        const total = baseRate * days * ageMult * destMult * covMult;

        document.getElementById('calcDays').textContent = days + (days === 1 ? ' day' : ' days');
        document.getElementById('calcAge').textContent = age + ' yrs';
        document.getElementById('calcBaseRate').textContent = '₹' + baseRate.toFixed(2) + ' / day';
        document.getElementById('calcAgeMult').textContent = ageMult.toFixed(2) + 'x';
        document.getElementById('calcDestMult').textContent = destMult.toFixed(2) + 'x';
        document.getElementById('calcCovMult').textContent = covMult.toFixed(2) + 'x';

        document.getElementById('calcTotal').textContent = '₹' + total.toFixed(2);
        document.getElementById('calcFormula').textContent = 
            `${baseRate} × ${days} × ${ageMult.toFixed(2)} × ${destMult.toFixed(2)} × ${covMult.toFixed(2)} = ₹${total.toFixed(2)}`;
    }

    ['plan_code', 'destination', 'sum_insured', 'date_of_birth', 'trip_start', 'trip_end'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', updateLivePricing);
            el.addEventListener('input', updateLivePricing);
        }
    });

    document.getElementById('btnGenId').addEventListener('click', () => {
        const num = Math.floor(1000 + Math.random() * 9000);
        document.getElementById('client_request_id').value = 'AGT-' + new Date().getFullYear() + '-' + num;
    });

    document.getElementById('btnPrefillExample').addEventListener('click', () => {
        document.getElementById('client_request_id').value = 'AGT-2026-0001';
        document.getElementById('agent_id').value = 'AGENT-17';
        document.getElementById('customer_name').value = 'Sample Customer';
        document.getElementById('date_of_birth').value = '1998-04-10';
        document.getElementById('destination').value = 'INTERNATIONAL';
        document.getElementById('trip_start').value = '2026-11-10';
        document.getElementById('trip_end').value = '2026-11-14';
        document.getElementById('plan_code').value = 'PLUS';
        document.getElementById('sum_insured').value = '300000';
        updateLivePricing();
    });

    updateLivePricing();
</script>
@endsection
