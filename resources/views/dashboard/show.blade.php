@extends('layouts.app')

@section('title', 'Policy ' . $policy->client_request_id . ' — PolicyFlow')

@section('styles')
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1.5rem;
    }

    .page-title-row {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-top: 0.25rem;
    }

    .page-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-heading);
        letter-spacing: -0.02em;
    }

    .meta-subtext {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 0.25rem;
    }

    .identifiers-box {
        text-align: right;
    }

    .id-label {
        font-size: 0.6875rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .id-value {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--text-heading);
    }

    /* Action Toolbar */
    .action-toolbar {
        background-color: #ffffff;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        box-shadow: var(--shadow-sm);
    }

    .action-toolbar-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-heading);
    }

    .action-toolbar-desc {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 0.1rem;
    }

    /* 2-Column Grid */
    .detail-grid {
        display: grid;
        grid-template-columns: 1fr 1.35fr;
        gap: 1.5rem;
        align-items: start;
    }

    @media (max-width: 960px) {
        .detail-grid {
            grid-template-columns: 1fr;
        }
    }

    .info-list {
        display: flex;
        flex-direction: column;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.7rem 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.8125rem;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-row-label {
        color: var(--text-muted);
    }

    .info-row-value {
        font-weight: 500;
        color: var(--text-heading);
    }

    /* Clean Timeline / Activity Log */
    .activity-stream {
        position: relative;
        padding-left: 1.5rem;
        margin-top: 0.75rem;
    }

    .activity-stream::before {
        content: '';
        position: absolute;
        top: 6px;
        bottom: 6px;
        left: 6px;
        width: 2px;
        background-color: var(--border);
    }

    .activity-node {
        position: relative;
        padding-bottom: 1.25rem;
    }

    .activity-node:last-child {
        padding-bottom: 0;
    }

    .node-bullet {
        position: absolute;
        left: -1.5rem;
        top: 4px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background-color: #ffffff;
        border: 2px solid var(--text-muted);
    }

    .node-bullet.issued {
        border-color: #10b981;
        background-color: #10b981;
    }

    .node-bullet.failed {
        border-color: #ef4444;
        background-color: #ef4444;
    }

    .node-bullet.submitted {
        border-color: #6366f1;
        background-color: #6366f1;
    }

    .node-bullet.pending {
        border-color: #f59e0b;
        background-color: #f59e0b;
    }

    .node-content {
        background-color: var(--bg-subtle);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 0.65rem 0.85rem;
    }

    .node-title {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--text-heading);
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .node-meta {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 0.2rem;
    }

    .node-payload {
        margin-top: 0.45rem;
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.72rem;
        color: var(--text-muted);
        background-color: #ffffff;
        border: 1px solid var(--border);
        border-radius: 4px;
        padding: 0.4rem 0.6rem;
        word-break: break-all;
    }
</style>
@endsection

@section('content')
<!-- Header -->
<div class="page-header">
    <div>
        <a href="{{ route('dashboard') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.8125rem;">← Back to policies</a>
        <div class="page-title-row">
            <h1 class="page-title mono">{{ $policy->client_request_id }}</h1>
            @if($policy->status === 'ISSUED')
                <span class="badge badge-issued"><span class="badge-dot"></span>ISSUED</span>
            @elseif($policy->status === 'VALIDATED')
                <span class="badge badge-validated"><span class="badge-dot"></span>VALIDATED</span>
            @elseif($policy->status === 'SUBMITTED')
                <span class="badge badge-submitted"><span class="badge-dot"></span>SUBMITTED</span>
            @elseif(in_array($policy->status, ['PENDING_CONFIRMATION', 'NEEDS_RECONCILIATION']))
                <span class="badge badge-pending"><span class="badge-dot"></span>{{ $policy->status }}</span>
            @elseif($policy->status === 'FAILED')
                <span class="badge badge-failed"><span class="badge-dot"></span>FAILED</span>
            @else
                <span class="badge badge-received"><span class="badge-dot"></span>{{ $policy->status }}</span>
            @endif
        </div>
        <div class="meta-subtext mono">
            UUID: {{ $policy->uuid }} • Created {{ $policy->created_at?->format('Y-m-d H:i:s') }}
        </div>
    </div>

    <div class="identifiers-box">
        @if($policy->policy_number)
            <div class="id-label" style="color: #065f46;">Confirmed Policy Number</div>
            <div class="id-value mono" style="color: #065f46;">{{ $policy->policy_number }}</div>
        @elseif($policy->provider_reference)
            <div class="id-label">Provider Reference</div>
            <div class="id-value mono">{{ $policy->provider_reference }}</div>
        @endif
    </div>
</div>

<!-- Workflow Action Bar -->
<div class="action-toolbar">
    <div>
        <div class="action-toolbar-title">Lifecycle Actions</div>
        <div class="action-toolbar-desc">Execute insurer submission, simulate edge scenarios, or trigger status reconciliation.</div>
    </div>

    <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
        @if($policy->status !== 'ISSUED')
            <form method="POST" action="{{ route('policies.submit', $policy->id) }}" style="display: flex; gap: 0.5rem; align-items: center;">
                @csrf
                <select name="scenario" class="form-select" style="height: 36px; padding: 0.35rem 0.65rem; font-size: 0.8125rem; width: auto;">
                    <option value="SUCCESS">Scenario: SUCCESS</option>
                    <option value="TEMPORARY_FAILURE">Scenario: TEMPORARY_FAILURE (503)</option>
                    <option value="TIMEOUT_AFTER_ISSUE">Scenario: TIMEOUT_AFTER_ISSUE (504)</option>
                    <option value="DELAYED_CALLBACK">Scenario: DELAYED_CALLBACK (202)</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm" style="height: 36px;">
                    Submit to Insurer →
                </button>
            </form>

            @if(in_array($policy->status, ['PENDING_CONFIRMATION', 'NEEDS_RECONCILIATION', 'FAILED']))
                <form method="POST" action="{{ route('policies.reconcile', $policy->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-sm" style="height: 36px;">
                        Reconcile Provider Status
                    </button>
                </form>
            @endif
        @else
            <div style="font-size: 0.8125rem; font-weight: 500; color: #065f46; background-color: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.4rem 0.75rem; border-radius: var(--radius-sm);">
                Policy finalized and issued. State is terminal and immutable.
            </div>
        @endif
    </div>
</div>

<!-- Detail Columns -->
<div class="detail-grid">
    <!-- Left Column: Specs & Financials -->
    <div style="display: flex; flex-direction: column; gap: 1.25rem;">
        <!-- Policy Specification Card -->
        <div class="card card-padded">
            <div class="card-title" style="margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border);">
                Policy Specifications
            </div>

            <div class="info-list">
                <div class="info-row">
                    <span class="info-row-label">Customer Name</span>
                    <span class="info-row-value">{{ $policy->customer_name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-row-label">Date of Birth</span>
                    <span class="info-row-value mono">{{ $policy->date_of_birth?->format('Y-m-d') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-row-label">Destination</span>
                    <span class="info-row-value">{{ $policy->destination }}</span>
                </div>
                <div class="info-row">
                    <span class="info-row-label">Trip Schedule</span>
                    <span class="info-row-value mono">
                        {{ $policy->trip_start?->format('Y-m-d') }} to {{ $policy->trip_end?->format('Y-m-d') }}
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-row-label">Travel Days</span>
                    <span class="info-row-value">{{ $policy->travel_days }} days (inclusive)</span>
                </div>
                <div class="info-row">
                    <span class="info-row-label">Plan Tier</span>
                    <span class="info-row-value mono" style="font-weight: 600;">{{ $policy->plan_code }}</span>
                </div>
                <div class="info-row">
                    <span class="info-row-label">Sum Insured</span>
                    <span class="info-row-value mono">₹{{ number_format($policy->sum_insured) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-row-label">Agent ID</span>
                    <span class="info-row-value mono">{{ $policy->agent_id }}</span>
                </div>
            </div>
        </div>

        <!-- Immutable Financial Snapshot -->
        <div class="card card-padded">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <div class="card-title">Accepted Premium Snapshot</div>
                <span class="badge badge-validated">Immutable</span>
            </div>

            <div style="background-color: var(--bg-subtle); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 1rem; text-align: center;">
                <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600; color: var(--text-muted); letter-spacing: 0.05em;">Final Calculated Premium</div>
                <div class="mono" style="font-size: 2rem; font-weight: 700; color: var(--text-heading); margin-top: 0.25rem;">
                    ₹{{ number_format($policy->calculated_premium, 2) }}
                </div>
                <div class="mono" style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.25rem;">
                    Computed via decimal-safe bcmath
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Audit Trail, Attempts, Events -->
    <div style="display: flex; flex-direction: column; gap: 1.25rem;">
        <!-- State Machine Transition Audit Trail -->
        <div class="card card-padded">
            <div class="card-title" style="margin-bottom: 0.5rem;">
                State Machine Audit Trail ({{ $policy->stateHistory->count() }})
            </div>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1rem;">
                Complete ledger of state transitions with triggers and context.
            </p>

            <div class="activity-stream">
                @forelse($policy->stateHistory as $h)
                    <div class="activity-node">
                        <div class="node-bullet {{ strtolower($h->to_state) }}"></div>
                        <div class="node-content">
                            <div class="node-title">
                                <span class="mono" style="color: var(--text-muted); font-size: 0.75rem;">{{ $h->from_state }}</span>
                                <span style="color: var(--text-subtle);">→</span>
                                <span style="font-weight: 600;">{{ $h->to_state }}</span>
                            </div>
                            <div class="node-meta mono">
                                Trigger: <strong>{{ $h->trigger }}</strong> • {{ $h->created_at?->format('H:i:s, M d') }}
                            </div>
                            @if($h->metadata)
                                <div class="node-payload">
                                    {{ $h->metadata }}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div style="font-size: 0.8125rem; color: var(--text-muted);">No state transitions recorded.</div>
                @endforelse
            </div>
        </div>

        <!-- Issuance Attempts -->
        <div class="card" style="overflow: hidden;">
            <div class="card-header">
                <div class="card-title">Submission Attempts ({{ $policy->issuanceAttempts->count() }})</div>
            </div>

            @if($policy->issuanceAttempts->isNotEmpty())
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Scenario</th>
                                <th>HTTP Code</th>
                                <th>Provider Ref</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($policy->issuanceAttempts as $att)
                                <tr>
                                    <td class="mono"><strong>#{{ $att->attempt_number }}</strong></td>
                                    <td class="mono" style="font-size: 0.78rem;">{{ $att->scenario }}</td>
                                    <td>
                                        @if($att->response_status == '200')
                                            <span class="badge badge-issued">200 OK</span>
                                        @elseif($att->response_status == '202')
                                            <span class="badge badge-pending">202 PENDING</span>
                                        @elseif($att->response_status == '504')
                                            <span class="badge badge-pending">504 TIMEOUT</span>
                                        @else
                                            <span class="badge badge-failed">{{ $att->response_status }} ERR</span>
                                        @endif
                                    </td>
                                    <td class="mono" style="font-size: 0.78rem;">{{ $att->provider_reference ?: '—' }}</td>
                                    <td class="mono" style="font-size: 0.75rem; color: var(--text-muted);">{{ $att->created_at?->format('H:i:s') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="padding: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.8125rem;">
                    No insurer submission attempts made yet.
                </div>
            @endif
        </div>

        <!-- Provider Callback Events -->
        <div class="card" style="overflow: hidden;">
            <div class="card-header">
                <div class="card-title">Provider Webhook Events ({{ $policy->providerEvents->count() }})</div>
            </div>

            @if($policy->providerEvents->isNotEmpty())
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Event ID</th>
                                <th>Event Type</th>
                                <th>Status</th>
                                <th>Policy #</th>
                                <th>Received</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($policy->providerEvents as $evt)
                                <tr>
                                    <td class="mono" style="font-weight: 600; font-size: 0.78rem;">{{ $evt->event_id }}</td>
                                    <td class="mono" style="font-size: 0.78rem;">{{ $evt->event_type }}</td>
                                    <td>
                                        @if($evt->processed)
                                            <span class="badge badge-issued">Processed</span>
                                        @else
                                            <span class="badge badge-pending">Pending</span>
                                        @endif
                                    </td>
                                    <td class="mono" style="font-size: 0.78rem;">{{ $evt->policy_number ?: '—' }}</td>
                                    <td class="mono" style="font-size: 0.75rem; color: var(--text-muted);">{{ $evt->created_at?->format('H:i:s') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="padding: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.8125rem;">
                    No asynchronous provider callbacks received for this policy.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
