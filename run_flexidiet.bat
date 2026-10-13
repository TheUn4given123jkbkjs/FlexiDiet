@echo off
chcp 65001 >nul
title FlexiDiet - Smart Platform Controller

echo =====================================================================
echo  FlexiDiet: Trung Tâm Khởi Chạy Tự Động (All-In-One Unified Runner)
echo =====================================================================
echo.

rem 1. Xác định thư mục repo/FlexiDiet
set "BASE_DIR=%~dp0"
if exist "%BASE_DIR%repo\FlexiDiet\public\index.html" (
    cd /d "%BASE_DIR%repo\FlexiDiet"
) else if exist "%BASE_DIR%public\index.html" (
    cd /d "%BASE_DIR%"
) else (
    echo [LỖI] Không tìm thấy thư mục FlexiDiet hợp lệ!
    pause
    exit /b 1
)

rem 2. Tự động tìm PHP
set "PHP_BIN="
where php >nul 2>nul
if %errorlevel% equ 0 set "PHP_BIN=php"
if "%PHP_BIN%"=="" if exist "D:\xampp\php\php.exe" set "PHP_BIN=D:\xampp\php\php.exe"
if "%PHP_BIN%"=="" if exist "C:\xampp\php\php.exe" set "PHP_BIN=C:\xampp\php\php.exe"
if "%PHP_BIN%"=="" if exist "E:\xampp\php\php.exe" set "PHP_BIN=E:\xampp\php\php.exe"

if "%PHP_BIN%"=="" (
    echo [LỖI] Không tìm thấy PHP! Vui lòng cài đặt XAMPP tại ổ C: hoặc D:.
    pause
    exit /b 1
)

rem 3. Tự động tìm MySQL
set "MYSQL_BIN="
where mysql >nul 2>nul
if %errorlevel% equ 0 set "MYSQL_BIN=mysql"
if "%MYSQL_BIN%"=="" if exist "D:\xampp\mysql\bin\mysql.exe" set "MYSQL_BIN=D:\xampp\mysql\bin\mysql.exe"
if "%MYSQL_BIN%"=="" if exist "C:\xampp\mysql\bin\mysql.exe" set "MYSQL_BIN=C:\xampp\mysql\bin\mysql.exe"
if "%MYSQL_BIN%"=="" if exist "E:\xampp\mysql\bin\mysql.exe" set "MYSQL_BIN=E:\xampp\mysql\bin\mysql.exe"

if "%MYSQL_BIN%"=="" (
    echo [LỖI] Không tìm thấy mysql.exe! Vui lòng kiểm tra XAMPP.
    pause
    exit /b 1
)

rem 4. Tự động tìm Python
set "PYTHON_BIN="
where python >nul 2>nul
if %errorlevel% equ 0 set "PYTHON_BIN=python"

echo [OK] Môi trường thực thi:
echo   - PHP:    %PHP_BIN%
echo   - MySQL:  %MYSQL_BIN%
if not "%PYTHON_BIN%"=="" (
    echo   - Python: %PYTHON_BIN%
) else (
    echo   - Python: Chưa phát hiện (chức năng AI camera sẽ tạm tắt)
)
echo.

rem 5. Kiểm tra kết nối MySQL & Khởi tạo CSDL nếu chưa có
echo [1/3] Đang kiểm tra cơ sở dữ liệu 'flexidiet'...
"%MYSQL_BIN%" -u root -e "USE flexidiet; SELECT COUNT(*) FROM dishes;" >nul 2>nul
if %errorlevel% neq 0 (
    echo [THÔNG BÁO] Database 'flexidiet' chưa có hoặc chưa đủ dữ liệu. Đang tự động nạp...
    "%MYSQL_BIN%" -u root -e "CREATE DATABASE IF NOT EXISTS flexidiet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    if %errorlevel% neq 0 (
        echo [LỖI] Không thể kết nối MySQL! Hãy đảm bảo bạn đã START MySQL trên XAMPP Control Panel.
        pause
        exit /b 1
    )
    "%MYSQL_BIN%" -u root --default-character-set=utf8mb4 flexidiet -e "source database/reload_all.sql;"
    if %errorlevel% neq 0 (
        echo [LỖI] Có lỗi khi nạp dữ liệu database/reload_all.sql!
        pause
        exit /b 1
    )
    echo [OK] Đã tạo thành công CSDL 'flexidiet' với đầy đủ 18 bảng và 7 bộ dữ liệu mẫu!
) else (
    echo [OK] CSDL 'flexidiet' đã tồn tại và sẵn sàng.
)
echo.

