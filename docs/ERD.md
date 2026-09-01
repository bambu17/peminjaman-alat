# ERD — Aplikasi Peminjaman Alat

## Entitas & Atribut

**users**
- id (PK), username, password, nama_lengkap, email, no_hp, role (admin/petugas/peminjam), status, created_at, updated_at

**kategori**
- id (PK), nama_kategori, deskripsi, created_at

**alat**
- id (PK), kode_alat, nama_alat, kategori_id (FK → kategori.id), jumlah_total, jumlah_tersedia, kondisi, lokasi_simpan, deskripsi, created_at, updated_at

**peminjaman**
- id (PK), kode_peminjaman, peminjam_id (FK → users.id), petugas_id (FK → users.id, nullable),
  tanggal_pinjam, tanggal_rencana_kembali, keperluan, status (menunggu/disetujui/ditolak/dikembalikan),
  catatan_petugas, created_at, updated_at

**detail_peminjaman**
- id (PK), peminjaman_id (FK → peminjaman.id), alat_id (FK → alat.id), jumlah

**pengembalian**
- id (PK), peminjaman_id (FK → peminjaman.id), petugas_id (FK → users.id), tanggal_kembali,
  kondisi_alat, telat_hari, denda, catatan, created_at

**log_aktifitas**
- id (PK), user_id (FK → users.id, nullable), aktivitas, waktu

**pengaturan**
- id (PK), nama_setting, nilai  (menyimpan tarif denda per hari)

## Relasi (Kardinalitas)

```
users (1) ──────< (N) peminjaman            [sebagai peminjam]
users (1) ──────< (N) peminjaman            [sebagai petugas yang approve]
users (1) ──────< (N) pengembalian          [sebagai petugas yang proses]
users (1) ──────< (N) log_aktifitas

kategori (1) ───< (N) alat

peminjaman (1) ─< (N) detail_peminjaman
alat (1) ───────< (N) detail_peminjaman

peminjaman (1) ─< (1) pengembalian          [satu peminjaman -> satu pengembalian]
```

## Diagram (ASCII)

```
 users                    kategori
 ┌───────────┐            ┌───────────┐
 │ id (PK)   │            │ id (PK)   │
 │ username  │            │ nama_kat  │
 │ role      │            └─────┬─────┘
 └────┬──────┘                  │ 1
      │ 1                       │
      │ N                       │ N
┌─────▼───────────┐      ┌──────▼─────┐
│  peminjaman      │      │   alat     │
│  id (PK)         │      │  id (PK)   │
│  peminjam_id(FK) │◄────┐│  kategori_id(FK)
│  petugas_id (FK) │     ││  jumlah_total
│  status          │     ││  jumlah_tersedia
└────┬─────────────┘     │└─────┬──────┘
     │ 1                 │      │ 1
     │ N                 │      │ N
┌────▼──────────────┐    │┌─────▼──────────┐
│ detail_peminjaman  │────┘  detail_peminjaman
│  peminjaman_id(FK) │       (relasi many-to-many
│  alat_id (FK)      │        antara peminjaman & alat)
│  jumlah            │
└────────────────────┘

peminjaman (1) ── (1) pengembalian
users (1) ── (N) log_aktifitas
```

Catatan: relasi `peminjaman` ke `alat` bersifat many-to-many, dijembatani oleh tabel
`detail_peminjaman` sehingga satu transaksi peminjaman bisa berisi beberapa jenis alat sekaligus.
