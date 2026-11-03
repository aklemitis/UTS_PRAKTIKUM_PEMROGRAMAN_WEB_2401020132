-- ============================================================
-- Praktikum Pemrograman Web INF11197 - Paket 2 Tipe D
-- Sistem Pasien Klinik Hewan
-- Basis data : klinik_hewan_2401020132
-- ============================================================

CREATE DATABASE IF NOT EXISTS klinik_hewan_2401020132
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE klinik_hewan_2401020132;

-- ------------------------------------------------------------
-- DDL
-- ------------------------------------------------------------
DROP TABLE IF EXISTS pasien_hewan;
DROP TABLE IF EXISTS jenis_hewan;

-- Tabel master
CREATE TABLE jenis_hewan (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama_jenis  VARCHAR(100)    NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT uq_jenis_hewan_nama UNIQUE (nama_jenis)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Tabel utama (sisi "banyak" dari relasi 1-ke-N)
CREATE TABLE pasien_hewan (
    id              BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    kode_pasien     CHAR(10)         NOT NULL,
    nama_hewan      VARCHAR(100)     NOT NULL,
    nomor_rekam     VARCHAR(30)      NOT NULL,
    usia_tahun      TINYINT UNSIGNED NOT NULL,
    jenis_hewan_id  BIGINT UNSIGNED  NOT NULL,
    created_at      TIMESTAMP        NULL DEFAULT NULL,
    updated_at      TIMESTAMP        NULL DEFAULT NULL,
    PRIMARY KEY (id),
    CONSTRAINT uq_pasien_kode  UNIQUE (kode_pasien),
    CONSTRAINT uq_pasien_rekam UNIQUE (nomor_rekam),
    CONSTRAINT chk_pasien_usia CHECK (usia_tahun BETWEEN 0 AND 30),
    CONSTRAINT fk_pasien_jenis FOREIGN KEY (jenis_hewan_id)
        REFERENCES jenis_hewan (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- DML (data awal)
-- ------------------------------------------------------------
INSERT INTO jenis_hewan (id, nama_jenis) VALUES
    (1, 'Kucing'),
    (2, 'Anjing'),
    (3, 'Kelinci'),
    (4, 'Burung');

INSERT INTO pasien_hewan
    (kode_pasien, nama_hewan, nomor_rekam, usia_tahun, jenis_hewan_id, created_at, updated_at)
VALUES
    ('PH00000001', 'Milo',  'RMH-0001', 3, 1, NOW(), NOW()),
    ('PH00000002', 'Bruno', 'RMH-0002', 5, 2, NOW(), NOW()),
    ('PH00000003', 'Luna',  'RMH-0003', 2, 1, NOW(), NOW());
