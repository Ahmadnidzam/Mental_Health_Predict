"""Training Script untuk Mental Health Risk Prediction.

Cara menjalankan dari root project Laravel:
    python storage/models/train_models.py

Encoding sesuai notebooks/fix_(2).ipynb:
  - education_level  : ordinal mapping (High School=0, Bachelor=1, Master=2, PhD=3)
  - gender           : one-hot encoding  -> gender_Female, gender_Male, gender_Other
  - marital_status   : one-hot encoding  -> marital_status_Divorced, _Married, _Single
  - employment_status: one-hot encoding  -> employment_status_Employed, _Self-Employed, _Student, _Unemployed
  - Total fitur X    : 31 kolom

Models yang dilatih (6 total):
  1. KNN         : KNeighborsClassifier(n_neighbors=21, metric='manhattan')
  2. KNN+HPO     : GridSearchCV over n_neighbors=[1,3,...,99] x metric=[euclidean,manhattan]
  3. SVM         : SVC(kernel='rbf', C=10, gamma=0.01, probability=True)
  4. SVM+HPO     : GridSearchCV({'C':[10],'gamma':[0.01],'kernel':['rbf']}, cv=3) best_estimator_
  5. DT          : DecisionTreeClassifier(random_state=0)  [default, tidak di-tune]
  6. DT+HPO      : GridSearchCV(criterion,max_depth,min_samples_split,min_samples_leaf, cv=5) best_estimator_
"""
from __future__ import annotations

import json
import os
import pickle
from pathlib import Path

import numpy as np
import pandas as pd
from sklearn.metrics import (
    accuracy_score,
    f1_score,
    precision_score,
    recall_score,
    confusion_matrix,
)
from sklearn.model_selection import GridSearchCV, train_test_split
from sklearn.neighbors import KNeighborsClassifier
from sklearn.preprocessing import StandardScaler
from sklearn.svm import SVC
from sklearn.tree import DecisionTreeClassifier

SCRIPT_DIR = Path(__file__).resolve().parent
DATA_PATH = SCRIPT_DIR / "data" / "mental_health_risk_dataset.csv"

SPLIT_RANDOM_STATE = 0
MODEL_RANDOM_STATE = 0

MAX_TRAIN_ROWS = int(os.environ.get("MAX_TRAIN_ROWS", "0"))

TARGET_COL = "mental_health_risk"

EDUCATION_MAP = {"High School": 0, "Bachelor": 1, "Master": 2, "PhD": 3}
OHE_COLS = ["gender", "marital_status", "employment_status"]

# Urutan fitur X setelah encoding (identik dengan notebook)
FEATURE_COLUMNS = [
    "age", "education_level", "sleep_hours", "physical_activity_hours_per_week",
    "screen_time_hours_per_day", "social_support_score", "work_stress_level",
    "academic_pressure_level", "job_satisfaction_score", "financial_stress_level",
    "working_hours_per_week", "anxiety_score", "depression_score", "stress_level",
    "mood_swings_frequency", "concentration_difficulty_level", "panic_attack_history",
    "family_history_mental_illness", "previous_mental_health_diagnosis",
    "therapy_history", "substance_use",
    # OHE gender
    "gender_Female", "gender_Male", "gender_Other",
    # OHE marital_status
    "marital_status_Divorced", "marital_status_Married", "marital_status_Single",
    # OHE employment_status
    "employment_status_Employed", "employment_status_Self-Employed",
    "employment_status_Student", "employment_status_Unemployed",
]


def _save(obj, filename: str) -> None:
    with open(SCRIPT_DIR / filename, "wb") as fh:
        pickle.dump(obj, fh, protocol=pickle.HIGHEST_PROTOCOL)
    print(f"      Saved: {filename}")


