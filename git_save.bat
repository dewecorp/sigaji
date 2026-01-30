@echo off
title Script Backup & Push Git
color 0A

echo ===================================================
echo   SCRIPT AUTO BACKUP & PUSH GITHUB
echo   Repository: https://github.com/dewecorp/sigaji.git
echo ===================================================
echo.

REM 1. Konfigurasi Git Remote
echo [1/5] Mengkonfigurasi Remote Repository...
git remote remove origin >nul 2>&1
git remote add origin https://github.com/dewecorp/sigaji.git
if %errorlevel% neq 0 (
    echo Gagal mengkonfigurasi remote.
    pause
    exit /b
)
echo Remote origin diatur ke https://github.com/dewecorp/sigaji.git
echo.

REM 2. Input Pesan Commit
:INPUT_MSG
set /p commit_msg="Masukkan pesan commit: "
if "%commit_msg%"=="" goto INPUT_MSG

echo.
echo ===================================================
echo   KONFIRMASI
echo ===================================================
echo   Pesan Commit : %commit_msg%
echo   Branch       : master
echo   Remote       : https://github.com/dewecorp/sigaji.git
echo ===================================================
echo.

set /p confirm="Apakah anda yakin ingin melanjutkan? (y/n): "
if /i not "%confirm%"=="y" (
    echo Dibatalkan oleh pengguna.
    pause
    exit /b
)

REM 3. Eksekusi Git
echo.
echo [2/5] Menambahkan file (git add)...
git add .

echo [3/5] Melakukan commit...
git commit -m "%commit_msg%"

echo [4/5] Mengirim ke GitHub (git push)...
git push -u origin master
if %errorlevel% neq 0 (
    echo.
    echo [ERROR] Gagal melakukan push ke GitHub.
    echo Cek koneksi internet atau izin akses repository.
    pause
    exit /b
)

REM 4. Backup ZIP
echo.
echo [5/5] Membuat/Update Backup ZIP...
echo Sedang memproses file ZIP (ini mungkin memakan waktu)...

REM Menggunakan PowerShell untuk update zip
REM Exclude folder .git dan file zip itu sendiri
powershell -Command "Get-ChildItem -Path . -Exclude '.git','backup_source_code.zip' | Compress-Archive -DestinationPath 'backup_source_code.zip' -Update"

if %errorlevel% neq 0 (
    echo [WARNING] Gagal membuat/update file ZIP.
) else (
    echo Backup ZIP berhasil diperbarui: backup_source_code.zip
)

echo.
echo ===================================================
echo   PROSES SELESAI
echo ===================================================
pause
