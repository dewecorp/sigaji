-- Schema for Honor Ekstrakurikuler Module

-- Table: ekstrakurikuler
CREATE TABLE IF NOT EXISTS ekstrakurikuler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jenis_ekstrakurikuler VARCHAR(100) NOT NULL,
    waktu VARCHAR(50) NOT NULL,
    jumlah_pertemuan INT NOT NULL DEFAULT 0,
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
    jumlah_honor DECIMAL(15,2) NOT NULL DEFAULT 0,
    aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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


