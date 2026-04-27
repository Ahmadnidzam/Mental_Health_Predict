<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mental Health Risk Prediction')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #8b5cf6;
            --danger: #ef4444;
            --warning: #f59e0b;
            --success: #10b981;
        }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #f8fafc;
        }
        .navbar-custom {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }
        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link { color: #fff !important; }
        .navbar-custom .nav-link:hover { color: #e0e7ff !important; }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: 0;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
        }
        .card { border: 0; }
        .section-divider { border-bottom: 2px solid #e2e8f0; padding-bottom: .5rem; }
        .badge-risk-0 { background-color: var(--success); }
        .badge-risk-1 { background-color: var(--warning); color: #000; }
        .badge-risk-2 { background-color: var(--danger); }
        footer { background-color: #1e293b; color: #cbd5e1; }
    </style>
    @stack('head')
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top shadow-sm">
        <div class="container-lg">
            <a class="navbar-brand fw-bold" href="{{ route('home') }}">
                <i class="bi bi-heart-pulse"></i> Mental Health Prediction
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('home') ? 'fw-bold' : '' }}" href="{{ route('home') }}">Home</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('predict.*') ? 'fw-bold' : '' }}" href="{{ route('predict.form') }}">Prediksi</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('history') ? 'fw-bold' : '' }}" href="{{ route('history') }}">Riwayat</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('models') ? 'fw-bold' : '' }}" href="{{ route('models') }}">Model Info</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('about') ? 'fw-bold' : '' }}" href="{{ route('about') }}">Tentang</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="py-5">
        @if (session('success'))
            <div class="container-lg"><div class="alert alert-success">{{ session('success') }}</div></div>
        @endif
        @if (session('error'))
            <div class="container-lg"><div class="alert alert-danger">{{ session('error') }}</div></div>
        @endif
        @yield('content')
    </main>

    <footer class="py-4 mt-5">
        <div class="container text-center">
            <p class="mb-1">&copy; {{ date('Y') }} Mental Health Prediction System</p>
            <small class="text-muted">Dikembangkan oleh Ahmad Nidzomunnashil, Vikry Achmad Sonjaya, &amp; Mardini Dwi Putri &middot; Untuk tujuan edukasi</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
