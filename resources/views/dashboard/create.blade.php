@extends('layouts.app')

@section('title', 'New Policy Intake — PolicyFlow')

@section('styles')
<style>
    .intake-grid {
        display: grid;
        grid-template-columns: 1.2fr 0.8fr;
        gap: 2rem;
    }

    @media (max-width: 992px) {
        .intake-grid {
            grid-template-columns: 1fr;
        }
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
        margin-bottom: 1.25rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
        margin-bottom: 1.25rem;
    }

    .form-group label {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .form-control {
        background: var(--bg-input);
        border: 1px solid var(--border);
        color: var(--text-main);
        padding: 0.75rem 1rem;
        border-radius: var(--radius-sm);
        font-size: 0.95rem;
        outline: none;
        transition: border-color 0.15s ease;
        width: 100%;
    }

    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    .error-msg {
        color: #f87171;
        font-size: 0.78rem;
        font-weight: 600;
    }

    /* Live Calculator Card */
    .calculator-card {
        background: linear-gradient(145deg, #131b2e 0%, #17233f 100%);
        border: 1px solid var(--border-light);
        border-radius: var(--radius-md);
        padding: 1.75rem;
        position: sticky;
        top: 90px;
    }

    .calc-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.7rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        font-size: 0.88rem;
    }

    .calc-row:last-child {
        border-bottom: none;
    }

    .calc-label {
        color: var(--text-muted);
    }

    .calc-val {
        font-family: 'JetBrains Mono', monospace;
        font-weight: 600;
        color: #fff;
    }

    .total-box {
        margin-top: 1.5rem;
        padding: 1.25rem;
        background: rgba(16, 185, 129, 0.08);
        border: 1px solid rgba(16, 185, 129, 0.3);
        border-radius: var(--radius-sm);
        text-align: center;
    }

    .total-title {
        font-size: 0.75rem;
        color: #34d399;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.05em;
        margin-bottom: 0.25rem;
    }

    .total-amount {
        font-size: 2.2rem;
        font-weight: 800;
        color: #34d399;
        letter-spacing: -0.02em;
    }

    .formula-breakdown {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.75rem;
        color: var(--text-dim);
        margin-top: 0.5rem;
        word-break: break-all;
    }
</style>
@endsection

@section('content')
<div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <a href="{{ route('dashboard') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; font-weight: 600;">← Back to Dashboard</a>
        <h2 style="font-size: 1.65rem; font-weight: 800; margin-top: 0.25rem;">Create Policy Request Intake</h2>
    </div>
    <button type="button" class="btn btn-outline btn-sm" id="btnPrefillExample">
        ⚡ Fill Assignment Spec Example (₹225.00)
    </button>
</div>

<div class="intake-grid">
    <!-- Intake Form -->
    <div class="card">
        <form method="POST" action="{{ route('policies.store') }}" id="policyForm">
            @csrf

            <div class="form-row">
                <div class="form-group">
                    <label for="client_request_id">Client Request ID *</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" id="client_request_id" name="client_request_id" class="form-control mono" value="{{ old('client_request_id', 'AGT-' . date('Y') . '-' . rand(1000, 9999)) }}" required>
                        <button type="button" class="btn btn-outline btn-sm" id="btnGenId">Gen</button>
                    </div>
                    @error('client_request_id') <span class="error-msg">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="agent_id">Agent ID *</label>
                    <input type="text" id="agent_id" name="agent_id" class="form-control mono" value="{{ old('agent_id', 'AGENT-17') }}" required>
                    @error('agent_id') <span class="error-msg">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="customer_name">Customer Full Name *</label>
                    <input type="text" id="customer_name" name="customer_name" class="form-control" value="{{ old('customer_name', 'Sample Customer') }}" required>
                    @error('customer_name') <span class="error-msg">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="date_of_birth">Date of Birth *</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', '1998-04-10') }}" required>
                    @error('date_of_birth') <span class="error-msg">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="trip_start">Trip Start Date *</label>
                    <input type="date" id="trip_start" name="trip_start" class="form-control" value="{{ old('trip_start', '2026-11-10') }}" required>
                    @error('trip_start') <span class="error-msg">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="trip_end">Trip End Date (Inclusive) *</label>
                    <input type="date" id="trip_end" name="trip_end" class="form-control" value="{{ old('trip_end', '2026-11-14') }}" required>
                    @error('trip_end') <span class="error-msg">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="plan_code">Plan Code *</label>
                    <select id="plan_code" name="plan_code" class="form-control" required>
                        <option value="BASIC" {{ old('plan_code') === 'BASIC' ? 'selected' : '' }}>BASIC — ₹12 / day</option>
                        <option value="PLUS" {{ old('plan_code', 'PLUS') === 'PLUS' ? 'selected' : '' }}>PLUS — ₹20 / day</option>
                        <option value="PREMIUM" {{ old('plan_code') === 'PREMIUM' ? 'selected' : '' }}>PREMIUM — ₹30 / day</option>
                    </select>
                    @error('plan_code') <span class="error-msg">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="destination">Destination *</label>
                    <select id="destination" name="destination" class="form-control" required>
                        <option value="DOMESTIC" {{ old('destination') === 'DOMESTIC' ? 'selected' : '' }}>DOMESTIC (1.00x multiplier)</option>
                        <option value="INTERNATIONAL" {{ old('destination', 'INTERNATIONAL') === 'INTERNATIONAL' ? 'selected' : '' }}>INTERNATIONAL (1.80x multiplier)</option>
                    </select>
                    @error('destination') <span class="error-msg">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="sum_insured">Sum Insured (Coverage Amount) *</label>
                <select id="sum_insured" name="sum_insured" class="form-control" required>
                    <option value="100000" {{ old('sum_insured') == 100000 ? 'selected' : '' }}>₹100,000 (1.00x multiplier)</option>
                    <option value="300000" {{ old('sum_insured', 300000) == 300000 ? 'selected' : '' }}>₹300,000 (1.25x multiplier)</option>
                    <option value="500000" {{ old('sum_insured') == 500000 ? 'selected' : '' }}>₹500,000 (1.50x multiplier)</option>
                </select>
                @error('sum_insured') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem;">
                <a href="{{ route('dashboard') }}" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary" style="padding-left: 2rem; padding-right: 2rem;">
                    Submit Policy Request →
                </button>
            </div>
        </form>
    </div>

    <!-- Live Pricing Engine Preview -->
    <div class="calculator-card">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.25rem;">Live Pricing Engine</h3>
        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1.25rem;">
            Deterministic decimal-safe pricing evaluated in real-time.
        </p>

        <div class="calc-row">
            <span class="calc-label">Travel Days (inclusive)</span>
            <span class="calc-val" id="calcDays">5 days</span>
        </div>
        <div class="calc-row">
            <span class="calc-label">Customer Age (at trip start)</span>
            <span class="calc-val" id="calcAge">28 years</span>
        </div>
        <div class="calc-row">
            <span class="calc-label">Base Daily Rate</span>
            <span class="calc-val" id="calcBaseRate">₹20.00</span>
        </div>
        <div class="calc-row">
            <span class="calc-label">Age Multiplier</span>
            <span class="calc-val" id="calcAgeMult">1.00x</span>
        </div>
        <div class="calc-row">
            <span class="calc-label">Destination Multiplier</span>
            <span class="calc-val" id="calcDestMult">1.80x</span>
        </div>
        <div class="calc-row">
            <span class="calc-label">Coverage Multiplier</span>
            <span class="calc-val" id="calcCovMult">1.25x</span>
        </div>

        <div class="total-box">
            <div class="total-title">Calculated Immutable Premium</div>
            <div class="total-amount" id="calcTotal">₹225.00</div>
            <div class="formula-breakdown" id="calcFormula">
                20 × 5 × 1.00 × 1.80 × 1.25 = ₹225.00
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
        document.getElementById('calcAge').textContent = age + ' years';
        document.getElementById('calcBaseRate').textContent = '₹' + baseRate.toFixed(2);
        document.getElementById('calcAgeMult').textContent = ageMult.toFixed(2) + 'x';
        document.getElementById('calcDestMult').textContent = destMult.toFixed(2) + 'x';
        document.getElementById('calcCovMult').textContent = covMult.toFixed(2) + 'x';

        document.getElementById('calcTotal').textContent = '₹' + total.toFixed(2);
        document.getElementById('calcFormula').textContent = 
            `${baseRate} × ${days} × ${ageMult.toFixed(2)} × ${destMult.toFixed(2)} × ${covMult.toFixed(2)} = ₹${total.toFixed(2)}`;
    }

    // Attach listeners
    ['plan_code', 'destination', 'sum_insured', 'date_of_birth', 'trip_start', 'trip_end'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', updateLivePricing);
            el.addEventListener('input', updateLivePricing);
        }
    });

    // Generate Client Request ID
    document.getElementById('btnGenId').addEventListener('click', () => {
        const num = Math.floor(1000 + Math.random() * 9000);
        document.getElementById('client_request_id').value = 'AGT-' + new Date().getFullYear() + '-' + num;
    });

    // Fill Assignment Example
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

    // Initial calculation on load
    updateLivePricing();
</script>
@endsection
