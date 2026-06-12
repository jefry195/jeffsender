@echo off
title Jeffsender - Queue Worker
color 0A

echo ========================================
echo   JEFFSENDER - Queue Worker
echo   Status: RUNNING
echo ========================================
echo.
echo Queue worker berjalan... Jangan tutup window ini.
echo Tekan Ctrl+C untuk menghentikan.
echo.

:loop
C:\xampp\php\php.exe C:\xampp\htdocs\jeffsender\artisan queue:work --timeout=900 --tries=1 --sleep=3 --max-jobs=100 2>&1
echo.
echo [%time%] Queue worker berhenti, restart dalam 5 detik...
timeout /t 5 /nobreak >nul
goto loop
