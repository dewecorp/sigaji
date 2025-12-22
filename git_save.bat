@echo off
echo ========================================
echo   Simpan Perubahan ke Git
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

REM Check if repository exists
if not exist .git (
    echo ERROR: Repository Git belum diinisialisasi!
    echo Jalankan git_setup.bat terlebih dahulu.
    pause
    exit /b 1
)

echo [1/4] Mengecek status perubahan...
git status --short
echo.

echo [2/4] Menambahkan semua perubahan...
git add .
if %errorlevel% neq 0 (
    echo ERROR: Gagal menambahkan file!
    pause
    exit /b 1
)

echo.
echo [3/4] Membuat commit...
set /p commit_msg="Masukkan pesan commit (atau tekan Enter untuk default): "
if "%commit_msg%"=="" (
    for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set datetime=%%I
    set commit_msg=Update: %date% %time%
)

git commit -m "%commit_msg%"
if %errorlevel% neq 0 (
    echo WARNING: Tidak ada perubahan untuk di-commit atau commit gagal.
    echo.
    set /p continue="Lanjutkan ke push? (y/n): "
    if /i not "%continue%"=="y" (
        exit /b 0
    )
) else (
    echo Commit berhasil dibuat!
    echo.
)

echo [4/4] Mengecek remote repository...
git remote -v >nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo INFO: Remote repository belum dikonfigurasi.
    echo.
    echo Untuk menambahkan remote repository:
    echo   git remote add origin [URL_REPOSITORY]
    echo   git branch -M main
    echo   git push -u origin main
    echo.
    pause
    exit /b 0
)

echo.
set /p push_confirm="Push ke remote repository? (y/n): "
if /i "%push_confirm%"=="y" (
    echo.
    echo Mengirim perubahan ke remote repository...
    git push
    if %errorlevel% neq 0 (
        echo.
        echo ERROR: Gagal push ke remote!
        echo Pastikan remote repository sudah dikonfigurasi dengan benar.
        echo.
    ) else (
        echo.
        echo ========================================
        echo   Backup berhasil!
        echo ========================================
        echo.
    )
) else (
    echo.
    echo Perubahan sudah di-commit secara lokal.
    echo Jalankan 'git push' secara manual untuk mengirim ke remote.
    echo.
)

pause


