@extends('layouts.app')

@section('title', 'Informasi Model ML')

@section('content')
<div class="container-lg">
    <h2 class="mb-4"><i class="bi bi-cpu"></i> Informasi Model Machine Learning</h2>
    <p class="text-muted mb-4">Performa setiap algoritma pada test set saat training.</p>

    @if ($metrics->isEmpty())
        <div class="alert alert-warning">
            Belum ada metrik tersimpan. Jalankan
            <code>python storage/models/train_models.py</code> lalu
            <code>php artisan db:seed --class=ModelMetricSeeder</code>.
        </div>
    @else
        <div class="row g-4 mb-5">
            @foreach ($metrics as $metric)
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">{{ $metric->algorithm }}</h5>
                        </div>
                        <div class="card-body">
                            @php
                                $rows = [
                                    ['Accuracy', $metric->accuracy, 'primary'],
                                    ['Precision', $metric->precision, 'success'],
                                    ['Recall', $metric->recall, 'info'],
                                    ['F1-Score', $metric->f1_score, 'warning'],
                                ];
                            @endphp
                            @foreach ($rows as [$label, $value, $color])
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">{{ $label }}</small>
                                    <strong>{{ number_format($value * 100, 2) }}%</strong>
                                </div>
                                <div class="progress mb-3" style="height:8px;">
                                    <div class="progress-bar bg-{{ $color }}" style="width: {{ $value * 100 }}%"></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <h3 class="mb-3"><i class="bi bi-book"></i> Penjelasan Algoritma</h3>
    <div class="accordion" id="algoAccordion">
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#knnDetail">
                    K-Nearest Neighbors (KNN)
                </button>
            </h2>
            <div id="knnDetail" class="accordion-collapse collapse show" data-bs-parent="#algoAccordion">
                <div class="accordion-body">
                    <p>KNN memprediksi kelas berdasarkan k tetangga terdekat di feature space. Pada project ini digunakan <strong>k=7</strong> dengan bobot berbasis jarak (<em>distance-weighted</em>).</p>
                    <ul>
                        <li>Mudah dipahami dan tidak memerlukan training phase</li>
                        <li>Membutuhkan feature scaling agar jarak antar fitur seimbang</li>
                        <li>Sensitif pada dataset besar / high-dimensional</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#svmDetail">
                    Support Vector Machine (SVM)
                </button>
            </h2>
            <div id="svmDetail" class="accordion-collapse collapse" data-bs-parent="#algoAccordion">
                <div class="accordion-body">
                    <p>SVM mencari hyperplane dengan margin maksimum yang memisahkan kelas. Kami menggunakan kernel <strong>RBF</strong> (C=1.0) untuk menangani decision boundary non-linear, serta <code>probability=True</code> agar bisa menampilkan confidence.</p>
                    <ul>
                        <li>Kuat untuk data berdimensi tinggi</li>
                        <li>Generalisasi bagus dengan margin maksimum</li>
                        <li>Wajib feature scaling, training relatif lambat untuk data besar</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#dtDetail">
                    Decision Tree (DT)
                </button>
            </h2>
            <div id="dtDetail" class="accordion-collapse collapse" data-bs-parent="#algoAccordion">
                <div class="accordion-body">
                    <p>Decision Tree membangun struktur pohon berbasis pertanyaan yes/no terhadap fitur. Kami batasi <code>max_depth=10</code> untuk mencegah overfitting.</p>
                    <ul>
                        <li>Interpretable - bisa divisualisasikan</li>
                        <li>Tidak butuh feature scaling</li>
                        <li>Rentan overfitting jika kedalaman tidak dibatasi</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ensembleDetail">
                    Ensemble - Majority Voting
                </button>
            </h2>
            <div id="ensembleDetail" class="accordion-collapse collapse" data-bs-parent="#algoAccordion">
                <div class="accordion-body">
                    <p>Hasil akhir prediksi ditentukan dengan <strong>majority voting</strong> dari ketiga model. Jika ketiganya berbeda, dipilih prediksi dengan confidence tertinggi sebagai tiebreaker.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