def _eval(name: str, model, X_test, y_test) -> dict:
    y_pred = model.predict(X_test)
    acc  = accuracy_score(y_test, y_pred)
    prec = precision_score(y_test, y_pred, average="weighted", zero_division=0)
    rec  = recall_score(y_test, y_pred, average="weighted", zero_division=0)
    f1   = f1_score(y_test, y_pred, average="weighted", zero_division=0)
    print(f"\n      [{name}]")
    print(f"        Accuracy : {acc:.4f}")
    print(f"        Precision: {prec:.4f}")
    print(f"        Recall   : {rec:.4f}")
    print(f"        F1-Score : {f1:.4f}")
    print("        Confusion matrix:")
    print(np.array2string(confusion_matrix(y_test, y_pred), prefix="        "))
    return {"accuracy": float(acc), "precision": float(prec),
            "recall": float(rec), "f1_score": float(f1)}


def main() -> None:
    print("=" * 70)
    print("MENTAL HEALTH RISK PREDICTION - TRAINING PIPELINE")
    print("Encoding: ordinal(education) + OHE(gender,marital,employment) = 31 fitur")
    print("=" * 70)

    if not DATA_PATH.exists():
        raise FileNotFoundError(f"Dataset tidak ditemukan di {DATA_PATH}")

    # ----- [1] Load -----
    print(f"\n[1/7] Loading dataset: {DATA_PATH}")
    df = pd.read_csv(DATA_PATH)
    print(f"      Shape (original): {df.shape}")

    # Gabungkan data kontribusi user jika ada
    user_data_path = SCRIPT_DIR / "data" / "user_contributions.csv"
    user_rows_added = 0
    if user_data_path.exists() and user_data_path.stat().st_size > 0:
        df_user = pd.read_csv(user_data_path)
        print(f"      + user_contributions.csv: {len(df_user)} baris (sebelum validasi)")

        # Validasi: pastikan semua kolom yang dibutuhkan ada
        required_raw_cols = [
            "age", "gender", "marital_status", "education_level", "employment_status",
            "sleep_hours", "physical_activity_hours_per_week", "screen_time_hours_per_day",
            "social_support_score", "work_stress_level", "academic_pressure_level",
            "job_satisfaction_score", "financial_stress_level", "working_hours_per_week",
            "anxiety_score", "depression_score", "stress_level", "mood_swings_frequency",
            "concentration_difficulty_level", "panic_attack_history",
            "family_history_mental_illness", "previous_mental_health_diagnosis",
            "therapy_history", "substance_use", TARGET_COL,
        ]
        missing_cols = [c for c in required_raw_cols if c not in df_user.columns]
        if missing_cols:
            print(f"      ⚠️  SKIP user_contributions.csv — kolom tidak lengkap: {missing_cols}")
        else:
            # Drop baris dengan target null atau nilai tidak valid (0/1/2)
            before_drop = len(df_user)
            df_user = df_user[df_user[TARGET_COL].isin([0, 1, 2])].reset_index(drop=True)
            dropped = before_drop - len(df_user)
            if dropped:
                print(f"      ⚠️  {dropped} baris dibuang (target tidak valid)")

            # Drop duplikat
            before_dedup = len(df_user)
            df_user = df_user.drop_duplicates().reset_index(drop=True)
            deduped = before_dedup - len(df_user)
            if deduped:
                print(f"      ⚠️  {deduped} baris duplikat dibuang")

            user_rows_added = len(df_user)
            print(f"      ✅ {user_rows_added} baris valid akan digabung")
            df = pd.concat([df, df_user], ignore_index=True)
            print(f"      Shape setelah merge    : {df.shape}")
    else:
        print(f"      (Tidak ada user_contributions.csv, pakai dataset asli)")

    print(f"      Missing values: {int(df.isnull().sum().sum())}")
    if df.isnull().sum().sum() > 0:
        before_clean = len(df)
        df = df.dropna().reset_index(drop=True)
        print(f"      Dropped {before_clean - len(df)} baris dengan NaN")

    # ----- [2] Encoding -----
    print("\n[2/7] Encoding fitur kategorik")
    df_enc = df.copy()

    # Ordinal untuk education_level
    df_enc["education_level"] = df_enc["education_level"].map(EDUCATION_MAP)
    print(f"      education_level (ordinal): {EDUCATION_MAP}")

    # One-hot untuk gender, marital_status, employment_status
    df_enc = pd.get_dummies(df_enc, columns=OHE_COLS, dtype=int)
    ohe_result = [c for c in df_enc.columns if any(c.startswith(k + "_") for k in OHE_COLS)]
    print(f"      OHE columns: {ohe_result}")

    # ----- [3] X, y -----
    print("\n[3/7] Membentuk X dan y")
    X = df_enc[FEATURE_COLUMNS]
    y = df_enc[TARGET_COL]
    print(f"      Shape X: {X.shape}  (harus 31 fitur)")
    print(f"      Shape y: {y.shape}")
    assert X.shape[1] == 31, f"Jumlah fitur harus 31, dapat {X.shape[1]}"

    # ----- [4] Split -----
    print("\n[4/7] Train-Test Split (70:30, stratified)")
    if MAX_TRAIN_ROWS and len(df_enc) > MAX_TRAIN_ROWS:
        X, _, y, _ = train_test_split(
            X, y, train_size=MAX_TRAIN_ROWS,
            random_state=SPLIT_RANDOM_STATE, stratify=y,
        )
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.3, random_state=SPLIT_RANDOM_STATE, stratify=y
    )
    print(f"      Train: {X_train.shape}  Test: {X_test.shape}")

    # ----- [5] Scaler -----
    print("\n[5/7] StandardScaler (fit on train only)")
    scaler = StandardScaler()
    X_train_s = scaler.fit_transform(X_train)
    X_test_s  = scaler.transform(X_test)

    # ----- [6] Training 6 model -----
    print("\n[6/7] Training 6 model...")
    metrics = {}

    # --- KNN (non-HPO) ---
    # print("\n  >> KNN (non-HPO): n_neighbors=21, metric=manhattan")
    # knn = KNeighborsClassifier(n_neighbors=21, metric="manhattan")
    # knn.fit(X_train_s, y_train)
    # metrics["KNN"] = _eval("KNN", knn, X_test_s, y_test)

    # --- KNN + HPO ---
    # print("\n  >> KNN+HPO: GridSearchCV (n_neighbors=1..99 odd x metric, cv=5)")
    # param_grid_knn = {
    #     "n_neighbors": list(range(1, 100, 2)),   # [1,3,...,99] = 50 nilai
    #     "metric": ["euclidean", "manhattan"],     # 2 nilai -> 100 kandidat
    # }
    # grid_knn = GridSearchCV(
    #     KNeighborsClassifier(), param_grid_knn,
    #     scoring="recall_macro", cv=5, refit=True, n_jobs=-1,
    # )
    # grid_knn.fit(X_train_s, y_train)
    # knn_hpo = grid_knn.best_estimator_
    # print(f"      Best params KNN+HPO: {grid_knn.best_params_}")
    # metrics["KNN+HPO"] = _eval("KNN+HPO", knn_hpo, X_test_s, y_test)

    # --- SVM (non-HPO) ---
    print("\n  >> SVM (non-HPO): kernel=rbf, C=10, gamma=0.01")
    svm = SVC(kernel="rbf", C=10, gamma=0.01, random_state=MODEL_RANDOM_STATE, probability=True)
    svm.fit(X_train_s, y_train)
    metrics["SVM"] = _eval("SVM", svm, X_test_s, y_test)

    # --- SVM + HPO ---
    print("\n  >> SVM+HPO: GridSearchCV({'C':[10],'gamma':[0.01],'kernel':['rbf']}, cv=3)")
    param_grid_svm = [{"C": [10], "gamma": [0.01], "kernel": ["rbf"]}]
    grid_svm = GridSearchCV(
        SVC(probability=True), param_grid_svm,
        scoring="recall_macro", cv=3, refit=True,
    )
    grid_svm.fit(X_train_s, y_train)
    svm_hpo = grid_svm.best_estimator_
    print(f"      Best params SVM+HPO: {grid_svm.best_params_}")
    metrics["SVM+HPO"] = _eval("SVM+HPO", svm_hpo, X_test_s, y_test)

    # --- DT (non-HPO) ---
    # print("\n  >> DT (non-HPO): default params")
    # dt = DecisionTreeClassifier(random_state=MODEL_RANDOM_STATE)
    # dt.fit(X_train_s, y_train)
    # metrics["DT"] = _eval("DT", dt, X_test_s, y_test)

    # # --- DT + HPO ---
    # print("\n  >> DT+HPO: GridSearchCV(criterion,max_depth,min_samples_split,min_samples_leaf, cv=5)")
    # param_grid_dt = {
    #     "criterion": ["gini", "entropy"],
    #     "max_depth": [5, 10, 15, None],
    #     "min_samples_split": [2, 5, 10],
    #     "min_samples_leaf": [1, 2, 5],
    # }
    # grid_dt = GridSearchCV(
    #     DecisionTreeClassifier(random_state=MODEL_RANDOM_STATE), param_grid_dt,
    #     scoring="recall_macro", cv=5, refit=True, n_jobs=-1,
    # )
    # grid_dt.fit(X_train_s, y_train)
    # dt_hpo = grid_dt.best_estimator_
    # print(f"      Best params DT+HPO: {grid_dt.best_params_}")
    # metrics["DT+HPO"] = _eval("DT+HPO", dt_hpo, X_test_s, y_test)

    # ----- [7] Simpan artifacts -----
    print("\n[7/7] Menyimpan artifacts ke storage/models/")
    # _save(knn,             "knn_model.pkl")
    # _save(knn_hpo,         "knn_hpo_model.pkl")
    _save(svm,             "svm_model.pkl")
    _save(svm_hpo,         "svm_hpo_model.pkl")
    # _save(dt,              "dt_model.pkl")
    # _save(dt_hpo,          "dt_hpo_model.pkl")
    _save(scaler,          "scaler.pkl")
    _save(FEATURE_COLUMNS, "feature_columns.pkl")

    with open(SCRIPT_DIR / "train_results.json", "w", encoding="utf-8") as f:
        json.dump(metrics, f, indent=2)

    with open(SCRIPT_DIR / "train_results.txt", "w", encoding="utf-8") as f:
        f.write("Mental Health Risk Prediction - Training Results\n")
        f.write("Encoding: ordinal(education) + OHE(gender,marital,employment) = 31 fitur\n")
        f.write("=" * 60 + "\n\n")
        for name, m in metrics.items():
            f.write(f"{name}\n{'-'*len(name)}\n")
            for k, v in m.items():
                f.write(f"  {k:<10s}: {v:.4f}\n")
            f.write("\n")

    # Perbarui retrain_metadata.json
    import datetime
    meta_path = SCRIPT_DIR / "retrain_metadata.json"
    if meta_path.exists():
        with open(meta_path, "r", encoding="utf-8") as f:
            meta = json.load(f)
    else:
        meta = {"retrains_count": 0}

    meta["last_retrain_timestamp"]    = datetime.datetime.utcnow().strftime("%Y-%m-%dT%H:%M:%SZ")
    meta["last_retrain_dataset_size"] = int(len(df))
    meta["user_data_rows_used"]       = int(user_rows_added)
    meta["models_trained"]            = list(metrics.keys())
    meta["training_metrics"]          = metrics
    meta["retrains_count"]            = meta.get("retrains_count", 0) + 1

    with open(meta_path, "w", encoding="utf-8") as f:
        json.dump(meta, f, indent=2)
    print("  retrain_metadata.json (diperbarui)")

    print("\nSelesai. Artifacts yang tersimpan:")
    for p in sorted(SCRIPT_DIR.glob("*.pkl")):
        print(f"  {p.name}")
    print("  train_results.json")
    print("  train_results.txt")


if __name__ == "__main__":
    main()
