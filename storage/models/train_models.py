"""Training Script untuk Mental Health Risk Prediction.

Cara menjalankan dari root project Laravel:
    python storage/models/train_models.py

Tahapan ML di script ini DIBUAT IDENTIK dengan notebook
notebooks/mental_health_ml.ipynb (kecuali bagian visualisasi):
  - random_state untuk train_test_split = 0
  - random_state untuk DecisionTree = 42
  - StandardScaler fit di train, transform test
  - Parameter model = hasil HPO (KNN n=39; SVM C=10,gamma=0.01,rbf;
    DT entropy,max_depth=10,min_split=2,min_leaf=2)
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
    confusion_matrix,
    f1_score,
    precision_score,
    recall_score,
)
from sklearn.model_selection import train_test_split
from sklearn.neighbors import KNeighborsClassifier
from sklearn.preprocessing import LabelEncoder, StandardScaler
from sklearn.svm import SVC
from sklearn.tree import DecisionTreeClassifier

SCRIPT_DIR = Path(__file__).resolve().parent
DATA_PATH = SCRIPT_DIR / "data" / "mental_health_risk_dataset.csv"

# random_state split = 0 supaya identik dengan notebook
SPLIT_RANDOM_STATE = 0
# random_state model = 42 supaya identik dengan notebook
MODEL_RANDOM_STATE = 42

# Default 0 = pakai FULL dataset (sesuai notebook).
# Set angka > 0 lewat env var jika butuh subsample untuk laptop lemah.
MAX_TRAIN_ROWS = int(os.environ.get("MAX_TRAIN_ROWS", "0"))

CATEGORICAL_COLS = ["gender", "marital_status", "education_level", "employment_status"]
TARGET_COL = "mental_health_risk"


def _save(obj, filename):
    with open(SCRIPT_DIR / filename, "wb") as fh:
        pickle.dump(obj, fh, protocol=pickle.HIGHEST_PROTOCOL)


def main() -> None:
    print("=" * 70)
    print("MENTAL HEALTH RISK PREDICTION - TRAINING PIPELINE")
    print("=" * 70)

    if not DATA_PATH.exists():
        raise FileNotFoundError(f"Dataset tidak ditemukan di {DATA_PATH}")

    print(f"\n[1/7] Loading dataset dari: {DATA_PATH}")
    df = pd.read_csv(DATA_PATH)
    print(f"      Shape awal: {df.shape}")

    print("\n[2/7] EDA singkat")
    print(f"      Missing values total : {int(df.isnull().sum().sum())}")
    print("      Distribusi target:")
    print(df[TARGET_COL].value_counts().sort_index().to_string())

    if df.isnull().sum().sum() > 0:
        df = df.dropna().reset_index(drop=True)

    print("\n[3/7] Encoding fitur kategorikal")
    label_encoders = {}
    for col in CATEGORICAL_COLS:
        if col not in df.columns:
            raise KeyError(f"Kolom kategorik '{col}' tidak ada di dataset.")
        le = LabelEncoder()
        df[col] = le.fit_transform(df[col].astype(str))
        label_encoders[col] = le
        print(f"      {col:25s} -> classes: {list(le.classes_)}")

    feature_names = [c for c in df.columns if c != TARGET_COL]

    if MAX_TRAIN_ROWS and len(df) > MAX_TRAIN_ROWS:
        df, _ = train_test_split(
            df, train_size=MAX_TRAIN_ROWS,
            random_state=SPLIT_RANDOM_STATE, stratify=df[TARGET_COL],
        )
        df = df.reset_index(drop=True)
        print(f"      -> Stratified subsample: {df.shape} (MAX_TRAIN_ROWS={MAX_TRAIN_ROWS})")
    else:
        print("      -> Pakai FULL dataset (sesuai notebook)")

    X = df[feature_names].values
    y = df[TARGET_COL].values
    print(f"\n[4/7] X={X.shape}, y={y.shape}")

    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.3, random_state=SPLIT_RANDOM_STATE, stratify=y
    )
    print(f"      Train: {X_train.shape}  Test: {X_test.shape}")

    print("\n[5/7] StandardScaler fit on TRAIN ONLY")
    scaler = StandardScaler()
    X_train_scaled = scaler.fit_transform(X_train)
    X_test_scaled = scaler.transform(X_test)

    print("\n[6/7] Training 3 algoritma (parameter sesuai HPO di notebook)")
    models = {
        "KNN": KNeighborsClassifier(n_neighbors=39),
        "SVM": SVC(
            kernel="rbf", C=10, gamma=0.01, probability=True,
            random_state=MODEL_RANDOM_STATE, cache_size=500,
        ),
        "Decision Tree": DecisionTreeClassifier(
            criterion="entropy", max_depth=10,
            min_samples_split=2, min_samples_leaf=2,
            random_state=MODEL_RANDOM_STATE,
        ),
    }

    metrics = {}
    for name, model in models.items():
        print(f"\n      -> Training {name} ...")
        model.fit(X_train_scaled, y_train)
        y_pred = model.predict(X_test_scaled)
        acc = accuracy_score(y_test, y_pred)
        prec = precision_score(y_test, y_pred, average="weighted", zero_division=0)
        rec = recall_score(y_test, y_pred, average="weighted", zero_division=0)
        f1 = f1_score(y_test, y_pred, average="weighted", zero_division=0)
        metrics[name] = {
            "accuracy": float(acc), "precision": float(prec),
            "recall": float(rec), "f1_score": float(f1),
        }
        print(f"         Accuracy : {acc:.4f}")
        print(f"         Precision: {prec:.4f}")
        print(f"         Recall   : {rec:.4f}")
        print(f"         F1-Score : {f1:.4f}")
        print("         Confusion matrix:")
        print(np.array2string(confusion_matrix(y_test, y_pred), prefix="         "))

    print("\n[7/7] Menyimpan model & artifacts ke storage/models/")
    _save(models["KNN"], "knn_model.pkl")
    _save(models["SVM"], "svm_model.pkl")
    _save(models["Decision Tree"], "dt_model.pkl")
    _save(scaler, "scaler.pkl")
    _save(label_encoders, "encoders.pkl")
    _save(feature_names, "feature_names.pkl")

    suffix_map = {
        "gender": "gender", "marital_status": "marital",
        "education_level": "education", "employment_status": "employment",
    }
    for col, le in label_encoders.items():
        suffix = suffix_map.get(col, col)
        _save(le, f"encoder_{suffix}.pkl")

    with open(SCRIPT_DIR / "train_results.json", "w", encoding="utf-8") as f:
        json.dump(metrics, f, indent=2)

    with open(SCRIPT_DIR / "train_results.txt", "w", encoding="utf-8") as f:
        f.write("Mental Health Risk Prediction - Training Results\n")
        f.write("=" * 60 + "\n\n")
        for name, m in metrics.items():
            f.write(f"{name}\n")
            f.write("-" * len(name) + "\n")
            for k, v in m.items():
                f.write(f"  {k:<10s}: {v:.4f}\n")
            f.write("\n")

    print("\nSelesai. File yang tersimpan:")
    for p in sorted(SCRIPT_DIR.glob("*.pkl")):
        print(f"  - {p.name}")
    print("  - train_results.json")
    print("  - train_results.txt")


if __name__ == "__main__":
    main()
