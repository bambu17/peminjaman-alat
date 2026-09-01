-- =====================================================================
-- DATABASE: db_peminjaman_alat
-- Aplikasi Peminjaman Alat - UKK RPL 2025/2026
-- Import file ini melalui phpMyAdmin (Laragon) atau via terminal:
--   mysql -u root -p < database.sql
-- =====================================================================

DROP DATABASE IF EXISTS db_peminjaman_alat;
CREATE DATABASE db_peminjaman_alat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_peminjaman_alat;

-- ---------------------------------------------------------------------
-- TABEL: users
-- Menyimpan seluruh akun (admin, petugas, peminjam)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    no_hp VARCHAR(20) DEFAULT NULL,
    role ENUM('admin','petugas','peminjam') NOT NULL DEFAULT 'peminjam',
    status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TABEL: kategori
-- ---------------------------------------------------------------------
CREATE TABLE kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TABEL: alat
-- ---------------------------------------------------------------------
CREATE TABLE alat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_alat VARCHAR(20) NOT NULL UNIQUE,
    nama_alat VARCHAR(150) NOT NULL,
    kategori_id INT NOT NULL,
    jumlah_total INT NOT NULL DEFAULT 0,
    jumlah_tersedia INT NOT NULL DEFAULT 0,
    kondisi ENUM('baik','rusak_ringan','rusak_berat') NOT NULL DEFAULT 'baik',
    lokasi_simpan VARCHAR(100) DEFAULT NULL,
    deskripsi TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_alat_kategori FOREIGN KEY (kategori_id) REFERENCES kategori(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TABEL: peminjaman (header)
-- ---------------------------------------------------------------------
CREATE TABLE peminjaman (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_peminjaman VARCHAR(30) NOT NULL UNIQUE,
    peminjam_id INT NOT NULL,
    petugas_id INT DEFAULT NULL,
    tanggal_pinjam DATE NOT NULL,
    tanggal_rencana_kembali DATE NOT NULL,
    keperluan VARCHAR(255) DEFAULT NULL,
    status ENUM('menunggu','disetujui','ditolak','dikembalikan') NOT NULL DEFAULT 'menunggu',
    catatan_petugas VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_peminjaman_peminjam FOREIGN KEY (peminjam_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_peminjaman_petugas FOREIGN KEY (petugas_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TABEL: detail_peminjaman (item alat yang dipinjam per transaksi)
-- ---------------------------------------------------------------------
CREATE TABLE detail_peminjaman (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peminjaman_id INT NOT NULL,
    alat_id INT NOT NULL,
    jumlah INT NOT NULL DEFAULT 1,
    CONSTRAINT fk_detail_peminjaman FOREIGN KEY (peminjaman_id) REFERENCES peminjaman(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_detail_alat FOREIGN KEY (alat_id) REFERENCES alat(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TABEL: pengembalian
-- ---------------------------------------------------------------------
CREATE TABLE pengembalian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peminjaman_id INT NOT NULL,
    petugas_id INT DEFAULT NULL,
    tanggal_kembali DATE NOT NULL,
    kondisi_alat ENUM('baik','rusak_ringan','rusak_berat','hilang') NOT NULL DEFAULT 'baik',
    telat_hari INT NOT NULL DEFAULT 0,
    denda DECIMAL(10,2) NOT NULL DEFAULT 0,
    catatan VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pengembalian_peminjaman FOREIGN KEY (peminjaman_id) REFERENCES peminjaman(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_pengembalian_petugas FOREIGN KEY (petugas_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TABEL: log_aktifitas
-- ---------------------------------------------------------------------
CREATE TABLE log_aktifitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    aktivitas VARCHAR(255) NOT NULL,
    waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TABEL: pengaturan (tarif denda per hari, dsb)
-- ---------------------------------------------------------------------
CREATE TABLE pengaturan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_setting VARCHAR(50) NOT NULL UNIQUE,
    nilai VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

INSERT INTO pengaturan (nama_setting, nilai) VALUES ('tarif_denda_per_hari', '5000');

-- =====================================================================
-- FUNCTION: fn_hitung_denda
-- Menghitung denda berdasarkan selisih hari keterlambatan x tarif x jumlah alat
-- =====================================================================
DELIMITER $$
CREATE FUNCTION fn_hitung_denda(
    p_tanggal_rencana DATE,
    p_tanggal_kembali DATE,
    p_jumlah_alat INT
) RETURNS DECIMAL(10,2)
DETERMINISTIC
BEGIN
    DECLARE v_telat INT DEFAULT 0;
    DECLARE v_tarif DECIMAL(10,2) DEFAULT 0;
    DECLARE v_denda DECIMAL(10,2) DEFAULT 0;

    IF p_tanggal_kembali > p_tanggal_rencana THEN
        SET v_telat = DATEDIFF(p_tanggal_kembali, p_tanggal_rencana);
    ELSE
        SET v_telat = 0;
    END IF;

    SELECT CAST(nilai AS DECIMAL(10,2)) INTO v_tarif
    FROM pengaturan WHERE nama_setting = 'tarif_denda_per_hari' LIMIT 1;

    SET v_denda = v_telat * v_tarif * p_jumlah_alat;

    RETURN v_denda;
END$$
DELIMITER ;

-- =====================================================================
-- STORED PROCEDURE: sp_setujui_peminjaman
-- Petugas menyetujui peminjaman -> kurangi stok alat, ubah status
-- Menggunakan transaksi (COMMIT / ROLLBACK)
-- =====================================================================
DELIMITER $$
CREATE PROCEDURE sp_setujui_peminjaman(
    IN p_peminjaman_id INT,
    IN p_petugas_id INT,
    IN p_catatan VARCHAR(255)
)
BEGIN
    DECLARE v_cukup INT DEFAULT 0;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Cek apakah semua alat pada detail peminjaman stoknya masih cukup
    SELECT COUNT(*) INTO v_cukup
    FROM detail_peminjaman dp
    JOIN alat a ON a.id = dp.alat_id
    WHERE dp.peminjaman_id = p_peminjaman_id
      AND a.jumlah_tersedia < dp.jumlah;

    IF v_cukup > 0 THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stok alat tidak mencukupi untuk menyetujui peminjaman ini';
    ELSE
        -- Kurangi stok tersedia
        UPDATE alat a
        JOIN detail_peminjaman dp ON dp.alat_id = a.id
        SET a.jumlah_tersedia = a.jumlah_tersedia - dp.jumlah
        WHERE dp.peminjaman_id = p_peminjaman_id;

        -- Update status peminjaman
        UPDATE peminjaman
        SET status = 'disetujui',
            petugas_id = p_petugas_id,
            catatan_petugas = p_catatan
        WHERE id = p_peminjaman_id;

        COMMIT;
    END IF;
END$$
DELIMITER ;

-- =====================================================================
-- STORED PROCEDURE: sp_tolak_peminjaman
-- =====================================================================
DELIMITER $$
CREATE PROCEDURE sp_tolak_peminjaman(
    IN p_peminjaman_id INT,
    IN p_petugas_id INT,
    IN p_catatan VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    UPDATE peminjaman
    SET status = 'ditolak',
        petugas_id = p_petugas_id,
        catatan_petugas = p_catatan
    WHERE id = p_peminjaman_id;

    COMMIT;
END$$
DELIMITER ;

-- =====================================================================
-- STORED PROCEDURE: sp_kembalikan_alat
-- Proses pengembalian alat + hitung denda otomatis + kembalikan stok
-- =====================================================================
DELIMITER $$
CREATE PROCEDURE sp_kembalikan_alat(
    IN p_peminjaman_id INT,
    IN p_petugas_id INT,
    IN p_tanggal_kembali DATE,
    IN p_kondisi_alat VARCHAR(20),
    IN p_catatan VARCHAR(255)
)
BEGIN
    DECLARE v_tanggal_rencana DATE;
    DECLARE v_total_jumlah INT DEFAULT 0;
    DECLARE v_telat INT DEFAULT 0;
    DECLARE v_denda DECIMAL(10,2) DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT tanggal_rencana_kembali INTO v_tanggal_rencana
    FROM peminjaman WHERE id = p_peminjaman_id;

    SELECT COALESCE(SUM(jumlah),0) INTO v_total_jumlah
    FROM detail_peminjaman WHERE peminjaman_id = p_peminjaman_id;

    IF p_tanggal_kembali > v_tanggal_rencana THEN
        SET v_telat = DATEDIFF(p_tanggal_kembali, v_tanggal_rencana);
    ELSE
        SET v_telat = 0;
    END IF;

    SET v_denda = fn_hitung_denda(v_tanggal_rencana, p_tanggal_kembali, v_total_jumlah);

    -- Kembalikan stok alat (hanya jika kondisi bukan 'hilang')
    IF p_kondisi_alat <> 'hilang' THEN
        UPDATE alat a
        JOIN detail_peminjaman dp ON dp.alat_id = a.id
        SET a.jumlah_tersedia = a.jumlah_tersedia + dp.jumlah
        WHERE dp.peminjaman_id = p_peminjaman_id;
    END IF;

    INSERT INTO pengembalian
        (peminjaman_id, petugas_id, tanggal_kembali, kondisi_alat, telat_hari, denda, catatan)
    VALUES
        (p_peminjaman_id, p_petugas_id, p_tanggal_kembali, p_kondisi_alat, v_telat, v_denda, p_catatan);

    UPDATE peminjaman SET status = 'dikembalikan' WHERE id = p_peminjaman_id;

    COMMIT;

    SELECT v_telat AS telat_hari, v_denda AS denda;
END$$
DELIMITER ;

-- =====================================================================
-- TRIGGER: trg_alat_jumlah_tersedia
-- Saat alat baru ditambahkan, jumlah_tersedia otomatis = jumlah_total
-- =====================================================================
DELIMITER $$
CREATE TRIGGER trg_alat_before_insert
BEFORE INSERT ON alat
FOR EACH ROW
BEGIN
    IF NEW.jumlah_tersedia IS NULL OR NEW.jumlah_tersedia = 0 THEN
        SET NEW.jumlah_tersedia = NEW.jumlah_total;
    END IF;
END$$
DELIMITER ;

-- =====================================================================
-- TRIGGER: trg_log_peminjaman_baru
-- Setiap ada pengajuan peminjaman baru, otomatis tercatat di log aktifitas
-- =====================================================================
DELIMITER $$
CREATE TRIGGER trg_log_peminjaman_baru
AFTER INSERT ON peminjaman
FOR EACH ROW
BEGIN
    INSERT INTO log_aktifitas (user_id, aktivitas)
    VALUES (NEW.peminjam_id, CONCAT('Mengajukan peminjaman dengan kode ', NEW.kode_peminjaman));
END$$
DELIMITER ;

-- =====================================================================
-- TRIGGER: trg_log_status_peminjaman
-- Mencatat log setiap kali status peminjaman berubah
-- =====================================================================
DELIMITER $$
CREATE TRIGGER trg_log_status_peminjaman
AFTER UPDATE ON peminjaman
FOR EACH ROW
BEGIN
    IF NEW.status <> OLD.status THEN
        INSERT INTO log_aktifitas (user_id, aktivitas)
        VALUES (NEW.petugas_id, CONCAT('Peminjaman ', NEW.kode_peminjaman, ' diubah status menjadi ', NEW.status));
    END IF;
END$$
DELIMITER ;

-- =====================================================================
-- VIEW: v_peminjaman_detail (mempermudah laporan / query gabungan)
-- =====================================================================
CREATE VIEW v_peminjaman_detail AS
SELECT
    p.id, p.kode_peminjaman, p.tanggal_pinjam, p.tanggal_rencana_kembali,
    p.status, u.nama_lengkap AS nama_peminjam, u.username AS username_peminjam,
    pt.nama_lengkap AS nama_petugas,
    GROUP_CONCAT(CONCAT(a.nama_alat, ' (', dp.jumlah, ')') SEPARATOR ', ') AS daftar_alat
FROM peminjaman p
JOIN users u ON u.id = p.peminjam_id
LEFT JOIN users pt ON pt.id = p.petugas_id
JOIN detail_peminjaman dp ON dp.peminjaman_id = p.id
JOIN alat a ON a.id = dp.alat_id
GROUP BY p.id;

-- =====================================================================
-- DATA AWAL (SEED) - Kategori & Alat contoh
-- Catatan: akun user (admin/petugas/peminjam) dibuat lewat seed.php
-- agar password ter-hash otomatis oleh PHP (password_hash).
-- =====================================================================
INSERT INTO kategori (nama_kategori, deskripsi) VALUES
('Alat Praktik Jaringan', 'Perangkat untuk praktik jaringan komputer'),
('Alat Elektronik', 'Perangkat elektronik umum'),
('Alat Tulis & Presentasi', 'Peralatan pendukung presentasi');

INSERT INTO alat (kode_alat, nama_alat, kategori_id, jumlah_total, jumlah_tersedia, kondisi, lokasi_simpan, deskripsi) VALUES
('ALT-001', 'Router Mikrotik RB941', 1, 5, 5, 'baik', 'Rak A1', 'Router untuk praktik konfigurasi jaringan'),
('ALT-002', 'Switch TP-Link 8 Port', 1, 4, 4, 'baik', 'Rak A2', 'Switch unmanaged 8 port'),
('ALT-003', 'Kabel LAN Tester', 1, 6, 6, 'baik', 'Rak A3', 'Alat uji kabel UTP'),
('ALT-004', 'Proyektor Epson', 2, 3, 3, 'baik', 'Rak B1', 'Proyektor untuk presentasi'),
('ALT-005', 'Laptop Praktik', 2, 10, 10, 'baik', 'Rak B2', 'Laptop untuk praktik pemrograman'),
('ALT-006', 'Multitester Digital', 2, 5, 5, 'baik', 'Rak B3', 'Alat ukur listrik'),
('ALT-007', 'Whiteboard Portable', 3, 2, 2, 'baik', 'Rak C1', 'Papan tulis portable untuk presentasi kelompok');
