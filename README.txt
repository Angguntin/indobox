=====================================================================
 INDO BOX — Sistem Otomasi Perpustakaan (PHP + MySQL / XAMPP)
 Tema: pink & hijau pastel
=====================================================================

CARA PASANG (XAMPP)
1. Ekstrak folder "indobox" ke  C:\xampp\htdocs\indobox
2. Buka XAMPP Control Panel, jalankan Apache dan MySQL.
3. Buka http://localhost/phpmyadmin  ->  tab "Import"  ->  pilih file
   indobox\database\indobox.sql  ->  klik "Import" (database "indobox" dibuat otomatis).
4. Buka  http://localhost:8123/indobox/   (OPAC publik; pastikan TIDAK ada folder indobox di dalam indobox)
   Login petugas/anggota: http://localhost/indobox/login.php

AKUN DEMO
  Admin     : admin   / admin123
  Petugas   : petugas / petugas123
  Anggota   : AGT-0001 s.d. AGT-0008 / anggota123
(Password demo otomatis di-hash saat pertama login. Segera ganti setelah dipasang.)

Jika MySQL memakai password, ubah di  config/config.php  (DB_USER / DB_PASS).
Kompatibel PHP 5.6 s.d. 8.x dengan ekstensi pdo_mysql & mbstring (sudah aktif di XAMPP).
Untuk unggah PDF besar, naikkan upload_max_filesize & post_max_size di php.ini lalu restart Apache.

ID DIMULAI DARI 0
- Data awal memakai ID 0,1,2,3,... di semua tabel (SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO").
- Kolom ID bertipe UNSIGNED, jadi tidak pernah bernilai minus.
- Data baru otomatis lanjut berurutan; setelah penghapusan, AUTO_INCREMENT dirapikan
  lagi (fungsi resequence), dan jika tabel kosong, data pertama kembali ber-ID 0.
- Catatan: ID tengah yang dihapus tidak "digeser" ulang (menjaga relasi peminjaman tetap benar).

PEMETAAN KE CEK LIST PDF
Login / Ganti password / Tambah username ... Masuk: login.php | Ganti password: admin/profile.php
   | Tambah username (pengguna baru) + reset password: admin/users.php | Logout: logout.php
OPAC ........ index.php, opac.php (cari: semua, judul, pengarang, penerbit, ISBN, tahun, barcode,
              sinopsis + filter kategori, rentang tahun, bahasa, tersedia, ada PDF + urut),
              detail.php (klik koleksi -> PDF langsung tampil di pembaca bawaan + unduh)
Bibliografi . admin/biblio.php (tampil, cari), biblio_form.php (tambah/update), hapus di daftar,
              labels.php (cetak label barcode & katalog -> PDF), impor/ekspor CSV, kategori
Keanggotaan . admin/members.php, member_form.php (tampil/tambah/update/hapus), cards.php (cetak kartu
              anggota ber-barcode), member_types.php, perpanjang masa berlaku, reset password
Sirkulasi ... circulation.php (pinjam, stok berkurang), return.php (kembali, stok bertambah,
              hitung hari telat & denda), loans.php (perpanjang), fines.php (bayar/bebaskan denda)
FITUR UNGGULAN
  - Reservasi buku online (anggota) + antrean otomatis + batas ambil + kedaluwarsa otomatis
  - Riwayat peminjaman-pengembalian (admin/history.php dan portal anggota)
  - Statistik (admin/stats.php): anggota paling banyak meminjam & koleksi paling banyak dipinjam
    per bulan (pilih bulan), tren 12 bulan, harian, kategori, hari tersibuk, rating, dll
  - Portal anggota: pinjaman aktif, perpanjang mandiri, reservasi, kartu digital, profil
  - Ulasan & rating buku, buku tamu/kunjungan, pengumuman, pengaturan, log aktivitas,
    backup database (.sql), peran admin/petugas, perlindungan CSRF, password ter-hash

CATATAN
- 14 koleksi contoh memakai PDF contoh (uploads/pdf/contoh-XX.pdf). Ganti dengan e-book asli
  lewat Bibliografi > Edit > Berkas PDF.
- Google Fonts dimuat dari internet; tanpa internet tampilan memakai font sistem.
