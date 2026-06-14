@extends('layouts.app')

@section('title', 'Tentang Kami')

@section('content')
<div class="container-lg" style="padding-top:48px; padding-bottom:80px;">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            {{-- Header --}}
            <div class="mb-5">
                <p class="section-label mb-1">Informasi</p>
                <h1 style="font-size:36px; font-weight:500; color:var(--ink-deep); margin:0;">Tentang Sistem Ini</h1>
            </div>

            {{-- Visi & Misi --}}
            <div style="background:var(--canvas); border:1px solid var(--hairline-soft); border-radius:var(--r-xl); padding:32px; margin-bottom:20px;">
                <h2 style="font-size:24px; font-weight:500; color:var(--ink-deep); margin:0 0 12px;">Visi &amp; Misi</h2>
                <p style="font-size:15px; color:var(--charcoal); line-height:1.6; margin:0;">
                    Sistem ini dikembangkan untuk membantu deteksi dini risiko kesehatan mental
                    menggunakan teknologi Machine Learning yang dapat diinterpretasikan.
                    Tujuan akhirnya adalah meningkatkan kesadaran dan mendorong tindakan preventif
                    sebelum masalah berkembang lebih jauh.
                </p>
            </div>

            {{-- Tech Stack --}}
            <div style="background:var(--canvas); border:1px solid var(--hairline-soft); border-radius:var(--r-xl); padding:32px; margin-bottom:20px;">
                <h2 style="font-size:24px; font-weight:500; color:var(--ink-deep); margin:0 0 20px;">Tech Stack</h2>
                <div class="row g-4">
                    @foreach ([
                        ['Backend',          ['Laravel 12 (PHP)', 'MySQL untuk penyimpanan data', 'Eloquent ORM']],
                        ['Machine Learning', ['Python 3 + scikit-learn', 'KNN, KNN+HPO, SVM, SVM+HPO, Decision Tree, DT+HPO', 'StandardScaler & encoding fitur']],
                        ['Frontend',         ['Bootstrap 5.3', 'Bootstrap Icons', 'Vanilla JS (AJAX fetch)']],
                        ['Integrasi',        ['proc_open (PHP ↔ Python)', 'JSON sebagai format pertukaran data']],
                    ] as [$group, $items])
                        <div class="col-md-6">
                            <p style="font-size:14px; font-weight:700; color:var(--ink-deep); margin-bottom:8px;">{{ $group }}</p>
                            <ul style="margin:0; padding-left:18px;">
                                @foreach ($items as $item)
                                    <li style="font-size:14px; color:var(--charcoal); line-height:1.7;">{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Tim Pengembang --}}
            <div style="background:var(--canvas); border:1px solid var(--hairline-soft); border-radius:var(--r-xl); padding:32px; margin-bottom:20px;">
                <h2 style="font-size:24px; font-weight:500; color:var(--ink-deep); margin:0 0 6px;">Tim Pengembang</h2>
                <p style="font-size:14px; color:var(--slate); margin-bottom:20px;">Project akademik ini dikembangkan oleh tim berikut:</p>
                <div class="row g-3">
                    @foreach ([
                        ['Ahmad Nidzomunnashil', '607012400122'],
                        ['Vikry Achmad Sonjaya', '607012400001'],
                        ['Mardini Dwi Putri',    '607012430015'],
                    ] as [$name, $nim])
                        <div class="col-md-4">
                            <div style="background:var(--surface-soft); border-radius:var(--r-xl); padding:24px; height:100%; text-align:center;">
                                <i class="bi bi-person-circle" style="font-size:36px; color:var(--primary);"></i>
                                <p style="font-size:15px; font-weight:700; color:var(--ink-deep); margin:12px 0 4px;">{{ $name }}</p>
                                <p style="font-size:12px; color:var(--slate); margin:0;">NIM: {{ $nim }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Disclaimer --}}
            <div class="alert alert-warning">
                <strong style="display:block; margin-bottom:6px;"><i class="bi bi-exclamation-triangle me-1"></i> Disclaimer Penting</strong>
                Prediksi yang dihasilkan sistem ini bersifat <strong>edukatif dan referensi</strong> saja, dan
                <strong>BUKAN diagnosis medis</strong>. Jangan gunakan sebagai pengganti konsultasi
                profesional. Jika Anda atau orang terdekat menunjukkan tanda-tanda gangguan kesehatan mental
                yang serius, segera hubungi tenaga profesional kesehatan mental atau layanan kegawatdaruratan
                psikologis terdekat.
            </div>

        </div>
    </div>
</div>
@endsection
