<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'PolicyFlow — Insurance Issuance & Reconciliation Engine')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-page: #f8fafc;
            --bg-surface: #ffffff;
            --bg-subtle: #f1f5f9;
            --border: #e2e8f0;
            --border-hover: #cbd5e1;
            --text-heading: #0f172a;
            --text-body: #334155;
            --text-muted: #64748b;
            --text-subtle: #94a3b8;
            
            --primary: #0f172a;
            --primary-hover: #1e293b;
            --accent: #2563eb;
            --accent-hover: #1d4ed8;

            --badge-issued-bg: #ecfdf5;
            --badge-issued-text: #065f46;
            --badge-issued-border: #a7f3d0;

            --badge-validated-bg: #eff6ff;
            --badge-validated-text: #1e40af;
            --badge-validated-border: #bfdbfe;

            --badge-pending-bg: #fffbeb;
            --badge-pending-text: #92400e;
            --badge-pending-border: #fde68a;

            --badge-failed-bg: #fef2f2;
            --badge-failed-text: #991b1b;
            --badge-failed-border: #fecaca;

            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg-page);
            color: var(--text-body);
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            -webkit-font-smoothing: antialiased;
        }

        /* Top Header */
        header {
            background-color: #ffffff;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 40;
        }

        .header-inner {
            max-width: 1320px;
            margin: 0 auto;
            padding: 0 1.5rem;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: inherit;
        }

        .brand-icon {
            width: 32px;
            height: 32px;
            background-color: var(--primary);
            color: #ffffff;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: -0.02em;
        }

        .brand-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-heading);
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .brand-subtitle {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 400;
            border-left: 1px solid var(--border);
            padding-left: 0.6rem;
            margin-left: 0.2rem;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .nav-item {
            padding: 0.45rem 0.85rem;
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .nav-item:hover {
            color: var(--text-heading);
            background-color: var(--bg-subtle);
        }

        .nav-item.active {
            color: var(--text-heading);
            background-color: var(--bg-subtle);
            font-weight: 600;
        }

        .system-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.25rem 0.65rem;
            background-color: var(--bg-subtle);
            border: 1px solid var(--border);
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-muted);
            margin-left: 0.75rem;
        }

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: #10b981;
        }

        /* Main Container */
        main {
            flex: 1;
            max-width: 1320px;
            width: 100%;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        /* Alerts */
        .alert {
            padding: 0.85rem 1.15rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid transparent;
        }

        .alert-success {
            background-color: #f0fdf4;
            color: #166534;
            border-color: #bbf7d0;
        }

        .alert-warning {
            background-color: #fffbeb;
            color: #92400e;
            border-color: #fde68a;
        }

        .alert-error {
            background-color: #fef2f2;
            color: #991b1b;
            border-color: #fecaca;
        }

        .alert-info {
            background-color: #f0f9ff;
            color: #075985;
            border-color: #bae6fd;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease;
            white-space: nowrap;
        }

        .btn-primary {
            background-color: var(--primary);
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
        }

        .btn-accent {
            background-color: var(--accent);
            color: #ffffff;
        }

        .btn-accent:hover {
            background-color: var(--accent-hover);
        }

        .btn-outline {
            background-color: #ffffff;
            color: var(--text-body);
            border-color: var(--border);
        }

        .btn-outline:hover {
            background-color: var(--bg-subtle);
            border-color: var(--border-hover);
            color: var(--text-heading);
        }

        .btn-warning {
            background-color: #d97706;
            color: #ffffff;
        }

        .btn-warning:hover {
            background-color: #b45309;
        }

        .btn-sm {
            padding: 0.35rem 0.75rem;
            font-size: 0.8125rem;
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.2rem 0.55rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        .badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: currentColor;
        }

        .badge-issued {
            background-color: var(--badge-issued-bg);
            color: var(--badge-issued-text);
            border: 1px solid var(--badge-issued-border);
        }

        .badge-validated {
            background-color: var(--badge-validated-bg);
            color: var(--badge-validated-text);
            border: 1px solid var(--badge-validated-border);
        }

        .badge-submitted {
            background-color: #f5f3ff;
            color: #6d28d9;
            border: 1px solid #ddd6fe;
        }

        .badge-pending {
            background-color: var(--badge-pending-bg);
            color: var(--badge-pending-text);
            border: 1px solid var(--badge-pending-border);
        }

        .badge-failed {
            background-color: var(--badge-failed-bg);
            color: var(--badge-failed-text);
            border: 1px solid var(--badge-failed-border);
        }

        .badge-received {
            background-color: var(--bg-subtle);
            color: var(--text-muted);
            border: 1px solid var(--border);
        }

        /* Cards */
        .card {
            background-color: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
        }

        .card-padded {
            padding: 1.5rem;
        }

        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-heading);
        }

        /* Forms */
        .form-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--text-heading);
            margin-bottom: 0.35rem;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 0.55rem 0.85rem;
            background-color: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            color: var(--text-heading);
            outline: none;
            transition: all 0.15s ease;
        }

        .form-input:focus, .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-help {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.3rem;
        }

        .mono {
            font-family: 'JetBrains Mono', monospace;
            font-feature-settings: "tnum";
        }

        /* Tables */
        .table-container {
            width: 100%;
            overflow-x: auto;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
            text-align: left;
        }

        table.data-table th {
            background-color: #fafbfc;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.75rem 1.25rem;
            border-bottom: 1px solid var(--border);
        }

        table.data-table td {
            padding: 0.85rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            color: var(--text-body);
            vertical-align: middle;
        }

        table.data-table tr:hover td {
            background-color: #f8fafc;
        }

        /* Footer */
        footer {
            margin-top: auto;
            border-top: 1px solid var(--border);
            background-color: #ffffff;
            padding: 1.5rem;
            text-align: center;
            font-size: 0.8125rem;
            color: var(--text-muted);
        }
    </style>
    @yield('styles')
</head>
<body>
    <header>
        <div class="header-inner">
            <a href="{{ route('dashboard') }}" class="brand">
                <div class="brand-icon">PF</div>
                <div class="brand-title">
                    PolicyFlow
                    <span class="brand-subtitle">Insurance Engine</span>
                </div>
            </a>

            <nav class="nav-menu">
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    Dashboard
                </a>
                <a href="{{ route('policies.create') }}" class="nav-item {{ request()->routeIs('policies.create') ? 'active' : '' }}">
                    New Intake
                </a>
                <a href="{{ route('sandbox') }}" class="nav-item {{ request()->routeIs('sandbox') ? 'active' : '' }}">
                    Reliability Sandbox
                </a>
            </nav>
        </div>
    </header>

    <main>
        @if (session('success'))
            <div class="alert alert-success">
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning">
                <span>{{ session('warning') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error">
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if (session('info'))
            <div class="alert alert-info">
                <span>{{ session('info') }}</span>
            </div>
        @endif

        @yield('content')
    </main>

    <footer>
        PolicyFlow • Travel-Insurance Issuance & Reconciliation Engine • Deterministic State Machine with bcmath Decimal Arithmetic
    </footer>

    @yield('scripts')
</body>
</html>
