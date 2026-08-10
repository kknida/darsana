'================================================================
' DARSANA - Export Realisasi Anggaran dari SAP ke folder
' Mode  : PINTAR -> pakai sesi yang sudah login; jika tidak ada, AUTO-LOGIN sendiri
' Syarat: PC nyala, layar TIDAK terkunci, aplikasi SAP Logon terbuka
'
' >>> PENTING <<<
' Pengaturan (user, password, folder, dll) TIDAK lagi ditulis di file ini.
' Semua nilai dibaca dari file : runtime_config.ini
' Letakkan runtime_config.ini di FOLDER YANG SAMA dengan script ini.
' File itu otomatis dibuat oleh runner.
'
' Daftar Exit Code:
' 0 = sukses
' 1 = export gagal atau CSV kosong
' 2 = logout gagal, tapi export sukses
' 3 = konfigurasi tidak valid termasuk exportFolder kosong
' 4 = kredensial SAP kosong
' 5 = folder export tidak dapat dibuat
'
' Jalankan: cscript //nologo export_sap.vbs
'================================================================

'---------- Siapkan FileSystemObject + temukan lokasi config ----------
Dim fso, scriptFolder, configPath
Set fso = CreateObject("Scripting.FileSystemObject")
scriptFolder = fso.GetParentFolderName(WScript.ScriptFullName)
configPath   = scriptFolder & "\runtime_config.ini"

If Not fso.FileExists(configPath) Then
    WScript.Echo "GAGAL 0: file pengaturan tidak ditemukan: " & configPath
    WScript.Quit 3
End If

'---------- Baca semua nilai dari runtime_config.ini ----------
Dim cfg
Set cfg = BacaConfig(configPath)

Dim exportFolder, filePrefix, reportTx, fmArea
Dim sapSystem, sapClient, sapUser, sapPass, sapLang, logoutAfter
Dim ficLow, ficHigh

exportFolder = Ambil(cfg, "exportFolder", "")
filePrefix   = Ambil(cfg, "filePrefix", "realisasi_")
reportTx     = Ambil(cfg, "reportTx", "ZFM001")
fmArea       = Ambil(cfg, "fmArea", "1000")
sapSystem    = Ambil(cfg, "sapSystem", "")
sapClient    = Ambil(cfg, "sapClient", "")
sapUser      = Ambil(cfg, "sapUser", "")
sapPass      = Ambil(cfg, "sapPass", "")
sapLang      = Ambil(cfg, "sapLang", "EN")
logoutAfter  = Ambil(cfg, "logoutAfter", "1")
logoutAfter  = (Trim(LCase(logoutAfter)) = "1" Or Trim(LCase(logoutAfter)) = "true" Or Trim(LCase(logoutAfter)) = "yes")
ficLow       = Ambil(cfg, "fundCenterLow", "A022020000")
ficHigh      = Ambil(cfg, "fundCenterHigh", "A022020005")

'---------- Validasi pengaturan wajib ----------
If sapSystem = "" Or sapClient = "" Or sapUser = "" Or sapPass = "" Then
    WScript.Echo "GAGAL 0: pengaturan belum lengkap di runtime_config.ini."
    WScript.Echo "         Wajib diisi: sapSystem, sapClient, sapUser, sapPass."
    WScript.Quit 4
End If

If exportFolder = "" Then
    WScript.Echo "ERROR_CONFIG=exportFolder kosong. Isi lewat halaman Pengaturan Bot SAP."
    WScript.Quit 3
End If

'---------- Buat folder tujuan (rekursif) ----------
Function CreateFolderRecursive(fsoObj, folderPath)
    If Not fsoObj.FolderExists(folderPath) Then
        Dim parentPath
        parentPath = fsoObj.GetParentFolderName(folderPath)
        If parentPath <> "" And Not fsoObj.FolderExists(parentPath) Then
            CreateFolderRecursive fsoObj, parentPath
        End If
        On Error Resume Next
        fsoObj.CreateFolder folderPath
        If Err.Number <> 0 Then
            WScript.Echo "ERROR_FOLDER=" & folderPath & " tidak dapat dibuat: " & Err.Description
            WScript.Quit 5
        End If
        On Error GoTo 0
    End If
End Function

CreateFolderRecursive fso, exportFolder

'---------- Buat nama file otomatis bertanggal ----------
' Contoh hasil: realisasi_20260731_1003.csv
Dim d, stamp, fullPath
d = Now
stamp = Year(d) & Right("0" & Month(d),2) & Right("0" & Day(d),2) _
      & "_" & Right("0" & Hour(d),2) & Right("0" & Minute(d),2)
