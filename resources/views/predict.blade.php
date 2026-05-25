@extends('layouts.app')

@section('title', 'Form Prediksi - Mental Health Prediction')

@section('content')
<div class="container-lg" style="padding-top:48px; padding-bottom:80px;">

    {{-- Page header --}}
    <div class="mb-5">
        <p class="section-label mb-1">Mental Health Prediction</p>
        <h1 style="font-size:36px; font-weight:500; color:var(--ink-deep); margin:0;">Form Prediksi</h1>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <strong>Periksa kembali isian Anda:</strong>
            <ul class="mb-0 mt-1 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- ===== TAB SWITCHER ===== --}}
    <div style="display:flex; gap:8px; margin-bottom:36px;" id="inputModeTabs">
        <button class="tab-pill active" id="tab-manual" data-target="panel-manual" type="button">
            <i class="bi bi-person-fill"></i> Input Data Pribadi
        </button>
        <button class="tab-pill" id="tab-csv" data-target="panel-csv" type="button">
            <i class="bi bi-filetype-csv"></i> Upload CSV
        </button>
    </div>

    @php
    $models = [
        'knn'     => ['label'=>'KNN',           'desc'=>'K-Nearest Neighbors',         'ready'=>true],
        'knn_hpo' => ['label'=>'KNN + HPO',     'desc'=>'KNN + Hyperparameter Tuning', 'ready'=>true],
        'svm'     => ['label'=>'SVM',           'desc'=>'Support Vector Machine',      'ready'=>true],
        'svm_hpo' => ['label'=>'SVM + HPO',     'desc'=>'SVM + Hyperparameter Tuning', 'ready'=>true],
        'dt'      => ['label'=>'Decision Tree', 'desc'=>'Pohon Keputusan',             'ready'=>false],
        'dt_hpo'  => ['label'=>'DT + HPO',      'desc'=>'Decision Tree + HPO',         'ready'=>false],
    ];
    $tips = [
        'age'                              => 'Usia Anda saat ini dalam satuan tahun (1–120).',
        'gender'                           => 'Jenis kelamin Anda: Laki-laki, Perempuan, atau Lainnya.',
        'marital_status'                   => 'Status pernikahan Anda saat ini.',
        'education_level'                  => 'Tingkat pendidikan formal tertinggi yang telah Anda selesaikan.',
        'employment_status'                => 'Kondisi pekerjaan Anda saat ini.',
        'sleep_hours'                      => 'Rata-rata jumlah jam tidur Anda per hari. Orang dewasa umumnya butuh 7–9 jam.',
        'physical_activity_hours_per_week' => 'Total jam aktivitas fisik (olahraga, gym, jalan kaki, dll.) dalam satu minggu.',
        'screen_time_hours_per_day'        => 'Rata-rata waktu menatap layar (HP, laptop, TV) per hari.',
        'social_support_score'             => 'Seberapa besar dukungan sosial yang Anda rasakan. 0 = tidak ada, 10 = sangat besar.',
        'work_stress_level'                => 'Tingkat stres akibat pekerjaan. 0 = tidak stres, 10 = sangat stres.',
        'academic_pressure_level'          => 'Tekanan dari tuntutan akademik. 0 = tidak ada, 10 = sangat besar.',
        'job_satisfaction_score'           => 'Kepuasan terhadap pekerjaan/studi. 0 = sangat tidak puas, 10 = sangat puas.',
        'financial_stress_level'           => 'Stres akibat kondisi keuangan. 0 = tidak ada, 10 = sangat parah.',
        'working_hours_per_week'           => 'Total jam kerja atau belajar efektif dalam satu minggu.',
        'anxiety_score'                    => 'Seberapa sering merasa cemas atau gelisah. 0 = tidak pernah, 10 = hampir selalu.',
        'depression_score'                 => 'Seberapa sering merasa sedih atau putus asa. 0 = tidak pernah, 10 = hampir selalu.',
        'stress_level'                     => 'Tingkat stres umum sehari-hari. 0 = tidak stres, 10 = sangat stres.',
        'mood_swings_frequency'            => 'Seberapa sering suasana hati berubah drastis. 0 = tidak pernah, 10 = sangat sering.',
        'concentration_difficulty_level'   => 'Kesulitan fokus pada tugas. 0 = tidak ada, 10 = sangat sulit.',
        'panic_attack_history'             => 'Apakah pernah mengalami serangan panik (detak jantung cepat, sesak napas, rasa takut tiba-tiba)?',
        'family_history_mental_illness'    => 'Apakah ada anggota keluarga inti yang pernah didiagnosis gangguan kesehatan mental?',
        'previous_mental_health_diagnosis' => 'Apakah pernah didiagnosis gangguan kesehatan mental oleh dokter atau psikiater?',
        'therapy_history'                  => 'Apakah pernah menjalani sesi terapi/konseling kesehatan mental secara profesional?',
        'substance_use'                    => 'Apakah mengonsumsi alkohol berlebihan, atau zat terlarang secara rutin?',
    ];
    $defaultModel = old('model', 'svm');
    @endphp

    {{-- ===== PANEL: INPUT MANUAL ===== --}}
    <div id="panel-manual">
        <form id="predictionForm" action="{{ route('predict.store') }}" method="POST">
            @csrf

            {{-- 00 — Model --}}
            <div class="form-section">
                <p class="section-label">00 — Pilih Model Prediksi</p>
                <div class="row g-3 mb-5">
                    @foreach ($models as $key => $m)
                    <div class="col-6 col-md-4">
                        <label class="radio-option {{ !$m['ready'] ? 'radio-option--disabled' : '' }}"
                               @if(!$m['ready']) title="Belum tersedia" @endif>
                            <input type="radio" name="model" value="{{ $key }}" class="d-none"
                                   @if(!$m['ready']) disabled @endif
                                   @checked($defaultModel===$key && $m['ready'])>
                            <div class="radio-option__inner">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <i class="bi {{ $m['ready'] ? 'bi-check-circle-fill' : 'bi-clock' }}"
                                       style="font-size:18px; color:{{ $m['ready'] ? 'var(--success)' : 'var(--stone)' }};"></i>
                                    @if(!$m['ready'])
                                        <span style="font-size:11px; font-weight:700; color:var(--slate); background:var(--surface-soft); border:1px solid var(--hairline-soft); padding:2px 8px; border-radius:var(--r-full);">Segera</span>
                                    @else
                                        <span style="font-size:11px; font-weight:700; color:var(--success); background:rgba(66,183,42,.1); padding:2px 8px; border-radius:var(--r-full);">Siap</span>
                                    @endif
                                </div>
                                <div style="font-size:14px; font-weight:700; color:var(--ink-deep); margin-bottom:3px;">{{ $m['label'] }}</div>
                                <div style="font-size:12px; color:var(--slate);">{{ $m['desc'] }}</div>
                            </div>
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- 01 — Demografi --}}
            <div class="form-section">
                <p class="section-label">01 — Demografi</p>
                <div class="row g-3 mb-5">
                    <div class="col-md-4">
                        <label class="form-label">Umur <i class="bi bi-info-circle info-tip" data-bs-toggle="tooltip" title="{{ $tips['age'] }}"></i></label>
                        <input type="number" class="form-control" name="age" min="1" max="120" value="{{ old('age') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Gender <i class="bi bi-info-circle info-tip" data-bs-toggle="tooltip" title="{{ $tips['gender'] }}"></i></label>
                        <select class="form-select" name="gender" required>
                            <option value="">Pilih...</option>
                            @foreach (['Male'=>'Laki-laki','Female'=>'Perempuan','Other'=>'Lainnya'] as $v=>$t)
                                <option value="{{ $v }}" @selected(old('gender')===$v)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status Pernikahan <i class="bi bi-info-circle info-tip" data-bs-toggle="tooltip" title="{{ $tips['marital_status'] }}"></i></label>
                        <select class="form-select" name="marital_status" required>
                            <option value="">Pilih...</option>
                            @foreach (['Single'=>'Lajang','Married'=>'Menikah','Divorced'=>'Cerai'] as $v=>$t)
                                <option value="{{ $v }}" @selected(old('marital_status')===$v)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Pendidikan Terakhir <i class="bi bi-info-circle info-tip" data-bs-toggle="tooltip" title="{{ $tips['education_level'] }}"></i></label>
                        <select class="form-select" name="education_level" required>
                            <option value="">Pilih...</option>
                            @foreach (['High School'=>'SMA/Sederajat','Bachelor'=>'Sarjana (S1)','Master'=>'Magister (S2)','PhD'=>'Doktor (S3)'] as $v=>$t)
                                <option value="{{ $v }}" @selected(old('education_level')===$v)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status Pekerjaan <i class="bi bi-info-circle info-tip" data-bs-toggle="tooltip" title="{{ $tips['employment_status'] }}"></i></label>
                        <select class="form-select" name="employment_status" required>
                            <option value="">Pilih...</option>
                            @foreach (['Employed'=>'Bekerja','Self-Employed'=>'Wiraswasta','Student'=>'Pelajar/Mahasiswa','Unemployed'=>'Tidak bekerja'] as $v=>$t)
                                <option value="{{ $v }}" @selected(old('employment_status')===$v)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- 02 — Gaya Hidup --}}
            <div class="form-section">
                <p class="section-label">02 — Gaya Hidup</p>
                <div class="row g-3 mb-5">
                    <div class="col-md-3">
                        <label class="form-label">Jam Tidur / hari <i class="bi bi-info-circle info-tip" data-bs-toggle="tooltip" title="{{ $tips['sleep_hours'] }}"></i></label>
                        <input type="number" step="0.1" class="form-control" name="sleep_hours" min="0" max="24" value="{{ old('sleep_hours') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Aktivitas Fisik (jam/minggu) <i class="bi bi-info-circle info-tip" data-bs-toggle="tooltip" title="{{ $tips['physical_activity_hours_per_week'] }}"></i></label>
                        <input type="number" step="0.1" class="form-control" name="physical_activity_hours_per_week" min="0" max="168" value="{{ old('physical_activity_hours_per_week') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Screen Time (jam/hari) <i class="bi bi-info-circle info-tip" data-bs-toggle="tooltip" title="{{ $tips['screen_time_hours_per_day'] }}"></i></label>
                        <input type="number" step="0.1" class="form-control" name="screen_time_hours_per_day" min="0" max="24" value="{{ old('screen_time_hours_per_day') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Social Support (0-10) <i class="bi bi-info-circle info-tip" data-bs-toggle="tooltip" title="{{ $tips['social_support_score'] }}"></i></label>
                        <input type="number" class="form-control" name="social_support_score" min="0" max="10" value="{{ old('social_support_score') }}" required>
                    </div>
                </div>
            </div>

            {{-- 03 — Pekerjaan --}}
            <div class="form-section">
                <p class="section-label">03 — Pekerjaan &amp; Akademik</p>
                <div class="row g-3 mb-5">
                    <div class="col-md-4">
                        <label class="form-label">Stress Kerja (0-10) <i class="bi bi-info-circle info-tip" data-bs-toggle="tooltip" title="{{ $tips['work_stress_level'] }}"></i></label>
                        <input type="number" class="form-control" name="work_stress_level" min="0" max="10" value="{{ old('work_stress_level') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tekanan Akademik (0-10) <i class="bi bi-info-circle info-tip" data-bs-toggle="tooltip" title="{{ $tips['academic_pressure_level'] }}"></i></label>
                        <input type="number" class="form-control" name="academic_pressure_level" min="0" max="10" value="{{ old('academic_pressure_level') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kepuasan Kerja (0-10) <i class="bi bi-info-circle info-tip" data-bs-toggle="tooltip" title="{{ $tips['job_satisfaction_score'] }}"></i></label>
                        <input type="number" class="form-control" name="job_satisfaction_score" min="0" max="10" value="{{ old('job_satisfaction_score') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Stress Finansial (0-10) <i class="bi bi-info-circle info-tip" data-bs-toggle="tooltip" title="{{ $tips['financial_stress_level'] }}"></i></label>
                        <input type="number" class="form-control" name="financial_stress_level" min="0" max="10" value="{{ old('financial_stress_level') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jam Kerja / minggu <i class="bi bi-info-circle info-tip" data-bs-toggle="tooltip" title="{{ $tips['working_hours_per_week'] }}"></i></label>
                        <input type="number" class="form-control" name="working_hours_per_week" min="0" max="168" value="{{ old('working_hours_per_week') }}" required>
                    </div>
                </div>
            </div>

            {{-- 04 — Indikator Kesehatan --}}
            <div class="form-section">
                <p class="section-label">04 — Indikator Kesehatan</p>
                <div class="row g-3 mb-5">
                    <div class="col-md-4">
                        <label class="form-label">Anxiety Score (0-10) <i class="bi bi-info-circle info-tip" data-bs-toggle="tooltip" title="{{ $tips['anxiety_score'] }}"></i></label>
                        <input type="number" class="form-control" name="anxiety_score" min="0" max="10" value="{{ old('anxiety_score') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Depression Score (0-10) <i class="bi bi-info-circle info-tip" data-bs-toggle="tooltip" title="{{ $tips['depression_score'] }}"></i></label>
                        <input type="number" class="form-control" name="depression_score" min="0" max="10" value="{{ old('depression_score') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Stress Level (0-10) <i class="bi bi-info-circle info-tip" data-bs-toggle="tooltip" title="{{ $tips['stress_level'] }}"></i></label>
                        <input type="number" class="form-control" name="stress_level" min="0" max="10" value="{{ old('stress_level') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mood Swings (0-10) <i class="bi bi-info-circle info-tip" data-bs-toggle="tooltip" title="{{ $tips['mood_swings_frequency'] }}"></i></label>
                        <input type="number" class="form-control" name="mood_swings_frequency" min="0" max="10" value="{{ old('mood_swings_frequency') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sulit Konsentrasi (0-10) <i class="bi bi-info-circle info-tip" data-bs-toggle="tooltip" title="{{ $tips['concentration_difficulty_level'] }}"></i></label>
                        <input type="number" class="form-control" name="concentration_difficulty_level" min="0" max="10" value="{{ old('concentration_difficulty_level') }}" required>
                    </div>
                </div>
            </div>

            {{-- 05 — Riwayat Medis --}}
            <div class="form-section">
                <p class="section-label">05 — Riwayat Medis</p>
                <div class="row g-3 mb-5">
                    @foreach ([
                        'panic_attack_history'             => 'Pernah Panic Attack?',
                        'family_history_mental_illness'    => 'Riwayat MH di Keluarga?',
                        'previous_mental_health_diagnosis' => 'Pernah Didiagnosis MH?',
                        'therapy_history'                  => 'Pernah Terapi?',
                        'substance_use'                    => 'Penggunaan Zat?',
                    ] as $field => $label)
                        <div class="col-md-4">
                            <label class="form-label">{{ $label }} <i class="bi bi-info-circle info-tip" data-bs-toggle="tooltip" title="{{ $tips[$field] }}"></i></label>
                            <select class="form-select" name="{{ $field }}" required>
                                <option value="">Pilih...</option>
                                <option value="0" @selected(old($field)==='0')>Tidak</option>
                                <option value="1" @selected(old($field)==='1')>Ya</option>
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="btn btn-buy btn-lg w-100" style="font-size:16px;">
                <i class="bi bi-cpu me-2"></i>Lakukan Prediksi
            </button>
        </form>

        <div id="resultSection" style="display:none; margin-top:40px;">
            <hr style="border-color:var(--hairline-soft);">
            <div id="loadingSpinner" class="text-center py-5" style="display:none;">
                <div class="spinner-border"></div>
                <p style="font-size:14px; color:var(--slate); margin-top:12px;">Memproses prediksi...</p>
            </div>
            <div id="resultCards"></div>
        </div>
    </div>

    {{-- ===== PANEL: UPLOAD CSV ===== --}}
    <div id="panel-csv" style="display:none;">

        <div class="alert alert-info mb-4">
            <strong>Format CSV:</strong> Sistem menerima dua format secara otomatis:
            <ul class="mb-1 mt-1 ps-3" style="font-size:13px;">
                <li><strong>Dengan header</strong> — baris pertama berisi nama kolom (urutan bebas, kolom ekstra seperti <code>mental_health_risk</code> di-ignore).</li>
                <li><strong>Tanpa header</strong> — langsung data mulai baris 1, urutan kolom harus sesuai <a href="#" id="downloadTemplate" class="alert-link">template</a>.</li>
            </ul>
            <div style="font-size:12px; color:var(--primary-deep);">
                Nilai biner (0/1): panic_attack_history, family_history_mental_illness, previous_mental_health_diagnosis, therapy_history, substance_use
            </div>
        </div>

        <form id="csvForm">
            @csrf
            <p class="section-label">00 — Pilih Model Prediksi</p>
            <div class="row g-3 mb-5">
                @foreach ($models as $key => $m)
                <div class="col-6 col-md-4">
                    <label class="radio-option {{ !$m['ready'] ? 'radio-option--disabled' : '' }}" @if(!$m['ready']) title="Belum tersedia" @endif>
                        <input type="radio" name="csv_model" value="{{ $key }}" class="d-none"
                               @if(!$m['ready']) disabled @endif
                               @if($key==='svm') checked @endif>
                        <div class="radio-option__inner">
                            <div class="d-flex align-items-start justify-content-between mb-2">
                                <i class="bi {{ $m['ready'] ? 'bi-check-circle-fill' : 'bi-clock' }}"
                                   style="font-size:18px; color:{{ $m['ready'] ? 'var(--success)' : 'var(--stone)' }};"></i>
                                @if(!$m['ready'])
                                    <span style="font-size:11px; font-weight:700; color:var(--slate); background:var(--surface-soft); border:1px solid var(--hairline-soft); padding:2px 8px; border-radius:var(--r-full);">Segera</span>
                                @else
                                    <span style="font-size:11px; font-weight:700; color:var(--success); background:rgba(66,183,42,.1); padding:2px 8px; border-radius:var(--r-full);">Siap</span>
                                @endif
                            </div>
                            <div style="font-size:14px; font-weight:700; color:var(--ink-deep); margin-bottom:3px;">{{ $m['label'] }}</div>
                            <div style="font-size:12px; color:var(--slate);">{{ $m['desc'] }}</div>
                        </div>
                    </label>
                </div>
                @endforeach
            </div>

            <p class="section-label">01 — Upload File CSV</p>
            <div class="upload-dropzone mb-5" id="dropzone">
                <i class="bi bi-cloud-upload" style="font-size:36px; color:var(--stone); display:block; margin-bottom:12px;"></i>
                <p style="font-size:15px; font-weight:500; color:var(--ink-deep); margin-bottom:4px;">Drag &amp; drop file CSV di sini</p>
                <p style="font-size:13px; color:var(--slate); margin-bottom:16px;">atau klik untuk memilih file</p>
                <input type="file" id="csvFileInput" accept=".csv,text/csv" class="d-none">
                <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('csvFileInput').click()">
                    <i class="bi bi-folder2-open me-2"></i>Pilih File
                </button>
                <div id="fileInfo" style="display:none; margin-top:14px;">
                    <span id="fileName" style="font-size:13px; font-weight:700; color:var(--success);"></span>
                    <button type="button" id="removeFile" style="background:none; border:none; color:var(--critical); cursor:pointer; font-size:13px; margin-left:10px;">
                        <i class="bi bi-x-circle"></i> Hapus
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-buy btn-lg w-100" style="font-size:16px;">
                <i class="bi bi-cpu me-2"></i>Proses CSV &amp; Prediksi
            </button>
        </form>

        <div id="csvResultSection" style="display:none; margin-top:40px;">
            <hr style="border-color:var(--hairline-soft);">
            <div id="csvLoadingSpinner" class="text-center py-5" style="display:none;">
                <div class="spinner-border"></div>
                <p style="font-size:14px; color:var(--slate); margin-top:12px;">Memproses prediksi batch...</p>
            </div>
            <div id="csvResultContent"></div>
        </div>
    </div>

