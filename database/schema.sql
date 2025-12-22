-- Database Schema for SIGaji
-- Salary Payment System for MI Sultan Fattah Sukosono

CREATE DATABASE IF NOT EXISTS sistem_gaji CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistem_gaji;

-- Table: users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    foto VARCHAR(255) DEFAULT 'default.jpg',
    role ENUM('admin', 'bendahara') DEFAULT 'bendahara',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: settings
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_madrasah VARCHAR(200) DEFAULT 'MI Sultan Fattah Sukosono',
    nama_kepala VARCHAR(100),
    nama_bendahara VARCHAR(100),
    logo VARCHAR(255) DEFAULT 'logo.png',
    periode_aktif VARCHAR(7) DEFAULT NULL,
    jumlah_periode INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: guru
CREATE TABLE IF NOT EXISTS guru (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    tmt INT,
    masa_bakti INT,
    jumlah_jam_mengajar INT DEFAULT 0,
    no_hp VARCHAR(20),
    jabatan VARCHAR(100),
    status_pegawai ENUM('PNS', 'Honor', 'Kontrak') DEFAULT 'Honor',
    foto VARCHAR(255) DEFAULT 'default.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: gaji_pokok
CREATE TABLE IF NOT EXISTS gaji_pokok (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guru_id INT NOT NULL,
    jumlah DECIMAL(15,2) NOT NULL DEFAULT 0,
    periode VARCHAR(7) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE CASCADE,
    INDEX idx_periode (periode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: tunjangan
CREATE TABLE IF NOT EXISTS tunjangan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_tunjangan VARCHAR(100) NOT NULL,
    keterangan TEXT,
    aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: tunjangan_detail
CREATE TABLE IF NOT EXISTS tunjangan_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guru_id INT NOT NULL,
    tunjangan_id INT NOT NULL,
    jumlah DECIMAL(15,2) NOT NULL DEFAULT 0,
    periode VARCHAR(7) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE CASCADE,
    FOREIGN KEY (tunjangan_id) REFERENCES tunjangan(id) ON DELETE CASCADE,
    INDEX idx_periode (periode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: potongan
CREATE TABLE IF NOT EXISTS potongan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_potongan VARCHAR(100) NOT NULL,
    keterangan TEXT,
    aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: potongan_detail
CREATE TABLE IF NOT EXISTS potongan_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guru_id INT NOT NULL,
    potongan_id INT NOT NULL,
    jumlah DECIMAL(15,2) NOT NULL DEFAULT 0,
    periode VARCHAR(7) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE CASCADE,
    FOREIGN KEY (potongan_id) REFERENCES potongan(id) ON DELETE CASCADE,
    INDEX idx_periode (periode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: legger_gaji
CREATE TABLE IF NOT EXISTS legger_gaji (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guru_id INT NOT NULL,
    periode VARCHAR(7) NOT NULL,
    gaji_pokok DECIMAL(15,2) DEFAULT 0,
    total_tunjangan DECIMAL(15,2) DEFAULT 0,
    total_potongan DECIMAL(15,2) DEFAULT 0,
    gaji_bersih DECIMAL(15,2) DEFAULT 0,
    tanda_tangan TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE CASCADE,
    UNIQUE KEY unique_guru_periode (guru_id, periode),
    INDEX idx_periode (periode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: legger_detail (for dynamic columns)
CREATE TABLE IF NOT EXISTS legger_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    legger_id INT NOT NULL,
    jenis ENUM('tunjangan', 'potongan') NOT NULL,
    item_id INT NOT NULL,
    nama_item VARCHAR(100) NOT NULL,
    jumlah DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (legger_id) REFERENCES legger_gaji(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: activities
CREATE TABLE IF NOT EXISTS activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    username VARCHAR(50),
    activity TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin123)
-- Hash password untuk 'admin123' yang benar
INSERT INTO users (username, password, nama_lengkap, email, role) 
VALUES ('admin', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Administrator', 'admin@sigaji.com', 'admin')
ON DUPLICATE KEY UPDATE password = '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy';

-- Insert default settings
INSERT INTO settings (nama_madrasah, nama_kepala, nama_bendahara) 
VALUES ('MI Sultan Fattah Sukosono', '', '');

-- Table: ekstrakurikuler
CREATE TABLE IF NOT EXISTS ekstrakurikuler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jenis_ekstrakurikuler VARCHAR(100) NOT NULL,
    waktu VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: pembina
CREATE TABLE IF NOT EXISTS pembina (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_pembina VARCHAR(100) NOT NULL,
    ekstrakurikuler_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ekstrakurikuler_id) REFERENCES ekstrakurikuler(id) ON DELETE CASCADE,
    INDEX idx_ekstrakurikuler (ekstrakurikuler_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: honor
CREATE TABLE IF NOT EXISTS honor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jabatan VARCHAR(100) NOT NULL,
    pembina_id INT NULL,
    jumlah_honor DECIMAL(15,2) NOT NULL DEFAULT 0,
    jumlah_pertemuan INT NOT NULL DEFAULT 0,
    aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pembina_id) REFERENCES pembina(id) ON DELETE SET NULL,
    INDEX idx_pembina (pembina_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: legger_honor
CREATE TABLE IF NOT EXISTS legger_honor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pembina_id INT NOT NULL,
    ekstrakurikuler_id INT NOT NULL,
    honor_id INT NOT NULL,
    jumlah_pertemuan INT NOT NULL DEFAULT 0,
    jumlah_honor_per_pertemuan DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_honor DECIMAL(15,2) NOT NULL DEFAULT 0,
    periode VARCHAR(7) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pembina_id) REFERENCES pembina(id) ON DELETE CASCADE,
    FOREIGN KEY (ekstrakurikuler_id) REFERENCES ekstrakurikuler(id) ON DELETE CASCADE,
    FOREIGN KEY (honor_id) REFERENCES honor(id) ON DELETE CASCADE,
    INDEX idx_periode (periode),
    UNIQUE KEY unique_pembina_ekstra_periode (pembina_id, ekstrakurikuler_id, periode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

