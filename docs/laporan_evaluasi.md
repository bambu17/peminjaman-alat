# Laporan Evaluasi Singkat — Aplikasi Peminjaman Alat

## a. Fitur yang Sudah Berjalan Dengan Baik
- Login/logout dengan 3 level akses (Admin, Petugas, Peminjam) beserta pembatasan hak akses (privilege) per halaman.
- CRUD User, Kategori, dan Alat oleh Admin.
- Pengajuan peminjaman oleh Peminjam dengan validasi ketersediaan stok.
- Persetujuan/penolakan peminjaman oleh Petugas menggunakan stored procedure dengan transaksi (COMMIT/ROLLBACK) sehingga stok tidak akan berkurang ganda atau tidak konsisten.
- Proses pengembalian alat dengan perhitungan denda otomatis berdasarkan selisih hari keterlambatan.
- Log aktifitas tercatat otomatis (trigger) maupun manual untuk aksi penting (login, approve, tolak, dsb).
- Cetak laporan rekap peminjaman berdasarkan filter periode tanggal.

## b. Bug / Keterbatasan yang Belum Diperbaiki
- Belum ada validasi peminjaman ganda pada alat yang sama dalam satu transaksi (jika petugas melakukan approve dua transaksi bersamaan pada alat dengan stok pas-pasan, perlu pengujian concurrency lebih lanjut).
- Notifikasi ke peminjam (email/WA) saat status peminjaman berubah belum diimplementasikan — saat ini peminjam harus mengecek halaman Riwayat secara manual.
- Upload foto kondisi alat saat pengembalian belum tersedia, saat ini hanya dicatat dalam bentuk teks kondisi.
- Belum ada fitur reset password mandiri (lupa password); saat ini hanya admin yang bisa mengubah password user lain melalui menu Data User.

## c. Rencana Pengembangan Berikutnya
- Menambahkan notifikasi email/WhatsApp otomatis saat status peminjaman berubah.
- Menambahkan upload bukti foto saat pengajuan dan saat pengembalian alat.
- Menambahkan dashboard grafik (statistik peminjaman per bulan, alat paling sering dipinjam).
- Menambahkan fitur cetak laporan ke PDF/Excel, tidak hanya cetak browser.
- Menambahkan sistem reset password melalui email.

---
*Dokumen ini adalah template yang dapat dilengkapi peserta uji berdasarkan hasil pengujian aktual di lokasi ujian.*
