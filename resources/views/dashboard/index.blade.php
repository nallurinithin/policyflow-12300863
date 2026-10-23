@extends('layouts.app')

@section('title', 'Operational Dashboard — PolicyFlow')

@section('styles')
<style>
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 1.25rem;
        margin-bottom: 2rem;
    }

    .metric-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 1.25rem;
        position: relative;
        overflow: hidden;
    }

    .metric-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: var(--border-light);
    }

    .metric-card.primary::before { background: var(--primary); }
    .metric-card.success::before { background: var(--success); }
    .metric-card.warning::before { background: var(--warning); }
    .metric-card.danger::before  { background: var(--danger); }
    .metric-card.accent::before  { background: var(--accent); }

    .metric-label {
        font-size: 0.78rem;
        color: var(--text-muted);
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.04em;
        margin-bottom: 0.35rem;
    }

    .metric-value {
        font-size: 1.85rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: var(--text-main);
    }

    /* Filters Bar */
    .filter-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: flex-end;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }

    .form-group label {
        font-size: 0.75rem;
        color: var(--text-muted);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .form-control {
        background: var(--bg-input);
        border: 1px solid var(--border);
        color: var(--text-main);
        padding: 0.55rem 0.85rem;
        border-radius: var(--radius-sm);
        font-size: 0.88rem;
        outline: none;
        transition: border-color 0.15s ease;
    }

    .form-control:focus {
        border-color: var(--primary);
    }

    select.form-control {
        cursor: pointer;
    }

    .status-tabs {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-bottom: 1.25rem;
    }

    .status-tab {
        padding: 0.45rem 0.85rem;
        border-radius: var(--radius-sm);
        font-size: 0.82rem;
        font-weight: 600;
        text-decoration: none;
        color: var(--text-muted);
        background: var(--bg-card);
        border: 1px solid var(--border);
        transition: all 0.15s ease;
    }

    .status-tab:hover {
        color: var(--text-main);
        border-color: var(--text-dim);
    }

    .status-tab.active {
        background: var(--bg-card-hover);
        color: var(--primary);
        border-color: var(--primary);
    }

    .pagination-wrapper {
        margin-top: 1.5rem;
        display: flex;
        justify-content: flex-end;
    }
</style>
@endsection

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.75rem;">
    <div>
        <h2 style="font-size: 1.65rem; font-weight: 800; letter-spacing: -0.02em;">Operational Dashboard</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Real-time policy request lifecycle, state machine transitions, and insurer reconciliations.</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="{{ route('sandbox') }}" class="btn btn-outline">
            ⚡ Reliability Sandbox
        </a>
        <a href="{{ route('policies.create') }}" class="btn btn-primary">
            + New Policy Intake
        </a>
    </div>
</div>

<!-- Key Operational Metrics -->
<div class="metrics-grid">
    <div class="metric-card primary">
        <div class="metric-label">Total Requests</div>
        <div class="metric-value">{{ number_format($metrics['total']) }}</div>
    </div>
    <div class="metric-card success">
        <div class="metric-label">Policies Issued</div>
        <div class="metric-value" style="color: #34d399;">{{ number_format($metrics['issued']) }}</div>
    </div>
    <div class="metric-card warning">
        <div class="metric-label">Pending / Reconcile</div>
        <div class="metric-value" style="color: #fbbf24;">{{ number_format($metrics['pending_confirmation']) }}</div>
    </div>
    <div class="metric-card danger">
        <div class="metric-label">Failed / Retriable</div>
        <div class="metric-value" style="color: #f87171;">{{ number_format($metrics['failed']) }}</div>
    </div>
    <div class="metric-card accent">
        <div class="metric-label">Total Premium Issued</div>
        <div class="metric-value" style="font-size: 1.6rem; color: #a5b4fc;">₹{{ number_format($metrics['total_premium_issued'], 2) }}</div>
    </div>
</div>

<!-- Status Filter Tabs -->
<div class="status-tabs">
    <a href="{{ route('dashboard') }}" class="status-tab {{ !request('status') ? 'active' : '' }}">
        All Requests ({{ $metrics['total'] }})
    </a>
    <a href="{{ route('dashboard', ['status' => 'ISSUED']) }}" class="status-tab {{ request('status') === 'ISSUED' ? 'active' : '' }}">
        ● Issued ({{ $metrics['issued'] }})
    </a>
    <a href="{{ route('dashboard', ['status' => 'VALIDATED']) }}" class="status-tab {{ request('status') === 'VALIDATED' ? 'active' : '' }}">
        Validated ({{ $metrics['validated'] }})
    </a>
    <a href="{{ route('dashboard', ['status' => 'PENDING_CONFIRMATION']) }}" class="status-tab {{ request('status') === 'PENDING_CONFIRMATION' ? 'active' : '' }}">
        Pending Confirmation ({{ $metrics['pending_confirmation'] }})
    </a>
    <a href="{{ route('dashboard', ['status' => 'FAILED']) }}" class="status-tab {{ request('status') === 'FAILED' ? 'active' : '' }}">
        Failed ({{ $metrics['failed'] }})
    </a>
</div>

<!-- Search & Detailed Filters -->
<div class="filter-card">
    <form method="GET" action="{{ route('dashboard') }}" class="filter-form">
        @if(request('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif

        <div class="form-group" style="flex: 1; min-width: 240px;">
            <label>Search Query</label>
            <input type="text" name="search" class="form-control" placeholder="Customer, Client Request ID, Policy #, Agent..." value="{{ request('search') }}">
        </div>

        <div class="form-group">
            <label>Plan Code</label>
            <select name="plan_code" class="form-control">
                <option value="">All Plans</option>
                <option value="BASIC" {{ request('plan_code') === 'BASIC' ? 'selected' : '' }}>BASIC (₹12/d)</option>
                <option value="PLUS" {{ request('plan_code') === 'PLUS' ? 'selected' : '' }}>PLUS (₹20/d)</option>
                <option value="PREMIUM" {{ request('plan_code') === 'PREMIUM' ? 'selected' : '' }}>PREMIUM (₹30/d)</option>
            </select>
        </div>

        <div class="form-group">
            <label>Destination</label>
            <select name="destination" class="form-control">
                <option value="">All Destinations</option>
                <option value="DOMESTIC" {{ request('destination') === 'DOMESTIC' ? 'selected' : '' }}>DOMESTIC (1.00x)</option>
                <option value="INTERNATIONAL" {{ request('destination') === 'INTERNATIONAL' ? 'selected' : '' }}>INTERNATIONAL (1.80x)</option>
            </select>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="{{ route('dashboard') }}" class="btn btn-outline btn-sm">Reset</a>
        </div>
    </form>
</div>

<!-- Policy Requests Table -->
<div class="card" style="padding: 0; overflow: hidden;">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Request ID / Agent</th>
                    <th>Customer / DOB</th>
                    <th>Destination / Dates</th>
                    <th>Plan / Coverage</th>
                    <th>Premium</th>
                    <th>Status</th>
                    <th>Policy # / Provider Ref</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($policies as $p)
                    <tr>
                        <td>
                            <strong style="color: #fff;">{{ $p->client_request_id }}</strong>
                            <div class="mono" style="color: var(--text-dim); font-size: 0.75rem;">Agent: {{ $p->agent_id }}</div>
                        </td>
                        <td>
                            <div>{{ $p->customer_name }}</div>
                            <div class="mono" style="color: var(--text-muted); font-size: 0.75rem;">DOB: {{ $p->date_of_birth?->format('Y-m-d') }}</div>
                        </td>
                        <td>
                            <div style="font-weight: 600;">{{ $p->destination }}</div>
                            <div class="mono" style="color: var(--text-muted); font-size: 0.75rem;">
                                {{ $p->trip_start?->format('M d') }} - {{ $p->trip_end?->format('M d, Y') }} ({{ $p->travel_days }}d)
                            </div>
                        </td>
                        <td>
                            <span class="mono" style="font-weight: 700; color: #a5b4fc;">{{ $p->plan_code }}</span>
                            <div class="mono" style="color: var(--text-muted); font-size: 0.75rem;">₹{{ number_format($p->sum_insured) }}</div>
                        </td>
                        <td>
                            <span style="font-weight: 800; font-size: 1rem; color: #34d399;">₹{{ number_format($p->calculated_premium, 2) }}</span>
                        </td>
                        <td>
                            @if($p->status === 'ISSUED')
                                <span class="badge badge-issued">● ISSUED</span>
                            @elseif($p->status === 'VALIDATED')
                                <span class="badge badge-validated">● VALIDATED</span>
                            @elseif($p->status === 'SUBMITTED')
                                <span class="badge badge-submitted">● SUBMITTED</span>
                            @elseif(in_array($p->status, ['PENDING_CONFIRMATION', 'NEEDS_RECONCILIATION']))
                                <span class="badge badge-pending">● {{ $p->status }}</span>
                            @elseif($p->status === 'FAILED')
                                <span class="badge badge-failed">● FAILED</span>
                            @else
                                <span class="badge badge-received">● {{ $p->status }}</span>
                            @endif
                        </td>
                        <td>
                            @if($p->policy_number)
                                <div class="mono" style="color: #34d399; font-weight: 700;">{{ $p->policy_number }}</div>
                            @elseif($p->provider_reference)
                                <div class="mono" style="color: var(--text-muted);">{{ $p->provider_reference }}</div>
                            @else
                                <span style="color: var(--text-dim); font-size: 0.8rem;">—</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('policies.show', $p->id) }}" class="btn btn-outline btn-sm">
                                View & Actions →
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                            <div style="font-size: 1.1rem; font-weight: 600; margin-bottom: 0.5rem;">No policy requests found</div>
                            <p style="font-size: 0.85rem; margin-bottom: 1rem;">Create a new policy request or run the seeders to populate demo records.</p>
                            <a href="{{ route('policies.create') }}" class="btn btn-primary btn-sm">+ Create Policy Intake</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($policies->hasPages())
    <div class="pagination-wrapper">
        {{ $policies->links() }}
    </div>
@endif
@endsection
