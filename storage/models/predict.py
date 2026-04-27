"""Inference script untuk Mental Health Risk Prediction.

Dipanggil oleh Laravel via Symfony/Process:
    python storage/models/predict.py '<JSON-input>'

Input  : JSON object berisi 24 fitur input.
Output : JSON object berisi prediksi & confidence dari KNN, SVM, DT
         + final prediction (majority voting).
"""
from __future__ import annotations

import json
import sys
import traceback
from collections import Counter
from pathlib import Path

import pickle

import numpy as np

SCRIPT_DIR = Path(__file__).resolve().parent

CATEGORICAL_COLS = ["gender", "marital_status", "education_level", "employment_status"]


def _load(filename):
    with open(SCRIPT_DIR / filename, "rb") as fh:
        return pickle.load(fh)


def _load_artifacts():
    knn = _load("knn_model.pkl")
    svm = _load("svm_model.pkl")
    dt = _load("dt_model.pkl")
    scaler = _load("scaler.pkl")
    encoders = _load("encoders.pkl")
    feature_names = _load("feature_names.pkl")
    return knn, svm, dt, scaler, encoders, feature_names


def _coerce(value, default=0.0):
    if value is None or value == "":
        return default
    try:
        return float(value)
    except (TypeError, ValueError):
        return default


def _preprocess(payload, encoders, feature_names, scaler):
    row = []
    for col in feature_names:
        raw = payload.get(col, None)
        if col in CATEGORICAL_COLS:
            le = encoders[col]
            classes = list(le.classes_)
            value = str(raw) if raw is not None else classes[0]
            if value not in classes:
                # Fallback: pakai class pertama agar tidak error
                value = classes[0]
            row.append(int(le.transform([value])[0]))
        else:
            row.append(_coerce(raw, 0.0))
    arr = np.asarray([row], dtype=float)
    return scaler.transform(arr)


def _majority_vote(preds, confidences):
    counts = Counter(preds)
    top_count = max(counts.values())
    candidates = [p for p, c in counts.items() if c == top_count]
    if len(candidates) == 1:
        return candidates[0]
    # Tiebreak: ambil prediksi dengan confidence tertinggi di antara kandidat
    best_pred = candidates[0]
    best_conf = -1.0
    for pred, conf in zip(preds, confidences):
        if pred in candidates and conf > best_conf:
            best_conf = conf
            best_pred = pred
    return best_pred


def main() -> None:
    try:
        if len(sys.argv) < 2:
            raise ValueError("Tidak ada input JSON. Usage: python predict.py '<json>'")

        payload = json.loads(sys.argv[1])

        knn, svm, dt, scaler, encoders, feature_names = _load_artifacts()
        X = _preprocess(payload, encoders, feature_names, scaler)

        knn_pred = int(knn.predict(X)[0])
        svm_pred = int(svm.predict(X)[0])
        dt_pred = int(dt.predict(X)[0])

        knn_conf = float(np.max(knn.predict_proba(X)[0]))
        svm_conf = float(np.max(svm.predict_proba(X)[0]))
        dt_conf = float(np.max(dt.predict_proba(X)[0]))

        final_pred = _majority_vote(
            [knn_pred, svm_pred, dt_pred],
            [knn_conf, svm_conf, dt_conf],
        )

        labels = {0: "Rendah", 1: "Sedang", 2: "Tinggi"}

        output = {
            "status": "success",
            "knn": knn_pred,
            "svm": svm_pred,
            "dt": dt_pred,
            "knn_confidence": round(knn_conf, 4),
            "svm_confidence": round(svm_conf, 4),
            "dt_confidence": round(dt_conf, 4),
            "final": int(final_pred),
            "labels": {
                "knn": labels.get(knn_pred, "Unknown"),
                "svm": labels.get(svm_pred, "Unknown"),
                "dt": labels.get(dt_pred, "Unknown"),
                "final": labels.get(int(final_pred), "Unknown"),
            },
        }
        print(json.dumps(output))
    except Exception as exc:  # noqa: BLE001
        err = {
            "status": "error",
            "message": str(exc),
            "trace": traceback.format_exc(),
        }
        print(json.dumps(err))
        sys.exit(1)


if __name__ == "__main__":
    main()
