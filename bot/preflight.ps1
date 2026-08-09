$ErrorActionPreference = "Stop"
$PSScriptRoot = Split-Path -Parent -Path $MyInvocation.MyCommand.Definition

$pass = 0
$fail = 0

function Report-Result {
    param([bool]$Success, [string]$TestName, [string]$Message)
    if ($Success) {
        Write-Host "[LULUS] $($TestName): $Message" -ForegroundColor Green
        Set-Variable -Name pass -Value ($script:pass + 1) -Scope Script
    } else {
        Write-Host "[GAGAL] $($TestName): $Message" -ForegroundColor Red
        Set-Variable -Name fail -Value ($script:fail + 1) -Scope Script
    }
}

# 1. Versi PowerShell minimal 5.1 dan versi Windows. Tampilkan keduanya.
$psVer = $PSVersionTable.PSVersion
$osVer = (Get-CimInstance Win32_OperatingSystem).Caption
if ($psVer.Major -ge 5) {
    Report-Result $true "Versi PowerShell & Windows" "PS $($psVer.Major).$($psVer.Minor), $osVer"
} else {
    Report-Result $false "Versi PowerShell & Windows" "PS $($psVer.Major).$($psVer.Minor), minimal 5.1"
}

# 2. cscript.exe ada di C:\Windows\System32\cscript.exe.
$cscriptPath = "C:\Windows\System32\cscript.exe"
if (Test-Path $cscriptPath) {
    Report-Result $true "Cscript" "Ditemukan di $cscriptPath"
} else {
    Report-Result $false "Cscript" "Tidak ditemukan di $cscriptPath"
}

# 3. Folder SAP GUI
$sapDir1 = "C:\Program Files (x86)\SAP\FrontEnd\SAPgui"
$sapDir2 = "C:\Program Files\SAP\FrontEnd\SAPgui"
if ((Test-Path $sapDir1) -or (Test-Path $sapDir2)) {
    Report-Result $true "Folder SAP GUI" "Ditemukan"
} else {
    Report-Result $false "Folder SAP GUI" "Tidak ditemukan di Program Files"
}

# 4. Registry HKCU:\Software\SAP\SAPGUI Front\SAP Frontend Server\Security UserScripting = 1
$regPath = "HKCU:\Software\SAP\SAPGUI Front\SAP Frontend Server\Security"
$userScripting = $null
if (Test-Path $regPath) {
    $userScripting = (Get-ItemProperty -Path $regPath -Name "UserScripting" -ErrorAction SilentlyContinue).UserScripting
}
if ($userScripting -eq 1) {
    Report-Result $true "SAP GUI Scripting" "UserScripting = 1"
} else {
    Report-Result $false "SAP GUI Scripting" "SAP GUI Scripting belum aktif di sisi klien."
}

# 5. bot\bot.env ada, memuat DASHBOARD_URL dan BOT_TOKEN, dan keduanya tidak kosong.
$envFile = Join-Path $PSScriptRoot "bot.env"
$dashboardUrl = $null
$botToken = $null
if (Test-Path $envFile) {
    Get-Content $envFile | ForEach-Object {
        if ($_ -match '^\s*([^#=\s]+)\s*=\s*(.*)$') {
            if ($Matches[1] -eq 'DASHBOARD_URL') { $dashboardUrl = $Matches[2].Trim() }
            if ($Matches[1] -eq 'BOT_TOKEN') { $botToken = $Matches[2].Trim() }
        }
    }
}
if ($dashboardUrl -and $botToken) {
    $maskedToken = ""
    if ($botToken.Length -ge 6) {
        $maskedToken = $botToken.Substring(0,6) + "******"
    } else {
        $maskedToken = "******"
    }
    Report-Result $true "Konfigurasi bot.env" "URL: $dashboardUrl, Token: $maskedToken"
} else {
    Report-Result $false "Konfigurasi bot.env" "Berkas bot.env tidak ada, atau DASHBOARD_URL/BOT_TOKEN kosong."
}

# 6. GET {DASHBOARD_URL}/api/sap/settings dengan header
$exportFolder = $null
if ($dashboardUrl -and $botToken) {
    $apiUrl = $dashboardUrl.TrimEnd('/') + "/api/sap/settings"
    try {
        $response = Invoke-RestMethod -Uri $apiUrl -Method Get -Headers @{ "Authorization" = "Bearer $botToken" } -ErrorAction Stop
        Report-Result $true "Koneksi API" "HTTP 200 OK"
        $exportFolder = $response.exportFolder
    } catch {
        if ($_.Exception.Response.StatusCode -eq "Unauthorized") {
            Report-Result $false "Koneksi API" "Token salah atau belum di-clear di server."
        } else {
            Report-Result $false "Koneksi API" "Dashboard tidak dapat dihubungi."
        }
    }
} else {
    Report-Result $false "Koneksi API" "Dilewati karena bot.env tidak valid"
}

# 7. exportFolder
if ($null -ne $exportFolder -and $exportFolder.Trim() -ne "") {
    if (-not (Test-Path $exportFolder)) {
        try {
            New-Item -ItemType Directory -Force -Path $exportFolder | Out-Null
        } catch { }
    }
    if (Test-Path $exportFolder) {
        $testFile = Join-Path $exportFolder "test_write.tmp"
        try {
            "test" | Out-File $testFile -ErrorAction Stop
            Remove-Item $testFile -ErrorAction Stop
            Report-Result $true "Folder Export" "Dapat ditulis ($exportFolder)"
        } catch {
            Report-Result $false "Folder Export" "Folder export ada tapi tidak dapat ditulis."
        }
    } else {
        Report-Result $false "Folder Export" "Folder export tidak dapat dibuat."
    }
} else {
    Report-Result $false "Folder Export" "Folder Export belum diisi. Buka halaman Pengaturan Bot SAP di dashboard."
}

# 8. Folder bot\logs dapat ditulis.
$logsDir = Join-Path $PSScriptRoot "logs"
if (-not (Test-Path $logsDir)) {
    try { New-Item -ItemType Directory -Force -Path $logsDir | Out-Null } catch { }
}
if (Test-Path $logsDir) {
    $testFile = Join-Path $logsDir "test_write.tmp"
    try {
        "test" | Out-File $testFile -ErrorAction Stop
        Remove-Item $testFile -ErrorAction Stop
        Report-Result $true "Folder Logs" "Dapat ditulis"
    } catch {
        Report-Result $false "Folder Logs" "Folder logs tidak dapat ditulis."
    }
} else {
    Report-Result $false "Folder Logs" "Folder logs tidak dapat dibuat."
}

# 9. Tampilkan isi bot\BOT_VERSION dan nama komputer ($env:COMPUTERNAME).
$versionFile = Join-Path $PSScriptRoot "BOT_VERSION"
$botVersion = "1.0.0"
if (Test-Path $versionFile) {
    $botVersion = (Get-Content $versionFile).Trim()
}
Report-Result $true "Info Lingkungan" "Versi Bot: $botVersion, Komputer: $env:COMPUTERNAME"

Write-Host "=========================================="
Write-Host "Ringkasan: $pass LULUS, $fail GAGAL"
if ($fail -gt 0) {
    exit 1
}
exit 0
