# Sistem Pasien Klinik Hewan

Praktikum Pemrograman Web (INF11197) — Paket 2, Tipe D.
Aplikasi CRUD pasien klinik hewan dengan Laravel, PDO (prepared statement), dan MySQL/MariaDB.

## Relasi data
`jenis_hewan` (1) ──< (N) `pasien_hewan`
FK `pasien_hewan.jenis_hewan_id` → `jenis_hewan.id` (ON UPDATE CASCADE, ON DELETE RESTRICT).
UNIQUE: `kode_pasien`, `nomor_rekam`. CHECK: `usia_tahun` 0–30.

## Cara menjalankan
1. `composer install`
2. `copy .env.example .env` lalu `php artisan key:generate`
3. Atur `DB_*` di `.env` (database: `klinik_hewan_2401020132`), `SESSION_DRIVER=file`
4. Impor `database/klinik_hewan_2401020132.sql` lewat phpMyAdmin atau
   `mysql -u root < database/klinik_hewan_2401020132.sql`
5. `composer dump-autoload` lalu `php artisan serve`
6. Buka `http://127.0.0.1:8000/pasien-hewan`
7. ## Catatan
Gunakan `SESSION_DRIVER=file` di `.env`. Dengan driver `database` (bawaan Laravel),
aplikasi membutuhkan tabel `sessions` dan halaman error saat koneksi database putus.

## Route
| Method | URI | Fungsi |
| --- | --- | --- |
| GET | /pasien-hewan | Daftar + form tambah + cari (`q`) + filter (`jenis_hewan_id`) |
| POST | /pasien-hewan | Create |
| GET | /pasien-hewan/{id}/edit | Form edit |
| PUT | /pasien-hewan/{id} | Update |
| DELETE | /pasien-hewan/{id} | Delete |

## Keamanan
- Semua query memakai prepared statement (PDO dari koneksi Laravel).
- Input dibersihkan dengan `trim` dan `strip_tags`, divalidasi di server, dilindungi `@csrf`.
- Error koneksi/query dicatat lewat `report()`; pengguna hanya melihat pesan aman.
- `.env` tidak masuk repository.
