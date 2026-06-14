# Panduan Use Case Diagram — Mental Health Predict

Panduan ini menjabarkan **aktor**, **use case**, dan **relasi** aplikasi prediksi
kesehatan mental, lengkap dengan kode siap-render (Mermaid + PlantUML) untuk
menggambar diagramnya. Semua use case di bawah diturunkan langsung dari
`routes/web.php` dan controller terkait, jadi cocok dengan perilaku aplikasi nyata.

> **Notasi UML klasik (mengikuti contoh `exmp_ucdiagram.png` di folder ini):**
> - **Aktor** = **stick figure (orang lidi)**, ditaruh di **tepi** — aktor utama di
>   **kiri**, aktor pendukung/eksternal di **kanan** (mirip `Bank` di contoh).
> - **Use case** = **oval/elips**, diletakkan **di dalam kotak batas sistem**
>   (system boundary) yang diberi label stereotype **`«subsystem»`** + nama sistem.
> - **Asosiasi** aktor↔use case = **garis lurus polos** (tanpa panah).
> - Relasi `«include»`, `«extend»`, dan generalization (segitiga) adalah penyempurnaan
>   opsional — tidak tampil di contoh sederhana, tapi disertakan di Bagian 5 untuk
>   versi detail.

---

## 1. Aktor

| Aktor | Tipe | Deskripsi |
|-------|------|-----------|
| **Guest (Tamu)** | Primary | Pengunjung belum login. Hanya akses halaman publik + auth. |
| **User (Pengguna)** | Primary | Sudah login. Bisa melakukan prediksi dan lihat riwayat. Mewarisi semua akses Guest. |
| **Admin** | Primary | User dengan `is_admin = true`. Mewarisi semua akses User + mengelola pengguna, data, dan lifecycle model lewat panel `/admin`. |
| **ML Engine (Python)** | Secondary / Supporting | Proses Python (`predict.py`) yang menjalankan model **versi aktif** & mengembalikan hasil prediksi. |
| **Retrain Job** | Secondary / Supporting | `RetrainModelsJob` yang melatih ulang model otomatis (serial, anti-overlap) saat ambang prediksi tercapai, menghasilkan **versi pending**. |

> Catatan generalization: **User** mewarisi semua use case **Guest**; **Admin**
> mewarisi semua use case **User**. Jadi rantainya `Admin ⟶ User ⟶ Guest`.

---

## 2. Daftar Use Case

### Akses publik (Guest, User, Admin)
| ID | Use Case | Sumber |
|----|----------|--------|
| UC-01 | Lihat Beranda (statistik dinamis) | `GET /` → `HomeController@index` |
| UC-02 | Lihat Info Model & Metrik (model aktif) | `GET /models` → `ModelController@index` |
| UC-03 | Lihat Halaman About | `GET /about` |
| UC-04 | Registrasi Akun | `GET/POST /register` → `AuthController@register` |
| UC-05 | Login | `GET/POST /login` → `AuthController@login` |

### Khusus User login (& Admin)
| ID | Use Case | Sumber |
|----|----------|--------|
| UC-06 | Logout | `POST /logout` → `AuthController@logout` |
| UC-07 | Prediksi Manual (1 data) | `POST /predict` → `PredictionController@predict` |
| UC-08 | Prediksi Batch via CSV | `POST /predict/csv` → `PredictionController@predictCsv` |
| UC-09 | Lihat Detail Prediksi | `GET /predict/{id}` → `PredictionController@show` |
| UC-10 | Lihat Riwayat Prediksi | `GET /history` → `HistoryController@index` |