</div>
@endsection

@push('head')
<style>
/* Tab pills */
.tab-pill {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 10px 20px; font-size: 14px; font-weight: 700;
    letter-spacing: -0.14px; border-radius: var(--r-full);
    border: 1px solid var(--hairline); background: var(--canvas);
    color: var(--ink); cursor: pointer; transition: background .15s, color .15s, border-color .15s;
    font-family: inherit;
}
.tab-pill.active { background: var(--ink-deep); color: #fff; border-color: var(--ink-deep); }
.tab-pill:not(.active):hover { background: var(--surface-soft); color: var(--ink-deep); }

/* Form sections */
.form-section {
    border-top: 1px solid var(--hairline-soft);
    padding-top: 28px;
    margin-bottom: 8px;
}

/* Radio option (model card) */
.radio-option { cursor: pointer; display: block; }
.radio-option--disabled { cursor: not-allowed; opacity: .45; }
.radio-option__inner {
    border: 1px solid rgba(10,19,23,.12);
    border-radius: var(--r-lg);
    padding: 16px;
    background: var(--canvas);
    transition: border-color .15s, border-width .1s;
}
.radio-option input:checked + .radio-option__inner {
    border: 2px solid var(--primary);
    background: var(--primary-soft);
}
.radio-option:not(.radio-option--disabled):hover .radio-option__inner { border-color: var(--primary); }

/* Info tip icon */
.info-tip {
    font-size: .8rem; color: var(--stone); cursor: pointer;
    vertical-align: middle; transition: color .15s;
}
.info-tip:hover { color: var(--primary); }

/* Tooltip override */
.tooltip-inner {
    background: var(--ink-deep); color: #fff;
    border-radius: var(--r-lg); font-size: 13px;
    font-weight: 400; max-width: 260px;
    text-align: left; padding: 8px 12px;
    font-family: inherit;
}

/* Upload dropzone */
.upload-dropzone {
    border: 2px dashed var(--hairline);
    border-radius: var(--r-xxxl);
    padding: 48px 24px;
    text-align: center;
    cursor: pointer;
    background: var(--surface-soft);
    transition: border-color .15s, background .15s;
}
.upload-dropzone.drag-over { border-color: var(--primary); background: var(--primary-soft); }
</style>
@endpush

@push('scripts')
<script>
const riskBg   = {0:'#42B72A', 1:'#F7B928', 2:'#E53935'};
const riskFg   = {0:'#fff',    1:'#1c1e21', 2:'#fff'};
const riskText = {0:'Rendah',  1:'Sedang',  2:'Tinggi'};
const modelLabels = {
    'knn':'K-Nearest Neighbors','knn_hpo':'KNN + HPO',
    'svm':'Support Vector Machine','svm_hpo':'SVM + HPO',
    'dt':'Decision Tree','dt_hpo':'Decision Tree + HPO',
};

// Tooltips
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el =>
        new bootstrap.Tooltip(el, {trigger:'hover focus', placement:'top'})
    );
});

