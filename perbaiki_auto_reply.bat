@echo off
title Jeffsender - Perbaikan Auto Reply Otomatis
color 0B

echo ========================================================
echo   JEFFSENDER - Perbaikan Auto Reply Otomatis
echo ========================================================
echo.
echo Menjalankan proses diagnosis dan perbaikan...
echo.

C:\xampp\php\php.exe C:\xampp\htdocs\jeffsender\artisan jeffsender:self-heal

echo.
echo ========================================================
echo   Perbaikan Selesai!
echo ========================================================
echo.
pause
