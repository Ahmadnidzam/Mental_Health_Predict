"""Training Script untuk Mental Health Risk Prediction.

Cara menjalankan dari root project Laravel:
    python storage/models/train_models.py --output-dir <abs path> [--base-only]

Encoding sesuai notebooks/file_default_predict.ipynb:
  - education_level  : ordinal mapping (High School=0, Bachelor=1, Master=2, PhD=3)
  - gender           : frequency encoding -> gender_freq_encoded
  - marital_status   : frequency encoding -> marital_status_freq_encoded
  - employment_status: frequency encoding -> employment_status_freq_encoded
  - Kolom kategorik asli di-drop. Total fitur X : 24 kolom

Models yang dilatih (6 total):
  1. KNN         : KNeighborsClassifier(n_neighbors=89, metric='manhattan')
  2. KNN+HPO     : GridSearchCV over n_neighbors=[1,3,...,99] x metric=[euclidean,manhattan], recall_macro, cv=5
  3. SVM         : SVC(kernel='rbf', C=100, gamma=0.01, probability=True)
  4. SVM+HPO     : GridSearchCV({C:[1,10,100],gamma:[0.01,0.001,0.0001],kernel:[rbf,linear]}, recall_macro, cv=3)
  5. DT          : DecisionTreeClassifier(max_depth=10, random_state=0)
  6. DT+HPO      : GridSearchCV(criterion,max_depth,min_samples_split,min_samples_leaf, recall_macro, cv=5)

Semua artifact ditulis ke --output-dir (tidak ada lagi penulisan ke storage/models/ root):
  scaler.pkl, knn_model.pkl, knn_hpo_model.pkl, svm_model.pkl, svm_hpo_model.pkl,
  dt_model.pkl, dt_hpo_model.pkl, freq_maps.pkl, feature_columns.pkl, metrics.json
"""
from __future__ import annotations

import argparse
import json
import os
import pickle
from pathlib import Path

import numpy as np
import pandas as pd
from sklearn.metrics import (
    accuracy_score,
    confusion_matrix,
    f1_score,
    precision_score,
    recall_score,
)
from sklearn.model_selection import GridSearchCV, train_test_split
from sklearn.neighbors import KNeighborsClassifier
from sklearn.preprocessing import StandardScaler
from sklearn.svm import SVC
from sklearn.tree import DecisionTreeClassifier

SCRIPT_DIR = Path(__file__).resolve().parent
DATA_PATH = SCRIPT_DIR / "data" / "mental_health_risk_dataset.csv"
USER_DATA_PATH = SCRIPT_DIR / "data" / "user_contributions.csv"

SPLIT_RANDOM_STATE = 0
MODEL_RANDOM_STATE = 0

MAX_TRAIN_ROWS = int(os.environ.get("MAX_TRAIN_ROWS", "0"))

TARGET_COL = "mental_health_risk"

EDUCATION_MAP = {"High School": 0, "Bachelor": 1, "Master": 2, "PhD": 3}
FREQ_COLS = ["gender", "marital_status", "employment_status"]

# Urutan fitur X setelah encoding (identik dengan notebook):
# kolom asli (minus target, minus kolom kategorik) dengan urutan CSV,
# lalu 3 kolom *_freq_encoded ditambahkan di akhir.
FEATURE_COLUMNS = [
    "age", "education_level", "sleep_hours", "physical_activity_hours_per_week",
    "screen_time_hours_per_day", "social_support_score", "work_stress_level",
    "academic_pressure_level", "job_satisfaction_score", "financial_stress_level",
    "working_hours_per_week", "anxiety_score", "depression_score", "stress_level",
    "mood_swings_frequency", "concentration_difficulty_level", "panic_attack_history",
    "family_history_mental_illness", "previous_mental_health_diagnosis",
    "therapy_history", "substance_use",
    "gender_freq_encoded", "marital_status_freq_encoded", "employment_status_freq_encoded",
]


def _save(obj, output_dir: Path, filename: str) -> None:
    with open(output_dir / filename, "wb") as fh:
        pickle.dump(obj, fh, protocol=pickle.HIGHEST_PROTOCOL)
    print(f"      Saved: {filename}")


def _eval(name: str, model, X_test, y_test) -> dict:
    y_pred = model.predict(X_test)
    acc = accuracy_score(y_test, y_pred)
    prec = precision_score(y_test, y_pred, average="weighted", zero_division=0)
    rec = recall_score(y_test, y_pred, average="weighted", zero_division=0)
    f1 = f1_score(y_test, y_pred, average="weighted", zero_division=0)
    print(f"\n      [{name}]")
    print(f"        Accuracy : {acc:.4f}")
    print(f"        Precision: {prec:.4f}")
    print(f"        Recall   : {rec:.4f}")
    print(f"        F1-Score : {f1:.4f}")
    print("        Confusion matrix:")
    print(np.array2string(confusion_matrix(y_test, y_pred), prefix="        "))
    return {"accuracy": float(acc), "precision": float(prec),
            "recall": float(rec), "f1_score": float(f1)}


