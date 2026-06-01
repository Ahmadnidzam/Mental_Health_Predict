# Ringkasan Pengembangan — Mental Health Risk Prediction

## Gambaran Umum

Aplikasi web prediksi risiko kesehatan mental berbasis **Laravel 12 + Python ML** dengan 6 pilihan model: KNN, KNN+HPO, SVM, SVM+HPO, Decision Tree, dan DT+HPO. Selama sesi ini, fitur-fitur berikut dikembangkan dari nol hingga siap deploy.

---

## 1. Fitur Login & Register

**File yang dibuat/diubah:**
- `app/Http/Controllers/AuthController.php`
- `resources/views/auth/login.blade.php`
- `resources/views/auth/register.blade.php`
- `routes/web.php`

**Yang diimplementasikan:**
- Register dengan nama, email, password (hash `bcrypt`)
- Login dengan `Auth::attempt()` + remember me
- Logout via POST
- Route group `middleware('guest')` untuk login/register
- Route group `middleware('auth')` untuk semua halaman prediksi & riwayat
- Toggle show/hide password di form

---

## 2. Pemilihan Model Prediksi (6 Model)

**File yang diubah:**
- `storage/models/train_models.py` — rewrite lengkap
- `storage/models/predict.py` — rewrite lengkap
- `app/Http/Controllers/PredictionController.php`
- `resources/views/predict.blade.php`

**Yang diimplementasikan:**
- 6 pilihan model aktif di UI
- `predict.py` memuat artifact sesuai pilihan user
- Encoding sesuai notebook `fix_(2).ipynb`:
  - **Ordinal**: `education_level` (High School=0, Bachelor=1, Master=2, PhD=3)
  - **One-Hot**: `gender`, `marital_status`, `employment_status`
  - Total: **31 fitur** setelah encoding
- Output prediksi: `prediction`, `confidence`, `label`, `model`

---

## 3. Upload CSV (Batch Predict)

**File yang diubah:**
- `resources/views/predict.blade.php` — tab "Upload CSV"
- `app/Http/Controllers/PredictionController.php` — method `predictCsv()`
- `storage/models/predict.py` — batch mode

**Yang diimplementasikan:**
- Tab switcher: Input Manual | Upload CSV
- Deteksi otomatis **header vs tanpa header**:
  - Dengan header: urutan kolom bebas, kolom ekstra (`mental_health_risk`) di-ignore
  - Tanpa header: mapping by position sesuai urutan `FEATURE_FIELDS`
- **Batch predict**: semua baris dikirim ke Python **sekali** (bukan per-baris), menghindari timeout
- Template CSV bisa di-download langsung dari halaman
- Validasi MIME toleran: `text/csv`, `text/plain`, `application/csv`, dll.
- Header `Accept: application/json` wajib pada fetch agar error dikembalikan sebagai JSON

---

## 4. Auto-Retrain Model (Continual Learning)

**File yang dibuat:**
- `app/Jobs/RetrainModelsJob.php`
- `storage/models/retrain_metadata.json`

**File yang diubah:**
- `app/Http/Controllers/PredictionController.php` — method `maybeDispatchRetrain()`
- `storage/models/train_models.py` — merge `user_contributions.csv`

**Yang diimplementasikan:**
- Trigger otomatis setiap **kelipatan 50 prediksi** (configurable via `RETRAIN_EVERY=50` di `.env`)
- Untuk CSV batch: deteksi apakah ada kelipatan 50 yang dilewati (`floor(after/50) > floor(before/50)`)
- `RetrainModelsJob` (async, Laravel Queue):
  1. Ekspor semua prediksi dari DB ke `user_contributions.csv`
  2. Jalankan `train_models.py` via `proc_open`
  3. Update tabel `model_metrics` dari `train_results.json`
  4. Tulis `retrain_metadata.json`
- **Pseudo-labeling**: `final_prediction` digunakan sebagai ground truth label data baru
- Seluruh model yang didefinisikan di `train_models.py` ikut retrain

### Validasi data sebelum merge:
- Cek kelengkapan 25 kolom wajib
- Drop baris dengan target di luar `{0, 1, 2}`
- Drop baris duplikat
- Drop baris dengan NaN

---

## 5. Halaman Beranda (Informasi Dataset Dinamis)

**File yang diubah:**
- `app/Http/Controllers/HomeController.php`
- `resources/views/home.blade.php`

