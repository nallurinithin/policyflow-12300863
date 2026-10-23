@extends('layouts.app')

@section('title', 'Policy ' . $policy->client_request_id . ' — PolicyFlow')

@section('styles')
<style>
    .detail-grid {
        display: grid;
        grid-template-columns: 1fr 1.3fr;
        gap: 1.75rem;
    }

    @media (max-width: 992px) {
        .detail-grid {
            grid-template-columns: 1fr;
        }
    }

    .info-list {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 0.65rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        font-size: 0.88rem;
    }

    .info-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .info-label {
        color: var(--text-muted);
        font-weight: 500;
    }

    .info-value {
        font-weight: 600;
        color: #fff;
    }

    /* Timeline */
    .timeline {
        position: relative;
        padding-left: 1.75rem;
        margin-top: 1rem;
    }

    .timeline::before {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        left: 8px;
        width: 2px;
        background: var(--border-light);
    }

    .timeline-item {
        position: relative;
        margin-bottom: 1.5rem;
    }

    .timeline-item:last-child {
        margin-bottom: 0;
    }

    .timeline-marker {
        position: absolute;
        left: -1.75rem;
        top: 2px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: var(--bg-card);
        border: 2px solid var(--primary);
        box-shadow: 0 0 0 3px var(--bg-body);
    }

    .timeline-marker.issued {
        border-color: var(--success);
        background: var(--success);
    }

    .timeline-marker.failed {
        border-color: var(--danger);
        background: var(--danger);
    }

    .timeline-content {
        background: var(--bg-input);
        padding: 0.85rem 1rem;
        border-radius: var(--radius-sm);
        border: 1px solid var(--border);
    }

    .timeline-title {
        font-size: 0.88rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.25rem;
    }

    .timeline-time {
        font-size: 0.72rem;
        color: var(--text-dim);
    }

    .timeline-meta {
        margin-top: 0.45rem;
        font-size: 0.76rem;
        color: var(--text-muted);
        background: rgba(0, 0, 0, 0.25);
        padding: 0.4rem 0.6rem;
        border-radius: 4px;
        word-break: break-all;
    }

    .action-panel {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 1.25rem;
        margin-bottom: 1.75rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }
</style>
@endsection

@section('content')
<!-- Header & Status -->
<div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
    <div>
        <a href="{{ route('dashboard') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; font-weight: 600;">← Back to Dashboard</a>
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0.35rem;">
            <h2 style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em;">{{ $policy->client_request_id }}</h2>
            @if($policy->status === 'ISSUED')
                <span class="badge badge-issued" style="font-size: 0.82rem; padding: 0.35rem 0.75rem;">● ISSUED</span>
            @elseif($policy->status === 'VALIDATED')
                <span class="badge badge-validated" style="font-size: 0.82rem; padding: 0.35rem 0.75rem;">● VALIDATED</span>
            @elseif($policy->status === 'SUBMITTED')
                <span class="badge badge-submitted" style="font-size: 0.82rem; padding: 0.35rem 0.75rem;">● SUBMITTED</span>
            @elseif(in_array($policy->status, ['PENDING_CONFIRMATION', 'NEEDS_RECONCILIATION']))
                <span class="badge badge-pending" style="font-size: 0.82rem; padding: 0.35rem 0.75rem;">● {{ $policy->status }}</span>
            @elseif($policy->status === 'FAILED')
                <span class="badge badge-failed" style="font-size: 0.82rem; padding: 0.35rem 0.75rem;">● FAILED</span>
            @else
                <span class="badge badge-received" style="font-size: 0.82rem; padding: 0.35rem 0.75rem;">● {{ $policy->status }}</span>
            @endif
        </div>
        <div class="mono" style="color: var(--text-dim); font-size: 0.78rem; margin-top: 0.25rem;">
            UUID: {{ $policy->uuid }} • Created: {{ $policy->created_at?->format('Y-m-d H:i:s') }}
        </div>
    </div>

    <!-- Quick Identifiers -->
    <div style="text-align: right;">
        @if($policy->policy_number)
            <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #34d399;">Official Policy Number</div>
            <div class="mono" style="font-size: 1.25rem; font-weight: 800; color: #34d399;">{{ $policy->policy_number }}</div>
        @elseif($policy->provider_reference)
            <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted);">Provider Reference</div>
            <div class="mono" style="font-size: 1.05rem; font-weight: 700; color: #fff;">{{ $policy->provider_reference }}</div>
        @endif
    </div>
