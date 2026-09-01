# Aplikasi Peminjaman Alat
Proyek UKK RPL 2025/2026 — Kode Soal KM25.4.1.1
Dibangun dengan **PHP native (PDO MySQL)** tanpa framework, agar mudah dijalankan di **Laragon**.

## Fitur Sesuai Soal

| Fitur | Admin | Petugas | Peminjam |
|---|---|---|---|
| Login / Logout | ✅ | ✅ | ✅ |
| CRUD User | ✅ | | |
| CRUD Alat | ✅ | | |
| CRUD Kategori | ✅ | | |
| CRUD Data Peminjaman (lihat semua) | ✅ | | |
| CRUD Pengembalian (lihat semua) | ✅ | | |
| Log Aktifitas | ✅ | | |
| Menyetujui/Menolak Peminjaman | | ✅ | |
| Memantau Pengembalian | | ✅ | |
| Mencetak Laporan | ✅ | | |
| Melihat Daftar Alat | | | ✅ |
| Mengajukan Peminjaman | | | ✅ |
| Mengembalikan Alat (+ denda) | | ✅ (proses) | ✅ (ajukan) |

## Instalasi di Laragon

1. **Salin folder project**
   Copy seluruh folder `peminjaman_alat` ke dalam folder `www` Laragon, contoh:
   `C:\laragon\www\peminjaman_alat`

2. **Jalankan Laragon**, klik **Start All** (Apache + MySQL akan aktif).

3. **Buat & Import Database**
   - Buka phpMyAdmin (klik menu Laragon → Database, atau akses `http://localhost/phpmyadmin`)
   - Klik tab **Import**
   - Pilih file `database.sql` dari folder project ini, lalu klik **Go**
   - Database `db_peminjaman_alat` beserta seluruh tabel, view, function, stored procedure,
     dan trigger akan otomatis terbuat.

   **Alternatif via terminal Laragon (klik kanan → Terminal):**
   ```
   mysql -u root -p < database.sql
   ```
   (password kosong, langsung tekan Enter saat diminta password)

4. **Cek konfigurasi koneksi**
   Buka `config/database.php`. Default Laragon (`root` / password kosong) biasanya sudah
   langsung cocok, tidak perlu diubah.

5. **Buat akun demo (WAJIB dijalankan sekali)**
   Buka browser ke:
   ```
   http://localhost/peminjaman_alat/seed.php
   ```
   Ini akan membuat 4 akun default dengan password ter-enkripsi (bcrypt):

   | Username | Password | Role |
   |---|---|---|
   | admin | admin123 | Admin |
   | petugas1 | petugas123 | Petugas |
   | budi | peminjam123 | Peminjam |
   | siti | peminjam123 | Peminjam |

   Setelah berhasil, disarankan menghapus/mengunci file `seed.php`.

6. **Akses aplikasi**
   ```
   http://localhost/peminjaman_alat/login.php
   ```

## Struktur Folder

```
peminjaman_alat/
├─ config/database.php        -> koneksi PDO ke MySQL
├─ includes/                  -> fungsi bantuan, header/footer layout
├─ assets/css/style.css        -> tampilan
├─ admin/                      -> modul khusus role admin
├─ petugas/                    -> modul khusus role petugas
├─ peminjam/                   -> modul khusus role peminjam
├─ login.php / logout.php / index.php
├─ seed.php                    -> pembuat akun demo (bcrypt)
├─ database.sql                -> schema + view + function + procedure + trigger + data awal
└─ docs/                       -> ERD, flowchart/pseudocode, rencana pengujian, laporan evaluasi
```

## Objek Database Penting (memenuhi poin 6 soal)

- **Function**: `fn_hitung_denda(tgl_rencana, tgl_kembali, jumlah_alat)` → menghitung denda.
- **Stored Procedure**:
  - `sp_setujui_peminjaman` — approve + kurangi stok (pakai `START TRANSACTION` / `COMMIT` / `ROLLBACK`).
  - `sp_tolak_peminjaman` — tolak pengajuan.
  - `sp_kembalikan_alat` — proses pengembalian, hitung denda otomatis, kembalikan stok.
- **Trigger**:
  - `trg_alat_before_insert` — set stok tersedia otomatis saat alat baru ditambahkan.
  - `trg_log_peminjaman_baru` — mencatat log saat ada pengajuan baru.
  - `trg_log_status_peminjaman` — mencatat log setiap perubahan status peminjaman.
- **View**: `v_peminjaman_detail` — gabungan data untuk laporan.

## Alur Penggunaan Singkat

1. **Peminjam** login → lihat *Daftar Alat* → *Ajukan Peminjaman* (pilih alat + tanggal).
2. **Petugas** login → menu *Setujui Peminjaman* → menyetujui/menolak (stok otomatis terkunci saat disetujui).
3. Saat alat kembali, **Petugas** buka *Proses Pengembalian* → input tanggal & kondisi alat →
   sistem otomatis menghitung denda (jika telat) dan mengembalikan stok.
4. **Admin** memantau seluruh data, mengelola user/alat/kategori, mencetak laporan periode, dan melihat log aktifitas.

Selengkapnya lihat dokumen di folder `docs/` (ERD, flowchart/pseudocode, skenario pengujian, laporan evaluasi).
