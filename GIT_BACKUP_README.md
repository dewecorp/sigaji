# Panduan Backup ke Git

Dokumen ini menjelaskan cara menggunakan script Git untuk backup proyek Sistem Gaji.

## 📋 Prasyarat

1. **Install Git** (jika belum terinstall)
   - Windows: Download dari https://git-scm.com/download/win
   - Linux/Mac: `sudo apt-get install git` atau `brew install git`

2. **Akun GitHub/GitLab** (opsional, untuk backup online)
   - Buat akun di https://github.com atau https://gitlab.com
   - Buat repository baru (public atau private)

## 🚀 Setup Awal (Hanya Sekali)

### Windows:
```batch
git_setup.bat
```

### Linux/Mac:
```bash
chmod +x git_setup.sh
./git_setup.sh
```

Script ini akan:
- ✅ Mengecek apakah Git terinstall
- ✅ Inisialisasi repository Git (jika belum ada)
- ✅ Menambahkan semua file ke staging
- ✅ Membuat commit pertama

## 💾 Menyimpan Perubahan (Setiap Kali)

### Windows:
```batch
git_save.bat
```

### Linux/Mac:
```bash
chmod +x git_save.sh
./git_save.sh
```

Script ini akan:
- ✅ Mengecek status perubahan
- ✅ Menambahkan semua perubahan
- ✅ Membuat commit dengan pesan
- ✅ Push ke remote repository (jika dikonfigurasi)

## 🔗 Menambahkan Remote Repository (GitHub/GitLab)

Setelah membuat repository di GitHub/GitLab, jalankan:

```bash
# Ganti [URL_REPOSITORY] dengan URL repository Anda
# Contoh: https://github.com/username/sistem-gaji.git
git remote add origin [URL_REPOSITORY]

# Set branch utama
git branch -M main

# Push pertama kali
git push -u origin main
```

## 📝 File yang Diabaikan (.gitignore)

File berikut **TIDAK** akan di-backup ke Git:
- `/vendor/` - Dependencies Composer
- `config/database.php` - Konfigurasi database (sensitive)
- `config/config.php` - Konfigurasi aplikasi (sensitive)
- `*.log` - File log
- `backup/*.sql` - File backup database
- `assets/img/users/*` - Foto pengguna
- File temporary dan cache

**⚠️ PENTING:** File konfigurasi database (`config/database.php`) tidak di-backup karena berisi informasi sensitif. Pastikan Anda menyimpan informasi koneksi database secara terpisah!

## 🔄 Workflow Harian

1. Setelah membuat perubahan pada kode
2. Jalankan `git_save.bat` (Windows) atau `./git_save.sh` (Linux/Mac)
3. Masukkan pesan commit (atau tekan Enter untuk default)
4. Pilih 'y' untuk push ke remote (jika sudah dikonfigurasi)
5. Selesai! Data sudah tersimpan

## 🆘 Troubleshooting

### Error: "Git tidak terinstall"
- Install Git dari https://git-scm.com/download/win
- Restart terminal/command prompt setelah install

### Error: "Repository Git belum diinisialisasi"
- Jalankan `git_setup.bat` atau `git_setup.sh` terlebih dahulu

### Error: "Gagal push ke remote"
- Pastikan remote repository sudah dikonfigurasi: `git remote -v`
- Pastikan Anda sudah login ke GitHub/GitLab
- Cek koneksi internet

### File tidak ter-backup
- Cek apakah file ada di `.gitignore`
- File sensitif (database config) sengaja tidak di-backup untuk keamanan

## 📌 Tips

1. **Commit secara rutin** - Setelah setiap perubahan penting
2. **Pesan commit yang jelas** - Contoh: "Fix: Perbaikan bug simpan tunjangan"
3. **Backup database secara terpisah** - Gunakan fitur backup di aplikasi
4. **Jangan commit file sensitif** - Database config, password, dll

## 🔐 Keamanan

- ✅ File konfigurasi database (`config/database.php`) **TIDAK** di-backup
- ✅ File log dan temporary diabaikan
- ✅ Upload files (foto user) diabaikan
- ⚠️ Pastikan repository private jika menyimpan kode sensitif

## 📞 Bantuan

Jika ada masalah, cek:
- Status Git: `git status`
- Log commit: `git log`
- Remote repository: `git remote -v`

---

**Selamat! Proyek Anda sekarang aman dengan backup Git! 🎉**