</div>

<!-- Operational Actions Bar -->
<div class="action-panel">
    <div>
        <strong style="font-size: 0.95rem;">Workflow Actions</strong>
        <p style="font-size: 0.78rem; color: var(--text-muted);">Trigger insurer submission, test mock failure scenarios, or reconcile provider status.</p>
    </div>

    <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
        @if($policy->status !== 'ISSUED')
            <!-- Submit form with scenario choice -->
            <form method="POST" action="{{ route('policies.submit', $policy->id) }}" style="display: flex; gap: 0.5rem; align-items: center;">
                @csrf
                <select name="scenario" class="form-control" style="padding: 0.45rem 0.75rem; font-size: 0.82rem; width: auto;">
                    <option value="SUCCESS">Scenario: SUCCESS (Normal)</option>
                    <option value="TEMPORARY_FAILURE">Scenario: TEMPORARY_FAILURE</option>
                    <option value="TIMEOUT_AFTER_ISSUE">Scenario: TIMEOUT_AFTER_ISSUE</option>
                    <option value="DELAYED_CALLBACK">Scenario: DELAYED_CALLBACK</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">
                    Submit to Insurer →
                </button>
            </form>

            @if(in_array($policy->status, ['PENDING_CONFIRMATION', 'NEEDS_RECONCILIATION', 'FAILED']))
                <form method="POST" action="{{ route('policies.reconcile', $policy->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-sm">
                        🔄 Reconcile Status
                    </button>
                </form>
            @endif
        @else
            <div style="display: flex; align-items: center; gap: 0.5rem; color: #34d399; font-weight: 600; font-size: 0.88rem;">
                <span>✓ Policy is final and immutable. No further transitions permitted.</span>
            </div>
        @endif
    </div>
</div>