// Tab switching
document.querySelectorAll('.tab-pill').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tab-pill').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const t = this.dataset.target;
        document.getElementById('panel-manual').style.display = t==='panel-manual' ? '' : 'none';
        document.getElementById('panel-csv').style.display    = t==='panel-csv'    ? '' : 'none';
    });
});

// Manual form submit
document.getElementById('predictionForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const data = {};
    new FormData(this).forEach((v,k) => { if(k!=='_token') data[k]=v; });
    if (!data.model) { alert('Pilih model prediksi terlebih dahulu.'); return; }

    const section = document.getElementById('resultSection');
    const spin    = document.getElementById('loadingSpinner');
    const out     = document.getElementById('resultCards');
    section.style.display = 'block';
    spin.style.display    = 'block';
    out.innerHTML = '';
    section.scrollIntoView({behavior:'smooth', block:'start'});

    try {
        const res  = await fetch('{{ route("predict.store") }}', {
            method:'POST',
            headers:{
                'Content-Type':'application/json',
                'Accept':'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(data),
        });
        const json = await res.json();
        spin.style.display = 'none';

        if (!res.ok || json.status !== 'success') {
            out.innerHTML = `<div class="alert alert-danger">${json.message||'Terjadi kesalahan'}</div>`;
            return;
        }

        const p     = json.prediction;
        const bg    = riskBg[p.final_prediction]   ?? '#ccc';
        const fg    = riskFg[p.final_prediction]   ?? '#000';
        const label = riskText[p.final_prediction] ?? '?';
        const mName = modelLabels[p.selected_model] ?? p.selected_model;
        const conf  = (p.confidence * 100).toFixed(2);

        out.innerHTML = `
            <p class="section-label mb-3">Hasil Prediksi</p>
            <div style="background:var(--surface-soft); border-radius:var(--r-xxxl); padding:32px; margin-bottom:20px; border:1px solid var(--hairline-soft);">
                <p style="font-size:13px; color:var(--slate); margin-bottom:12px;">Model: <strong style="color:var(--ink-deep);">${mName}</strong></p>
                <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                    <span style="padding:10px 28px; border-radius:var(--r-full); background:${bg}; color:${fg}; font-size:20px; font-weight:700;">${label}</span>
                    <span style="font-size:15px; color:var(--charcoal);">Confidence: <strong style="color:var(--ink-deep);">${conf}%</strong></span>
                </div>
            </div>
            <div class="alert alert-info">
                <strong>Catatan:</strong> Hasil ini hanyalah indikasi berbasis ML, BUKAN diagnosis medis.
                Untuk kondisi serius, silakan konsultasi dengan tenaga profesional kesehatan mental.
            </div>
            <a href="{{ route('history') }}" class="btn btn-outline-primary">Lihat Riwayat &rarr;</a>
        `;
    } catch (err) {
        spin.style.display = 'none';
        out.innerHTML = `<div class="alert alert-danger">Gagal terhubung: ${err.message}</div>`;
    }
});

