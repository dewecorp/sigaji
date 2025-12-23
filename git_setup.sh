#!/bin/bash

echo "========================================"
echo "  Setup Git Repository untuk Backup"
echo "========================================"
echo ""

# Check if git is installed
if ! command -v git &> /dev/null; then
    echo "ERROR: Git tidak terinstall!"
    echo "Silakan install Git terlebih dahulu."
    exit 1
fi

echo "[1/4] Mengecek repository Git..."
if [ -d ".git" ]; then
    echo "Repository Git sudah ada."
else
    echo "[2/4] Inisialisasi repository Git..."
    git init
    if [ $? -ne 0 ]; then
        echo "ERROR: Gagal inisialisasi Git!"
        exit 1
    fi
    echo "Repository Git berhasil diinisialisasi."
fi

echo ""
echo "[3/4] Menambahkan semua file ke staging..."
git add .
if [ $? -ne 0 ]; then
    echo "ERROR: Gagal menambahkan file!"
    exit 1
fi

echo ""
echo "[4/4] Membuat commit pertama..."
read -p "Masukkan pesan commit (atau tekan Enter untuk default): " commit_msg
if [ -z "$commit_msg" ]; then
    commit_msg="Initial commit - Backup proyek Sistem Gaji"
fi

git commit -m "$commit_msg"
if [ $? -ne 0 ]; then
    echo "ERROR: Gagal membuat commit!"
    exit 1
fi

echo ""
echo "========================================"
echo "  Setup selesai!"
echo "========================================"
echo ""
echo "Repository Git sudah siap."
echo ""
echo "Untuk menambahkan remote repository (GitHub/GitLab):"
echo "  1. Buat repository baru di GitHub/GitLab"
echo "  2. Jalankan: git remote add origin [URL_REPOSITORY]"
echo "  3. Jalankan: git push -u origin main"
echo ""
echo "Atau gunakan script git_save.sh untuk commit dan push."
echo ""



