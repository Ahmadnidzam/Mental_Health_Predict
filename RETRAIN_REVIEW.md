# RETRAIN MECHANISM - COMPREHENSIVE REVIEW

## 📋 CURRENT STATE ANALYSIS

### ✅ Apa yang Sudah Ada:

1. **train_models.py** (storage/models/train_models.py)
   - Load dataset + user_contributions.csv (jika ada)
   - Training pipeline untuk 2 model: SVM & SVM+HPO
   - Menyimpan model ke pickle files (svm_model.pkl, svm_hpo_model.pkl)
   - Scaler & feature columns disimpan

2. **predict.py** (storage/models/predict.py)
   - Load model dari pickle
   - Preprocessing input dengan encoding yang benar
   - Return prediction + confidence

3. **Notebook** (notebooks/fix_(2).ipynb)
   - KNN, SVM, Decision Tree training
   - StandardScaler preprocessing
   - Feature encoding (ordinal + OHE)
   - 31 fitur yang konsisten

4. **Data Structure**
   - Dataset master: storage/models/data/mental_health_risk_dataset.csv
   - User contributions: storage/models/data/user_contributions.csv (tidak ada saat ini)

### ❌ MASALAH DITEMUKAN:

#### 1. **TIDAK ADA RETRAIN TRIGGER LOGIC**
```
Rule: "Setiap kali total user input = 50 atau kelipatan 50, trigger retrain"
Status: ❌ NOT IMPLEMENTED

Yang hilang:
- Fungsi untuk tracking total user input
- Logika pengecekan kelipatan 50
- Kondisional trigger untuk memanggil retrain
- Metadata tracking (kapan terakhir retrain, berapa data terakhir)
```

#### 2. **MISMATCH ANTARA NOTEBOOK & TRAIN_MODELS.PY**
```
Notebook (fix_(2).ipynb):
- Train 3 model: KNN, SVM, DT
- Tidak menyimpan model ke pickle
- Berjalan di Colab dengan data dari Google Drive

train_models.py:
- Train hanya 2 model: SVM, SVM+HPO
- Menyimpan ke pickle files
- Berjalan offline dengan CSV lokal
- KNN & DT di-comment out

⚠️ INCONSISTENCY: Kedua file train model yang berbeda!
```

#### 3. **USER CONTRIBUTIONS DATA FLOW**
```
Alur yang diharapkan:
1. User input data baru
2. Simpan ke user_contributions.csv
3. Hitung total input
4. Jika total % 50 == 0 → trigger retrain
5. Retrain gabung: dataset master + user contributions
6. Update model files
7. Reset atau track input count

Status: ❌ STEP 2-7 TIDAK DIIMPLEMENTASIKAN
```

#### 4. **TRAINING CONSISTENCY ISSUE**
```
train_models.py Line 113-120:
    df_user = pd.read_csv(user_data_path)
    df = pd.concat([df, df_user], ignore_index=True)
    
✅ BAIK: Merging user data dengan dataset master
❌ MASALAH: Tidak ada pengecekan schema compatibility
          Tidak ada validation kolom
          Tidak ada deduplication
```

#### 5. **MODEL SELECTION MISMATCH**
```
train_models.py saat ini:
- SVM (non-HPO): ✅ Working
- SVM+HPO: ✅ Working
- KNN & DT: ❌ Commented out

Notebook:
- KNN: ✅ Working (akurasi 65.40%)
- SVM: ✅ Working (akurasi 79.37%)
- DT: ✅ Working (akurasi 98.17%)

⚠️ PROBLEM: Notebook melatih 3 model tapi train_models.py hanya 2
            Tidak ada konsistensi model selection
```

#### 6. **MISSING: RETRAIN METADATA TRACKING**
```
Tidak ada file untuk tracking:
- Berapa total user input saat ini
- Kapan terakhir kali retrain dilakukan
- Berapa data yang digunakan saat retrain terakhir
- Training statistics (akurasi, precision, recall)

Seharusnya ada file seperti: 
- storage/models/retrain_metadata.json
```

---

## 🔄 CURRENT RETRAIN FLOW (BROKEN)

```
User Input Data
    ↓
[MISSING] Hitung total input
    ↓
[MISSING] Check if total % 50 == 0?
    ↓
[MISSING] Trigger retrain function
    ↓
train_models.py (run manually)
    ├─ Load master dataset
    ├─ Load user_contributions.csv (jika ada)
    ├─ Merge data
    ├─ Train models
    ├─ Save model files ✅
    └─ Train results
    
[MISSING] Update metadata
[MISSING] Reset input counter
```

