@extends('layouts.app')

@section('title', 'Beranda - Mental Health Risk Prediction')

@section('content')

{{-- Hero --}}
<section style="padding: 80px 0; background: var(--canvas);">
    <div class="container-lg">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <p class="section-label mb-3">Machine Learning · Kesehatan Mental</p>
                <h1 style="font-size: clamp(36px,5vw,56px); font-weight:500; line-height:1.17; color:var(--ink-deep); margin-bottom:20px;">
                    Deteksi Dini Risiko<br>Kesehatan Mental
                </h1>
                <p style="font-size:18px; font-weight:300; color:var(--charcoal); line-height:1.6; max-width:520px; margin-bottom:36px;">
                    Sistem prediksi berbasis Machine Learning menggunakan 6 model klasik
                    untuk mengenali kemungkinan risiko kesehatan mental dari 24 faktor psikososial.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('predict.form') }}" class="btn btn-primary btn-lg">
                        <i class="bi bi-clipboard2-pulse me-2"></i>Mulai Prediksi
                    </a>
                    <a href="{{ route('models') }}" class="btn btn-outline-primary btn-lg">
                        Performa Model
                    </a>
                </div>
            </div>
            <div class="col-lg-5">
                <div style="background: var(--surface-soft); border-radius: var(--r-xxxl); padding: 40px; text-align:center;">
                    <i class="bi bi-heart-pulse" style="font-size:80px; color:var(--primary); display:block; margin-bottom:20px;"></i>
                    <p style="font-size:14px; font-weight:700; color:var(--slate); margin:0;">Prediksi akurat berbasis ML</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Feature cards --}}
<section style="padding: 80px 0; background: var(--surface-soft);">
    <div class="container-lg">
        <p class="section-label text-center mb-2">Keunggulan Sistem</p>
        <h2 style="font-size:36px; font-weight:500; text-align:center; margin-bottom:48px; color:var(--ink-deep);">Kenapa menggunakan sistem ini?</h2>
        <div class="row g-4">
            @foreach ([
                ['bi-cpu',            '6 Opsi Model',          'KNN, KNN+HPO, SVM, SVM+HPO, Decision Tree, dan DT+HPO bisa dipilih langsung.'],
                ['bi-clipboard-data', '24 Fitur Komprehensif', 'Demografi, gaya hidup, tekanan kerja/akademis, indikator kesehatan, dan riwayat medis.'],
                ['bi-graph-up',       'Riwayat Tersimpan',     'Semua prediksi tersimpan di database — bandingkan kondisi Anda dari waktu ke waktu.'],
            ] as [$icon, $title, $desc])
            <div class="col-md-4">
                <div style="background:var(--canvas); border-radius:var(--r-xl); border:1px solid var(--hairline-soft); padding:28px 24px; height:100%;">
                    <div style="width:48px; height:48px; background:var(--primary-soft); border-radius:var(--r-xl); display:flex; align-items:center; justify-content:center; margin-bottom:18px;">
                        <i class="bi {{ $icon }}" style="font-size:22px; color:var(--primary);"></i>
                    </div>
                    <h5 style="font-size:18px; font-weight:700; color:var(--ink-deep); margin-bottom:10px;">{{ $title }}</h5>
                    <p style="font-size:15px; color:var(--charcoal); line-height:1.55; margin:0;">{{ $desc }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Stats + Dataset --}}