rem 6. Kiểm tra thư viện Python AI (nếu có Python)
if not "%PYTHON_BIN%"=="" (
    echo [2/3] Đang kiểm tra thư viện AI Vision (FastAPI, ONNX Runtime)...
    "%PYTHON_BIN%" -c "import fastapi, uvicorn, onnxruntime, numpy, PIL" >nul 2>nul
    if %errorlevel% neq 0 (
        echo [THÔNG BÁO] Đang tự động cài đặt thư viện AI từ requirements.txt...
        pip install -r ai_service\requirements.txt
        if %errorlevel% neq 0 (
            echo [CẢNH BÁO] Không thể tự động cài thư viện AI. Tạm thời bỏ qua AI Service.
            set "PYTHON_BIN="
        ) else (
            echo [OK] Đã cài đặt xong toàn bộ thư viện AI.
        )
    ) else (
        echo [OK] Thư viện AI đã sẵn sàng.
    )
)
echo.

rem 7. Dọn dẹp tiến trình cũ ở cổng 8000 & 8001 (nếu có)
powershell -Command "Get-Process -Name php -ErrorAction SilentlyContinue | Stop-Process -Force" >nul 2>nul
powershell -Command "Get-NetTCPConnection -LocalPort 8001 -State Listen -ErrorAction SilentlyContinue | ForEach-Object { Stop-Process -Id $_.OwningProcess -Force }" >nul 2>nul

rem 8. Khởi động Web Server và AI Service
echo [3/3] Đang khởi động các dịch vụ nền tảng...
start "FlexiDiet - Web Server (:8000)" "%PHP_BIN%" -S localhost:8000 -t public
if not "%PYTHON_BIN%"=="" (
    start "FlexiDiet - AI Vision (:8001)" "%PYTHON_BIN%" ai_service\app.py
)

rem Chờ 1.5 giây để server kịp bind port
timeout /t 2 /nobreak >nul

rem 9. Tự động mở trình duyệt
start http://localhost:8000

:MENU_LOOP
cls
echo =====================================================================
echo  FlexiDiet Platform - All-In-One Unified Controller
echo =====================================================================
echo.
echo  [Trạng thái các dịch vụ]
echo    * Web App / API:      http://localhost:8000
echo    * AI Microservice:    http://127.0.0.1:8001 (Tài liệu API: :8001/docs)
echo    * Cơ sở dữ liệu:     MySQL 127.0.0.1:3306 (flexidiet)
echo.
echo  [Tài khoản kiểm thử]
echo    * Member: demo@flexidiet.vn  / Demo@123456
echo    * Admin:  admin@flexidiet.vn / Admin@123456
echo.
echo =====================================================================
echo  PHÍM ĐIỀU KHIỂN NHANH:
echo    [O] Mở lại trình duyệt Web (http://localhost:8000)
echo    [R] Reset & Nạp lại toàn bộ CSDL (reload_all.sql)
echo    [Q] Dừng tất cả dịch vụ và Thoát
echo =====================================================================
echo.

choice /C ORQ /N /M "Bấm phím [O / R / Q] để thực hiện: "
if errorlevel 3 goto :STOP_ALL
if errorlevel 2 goto :RELOAD_DB
if errorlevel 1 goto :OPEN_BROWSER

:OPEN_BROWSER
start http://localhost:8000
goto :MENU_LOOP

:RELOAD_DB
echo.
echo [ĐANG NẠP LẠI DATABASE]...
"%MYSQL_BIN%" -u root --default-character-set=utf8mb4 flexidiet -e "source database/reload_all.sql;"
if %errorlevel% equ 0 (
    echo [THÀNH CÔNG] Đã reset toàn bộ database về trạng thái mặc định ban đầu!
) else (
    echo [LỖI] Không thể nạp lại CSDL. Vui lòng kiểm tra MySQL trên XAMPP.
)
timeout /t 3 >nul
goto :MENU_LOOP

:STOP_ALL
echo.
echo Đang dừng tất cả tiến trình Web và AI...
powershell -Command "Get-Process -Name php -ErrorAction SilentlyContinue | Stop-Process -Force" >nul 2>nul
powershell -Command "Get-NetTCPConnection -LocalPort 8001 -State Listen -ErrorAction SilentlyContinue | ForEach-Object { Stop-Process -Id $_.OwningProcess -Force }" >nul 2>nul
echo [OK] Đã dừng toàn bộ hệ thống FlexiDiet an toàn.
timeout /t 1 >nul
exit /b 0
