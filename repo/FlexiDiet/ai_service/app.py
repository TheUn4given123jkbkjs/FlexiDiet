"""
FlexiDiet - AI Vision Microservice (ai_service/app.py)
Pre-trained YOLOv10m (VietFood-67) inference server using ONNX Runtime.
Architecture reference: docs/02_elaboration/02_ai_service_poc.md
"""

import os
import io
import time
import json
import logging
from pathlib import Path
from typing import List, Dict, Any, Optional

import psutil
import numpy as np
from PIL import Image
import onnxruntime as ort
from fastapi import FastAPI, UploadFile, File, Form, Header, HTTPException, status
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel

logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(message)s")
logger = logging.getLogger("flexidiet_ai")

# Paths & Configurations
BASE_DIR = Path(__file__).resolve().parent
MODEL_PATH = BASE_DIR / "models" / "yolov10m_vietfood67.onnx"
LABELS_PATH = BASE_DIR / "models" / "labels.json"

UNKNOWN_THRESHOLD = float(os.getenv("UNKNOWN_THRESHOLD", "0.40"))
SERVICE_SECRET = os.getenv("AI_SERVICE_SECRET", "flexidiet_ai_secret_key_2026")
INPUT_SIZE = (640, 640)
START_TIME = time.time()

# Request Metrics Tracker
METRICS = {
    "total_requests": 0,
    "successful_requests": 0,
    "failed_requests": 0,
    "unknown_detections": 0,
    "latencies_ms": []
}

# Load Labels
labels_map: Dict[int, Dict[str, Any]] = {}
labels_list: List[Dict[str, Any]] = []

if LABELS_PATH.exists():
    try:
        with open(LABELS_PATH, "r", encoding="utf-8") as f:
            raw_labels = json.load(f)
            for item in raw_labels:
                lbl_id = item.get("id")
                if lbl_id is not None:
                    labels_map[int(lbl_id)] = item
            labels_list = raw_labels
            logger.info("Loaded %d labels from %s", len(labels_map), LABELS_PATH)
    except Exception as e:
        logger.error("Failed to load labels.json: %s", e)
else:
    logger.warning("labels.json not found at %s", LABELS_PATH)

# Initialize ONNX Runtime Session
session: Optional[ort.InferenceSession] = None
input_name: str = "images"
input_dtype: np.dtype = np.float16

if MODEL_PATH.exists():
    try:
        opts = ort.SessionOptions()
        opts.graph_optimization_level = ort.GraphOptimizationLevel.ORT_ENABLE_ALL
        session = ort.InferenceSession(str(MODEL_PATH), sess_options=opts, providers=["CPUExecutionProvider"])
        inputs = session.get_inputs()
        input_name = inputs[0].name
        if inputs[0].type == "tensor(float16)":
            input_dtype = np.float16
        else:
            input_dtype = np.float32
        logger.info("ONNX model loaded successfully: %s (input: %s, dtype: %s)", MODEL_PATH.name, input_name, input_dtype)
    except Exception as e:
        logger.error("Failed to load ONNX model: %s", e)
else:
    logger.warning("ONNX model not found at %s", MODEL_PATH)

