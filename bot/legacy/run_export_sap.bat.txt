@echo off
REM ================================================================
REM DARSANA - Pembungkus Task Scheduler (jalan otomatis malam)
REM Alur: LANGKAH 1 export dari SAP  ->  LANGKAH 2 impor ke dashboard
REM ================================================================

REM Dapatkan lokasi folder saat ini (bot folder) dan folder parent (root project)
set "BOT_DIR=%~dp0"
REM Hapus trailing slash
set "BOT_DIR=%BOT_DIR:~0,-1%"
for %%I in ("%BOT_DIR%\..") do set "ROOT_DIR=%%~fI"

REM --- Ganti ini kalau 'php' tidak dikenali di CMD ---
REM Contoh XAMPP  : set PHP=C:\xampp\php\php.exe
REM Contoh Laragon: set PHP=C:\laragon\bin\php\php-8.x\php.exe
set PHP=php

set LOG=%BOT_DIR%\log_bot.txt

REM ---------- LANGKAH 0: Cek Jam Eksekusi ----------
%PHP% "%BOT_DIR%\check_time.php"
if not "%errorlevel%"=="0" (
    REM Bukan waktunya, langsung keluar secara diam-diam
    exit /b 0
)

echo ================================================== >> "%LOG%"
echo [%date% %time%] LANGKAH 1 - Mulai export SAP >> "%LOG%"

REM ---------- LANGKAH 1: export dari SAP ----------
cd /d "%BOT_DIR%"

REM Jalankan Watcher AutoIt
start "" "%BOT_DIR%\sap_security_watcher.exe"
timeout /t 2 /nobreak >nul

"C:\Windows\System32\cscript.exe" //nologo "%BOT_DIR%\export_sap.vbs" >> "%LOG%" 2>&1
set EXPORT_CODE=%errorlevel%

REM Matikan Watcher AutoIt
taskkill /IM sap_security_watcher.exe /F >nul 2>&1

echo [%date% %time%] Export selesai - exit code %EXPORT_CODE% >> "%LOG%"

REM Kalau export gagal, lewati impor
if not "%EXPORT_CODE%"=="0" goto :export_gagal

REM ---------- LANGKAH 2: impor ke dashboard ----------
echo [%date% %time%] LANGKAH 2 - Mulai impor ke dashboard >> "%LOG%"
cd /d "%ROOT_DIR%"
%PHP% artisan sap:import-latest >> "%LOG%" 2>&1
echo [%date% %time%] Impor selesai - exit code %errorlevel% >> "%LOG%"
goto :selesai

:export_gagal
echo [%date% %time%] Export GAGAL - impor dilewati >> "%LOG%"

:selesai
echo. >> "%LOG%"

