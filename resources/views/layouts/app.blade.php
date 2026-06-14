<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mental Health Risk Prediction')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* ── Design Tokens ── */
        :root {
            /* Brand & accent */
            --primary:        #0562E2;  /* Cobalt — buy-now CTA only */
            --primary-deep:   #0143B5;  /* Deep cobalt — pressed / radio-selected */
            --primary-soft:   rgba(5, 98, 226, 0.10);
            --fb-blue:        #1877F2;  /* Selected radio/checkbox + input activation */
            --meta-link:      #385898;  /* Legacy nav/footer link */
            --oculus-purple:  #6B3FA0;  /* VR category accent */
            --ink-button:     #1c1e21;
            --canvas:         #FFFFFF;
            --surface-soft:   #F0F2F5;
            --hairline:       #CDD0D4;
            --hairline-soft:  #E4E6EB;
            --ink-deep:       #1c1e21;
            --ink:            #3c4043;
            --charcoal:       #606770;
            --slate:          #8A8D91;
            --steel:          #989A9C;
            --stone:          #BEC3C9;
            --success:        #42B72A;
            --attention:      #FF9500;
            --warning:        #F7B928;
            --critical:       #E53935;
            --critical-strong:#C62828;  /* Error border + inline error label */

            /* Radius */
            --r-xs:     2px;
            --r-sm:     4px;
            --r-md:     6px;
            --r-lg:     8px;
            --r-xl:     16px;
            --r-xxl:    24px;
            --r-xxxl:   32px;
            --r-feature:40px;
            --r-full:   100px;

            /* Spacing — 4px base, 8px primary step */
            --space-xxs:        4px;
            --space-xs:         8px;
            --space-sm:         10px;
            --space-md:         12px;
            --space-base:       16px;
            --space-lg:         20px;
            --space-xl:         24px;
            --space-xxl:        32px;
            --space-xxxl:       40px;
            --space-section-sm: 48px;
            --space-section:    64px;
            --space-section-lg: 80px;
            --space-hero:       120px;
        }

        /* ── Base ── */
        *, *::before, *::after { box-sizing: border-box; }
        html, body {
            background: var(--canvas);
            color: var(--ink-deep);
            font-family: 'Montserrat', Helvetica, Arial, sans-serif;
            font-weight: 400;
            font-size: 16px;
            line-height: 1.5;
            letter-spacing: -0.16px;
            min-height: 100vh;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Montserrat', Helvetica, Arial, sans-serif;
            font-weight: 500;
            color: var(--ink-deep);
            letter-spacing: 0;
            font-feature-settings: "ss01" 1, "ss02" 1;
        }
        p, li { color: var(--charcoal); font-weight: 400; }
        a { color: var(--primary); text-decoration: none; }
        a:hover { color: var(--primary-deep); }

        /* ── Navbar ── */
        .site-nav {
            background: var(--canvas);
            border-bottom: 1px solid var(--hairline-soft);
            position: sticky;
            top: 0;
            z-index: 1000;
            height: 64px;
        }
        .site-nav .navbar-brand {
            font-weight: 700;
            font-size: 15px;
            color: var(--ink-deep) !important;
            letter-spacing: -0.14px;
        }
        .nav-pill-tab {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: -0.14px;
            border-radius: var(--r-full);
            border: 1px solid var(--hairline);
            background: var(--canvas);
            color: var(--ink);
            cursor: pointer;
            text-decoration: none;
            transition: background .15s, color .15s, border-color .15s;
            white-space: nowrap;
        }
        .nav-pill-tab:hover { background: var(--surface-soft); color: var(--ink-deep); }
        .nav-pill-tab.active { background: var(--ink-deep); color: var(--canvas); border-color: var(--ink-deep); }
        .navbar-toggler { border: 1px solid var(--hairline); padding: 4px 10px; border-radius: var(--r-lg); }
        .navbar-toggler:focus { box-shadow: none; }
        .dropdown-menu {
            border-radius: var(--r-xl);
            border: 1px solid var(--hairline-soft);
            box-shadow: rgba(20,22,26,.15) 0 4px 16px;
            padding: 8px;
            min-width: 200px;
        }
        .dropdown-item {
            border-radius: var(--r-lg);
            color: var(--ink);
            font-size: 14px;
            font-weight: 400;
            padding: 8px 12px;
        }
        .dropdown-item:hover { background: var(--surface-soft); color: var(--ink-deep); }
        .dropdown-divider { border-color: var(--hairline-soft); margin: 4px 0; }
        .dropdown-item-text { color: var(--slate); font-size: 12px; padding: 4px 12px; }

        /* ── Buttons ── */
        .btn {
            border-radius: var(--r-full);
            font-size: 14px;
            font-weight: 700;
            letter-spacing: -0.14px;
            padding: 14px 30px;
            line-height: 1.43;
            transition: background .15s, color .15s, border-color .15s, box-shadow .15s;
            border: none;
        }
        .btn-primary {
            background: var(--ink-button);
            color: #fff;
        }
        .btn-primary:hover { background: #3c4043; color: #fff; }
        .btn-buy {
            background: var(--primary);
            color: #fff;
            border: none;
        }
        .btn-buy:hover { background: var(--primary-deep); color: #fff; }
        .btn-outline-primary {
            background: transparent;
            color: var(--ink-deep);
            border: 2px solid var(--ink-deep);
        }
        .btn-outline-primary:hover { background: var(--ink-deep); color: #fff; }
        .btn-ghost {
            background: transparent;
            color: var(--ink-deep);
            border: 2px solid rgba(10,19,23,.12);
        }
        .btn-ghost:hover { background: var(--surface-soft); color: var(--ink-deep); }
        .btn-outline-secondary {
            background: transparent;
            color: var(--charcoal);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
        }
        .btn-outline-secondary:hover { background: var(--surface-soft); color: var(--ink-deep); }
        .btn-sm { padding: 8px 18px; font-size: 13px; }
        .btn-lg { padding: 16px 36px; font-size: 15px; }

        /* ── Cards ── */
        .card {
            background: var(--canvas);
            border: 1px solid var(--hairline-soft);
            border-radius: var(--r-xl);
            color: var(--ink-deep);
            box-shadow: none;
        }
        .card-feature { border-radius: var(--r-xxxl); }
        .card-header {
            background: var(--surface-soft);
            border-bottom: 1px solid var(--hairline-soft);
            border-radius: var(--r-xl) var(--r-xl) 0 0 !important;
            padding: 20px 24px;
        }
        .card-body { padding: 24px; }

        /* ── Forms ── */
        .form-control, .form-select {
            background: var(--canvas);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            color: var(--ink-deep);
            font-family: inherit;
            font-weight: 400;
            height: 44px;
            padding: 10px 14px;
            font-size: 15px;
            letter-spacing: -0.14px;
            transition: border-color .15s, box-shadow .15s;
        }
        .form-control::placeholder { color: var(--steel); }
        .form-control:focus, .form-select:focus {
            border: 2px solid var(--fb-blue);
            box-shadow: none;
            outline: none;
            background: var(--canvas);
            color: var(--ink-deep);
        }
        .form-control.is-invalid, .form-select.is-invalid { border: 1px solid var(--critical-strong); }
        .form-label {
            font-size: 14px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 6px;
            letter-spacing: -0.14px;
        }
        .form-check-input {
            border-radius: var(--r-sm);
            border-color: var(--hairline);
        }
        .form-check-input:checked { background-color: var(--fb-blue); border-color: var(--fb-blue); }
        .invalid-feedback { color: var(--critical-strong); font-size: 13px; font-weight: 400; margin-top: 4px; }
        .input-group .form-control { border-radius: var(--r-lg) 0 0 var(--r-lg); }
        .input-group .btn { border-radius: 0 var(--r-lg) var(--r-lg) 0; height: 44px; padding: 0 14px; }

        /* ── Table ── */
        .table { color: var(--ink); border-color: var(--hairline-soft); }
        .table th {
            font-size: 12px; font-weight: 700; letter-spacing: -0.12px;
            color: var(--slate); background: var(--surface-soft);
            border-color: var(--hairline-soft); padding: 12px 16px;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .table td { border-color: var(--hairline-soft); padding: 14px 16px; vertical-align: middle; }
        .table-hover tbody tr:hover { background: var(--surface-soft); }
        .table-container { border: 1px solid var(--hairline-soft); border-radius: var(--r-xl); overflow-x: auto; overflow-y: hidden; }

        /* ── Badges ── */
        .badge {
            border-radius: var(--r-full);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0;
            padding: 4px 10px;
        }
        .badge.bg-success  { background: var(--success) !important; color: #fff; }
        .badge.bg-warning  { background: var(--warning) !important; color: var(--ink-deep); }
        .badge.bg-danger   { background: var(--critical) !important; color: #fff; }
        .badge.bg-primary  { background: var(--primary-soft) !important; color: var(--primary); border: 1px solid rgba(5,98,226,.2); }
        .badge.bg-secondary{ background: var(--surface-soft) !important; color: var(--slate); border: 1px solid var(--hairline-soft); }

        /* ── Alerts ── */
        .alert {
            border-radius: var(--r-xl);
            font-size: 14px;
            font-weight: 400;
            padding: 14px 18px;
            border: none;
        }
        .alert-danger  { background: rgba(229,57,53,.08);  color: #c62828; }
        .alert-success { background: rgba(66,183,42,.08);  color: #2e7d32; }
        .alert-info    { background: var(--primary-soft);   color: var(--primary-deep); }
        .alert-warning { background: rgba(247,185,40,.12); color: #856404; }
        .alert strong  { font-weight: 700; color: inherit; }
        .alert a, .alert .alert-link { color: inherit; font-weight: 700; }

        /* ── Modal ── */
        .modal-content { border-radius: var(--r-xxxl); border: 1px solid var(--hairline-soft); box-shadow: rgba(20,22,26,.2) 0 8px 32px; }
        .modal-header { border-bottom: 1px solid var(--hairline-soft); padding: 20px 24px; border-radius: var(--r-xxxl) var(--r-xxxl) 0 0; }
        .modal-header .modal-title { font-weight: 700; font-size: 16px; color: var(--ink-deep); }
        .modal-body { padding: 24px; }

        /* ── Pagination ── */
        .page-link { border-radius: var(--r-full) !important; border-color: var(--hairline-soft); color: var(--ink); margin: 0 2px; }
        .page-item.active .page-link { background: var(--ink-deep); border-color: var(--ink-deep); color: #fff; }
        .page-item.disabled .page-link { color: var(--stone); background: var(--surface-soft); }

        /* ── Spinner ── */
        .spinner-border { border-color: var(--hairline); border-right-color: var(--primary); }

        /* ── Utility ── */
        .text-primary { color: var(--primary) !important; }
        .text-muted   { color: var(--slate) !important; }
        .bg-primary   { background: var(--primary) !important; }
        hr { border-color: var(--hairline-soft); opacity: 1; }

        /* ── Main ── */
        main { min-height: calc(100vh - 64px - 100px); }

        /* ── Section title style ── */
        .section-label {
            font-size: 12px; font-weight: 700; letter-spacing: 0.5px;
            text-transform: uppercase; color: var(--slate); margin-bottom: 8px;
        }
    </style>
    @stack('head')
</head>
<body>

    <!-- Navbar -->
    <nav class="site-nav navbar navbar-expand-lg">
        <div class="container-lg h-100 align-items-center">
            <a class="navbar-brand d-flex align-items-center gap-2 me-4" href="{{ route('home') }}">
                <i class="bi bi-heart-pulse" style="color:var(--primary);"></i>
                MH Prediction
            </a>

            <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="bi bi-list" style="font-size:20px; color:var(--ink);"></i>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Center nav pills -->
                <div class="d-flex flex-wrap align-items-center gap-2 me-auto">
                    @auth
                        @if (auth()->user()->isAdmin())
                            <a class="nav-pill-tab {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
                            <a class="nav-pill-tab {{ request()->routeIs('admin.models.*') ? 'active' : '' }}" href="{{ route('admin.models.index') }}">Kontrol Model</a>
                            <a class="nav-pill-tab {{ request()->routeIs('admin.predictions.*') ? 'active' : '' }}" href="{{ route('admin.predictions.index') }}">Kelola Riwayat</a>
                            <a class="nav-pill-tab {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">Kelola Pengguna</a>
                        @else
                            <a class="nav-pill-tab {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Beranda</a>
                            <a class="nav-pill-tab {{ request()->routeIs('models') ? 'active' : '' }}" href="{{ route('models') }}">Model</a>
                            <a class="nav-pill-tab {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}">Tentang</a>
                            <a class="nav-pill-tab {{ request()->routeIs('predict.*') ? 'active' : '' }}" href="{{ route('predict.form') }}">Prediksi</a>
                            <a class="nav-pill-tab {{ request()->routeIs('history') ? 'active' : '' }}" href="{{ route('history') }}">Riwayat</a>
                        @endif
                    @else
                        <a class="nav-pill-tab {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Beranda</a>
                        <a class="nav-pill-tab {{ request()->routeIs('models') ? 'active' : '' }}" href="{{ route('models') }}">Model</a>
                        <a class="nav-pill-tab {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}">Tentang</a>
                    @endauth
                </div>

                <!-- Right auth actions -->
                <div class="d-flex align-items-center gap-2 mt-2 mt-lg-0">
                    @auth
                        <div class="dropdown">
                            <button class="nav-pill-tab" data-bs-toggle="dropdown" style="border:none; cursor:pointer;">
                                <i class="bi bi-person-circle"></i>
                                {{ Auth::user()->name }}
                                <i class="bi bi-chevron-down" style="font-size:11px;"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><span class="dropdown-item-text">{{ Auth::user()->email }}</span></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item" style="color:var(--critical);">
                                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @else
                        <a class="nav-pill-tab {{ request()->routeIs('login') ? 'active' : '' }}" href="{{ route('login') }}">Masuk</a>
                        <a class="btn btn-primary btn-sm" href="{{ route('register') }}">Daftar</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main>
        @if (session('success'))
            <div class="container-lg pt-4">
                <div class="alert alert-success">{{ session('success') }}</div>
            </div>
        @endif
        @if (session('error'))
            <div class="container-lg pt-4">
                <div class="alert alert-danger">{{ session('error') }}</div>
            </div>
        @endif
        @yield('content')
    </main>

    <footer style="border-top:1px solid var(--hairline-soft); padding:40px 0; background:var(--canvas); margin-top:80px;">
        <div class="container-lg">
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-md-0">
                    <p style="font-size:15px; font-weight:700; color:var(--ink-deep); margin-bottom:4px;">Mental Health Risk Prediction</p>
                    <p style="font-size:13px; color:var(--steel); margin:0;">&copy; {{ date('Y') }} — Untuk tujuan edukasi</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p style="font-size:13px; color:var(--steel); margin:0;">
                        Ahmad Nidzomunnashil &middot; Vikry Achmad Sonjaya &middot; Mardini Dwi Putri
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
