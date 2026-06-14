"""Inference script untuk Mental Health Risk Prediction.

Dipanggil oleh Laravel via Symfony/Process:
    python storage/models/predict.py --stdin    (JSON dari stdin)
    python storage/models/predict.py '<JSON>'   (backward-compat / manual test)

Artifact dimuat dari versi model AKTIF (resolusi via active_version.json):
    storage/models/active_version.json  ->  { "id": 1, "path": "versions/1" }

Mode single (input manual):
    { "model": "svm", "age": 25, "gender": "Male", ... }
    Output: { "status": "success", "model": "svm", "prediction": 1, "confidence": 0.82, "label": "Sedang" }

Mode batch (upload CSV — semua baris sekaligus):
    { "model": "svm", "rows": [ {"age":25,...}, {"age":30,...}, ... ] }
    Output: { "status": "success", "model": "svm", "results": [
        { "index": 0, "prediction": 1, "confidence": 0.82, "label": "Sedang" }, ...
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
ACTIVE_POINTER = SCRIPT_DIR / "active_version.json"

EDUCATION_MAP = {"High School": 0, "Bachelor": 1, "Master": 2, "PhD": 3}

# Kolom kategorik yang di-frequency-encode (nama kolom hasil: <col>_freq_encoded)
FREQ_COLS = ["gender", "marital_status", "employment_status"]

MODEL_FILES = {
    "knn":     "knn_model.pkl",
    "knn_hpo": "knn_hpo_model.pkl",
    "svm":     "svm_model.pkl",
    "svm_hpo": "svm_hpo_model.pkl",
    "dt":      "dt_model.pkl",
    "dt_hpo":  "dt_hpo_model.pkl",
}

RISK_LABELS = {0: "Rendah", 1: "Sedang", 2: "Tinggi"}

NO_ACTIVE_MODEL_MSG = "Tidak ada model aktif. Jalankan models:seed-base."


class NoActiveModelError(Exception):
    """Pointer model aktif tidak ada atau tidak valid."""


def _resolve_active_dir(model_key: str) -> Path:
    """Baca active_version.json dan kembalikan direktori versi aktif untuk
    algoritma `model_key`.

    Format baru (map per-algoritma):
        { "knn": {"version_id":1,"path":"versions/1"}, "dt": {...}, ... }
    Format lama (single, backward-compat):
        { "id": 1, "path": "versions/1" }  -> dipakai untuk semua algoritma.
    """
    if not ACTIVE_POINTER.exists():
        raise NoActiveModelError(NO_ACTIVE_MODEL_MSG)
    try:
        with open(ACTIVE_POINTER, "r", encoding="utf-8") as fh:
            pointer = json.load(fh)
    except (json.JSONDecodeError, OSError):
        raise NoActiveModelError(NO_ACTIVE_MODEL_MSG)

    if not isinstance(pointer, dict):
        raise NoActiveModelError(NO_ACTIVE_MODEL_MSG)

    # Format baru: entri per-algoritma.
    entry = pointer.get(model_key)
    if isinstance(entry, dict) and entry.get("path"):
        rel_path = entry["path"]
    elif pointer.get("path"):
        # Format lama single -> berlaku untuk semua algoritma.
        rel_path = pointer["path"]
    else:
        raise NoActiveModelError(NO_ACTIVE_MODEL_MSG)

    version_dir = (SCRIPT_DIR / rel_path).resolve()
    if not version_dir.is_dir():
        raise NoActiveModelError(NO_ACTIVE_MODEL_MSG)
    return version_dir


def _load(version_dir: Path, filename: str):
    path = version_dir / filename
    if not path.exists():
        raise FileNotFoundError(f"Artifact tidak ditemukan: {path}")
    with open(path, "rb") as fh:
        return pickle.load(fh)


def _coerce(value, default: float = 0.0) -> float:
    if value is None or value == "":
        return default
    try:
        return float(value)
    except (TypeError, ValueError):
        return default


def _preprocess(payload: dict, feature_columns: list[str], freq_maps: dict) -> np.ndarray:
    """Bangun vektor fitur sesuai urutan feature_columns yang tersimpan.

    - Kolom *_freq_encoded: map kategori mentah lewat freq_maps (unseen -> 0.0).
    - education_level     : ordinal map.
    - Lainnya (numerik/binary): coerce ke float.
    """
    row: dict[str, float] = {}

    for col in feature_columns:
        if col == "education_level":
            edu_raw = str(payload.get("education_level", "High School"))
            row[col] = float(EDUCATION_MAP.get(edu_raw, 0))
            continue

        if col.endswith("_freq_encoded"):
            base_col = col[: -len("_freq_encoded")]
            raw = str(payload.get(base_col, ""))
            mapping = freq_maps.get(base_col, {})
            row[col] = float(mapping.get(raw, 0.0))
            continue

        row[col] = _coerce(payload.get(col))

    vector = [row[col] for col in feature_columns]
    return np.array([vector], dtype=float)


def _predict_rows(rows: list[dict], model, scaler,
                  feature_columns: list[str], freq_maps: dict) -> list[dict]:
    """Proses banyak baris sekaligus — model & scaler hanya di-load sekali."""
    if not rows:
        return []

    X_raw = np.vstack([_preprocess(r, feature_columns, freq_maps) for r in rows])
    X_scaled = scaler.transform(X_raw)

    predictions = model.predict(X_scaled)
    probas = model.predict_proba(X_scaled)

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

        version_dir = _resolve_active_dir(model_key)
        scaler = _load(version_dir, "scaler.pkl")
        model = _load(version_dir, MODEL_FILES[model_key])
        freq_maps = _load(version_dir, "freq_maps.pkl")
        feature_columns = _load(version_dir, "feature_columns.pkl")

        # ---- Mode batch ----
        if "rows" in payload:
            results = _predict_rows(payload["rows"], model, scaler, feature_columns, freq_maps)
            print(json.dumps({"status": "success", "model": model_key, "results": results}))
            return

        # ---- Mode single ----
        X_raw = _preprocess(payload, feature_columns, freq_maps)
        X_scaled = scaler.transform(X_raw)

        prediction = int(model.predict(X_scaled)[0])
        proba = model.predict_proba(X_scaled)[0]
        confidence = float(np.max(proba))

        print(json.dumps({
            "status":     "success",
            "model":      model_key,
            "prediction": prediction,
            "confidence": round(confidence, 4),
            "label":      RISK_LABELS.get(prediction, "Unknown"),
        }))

    except NoActiveModelError as exc:
        print(json.dumps({"status": "error", "message": str(exc)}))
        sys.exit(1)
    except Exception as exc:
        print(json.dumps({
            "status":  "error",
            "message": str(exc),
            "trace":   traceback.format_exc(),
        }))
        sys.exit(1)


if __name__ == "__main__":
    main()