<section style="padding: 80px 0;">
    <div class="container-lg">

        {{-- Stats row --}}
        <div class="row g-4 mb-5">
            @php
                $stats = [
                    ['bi-database',        number_format($datasetSize ?? 25000), 'Total Records',       $retrainCount > 0 ? 'Termasuk ' . number_format($userRows) . ' data user' : 'Dataset awal',        'var(--primary)'],
                    ['bi-list-columns',    '24',                                  'Fitur Input',         'Demografi, gaya hidup, psikologis, medis',                                                         'var(--primary)'],
                    ['bi-bullseye',        '1',                                   'Target Label',        '3 kelas: Rendah / Sedang / Tinggi',                                                                'var(--primary)'],
                    ['bi-clock-history',   number_format($totalPredictions ?? 0), 'Prediksi Tersimpan',  'Dari seluruh pengguna sistem',                                                                     'var(--primary)'],
                    ['bi-arrow-repeat',    number_format($userRows ?? 0),          'Data Diretrain',      $retrainCount > 0 ? 'Retrain ke-' . $retrainCount . ' · ' . number_format($datasetSize ?? 25000) . ' baris total' : 'Belum ada retrain', $retrainCount > 0 ? 'var(--success)' : 'var(--slate)'],
                ];
            @endphp
            @foreach ($stats as [$icon, $val, $label, $sub, $color])
            <div class="col-6 col-lg">
                <div style="background:var(--canvas); border:1px solid var(--hairline-soft); border-radius:var(--r-xl); padding:24px 20px; text-align:center; height:100%;">
                    <i class="bi {{ $icon }}" style="font-size:28px; color:{{ $color }}; display:block; margin-bottom:12px;"></i>
                    <div style="font-size:28px; font-weight:700; color:var(--ink-deep); line-height:1;">{{ $val }}</div>
                    <div style="font-size:13px; font-weight:700; color:var(--charcoal); margin-top:6px;">{{ $label }}</div>
                    <div style="font-size:11px; color:var(--slate); margin-top:3px;">{{ $sub }}</div>
                </div>
            </div>
            @endforeach
        </div>


        <div class="row g-5 align-items-start">

            {{-- Feature breakdown --}}
            <div class="col-lg-7">
                <p class="section-label mb-2">Dataset</p>
                <h2 style="font-size:36px; font-weight:500; color:var(--ink-deep); margin-bottom:28px; line-height:1.28;">25 Fitur Dataset</h2>

                @php
                $featureGroups = [
                    ['bi-person',           'Demografi',           'var(--primary)',  [
                        ['age',               'Umur',                         'Numerik (tahun)'],
                        ['gender',            'Jenis Kelamin',                'Kategorik: Male / Female / Other'],
                        ['marital_status',    'Status Pernikahan',            'Kategorik: Single / Married / Divorced'],
                        ['education_level',   'Tingkat Pendidikan',           'Ordinal: High School → Bachelor → Master → PhD'],
                        ['employment_status', 'Status Pekerjaan',             'Kategorik: Employed / Unemployed / Self-Employed / Student'],
                    ]],
                    ['bi-activity',         'Gaya Hidup',          '#0ea5e9',         [
                        ['sleep_hours',                          'Jam Tidur per Hari',             'Numerik (0–24 jam)'],
                        ['physical_activity_hours_per_week',     'Aktivitas Fisik per Minggu',     'Numerik (jam)'],
                        ['screen_time_hours_per_day',            'Screen Time per Hari',           'Numerik (0–24 jam)'],
                        ['substance_use',                        'Penggunaan Zat',                 'Biner: 0 = Tidak / 1 = Ya'],
                    ]],
                    ['bi-graph-up-arrow',   'Beban & Stres',       '#f59e0b',         [
                        ['work_stress_level',         'Tingkat Stres Kerja',          'Skala 0–10'],
                        ['academic_pressure_level',   'Tekanan Akademik',             'Skala 0–10'],
                        ['financial_stress_level',    'Stres Finansial',              'Skala 0–10'],
                        ['working_hours_per_week',    'Jam Kerja per Minggu',         'Numerik (jam)'],
                        ['stress_level',              'Tingkat Stres Umum',           'Skala 0–10'],
                    ]],
                    ['bi-heart',            'Kesehatan Psikologis','#ec4899',         [
                        ['social_support_score',           'Dukungan Sosial',              'Skala 0–10'],
                        ['job_satisfaction_score',         'Kepuasan Kerja',               'Skala 0–10'],
                        ['anxiety_score',                  'Skor Kecemasan',               'Skala 0–10'],
                        ['depression_score',               'Skor Depresi',                 'Skala 0–10'],
                        ['mood_swings_frequency',          'Frekuensi Mood Swing',         'Skala 0–10'],
                        ['concentration_difficulty_level', 'Kesulitan Konsentrasi',        'Skala 0–10'],
                    ]],
                    ['bi-clipboard2-pulse', 'Riwayat Medis',       '#10b981',         [
                        ['panic_attack_history',               'Riwayat Serangan Panik',   'Biner: 0 = Tidak / 1 = Ya'],
                        ['family_history_mental_illness',      'Riwayat Keluarga',         'Biner: 0 = Tidak / 1 = Ya'],
                        ['previous_mental_health_diagnosis',   'Diagnosis Sebelumnya',     'Biner: 0 = Tidak / 1 = Ya'],
                        ['therapy_history',                    'Riwayat Terapi',           'Biner: 0 = Tidak / 1 = Ya'],
                    ]],
                    ['bi-bullseye',         'Target (Label)',       'var(--critical)', [
                        ['mental_health_risk', 'Risiko Kesehatan Mental', '0 = Rendah · 1 = Sedang · 2 = Tinggi'],
                    ]],
                ];
                @endphp

                <div class="accordion" id="featureAccordion">
                @foreach ($featureGroups as $gi => [$icon, $groupName, $color, $features])
                    <div class="accordion-item" style="border:1px solid var(--hairline-soft); border-radius:var(--r-xl) !important; margin-bottom:8px; overflow:hidden;">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ $gi > 0 ? 'collapsed' : '' }}"
                                    type="button" data-bs-toggle="collapse"
                                    data-bs-target="#fg{{ $gi }}"
                                    style="background:var(--canvas); font-weight:700; font-size:14px; color:var(--ink-deep); padding:14px 20px; box-shadow:none;">
                                <i class="bi {{ $icon }}" style="font-size:16px; color:{{ $color }}; margin-right:10px;"></i>
                                {{ $groupName }}
                                <span style="margin-left:auto; margin-right:12px; font-size:12px; font-weight:400; color:var(--slate);">{{ count($features) }} fitur</span>
                            </button>
                        </h2>
                        <div id="fg{{ $gi }}" class="accordion-collapse collapse {{ $gi === 0 ? 'show' : '' }}" data-bs-parent="#featureAccordion">
                            <div class="accordion-body" style="padding:0;">
                                <table class="table table-sm mb-0" style="font-size:13px;">
                                    <tbody>
                                    @foreach ($features as [$colName, $label, $type])
                                    <tr>
                                        <td style="padding:10px 20px; font-family:monospace; color:var(--primary); font-size:12px; white-space:nowrap; width:1%; border-color:var(--hairline-soft);">{{ $colName }}</td>
                                        <td style="padding:10px 12px; font-weight:600; color:var(--ink-deep); border-color:var(--hairline-soft);">{{ $label }}</td>
                                        <td style="padding:10px 20px; color:var(--slate); border-color:var(--hairline-soft);">{{ $type }}</td>
                                    </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
                </div>
            </div>

            {{-- Label Prediksi --}}
            <div class="col-lg-5">
                <p class="section-label mb-2">Output Model</p>
                <h2 style="font-size:36px; font-weight:500; color:var(--ink-deep); margin-bottom:28px; line-height:1.28;">Kategori Risiko</h2>
                <div class="d-flex flex-column gap-3">
                    @foreach ([
                        ['0 — Rendah', 'var(--success)',  'Risiko rendah. Tetap jaga pola hidup sehat dan keseimbangan mental.'],
                        ['1 — Sedang',  '#d68910',        'Perlu perhatian. Pertimbangkan konsultasi preventif dengan profesional.'],
                        ['2 — Tinggi',  'var(--critical)', 'Risiko tinggi. Segera konsultasikan dengan tenaga kesehatan mental.'],
                    ] as [$label, $color, $desc])
                    <div style="background:var(--canvas); border:1px solid var(--hairline-soft); border-radius:var(--r-xl); padding:20px; display:flex; gap:16px; align-items:flex-start;">
                        <div style="width:10px; height:10px; border-radius:50%; background:{{ $color }}; flex-shrink:0; margin-top:5px;"></div>
                        <div>
                            <div style="font-size:15px; font-weight:700; color:var(--ink-deep); margin-bottom:4px;">{{ $label }}</div>
                            <div style="font-size:14px; color:var(--charcoal);">{{ $desc }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Encoding info --}}
                <div style="margin-top:28px; background:var(--surface-soft); border-radius:var(--r-xl); padding:20px;">
                    <p style="font-size:12px; font-weight:700; color:var(--slate); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:12px;">Preprocessing</p>
                    <div class="d-flex flex-column gap-2">
                        <div style="font-size:13px; color:var(--charcoal);">
                            <i class="bi bi-arrow-right-short" style="color:var(--primary);"></i>
                            <strong>Ordinal Encoding</strong> — education_level (0–3)
                        </div>
                        <div style="font-size:13px; color:var(--charcoal);">
                            <i class="bi bi-arrow-right-short" style="color:var(--primary);"></i>
                            <strong>One-Hot Encoding</strong> — gender, marital_status, employment_status
                        </div>
                        <div style="font-size:13px; color:var(--charcoal);">
                            <i class="bi bi-arrow-right-short" style="color:var(--primary);"></i>
                            <strong>StandardScaler</strong> — normalisasi semua fitur numerik
                        </div>
                        <div style="font-size:13px; color:var(--charcoal);">
                            <i class="bi bi-arrow-right-short" style="color:var(--primary);"></i>
                            <strong>Hasil akhir:</strong> 31 kolom fitur (setelah OHE)
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

