<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'PolicyFlow — Insurance Issuance & Reconciliation Engine')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #0b0f19;
            --bg-card: #131b2e;
            --bg-card-hover: #19243c;
            --bg-input: #1a233a;
            --border: #22304e;
            --border-light: #2d3f66;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --text-dim: #64748b;
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --accent: #6366f1;
            --success: #10b981;
            --success-bg: rgba(16, 185, 129, 0.12);
            --warning: #f59e0b;
            --warning-bg: rgba(245, 158, 11, 0.12);
            --danger: #ef4444;
            --danger-bg: rgba(239, 68, 68, 0.12);
            --info: #06b6d4;
            --info-bg: rgba(6, 182, 212, 0.12);
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 16px;
            --shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.5);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Navigation */
        header {
            background: rgba(19, 27, 46, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1.5rem;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            text-decoration: none;
            color: inherit;
        }

        .logo-badge {
            background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
            width: 38px;
            height: 38px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.2rem;
            color: #fff;
            box-shadow: 0 2px 10px rgba(59, 130, 246, 0.4);
        }

        .logo-text h1 {
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo-text span {
            font-size: 0.72rem;
            color: var(--text-muted);
            font-weight: 500;
            letter-spacing: 0.02em;
            display: block;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link {
            padding: 0.55rem 1rem;
            border-radius: var(--radius-sm);
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.88rem;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .nav-link:hover {
            color: var(--text-main);
            background: var(--bg-card-hover);
        }

        .nav-link.active {
            color: #fff;
            background: var(--primary);
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            background: rgba(16, 185, 129, 0.12);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.25);
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 8px currentColor;
        }

        /* Container & Grid */
        main {
            flex: 1;
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        /* Flash Messages */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            font-size: 0.92rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid transparent;
        }

        .alert-success {
            background: var(--success-bg);
            color: #34d399;
            border-color: rgba(16, 185, 129, 0.3);
        }

        .alert-warning {
            background: var(--warning-bg);
            color: #fbbf24;
            border-color: rgba(245, 158, 11, 0.3);
        }

        .alert-error {
            background: var(--danger-bg);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.3);
        }

        .alert-info {
            background: var(--info-bg);
            color: #38bdf8;
            border-color: rgba(6, 182, 212, 0.3);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.62rem 1.25rem;
            border-radius: var(--radius-sm);
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .btn-success {
            background: var(--success);
            color: #fff;
        }

        .btn-warning {
            background: var(--warning);
            color: #111;
        }

        .btn-outline {
            background: transparent;
            color: var(--text-main);
            border-color: var(--border-light);
        }

        .btn-outline:hover {
            background: var(--bg-card-hover);
            border-color: var(--text-muted);
        }

        .btn-sm {
            padding: 0.4rem 0.85rem;
            font-size: 0.8rem;
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.25rem 0.65rem;
            border-radius: var(--radius-sm);
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-family: 'JetBrains Mono', monospace;
        }

        .badge-issued {
            background: var(--success-bg);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .badge-validated {
            background: var(--info-bg);
            color: #38bdf8;
            border: 1px solid rgba(6, 182, 212, 0.3);
        }

        .badge-submitted {
            background: rgba(99, 102, 241, 0.15);
            color: #a5b4fc;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        .badge-pending {
            background: var(--warning-bg);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .badge-failed {
            background: var(--danger-bg);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .badge-received {
            background: rgba(148, 163, 184, 0.15);
            color: #cbd5e1;
            border: 1px solid rgba(148, 163, 184, 0.3);
        }

        /* Cards */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border);
        }

        .card-title {
            font-size: 1.05rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.88rem;
        }

        th {
            background: rgba(11, 15, 25, 0.6);
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 0.95rem 1rem;
            border-bottom: 1px solid rgba(34, 48, 78, 0.6);
            vertical-align: middle;
        }

        tr:hover td {
            background: rgba(25, 36, 60, 0.4);
        }

        .mono {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.82rem;
        }

        /* Footer */
        footer {
            margin-top: auto;
            border-top: 1px solid var(--border);
            padding: 1.5rem;
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-dim);
            background: #090d16;
        }
    </style>
    @yield('styles')
</head>
<body>
    <header>
        <div class="nav-container">
            <a href="{{ route('dashboard') }}" class="logo-area">
                <div class="logo-badge">PF</div>
                <div class="logo-text">
                    <h1>PolicyFlow</h1>
                    <span>Fault-Tolerant Issuance & Reconciliation Engine</span>
                </div>
            </a>

            <nav class="nav-links">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    Dashboard
                </a>
                <a href="{{ route('policies.create') }}" class="nav-link {{ request()->routeIs('policies.create') ? 'active' : '' }}">
                    + New Policy Intake
                </a>
                <a href="{{ route('sandbox') }}" class="nav-link {{ request()->routeIs('sandbox') ? 'active' : '' }}">
                    Reliability Sandbox
                </a>
                <div class="status-pill" style="margin-left: 0.75rem;">
                    <span class="status-dot"></span>
                    <span>MySQL 8 Active • PHP 8.2</span>
                </div>
            </nav>
        </div>
    </header>

    <main>
        @if (session('success'))
            <div class="alert alert-success">
                <span>✓ {{ session('success') }}</span>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning">
                <span>⚠ {{ session('warning') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error">
                <span>✕ {{ session('error') }}</span>
            </div>
        @endif

        @if (session('info'))
            <div class="alert alert-info">
                <span>ℹ {{ session('info') }}</span>
            </div>
        @endif

        @yield('content')
    </main>

    <footer>
        <p>PolicyFlow Engine • Built with Laravel 11, PHP 8.2, MySQL 8, bcmath Decimal Arithmetic & Deterministic State Machine.</p>
    </footer>

    @yield('scripts')
</body>
</html>
