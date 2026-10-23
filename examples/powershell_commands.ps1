# PolicyFlow API — PowerShell Examples

$baseUrl = "http://127.0.0.1:8000"

# 1. Policy Intake (Assignment Spec Example: Rs 225.00)
Write-Host "1. Submitting Policy Intake..." -ForegroundColor Cyan
$body = @{
    client_request_id = "AGT-2026-0001"
    agent_id          = "AGENT-17"
    customer_name     = "Sample Customer"
    date_of_birth     = "1998-04-10"
    destination       = "INTERNATIONAL"
    trip_start        = "2026-11-10"
    trip_end          = "2026-11-14"
    plan_code         = "PLUS"
    sum_insured       = 300000
} | ConvertTo-Json

$response = Invoke-RestMethod -Uri "$baseUrl/api/policy-requests" -Method Post -ContentType "application/json" -Body $body
$response | ConvertTo-Json -Depth 4

# 2. Repeated Intake (Idempotent: Expect 200 OK & is_duplicate: true)
Write-Host "`n2. Sending Duplicate Intake (Idempotency Test)..." -ForegroundColor Cyan
$dupResponse = Invoke-RestMethod -Uri "$baseUrl/api/policy-requests" -Method Post -ContentType "application/json" -Body $body
$dupResponse | ConvertTo-Json -Depth 4

# 3. Retrieve Status & Audit Trail
Write-Host "`n3. Retrieving Status and Complete Audit Trail..." -ForegroundColor Cyan
$statusResponse = Invoke-RestMethod -Uri "$baseUrl/api/policy-requests/1" -Method Get
$statusResponse | ConvertTo-Json -Depth 4
