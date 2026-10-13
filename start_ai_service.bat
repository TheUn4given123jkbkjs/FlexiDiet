@echo off
chcp 65001 >nul
if exist "repo\FlexiDiet\start_ai_service.bat" (
    call repo\FlexiDiet\start_ai_service.bat
) else (
    echo [LỖI] Không tìm thấy file repo\FlexiDiet\start_ai_service.bat
    pause
)