def _load_dataframe(base_only: bool) -> pd.DataFrame:
    """Load base dataset, optionally merge validated user contributions."""
    if not DATA_PATH.exists():
        raise FileNotFoundError(f"Dataset tidak ditemukan di {DATA_PATH}")

    print(f"\n[1/7] Loading dataset: {DATA_PATH}")
    df = pd.read_csv(DATA_PATH)
    print(f"      Shape (original): {df.shape}")

    if base_only:
        print("      Mode --base-only: user_contributions.csv diabaikan")
    elif USER_DATA_PATH.exists() and USER_DATA_PATH.stat().st_size > 0:
        df_user = pd.read_csv(USER_DATA_PATH)
        print(f"      + user_contributions.csv: {len(df_user)} baris (sebelum validasi)")

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
            print(f"      [SKIP] user_contributions.csv — kolom tidak lengkap: {missing_cols}")
        else:
            before_drop = len(df_user)
            df_user = df_user[df_user[TARGET_COL].isin([0, 1, 2])].reset_index(drop=True)
            dropped = before_drop - len(df_user)
            if dropped:
                print(f"      [WARN] {dropped} baris dibuang (target tidak valid)")

            before_dedup = len(df_user)
            df_user = df_user.drop_duplicates().reset_index(drop=True)
            deduped = before_dedup - len(df_user)
            if deduped:
                print(f"      [WARN] {deduped} baris duplikat dibuang")

            print(f"      [OK] {len(df_user)} baris valid akan digabung")
            df = pd.concat([df, df_user], ignore_index=True)
            print(f"      Shape setelah merge    : {df.shape}")
    else:
        print("      (Tidak ada user_contributions.csv, pakai dataset asli)")

    print(f"      Missing values: {int(df.isnull().sum().sum())}")
    if df.isnull().sum().sum() > 0:
        before_clean = len(df)
        df = df.dropna().reset_index(drop=True)
        print(f"      Dropped {before_clean - len(df)} baris dengan NaN")

    return df


