@echo off
cd /d "%~dp0"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0runner.ps1"
exit /b %ERRORLEVEL%
