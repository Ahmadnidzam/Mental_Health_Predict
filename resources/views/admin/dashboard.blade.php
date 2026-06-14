@extends('layouts.app')

@section('title', 'Admin · Dashboard')

@section('content')
<div class="container-lg" style="padding-top:48px; padding-bottom:80px;">

    {{-- Header --}}
    <div class="mb-5">
        <p class="section-label mb-1">Admin</p>
        <h1 style="font-size:32px; font-weight:500; color:var(--ink-deep); margin:0;">Dashboard</h1>
    </div>

    {{-- Stat cards --}}
    @php
        $activeAcc = $activeVersion?->bestAccuracy();
        $cards = [
            ['bi-people',         number_format($totalUsers ?? 0),       'Total Pengguna',   'Seluruh akun terdaftar',                                            'var(--primary)',  route('admin.users.index')],
            ['bi-clock-history',  number_format($totalPredictions ?? 0), 'Total Prediksi',   'Dari seluruh pengguna',                                             'var(--primary)',  route('admin.predictions.index')],
            ['bi-cpu',            $activeVersion ? $activeVersion->label : '—', 'Model Aktif', $activeAcc !== null ? 'Akurasi terbaik ' . number_format($activeAcc * 100, 2) . '%' : 'Belum ada model aktif', 'var(--success)', route('admin.models.index')],
            ['bi-hourglass-split',number_format($pendingCount ?? 0),     'Menunggu Review',  'Versi pending untuk disetujui',                                     ($pendingCount ?? 0) > 0 ? 'var(--warning)' : 'var(--slate)', route('admin.models.index')],
            ['bi-arrow-repeat',   $lastRetrain ? $lastRetrain->format('d/m/Y H:i') : '—', 'Retrain Terakhir', $lastRetrain ? $lastRetrain->diffForHumans() : 'Belum pernah retrain', 'var(--primary)', route('admin.models.index')],
            ['bi-database',       number_format($datasetSize ?? 0),      'Ukuran Dataset',   'Baris pada model aktif',                                            'var(--primary)',  route('admin.data.export')],
        ];
    @endphp
    <div class="row g-4 mb-5">
        @foreach ($cards as [$icon, $val, $label, $sub, $color, $link])
        <div class="col-md-6 col-lg-4">
            <a href="{{ $link }}" style="text-decoration:none; display:block; height:100%;">
                <div style="background:var(--canvas); border:1px solid var(--hairline-soft); border-radius:var(--r-xl); padding:24px; height:100%; transition:border-color .15s;"
                     onmouseover="this.style.borderColor='var(--hairline)'" onmouseout="this.style.borderColor='var(--hairline-soft)'">
                    <i class="bi {{ $icon }}" style="font-size:26px; color:{{ $color }}; display:block; margin-bottom:14px;"></i>
                    <div style="font-size:26px; font-weight:700; color:var(--ink-deep); line-height:1.1;">{{ $val }}</div>
                    <div style="font-size:14px; font-weight:700; color:var(--charcoal); margin-top:8px;">{{ $label }}</div>
                    <div style="font-size:12px; color:var(--slate); margin-top:3px;">{{ $sub }}</div>
                </div>
            </a>
        </div>
        @endforeach
    </div>

    {{-- Quick links --}}
    <p class="section-label mb-3">Aksi Cepat</p>
    <div class="d-flex flex-wrap gap-3">
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-people me-2"></i>Kelola Pengguna
        </a>
        <a href="{{ route('admin.predictions.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-clock-history me-2"></i>Semua Prediksi
        </a>
        <a href="{{ route('admin.models.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-cpu me-2"></i>Kontrol Model
        </a>
        <a href="{{ route('admin.data.export') }}" class="btn btn-primary">
            <i class="bi bi-download me-2"></i>Export CSV
        </a>
    </div>

</div>
@endsection