### Problems di setiap tahap:

1. **Data Input**: Tidak ada mekanisme to collect & count user inputs
2. **Trigger Logic**: Tidak ada pengecekan kelipatan 50
3. **Training Execution**: Must be manual run Python script
4. **Model Update**: Models update tapi tidak ada versioning
5. **Metadata**: Tidak track apa yang terjadi

---

## 📊 WHAT NEEDS TO BE DONE

### Priority 1: CREATE RETRAIN TRIGGER SYSTEM
```python
# File: storage/models/retrain_utils.py (BARU)
1. Function: count_user_inputs()
   - Read user_contributions.csv
   - Return total row count
   
2. Function: should_retrain(current_total)
   - Check if current_total % 50 == 0
   - Return True/False
   
3. Function: trigger_retrain_if_needed()
   - Get current count
   - Check should_retrain()
   - If yes → call retrain_models()
   - Log metadata
```

### Priority 2: SYNCHRONIZE MODELS
```
Pilihan:
A. Keep train_models.py simple (SVM only)
   - Uncomment KNN & DT if needed
   - Ensure feature columns match

B. Update notebook to match train_models.py
   - Modify notebook to only train SVM
   - Save models to pickle files
   - Run from command line

Rekomendasi: ✅ OPTION A (keep train_models.py as source of truth)
```

### Priority 3: RETRAIN METADATA TRACKING
```python
# File: storage/models/retrain_metadata.json (BARU)
{
    "last_retrain_timestamp": "2026-05-25T10:30:00Z",
    "last_retrain_dataset_size": 25050,
    "total_user_inputs_processed": 50,
    "models_trained": ["svm", "svm_hpo"],
    "training_metrics": {
        "svm": {
            "accuracy": 0.7937,
            "precision": 0.79,
            "recall": 0.77,
            "f1_score": 0.78
        }
    },
    "retrains_count": 1
}
```

### Priority 4: USER CONTRIBUTIONS DATA VALIDATION
```python
# In train_models.py, add validation:
1. Check schema compatibility
2. Validate numeric ranges (age, scores, etc.)
3. Remove duplicates if any
4. Ensure all required columns exist
```

---

## 🚨 CRITICAL ISSUES TO FIX

### Issue #1: Missing Retrain Trigger
- **Impact**: Model tidak pernah di-retrain otomatis
- **Fix**: Create retrain_utils.py dengan trigger logic
- **Time**: ~30 menit

### Issue #2: Notebook vs train_models.py Mismatch
- **Impact**: Uncertainty mana file yang authoritative
- **Fix**: Ensure train_models.py is the single source of truth
- **Time**: ~15 menit

### Issue #3: No Data Validation
- **Impact**: Corrupted data could break training
- **Fix**: Add validation in train_models.py before training
- **Time**: ~20 menit

### Issue #4: No Training History
- **Impact**: Cannot track model improvement or debug failures
- **Fix**: Create metadata tracking system
- **Time**: ~25 menit

---

## 🎯 IMPLEMENTATION CHECKLIST

- [ ] Create storage/models/retrain_utils.py
  - [ ] count_user_inputs()
  - [ ] should_retrain()
  - [ ] trigger_retrain_if_needed()
  - [ ] save_retrain_metadata()

- [ ] Enhance train_models.py
  - [ ] Add data validation
  - [ ] Add metadata logging
  - [ ] Ensure KNN & DT properly commented/documented
  - [ ] Test with sample user data

- [ ] Create retrain_metadata.json template
  - [ ] Initialize with baseline

- [ ] Update notebook (fix_(2).ipynb)
  - [ ] Add comment clarifying it's reference only
  - [ ] Ensure feature order matches train_models.py

- [ ] Integration testing
  - [ ] Test retrain trigger with 50 inputs
  - [ ] Verify model files update
  - [ ] Check metadata is recorded

---

## 📝 RECOMMENDED EXECUTION ORDER

1. **STEP 1**: Create retrain_utils.py dengan core logic
2. **STEP 2**: Enhance train_models.py dengan validation & metadata
3. **STEP 3**: Create metadata json template
4. **STEP 4**: Test dengan sample data (50 rows)
5. **STEP 5**: Verify end-to-end flow
6. **STEP 6**: Document in CLAUDE.md

Total estimated time: ~2 hours
