#!/bin/bash

echo "========================================"
echo "  Simpan Perubahan ke Git"
echo "========================================"
echo ""

# Check if git is installed
if ! command -v git &> /dev/null; then
    echo "ERROR: Git tidak terinstall!"
    echo "Silakan install Git terlebih dahulu."
    exit 1
fi

# Check if repository exists
if [ ! -d ".git" ]; then
    echo "ERROR: Repository Git belum diinisialisasi!"
    echo "Jalankan git_setup.sh terlebih dahulu."
    exit 1
fi

echo "[1/4] Mengecek status perubahan..."
git status --short
echo ""

echo "[2/4] Menambahkan semua perubahan..."
git add .
if [ $? -ne 0 ]; then
    echo "ERROR: Gagal menambahkan file!"
    exit 1
fi

echo ""
echo "[3/4] Membuat commit..."
read -p "Masukkan pesan commit (atau tekan Enter untuk default): " commit_msg
if [ -z "$commit_msg" ]; then
    commit_msg="Update: $(date '+%Y-%m-%d %H:%M:%S')"
fi

git commit -m "$commit_msg"
if [ $? -ne 0 ]; then
    echo "WARNING: Tidak ada perubahan untuk di-commit atau commit gagal."
    echo ""
    read -p "Lanjutkan ke push? (y/n): " continue
    if [ "$continue" != "y" ] && [ "$continue" != "Y" ]; then
        exit 0
    fi
else
    echo "Commit berhasil dibuat!"
    echo ""
fi

echo "[4/4] Mengecek remote repository..."
if ! git remote -v &> /dev/null || [ -z "$(git remote -v)" ]; then
    echo ""
    echo "INFO: Remote repository belum dikonfigurasi."
    echo ""
    echo "Untuk menambahkan remote repository:"
    echo "  git remote add origin [URL_REPOSITORY]"
    echo "  git branch -M main"
    echo "  git push -u origin main"
    echo ""
    exit 0
fi

echo ""
read -p "Push ke remote repository? (y/n): " push_confirm
if [ "$push_confirm" == "y" ] || [ "$push_confirm" == "Y" ]; then
    echo ""
    echo "Mengirim perubahan ke remote repository..."
    git push
    if [ $? -ne 0 ]; then
        echo ""
        echo "ERROR: Gagal push ke remote!"
        echo "Pastikan remote repository sudah dikonfigurasi dengan benar."
        echo ""
    else
        echo ""
        echo "========================================"
        echo "  Backup berhasil!"
        echo "========================================"
        echo ""
    fi
else
    echo ""
    echo "Perubahan sudah di-commit secara lokal."
    echo "Jalankan 'git push' secara manual untuk mengirim ke remote."
    echo ""
fi


