# Skenario Pengujian (Test Case)

Format: **Kasus** → **Langkah** → **Hasil yang Diharapkan**. Kolom "Hasil Aktual" & "Screenshot"
diisi saat pengujian nyata dilakukan di sekolah/tempat uji.

| No | Modul | Kasus Uji | Langkah Pengujian | Hasil yang Diharapkan | Hasil Aktual |
|----|-------|-----------|--------------------|-------------------------|---------------|
| 1 | Login | Login dengan username & password benar | Buka `login.php`, isi `admin`/`admin123`, submit | Berhasil login, redirect ke dashboard admin | (isi saat uji) |
| 2 | Login | Login dengan password salah | Isi username benar, password salah, submit | Gagal login, muncul notifikasi "Password salah" | (isi saat uji) |
| 3 | Alat | Tambah alat baru (Admin) | Login admin → menu Data Alat → isi form → simpan | Alat baru muncul di daftar, stok tersedia = jumlah total | (isi saat uji) |
| 4 | Alat | Tambah alat dengan kategori kosong | Hapus semua kategori, coba tambah alat | Form alat tidak tampil / muncul peringatan tambah kategori dulu | (isi saat uji) |
| 5 | Peminjaman | Peminjam mengajukan peminjaman alat tersedia | Login peminjam → Ajukan Peminjaman → pilih alat & tanggal → submit | Data tersimpan status "menunggu", muncul di riwayat peminjam & antrian petugas | (isi saat uji) |
| 6 | Peminjaman | Peminjam mengajukan alat dengan stok tidak cukup | Ajukan alat dengan jumlah melebihi stok tersedia | Sistem menolak, muncul notifikasi stok tidak mencukupi | (isi saat uji) |
| 7 | Persetujuan | Petugas menyetujui peminjaman | Login petugas → Setujui Peminjaman → klik Setujui | Status berubah "disetujui", stok alat berkurang sesuai jumlah dipinjam | (isi saat uji) |
| 8 | Pengembalian | Kembalikan alat tepat waktu (tanpa denda) | Petugas → Proses Pengembalian → input tanggal ≤ rencana kembali | Status "dikembalikan", denda = Rp0, stok bertambah kembali | (isi saat uji) |
| 9 | Pengembalian | Kembalikan alat terlambat (dengan denda) | Input tanggal kembali > tanggal rencana kembali | Sistem menghitung denda = (hari telat × tarif × jumlah alat), tersimpan di tabel pengembalian | (isi saat uji) |
| 10 | Privilege | Cek privilege user (akses terlarang) | Login sebagai peminjam, coba akses URL `admin/users.php` langsung | Sistem menolak akses (403 Forbidden), tidak menampilkan data admin | (isi saat uji) |
| 11 | Privilege | Cek privilege user (akses petugas ke halaman admin) | Login sebagai petugas, coba akses `admin/laporan.php` | Sistem menolak akses (403 Forbidden) | (isi saat uji) |
| 12 | Log | Log aktifitas tercatat otomatis | Lakukan login, ajukan peminjaman, approve, lalu cek menu Log Aktifitas (admin) | Semua aktivitas di atas tercatat dengan waktu & nama user yang sesuai | (isi saat uji) |
| 13 | Laporan | Cetak laporan periode tertentu | Admin → Cetak Laporan → pilih rentang tanggal → tampilkan → cetak | Data yang tampil sesuai filter tanggal, tombol cetak membuka dialog print | (isi saat uji) |

**Catatan pengujian:**
- Skenario 5, 6, 7, 8, 9 wajib disertai tangkapan layar (screenshot) sesuai poin dokumentasi soal.
- Untuk skenario nomor 6 dan 9, `Stored Procedure` (`sp_setujui_peminjaman`, `sp_kembalikan_alat`)
  akan melakukan `ROLLBACK` otomatis apabila terjadi kegagalan validasi di tengah transaksi.
