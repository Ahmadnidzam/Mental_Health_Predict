@extends('layouts.app')

@section('title', 'Tentang Kami')

@section('content')
<div class="container-lg">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <h2 class="mb-4"><i class="bi bi-info-circle"></i> Tentang Sistem Ini</h2>

            <div class="card shadow-sm mb-4">
                <div class="card-body p-4">
                    <h4>Visi & Misi</h4>
                    <p>
                        Sistem ini dikembangkan untuk membantu deteksi dini risiko kesehatan mental
                        menggunakan teknologi Machine Learning yang dapat diinterpretasikan.
                        Tujuan akhirnya adalah meningkatkan kesadaran dan mendorong tindakan preventif
                        sebelum masalah berkembang lebih jauh.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body p-4">
                    <h4>Tech Stack</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <strong>Backend</strong>
                            <ul class="mb-0">
                                <li>Laravel 11 (PHP)</li>
                                <li>MySQL untuk penyimpanan data</li>
                                <li>Eloquent ORM</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <strong>Machine Learning</strong>
                            <ul class="mb-0">
                                <li>Python 3 + scikit-learn</li>
                                <li>KNN, SVM, Decision Tree</li>
                                <li>StandardScaler & LabelEncoder</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <strong>Frontend</strong>
                            <ul class="mb-0">
                                <li>Bootstrap 5.3</li>
                                <li>Bootstrap Icons</li>
                                <li>Vanilla JS (AJAX fetch)</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <strong>Integrasi</strong>
                            <ul class="mb-0">
                                <li>Symfony Process (PHP &harr; Python)</li>
                                <li>JSON sebagai format pertukaran data</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body p-4">
                    <h4 class="mb-3">Tim Pengembang</h4>
                    <p class="text-muted mb-3">Project akademik ini dikembangkan oleh tim berikut:</p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 text-center">
                                <i class="bi bi-person-circle fs-2 text-primary"></i>
                                <h6 class="mt-2 mb-1">Ahmad Nidzomunnashil</h6>
                                <small class="text-muted">NIM: 607012400122</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 text-center">
                                <i class="bi bi-person-circle fs-2 text-primary"></i>
                                <h6 class="mt-2 mb-1">Vikry Achmad Sonjaya</h6>
                                <small class="text-muted">NIM: 607012400001</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 text-center">
                                <i class="bi bi-person-circle fs-2 text-primary"></i>
                                <h6 class="mt-2 mb-1">Mardini Dwi Putri</h6>
                                <small class="text-muted">NIM: 607012430015</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-warning shadow-sm">
                <h5><i class="bi bi-exclamation-triangle"></i> Disclaimer Penting</h5>
                <p class="mb-0">
                    Prediksi yang dihasilkan sistem ini bersifat <strong>edukatif dan referensi</strong> saja, dan
                    <strong>BUKAN diagnosis medis</strong>. Jangan gunakan sebagai pengganti konsultasi
                    profesional. Jika Anda atau orang terdekat menunjukkan tanda-tanda gangguan kesehatan mental
                    yang serius, segera hubungi tenaga profesional kesehatan mental atau layanan kegawatdaruratan
                    psikologis terdekat.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
