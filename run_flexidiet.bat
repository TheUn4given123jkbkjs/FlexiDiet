@echo off
chcp 65001 >nul
title FlexiDiet - Smart Platform Controller

echo =====================================================================
echo  FlexiDiet: Trung Tam Khoi Chay Tu Dong (All-In-One Unified Runner)
echo =====================================================================
echo.

rem 1. Xac dinh thu muc chua source code
set "BASE_DIR=%~dp0"
if exist "%BASE_DIR%repo\FlexiDiet\public\index.html" (
    cd /d "%BASE_DIR%repo\FlexiDiet"
    goto :DIR_FOUND
)
if exist "%BASE_DIR%public\index.html" (
    cd /d "%BASE_DIR%"
    goto :DIR_FOUND
)

echo [LOI] Khong tim thay thu muc FlexiDiet hop le!
pause
exit /b 1

:DIR_FOUND

rem 2. Tu dong tim PHP
set "PHP_BIN="
where php >nul 2>nul
if %errorlevel% equ 0 set "PHP_BIN=php"
for %%D in (C D E F) do if "%PHP_BIN%"=="" if exist "%%D:\xampp\php\php.exe" set "PHP_BIN=%%D:\xampp\php\php.exe"

if "%PHP_BIN%"=="" (
    echo [LOI] Khong tim thay PHP! Vui long kiem tra XAMPP da cai dat o o C: hoac D:.
    pause
    exit /b 1
)

rem 3. Tu dong tim MySQL
set "MYSQL_BIN="
where mysql >nul 2>nul
if %errorlevel% equ 0 set "MYSQL_BIN=mysql"
for %%D in (C D E F) do if "%MYSQL_BIN%"=="" if exist "%%D:\xampp\mysql\bin\mysql.exe" set "MYSQL_BIN=%%D:\xampp\mysql\bin\mysql.exe"

if "%MYSQL_BIN%"=="" (
    echo [LOI] Khong tim thay mysql.exe! Vui long kiem tra XAMPP da cai dat o o C: hoac D:.
    pause
    exit /b 1
)

rem Tim bo cai XAMPP de co the tu dong khoi dong MySQL
set "XAMPP_HOME="
for %%D in (C D E F) do if exist "%%D:\xampp\mysql_start.bat" set "XAMPP_HOME=%%D:\xampp"
set "MYSQL_START="
if not "%XAMPP_HOME%"=="" set "MYSQL_START=%XAMPP_HOME%\mysql_start.bat"

rem 4. Tu dong tim Python
set "PYTHON_BIN="
where python >nul 2>nul
if %errorlevel% equ 0 set "PYTHON_BIN=python"

echo [OK] Phat hien moi truong:
echo   - PHP:    %PHP_BIN%
echo   - MySQL:  %MYSQL_BIN%
if not "%PYTHON_BIN%"=="" (
    echo   - Python: %PYTHON_BIN%
) else (
    echo   - Python: Chua tim thay (Dich vu AI camera se tam tat)
)
echo.

rem 5. Kiem tra MySQL va Khoi tao CSDL neu chua co
echo [1/3] Dang kiem tra co so du lieu flexidiet...
"%MYSQL_BIN%" -u root -e "SELECT 1;" >nul 2>nul
if errorlevel 1 (
    if "%MYSQL_START%"=="" goto :MYSQL_START_FAILED
    echo [THONG BAO] MySQL chua chay. Dang tu dong khoi dong XAMPP MySQL...
    start "" /min "%MYSQL_START%"
    set "MYSQL_WAIT=0"
    goto :WAIT_FOR_MYSQL
)
goto :MYSQL_READY

:WAIT_FOR_MYSQL
ping -n 2 127.0.0.1 >nul
"%MYSQL_BIN%" -u root -e "SELECT 1;" >nul 2>nul
if not errorlevel 1 goto :MYSQL_READY
set /a MYSQL_WAIT+=1
if %MYSQL_WAIT% lss 30 goto :WAIT_FOR_MYSQL

:MYSQL_START_FAILED
echo.
echo [LOI] Khong the khoi dong MySQL. Hay kiem tra XAMPP hoac bat MySQL thu cong.
pause
exit /b 1

:MYSQL_READY
"%MYSQL_BIN%" -u root -e "USE flexidiet; SELECT COUNT(*) FROM dishes;" >nul 2>nul
if %errorlevel% equ 0 (
    echo [OK] Co so du lieu flexidiet da san sang.
    goto :DB_CHECK_DONE
)

echo [THONG BAO] Database flexidiet chua co hoac chua co du lieu. Dang tu dong khoi tao...
"%MYSQL_BIN%" -u root -e "CREATE DATABASE IF NOT EXISTS flexidiet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if %errorlevel% neq 0 (
    echo.
    echo [LOI] Khong the ket noi MySQL! Hay dam bao ban da START MySQL tren XAMPP Control Panel.
    pause
    exit /b 1
)

"%MYSQL_BIN%" -u root --default-character-set=utf8mb4 flexidiet -e "source database/reload_all.sql;"
if %errorlevel% neq 0 (
    echo.
    echo [LOI] Co loi khi nap du lieu seeds tu database/reload_all.sql!
    pause
    exit /b 1
)
echo [OK] Da khoi tao thanh cong database flexidiet voi day du 18 bang va 7 bo seeds!

