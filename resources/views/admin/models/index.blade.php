@extends('layouts.app')

@section('title', 'Admin · Kontrol Model')

@section('content')
<div class="container-lg" style="padding-top:48px; padding-bottom:80px;">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <p class="section-label mb-1">Admin</p>
            <h1 style="font-size:32px; font-weight:500; color:var(--ink-deep); margin:0;">Kontrol Model</h1>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Dashboard
        </a>
    </div>

    @php
        // Map versionId aktif per-algoritma untuk indikator.
        $activeVersionIdByAlgo = [];
        foreach ($algoKeys as $ak) {
            $activeVersionIdByAlgo[$ak] = $assignments[$ak]?->id;
        }
    @endphp

    {{-- Caveat box --}}
    <div class="alert alert-warning d-flex gap-3 mb-5" style="align-items:flex-start;">
        <i class="bi bi-exclamation-triangle-fill" style="font-size:20px; flex-shrink:0; margin-top:2px;"></i>
        <div>
            <strong>Perhatian soal akurasi retrain.</strong>
            Label pada proses retrain berasal dari prediksi model itu sendiri (<code>final_prediction</code>),
            bukan ground truth terverifikasi. Angka akurasi mencerminkan <strong>konsistensi internal</strong>,
            bukan kebenaran sesungguhnya. Tiap algoritma bisa diambil dari versi berbeda — pilih kombinasi terbaik.
        </div>
    </div>

    {{-- Ringkasan assignment aktif per-algoritma --}}
    <p class="section-label mb-2">Assignment Aktif (per algoritma)</p>
    <div class="row g-3 mb-5">
        @foreach ($algoKeys as $ak)
            @php
                $mv  = $assignments[$ak];
                $mk  = $metricsKey[$ak];
                $acc = $mv?->accuracyFor($mk);
            @endphp
            <div class="col-6 col-md-4 col-lg-2">
                <div style="border:1px solid var(--hairline-soft); border-radius:var(--r-lg); padding:14px; text-align:center;">
                    <div style="font-size:12px; font-weight:700; color:var(--slate); margin-bottom:6px;">{{ $mk }}</div>
                    <div style="font-size:18px; font-weight:700; color:var(--ink-deep);">
                        {{ $acc !== null ? number_format($acc * 100, 2) . '%' : '—' }}
                    </div>
                    <div style="font-size:11px; color:var(--primary); margin-top:4px;">
                        {{ $mv ? ($mv->label ?? 'Versi #' . $mv->id) : 'Belum ada' }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Daftar versi (aktif + pending): pilih algoritma per versi --}}
    <p class="section-label mb-2">Versi Tersedia (aktif + pending)</p>
    @forelse ($versions as $v)
        @php
            $isPending  = $v->status === \App\Models\ModelVersion::STATUS_PENDING;
            $referenced = in_array($v->id, $activeVersionIdByAlgo, true);
            $borderCol  = $isPending ? 'var(--warning)' : 'var(--success)';
        @endphp
        <div style="background:var(--canvas); border:1px solid {{ $borderCol }}; border-radius:var(--r-xl); padding:24px; margin-bottom:24px;">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi {{ $isPending ? 'bi-hourglass-split' : 'bi-cpu' }}" style="font-size:20px; color:{{ $borderCol }};"></i>
                        <h3 style="font-size:20px; font-weight:700; color:var(--ink-deep); margin:0;">{{ $v->label ?? 'Versi #' . $v->id }}</h3>
                        <span class="badge {{ $isPending ? 'bg-warning' : 'bg-success' }}">{{ $isPending ? 'Pending' : 'Aktif' }}</span>
                    </div>
                    <p style="font-size:13px; color:var(--slate); margin:0;">
                        Dataset: {{ number_format($v->dataset_size ?? 0) }} baris ·
                        Dibuat: {{ $v->created_at ? $v->created_at->format('d/m/Y H:i') : '—' }}
                    </p>
                </div>
                {{-- Reject: hanya bila versi tidak dirujuk algoritma manapun --}}
                @if ($referenced)
                    <button type="button" class="btn btn-outline-secondary btn-sm" disabled
                            title="Versi masih dipakai algoritma. Alihkan dulu sebelum menolak.">
                        <i class="bi bi-lock me-1"></i>Dipakai
                    </button>
                @else
                    <form method="POST" action="{{ route('admin.models.reject', $v) }}"
                          onsubmit="return confirm('Reject menghapus artifact model permanen. Lanjutkan?');">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-lg me-1"></i>Reject
                        </button>
                    </form>
                @endif
            </div>

            {{-- Form pilih algoritma dari versi ini --}}
            <form method="POST" action="{{ route('admin.models.apply', $v) }}"
                  onsubmit="return confirm('Terapkan algoritma terpilih dari {{ addslashes($v->label ?? 'versi #' . $v->id) }}?');">
                @csrf
                <div class="row g-3">
                    @foreach ($algoKeys as $ak)
                        @php
                            $mk        = $metricsKey[$ak];
                            $acc       = $v->accuracyFor($mk);
                            $activeMv  = $assignments[$ak];
                            $activeAcc = $activeMv?->accuracyFor($mk);
                            $isHere    = $activeMv?->id === $v->id;

                            $delta = null; $dir = 'equal';
                            if ($acc !== null && $activeAcc !== null) {
                                $delta = $acc - $activeAcc;
                                $dir = abs($delta) < 1e-9 ? 'equal' : ($delta > 0 ? 'up' : 'down');
                            }
                            $dirColor = $dir === 'up' ? 'var(--success)' : ($dir === 'down' ? 'var(--critical)' : 'var(--slate)');
                            $dirIcon  = $dir === 'up' ? 'bi-caret-up-fill' : ($dir === 'down' ? 'bi-caret-down-fill' : 'bi-dash');
                            $cid = "algo-{$v->id}-{$ak}";
                            $present = $presence[$v->id][$ak] ?? false;
                        @endphp
                        <div class="col-6 col-md-4 col-lg-2">
                            <label for="{{ $cid }}" style="display:block; cursor:pointer; border:1px solid {{ $isHere ? 'var(--success)' : 'var(--hairline-soft)' }}; border-radius:var(--r-lg); padding:14px; text-align:center; {{ $isHere ? 'background:rgba(66,183,42,.06);' : '' }}">
                                <div style="font-size:12px; font-weight:700; color:var(--slate); margin-bottom:6px;">
                                    {{ $mk }}
                                    @if ($isHere)<i class="bi bi-check-circle-fill" style="color:var(--success); font-size:11px;" title="Sedang aktif dari versi ini"></i>@endif
                                </div>
                                <div style="font-size:18px; font-weight:700; color:var(--ink-deep);">
                                    {{ $acc !== null ? number_format($acc * 100, 2) . '%' : '—' }}
                                </div>
                                <div style="font-size:12px; font-weight:700; color:{{ $dirColor }}; margin-top:4px; margin-bottom:8px;">
                                    <i class="bi {{ $dirIcon }}"></i>
                                    @if ($delta !== null && $dir !== 'equal')
                                        {{ ($delta > 0 ? '+' : '') . number_format($delta * 100, 2) }}%
                                    @elseif ($activeAcc === null)
                                        <span style="font-weight:400;">vs —</span>
                                    @else
                                        0.00%
                                    @endif
                                </div>
                                <div class="form-check d-flex justify-content-center" style="min-height:auto;">
                                    @if (! $present)
                                        <span class="badge bg-secondary" title="Artifact .pkl sudah dihapus dari disk">Terhapus</span>
                                    @elseif ($isHere)
                                        <span class="badge bg-success" title="Sedang aktif dari versi ini — terkunci">
                                            <i class="bi bi-lock-fill"></i> Aktif
                                        </span>
                                    @else
                                        <input class="form-check-input" type="checkbox" name="algos[]" value="{{ $ak }}"
                                               id="{{ $cid }}" {{ $acc === null ? 'disabled' : '' }}>
                                    @endif
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>

                <div class="mt-3 d-flex justify-content-end">
                    <button type="submit" class="btn btn-buy btn-sm">
                        <i class="bi bi-check-lg me-1"></i>Terapkan algoritma terpilih
                    </button>
                </div>
            </form>

            {{-- Form hapus artifact dari disk: hanya algo yang masih ada (.pkl) dan
                 tidak sedang aktif dari versi ini. --}}
            @php
                $deletable = [];
                foreach ($algoKeys as $ak2) {
                    $present2 = $presence[$v->id][$ak2] ?? false;
                    $isHere2  = ($assignments[$ak2]?->id === $v->id);
                    if ($present2 && ! $isHere2) {
                        $deletable[] = $ak2;
                    }
                }
            @endphp
            @if (! empty($deletable))
                <form method="POST" action="{{ route('admin.models.algos.destroy', $v) }}"
                      onsubmit="return confirm('Hapus artifact terpilih dari disk secara permanen? Bila ini artifact terakhir, seluruh versi akan dibuang.');"
                      class="mt-3 pt-3" style="border-top:1px dashed var(--hairline-soft);">
                    @csrf
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center flex-wrap gap-3">
                            <span style="font-size:12px; font-weight:700; color:var(--slate);">Hapus dari disk:</span>
                            @foreach ($deletable as $ak2)
                                <div class="form-check" style="min-height:auto;">
                                    <input class="form-check-input" type="checkbox" name="algos[]"
                                           value="{{ $ak2 }}" id="del-{{ $v->id }}-{{ $ak2 }}">
                                    <label class="form-check-label" style="font-size:12px; font-weight:700; color:var(--slate);"
                                           for="del-{{ $v->id }}-{{ $ak2 }}">{{ $metricsKey[$ak2] }}</label>
                                </div>
                            @endforeach
                        </div>
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash me-1"></i>Hapus dari disk
                        </button>
                    </div>
                </form>
            @endif
        </div>
    @empty
        <div style="background:var(--surface-soft); border-radius:var(--r-xl); padding:48px 24px; text-align:center; color:var(--slate);">
            <i class="bi bi-inbox" style="font-size:36px; display:block; margin-bottom:12px;"></i>
            Belum ada versi. Jalankan <code>php artisan models:seed-base</code> untuk versi dasar.
        </div>
    @endforelse

</div>
@endsection
