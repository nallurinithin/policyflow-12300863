@extends('layouts.app')

@section('title', 'Operational Dashboard — PolicyFlow')

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

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 1rem;
        margin-bottom: 1.75rem;
    }

    .stat-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 1.15rem 1.25rem;
        box-shadow: var(--shadow-sm);
    }

    .stat-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.35rem;
    }

    .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--text-heading);
        letter-spacing: -0.02em;
        line-height: 1.1;
    }

    .stat-sub {
        font-size: 0.75rem;
        color: var(--text-subtle);
        margin-top: 0.4rem;
    }

    /* Filter Toolbar */
    .toolbar-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 1rem 1.25rem;
        margin-bottom: 1.25rem;
        box-shadow: var(--shadow-sm);
    }

    .status-segmented {
        display: flex;
        gap: 0.25rem;
        flex-wrap: wrap;
        border-bottom: 1px solid var(--border);
        padding-bottom: 0.85rem;
        margin-bottom: 0.85rem;
    }

    .segment-item {
        padding: 0.35rem 0.75rem;
        border-radius: var(--radius-sm);
        font-size: 0.8125rem;
        font-weight: 500;
        color: var(--text-muted);
        text-decoration: none;
        transition: all 0.15s ease;
    }

    .segment-item:hover {
        color: var(--text-heading);
        background-color: var(--bg-subtle);
    }

    .segment-item.active {
        background-color: var(--primary);
        color: #ffffff;
        font-weight: 600;
    }

    .filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.85rem;
        align-items: flex-end;
    }

    .filter-item {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .filter-item label {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .filter-row .form-input, .filter-row .form-select {
        height: 36px;
        padding: 0.4rem 0.75rem;
        font-size: 0.8125rem;
    }

    .pagination-bar {
        display: flex;
        justify-content: flex-end;
        margin-top: 1.25rem;
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Operational Policies</h1>
        <p class="page-desc">Real-time insurance request lifecycle, state transitions, and provider reconciliations.</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="{{ route('sandbox') }}" class="btn btn-outline">
            Reliability Sandbox
        </a>
        <a href="{{ route('policies.create') }}" class="btn btn-primary">
            + New Intake
        </a>
    </div>
</div>

<!-- Operational Metrics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Requests</div>
        <div class="stat-value">{{ number_format($metrics['total']) }}</div>
        <div class="stat-sub">Across all agents</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Issued Policies</div>
        <div class="stat-value" style="color: #059669;">{{ number_format($metrics['issued']) }}</div>
        <div class="stat-sub">Finalized with insurer</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Pending Confirmation</div>
        <div class="stat-value" style="color: #d97706;">{{ number_format($metrics['pending_confirmation']) }}</div>
        <div class="stat-sub">Awaiting callback/reconcile</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Failed / Retriable</div>
        <div class="stat-value" style="color: #dc2626;">{{ number_format($metrics['failed']) }}</div>
        <div class="stat-sub">Safe retry available</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Premium (Issued)</div>
        <div class="stat-value" style="font-size: 1.5rem;">₹{{ number_format($metrics['total_premium_issued'], 2) }}</div>
        <div class="stat-sub">Immutable financial total</div>
    </div>
</div>

<!-- Filter Toolbar -->
<div class="toolbar-card">
    <div class="status-segmented">
        <a href="{{ route('dashboard') }}" class="segment-item {{ !request('status') ? 'active' : '' }}">
            All Requests ({{ $metrics['total'] }})
        </a>
        <a href="{{ route('dashboard', ['status' => 'ISSUED']) }}" class="segment-item {{ request('status') === 'ISSUED' ? 'active' : '' }}">
            Issued ({{ $metrics['issued'] }})
        </a>
        <a href="{{ route('dashboard', ['status' => 'VALIDATED']) }}" class="segment-item {{ request('status') === 'VALIDATED' ? 'active' : '' }}">
            Validated ({{ $metrics['validated'] }})
        </a>
        <a href="{{ route('dashboard', ['status' => 'PENDING_CONFIRMATION']) }}" class="segment-item {{ request('status') === 'PENDING_CONFIRMATION' ? 'active' : '' }}">
            Pending Confirmation ({{ $metrics['pending_confirmation'] }})
        </a>
        <a href="{{ route('dashboard', ['status' => 'FAILED']) }}" class="segment-item {{ request('status') === 'FAILED' ? 'active' : '' }}">
            Failed ({{ $metrics['failed'] }})
        </a>
    </div>

    <form method="GET" action="{{ route('dashboard') }}" class="filter-row">
        @if(request('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif

        <div class="filter-item" style="flex: 1; min-width: 220px;">
            <label>Search</label>
            <input type="text" name="search" class="form-input" placeholder="Search customer, request ID, policy #, agent..." value="{{ request('search') }}">
        </div>

        <div class="filter-item">
            <label>Plan</label>
            <select name="plan_code" class="form-select">
                <option value="">All Plans</option>
                <option value="BASIC" {{ request('plan_code') === 'BASIC' ? 'selected' : '' }}>BASIC</option>
                <option value="PLUS" {{ request('plan_code') === 'PLUS' ? 'selected' : '' }}>PLUS</option>
                <option value="PREMIUM" {{ request('plan_code') === 'PREMIUM' ? 'selected' : '' }}>PREMIUM</option>
            </select>
        </div>

        <div class="filter-item">
            <label>Destination</label>
            <select name="destination" class="form-select">
                <option value="">All Destinations</option>
                <option value="DOMESTIC" {{ request('destination') === 'DOMESTIC' ? 'selected' : '' }}>DOMESTIC</option>
                <option value="INTERNATIONAL" {{ request('destination') === 'INTERNATIONAL' ? 'selected' : '' }}>INTERNATIONAL</option>
            </select>
        </div>

        <div style="display: flex; gap: 0.35rem;">
            <button type="submit" class="btn btn-outline btn-sm" style="height: 36px;">Apply</button>
            <a href="{{ route('dashboard') }}" class="btn btn-outline btn-sm" style="height: 36px;">Reset</a>
        </div>
    </form>
</div>

<!-- Main Data Table -->
<div class="card" style="overflow: hidden;">
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Customer</th>
                    <th>Trip Details</th>
                    <th>Plan & Coverage</th>
                    <th>Premium</th>
                    <th>Status</th>
                    <th>Provider Reference / Policy #</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($policies as $p)
                    <tr>
                        <td>
                            <strong class="mono" style="color: var(--text-heading);">{{ $p->client_request_id }}</strong>
                            <div class="mono" style="color: var(--text-muted); font-size: 0.75rem;">Agent: {{ $p->agent_id }}</div>
                        </td>
                        <td>
                            <div style="font-weight: 500; color: var(--text-heading);">{{ $p->customer_name }}</div>
                            <div class="mono" style="color: var(--text-muted); font-size: 0.75rem;">DOB: {{ $p->date_of_birth?->format('Y-m-d') }}</div>
                        </td>
                        <td>
                            <div style="font-weight: 500;">{{ $p->destination }}</div>
                            <div class="mono" style="color: var(--text-muted); font-size: 0.75rem;">
                                {{ $p->trip_start?->format('M d') }} – {{ $p->trip_end?->format('M d, Y') }} ({{ $p->travel_days }}d)
                            </div>
                        </td>
                        <td>
                            <span class="mono" style="font-weight: 600;">{{ $p->plan_code }}</span>
                            <div class="mono" style="color: var(--text-muted); font-size: 0.75rem;">₹{{ number_format($p->sum_insured) }}</div>
                        </td>
                        <td>
                            <span class="mono" style="font-weight: 600; color: var(--text-heading);">₹{{ number_format($p->calculated_premium, 2) }}</span>
                        </td>
                        <td>
                            @if($p->status === 'ISSUED')
                                <span class="badge badge-issued"><span class="badge-dot"></span>ISSUED</span>
                            @elseif($p->status === 'VALIDATED')
                                <span class="badge badge-validated"><span class="badge-dot"></span>VALIDATED</span>
                            @elseif($p->status === 'SUBMITTED')
                                <span class="badge badge-submitted"><span class="badge-dot"></span>SUBMITTED</span>
                            @elseif(in_array($p->status, ['PENDING_CONFIRMATION', 'NEEDS_RECONCILIATION']))
                                <span class="badge badge-pending"><span class="badge-dot"></span>{{ $p->status }}</span>
                            @elseif($p->status === 'FAILED')
                                <span class="badge badge-failed"><span class="badge-dot"></span>FAILED</span>
                            @else
                                <span class="badge badge-received"><span class="badge-dot"></span>{{ $p->status }}</span>
                            @endif
                        </td>
                        <td>
                            @if($p->policy_number)
                                <div class="mono" style="color: #065f46; font-weight: 600; font-size: 0.8125rem;">{{ $p->policy_number }}</div>
                            @elseif($p->provider_reference)
                                <div class="mono" style="color: var(--text-muted); font-size: 0.8125rem;">{{ $p->provider_reference }}</div>
                            @else
                                <span style="color: var(--text-subtle); font-size: 0.8125rem;">—</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('policies.show', $p->id) }}" class="btn btn-outline btn-sm">
                                View →
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                            <div style="font-weight: 600; margin-bottom: 0.25rem;">No policy requests found</div>
                            <p style="font-size: 0.8125rem; margin-bottom: 1rem;">Create a new request or adjust your filters.</p>
                            <a href="{{ route('policies.create') }}" class="btn btn-primary btn-sm">+ New Policy Intake</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($policies->hasPages())
    <div class="pagination-bar">
        {{ $policies->links() }}
    </div>
@endif
@endsection
