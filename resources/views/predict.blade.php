@extends('layouts.app')

@section('title', 'Form Prediksi - Mental Health Prediction')

@section('content')
<div class="container-lg">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white py-3">
                    <h3 class="mb-0"><i class="bi bi-clipboard2-pulse"></i> Form Prediksi Kesehatan Mental</h3>
                    <small class="text-white-50">Lengkapi 24 pertanyaan berikut. Semua field wajib diisi.</small>
                </div>
                <div class="card-body p-4 p-md-5">

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <strong>Periksa kembali isian Anda:</strong>
                            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <form id="predictionForm" action="{{ route('predict.store') }}" method="POST">
                        @csrf

                        <div class="section-divider mb-3"><h5 class="text-primary mb-0"><i class="bi bi-person"></i> 1. Demografi</h5></div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Umur</label>
                                <input type="number" class="form-control" name="age" min="1" max="120" value="{{ old('age') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Gender</label>
                                <select class="form-select" name="gender" required>
                                    <option value="">Pilih...</option>
                                    @foreach (['Male' => 'Laki-laki', 'Female' => 'Perempuan', 'Other' => 'Lainnya'] as $v => $t)
                                        <option value="{{ $v }}" @selected(old('gender')===$v)>{{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status Pernikahan</label>
                                <select class="form-select" name="marital_status" required>
                                    <option value="">Pilih...</option>
                                    @foreach (['Single' => 'Lajang', 'Married' => 'Menikah', 'Divorced' => 'Cerai'] as $v => $t)
                                        <option value="{{ $v }}" @selected(old('marital_status')===$v)>{{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pendidikan Terakhir</label>
                                <select class="form-select" name="education_level" required>
                                    <option value="">Pilih...</option>
                                    @foreach (['High School' => 'SMA/Sederajat', 'Bachelor' => 'Sarjana (S1)', 'Master' => 'Magister (S2)', 'PhD' => 'Doktor (S3)'] as $v => $t)
                                        <option value="{{ $v }}" @selected(old('education_level')===$v)>{{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status Pekerjaan</label>
                                <select class="form-select" name="employment_status" required>
                                    <option value="">Pilih...</option>
                                    @foreach (['Employed' => 'Bekerja', 'Self-Employed' => 'Wiraswasta', 'Student' => 'Pelajar/Mahasiswa', 'Unemployed' => 'Tidak bekerja'] as $v => $t)
                                        <option value="{{ $v }}" @selected(old('employment_status')===$v)>{{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="section-divider mb-3"><h5 class="text-primary mb-0"><i class="bi bi-activity"></i> 2. Gaya Hidup</h5></div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3"><label class="form-label">Jam Tidur / hari</label><input type="number" step="0.1" class="form-control" name="sleep_hours" min="0" max="24" value="{{ old('sleep_hours') }}" required></div>
                            <div class="col-md-3"><label class="form-label">Aktivitas Fisik (jam/minggu)</label><input type="number" step="0.1" class="form-control" name="physical_activity_hours_per_week" min="0" max="168" value="{{ old('physical_activity_hours_per_week') }}" required></div>
                            <div class="col-md-3"><label class="form-label">Screen Time (jam/hari)</label><input type="number" step="0.1" class="form-control" name="screen_time_hours_per_day" min="0" max="24" value="{{ old('screen_time_hours_per_day') }}" required></div>
                            <div class="col-md-3"><label class="form-label">Social Support (0-10)</label><input type="number" class="form-control" name="social_support_score" min="0" max="10" value="{{ old('social_support_score') }}" required></div>
                        </div>

                        <div class="section-divider mb-3"><h5 class="text-primary mb-0"><i class="bi bi-briefcase"></i> 3. Pekerjaan & Akademik</h5></div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4"><label class="form-label">Stress Kerja (0-10)</label><input type="number" class="form-control" name="work_stress_level" min="0" max="10" value="{{ old('work_stress_level') }}" required></div>
                            <div class="col-md-4"><label class="form-label">Tekanan Akademik (0-10)</label><input type="number" class="form-control" name="academic_pressure_level" min="0" max="10" value="{{ old('academic_pressure_level') }}" required></div>
                            <div class="col-md-4"><label class="form-label">Kepuasan Kerja (0-10)</label><input type="number" class="form-control" name="job_satisfaction_score" min="0" max="10" value="{{ old('job_satisfaction_score') }}" required></div>
                            <div class="col-md-6"><label class="form-label">Stress Finansial (0-10)</label><input type="number" class="form-control" name="financial_stress_level" min="0" max="10" value="{{ old('financial_stress_level') }}" required></div>
                            <div class="col-md-6"><label class="form-label">Jam Kerja / minggu</label><input type="number" class="form-control" name="working_hours_per_week" min="0" max="168" value="{{ old('working_hours_per_week') }}" required></div>
                        </div>

                        <div class="section-divider mb-3"><h5 class="text-primary mb-0"><i class="bi bi-heart"></i> 4. Indikator Kesehatan</h5></div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4"><label class="form-label">Anxiety Score (0-10)</label><input type="number" class="form-control" name="anxiety_score" min="0" max="10" value="{{ old('anxiety_score') }}" required></div>
                            <div class="col-md-4"><label class="form-label">Depression Score (0-10)</label><input type="number" class="form-control" name="depression_score" min="0" max="10" value="{{ old('depression_score') }}" required></div>
                            <div class="col-md-4"><label class="form-label">Stress Level (0-10)</label><input type="number" class="form-control" name="stress_level" min="0" max="10" value="{{ old('stress_level') }}" required></div>
                            <div class="col-md-6"><label class="form-label">Mood Swings (0-10)</label><input type="number" class="form-control" name="mood_swings_frequency" min="0" max="10" value="{{ old('mood_swings_frequency') }}" required></div>
                            <div class="col-md-6"><label class="form-label">Sulit Konsentrasi (0-10)</label><input type="number" class="form-control" name="concentration_difficulty_level" min="0" max="10" value="{{ old('concentration_difficulty_level') }}" required></div>
                        </div>

                        <div class="section-divider mb-3"><h5 class="text-primary mb-0"><i class="bi bi-file-medical"></i> 5. Riwayat Medis</h5></div>
                        <div class="row g-3 mb-4">
                            @foreach ([
                                'panic_attack_history'             => 'Pernah Panic Attack?',
                                'family_history_mental_illness'    => 'Riwayat MH di Keluarga?',
                                'previous_mental_health_diagnosis' => 'Pernah Didiagnosis MH?',
                                'therapy_history'                  => 'Pernah Terapi?',
                                'substance_use'                    => 'Penggunaan Zat?',
                            ] as $field => $label)
                                <div class="col-md-4">
                                    <label class="form-label">{{ $label }}</label>
                                    <select class="form-select" name="{{ $field }}" required>
                                        <option value="">Pilih...</option>
                                        <option value="0" @selected(old($field)==='0')>Tidak</option>
                                        <option value="1" @selected(old($field)==='1')>Ya</option>
                                    </select>
                                </div>
                            @endforeach
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-cpu"></i> Lakukan Prediksi
                        </button>
                    </form>

                    <div id="resultSection" class="mt-4" style="display:none;">
                        <hr>
                        <div id="loadingSpinner" class="text-center py-3" style="display:none;">
                            <div class="spinner-border text-primary"></div>
                            <p class="mt-2">Memproses prediksi...</p>
                        </div>
                        <div id="resultCards"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const labels = {0: {text:'Rendah', cls:'success'}, 1: {text:'Sedang', cls:'warning'}, 2: {text:'Tinggi', cls:'danger'}};
const form = document.getElementById('predictionForm');

form.addEventListener('submit', async function(e) {
    e.preventDefault();
    const data = {};
    new FormData(form).forEach((v, k) => { if (k !== '_token') data[k] = v; });

    const result = document.getElementById('resultSection');
    const spin = document.getElementById('loadingSpinner');
    const out = document.getElementById('resultCards');
    result.style.display = 'block';
    spin.style.display = 'block';
    out.innerHTML = '';
    result.scrollIntoView({behavior:'smooth', block:'start'});

    try {
        const res = await fetch('{{ route("predict.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        spin.style.display = 'none';

        if (!res.ok || json.status !== 'success') {
            out.innerHTML = `<div class="alert alert-danger">${(json.message || 'Terjadi kesalahan')}</div>`;
            return;
        }
        const p = json.prediction;
        const card = (title, pred, conf) => {
            const lab = labels[pred] || {text:'?', cls:'secondary'};
            return `
              <div class="col-md-4">
                <div class="card border-${lab.cls} h-100 text-center">
                  <div class="card-body">
                    <h6 class="text-muted">${title}</h6>
                    <h3 class="text-${lab.cls}">${lab.text}</h3>
                    <small class="text-muted">Confidence: ${(conf*100).toFixed(2)}%</small>
                  </div>
                </div>
              </div>`;
        };
        const final = labels[p.final_prediction] || {text:'?', cls:'secondary'};
        out.innerHTML = `
            <h4 class="mb-3"><i class="bi bi-bar-chart"></i> Hasil Prediksi</h4>
            <div class="row g-3 mb-3">
                ${card('K-Nearest Neighbors', p.knn_prediction, p.knn_confidence)}
                ${card('Support Vector Machine', p.svm_prediction, p.svm_confidence)}
                ${card('Decision Tree', p.dt_prediction, p.dt_confidence)}
            </div>
            <div class="alert alert-${final.cls} text-center">
                <h5 class="mb-1"><i class="bi bi-bullseye"></i> Prediksi Final (Majority Voting)</h5>
                <h2 class="mb-0">${final.text}</h2>
            </div>
            <div class="alert alert-info small">
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
</script>
@endpush
