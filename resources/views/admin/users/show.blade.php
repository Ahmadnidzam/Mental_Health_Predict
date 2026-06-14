@extends('layouts.app')

@section('title', 'Admin · ' . $user->name)

@section('content')
<div class="container-lg" style="padding-top:48px; padding-bottom:80px;">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <p class="section-label mb-1">Admin · Pengguna</p>
            <h1 style="font-size:32px; font-weight:500; color:var(--ink-deep); margin:0;">{{ $user->name }}</h1>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Daftar Pengguna
        </a>
    </div>

    {{-- User info --}}
    <div style="background:var(--canvas); border:1px solid var(--hairline-soft); border-radius:var(--r-xl); padding:24px; margin-bottom:32px;">
        <div class="row g-4">
            <div class="col-md-4">
                <p style="font-size:12px; color:var(--slate); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Email</p>
                <p style="font-size:15px; font-weight:500; color:var(--ink-deep); margin:0;">{{ $user->email }}</p>
            </div>
            <div class="col-md-4">
                <p style="font-size:12px; color:var(--slate); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Peran</p>
                @if ($user->isAdmin())
                    <span class="badge bg-primary">Admin</span>
                @else
                    <span class="badge bg-secondary">Pengguna</span>
                @endif
            </div>
            <div class="col-md-4">
                <p style="font-size:12px; color:var(--slate); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Terdaftar</p>
                <p style="font-size:15px; font-weight:500; color:var(--ink-deep); margin:0;">{{ $user->created_at?->format('d/m/Y H:i') ?? '—' }}</p>
            </div>
        </div>
    </div>

    {{-- Predictions table --}}
    <p class="section-label mb-2">Riwayat Prediksi</p>
    @php
        $riskBg = ['var(--success)', 'var(--warning)', 'var(--critical)'];
        $riskFg = ['#fff', 'var(--ink-deep)', '#fff'];
    @endphp
    <div class="table-container">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Model</th>
                    <th class="text-center">Hasil Risiko</th>
                    <th class="text-center">Confidence</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($predictions as $pred)
                    <tr>
                        <td style="font-size:13px; color:var(--charcoal);">{{ $pred->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <span style="font-size:12px; font-weight:700; color:var(--primary); background:var(--primary-soft); padding:4px 10px; border-radius:var(--r-full);">
                                {{ $pred->model_label }}
                            </span>
                        </td>
                        <td class="text-center">
                            @php $rv = $pred->final_prediction; @endphp
                            <span style="padding:4px 12px; font-size:12px; font-weight:700; border-radius:var(--r-full); background:{{ $riskBg[$rv] ?? '#ccc' }}; color:{{ $riskFg[$rv] ?? '#000' }};">
                                {{ $pred->final_label }}
                            </span>
                        </td>
                        <td class="text-center" style="font-size:14px; font-weight:700; color:var(--ink-deep);">
                            {{ $pred->confidence !== null ? number_format($pred->confidence * 100, 1) . '%' : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center; padding:64px 0; color:var(--slate);">
                            Pengguna ini belum membuat prediksi.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($predictions->hasPages())
        <div class="mt-4 d-flex justify-content-center">{{ $predictions->links() }}</div>
    @endif

</div>
@endsection
