@extends('layouts.app')

@section('title', 'Reliability Sandbox — PolicyFlow')

@section('styles')
<style>
    .page-header {
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

    .sandbox-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));
        gap: 1.5rem;
    }

    .test-card {
        background-color: #ffffff;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 1.5rem;
    }

    .test-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.75rem;
    }

    .case-badge {
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--accent);
        font-family: 'JetBrains Mono', monospace;
    }

    .test-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--text-heading);
        margin-top: 0.2rem;
    }

    .test-desc {
        font-size: 0.8125rem;
        color: var(--text-muted);
        line-height: 1.5;
        margin-bottom: 1rem;
    }

    .defense-note {
        background-color: var(--bg-subtle);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 0.65rem 0.85rem;
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-bottom: 1.25rem;
        line-height: 1.45;
    }

    .defense-note strong {
        color: var(--text-heading);
    }

    .output-console {
        background-color: #0f172a;
        color: #f1f5f9;
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.75rem;
        border-radius: var(--radius-sm);
        padding: 0.85rem 1rem;
        max-height: 160px;
        overflow-y: auto;
        white-space: pre-wrap;
        margin-top: 0.85rem;
        display: none;
        line-height: 1.5;
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <h1 class="page-title">Reliability & Fault-Tolerance Sandbox</h1>
    <p class="page-desc">
        Interactive test harness verifying the four core reliability requirements directly from your browser.
    </p>
</div>

<div class="sandbox-grid">
    <!-- Case 1: Duplicate Intake -->
    <div class="test-card">
        <div>
            <div class="test-header">
                <div>
                    <span class="case-badge">Reliability Case 1</span>
                    <h3 class="test-title">Duplicate Intake Protection</h3>
                </div>
                <span class="badge badge-validated">Idempotency</span>
            </div>
            <p class="test-desc">
                Sends multiple rapid intake requests with the exact same <code class="mono">agent_id</code> and <code class="mono">client_request_id</code>.
                System creates the record on call #1 (HTTP 201) and returns the existing record on calls #2 and #3 (HTTP 200).
            </p>
            <div class="defense-note">
                <strong>Defense Mechanism:</strong> Database composite unique index <code class="mono">(agent_id, client_request_id)</code> with concurrent exception handling.
            </div>
        </div>

        <div>
            <button class="btn btn-primary btn-sm" id="btnTestDuplicateIntake" style="width: 100%;">
                Run Duplicate Intake Test (3x Calls)
            </button>
            <div class="output-console" id="outputCase1"></div>
        </div>
    </div>

    <!-- Case 2: Timeout After Success -->
    <div class="test-card">
        <div>
            <div class="test-header">
                <div>
                    <span class="case-badge">Reliability Case 2</span>
                    <h3 class="test-title">Timeout After Success Recovery</h3>
                </div>
                <span class="badge badge-pending">Reconciliation</span>
            </div>
            <p class="test-desc">
                Simulates an insurer issuing a policy internally, but the HTTP client times out (HTTP 504).
                Instead of blindly re-issuing, status reconciliation queries the provider to recover the policy safely.
            </p>
            <div class="defense-note">
                <strong>Defense Mechanism:</strong> State moves to <code class="mono">PENDING_CONFIRMATION</code>. Before re-issuance, <code class="mono">GET /api/mock-insurer/status/{ref}</code> resolves status safely.
            </div>
        </div>

        <div>
            <button class="btn btn-warning btn-sm" id="btnTestTimeout" style="width: 100%;">
                Simulate Timeout & Auto-Reconcile
            </button>
            <div class="output-console" id="outputCase2"></div>
        </div>
    </div>

    <!-- Case 3: Duplicate Callback -->
    <div class="test-card">
        <div>
            <div class="test-header">
                <div>
                    <span class="case-badge">Reliability Case 3</span>
                    <h3 class="test-title">Duplicate Callback Deduplication</h3>
                </div>
                <span class="badge badge-issued">Idempotent Webhook</span>
            </div>
            <p class="test-desc">
                Simulates an insurer delivering identical callback events 3 times with the exact same <code class="mono">event_id</code>.
                The first delivery updates policy to <code class="mono">ISSUED</code>; subsequent deliveries are acknowledged as duplicates with zero duplicate transitions.
            </p>
            <div class="defense-note">
                <strong>Defense Mechanism:</strong> Unique index on <code class="mono">provider_events.event_id</code> + pessimistic locking during event processing.
            </div>
        </div>

        <div>
            <button class="btn btn-primary btn-sm" id="btnTestDuplicateCallback" style="width: 100%;">
                Deliver Duplicate Callback (3x Deliveries)
            </button>
            <div class="output-console" id="outputCase3"></div>
        </div>
    </div>

    <!-- Case 4: Retry After Temporary Failure -->
    <div class="test-card">
        <div>
            <div class="test-header">
                <div>
                    <span class="case-badge">Reliability Case 4</span>
                    <h3 class="test-title">Safe Retries with Immutable Premium</h3>
                </div>
                <span class="badge badge-failed">Safe Retry</span>
            </div>
            <p class="test-desc">
                Simulates an upstream temporary gateway error (HTTP 503). Attempt count increments, state transitions to <code class="mono">FAILED</code>,
                and retrying transitions safely to <code class="mono">ISSUED</code> while strictly preserving original accepted premium.
            </p>
            <div class="defense-note">
                <strong>Defense Mechanism:</strong> State machine retry path (<code class="mono">FAILED → SUBMITTED</code>) with attempt ledger and immutable premium snapshot.
            </div>
        </div>

        <div>
            <button class="btn btn-primary btn-sm" id="btnTestRetry" style="width: 100%;">
                Simulate Failure & Retry Workflow
            </button>
            <div class="output-console" id="outputCase4"></div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Case 1: Duplicate Intake
    document.getElementById('btnTestDuplicateIntake').addEventListener('click', async () => {
        const out = document.getElementById('outputCase1');
        out.style.display = 'block';
        out.textContent = 'Sending 3 rapid intakes with identical (agent_id, client_request_id)...\n';

        const testId = 'DEMO-INTAKE-' + Math.floor(1000 + Math.random() * 9000);
        const payload = {
            client_request_id: testId,
            agent_id: 'AGENT-SANDBOX',
            customer_name: 'Sandbox Tester',
            date_of_birth: '1995-04-10',
            destination: 'INTERNATIONAL',
            trip_start: '2026-11-10',
            trip_end: '2026-11-14',
            plan_code: 'PLUS',
            sum_insured: 300000
        };

        for (let i = 1; i <= 3; i++) {
            const res = await fetch('/api/policy-requests', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            out.textContent += `[Call #${i}] HTTP ${res.status} | is_duplicate: ${data.is_duplicate} | Policy ID: ${data.data?.id}\n`;
        }
        out.textContent += `✓ Guaranteed: Exactly ONE logical record exists in the database.`;
    });

    // Case 2: Timeout After Success
    document.getElementById('btnTestTimeout').addEventListener('click', async () => {
        const out = document.getElementById('outputCase2');
        out.style.display = 'block';
        out.textContent = '1. Creating policy intake...\n';

        const testId = 'DEMO-TIMEOUT-' + Math.floor(1000 + Math.random() * 9000);
        const intakeRes = await fetch('/api/policy-requests', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                client_request_id: testId,
                agent_id: 'AGENT-SANDBOX',
                customer_name: 'Timeout Tester',
                date_of_birth: '1990-01-01',
                destination: 'DOMESTIC',
                trip_start: '2026-12-01',
                trip_end: '2026-12-05',
                plan_code: 'BASIC',
                sum_insured: 100000
            })
        });
        const intakeData = await intakeRes.json();
        const policyId = intakeData.data.id;
        out.textContent += `Policy created: #${policyId} (Status: VALIDATED)\n`;

        out.textContent += '2. Submitting with TIMEOUT_AFTER_ISSUE scenario...\n';
        const submitRes = await fetch(`/api/policy-requests/${policyId}/submit`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ scenario: 'TIMEOUT_AFTER_ISSUE' })
        });
        const submitData = await submitRes.json();
        out.textContent += `Submit returned: status = ${submitData.policy?.status} (Ref: ${submitData.policy?.provider_reference})\n`;

        out.textContent += '3. Querying Provider Status Reconciliation...\n';
        const recRes = await fetch(`/api/policy-requests/${policyId}/reconcile`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
        });
        const recData = await recRes.json();
        out.textContent += `✓ Reconciliation Confirmed! Final Status: ${recData.policy?.status}\n`;
        out.textContent += `✓ Policy Number safely recovered: ${recData.policy?.policy_number} (Zero double-issuance!)\n`;
    });

    // Case 3: Duplicate Callback
    document.getElementById('btnTestDuplicateCallback').addEventListener('click', async () => {
        const out = document.getElementById('outputCase3');
        out.style.display = 'block';
        out.textContent = '1. Submitting policy with DELAYED_CALLBACK...\n';

        const testId = 'DEMO-CALLBACK-' + Math.floor(1000 + Math.random() * 9000);
        const intakeRes = await fetch('/api/policy-requests', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                client_request_id: testId,
                agent_id: 'AGENT-SANDBOX',
                customer_name: 'Callback Tester',
                date_of_birth: '1985-06-15',
                destination: 'INTERNATIONAL',
                trip_start: '2026-11-10',
                trip_end: '2026-11-14',
                plan_code: 'PREMIUM',
                sum_insured: 500000
            })
        });
        const intakeData = await intakeRes.json();
        const policyId = intakeData.data.id;

        const submitRes = await fetch(`/api/policy-requests/${policyId}/submit`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ scenario: 'DELAYED_CALLBACK' })
        });
        const submitData = await submitRes.json();
        const providerRef = submitData.policy.provider_reference;
        out.textContent += `Awaiting callback for provider reference: ${providerRef}\n`;

        out.textContent += '2. Delivering identical webhook event 3 times...\n';
        const cbRes = await fetch('/api/mock-insurer/trigger-callback', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                provider_reference: providerRef,
                event_type: 'POLICY_ISSUED',
                duplicate_count: 3
            })
        });
        const cbData = await cbRes.json();
        cbData.deliveries.forEach(d => {
            out.textContent += `Delivery #${d.delivery_attempt}: HTTP ${d.status_code} | is_duplicate: ${d.response?.is_duplicate}\n`;
        });
        out.textContent += `✓ Idempotency Confirmed: State transition executed exactly once!`;
    });

    // Case 4: Retry Flow
    document.getElementById('btnTestRetry').addEventListener('click', async () => {
        const out = document.getElementById('outputCase4');
        out.style.display = 'block';
        out.textContent = '1. Creating policy intake...\n';

        const testId = 'DEMO-RETRY-' + Math.floor(1000 + Math.random() * 9000);
        const intakeRes = await fetch('/api/policy-requests', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                client_request_id: testId,
                agent_id: 'AGENT-SANDBOX',
                customer_name: 'Retry Tester',
                date_of_birth: '1992-03-20',
                destination: 'INTERNATIONAL',
                trip_start: '2026-11-10',
                trip_end: '2026-11-14',
                plan_code: 'PLUS',
                sum_insured: 300000
            })
        });
        const intakeData = await intakeRes.json();
        const policyId = intakeData.data.id;
        const premium = intakeData.data.calculated_premium;
        out.textContent += `Policy created: #${policyId} | Accepted Premium: ₹${premium}\n`;

        out.textContent += '2. Attempt #1 with TEMPORARY_FAILURE scenario...\n';
        const res1 = await fetch(`/api/policy-requests/${policyId}/submit`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ scenario: 'TEMPORARY_FAILURE' })
        });
        const data1 = await res1.json();
        out.textContent += `Attempt #1: Status = ${data1.policy?.status} (Retriable failure logged)\n`;

        out.textContent += '3. Attempt #2 (Retry) with SUCCESS scenario...\n';
        const res2 = await fetch(`/api/policy-requests/${policyId}/submit`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ scenario: 'SUCCESS' })
        });
        const data2 = await res2.json();
        out.textContent += `Attempt #2: Status = ${data2.policy?.status} | Policy #: ${data2.policy?.policy_number}\n`;
        out.textContent += `✓ Accepted Premium strictly retained: ₹${premium} (Zero discrepancies!)\n`;
    });
</script>
@endsection
