$ErrorActionPreference = "Stop"
$PSScriptRoot = Split-Path -Parent -Path $MyInvocation.MyCommand.Definition

$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "Pencabutan gagal: Skrip harus dijalankan sebagai Administrator." -ForegroundColor Red
    exit 1
}

$taskName = "DarsanaSapBot"
$existingTask = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
if ($existingTask) {
    Unregister-ScheduledTask -TaskName $taskName -Confirm:$false -ErrorAction SilentlyContinue
    Write-Host "Scheduled Task '$taskName' berhasil dihapus." -ForegroundColor Green
} else {
    Write-Host "Scheduled Task '$taskName' tidak ditemukan." -ForegroundColor Yellow
}

Write-Host "Pencabutan selesai. Berkas, log, dan bot.env tidak dihapus." -ForegroundColor Green
