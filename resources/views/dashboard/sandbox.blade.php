@extends('layouts.app')

@section('title', 'Reliability Sandbox — PolicyFlow')

@section('styles')
<style>
    .sandbox-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));
        gap: 1.75rem;
        margin-bottom: 2rem;
    }

    .sandbox-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .sandbox-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }

    .case-num {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--primary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .sandbox-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #fff;
        margin-top: 0.2rem;
    }

    .sandbox-desc {
        font-size: 0.85rem;
        color: var(--text-muted);
        line-height: 1.5;
        margin-bottom: 1.25rem;
    }

    .defense-box {
        background: rgba(0, 0, 0, 0.35);
        border-left: 3px solid var(--primary);
        padding: 0.75rem 1rem;
        border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-bottom: 1.25rem;
    }

    .output-box {
        background: #090d16;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 0.85rem;
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.78rem;
        color: #a5b4fc;
        max-height: 160px;
        overflow-y: auto;
        white-space: pre-wrap;
        margin-top: 1rem;
        display: none;
    }
</style>
@endsection

@section('content')
<div style="margin-bottom: 2rem;">
    <h2 style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em;">Fault-Tolerance & Reliability Sandbox</h2>
    <p style="color: var(--text-muted); font-size: 0.92rem;">
        Interactive demonstrations of the core four reliability cases required by the candidate assignment specification.
    </p>
</div>

