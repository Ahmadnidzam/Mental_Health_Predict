@extends('layouts.app')

@section('title', 'Admin · Prediksi')

@section('content')
<div class="container-lg" style="padding-top:48px; padding-bottom:80px;">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <p class="section-label mb-1">Admin</p>
            <h1 style="font-size:32px; font-weight:500; color:var(--ink-deep); margin:0;">Semua Prediksi</h1>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Dashboard
        </a>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('admin.predictions.index') }}" class="mb-4" style="max-width:360px;">
        <label class="form-label" for="user_id">Filter Pengguna</label>
        <select name="user_id" id="user_id" class="form-select" onchange="this.form.submit()">
            <option value="">Semua pengguna</option>
            @foreach ($users as $u)
                <option value="{{ $u->id }}" {{ (string) $selectedUserId === (string) $u->id ? 'selected' : '' }}>
                    {{ $u->name }}
                </option>
            @endforeach
        </select>
    </form>

    {{-- Table --}}
    @php
        $riskBg = ['var(--success)', 'var(--warning)', 'var(--critical)'];
        $riskFg = ['#fff', 'var(--ink-deep)', '#fff'];
    @endphp
    <div class="table-container">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Pengguna</th>
                    <th>Model</th>
                    <th class="text-center">Hasil Risiko</th>
                    <th class="text-center">Confidence</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($predictions as $pred)
                    <tr>
                        <td style="font-size:13px; color:var(--charcoal);">{{ $pred->created_at->format('d/m/Y H:i') }}</td>
                        <td style="font-weight:500; color:var(--ink-deep);">{{ $pred->user?->name ?? '—' }}</td>
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
                        <td colspan="5" style="text-align:center; padding:64px 0; color:var(--slate);">
                            Tidak ada prediksi.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($predictions->hasPages())
        <div class="mt-4 d-flex justify-content-center">{{ $predictions->withQueryString()->links() }}</div>
    @endif

</div>
@endsection