fullPath = exportFolder & "\" & filePrefix & stamp & ".csv"

'---------- Hubungkan ke SAP GUI (attach jika sudah login; jika tidak: AUTO-LOGIN) ----------
On Error Resume Next
Dim SapGuiAuto, application, connection, session, botLoggedIn
botLoggedIn = False
Set connection = Nothing
Set session = Nothing

Set SapGuiAuto = GetObject("SAPGUI")
If Err.Number <> 0 Or Not IsObject(SapGuiAuto) Then
    WScript.Echo "GAGAL 1: SAP GUI / SAP Logon tidak ditemukan. Pastikan aplikasi SAP Logon terbuka."
    WScript.Quit 1
End If
Set application = SapGuiAuto.GetScriptingEngine
Err.Clear

' Coba pakai sesi yang SUDAH login (mode attach)
If application.Children.Count > 0 Then
    Set connection = application.Children(0)
    If connection.Children.Count > 0 Then Set session = connection.Children(0)
End If
Err.Clear

' Jika belum ada sesi -> BUKA koneksi + AUTO-LOGIN
If session Is Nothing Then
    Set connection = application.OpenConnection(sapSystem, True)
    If Err.Number <> 0 Or connection Is Nothing Then
        WScript.Echo "GAGAL 1: tidak bisa membuka koneksi ke '" & sapSystem & "'. Cek nama sistem di SAP Logon pad."
        WScript.Quit 1
    End If
    Set session = connection.Children(0)
    session.findById("wnd[0]/usr/txtRSYST-MANDT").text = sapClient
    session.findById("wnd[0]/usr/txtRSYST-BNAME").text = sapUser
    session.findById("wnd[0]/usr/pwdRSYST-BCODE").text = sapPass
    session.findById("wnd[0]/usr/txtRSYST-LANGU").text = sapLang
    session.findById("wnd[0]").sendVKey 0
    botLoggedIn = True
    Err.Clear
    ' Popup "sudah login di tempat lain" (multiple logon) bila muncul
    If Not (session.findById("wnd[1]", False) Is Nothing) Then
        session.findById("wnd[1]/usr/radMULTI_LOGON_OPT1").select
        session.findById("wnd[1]/tbar[0]/btn[0]").press
    End If
    Err.Clear
End If

If session Is Nothing Then
    WScript.Echo "GAGAL 1: sesi SAP tidak tersedia (login gagal / kredensial salah)."
    WScript.Quit 1
End If
Err.Clear

'---------- Cek: masih di layar login? berarti login GAGAL (user/pass/client salah) ----------
Dim masihLogin
masihLogin = False
If Not (session.findById("wnd[0]/usr/txtRSYST-BNAME", False) Is Nothing) Then masihLogin = True
Err.Clear
If masihLogin Then
    WScript.Echo "GAGAL 1: masih di layar login SAP -> kemungkinan username / password / client salah. Cek pengaturan di halaman Settings."
    WScript.Quit 1
End If

'---------- Mulai otomasi ----------
session.findById("wnd[0]").maximize

' 1) Buka laporan lewat KODE TRANSAKSI (lebih andal daripada double-click Favorites)
session.findById("wnd[0]/tbar[0]/okcd").text = "/n" & reportTx
session.findById("wnd[0]").sendVKey 0
If Err.Number <> 0 Then
    WScript.Echo "GAGAL 2: tidak bisa membuka transaksi " & reportTx & ". Cek kode transaksinya."
    WScript.Quit 2
End If
Err.Clear

' 2) Layar seleksi (pass 1): isi FM Area + Fund Center, lalu Execute (F8)
session.findById("wnd[0]/usr/ctxt$4FFIKRS").text = fmArea
session.findById("wnd[0]/usr/ctxt_4FFICTR-LOW").text  = ficLow
session.findById("wnd[0]/usr/ctxt_4FFICTR-HIGH").text = ficHigh
session.findById("wnd[0]/usr/ctxt_4FFICTR-HIGH").setFocus
session.findById("wnd[0]/usr/ctxt_4FFICTR-HIGH").caretPosition = 10
session.findById("wnd[0]").sendVKey 8

' 3) Layar seleksi (pass 2): isi ulang FM Area + Fund Center
session.findById("wnd[0]/usr/ctxt$4FFIKRS").text = fmArea
session.findById("wnd[0]/usr/ctxt_4FFICTR-LOW").text  = ficLow
session.findById("wnd[0]/usr/ctxt_4FFICTR-HIGH").text = ficHigh
session.findById("wnd[0]/usr/ctxt_4FFICTR-HIGH").setFocus
session.findById("wnd[0]/usr/ctxt_4FFICTR-HIGH").caretPosition = 10

