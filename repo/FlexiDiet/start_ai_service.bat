@echo off
chcp 65001 >nul
echo =====================================================================
echo  FlexiDiet: Khởi động Dịch vụ AI Vision Microservice (Python FastAPI)
echo  Mô hình: YOLOv10m VietFood-67 (ONNX Runtime)
echo =====================================================================

rem 1. Kiểm tra thư mục ai_service
if exist "ai_service\app.py" (
    rem Đang ở đúng thư mục repo/FlexiDiet
) else if exist "repo\FlexiDiet\ai_service\app.py" (
    cd repo\FlexiDiet
)

if not exist "ai_service\app.py" (
    echo [LỖI] Không tìm thấy thư mục 'ai_service'!
    pause
    exit /b 1
)

rem 2. Kiểm tra Python
where python >nul 2>nul
if %errorlevel% neq 0 (
    echo [LỖI] Không tìm thấy Python trên máy tính của bạn!
    echo Vui lòng cài đặt Python 3.10+ từ https://www.python.org/
    pause
    exit /b 1
)

echo [OK] Đã tìm thấy Python.
echo.
echo =====================================================================
echo  AI SERVICE ĐANG CHẠY TẠI: http://127.0.0.1:8001
echo  Tài liệu API (Swagger UI): http://127.0.0.1:8001/docs
echo.
echo  (Nhấn Ctrl + C để dừng dịch vụ khi muốn thoát)
echo =====================================================================
echo.

python ai_service\app.py
