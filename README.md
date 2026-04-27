# Mental Health Risk Prediction Web

Sistem web untuk prediksi tingkat risiko kesehatan mental (Rendah / Sedang / Tinggi) berbasis ensemble dari tiga algoritma machine learning klasik: **K-Nearest Neighbors**, **Support Vector Machine**, dan **Decision Tree**. Hasil akhir ditentukan dengan majority voting.

Dibangun dengan Laravel 12 (PHP) di sisi backend, scikit-learn (Python) untuk inferensi ML, dan Bootstrap 5 di sisi frontend.

## Fitur

Halaman beranda yang menampilkan ringkasan dataset dan model dengan akurasi tertinggi. Form prediksi 24 fitur input yang dikelompokkan jadi lima section (demografi, gaya hidup, pekerjaan/akademik, indikator kesehatan, riwayat medis). Halaman riwayat berisi semua prediksi yang pernah dilakukan beserta detail per record. Halaman info model menampilkan metrik tiap algoritma (accuracy, precision, recall, F1) dengan progress bar dan accordion penjelasan tiap algoritma. Halaman tentang berisi visi-misi, tech stack, dan disclaimer medis.

Data flow: form di-submit via AJAX, Laravel validasi 24 input, lalu memanggil script Python `predict.py` via Symfony Process. Script tersebut me-load model `.pkl` (KNN, SVM, DT) plus scaler dan encoders, melakukan preprocessing, prediksi tiga model paralel, lalu mengembalikan JSON. Laravel menyimpan ke tabel `predictions` dan menampilkan tiga kartu hasil + final majority vote di browser.

## Stack

- **Backend**: Laravel 12, PHP 8.2+
- **Database**: MySQL 5.7+ / MariaDB (default), SQLite (alternatif)
- **ML**: Python 3.8+, scikit-learn, pandas, numpy
- **Frontend**: Blade + Bootstrap 5.3 + Bootstrap Icons
- **Integrasi PHP-Python**: Symfony Process
- **Serialisasi model**: pickle (Python standard library, bukan joblib)

## Prasyarat

PHP >= 8.2, Composer 2, MySQL atau MariaDB, Python 3.8+, Node.js opsional. Untuk Windows, Python sebaiknya install dari python.org (bukan dari Microsoft Store) supaya tidak ketemu masalah stub yang aneh saat dipanggil dari PHP.

## Cara Setup

```bash
# 1. Clone & masuk folder
git clone <repo-url> mental-health-prediction
cd mental-health-prediction

# 2. Install dependencies PHP
composer install

# 3. Install dependencies Python
pip install scikit-learn numpy pandas

# 4. Setup environment
cp .env.example .env
php artisan key:generate
```

Lalu edit `.env`. Dua hal yang **wajib** disesuaikan:

```env
DB_DATABASE=mental_health_db          # nama database Anda
DB_USERNAME=root
DB_PASSWORD=

PYTHON_PATH=C:/Users/NAMA/AppData/Local/Programs/Python/Python311/python.exe
```

**Catatan tentang `PYTHON_PATH`**: di Windows wajib pakai path absolut. Jangan tulis `python` saja, sering gagal saat dipanggil dari PHP/XAMPP karena resolusi PATH-nya beda. Cara cek path Python Anda di PowerShell: `where.exe python`. Pakai forward slash atau double backslash di .env.

```bash
# 5. Buat database (lewat phpMyAdmin atau MySQL CLI)
mysql -u root -e "CREATE DATABASE mental_health_db;"

# 6. Migrate
php artisan migrate

# 7. Train model (~ 1-2 menit di subsample 10k baris)
python storage/models/train_models.py

# 8. Seed metrik ke database
php artisan db:seed --class=ModelMetricSeeder

# 9. Jalankan server
php artisan serve
```

Buka http://localhost:8000 di browser.

### Training dengan full dataset

Default training pakai stratified subsample 10.000 baris untuk efisiensi. Untuk pakai full dataset (25.000 baris, SVM jadi beberapa menit):

