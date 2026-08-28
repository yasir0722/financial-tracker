<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Financial Tracker')</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="alternate icon" href="/favicon.ico">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Custom styles -->
    @stack('styles')

    <style>
        /* =============================================
           BASE
        ============================================= */
        *, *::before, *::after { box-sizing: border-box; }

        :root {
            --bg-base:      #161f30;
            --bg-surface:   #1c2840;
            --bg-elevated:  #1f2f44;
            --bg-hover:     #27384f;
            --border:       rgba(255,255,255,0.1);
            --border-strong:rgba(255,255,255,0.16);
            --text-primary: #e8edf5;
            --text-muted:   rgba(232,237,245,0.6);
            --text-subtle:  rgba(232,237,245,0.38);
            --accent:       #6366f1;
            --accent-light: #818cf8;
            --accent-glow:  rgba(99,102,241,0.25);
            --success:      #10b981;
            --danger:       #ef4444;
            --warning:      #f59e0b;
            --info:         #38bdf8;
            --sidebar-w:    220px;
            --topbar-h:     56px;
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: var(--bg-base);
            color: var(--text-primary);
            margin: 0;
            overflow-x: hidden;
        }

        /* =============================================
           SIDEBAR
        ============================================= */
        .app-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--bg-surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            z-index: 100;
            overflow-y: auto;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 1.1rem 1.25rem;
            border-bottom: 1px solid var(--border);
            text-decoration: none;
        }

        .sidebar-brand .brand-icon {
            width: 34px;
            height: 34px;
            background: linear-gradient(135deg, var(--accent), #8b5cf6);
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 4px 12px var(--accent-glow);
        }

        .sidebar-brand .brand-name {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.2px;
            white-space: nowrap;
        }

        .sidebar-nav {
            flex: 1;
            padding: 1rem 0.75rem;
        }

        .sidebar-section-label {
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-subtle);
            padding: 0 0.75rem;
            margin: 1rem 0 0.4rem;
        }

        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.55rem 0.75rem;
            border-radius: 8px;
            color: var(--text-muted);
            font-size: 0.855rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.18s ease;
            margin-bottom: 2px;
        }

        .sidebar-nav .nav-link i {
            width: 16px;
            font-size: 0.8rem;
            text-align: center;
            flex-shrink: 0;
        }

        .sidebar-nav .nav-link:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }

        .sidebar-nav .nav-link.active {
            background: rgba(118, 119, 161, 0.15);
            color: var(--accent-light);
            border: 1px solid rgba(99,102,241,0.2);
        }

        .sidebar-nav .nav-link.active i {
            color: var(--accent);
        }

        .sidebar-footer {
            padding: 0.75rem;
            border-top: 1px solid var(--border);
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.6rem 0.75rem;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.18s;
            text-decoration: none;
        }

        .sidebar-user:hover { background: var(--bg-hover); }

        .user-avatar {
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, var(--accent), #8b5cf6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            color: white;
            font-weight: 600;
            flex-shrink: 0;
        }

        .user-name {
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* =============================================
           TOPBAR
        ============================================= */
        .app-topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-w);
            right: 0;
            height: var(--topbar-h);
            background: var(--bg-surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            z-index: 99;
        }

        .topbar-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .topbar-user-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--bg-elevated);
            border: 1px solid var(--border-strong);
            border-radius: 8px;
            padding: 0.35rem 0.75rem;
            color: var(--text-primary);
            font-size: 0.82rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.18s;
        }

        .topbar-user-btn:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }

        /* =============================================
           MAIN CONTENT
        ============================================= */
        .app-main {
            margin-left: var(--sidebar-w);
            padding-top: var(--topbar-h);
            min-height: 100vh;
        }

        .main-content {
            padding: 1.75rem;
        }

        /* =============================================
           BOOTSTRAP DARK OVERRIDES
        ============================================= */

        /* Cards */
        .card {
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text-primary);
        }

        .card-header {
            background: rgba(255,255,255,0.03);
            border-bottom: 1px solid var(--border);
            color: var(--text-primary);
            padding: 0.9rem 1.25rem;
            font-weight: 600;
        }

        .card-body { padding: 1.25rem; }

        /* Colored stat cards */
        .card.bg-primary  { background: linear-gradient(135deg,#3730a3,#4f46e5) !important; border:none; }
        .card.bg-success  { background: linear-gradient(135deg,#065f46,#059669) !important; border:none; }
        .card.bg-danger   { background: linear-gradient(135deg,#7f1d1d,#dc2626) !important; border:none; }
        .card.bg-info     { background: linear-gradient(135deg,#0c4a6e,#0284c7) !important; border:none; }
        .card.bg-warning  { background: linear-gradient(135deg,#78350f,#d97706) !important; border:none; }
        .card.text-white  { color: #fff !important; }

        /* Tables */
        .table {
            color: var(--text-primary);
            border-color: var(--border);
        }

        .table > :not(caption) > * > * {
            background-color: transparent;
            color: var(--text-primary);
            border-bottom-color: var(--border);
        }

        .table thead th {
            background: rgba(255,255,255,0.04);
            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border-strong);
        }

        .table-hover > tbody > tr:hover > * {
            background-color: var(--bg-hover);
            color: var(--text-primary);
        }

        .table-responsive { border-radius: 8px; }

        /* Forms */
        .form-control, .form-select {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border-strong);
            color: var(--text-primary);
            border-radius: 8px;
        }

        select.form-control option, .form-select option {
            background: var(--bg-elevated);
            color: var(--text-primary);
        }

        select.form-control option:disabled, .form-select option:disabled {
            color: var(--text-subtle);
        }

        .form-control:focus, .form-select:focus {
            background: rgba(255,255,255,0.08);
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
            color: var(--text-primary);
        }

        .form-control::placeholder { color: var(--text-subtle); }

        .form-control.is-invalid { border-color: var(--danger); }
        .invalid-feedback { color: #f87171; }

        .form-label {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 0.35rem;
        }

        .form-check-input {
            background-color: rgba(255,255,255,0.08);
            border-color: rgba(255,255,255,0.2);
        }
        .form-check-input:checked {
            background-color: var(--accent);
            border-color: var(--accent);
        }

        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), #8b5cf6);
            border: none;
            font-weight: 500;
        }
        .btn-primary:hover { background: linear-gradient(135deg, #4f46e5, #7c3aed); border: none; }

        .btn-success  { background: #059669; border-color: #059669; }
        .btn-success:hover { background: #047857; border-color: #047857; }

        .btn-danger   { background: var(--danger); border-color: var(--danger); }
        .btn-danger:hover { background: #dc2626; border-color: #dc2626; }

        .btn-warning  { background: var(--warning); border-color: var(--warning); color:#fff; }
        .btn-outline-secondary {
            color: var(--text-muted);
            border-color: var(--border-strong);
        }
        .btn-outline-secondary:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
            border-color: var(--border-strong);
        }

        /* Alerts */
        .alert {
            border-radius: 10px;
            font-size: 0.875rem;
        }
        .alert-success {
            background: rgba(16,185,129,0.1);
            border: 1px solid rgba(16,185,129,0.25);
            color: #6ee7b7;
        }
        .alert-danger {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            color: #fca5a5;
        }
        .alert-warning {
            background: rgba(245,158,11,0.1);
            border: 1px solid rgba(245,158,11,0.25);
            color: #fcd34d;
        }
        .btn-close { filter: invert(1) brightness(0.7); }

        /* Badges */
        .badge { font-weight: 500; letter-spacing: 0.2px; }

        /* Dropdowns */
        .dropdown-menu {
            background: var(--bg-elevated);
            border: 1px solid var(--border-strong);
            border-radius: 10px;
            box-shadow: 0 16px 40px rgba(0,0,0,0.5);
        }
        .dropdown-item {
            color: var(--text-muted);
            font-size: 0.875rem;
            border-radius: 6px;
            margin: 2px 4px;
            padding: 0.45rem 0.75rem;
        }
        .dropdown-item:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }
        .dropdown-divider { border-color: var(--border); }

        /* Pagination */
        .pagination {
            gap: 2px;
        }
        .page-link {
            background: var(--bg-elevated);
            border: 1px solid var(--border-strong);
            color: var(--text-primary);
            border-radius: 7px !important;
            font-size: 0.82rem;
            font-weight: 500;
            padding: 0.38rem 0.65rem;
            transition: all 0.15s ease;
            line-height: 1.4;
        }
        .page-link:hover {
            background: var(--bg-hover);
            border-color: var(--accent);
            color: var(--accent-light);
        }
        .page-item.active .page-link {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
            box-shadow: 0 3px 10px var(--accent-glow);
        }
        .page-item.disabled .page-link {
            background: var(--bg-elevated);
            border-color: var(--border);
            color: var(--text-subtle);
            opacity: 0.6;
        }

        /* Modals */
        .modal-content {
            background: var(--bg-elevated);
            border: 1px solid var(--border-strong);
            border-radius: 14px;
            color: var(--text-primary);
        }
        .modal-header {
            border-bottom: 1px solid var(--border);
            padding: 1rem 1.25rem;
        }
        .modal-footer {
            border-top: 1px solid var(--border);
        }
        .modal-backdrop.show { opacity: 0.7; }

        /* Select2 dark overrides */
        .select2-container--default .select2-selection--single {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border-strong);
            border-radius: 8px;
            height: 38px;
            color: var(--text-primary);
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: var(--text-primary);
            line-height: 36px;
            padding-left: 10px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }
        .select2-dropdown {
            background: var(--bg-elevated);
            border: 1px solid var(--border-strong);
            border-radius: 10px;
            box-shadow: 0 16px 40px rgba(0,0,0,0.5);
        }
        .select2-container--default .select2-results__option {
            color: var(--text-muted);
            font-size: 0.875rem;
            padding: 0.45rem 0.75rem;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background: var(--bg-hover);
            color: var(--text-primary);
        }
        .select2-search--dropdown .select2-search__field {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            color: var(--text-primary);
            border-radius: 6px;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.12); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }

        /* Misc */
        hr { border-color: var(--border); opacity: 1; }
        .text-muted { color: var(--text-muted) !important; }
        h1,h2,h3,h4,h5,h6 { color: var(--text-primary); }
        .h3, .h4, .h5 { color: var(--text-primary); }

        /* Navbar override — hide old navbar for authenticated users (sidebar replaces it) */
        @auth
        .navbar { display: none !important; }
        @endauth

        /* Chart.js canvas context fix */
        canvas { max-width: 100%; }

        /* Responsive */
        @media (max-width: 768px) {
            .app-sidebar { transform: translateX(-100%); }
            .app-topbar  { left: 0; }
            .app-main    { margin-left: 0; }
        }
    </style>
</head>
<body>

    @auth
    <!-- Sidebar -->
    <aside class="app-sidebar">
        <a class="sidebar-brand" href="{{ route('dashboard') }}">
            <div class="brand-icon"><i class="fas fa-wallet"></i></div>
            <span class="brand-name">Financial Tracker</span>
        </a>

        <nav class="sidebar-nav">
            <div class="sidebar-section-label">Menu</div>

            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="fas fa-gauge-high"></i> Dashboard
            </a>
            <a class="nav-link {{ request()->routeIs('transactions.*') ? 'active' : '' }}" href="{{ route('transactions.index') }}">
                <i class="fas fa-arrow-right-arrow-left"></i> Transactions
            </a>
            <a class="nav-link {{ request()->routeIs('monitor.*') ? 'active' : '' }}" href="{{ route('monitor.index') }}">
                <i class="fas fa-chart-line"></i> Monitor
            </a>

            <div class="sidebar-section-label">Investment</div>
            <a class="nav-link {{ request()->routeIs('investments.*') ? 'active' : '' }}" href="{{ route('investments.index') }}">
                <i class="fas fa-piggy-bank"></i> Investments
            </a>

            <div class="sidebar-section-label">Car</div>
            <a class="nav-link {{ request()->routeIs('car-expenses.index') ? 'active' : '' }}" href="{{ route('car-expenses.index') }}">
                <i class="fas fa-chart-pie"></i> Car Dashboard
            </a>
            <a class="nav-link {{ request()->routeIs('car-expenses.list') ? 'active' : '' }}" href="{{ route('car-expenses.list') }}">
                <i class="fas fa-list"></i> Maintenance List
            </a>
            <a class="nav-link {{ request()->routeIs('vehicles.*') ? 'active' : '' }}" href="{{ route('vehicles.index') }}">
                <i class="fas fa-car-side"></i> Vehicles
            </a>

            <div class="sidebar-section-label">Settings</div>
            <a class="nav-link {{ request()->routeIs('spending-types.*') ? 'active' : '' }}" href="{{ route('spending-types.index') }}">
                <i class="fas fa-tags"></i> Spending Types
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="dropdown">
                <a class="sidebar-user dropdown-toggle" data-bs-toggle="dropdown" href="#" style="color:inherit;">
                    <div class="user-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                    <span class="user-name">{{ Auth::user()->name }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-up mb-2" style="bottom:100%;top:auto;">
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="fas fa-user-pen me-2"></i> Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="fas fa-right-from-bracket me-2"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </aside>

    <!-- Topbar -->
    <header class="app-topbar">
        <span class="topbar-title">@yield('title', 'Financial Tracker')</span>
        <div class="topbar-actions">
            @auth
            <div class="dropdown">
                <a class="topbar-user-btn dropdown-toggle" href="#" data-bs-toggle="dropdown">
                    <i class="fas fa-user fa-xs"></i> {{ Auth::user()->name }}
                </a>
                <ul class="dropdown-menu dropdown-menu-end mt-1">
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="fas fa-user-pen me-2"></i> Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="fas fa-right-from-bracket me-2"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
            @endauth
        </div>
    </header>
    @endauth

    @guest
    <!-- Navigation for guests -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="fas fa-wallet"></i> Financial Tracker
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">
                                <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('register') }}">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    @endguest

    <!-- App shell -->
    <div class="@auth app-main @endauth">
        <div class="main-content">
            <!-- Flash Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Chart.js dark defaults -->
    <script>
        if (typeof Chart !== 'undefined') {
            Chart.defaults.color = 'rgba(255,255,255,0.5)';
            Chart.defaults.borderColor = 'rgba(255,255,255,0.07)';
            Chart.defaults.plugins.legend.labels.color = 'rgba(255,255,255,0.6)';
        }
    </script>

    <!-- Custom scripts -->
    @stack('scripts')
</body>
</html>