// CSV dropzone
const dropzone   = document.getElementById('dropzone');
const csvInput   = document.getElementById('csvFileInput');
const fileInfo   = document.getElementById('fileInfo');
const fileNameEl = document.getElementById('fileName');
let selectedFile = null;

dropzone.addEventListener('click', e => { if(!e.target.closest('button')&&!e.target.closest('#fileInfo')) csvInput.click(); });
dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('drag-over'); });
dropzone.addEventListener('dragleave', () => dropzone.classList.remove('drag-over'));
dropzone.addEventListener('drop', e => { e.preventDefault(); dropzone.classList.remove('drag-over'); const f=e.dataTransfer.files[0]; if(f) setFile(f); });
csvInput.addEventListener('change', () => { if(csvInput.files[0]) setFile(csvInput.files[0]); });
document.getElementById('removeFile').addEventListener('click', () => { selectedFile=null; csvInput.value=''; fileInfo.style.display='none'; });

function setFile(file) {
    if (!file.name.toLowerCase().endsWith('.csv')) { alert('Hanya file .csv yang diterima.'); return; }
    selectedFile = file;
    fileNameEl.textContent = file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)';
    fileInfo.style.display = 'block';
}

// CSV template download
document.getElementById('downloadTemplate').addEventListener('click', e => {
    e.preventDefault();
    const cols = ['age','gender','marital_status','education_level','employment_status','sleep_hours','physical_activity_hours_per_week','screen_time_hours_per_day','social_support_score','work_stress_level','academic_pressure_level','job_satisfaction_score','financial_stress_level','working_hours_per_week','anxiety_score','depression_score','stress_level','mood_swings_frequency','concentration_difficulty_level','panic_attack_history','family_history_mental_illness','previous_mental_health_diagnosis','therapy_history','substance_use'];
    const ex   = ['25','Male','Single','Bachelor','Employed','7','3','4','6','5','4','7','3','40','4','3','5','3','2','0','0','0','0','0'];
    const csv  = cols.join(',') + '\n' + ex.join(',');
    const url  = URL.createObjectURL(new Blob([csv], {type:'text/csv'}));
    const a    = document.createElement('a'); a.href=url; a.download='template_prediksi.csv'; a.click();
    URL.revokeObjectURL(url);
});