@if(!empty($bestModel))
<section style="padding:64px 0; background:var(--surface-soft);">
    <div class="container-lg text-center">
        <p class="section-label mb-2">Model Terbaik Saat Ini</p>
        <h2 style="font-size:48px; font-weight:500; color:var(--ink-deep); margin-bottom:8px;">{{ $bestModel->algorithm }}</h2>
        <p style="font-size:28px; font-weight:700; color:var(--primary); margin-bottom:4px;">{{ number_format($bestModel->accuracy * 100, 2) }}%</p>
        <p style="font-size:14px; color:var(--slate);">F1-Score: {{ number_format($bestModel->f1_score * 100, 2) }}%</p>
    </div>
</section>
@endif

{{-- CTA --}}
<section style="padding:80px 0; background:var(--ink-deep); margin-top:80px;">
    <div class="container-lg text-center">
        <h2 style="font-size:40px; font-weight:500; color:#fff; margin-bottom:16px; line-height:1.17;">Siap mencoba?</h2>
        <p style="font-size:18px; font-weight:300; color:rgba(255,255,255,.7); margin-bottom:36px; max-width:480px; margin-left:auto; margin-right:auto;">
            Isi form prediksi dan dapatkan hasil analisis kesehatan mental dalam hitungan detik.
        </p>
        <a href="{{ route('predict.form') }}" class="btn btn-lg" style="background:#fff; color:var(--ink-deep); border-radius:var(--r-full);">
            Mulai Sekarang &rarr;
        </a>
    </div>
</section>

@endsection