### Khusus Admin (panel `/admin`, middleware `auth` + `admin`)
| ID | Use Case | Sumber |
|----|----------|--------|
| UC-16 | Lihat Dashboard Admin | `GET /admin` → `Admin\DashboardController@index` |
| UC-17 | Kelola Pengguna (daftar) | `GET /admin/users` → `Admin\UserController@index` |
| UC-18 | Lihat Detail & Riwayat Prediksi per User | `GET /admin/users/{user}` → `Admin\UserController@show` |
| UC-19 | Toggle Hak Admin Pengguna | `POST /admin/users/{user}/toggle-admin` → `Admin\UserController@toggleAdmin` |
| UC-20 | Lihat Semua Prediksi (filter per user) | `GET /admin/predictions` → `Admin\PredictionController@index` |
| UC-21 | Kontrol Model (lihat aktif + pending) | `GET /admin/models` → `Admin\ModelVersionController@index` |
| UC-22 | Approve Versi Model (jadikan aktif) | `POST /admin/models/{v}/approve` → `Admin\ModelVersionController@approve` |
| UC-23 | Reject Versi Model (hapus artifact) | `POST /admin/models/{v}/reject` → `Admin\ModelVersionController@reject` |
| UC-24 | Export CSV Total Records | `GET /admin/data/export` → `Admin\DataExportController@export` |

