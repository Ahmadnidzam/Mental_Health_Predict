"""Inference script untuk Mental Health Risk Prediction.

Dipanggil oleh Laravel via Symfony/Process:
    python storage/models/predict.py '<JSON-input>'

Mode single (input manual):
    { "model": "svm", "age": 25, "gender": "Male", ... }
    Output: { "status": "success", "prediction": 1, "confidence": 0.82, "label": "Sedang", "model": "svm" }

Mode batch (upload CSV — semua baris sekaligus):
    { "model": "svm", "rows": [ {"age":25,...}, {"age":30,...}, ... ] }
    Output: { "status": "success", "model": "svm", "results": [
        { "index": 0, "prediction": 1, "confidence": 0.82, "label": "Sedang" },
        ...
    ]}
"""
from __future__ import annotations

import json
import pickle
import sys
import traceback
from pathlib import Path

import numpy as np

SCRIPT_DIR = Path(__file__).resolve().parent

EDUCATION_MAP = {"High School": 0, "Bachelor": 1, "Master": 2, "PhD": 3}

GENDER_CLASSES     = ["Female", "Male", "Other"]
MARITAL_CLASSES    = ["Divorced", "Married", "Single"]
EMPLOYMENT_CLASSES = ["Employed", "Self-Employed", "Student", "Unemployed"]

FEATURE_COLUMNS = [
    "age", "education_level", "sleep_hours", "physical_activity_hours_per_week",
    "screen_time_hours_per_day", "social_support_score", "work_stress_level",
    "academic_pressure_level", "job_satisfaction_score", "financial_stress_level",
    "working_hours_per_week", "anxiety_score", "depression_score", "stress_level",
    "mood_swings_frequency", "concentration_difficulty_level", "panic_attack_history",
    "family_history_mental_illness", "previous_mental_health_diagnosis",
    "therapy_history", "substance_use",
    "gender_Female", "gender_Male", "gender_Other",
    "marital_status_Divorced", "marital_status_Married", "marital_status_Single",
    "employment_status_Employed", "employment_status_Self-Employed",
    "employment_status_Student", "employment_status_Unemployed",
]

NUMERIC_FIELDS = [
    "age", "sleep_hours", "physical_activity_hours_per_week",
    "screen_time_hours_per_day", "social_support_score", "work_stress_level",
    "academic_pressure_level", "job_satisfaction_score", "financial_stress_level",
    "working_hours_per_week", "anxiety_score", "depression_score", "stress_level",
    "mood_swings_frequency", "concentration_difficulty_level", "panic_attack_history",
    "family_history_mental_illness", "previous_mental_health_diagnosis",
    "therapy_history", "substance_use",
]

MODEL_FILES = {
    "knn":     "knn_model.pkl",
    "knn_hpo": "knn_hpo_model.pkl",
    "svm":     "svm_model.pkl",
    "svm_hpo": "svm_hpo_model.pkl",
    "dt":      "dt_model.pkl",
    "dt_hpo":  "dt_hpo_model.pkl",
}

RISK_LABELS = {0: "Rendah", 1: "Sedang", 2: "Tinggi"}


def _load(filename: str):
    path = SCRIPT_DIR / filename
    if not path.exists():
        raise FileNotFoundError(f"File model tidak ditemukan: {path}")
    with open(path, "rb") as fh:
        return pickle.load(fh)


def _coerce(value, default: float = 0.0) -> float:
    if value is None or value == "":
        return default
    try:
        return float(value)
    except (TypeError, ValueError):
        return default


def _preprocess(payload: dict) -> np.ndarray:
    """
    Membangun vektor 31-fitur dari payload mentah sesuai encoding notebook.
    """
    row: dict[str, float] = {}

    # Fitur numerik langsung (termasuk binary 0/1)
    for col in NUMERIC_FIELDS:
        row[col] = _coerce(payload.get(col))

    # Ordinal encoding untuk education_level
    edu_raw = str(payload.get("education_level", "High School"))
    row["education_level"] = float(EDUCATION_MAP.get(edu_raw, 0))

    # OHE gender
    gender_raw = str(payload.get("gender", ""))
    for cls in GENDER_CLASSES:
        row[f"gender_{cls}"] = 1.0 if gender_raw == cls else 0.0

    # OHE marital_status
    marital_raw = str(payload.get("marital_status", ""))
    for cls in MARITAL_CLASSES:
        row[f"marital_status_{cls}"] = 1.0 if marital_raw == cls else 0.0

    # OHE employment_status
    emp_raw = str(payload.get("employment_status", ""))
    for cls in EMPLOYMENT_CLASSES:
        row[f"employment_status_{cls}"] = 1.0 if emp_raw == cls else 0.0

    # Susun vektor sesuai urutan FEATURE_COLUMNS
    vector = [row[col] for col in FEATURE_COLUMNS]
    return np.array([vector], dtype=float)


def _predict_rows(rows: list[dict], model, scaler) -> list[dict]:
    """Proses banyak baris sekaligus — model & scaler hanya di-load sekali."""
    if not rows:
        return []

    # Bangun matrix (N x 31)
    X_raw = np.vstack([_preprocess(r) for r in rows])
    X_scaled = scaler.transform(X_raw)

    predictions = model.predict(X_scaled)
    probas      = model.predict_proba(X_scaled)

    results = []
    for i, (pred, proba) in enumerate(zip(predictions, probas)):
        results.append({
            "index":      i,
            "prediction": int(pred),
            "confidence": round(float(np.max(proba)), 4),
            "label":      RISK_LABELS.get(int(pred), "Unknown"),
        })
    return results


def main() -> None:
    try:
        # --stdin  → baca JSON dari stdin (dipakai Laravel via proc_open tempfile)
        # '<json>' → baca JSON dari argv[1] (backward-compat / manual test)
        if len(sys.argv) >= 2 and sys.argv[1] == "--stdin":
            raw = sys.stdin.read()
        elif len(sys.argv) >= 2:
            raw = sys.argv[1]
        else:
            raise ValueError("Usage: python predict.py --stdin  OR  python predict.py '<json>'")

        payload = json.loads(raw)

        model_key = str(payload.get("model", "svm")).lower().strip()
        if model_key not in MODEL_FILES:
            raise ValueError(
                f"Model '{model_key}' tidak dikenal. "
                f"Pilihan valid: {list(MODEL_FILES.keys())}"
            )

        scaler = _load("scaler.pkl")
        model  = _load(MODEL_FILES[model_key])

        # ---- Mode batch ----
        if "rows" in payload:
            rows    = payload["rows"]
            results = _predict_rows(rows, model, scaler)
            print(json.dumps({"status": "success", "model": model_key, "results": results}))
            return

        # ---- Mode single ----
        X_raw    = _preprocess(payload)
        X_scaled = scaler.transform(X_raw)

        prediction = int(model.predict(X_scaled)[0])
        proba      = model.predict_proba(X_scaled)[0]
        confidence = float(np.max(proba))

        print(json.dumps({
            "status":     "success",
            "model":      model_key,
            "prediction": prediction,
            "confidence": round(confidence, 4),
            "label":      RISK_LABELS.get(prediction, "Unknown"),
        }))

    except Exception as exc:
        print(json.dumps({
            "status":  "error",
            "message": str(exc),
            "trace":   traceback.format_exc(),
        }))
        sys.exit(1)


if __name__ == "__main__":
    main()
