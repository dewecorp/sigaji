@echo off
echo ========================================
echo   Setup Git Repository untuk Backup
echo ========================================
echo.

REM Check if git is installed
git --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Git tidak terinstall!
    echo Silakan install Git terlebih dahulu dari: https://git-scm.com/download/win
    pause
    exit /b 1
)

echo [1/4] Mengecek repository Git...
if exist .git (
    echo Repository Git sudah ada.
) else (
    echo [2/4] Inisialisasi repository Git...
    git init
    if %errorlevel% neq 0 (
        echo ERROR: Gagal inisialisasi Git!
        pause
        exit /b 1
    )
    echo Repository Git berhasil diinisialisasi.
)

echo.
echo [3/4] Menambahkan semua file ke staging...
git add .
if %errorlevel% neq 0 (
    echo ERROR: Gagal menambahkan file!
    pause
    exit /b 1
)

echo.
echo [4/4] Membuat commit pertama...
set /p commit_msg="Masukkan pesan commit (atau tekan Enter untuk default): "
if "%commit_msg%"=="" set commit_msg=Initial commit - Backup proyek Sistem Gaji

git commit -m "%commit_msg%"
if %errorlevel% neq 0 (
    echo ERROR: Gagal membuat commit!
    pause
    exit /b 1
)

echo.
echo ========================================
echo   Setup selesai!
echo ========================================
echo.
echo Repository Git sudah siap.
echo.
echo Untuk menambahkan remote repository (GitHub/GitLab):
echo   1. Buat repository baru di GitHub/GitLab
echo   2. Jalankan: git remote add origin [URL_REPOSITORY]
echo   3. Jalankan: git push -u origin main
echo.
echo Atau gunakan script git_save.bat untuk commit dan push.
echo.
pause



