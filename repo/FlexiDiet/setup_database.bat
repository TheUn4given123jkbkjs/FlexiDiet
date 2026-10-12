@echo off
chcp 65001 >nul
echo =====================================================================
echo  FlexiDiet: Tu dong thiet lap Database (Auto-Detect XAMPP)
echo =====================================================================

rem 1. Tim thu muc database
if exist "database\reload_all.sql" (
    cd database
) else if exist "repo\FlexiDiet\database\reload_all.sql" (
    cd repo\FlexiDiet\database
)

if not exist "reload_all.sql" (
    echo [LOI] Khong tim thay file reload_all.sql!
    pause
    exit /b 1
)

rem 2. Tu dong tim duong dan MySQL
set "MYSQL_BIN="
where mysql >nul 2>nul
if %errorlevel% equ 0 (
    set "MYSQL_BIN=mysql"
    goto :MYSQL_FOUND
)
if exist "D:\xampp\mysql\bin\mysql.exe" (
    set "MYSQL_BIN=D:\xampp\mysql\bin\mysql.exe"
    goto :MYSQL_FOUND
)
if exist "C:\xampp\mysql\bin\mysql.exe" (
    set "MYSQL_BIN=C:\xampp\mysql\bin\mysql.exe"
    goto :MYSQL_FOUND
)
if exist "E:\xampp\mysql\bin\mysql.exe" (
    set "MYSQL_BIN=E:\xampp\mysql\bin\mysql.exe"
    goto :MYSQL_FOUND
)

echo [LOI] Khong tim thay mysql.exe! Vui long kiem tra XAMPP da cai dat o o C: hoac D:.
pause
exit /b 1

:MYSQL_FOUND
echo [1/2] Dang kiem tra va khoi tao database 'flexidiet'...
"%MYSQL_BIN%" -u root -e "CREATE DATABASE IF NOT EXISTS flexidiet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if %errorlevel% neq 0 (
    echo.
    echo [LOI] Khong the ket noi MySQL! Hay dam bao ban da START MySQL tren XAMPP Control Panel.
    pause
    exit /b 1
)

echo [2/2] Dang nap toan bo schema va 7 bo seeds du lieu mau...
"%MYSQL_BIN%" -u root --default-character-set=utf8mb4 flexidiet -e "source reload_all.sql;"
if %errorlevel% neq 0 (
    echo.
    echo [LOI] Co loi xay ra khi nap du lieu seeds vao MySQL!
    pause
    exit /b 1
)

echo.
echo =====================================================================
echo [THANH CONG] Database 'flexidiet' da san sang voi day du du lieu!
echo =====================================================================
pause
