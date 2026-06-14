@extends('layouts.app')

@section('title', 'Informasi Model ML')

@section('content')
<div class="container-lg" style="padding-top:48px; padding-bottom:80px;">

    {{-- Header --}}
    <div class="mb-5">
        <p class="section-label mb-1">Machine Learning</p>
        <h1 style="font-size:36px; font-weight:500; color:var(--ink-deep); margin:0 0 12px;">Informasi Model</h1>
        <p style="font-size:16px; color:var(--charcoal); max-width:620px; margin:0;">
            Performa setiap algoritma pada test set saat training. Enam model aktif:
            KNN, KNN+HPO, SVM, SVM+HPO, Decision Tree, dan DT+HPO.
        </p>
    </div>

    @if ($metrics->isEmpty())
        <div class="alert alert-warning">
            Belum ada metrik tersimpan. Jalankan
            <code>python storage/models/train_models.py</code> lalu
            <code>php artisan db:seed --class=ModelMetricSeeder</code>.
        </div>
    @else
        <div class="row g-4 mb-5">
            @php
                $bars = [
                    ['Accuracy',  'accuracy',  'var(--primary)'],
                    ['Precision', 'precision', 'var(--success)'],
                    ['Recall',    'recall',    'var(--oculus-purple)'],
                    ['F1-Score',  'f1_score',  'var(--attention)'],
                ];
            @endphp
            @foreach ($metrics as $metric)
                <div class="col-md-6 col-lg-4">
                    <div style="background:var(--canvas); border:1px solid var(--hairline-soft); border-radius:var(--r-xl); padding:24px; height:100%;">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-cpu" style="font-size:18px; color:var(--primary);"></i>
                            <h5 style="font-size:18px; font-weight:700; color:var(--ink-deep); margin:0;">{{ $metric->algorithm }}</h5>
                        </div>
                        @foreach ($bars as [$label, $field, $color])
                            <div class="d-flex justify-content-between" style="margin-bottom:4px;">
                                <span style="font-size:13px; color:var(--slate);">{{ $label }}</span>
                                <strong style="font-size:13px; color:var(--ink-deep);">{{ number_format($metric->$field * 100, 2) }}%</strong>
                            </div>
                            <div style="height:8px; background:var(--surface-soft); border-radius:var(--r-full); overflow:hidden; margin-bottom:16px;">
                                <div style="height:100%; width:{{ $metric->$field * 100 }}%; background:{{ $color }}; border-radius:var(--r-full);"></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Penjelasan Algoritma --}}
    <p class="section-label mb-2">Referensi</p>
    <h2 style="font-size:28px; font-weight:300; color:var(--ink-deep); margin-bottom:24px;">Penjelasan Algoritma</h2>

    <div class="accordion" id="algoAccordion">
        <div class="accordion-item" style="border:1px solid var(--hairline-soft); border-radius:var(--r-xl) !important; margin-bottom:8px; overflow:hidden;">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#knnDetail"
                        style="background:var(--canvas); font-weight:700; font-size:15px; color:var(--ink-deep); box-shadow:none;">
                    K-Nearest Neighbors (KNN)
                </button>
            </h2>
            <div id="knnDetail" class="accordion-collapse collapse show" data-bs-parent="#algoAccordion">
                <div class="accordion-body">
                    <p>KNN memprediksi kelas berdasarkan k tetangga terdekat di feature space. Model dasar memakai
                        <strong>n_neighbors=89</strong> dengan metric <strong>manhattan</strong>. Varian
                        <strong>KNN+HPO</strong> mencari kombinasi terbaik via GridSearchCV (k ganjil 1–99 &times; {euclidean, manhattan}).</p>
                    <ul>
                        <li>Mudah dipahami dan tidak memerlukan training phase eksplisit</li>
                        <li>Membutuhkan feature scaling agar jarak antar fitur seimbang</li>
                        <li>Sensitif pada dataset besar / high-dimensional</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item" style="border:1px solid var(--hairline-soft); border-radius:var(--r-xl) !important; margin-bottom:8px; overflow:hidden;">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#svmDetail"
                        style="background:var(--canvas); font-weight:700; font-size:15px; color:var(--ink-deep); box-shadow:none;">
                    Support Vector Machine (SVM)
                </button>
            </h2>
            <div id="svmDetail" class="accordion-collapse collapse" data-bs-parent="#algoAccordion">
                <div class="accordion-body">
                    <p>SVM mencari hyperplane dengan margin maksimum yang memisahkan kelas. Model dasar memakai kernel
                        <strong>RBF</strong> dengan <strong>C=10</strong>, <strong>gamma=0.01</strong>, dan
                        <code>probability=True</code> agar dapat menampilkan confidence. Varian <strong>SVM+HPO</strong>
                        memilih estimator terbaik via GridSearchCV.</p>
                    <ul>
                        <li>Kuat untuk data berdimensi tinggi</li>
                        <li>Generalisasi bagus dengan margin maksimum</li>
                        <li>Wajib feature scaling, training relatif lambat untuk data besar</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item" style="border:1px solid var(--hairline-soft); border-radius:var(--r-xl) !important; margin-bottom:8px; overflow:hidden;">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#dtDetail"
                        style="background:var(--canvas); font-weight:700; font-size:15px; color:var(--ink-deep); box-shadow:none;">
                    Decision Tree (DT)
                </button>
            </h2>
            <div id="dtDetail" class="accordion-collapse collapse" data-bs-parent="#algoAccordion">
                <div class="accordion-body">
                    <p>Decision Tree membangun struktur pohon berbasis pertanyaan yes/no terhadap fitur. Model dasar dibatasi
                        <code>max_depth=10</code> untuk mencegah overfitting. Varian <strong>DT+HPO</strong> menyetel
                        criterion, max_depth, min_samples_split, dan min_samples_leaf via GridSearchCV.</p>
                    <ul>
                        <li>Interpretable — bisa divisualisasikan</li>
                        <li>Tidak butuh feature scaling</li>
                        <li>Rentan overfitting jika kedalaman tidak dibatasi</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item" style="border:1px solid var(--hairline-soft); border-radius:var(--r-xl) !important; margin-bottom:8px; overflow:hidden;">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#hpoDetail"
                        style="background:var(--canvas); font-weight:700; font-size:15px; color:var(--ink-deep); box-shadow:none;">
                    HPO — Hyperparameter Tuning
                </button>
            </h2>
            <div id="hpoDetail" class="accordion-collapse collapse" data-bs-parent="#algoAccordion">
                <div class="accordion-body">
                    <p>Setiap algoritma punya varian <strong>+HPO</strong> yang menjalankan GridSearchCV dengan cross-validation
                        untuk mencari kombinasi hyperparameter terbaik. Saat prediksi, hasil ditentukan oleh satu model yang
                        Anda pilih di form — sistem menampilkan prediksi dan confidence model tersebut (bukan ensemble voting).</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
