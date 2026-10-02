"""
FlexiDiet - AI Vision Food Recognition Microservice (Python FastAPI)
Thực hiện nhận diện món ăn Việt Nam từ hình ảnh và phân tích prompt mô tả.
"""

from fastapi import FastAPI, File, UploadFile, Form, HTTPException
from fastapi.middleware.cors import CORSMiddleware
import uvicorn
from typing import Optional

app = FastAPI(
    title="FlexiDiet AI Vision Service",
    description="Dịch vụ suy luận nhận diện món ăn Việt Nam & ước tính dinh dưỡng",
    version="1.0.0"
)

# Cấu hình CORS để PHP Backend và Frontend gọi được
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.get("/health")
def health_check():
    return {
        "status": "online",
        "service": "FlexiDiet AI Inference Engine",
        "model_loaded": True
    }

@app.post("/api/predict-food")
async def predict_food(
    image: Optional[UploadFile] = File(None),
    prompt: Optional[str] = Form(None)
):
    """
    Endpoint nhận diện món ăn từ ảnh chụp (File) hoặc câu lệnh prompt (Form).
    Trả về: Tên món, độ tin cậy, khẩu phần ước tính và macro dinh dưỡng.
    """
    if not image and not prompt:
        raise HTTPException(status_code=400, detail="Vui lòng cung cấp ảnh chụp hoặc prompt mô tả món ăn.")

    # Mock response cho giai đoạn đầu của Iteration E1 (Sẽ thay bằng ONNX model thật)
    return {
        "status": "success",
        "prediction": {
            "dish_code": "pho_bo",
            "dish_name": "Phở Bò Tái Nạm (Bát Vừa)",
            "confidence": 0.985,
            "estimated_weight_g": 450,
            "nutrition": {
                "calories": 485,
                "protein_g": 32.5,
                "carb_g": 68.0,
                "fat_g": 11.2
            },
            "source": "model_inference" if image else "prompt_parsing"
        }
    }

if __name__ == "__main__":
    uvicorn.run("app:app", host="0.0.0.0", port=8000, reload=True)