```bash
# Linux/Mac
MAX_TRAIN_ROWS=25000 python storage/models/train_models.py

# Windows PowerShell
$env:MAX_TRAIN_ROWS="25000"; python storage/models/train_models.py

# Windows cmd
set MAX_TRAIN_ROWS=25000 && python storage/models/train_models.py
```

## Struktur Folder Penting

```
mental-health-prediction/
├── app/Http/Controllers/
│   ├── HomeController.php
│   ├── PredictionController.php       <- Validasi + spawn Python
│   ├── HistoryController.php
│   └── ModelController.php
├── app/Models/
│   ├── Prediction.php                 <- Tabel predictions
│   └── ModelMetric.php                <- Tabel model_metrics
├── database/
│   ├── migrations/
│   │   ├── 2026_04_27_000001_create_predictions_table.php
│   │   └── 2026_04_27_000002_create_model_metrics_table.php
│   └── seeders/ModelMetricSeeder.php  <- Baca train_results.json
├── resources/views/
│   ├── layouts/app.blade.php
│   ├── home, predict, history, model-info, about (.blade.php)
├── routes/web.php
├── storage/models/
│   ├── data/mental_health_risk_dataset.csv  <- Dataset training
│   ├── train_models.py                <- Script training (run sekali)
│   ├── predict.py                     <- Script inferensi (dipanggil tiap prediksi)
│   └── *.pkl                          <- Model artifacts (di-gitignore)
└── .env
```

## Tabel Database

`predictions` — menyimpan setiap hasil prediksi beserta input fitur dalam JSON, prediksi tiap model + confidence, dan hasil majority vote.

`model_metrics` — menyimpan metrik training tiap algoritma. Di-seed dari `storage/models/train_results.json`.

## Performa Model (subsample 10k)

| Algoritma | Accuracy | Precision | Recall | F1-Score |
|-----------|----------|-----------|--------|----------|
| K-Nearest Neighbors (k=7) | ~64% | ~63% | ~64% | ~62% |
| Support Vector Machine (RBF) | ~80% | ~81% | ~80% | ~80% |
| Decision Tree (max_depth=10) | ~97% | ~97% | ~97% | ~97% |

Catatan: Decision Tree mendominasi karena dataset ini punya threshold-rule yang relatif jelas. Untuk evaluasi yang lebih realistis, pertimbangkan training dengan full dataset dan eksperimen dengan `max_depth` yang lebih kecil agar tidak overfit.

## Troubleshooting Khusus Windows

**`WinError 10106 - service provider could not be loaded`** saat prediction. Penyebab: PHP/XAMPP saat spawn Python tidak meneruskan PATH yang berisi `C:\Windows\System32`, sehingga Python gagal load DLL Winsock. **Sudah otomatis di-handle** oleh `PredictionController` yang meng-inject env lengkap saat spawn. Kalau masih muncul, pastikan `PYTHON_PATH` di .env adalah path absolut ke `python.exe` (bukan stub Microsoft Store).

**`'cmd' is not recognized`**. Sama penyebabnya — fix di atas otomatis menyelesaikan ini juga.

**`predict.py` tidak ditemukan**. Pastikan menjalankan `php artisan serve` dari root project, dan file `storage/models/predict.py` ada (file ini di-commit, bukan di-gitignore).

**`No module named 'sklearn'`**. Install: `pip install scikit-learn pandas numpy`. Pastikan pip yang dipakai sama dengan Python yang ditunjuk `PYTHON_PATH`.

**Database migration gagal di MySQL**. Cek `DB_DATABASE` di .env sudah dibuat di MySQL. `php artisan config:clear` setelah edit .env.

## Tim Pengembang

Project akademik oleh:

- **Ahmad Nidzomunnashil** &mdash; NIM 607012400122
- **Vikry Achmad Sonjaya** &mdash; NIM 607012400001
- **Mardini Dwi Putri** &mdash; NIM 607012430015

## Disclaimer

Hasil prediksi sistem ini bersifat **edukatif dan referensi**. Bukan diagnosis medis. Jangan menggantikan konsultasi profesional kesehatan mental.

## Lisensi

MIT. Silakan gunakan, modifikasi, dan distribusikan.