def main() -> None:
    parser = argparse.ArgumentParser(description="Train mental health risk models.")
    parser.add_argument("--output-dir", required=True,
                        help="Direktori absolut tempat menulis semua artifact + metrics.json")
    parser.add_argument("--base-only", action="store_true",
                        help="Latih hanya pada dataset dasar (abaikan user_contributions.csv)")
    parser.add_argument("--max-rows", type=int, default=0,
                        help="Batasi jumlah baris training (subsample stratified). 0 = tanpa batas. "
                             "Override env MAX_TRAIN_ROWS. Berguna agar GridSearch SVM tetap feasible.")
    args = parser.parse_args()

    # CLI --max-rows menang atas env MAX_TRAIN_ROWS.
    max_rows = args.max_rows if args.max_rows > 0 else MAX_TRAIN_ROWS

    output_dir = Path(args.output_dir).resolve()
    output_dir.mkdir(parents=True, exist_ok=True)

    print("=" * 70)
    print("MENTAL HEALTH RISK PREDICTION - TRAINING PIPELINE")
    print("Encoding: ordinal(education) + frequency(gender,marital,employment) = 24 fitur")
    print(f"Output dir: {output_dir}")
    print("=" * 70)

    # ----- [1] Load -----
    df = _load_dataframe(args.base_only)
    dataset_size = int(len(df))

    # ----- [2] Encoding -----
    print("\n[2/7] Encoding fitur kategorik")
    df_enc = df.copy()

    # Ordinal untuk education_level
    df_enc["education_level"] = df_enc["education_level"].map(EDUCATION_MAP)
    print(f"      education_level (ordinal): {EDUCATION_MAP}")

    # Frequency encoding untuk gender, marital_status, employment_status
    freq_maps: dict[str, dict] = {}
    for col in FREQ_COLS:
        freq = df_enc[col].value_counts(normalize=True)
        freq_maps[col] = {str(k): float(v) for k, v in freq.items()}
        df_enc[f"{col}_freq_encoded"] = df_enc[col].map(freq)
        print(f"      frequency encoded: {col} -> {col}_freq_encoded")
    df_enc = df_enc.drop(columns=FREQ_COLS)

    # ----- [3] X, y -----
    print("\n[3/7] Membentuk X dan y")
    X = df_enc[FEATURE_COLUMNS]
    y = df_enc[TARGET_COL]
    print(f"      Shape X: {X.shape}  (harus 24 fitur)")
    print(f"      Shape y: {y.shape}")
    assert X.shape[1] == 24, f"Jumlah fitur harus 24, dapat {X.shape[1]}"

    # ----- [4] Split -----
    print("\n[4/7] Train-Test Split (70:30, stratified)")
    if max_rows and len(df_enc) > max_rows:
        print(f"      Subsample stratified: {len(df_enc)} -> {max_rows} baris (cap)")
        X, _, y, _ = train_test_split(
            X, y, train_size=max_rows,
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
    X_test_s = scaler.transform(X_test)

    # ----- [6] Training 6 model -----
    print("\n[6/7] Training 6 model...")
    metrics = {}

    # --- KNN (non-HPO) ---
    print("\n  >> KNN (non-HPO): n_neighbors=89, metric=manhattan")
    knn = KNeighborsClassifier(n_neighbors=89, metric="manhattan")
    knn.fit(X_train_s, y_train)
    metrics["KNN"] = _eval("KNN", knn, X_test_s, y_test)

    # --- KNN + HPO ---
    print("\n  >> KNN+HPO: GridSearchCV (n_neighbors=1..99 odd x metric, recall_macro, cv=5)")
    param_grid_knn = {
        "n_neighbors": list(range(1, 100, 2)),
        "metric": ["euclidean", "manhattan"],
    }
    grid_knn = GridSearchCV(
        KNeighborsClassifier(), param_grid_knn,
        scoring="recall_macro", cv=5, refit=True, n_jobs=-1,
    )
    grid_knn.fit(X_train_s, y_train)
    knn_hpo = grid_knn.best_estimator_
    print(f"      Best params KNN+HPO: {grid_knn.best_params_}")
    metrics["KNN+HPO"] = _eval("KNN+HPO", knn_hpo, X_test_s, y_test)

    # --- SVM (non-HPO) ---
    print("\n  >> SVM (non-HPO): kernel=rbf, C=100, gamma=0.01")
    svm = SVC(kernel="rbf", C=100, gamma=0.01, random_state=MODEL_RANDOM_STATE, probability=True)
    svm.fit(X_train_s, y_train)
    metrics["SVM"] = _eval("SVM", svm, X_test_s, y_test)

    # --- SVM + HPO ---
    print("\n  >> SVM+HPO: GridSearchCV({C:[1,10,100],gamma:[0.01,0.001,0.0001],kernel:[rbf,linear]}, recall_macro, cv=3)")
    param_grid_svm = [
        {"C": [1, 10, 100], "gamma": [0.01, 0.001, 0.0001], "kernel": ["rbf", "linear"]},
    ]
    grid_svm = GridSearchCV(
        SVC(probability=True), param_grid_svm,
        scoring="recall_macro", cv=3, refit=True,
    )
    grid_svm.fit(X_train_s, y_train)
    svm_hpo = grid_svm.best_estimator_
    print(f"      Best params SVM+HPO: {grid_svm.best_params_}")
    metrics["SVM+HPO"] = _eval("SVM+HPO", svm_hpo, X_test_s, y_test)

    # --- DT (non-HPO) ---
    print("\n  >> DT (non-HPO): max_depth=10, random_state=0")
    dt = DecisionTreeClassifier(max_depth=10, random_state=MODEL_RANDOM_STATE)
    dt.fit(X_train_s, y_train)
    metrics["DT"] = _eval("DT", dt, X_test_s, y_test)

    # --- DT + HPO ---
    print("\n  >> DT+HPO: GridSearchCV(criterion,max_depth,min_samples_split,min_samples_leaf, recall_macro, cv=5)")
    param_grid_dt = {
        "criterion": ["gini", "entropy"],
        "max_depth": [5, 10, 15, None],
        "min_samples_split": [2, 5, 10],
        "min_samples_leaf": [1, 2, 5],
    }
    grid_dt = GridSearchCV(
        DecisionTreeClassifier(random_state=MODEL_RANDOM_STATE), param_grid_dt,
        scoring="recall_macro", cv=5, refit=True, n_jobs=-1,
    )
    grid_dt.fit(X_train_s, y_train)
    dt_hpo = grid_dt.best_estimator_
    print(f"      Best params DT+HPO: {grid_dt.best_params_}")
    metrics["DT+HPO"] = _eval("DT+HPO", dt_hpo, X_test_s, y_test)

    # ----- [7] Simpan artifacts -----
    print(f"\n[7/7] Menyimpan artifacts ke {output_dir}")
    _save(scaler, output_dir, "scaler.pkl")
    _save(knn, output_dir, "knn_model.pkl")
    _save(knn_hpo, output_dir, "knn_hpo_model.pkl")
    _save(svm, output_dir, "svm_model.pkl")
    _save(svm_hpo, output_dir, "svm_hpo_model.pkl")
    _save(dt, output_dir, "dt_model.pkl")
    _save(dt_hpo, output_dir, "dt_hpo_model.pkl")
    _save(freq_maps, output_dir, "freq_maps.pkl")
    _save(FEATURE_COLUMNS, output_dir, "feature_columns.pkl")

    with open(output_dir / "metrics.json", "w", encoding="utf-8") as f:
        json.dump(metrics, f, indent=2)
    print("      Saved: metrics.json")

    print(f"\nSelesai. dataset_size={dataset_size}. Artifacts tersimpan di {output_dir}")


if __name__ == "__main__":
    main()
