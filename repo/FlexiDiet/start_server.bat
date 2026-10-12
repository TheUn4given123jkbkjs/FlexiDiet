@echo off
chcp 65001 >nul
echo =====================================================================
echo  FlexiDiet: Khoi dong Web Server (Auto-Detect PHP)
echo =====================================================================

rem 1. Tim thu muc repo chua public
if exist "public\index.html" (
    rem Dang o dung thu muc repo/FlexiDiet
) else if exist "repo\FlexiDiet\public\index.html" (
    cd repo\FlexiDiet
)

if not exist "public\index.html" (
    echo [LOI] Khong tim thay thu muc 'public'! Vui long chay file tu thu muc repo/FlexiDiet.
    pause
    exit /b 1
)

rem 2. Tu dong tim duong dan PHP
set "PHP_BIN="
where php >nul 2>nul
if %errorlevel% equ 0 (
    set "PHP_BIN=php"
    goto :PHP_FOUND
)
if exist "D:\xampp\php\php.exe" (
    set "PHP_BIN=D:\xampp\php\php.exe"
    goto :PHP_FOUND
)
if exist "C:\xampp\php\php.exe" (
    set "PHP_BIN=C:\xampp\php\php.exe"
    goto :PHP_FOUND
)
if exist "E:\xampp\php\php.exe" (
    set "PHP_BIN=E:\xampp\php\php.exe"
    goto :PHP_FOUND
)

echo [LOI] Khong tim thay php.exe! Vui long kiem tra XAMPP da cai dat o o C: hoac D:.
pause
exit /b 1

:PHP_FOUND
echo [OK] Da tim thay PHP: %PHP_BIN%
echo.
echo =====================================================================
echo  WEB SERVER DANG CHAY TAI: http://localhost:8000
echo.
echo  Tai khoan thu nghiem:
echo    - Member: demo@flexidiet.vn  / Demo@123456
echo    - Admin:  admin@flexidiet.vn / Admin@123456
echo.
echo  (Nhan Ctrl + C de dung may chu khi muon thoat)
echo =====================================================================
echo.

"%PHP_BIN%" -S localhost:8000 -t public
