# AI Usage & Collaboration Log

## Project: PolicyFlow — Fault-Tolerant Insurance Issuance Engine

This document details how Artificial Intelligence was utilized collaboratively as a pair-programming partner during the development of PolicyFlow, adhering strictly to the candidate assignment guidelines.

---

## 1. Collaboration Philosophy & Approach

The project was executed through an interactive pair-programming methodology:
- **Human Guidance & Verification**: Architecture direction, environment verification, database configuration in MySQL Workbench, business logic validation, test case criteria, and git commit staging were steered by the developer.
- **AI Acceleration**: Boilerplate generation, decimal-safe `bcmath` mathematical logic, state machine matrix definitions, Eloquent relationship mapping, and exhaustive automated test suite construction.

---

## 2. Key Areas of AI Collaboration

### A. Architectural Design & State Machine Workflow
- **Discussion**: Defining the exact transition graph to guarantee that an `ISSUED` policy can never be regressed, overwritten, or re-submitted.
- **Implementation**: Structured `PolicyStateMachine` with a closed transition matrix and integrated pessimistic database locking (`lockForUpdate`).

### B. High-Precision Financial Arithmetic
- **Discussion**: Avoiding floating-point precision issues in currency calculations (such as IEEE-754 binary floating-point drift).
- **Implementation**: Wrote `PremiumCalculator` using string-based PHP `bcmath` functions throughout, verifying the exact assignment example: `20 × 5 × 1.00 × 1.80 × 1.25 = ₹225.00`.

### C. Concurrency & Reliability Hardening
- **Discussion**: Designing defense mechanisms for the 4 core reliability scenarios.
- **Implementation**:
  1. **Duplicate Intake**: Applied database-level unique composite index `(agent_id, client_request_id)` with both pre-check query and `QueryException` catch to resolve near-simultaneous races without 500 errors.
  2. **Timeout After Success**: Built provider status lookup (`GET /api/mock-insurer/status/{ref}`) and reconciliation service before any re-submission.
  3. **Duplicate Callback**: Deduplicated callbacks using a unique `event_id` in `provider_events` with idempotent 200 acknowledgments.
  4. **Retry After Failure**: Enforced immutable premium snapshots across retries, maintaining attempt counts in `issuance_attempts`.

### D. Automated Testing Rigor
- Developed 36 automated unit and feature tests with 168 assertions covering 100% of required paths, edge cases, date validations, and concurrency defenses.

---

## 3. Milestones & Progressive Commit Trail

Rather than squashing all changes into a single final commit, development was structured into distinct, logical milestones with clean Git commits:
1. `chore: initialize Laravel 11 project with MySQL configuration`
2. `feat: add database schema and Eloquent models`
3. `feat: add policy intake API and decimal-safe premium calculator`
4. `feat: implement issuance workflow, mock insurer, and reliability guarantees`
5. `feat: add operational web dashboard, reliability sandbox, seeders, and documentation`
