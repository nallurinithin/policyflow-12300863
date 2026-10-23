#!/usr/bin/env bash

# ==============================================================================
# PolicyFlow API — Quick Curl Examples
# Ensure the local server is running: php artisan serve
# ==============================================================================

BASE_URL="http://127.0.0.1:8000"

echo "=== 1. Policy Request Intake (Assignment Spec Example: ₹225.00) ==="
curl -X POST "${BASE_URL}/api/policy-requests" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "client_request_id": "AGT-2026-0001",
    "agent_id": "AGENT-17",
    "customer_name": "Sample Customer",
    "date_of_birth": "1998-04-10",
    "destination": "INTERNATIONAL",
    "trip_start": "2026-11-10",
    "trip_end": "2026-11-14",
    "plan_code": "PLUS",
    "sum_insured": 300000
  }'
echo -e "\n"

echo "=== 2. Repeated Intake (Idempotent: Expect HTTP 200 & is_duplicate=true) ==="
curl -X POST "${BASE_URL}/api/policy-requests" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "client_request_id": "AGT-2026-0001",
    "agent_id": "AGENT-17",
    "customer_name": "Sample Customer",
    "date_of_birth": "1998-04-10",
    "destination": "INTERNATIONAL",
    "trip_start": "2026-11-10",
    "trip_end": "2026-11-14",
    "plan_code": "PLUS",
    "sum_insured": 300000
  }'
echo -e "\n"

echo "=== 3. Retrieve Status & Complete Audit Trail ==="
curl -X GET "${BASE_URL}/api/policy-requests/1" \
  -H "Accept: application/json"
echo -e "\n"

echo "=== 4. Submit Policy Issuance (SUCCESS scenario) ==="
curl -X POST "${BASE_URL}/api/policy-requests/1/submit" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"scenario": "SUCCESS"}'
echo -e "\n"

echo "=== 5. Provider Status Reconciliation (Timeout Recovery) ==="
curl -X POST "${BASE_URL}/api/policy-requests/1/reconcile" \
  -H "Accept: application/json"
echo -e "\n"

echo "=== 6. Deliver Idempotent Callback Event ==="
curl -X POST "${BASE_URL}/api/mock-insurer/callback" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "event_id": "EVT-TEST-777",
    "event_type": "POLICY_ISSUED",
    "provider_reference": "PRV-EXAMPLE",
    "policy_number": "POL-INS-DEMO-001"
  }'
echo -e "\n"
