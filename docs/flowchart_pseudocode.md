# Deskripsi Alur & Pseudocode Proses Utama

## 1. Proses Login

**Deskripsi:** User memasukkan username & password. Sistem memverifikasi ke tabel `users`
menggunakan `password_verify()` (bcrypt). Jika valid dan status aktif, sistem membuat session
sesuai role, lalu mengarahkan ke dashboard masing-masing role.

**Pseudocode:**
```
INPUT username, password
user = SELECT * FROM users WHERE username = username
IF user tidak ditemukan THEN
    TAMPILKAN "Username tidak ditemukan"
ELSE IF user.status != 'aktif' THEN
    TAMPILKAN "Akun nonaktif"
ELSE IF password_verify(password, user.password) == FALSE THEN
    TAMPILKAN "Password salah"
    CATAT log "percobaan login gagal"
ELSE
    SET SESSION user_id, role, nama_lengkap
    CATAT log "login ke sistem"
    REDIRECT ke dashboard sesuai role
END IF
```

**Flowchart (alur sederhana):**
```
[Mulai] → [Input username & password] → [Cari user di DB]
   → <User ditemukan?> --Tidak--> [Tampilkan error] → [Selesai]
   --Ya--> <Status aktif?> --Tidak--> [Tampilkan error] → [Selesai]
   --Ya--> <Password cocok?> --Tidak--> [Tampilkan error] → [Selesai]
   --Ya--> [Buat session] → [Redirect dashboard role] → [Selesai]
```

---

## 2. Proses Peminjaman Alat

**Deskripsi:** Peminjam memilih alat + jumlah + tanggal pinjam/kembali → sistem mengecek stok →
menyimpan header `peminjaman` (status = menunggu) dan detail alat di `detail_peminjaman`.
Petugas kemudian meninjau dan memanggil stored procedure `sp_setujui_peminjaman` (mengunci stok)
atau `sp_tolak_peminjaman`.

**Pseudocode (pengajuan oleh peminjam):**
```
INPUT tanggal_pinjam, tanggal_kembali, keperluan, daftar_alat[ ]
IF tanggal_kembali < tanggal_pinjam THEN ERROR "Tanggal tidak valid"

FOR EACH item IN daftar_alat
    IF stok_tersedia(item.alat_id) < item.jumlah THEN
        ERROR "Stok tidak cukup untuk " + item.nama_alat
    END IF
END FOR

BEGIN TRANSACTION
    kode = generate_kode("PJM")
    INSERT INTO peminjaman (kode, peminjam_id, tgl_pinjam, tgl_kembali, status='menunggu')
    FOR EACH item IN daftar_alat
        INSERT INTO detail_peminjaman (peminjaman_id, alat_id, jumlah)
    END FOR
COMMIT
TAMPILKAN "Pengajuan berhasil, menunggu persetujuan"
```

**Pseudocode (persetujuan oleh petugas — stored procedure `sp_setujui_peminjaman`):**
```
PROCEDURE sp_setujui_peminjaman(peminjaman_id, petugas_id, catatan)
    START TRANSACTION
    IF EXISTS alat dengan stok < jumlah_diminta THEN
        ROLLBACK
        RAISE ERROR "Stok tidak mencukupi"
    ELSE
        UPDATE alat SET jumlah_tersedia = jumlah_tersedia - jumlah_diminta
            (untuk setiap detail_peminjaman terkait)
        UPDATE peminjaman SET status='disetujui', petugas_id, catatan_petugas
        COMMIT
    END IF
END PROCEDURE
```

**Flowchart:**
```
[Mulai] → [Peminjam pilih alat & tanggal] → <Stok cukup semua item?>
   --Tidak--> [Tampilkan error stok] → [Selesai]
   --Ya--> [Simpan peminjaman status=menunggu] → [Selesai pengajuan]

[Petugas buka antrian] → [Pilih Setujui/Tolak]
   --Tolak--> [UPDATE status=ditolak] → [Selesai]
   --Setujui--> <Stok masih cukup?>
        --Tidak--> [ROLLBACK, tampilkan error] → [Selesai]
        --Ya--> [Kurangi stok, UPDATE status=disetujui] → [COMMIT] → [Selesai]
```

---

## 3. Proses Pengembalian Alat & Perhitungan Denda

**Deskripsi:** Petugas mencatat tanggal alat benar-benar dikembalikan beserta kondisinya.
Sistem (melalui stored procedure `sp_kembalikan_alat` dan function `fn_hitung_denda`) menghitung
jumlah hari keterlambatan (jika ada) dikalikan tarif denda per hari per unit alat, mengembalikan
stok (kecuali kondisi "hilang"), lalu menyimpan record di tabel `pengembalian` dan mengubah status
peminjaman menjadi "dikembalikan".

**Pseudocode:**
```
FUNCTION fn_hitung_denda(tgl_rencana, tgl_kembali, jumlah_alat)
    IF tgl_kembali > tgl_rencana THEN
        telat = DATEDIFF(tgl_kembali, tgl_rencana)
    ELSE
        telat = 0
    END IF
    tarif = ambil dari tabel pengaturan ('tarif_denda_per_hari')
    RETURN telat * tarif * jumlah_alat
END FUNCTION

PROCEDURE sp_kembalikan_alat(peminjaman_id, petugas_id, tgl_kembali, kondisi, catatan)
    START TRANSACTION
    tgl_rencana = ambil dari peminjaman
    total_jumlah = SUM(jumlah) dari detail_peminjaman
    telat = hitung selisih hari
    denda = fn_hitung_denda(tgl_rencana, tgl_kembali, total_jumlah)

    IF kondisi != 'hilang' THEN
        UPDATE alat SET jumlah_tersedia += jumlah  (untuk tiap alat terkait)
    END IF

    INSERT INTO pengembalian (peminjaman_id, tgl_kembali, kondisi, telat, denda, catatan)
    UPDATE peminjaman SET status = 'dikembalikan'
    COMMIT

    RETURN telat, denda
END PROCEDURE
```

**Flowchart:**
```
[Mulai] → [Petugas input tanggal kembali & kondisi alat]
   → [Hitung selisih hari dari tanggal rencana kembali]
   → <Terlambat?> --Ya--> [Hitung denda = hari x tarif x jumlah alat]
                  --Tidak--> [Denda = 0]
   → <Kondisi = hilang?> --Ya--> [Stok tidak dikembalikan]
                          --Tidak--> [Stok alat ditambahkan kembali]
   → [Simpan data pengembalian] → [Update status peminjaman = dikembalikan]
   → [Tampilkan hasil (telat & denda) ke petugas] → [Selesai]
```