### Use case sistem / CLI
| ID | Use Case | Sumber |
|----|----------|--------|
| UC-25 | Bootstrap Model Base (versi #1 aktif) | CLI `php artisan models:seed-base` → `SeedBaseModel` |

### Use case pendukung (include/extend)
| ID | Use Case | Relasi |
|----|----------|--------|
| UC-11 | Validasi Input Fitur (24 fitur) | `<<include>>` dari UC-07, UC-08 |
| UC-12 | Jalankan Model ML (versi aktif) | `<<include>>` dari UC-07, UC-08 (dilayani ML Engine) |
| UC-13 | Parse & Deteksi Header CSV | `<<include>>` dari UC-08 |
| UC-14 | Simpan Hasil Prediksi | `<<include>>` dari UC-07, UC-08 |
| UC-15 | Auto-Retrain Model (→ versi pending) | `<<extend>>` UC-14 (saat kelipatan ambang tercapai) |

---

## 3. Relasi Penting

- **Generalization:** `Admin` ⟶ `User` ⟶ `Guest` (admin adalah user, user adalah guest yang login).
- **Include (wajib):**
  - UC-07 Prediksi Manual **include** UC-11 Validasi, UC-12 Jalankan Model, UC-14 Simpan.
  - UC-08 Prediksi CSV **include** UC-13 Parse CSV, UC-11 Validasi, UC-12 Jalankan Model, UC-14 Simpan.
- **Extend (opsional/bersyarat):**
  - UC-15 Auto-Retrain **extend** UC-14 — terpicu hanya saat
    `total prediksi % services.retrain.every == 0` (default tiap 50 prediksi).
    Hasilnya **versi `pending`**, bukan langsung dipakai.
- **Governance model (Admin):**
  - UC-15 (Retrain Job) & UC-25 (CLI bootstrap) **menghasilkan** `ModelVersion`.
  - UC-22 Approve mengubah versi `pending` → `active` (versi aktif lama → `archived`),
    memindahkan pointer `active_version.json`, dan sinkron `model_metrics`.
  - UC-23 Reject menghapus artifact versi & set status `rejected`.
  - UC-12 selalu memuat **versi aktif** — jadi hasil retrain tak dipakai sebelum di-approve.
- **Secondary actor:**
  - UC-12 dijalankan oleh **ML Engine (Python)**.
  - UC-15 dieksekusi oleh **Retrain Job**.

---

## 4. Kode Mermaid (render langsung di GitHub / VS Code)

Mermaid tidak punya notasi use-case resmi; di bawah pakai pendekatan graph yang
mendekati. Untuk diagram UML formal, pakai PlantUML di Bagian 5.

```mermaid
flowchart LR
    Guest([Guest])
    User([User])
    Admin([Admin])
    ML([ML Engine - Python])
    Job([Retrain Job])

    User -. generalization .-> Guest
    Admin -. generalization .-> User

    subgraph Publik
        UC01[Lihat Beranda]
        UC02[Lihat Info Model]
        UC03[Lihat About]
        UC04[Registrasi]
        UC05[Login]
    end

    subgraph User_Login[Khusus User]
        UC06[Logout]
        UC07[Prediksi Manual]
        UC08[Prediksi Batch CSV]
        UC09[Lihat Detail Prediksi]
        UC10[Lihat Riwayat]
    end

    subgraph Admin_Panel[Khusus Admin]
        UC16[Dashboard Admin]
        UC17[Kelola Pengguna]
        UC18[Detail/Riwayat per User]
        UC19[Toggle Hak Admin]
        UC20[Semua Prediksi]
        UC21[Kontrol Model]
        UC22[Approve Versi]
        UC23[Reject Versi]
        UC24[Export CSV Total]
    end

    subgraph Pendukung
        UC11[Validasi Input]
        UC12[Jalankan Model ML]
        UC13[Parse CSV]
        UC14[Simpan Hasil]
        UC15[Auto-Retrain -> pending]
        UC25[Bootstrap Base CLI]
    end

    Guest --> UC01 & UC02 & UC03 & UC04 & UC05
    User --> UC06 & UC07 & UC08 & UC09 & UC10
    Admin --> UC16 & UC17 & UC18 & UC19 & UC20 & UC21 & UC22 & UC23 & UC24

    UC07 -->|include| UC11
    UC07 -->|include| UC12
    UC07 -->|include| UC14
    UC08 -->|include| UC13
    UC08 -->|include| UC11
    UC08 -->|include| UC12
    UC08 -->|include| UC14
    UC14 -.->|extend| UC15

    UC12 --- ML
    UC15 --- Job
    UC15 -->|buat versi| UC21
    UC25 -->|buat versi| UC21
```

---

## 5. Kode PlantUML (UML use case formal — disarankan)

Render via plugin PlantUML (VS Code) atau https://www.plantuml.com/plantuml.

```plantuml
@startuml
left to right direction
skinparam packageStyle rectangle
skinparam actorStyle awesome

actor "Guest\n(Tamu)" as Guest
actor "User\n(Pengguna)" as User
actor "Admin" as Admin
actor "ML Engine\n(Python)" as ML
actor "Retrain Job\n(Scheduler)" as Job

User --|> Guest
Admin --|> User

rectangle "<<subsystem>>\nAplikasi Mental Health Predict" {
    ' --- Publik ---
    usecase "Lihat Beranda" as UC01
    usecase "Lihat Info Model & Metrik" as UC02
    usecase "Lihat About" as UC03
    usecase "Registrasi Akun" as UC04
    usecase "Login" as UC05

    ' --- User login ---
    usecase "Logout" as UC06
    usecase "Prediksi Manual" as UC07
    usecase "Prediksi Batch CSV" as UC08
    usecase "Lihat Detail Prediksi" as UC09
    usecase "Lihat Riwayat Prediksi" as UC10

    ' --- Admin ---
    usecase "Dashboard Admin" as UC16
    usecase "Kelola Pengguna" as UC17
    usecase "Detail & Riwayat per User" as UC18
    usecase "Toggle Hak Admin" as UC19
    usecase "Lihat Semua Prediksi" as UC20
    usecase "Kontrol Model" as UC21
    usecase "Approve Versi Model" as UC22
    usecase "Reject Versi Model" as UC23
    usecase "Export CSV Total Records" as UC24

    ' --- Pendukung / sistem ---
    usecase "Validasi Input Fitur" as UC11
    usecase "Jalankan Model ML (versi aktif)" as UC12
    usecase "Parse & Deteksi Header CSV" as UC13
    usecase "Simpan Hasil Prediksi" as UC14
    usecase "Auto-Retrain Model (pending)" as UC15
    usecase "Bootstrap Model Base (CLI)" as UC25
}

' Asosiasi Guest
Guest -- UC01
Guest -- UC02
Guest -- UC03
Guest -- UC04
Guest -- UC05

' Asosiasi User
User -- UC06
User -- UC07
User -- UC08
User -- UC09
User -- UC10

' Asosiasi Admin
Admin -- UC16
Admin -- UC17
Admin -- UC18
Admin -- UC19
Admin -- UC20
Admin -- UC21
Admin -- UC22
Admin -- UC23
Admin -- UC24

' Include
UC07 ..> UC11 : <<include>>
UC07 ..> UC12 : <<include>>
UC07 ..> UC14 : <<include>>
UC08 ..> UC13 : <<include>>
UC08 ..> UC11 : <<include>>
UC08 ..> UC12 : <<include>>
UC08 ..> UC14 : <<include>>

' Extend
UC15 ..> UC14 : <<extend>>

' Secondary actor
UC12 -- ML
UC15 -- Job

@enduml
```

---

## 6. Cara Menggambar Manual (draw.io / Lucidchart) — sesuai `exmp_ucdiagram.png`

Wajib (sama seperti contoh):
1. **Kotak batas sistem** (system boundary) di tengah, beri label stereotype
   **`«subsystem»`** + nama sistem ("Aplikasi Mental Health Predict"). Semua use case
   ada **di dalam** kotak ini.
2. **Use case = oval/elips** di dalam boundary (UC-01 … UC-25), susun vertikal.
3. **Aktor = stick figure (orang lidi)** di tepi:
   - **Kiri**: aktor utama `Guest`, `User`, `Admin`.
   - **Kanan**: aktor pendukung/eksternal `ML Engine (Python)`, `Retrain Job`
     (mirip posisi `Bank` di contoh).
4. **Asosiasi = garis lurus polos** (tanpa panah) dari aktor ke tiap use case yang
   diaksesnya.

Penyempurnaan opsional (TIDAK ada di contoh sederhana — pakai bila perlu versi detail):
5. **Panah putus-putus `«include»`** dari use case utama ke use case wajib
   (UC-07/08 → UC-11/12/14).
6. **Panah putus-putus `«extend»`** dari use case opsional ke yang diperluas
   (UC-15 → UC-14).
7. **Segitiga kosong (generalization)** dari `Admin → User → Guest`.
8. Kelompokkan use case Admin (UC-16..UC-24) dalam blok terpisah agar batas peran jelas.

---

## 7. Ringkasan Alur Inti (untuk narasi laporan)

> Pengguna mendaftar/login, mengisi form 24 fitur kesehatan mental dan memilih
> model (KNN, SVM, atau Decision Tree — versi base maupun HPO). Sistem memvalidasi
> input, memanggil **ML Engine (Python)** yang memuat **model versi aktif** untuk
> menjalankan prediksi, menyimpan hasil beserta confidence, lalu menampilkannya di
> riwayat. Pengguna juga bisa mengunggah CSV untuk prediksi massal.
>
> Setiap kelipatan ambang tertentu (default 50 prediksi), **Retrain Job** otomatis
> melatih ulang model di latar belakang secara **serial (anti-overlap)** dan
> menghasilkan **versi model `pending`** — bukan langsung dipakai. **Admin** membuka
> panel Kontrol Model untuk membandingkan akurasi tiap versi pending terhadap model
> aktif (indikator naik/turun per algoritma), lalu **approve** versi yang lebih baik
> (menjadikannya aktif + sinkron metrik publik) atau **reject** (hapus artifact).
> Dengan begitu akurasi model yang dipakai selalu terkontrol. Admin juga mengelola
> pengguna (termasuk memberi/cabut hak admin), meninjau riwayat prediksi per
> pengguna, dan mengekspor CSV total records (dataset awal + kontribusi user terlatih).
> Saat pertama kali deploy, model base diinisialisasi via CLI `php artisan models:seed-base`.
