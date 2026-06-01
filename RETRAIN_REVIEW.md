# RETRAIN MECHANISM - CURRENT STATE

> Dokumen ringkas. Status sekarang: retrain otomatis sudah aktif, `train_models.py` jadi source of truth, dan pipeline melatih 6 model.

## Yang Sudah Ada

1. `storage/models/train_models.py`
   - Load dataset master + `user_contributions.csv`
   - Validation schema, drop duplicate, drop row invalid
   - Training 6 model: KNN, KNN+HPO, SVM, SVM+HPO, DT, DT+HPO
   - Simpan artifact `.pkl`, `train_results.json`, `retrain_metadata.json`

2. `storage/models/predict.py`
   - Load model sesuai `model` dari payload
   - Support single dan batch mode
   - Preprocessing konsisten dengan training

3. `app/Http/Controllers/PredictionController.php`
   - Validasi model selection
   - Submit prediksi manual dan CSV
   - Trigger retrain saat total prediksi melewati kelipatan `RETRAIN_EVERY`

4. `app/Jobs/RetrainModelsJob.php`
   - Export semua prediksi ke `user_contributions.csv`
   - Jalankan `train_models.py`
   - Update `model_metrics`

5. `storage/models/retrain_metadata.json`
   - Simpan timestamp retrain terakhir
   - Simpan ukuran dataset terakhir
   - Simpan jumlah data user yang dipakai
   - Simpan metrik training terakhir

## Alur Retrain

```text
User submit prediksi
    -> PredictionController simpan prediction
    -> jika total % RETRAIN_EVERY == 0
    -> RetrainModelsJob jalan async
    -> export user_contributions.csv
    -> train_models.py train ulang 6 model
    -> update model_metrics + retrain_metadata.json
```

## Catatan Penting

- `train_models.py` adalah source of truth.
- `predict.py` harus selalu sinkron dengan nama artifact di `train_models.py`.
- File `user_contributions.csv` bersifat generated dan tidak perlu di-commit.
- Kalau tambah model baru, update 3 tempat sekaligus: `train_models.py`, `predict.py`, dan UI selection di `resources/views/predict.blade.php`.