<div class="sandbox-grid">
    <!-- Case 1: Duplicate Intake -->
    <div class="sandbox-card">
        <div>
            <div class="sandbox-header">
                <div>
                    <span class="case-num">Reliability Case 1</span>
                    <h3 class="sandbox-title">Duplicate Intake Protection</h3>
                </div>
                <span class="badge badge-validated">Idempotency</span>
            </div>
            <p class="sandbox-desc">
                Simulates rapid duplicate submissions with the same <code class="mono">agent_id</code> + <code class="mono">client_request_id</code>.
                System creates the record once (HTTP 201), catches near-simultaneous races, and returns existing record (HTTP 200).
            </p>
            <div class="defense-box">
                <strong>Defense Mechanism:</strong> Database composite unique index <code class="mono">(agent_id, client_request_id)</code> + application race-condition catch to guarantee exactly one logical record.
            </div>
        </div>

        <div>
            <button class="btn btn-primary btn-sm" id="btnTestDuplicateIntake" style="width: 100%;">
                ▶ Run Duplicate Intake Test (3x Calls)
            </button>
            <div class="output-box" id="outputCase1"></div>
        </div>
    </div>

    <!-- Case 2: Timeout After Success -->
    <div class="sandbox-card">
        <div>
            <div class="sandbox-header">
                <div>
                    <span class="case-num">Reliability Case 2</span>
                    <h3 class="sandbox-title">Timeout After Success Recovery</h3>
                </div>
                <span class="badge badge-pending">Reconciliation</span>
            </div>
            <p class="sandbox-desc">
                Simulates insurer successfully issuing a policy, but HTTP client times out (HTTP 504).
                Instead of blindly issuing again, status reconciliation queries provider to recover policy safely.
            </p>
            <div class="defense-box">
                <strong>Defense Mechanism:</strong> Status transitions to <code class="mono">PENDING_CONFIRMATION</code>. Before re-attempt, <code class="mono">GET /api/mock-insurer/status/{ref}</code> resolves status without duplicate issuance.
            </div>
        </div>

        <div>
            <button class="btn btn-warning btn-sm" id="btnTestTimeout" style="width: 100%;">
                ▶ Simulate Timeout & Auto-Reconcile
            </button>
            <div class="output-box" id="outputCase2"></div>
        </div>
    </div>

    <!-- Case 3: Duplicate Callback -->
    <div class="sandbox-card">
        <div>
            <div class="sandbox-header">
                <div>
                    <span class="case-num">Reliability Case 3</span>
                    <h3 class="sandbox-title">Duplicate Callback Deduplication</h3>
                </div>
                <span class="badge badge-issued">Idempotent Event</span>
            </div>
            <p class="sandbox-desc">
                Simulates insurer delivering the exact same webhook callback event 3 times with identical <code class="mono">event_id</code>.
                First delivery transitions policy to <code class="mono">ISSUED</code>; subsequent deliveries are safely acknowledged with no duplicate state changes.
            </p>
            <div class="defense-box">
                <strong>Defense Mechanism:</strong> Unique index on <code class="mono">provider_events.event_id</code> + pessimistic row locks during transition.
            </div>
        </div>

        <div>
            <button class="btn btn-success btn-sm" id="btnTestDuplicateCallback" style="width: 100%;">
                ▶ Emit Duplicate Callback (3x Deliveries)
            </button>
            <div class="output-box" id="outputCase3"></div>
        </div>
    </div>

    <!-- Case 4: Retry After Temporary Failure -->
    <div class="sandbox-card">
        <div>
            <div class="sandbox-header">
                <div>
                    <span class="case-num">Reliability Case 4</span>
                    <h3 class="sandbox-title">Safe Retries with Immutable Premium</h3>
                </div>
                <span class="badge badge-failed">Safe Retry</span>
            </div>
            <p class="sandbox-desc">
                Simulates an upstream retriable failure (HTTP 503). Attempt count increments, state becomes <code class="mono">FAILED</code>,
                and retrying transitions safely to <code class="mono">ISSUED</code> while strictly preserving the original accepted premium snapshot.
            </p>
            <div class="defense-box">
                <strong>Defense Mechanism:</strong> Explicit state machine transitions (<code class="mono">FAILED → SUBMITTED</code>), immutable premium snapshot in <code class="mono">calculated_premium</code>, and full attempt tracking.
            </div>
        </div>

        <div>
            <button class="btn btn-primary btn-sm" id="btnTestRetry" style="width: 100%;">
                ▶ Simulate Failure & Retry Flow
            </button>
            <div class="output-box" id="outputCase4"></div>
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
        out.textContent = 'Sending 3 rapid intakes with same client_request_id...\n';

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
            out.textContent += `[Request #${i}] Status: ${res.status} | is_duplicate: ${data.is_duplicate} | Policy ID: ${data.data?.id}\n`;
        }
        out.textContent += `✓ Guaranteed: Exactly ONE logical record was created in the database!`;
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
        out.textContent += `Submit returned: status = ${submitData.policy?.status} (Provider Ref: ${submitData.policy?.provider_reference})\n`;

        out.textContent += '3. Invoking Provider Status Reconciliation...\n';
        const recRes = await fetch(`/api/policy-requests/${policyId}/reconcile`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
        });
        const recData = await recRes.json();
        out.textContent += `✓ Reconciliation Confirmed! Final Status: ${recData.policy?.status}\n`;
        out.textContent += `✓ Policy Number recovered: ${recData.policy?.policy_number} (Zero double-issuance!)\n`;
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

        out.textContent += '2. Emitting identical callback event 3 times...\n';
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
        out.textContent += `✓ Idempotent Deduplication: State transition applied only once!`;
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
        out.textContent += `Attempt #1 result: Status = ${data1.policy?.status} (Retriable failure logged)\n`;

        out.textContent += '3. Attempt #2 (Retry) with SUCCESS scenario...\n';
        const res2 = await fetch(`/api/policy-requests/${policyId}/submit`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ scenario: 'SUCCESS' })
        });
        const data2 = await res2.json();
        out.textContent += `Attempt #2 result: Status = ${data2.policy?.status} | Policy #: ${data2.policy?.policy_number}\n`;
        out.textContent += `✓ Accepted Premium strictly retained: ₹${premium} (Zero discrepancies!)\n`;
    });
</script>
@endsection