' 4) Atur tipe file (Spreadsheet) -> OK -> Execute (F8)
session.findById("wnd[0]/tbar[1]/btn[7]").press
session.findById("wnd[1]/usr/subOI_DOC_TYPE:SAPLGRWOS:0210/cmbGRWOS_S_SCREEN_FIELDS-FILE_TYPE").setFocus
session.findById("wnd[1]/tbar[0]/btn[0]").press
session.findById("wnd[0]").sendVKey 8

' 5) Export ke Local File
session.findById("wnd[0]/tbar[1]/btn[14]").press

' 6) >>> Folder + nama file diatur OTOMATIS di sini <<<
session.findById("wnd[1]/usr/ctxtLGRWO-OUT_FILE").text = fullPath
session.findById("wnd[1]/usr/ctxtLGRWO-OUT_FILE").setFocus
session.findById("wnd[1]/usr/ctxtLGRWO-OUT_FILE").caretPosition = Len(fullPath)
session.findById("wnd[1]/tbar[0]/btn[0]").press

' 7) Konfirmasi popup bila muncul (aman walau popup tidak ada)
session.findById("wnd[2]/usr/btnSPOP-VAROPTION2").press
session.findById("wnd[1]/usr/btnSPOP-VAROPTION1").press
Err.Clear

'---------- Verifikasi file BENAR-BENAR ada sebelum lapor sukses ----------
WScript.Sleep 1500   ' beri waktu SAP menuliskan file ke disk
Dim exportBerhasil
exportBerhasil = VerifyExport(fullPath, fso)

If Not exportBerhasil Then
    WScript.Echo "GAGAL 1: proses selesai tetapi file CSV kosong atau tidak ditemukan di " & fullPath
    WScript.Quit 1
End If

WScript.Echo "EXPORTED_FILE=" & fullPath

'---------- Logout otomatis (HANYA jika BOT yang login & export sukses) ----------
If botLoggedIn And logoutAfter Then
    DoLogout session
End If

'---------- Selesai ----------
WScript.Echo "SUKSES: file tersimpan di " & fullPath
WScript.Quit 0

'================================================================
' FUNGSI BANTU
'================================================================
Function BacaConfig(path)
    Dim f, baris, pos, k, v, dict
    Set dict = CreateObject("Scripting.Dictionary")
    dict.CompareMode = 1   ' TextCompare -> nama kunci tidak case-sensitive
    Set f = fso.OpenTextFile(path, 1, False)   ' 1 = ForReading
    Do Until f.AtEndOfStream
        baris = Trim(f.ReadLine)
        ' lewati baris kosong, komentar (; atau #), dan header seksi [..]
        If baris <> "" And Left(baris,1) <> ";" And Left(baris,1) <> "#" And Left(baris,1) <> "[" Then
            pos = InStr(baris, "=")
            If pos > 0 Then
                k = Trim(Left(baris, pos - 1))
                v = Trim(Mid(baris, pos + 1))
                If dict.Exists(k) Then
                    dict(k) = v
                Else
                    dict.Add k, v
                End If
            End If
        End If
    Loop
    f.Close
    Set BacaConfig = dict
End Function

Function Ambil(dict, key, standar)
    If dict.Exists(key) Then
        Ambil = dict(key)
    Else
        Ambil = standar
    End If
End Function

Sub DoLogout(sess)
    On Error Resume Next
    ' Coba /n dulu
    sess.findById("wnd[0]/tbar[0]/okcd").Text = "/n"
    sess.findById("wnd[0]").sendVKey 0
    
    ' Kemudian /nex
    sess.findById("wnd[0]/tbar[0]/okcd").Text = "/nex"
    sess.findById("wnd[0]").sendVKey 0
    
    ' Tangani popup logoff jika ada
    If Not (sess.findById("wnd[1]", False) Is Nothing) Then
        ' Klik tombol Yes / Log off
        sess.findById("wnd[1]/usr/btnSPOP-OPTION1").press
    End If
    
    WScript.Sleep 1500
    Set sess = Nothing
    Set connection = Nothing
    Set application = Nothing
    Set SapGuiAuto = Nothing
    
    If Err.Number <> 0 Then WScript.Quit 2
End Sub

Function VerifyExport(path, fsoObj)
    If Not fsoObj.FileExists(path) Then
        VerifyExport = False
        Exit Function
    End If
    
    Dim f
    Set f = fsoObj.GetFile(path)
    If f.Size > 0 Then
        VerifyExport = True
    Else
        VerifyExport = False
    End If
End Function
