'================================================================
' DARSANA - Export Realisasi Anggaran dari SAP ke folder
' Mode  : PINTAR -> pakai sesi yang sudah login; jika tidak ada, AUTO-LOGIN sendiri
' Syarat: PC nyala, layar TIDAK terkunci, aplikasi SAP Logon terbuka
'         (untuk auto-login: isi bagian PENGATURAN AUTO-LOGIN di bawah)
' Jalankan: cscript //nologo export_sap.vbs
'================================================================

'---------- PENGATURAN (boleh diubah) ----------
Dim exportFolder, filePrefix, reportTx, fmArea
exportFolder = "D:\Sap_export"      ' folder tujuan file
filePrefix   = "realisasi_"          ' awalan nama file
reportTx     = "ZFM001"              ' kode transaksi laporan (Budget Usage)
fmArea       = "1000"                ' Financial Management Area (ctxt$4FFIKRS)

'---------- PENGATURAN AUTO-LOGIN (ISI SENDIRI DI SERVER LOKAL) ----------
' Dipakai HANYA jika belum ada sesi SAP yang login (mode auto-login).
' Jika sudah ada yang login, skrip memakai sesi itu & nilai di bawah DIABAIKAN.
Dim sapSystem, sapClient, sapUser, sapPass, sapLang, logoutAfter
sapSystem   = "NAMA_SISTEM_SAP"     ' <-- nama entri di SAP Logon pad (mis. "PRD")
sapClient   = "300"                ' <-- dari recording-mu (client 300)
sapUser     = "A022C03029"          ' <-- dari recording-mu (user SAP)
sapPass     = "PASSWORD_SAP"        ' <-- password (AMANKAN file ini!)
sapLang     = "EN"                  ' <-- bahasa login (EN / ID)
logoutAfter = True                  ' <-- True: logout otomatis setelah export (jika bot yang login)

'---------- Buat nama file otomatis bertanggal ----------
' Contoh hasil: D:\Sap_export\realisasi_20260731_1003.csv
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

'---------- Mulai otomasi ----------
session.findById("wnd[0]").maximize

' 1) Buka laporan lewat KODE TRANSAKSI (lebih andal daripada double-click Favorites)
session.findById("wnd[0]/tbar[0]/okcd").text = "/n" & reportTx
session.findById("wnd[0]").sendVKey 0
If Err.Number <> 0 Then
    WScript.Echo "GAGAL: tidak bisa membuka transaksi " & reportTx & ". Cek kode transaksinya."
    WScript.Quit 2
End If
Err.Clear

' 2) Layar seleksi (pass 1): isi FM Area + Fund Center, lalu Execute (F8)
session.findById("wnd[0]/usr/ctxt$4FFIKRS").text = fmArea
session.findById("wnd[0]/usr/ctxt_4FFICTR-LOW").text  = "A022020000"
session.findById("wnd[0]/usr/ctxt_4FFICTR-HIGH").text = "A022020005"
session.findById("wnd[0]/usr/ctxt_4FFICTR-HIGH").setFocus
session.findById("wnd[0]/usr/ctxt_4FFICTR-HIGH").caretPosition = 10
session.findById("wnd[0]").sendVKey 8

' 3) Layar seleksi (pass 2): isi ulang FM Area + Fund Center
session.findById("wnd[0]/usr/ctxt$4FFIKRS").text = fmArea
session.findById("wnd[0]/usr/ctxt_4FFICTR-LOW").text  = "A022020000"
session.findById("wnd[0]/usr/ctxt_4FFICTR-HIGH").text = "A022020005"
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

'---------- Logout otomatis (HANYA jika BOT yang login) ----------
' Kalau tadi menempel ke sesi orang lain, TIDAK di-logout (biar tidak mengganggu user).
If botLoggedIn And logoutAfter Then
    session.findById("wnd[0]/tbar[0]/okcd").text = "/nex"
    session.findById("wnd[0]").sendVKey 0
    ' konfirmasi popup logoff bila muncul (aman walau tidak ada)
    If Not (session.findById("wnd[1]", False) Is Nothing) Then
        session.findById("wnd[1]/usr/btnSPOP-OPTION1").press
    End If
    Err.Clear
End If

'---------- Selesai ----------
WScript.Echo "SUKSES: file tersimpan di " & fullPath
WScript.Quit 0