:DB_CHECK_DONE
echo.

rem 6. Kiem tra thu vien Python AI
if "%PYTHON_BIN%"=="" goto :SKIP_AI_CHECK
echo [2/3] Dang kiem tra thu vien AI Vision (FastAPI, ONNX Runtime)...
"%PYTHON_BIN%" -c "import fastapi, uvicorn, onnxruntime, numpy, PIL" >nul 2>nul
if %errorlevel% equ 0 (
    echo [OK] Thu vien AI da san sang.
    goto :AI_CHECK_DONE
)

echo [THONG BAO] Dang tu dong cai dat cac thu vien AI tu requirements.txt...
pip install -r ai_service\requirements.txt
if %errorlevel% neq 0 (
    echo [CANH BAO] Khong the cai thu vien AI. Tam thoi bo qua dich vu AI.
    set "PYTHON_BIN="
) else (
    echo [OK] Da cai dat xong thu vien AI.
)

:AI_CHECK_DONE
:SKIP_AI_CHECK
echo.

rem 7. Don dep tien trinh cu o cong 8000 va 8001
powershell -NoProfile -Command "Get-Process -Name php -ErrorAction SilentlyContinue | Stop-Process -Force" >nul 2>nul
powershell -NoProfile -Command "Get-NetTCPConnection -LocalPort 8001 -State Listen -ErrorAction SilentlyContinue | ForEach-Object { Stop-Process -Id $_.OwningProcess -Force }" >nul 2>nul

rem 8. Khoi dong Web Server va AI Service
echo [3/3] Dang khoi dong cac dich vu nen tang...
start "FlexiDiet - PHP Web Server (Port 8000)" "%PHP_BIN%" -S localhost:8000 -t public
if not "%PYTHON_BIN%"=="" (
    start "FlexiDiet - AI Vision Microservice (Port 8001)" "%PYTHON_BIN%" ai_service\app.py
)

rem Cho web server san sang truoc khi mo trinh duyet
set "WEB_WAIT=0"
:WAIT_FOR_WEB
powershell -NoProfile -Command "try { $r = Invoke-WebRequest -UseBasicParsing -Uri 'http://localhost:8000' -TimeoutSec 2; if ($r.StatusCode -ge 200 -and $r.StatusCode -lt 500) { exit 0 } } catch {}; exit 1" >nul 2>nul
if not errorlevel 1 goto :WEB_READY
set /a WEB_WAIT+=1
if %WEB_WAIT% lss 15 (
    ping -n 2 127.0.0.1 >nul
    goto :WAIT_FOR_WEB
)
echo.
echo [LOI] PHP Web Server khong phan hoi tren cong 8000.
echo Hay kiem tra PHP va dam bao cong 8000 khong bi ung dung khac su dung.
pause
goto :STOP_ALL

rem 9. Tu dong mo trinh duyet Web
:WEB_READY
start http://localhost:8000

:MENU_LOOP
cls
echo =====================================================================
echo  FlexiDiet Platform - All-In-One Unified Controller
echo =====================================================================
echo.
echo  [Trang thai cac dich vu]
echo    * Web App / API:      http://localhost:8000
echo    * AI Microservice:    http://127.0.0.1:8001 (Tai lieu API: :8001/docs)
echo    * Co so du lieu:      MySQL 127.0.0.1:3306 (flexidiet)
echo.
echo  [Tai khoan kiem thu]
echo    * Member: demo@flexidiet.vn  / Demo@123456
echo    * Admin:  admin@flexidiet.vn / Admin@123456
echo.
echo =====================================================================
echo  PHIM DIEU KHIEN NHANH:
echo    [O] Mo lai trinh duyet Web (http://localhost:8000)
echo    [R] Reset va Nap lai toan bo CSDL (reload_all.sql)
echo    [Q] Dung tat ca dich vu va Thoat
echo =====================================================================
echo.

choice /C ORQ /N /M "Bam phim [O / R / Q] de thuc hien: "
if errorlevel 3 goto :STOP_ALL
if errorlevel 2 goto :RELOAD_DB
if errorlevel 1 goto :OPEN_BROWSER

:OPEN_BROWSER
start http://localhost:8000
goto :MENU_LOOP

:RELOAD_DB
echo.
echo [DANG NAP LAI DATABASE]...
"%MYSQL_BIN%" -u root --default-character-set=utf8mb4 flexidiet -e "source database/reload_all.sql;"
if %errorlevel% equ 0 (
    echo [THANH CONG] Da reset toan bo database ve trang thai mac dinh!
) else (
    echo [LOI] Khong the nap lai CSDL. Vui long kiem tra MySQL tren XAMPP.
)
ping -n 3 127.0.0.1 >nul
goto :MENU_LOOP

:STOP_ALL
echo.
echo Dang dung tat ca tien trinh Web va AI...
powershell -NoProfile -Command "Get-Process -Name php -ErrorAction SilentlyContinue | Stop-Process -Force" >nul 2>nul
powershell -NoProfile -Command "Get-NetTCPConnection -LocalPort 8001 -State Listen -ErrorAction SilentlyContinue | ForEach-Object { Stop-Process -Id $_.OwningProcess -Force }" >nul 2>nul
echo [OK] Da dung toan bo he thong FlexiDiet an toan.
ping -n 2 127.0.0.1 >nul
exit /b 0
