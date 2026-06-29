@echo off
title Diagnostik & Pengujian Sistem WhatsML Doorenz
color 0b
echo ==============================================================
echo       MEMULAI DIAGNOSTIK & SELF-HEAL SISTEM WHATSML
echo ==============================================================
echo.
echo [1/2] Menjalankan Pemeriksaan & Perbaikan Otomatis...
C:\xampp\php\php.exe artisan jeffsender:self-heal
echo.
echo --------------------------------------------------------------
echo.
echo [2/2] Menjalankan Pengujian Alur & Fitur End-to-End (E2E)...
C:\xampp\php\php.exe scratch\test_all_flows.php
echo.
echo ==============================================================
echo DIAGNOSTIK & PENGUJIAN SELESAI
echo ==============================================================
pause