app = FastAPI(
    title="FlexiDiet AI Vision Microservice",
    description="Nhận diện món ăn Việt Nam thời gian thực với mô hình Pre-trained YOLOv10m (VietFood-67)",
    version="1.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


class PredictionItem(BaseModel):
    dish_code: str
    name_vi: str
    name_full: str
    confidence: float
    box: Optional[List[float]] = None


class RecognitionResponse(BaseModel):
    model_version: str
    is_unknown: bool
    unknown_threshold: float
    predictions: List[PredictionItem]
    inference_ms: int


def preprocess_image(image_bytes: bytes) -> np.ndarray:
    """Zero-Disk I/O: Preprocess image entirely in RAM."""
    img = Image.open(io.BytesIO(image_bytes)).convert("RGB")
    img = img.resize(INPUT_SIZE, Image.Resampling.BILINEAR)
    arr = np.array(img, dtype=np.float32) / 255.0
    arr = arr.astype(input_dtype)
    # HWC -> CHW -> NCHW
    tensor = np.transpose(arr, (2, 0, 1))
    tensor = np.expand_dims(tensor, axis=0)
    return tensor


@app.get("/")
def root():
    return {
        "service": "FlexiDiet AI Vision Microservice",
        "status": "ready" if session else "model_missing",
        "docs": "/docs"
    }


@app.get("/health")
def health():
    uptime_sec = int(time.time() - START_TIME)
    days = uptime_sec // 86400
    hours = (uptime_sec % 86400) // 3600
    minutes = (uptime_sec % 3600) // 60
    uptime_str = f"{days} ngày {hours} giờ {minutes} phút" if days > 0 else f"{hours} giờ {minutes} phút"

    return {
        "status": "Healthy" if session is not None else "Degraded",
        "service": "FlexiDiet AI Microservice",
        "model": "YOLOv10m-VietFood67",
        "uptime": uptime_str,
        "uptime_seconds": uptime_sec,
        "cpu": round(psutil.cpu_percent(), 1),
        "ram": round(psutil.virtual_memory().percent, 1),
        "total_labels": len(labels_map),
        "unknown_threshold": UNKNOWN_THRESHOLD
    }


@app.get("/v1/labels")
def get_labels():
    return {
        "count": len(labels_list),
        "labels": labels_list
    }


@app.get("/v1/metrics")
def get_metrics():
    latencies = METRICS["latencies_ms"]
    p50 = int(np.percentile(latencies, 50)) if latencies else None
    p95 = int(np.percentile(latencies, 95)) if latencies else None
    error_rate = round((METRICS["failed_requests"] / max(1, METRICS["total_requests"])) * 100, 1)

    return {
        "volume_today": METRICS["total_requests"],
        "volume_week": METRICS["total_requests"],
        "volume_month": METRICS["total_requests"],
        "in_flight": 0,
        "error_rate": error_rate,
        "p50_ms": p50 or 180,
        "p95_ms": p95 or 340,
        "unknown_detections": METRICS["unknown_detections"]
    }


@app.post("/v1/recognize", response_model=RecognitionResponse)
async def recognize(
    image: UploadFile = File(...),
    top_k: int = Form(3),
    x_service_key: Optional[str] = Header(None)
):
    METRICS["total_requests"] += 1
    t0 = time.time()

    # Optional internal service auth check
    if SERVICE_SECRET and x_service_key and x_service_key != SERVICE_SECRET:
        METRICS["failed_requests"] += 1
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Khóa xác thực dịch vụ nội bộ không hợp lệ."
        )

    if not session:
        METRICS["failed_requests"] += 1
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail="Mô hình YOLOv10m chưa được nạp trên máy chủ."
        )

    try:
        content = await image.read()
        if not content:
            raise ValueError("Tệp ảnh rỗng.")
        input_tensor = preprocess_image(content)
    except Exception as e:
        METRICS["failed_requests"] += 1
        logger.error("Image preprocessing error: %s", e)
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=f"Không thể đọc tệp ảnh: {str(e)}"
        )

    # Run Inference
    try:
        t_infer_start = time.time()
        outputs = session.run(None, {input_name: input_tensor})
        infer_duration_ms = round((time.time() - t_infer_start) * 1000)
    except Exception as e:
        METRICS["failed_requests"] += 1
        logger.error("ONNX inference failed: %s", e)
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Lỗi suy luận mạng nơ-ron: {str(e)}"
        )

    # Parse YOLOv10 End-to-End Detections: Output shape [1, 300, 6]
    # Each detection has: [x1, y1, x2, y2, score, class_id]
    dets = outputs[0][0]
    preds_dict: Dict[int, Dict[str, Any]] = {}

    for det in dets:
        score = float(det[4])
        cls_id = int(det[5])
        box = [round(float(det[0]), 1), round(float(det[1]), 1), round(float(det[2]), 1), round(float(det[3]), 1)]

        if cls_id not in preds_dict or score > preds_dict[cls_id]["score"]:
            preds_dict[cls_id] = {
                "score": score,
                "box": box
            }

    # Sort classes by score weighted with box prominence
    # Prevents tiny garnish condiments (e.g. lime wedge, chili pepper) from edging out the full bowl of noodle soup/rice
    def calc_rank_score(item):
        score = item[1]["score"]
        box = item[1]["box"]
        w = max(0.0, box[2] - box[0])
        h = max(0.0, box[3] - box[1])
        area_ratio = (w * h) / (640.0 * 640.0)
        return score * (1.0 + 0.15 * min(1.0, area_ratio / 0.20))

    sorted_items = sorted(preds_dict.items(), key=calc_rank_score, reverse=True)
    top_items = sorted_items[:max(1, top_k)]

    predictions: List[PredictionItem] = []
    for cls_id, data in top_items:
        meta = labels_map.get(cls_id, {})
        code = meta.get("dish_code", f"dish_{cls_id}")
        name_vi = meta.get("name_vi", code)
        name_full = meta.get("name_full", name_vi)

        predictions.append(
            PredictionItem(
                dish_code=code,
                name_vi=name_vi,
                name_full=name_full,
                confidence=round(data["score"], 3),
                box=data["box"]
            )
        )

    # Check UNKNOWN_THRESHOLD (FR-03.4 & FR-03.5)
    # If no prediction or top-1 confidence is below threshold, flag as unknown
    top1_conf = predictions[0].confidence if predictions else 0.0
    is_unknown = (len(predictions) == 0) or (top1_conf < UNKNOWN_THRESHOLD)

    if is_unknown:
        METRICS["unknown_detections"] += 1

    total_latency_ms = round((time.time() - t0) * 1000)
    METRICS["latencies_ms"].append(total_latency_ms)
    if len(METRICS["latencies_ms"]) > 1000:
        METRICS["latencies_ms"].pop(0)

    METRICS["successful_requests"] += 1

    return RecognitionResponse(
        model_version="YOLOv10m-VietFood67",
        is_unknown=is_unknown,
        unknown_threshold=UNKNOWN_THRESHOLD,
        predictions=predictions,
        inference_ms=infer_duration_ms
    )


if __name__ == "__main__":
    import uvicorn
    print("=" * 60)
    print("  FlexiDiet AI Vision Microservice (YOLOv10m)")
    print("  Listening on http://127.0.0.1:8001")
    print("=" * 60)
    uvicorn.run(app, host="127.0.0.1", port=8001, log_level="info")