<div class="detail-grid">
    <!-- Left Column: Policy Overview & Pricing -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Policy Data Card -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Policy Details</span>
                <span class="mono" style="font-size: 0.75rem; color: var(--text-dim);">Agent: {{ $policy->agent_id }}</span>
            </div>

            <div class="info-list">
                <div class="info-item">
                    <span class="info-label">Customer Name</span>
                    <span class="info-value">{{ $policy->customer_name }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Date of Birth</span>
                    <span class="info-value mono">{{ $policy->date_of_birth?->format('Y-m-d') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Destination</span>
                    <span class="info-value">{{ $policy->destination }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Trip Dates</span>
                    <span class="info-value mono">
                        {{ $policy->trip_start?->format('Y-m-d') }} to {{ $policy->trip_end?->format('Y-m-d') }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Travel Days (inclusive)</span>
                    <span class="info-value">{{ $policy->travel_days }} days</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Plan Code</span>
                    <span class="info-value mono" style="color: #a5b4fc;">{{ $policy->plan_code }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Sum Insured (Coverage)</span>
                    <span class="info-value mono">₹{{ number_format($policy->sum_insured) }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Provider Reference</span>
                    <span class="info-value mono">{{ $policy->provider_reference ?: '—' }}</span>
                </div>
            </div>
        </div>

        <!-- Immutable Pricing Snapshot Card -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Immutable Premium Snapshot</span>
                <span class="badge badge-validated">Locked</span>
            </div>

            <div style="text-align: center; padding: 1.25rem 0; border-bottom: 1px solid var(--border);">
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Calculated Final Premium</div>
                <div style="font-size: 2.2rem; font-weight: 800; color: #34d399; letter-spacing: -0.02em;">
                    ₹{{ number_format($policy->calculated_premium, 2) }}
                </div>
                <div class="mono" style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.25rem;">
                    Decimal-Safe `bcmath` computation snapshot
                </div>
            </div>

            <div class="info-list" style="margin-top: 1rem;">
                <div class="info-item">
                    <span class="info-label">Formula</span>
                    <span class="info-value mono" style="font-size: 0.8rem;">Rate × Days × Age × Dest × Cov</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Snapshot Created At</span>
                    <span class="info-value mono" style="font-size: 0.8rem;">{{ $policy->created_at?->format('Y-m-d H:i:s') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Audit Trail, Attempts, Events -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- State Machine Transition Audit Trail -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">State Machine Audit Trail</span>
                <span class="mono" style="font-size: 0.75rem; color: var(--text-muted);">{{ $policy->stateHistory->count() }} transitions</span>
            </div>

            <div class="timeline">
                @forelse($policy->stateHistory as $h)
                    <div class="timeline-item">
                        <div class="timeline-marker {{ strtolower($h->to_state) }}"></div>
                        <div class="timeline-content">
                            <div class="timeline-title">
                                <span class="mono" style="color: var(--text-muted); font-size: 0.78rem;">{{ $h->from_state }}</span>
                                <span style="color: var(--text-dim);">→</span>
                                <strong style="color: #fff;">{{ $h->to_state }}</strong>
                            </div>
                            <div class="timeline-time">
                                Trigger: <strong class="mono" style="color: #a5b4fc;">{{ $h->trigger }}</strong> • {{ $h->created_at?->format('Y-m-d H:i:s') }}
                            </div>
                            @if($h->metadata)
                                <div class="timeline-meta mono">
                                    {{ $h->metadata }}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p style="color: var(--text-muted); font-size: 0.85rem;">No state history recorded yet.</p>
                @endforelse
            </div>
        </div>

        <!-- Issuance Attempts Log -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Insurer Issuance Attempts</span>
                <span class="mono" style="font-size: 0.75rem; color: var(--text-muted);">{{ $policy->issuanceAttempts->count() }} total attempts</span>
            </div>

            @if($policy->issuanceAttempts->isNotEmpty())
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Scenario</th>
                                <th>Status Code</th>
                                <th>Provider Ref</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($policy->issuanceAttempts as $att)
                                <tr>
                                    <td class="mono"><strong>#{{ $att->attempt_number }}</strong></td>
                                    <td><span class="mono" style="font-size: 0.8rem;">{{ $att->scenario }}</span></td>
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
                <p style="color: var(--text-muted); font-size: 0.85rem;">No submission attempts made yet. Click "Submit to Insurer" above to trigger issuance.</p>
            @endif
        </div>

        <!-- Provider Callback Events Log -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Provider Events & Callbacks</span>
                <span class="mono" style="font-size: 0.75rem; color: var(--text-muted);">{{ $policy->providerEvents->count() }} received</span>
            </div>

            @if($policy->providerEvents->isNotEmpty())
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Event ID</th>
                                <th>Type</th>
                                <th>Processed</th>
                                <th>Policy #</th>
                                <th>Received At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($policy->providerEvents as $evt)
                                <tr>
                                    <td class="mono" style="font-weight: 700;">{{ $evt->event_id }}</td>
                                    <td class="mono">{{ $evt->event_type }}</td>
                                    <td>
                                        @if($evt->processed)
                                            <span class="badge badge-issued">Processed</span>
                                        @else
                                            <span class="badge badge-pending">Pending</span>
                                        @endif
                                    </td>
                                    <td class="mono" style="font-size: 0.78rem; color: #34d399;">{{ $evt->policy_number ?: '—' }}</td>
                                    <td class="mono" style="font-size: 0.75rem; color: var(--text-muted);">{{ $evt->created_at?->format('H:i:s') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p style="color: var(--text-muted); font-size: 0.85rem;">No asynchronous callbacks received for this policy.</p>
            @endif
        </div>
    </div>
</div>
@endsection
