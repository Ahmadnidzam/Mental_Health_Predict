@extends('layouts.app')

@section('title', 'Beranda - Mental Health Prediction')

@section('content')
<div class="container-lg">
    <section class="text-center py-5">
        <h1 class="display-4 fw-bold mb-3">
            Deteksi Dini <span class="text-primary">Risiko Kesehatan Mental</span>
        </h1>
        <p class="lead text-muted mb-4 mx-auto" style="max-width:720px;">
            Sistem prediksi berbasis Machine Learning yang menggunakan ensemble dari tiga algoritma populer
            (KNN, SVM, dan Decision Tree) untuk membantu Anda mengenali kemungkinan risiko kesehatan mental
            berdasarkan 24 faktor demografi, gaya hidup, dan kondisi psikososial.
        </p>
        <a href="{{ route('predict.form') }}" class="btn btn-primary btn-lg me-2">
            <i class="bi bi-clipboard2-pulse"></i> Mulai Prediksi
        </a>
        <a href="{{ route('models') }}" class="btn btn-outline-primary btn-lg">
            Lihat Performa Model
        </a>
    </section>

    <section class="mb-5">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-cpu fs-1 text-primary"></i>
                        <h5 class="card-title mt-3">3 Algoritma ML</h5>
                        <p class="card-text text-muted">
                            KNN, SVM, dan Decision Tree dijalankan paralel.
                            Hasil akhir ditentukan dengan majority voting agar lebih robust.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-clipboard-data fs-1 text-primary"></i>
                        <h5 class="card-title mt-3">24 Fitur Input</h5>
                        <p class="card-text text-muted">
                            Mempertimbangkan demografi, gaya hidup, tekanan kerja/akademis,
                            indikator kesehatan, dan riwayat medis Anda.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-graph-up fs-1 text-primary"></i>
                        <h5 class="card-title mt-3">Riwayat Tersimpan</h5>
                        <p class="card-text text-muted">
                            Semua prediksi dicatat di database sehingga Anda
                            bisa membandingkan kondisi dari waktu ke waktu.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white p-5 rounded shadow-sm mb-5">
        <h3 class="mb-4"><i class="bi bi-database"></i> Informasi Dataset</h3>
        <div class="row">
            <div class="col-md-6">
                <p><strong>Total Records:</strong> 25.000 baris</p>
                <p><strong>Total Fitur:</strong> 24 fitur input + 1 target</p>
                <p><strong>Total Prediksi Tersimpan:</strong> {{ $totalPredictions ?? 0 }}</p>
            </div>
            <div class="col-md-6">
                <p class="mb-2"><strong>Kategori Risiko:</strong></p>
                <ul class="list-unstyled">
                    <li><span class="badge badge-risk-0">0 - Rendah</span> Risiko kesehatan mental rendah</li>
                    <li class="mt-1"><span class="badge badge-risk-1">1 - Sedang</span> Risiko sedang, perlu perhatian</li>
                    <li class="mt-1"><span class="badge badge-risk-2">2 - Tinggi</span> Risiko tinggi, sebaiknya konsultasi</li>
                </ul>
            </div>
        </div>
    </section>

    @if(!empty($bestModel))
    <section class="text-center bg-primary bg-opacity-10 p-4 rounded mb-5">
        <h5 class="mb-2">Model dengan Akurasi Tertinggi</h5>
        <h3 class="mb-1">{{ $bestModel->algorithm }} &mdash; {{ number_format($bestModel->accuracy * 100, 2) }}%</h3>
        <small class="text-muted">F1-Score: {{ number_format($bestModel->f1_score * 100, 2) }}%</small>
    </section>
    @endif

    <section class="text-center py-5">
        <h3 class="mb-3">Siap Mencoba?</h3>
        <p class="text-muted mb-4">Isi form prediksi dan dapatkan hasil dari ketiga model ML sekaligus.</p>
        <a href="{{ route('predict.form') }}" class="btn btn-primary btn-lg">Mulai Sekarang &rarr;</a>
    </section>
</div>
@endsection
