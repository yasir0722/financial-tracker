<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Financial Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: #161f30;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Animated background */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(ellipse at 20% 50%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                        radial-gradient(ellipse at 80% 20%, rgba(16, 185, 129, 0.1) 0%, transparent 40%),
                        radial-gradient(ellipse at 60% 80%, rgba(139, 92, 246, 0.1) 0%, transparent 40%);
            animation: bgShift 12s ease-in-out infinite alternate;
        }

        @keyframes bgShift {
            0%   { transform: translate(0, 0); }
            100% { transform: translate(2%, 2%); }
        }

        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1000px;
            padding: 1rem;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 32px 64px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255,255,255,0.04);
            display: flex;
            min-height: 560px;
        }

        /* Left panel */
        .login-panel-left {
            flex: 1;
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #1e3a5f 100%);
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .login-panel-left::after {
            content: '';
            position: absolute;
            top: -80px;
            right: -80px;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.2);
            filter: blur(40px);
        }

        .login-panel-left::before {
            content: '';
            position: absolute;
            bottom: -60px;
            left: -60px;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: rgba(16, 185, 129, 0.12);
            filter: blur(40px);
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            z-index: 1;
        }

        .brand-logo .logo-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }

        .brand-logo .logo-text {
            font-size: 1.15rem;
            font-weight: 700;
            color: white;
            letter-spacing: -0.3px;
        }

        .panel-hero {
            position: relative;
            z-index: 1;
        }

        .panel-hero h1 {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            line-height: 1.25;
            margin-bottom: 1rem;
            letter-spacing: -0.5px;
        }

        .panel-hero p {
            color: rgba(255,255,255,0.6);
            font-size: 0.92rem;
            line-height: 1.7;
            margin: 0;
        }

        .stats-row {
            display: flex;
            gap: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .stat-item {
            flex: 1;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 0.9rem 1rem;
        }

        .stat-item .stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
            margin-bottom: 2px;
        }

        .stat-item .stat-label {
            font-size: 0.72rem;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Right panel */
        .login-panel-right {
            width: 420px;
            flex-shrink: 0;
            background: #1c2840;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-panel-right h2 {
            font-size: 1.6rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.4rem;
            letter-spacing: -0.4px;
        }

        .login-panel-right .subtitle {
            color: rgba(255,255,255,0.45);
            font-size: 0.875rem;
            margin-bottom: 2rem;
        }

        /* Form styles */
        .form-group {
            margin-bottom: 1.1rem;
        }

        .form-label {
            font-size: 0.8rem;
            font-weight: 500;
            color: rgba(255,255,255,0.6);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 0.45rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.3);
            font-size: 0.85rem;
            pointer-events: none;
            transition: color 0.2s;
        }

        .form-control {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: white;
            border-radius: 10px;
            padding: 0.7rem 1rem 0.7rem 2.6rem;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            width: 100%;
        }

        .form-control:focus {
            background: rgba(255,255,255,0.08);
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
            color: white;
            outline: none;
        }

        .form-control:focus ~ .input-icon,
        .input-wrapper:focus-within .input-icon {
            color: #6366f1;
        }

        .form-control::placeholder {
            color: rgba(255,255,255,0.2);
        }

        .form-control.is-invalid {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
        }

        .invalid-feedback {
            color: #f87171;
            font-size: 0.78rem;
            margin-top: 0.4rem;
        }

        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255,255,255,0.3);
            cursor: pointer;
            padding: 0;
            font-size: 0.85rem;
            transition: color 0.2s;
        }

        .password-toggle:hover {
            color: rgba(255,255,255,0.7);
        }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .form-check-input {
            background-color: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 4px;
            width: 15px;
            height: 15px;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: #6366f1;
            border-color: #6366f1;
        }

        .form-check-input:focus {
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        .form-check-label {
            font-size: 0.82rem;
            color: rgba(255,255,255,0.5);
            cursor: pointer;
        }

        .forgot-link {
            font-size: 0.82rem;
            color: #6366f1;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .forgot-link:hover {
            color: #818cf8;
        }

        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 0.9rem;
            font-weight: 600;
            letter-spacing: 0.2px;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.5);
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,0.08);
        }

        .divider span {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.3);
        }

        .register-link {
            text-align: center;
            font-size: 0.84rem;
            color: rgba(255,255,255,0.4);
        }

        .register-link a {
            color: #6366f1;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .register-link a:hover {
            color: #818cf8;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
            border-radius: 10px;
            font-size: 0.85rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1.25rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-panel-left { display: none; }
            .login-panel-right {
                width: 100%;
                padding: 2.5rem 2rem;
            }
            .login-card {
                min-height: auto;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <!-- Left Branding Panel -->
            <div class="login-panel-left">
                <div class="brand-logo">
                    <div class="logo-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <span class="logo-text">Financial Tracker</span>
                </div>

                <div class="panel-hero">
                    <h1>Take control of your finances</h1>
                    <p>Track spending, manage budgets, and gain real-time insights into your financial health — all in one place.</p>
                </div>

                <div class="stats-row">
                    <div class="stat-item">
                        <div class="stat-value">100%</div>
                        <div class="stat-label">Secure</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">Live</div>
                        <div class="stat-label">Analytics</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">CSV</div>
                        <div class="stat-label">Import</div>
                    </div>
                </div>
            </div>

            <!-- Right Form Panel -->
            <div class="login-panel-right">
                <h2>Welcome back</h2>
                <p class="subtitle">Sign in to your account to continue</p>

                @if(session('status'))
                    <div class="alert-success">
                        <i class="fas fa-check-circle me-2"></i>{{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email"
                                   value="{{ old('email') }}"
                                   placeholder="you@example.com"
                                   required autofocus>
                        </div>
                        @error('email')
                            <div class="invalid-feedback"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   id="password" name="password"
                                   placeholder="••••••••"
                                   required>
                            <button type="button" class="password-toggle" onclick="togglePassword()" tabindex="-1">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-options">
                        <div class="form-check d-flex align-items-center gap-2 m-0">
                            <input type="checkbox" class="form-check-input m-0" id="remember_me" name="remember">
                            <label class="form-check-label" for="remember_me">Remember me</label>
                        </div>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="forgot-link">Forgot password?</a>
                        @endif
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="fas fa-arrow-right-to-bracket"></i>
                        Sign in
                    </button>

                    <div class="divider"><span>or</span></div>

                    <div class="register-link">
                        Don't have an account? <a href="{{ route('register') }}">Create one</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>