**Yang diimplementasikan:**
- **5 stat cards** dinamis:
  1. Total Records — dari `retrain_metadata.json` (bukan statis 25.000)
  2. Fitur Input — 24
  3. Target Label — 1
  4. Prediksi Tersimpan — live dari `Prediction::count()`
  5. **Data Diretrain** — jumlah data user yang sudah masuk training, hijau jika sudah retrain
- **Accordion 25 fitur** dikelompokkan per kategori:
  - Demografi (5), Gaya Hidup (4), Beban & Stres (5), Kesehatan Psikologis (6), Riwayat Medis (4), Target Label (1)
- Setiap fitur ditampilkan dengan nama kolom, label Indonesia, dan tipe data
- Info preprocessing: Ordinal Encoding, OHE, StandardScaler, 31 kolom final

---

## 6. Perbaikan Bug & Error

| Error | Penyebab | Fix |
|---|---|---|
| `ModuleNotFoundError: No module named 'pandas'` | Python 3.14 tidak punya pandas | `pip install pandas scikit-learn` |
| `Unexpected token '<'` pada CSV upload | Tidak ada header `Accept: application/json` di fetch | Tambah header di JS fetch |
| `mimes:csv,txt` validation gagal | Windows deteksi MIME berbeda | Ganti ke `mimetypes:text/csv,text/plain,...` |
| `Maximum execution time 60s exceeded` | 50 baris CSV = 50 panggilan Python | Batch mode: 1 panggilan untuk semua baris |
| `Environment block size exceeds 32767` | Symfony Process serialisasi seluruh `$_ENV` | Ganti ke `proc_open` native dengan `null` env |
| `UnicodeEncodeError: charmap cp1252` | Emoji `⚠️` `✅` di print Python | Ganti dengan `[WARN]` dan `[OK]` |

---

## 7. Arsitektur Sistem

```
User Submit Prediksi (manual / CSV)
    │
    ▼
PredictionController
    ├── callPythonPredict()  →  proc_open → predict.py
    │       ├── Single mode: { model, features... }
    │       └── Batch mode:  { model, rows: [...] }
    │
    ├── Prediction::create()  →  simpan ke DB
    │
    └── maybeDispatchRetrain()
            └── total % 50 === 0 ?
                    └── RetrainModelsJob::dispatch()
                            │
                            ▼ (async, queue worker)
                    ├── Tulis user_contributions.csv (dari DB)
                    ├── proc_open → train_models.py
                    │       ├── Load dataset asli (25.000 baris)
                    │       ├── Merge user_contributions.csv (validasi + dedup)
                    │       ├── Train KNN, KNN+HPO, SVM, SVM+HPO, DT, DT+HPO
                    │       └── Simpan pkl + retrain_metadata.json
                    └── Update tabel model_metrics
```

---

## 8. File Penting

| File | Fungsi |
|---|---|
| `storage/models/train_models.py` | Pipeline training 6 model |
| `storage/models/predict.py` | Inferensi single & batch |
| `storage/models/retrain_metadata.json` | Tracking riwayat retrain |
| `storage/models/data/user_contributions.csv` | Data prediksi user untuk retrain |
| `storage/models/train_results.json` | Metrik akurasi hasil training terakhir |
| `app/Jobs/RetrainModelsJob.php` | Job async retrain |
| `app/Http/Controllers/PredictionController.php` | Controller prediksi + trigger retrain |
| `app/Http/Controllers/HomeController.php` | Controller beranda + baca metadata |

---

## 9. Yang Perlu Disiapkan Saat Deploy

1. **Queue worker harus selalu jalan** (via Supervisor/systemd):
   ```bash
   php artisan queue:work --timeout=620 --tries=1
   ```
2. **Python + dependencies** terinstall di server:
   ```bash
   pip install pandas scikit-learn numpy
   ```
3. **Set `PYTHON_PATH`** di `.env` jika Python tidak ada di PATH:
   ```
   PYTHON_PATH=/usr/bin/python3
   ```
4. **Folder `storage/models/`** harus writable oleh web server
5. **Jalankan migrasi** setelah MySQL aktif:
   ```bash
   php artisan migrate
   ```

---

## 10. Konfigurasi `.env` Penting

```env
QUEUE_CONNECTION=database
RETRAIN_EVERY=50
PYTHON_PATH=python
```