// CSV submit
document.getElementById('csvForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    if (!selectedFile) { alert('Pilih file CSV terlebih dahulu.'); return; }
    const model = document.querySelector('input[name="csv_model"]:checked')?.value;
    if (!model) { alert('Pilih model prediksi terlebih dahulu.'); return; }

    const section = document.getElementById('csvResultSection');
    const spin    = document.getElementById('csvLoadingSpinner');
    const out     = document.getElementById('csvResultContent');
    section.style.display = 'block'; spin.style.display = 'block'; out.innerHTML = '';
    section.scrollIntoView({behavior:'smooth', block:'start'});

    const fd = new FormData();
    fd.append('model', model);
    fd.append('csv', selectedFile);

    try {
        const res  = await fetch('{{ route("predict.csv") }}', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: fd,
        });
        const json = await res.json();
        spin.style.display = 'none';

        if (!res.ok || json.status !== 'success') {
            out.innerHTML = `<div class="alert alert-danger">${json.message||'Terjadi kesalahan'}</div>`;
            return;
        }

        const mName = modelLabels[json.model] ?? json.model;
        const ok    = json.results.filter(r=>r.status==='success').length;
        const fail  = json.total - ok;

        let rows = '';
        json.results.forEach(r => {
            if (r.status==='success') {
                const bg = riskBg[r.final_prediction]??'#ccc', fg=riskFg[r.final_prediction]??'#000';
                rows += `<tr>
                    <td style="color:var(--slate);font-size:13px;">${r.row}</td>
                    <td style="font-weight:500;">${r.input.age??'—'}</td>
                    <td>${r.input.gender??'—'}</td>
                    <td><span style="padding:3px 10px;border-radius:var(--r-full);background:${bg};color:${fg};font-size:12px;font-weight:700;">${riskText[r.final_prediction]??'?'}</span></td>
                    <td style="font-weight:700;color:var(--ink-deep);">${(r.confidence*100).toFixed(2)}%</td>
                    <td><span style="font-size:12px;font-weight:700;color:var(--success);">Berhasil</span></td>
                </tr>`;
            } else {
                rows += `<tr style="background:rgba(229,57,53,.04);">
                    <td style="color:var(--slate);font-size:13px;">${r.row}</td>
                    <td colspan="4" style="font-size:13px;color:var(--critical);">${r.message}</td>
                    <td><span style="font-size:12px;font-weight:700;color:var(--critical);">Gagal</span></td>
                </tr>`;
            }
        });

        out.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <p class="section-label mb-1">Hasil Prediksi Batch</p>
                    <p style="font-size:14px;color:var(--charcoal);margin:0;">Model: <strong style="color:var(--ink-deep);">${mName}</strong></p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <span style="padding:4px 12px;border-radius:var(--r-full);background:var(--surface-soft);border:1px solid var(--hairline-soft);font-size:13px;font-weight:700;color:var(--ink);">${json.total} baris</span>
                    <span style="padding:4px 12px;border-radius:var(--r-full);background:rgba(66,183,42,.1);font-size:13px;font-weight:700;color:var(--success);">${ok} berhasil</span>
                    ${fail>0?`<span style="padding:4px 12px;border-radius:var(--r-full);background:rgba(229,57,53,.08);font-size:13px;font-weight:700;color:var(--critical);">${fail} gagal</span>`:''}
                </div>
            </div>
            <div class="table-container mb-4">
                <table class="table mb-0">
                    <thead><tr><th>#</th><th>Umur</th><th>Gender</th><th>Hasil Risiko</th><th>Confidence</th><th>Status</th></tr></thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
            <div class="alert alert-info">Semua baris yang berhasil telah disimpan ke riwayat prediksi.</div>
            <a href="{{ route('history') }}" class="btn btn-outline-primary">Lihat Riwayat &rarr;</a>
        `;
    } catch (err) {
        spin.style.display = 'none';
        out.innerHTML = `<div class="alert alert-danger">Gagal terhubung: ${err.message}</div>`;
    }
});
</script>
@endpush
