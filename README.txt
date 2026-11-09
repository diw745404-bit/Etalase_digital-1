=====================================================
 ETALASE DIGITAL SKILLS  -  UKK Level 3 • XI RPL
=====================================================

Aplikasi portfolio siswa: tampilkan karya digital, link project, dan demo.
Dibuat dengan PHP + SQLite, tampilan gelap modern dengan animasi 3D.


ISI FOLDER
----------
  index.php   -> seluruh logika aplikasi (login, project, user) + tampilan
  style.css   -> desain, tema gelap, kubus 3D, animasi
  script.js   -> partikel, tilt 3D, animasi scroll, efek mengetik
  README.txt  -> petunjuk ini

Folder "uploads" dan file "database.sqlite" dibuat otomatis saat pertama
kali dijalankan / saat upload gambar pertama.


CARA MENJALANKAN DI VISUAL STUDIO CODE
--------------------------------------
1. Install PHP (versi 7.4 atau lebih baru)
   - Windows : download dari https://windows.php.net/download lalu ekstrak
               (misal ke C:\php) dan tambahkan ke PATH.
               Atau install XAMPP / Laragon (sudah termasuk PHP).
   - Cek di terminal:  php -v

2. Aktifkan ekstensi SQLite di php.ini (hapus tanda ; di depan baris):
       extension=pdo_sqlite
       extension=sqlite3
   (Jika php.ini belum ada, salin php.ini-development menjadi php.ini)
   Cek dengan:  php -m   -> pastikan "pdo_sqlite" muncul.

3. Buka folder project di VS Code:
   File > Open Folder... > pilih folder yang berisi index.php

4. Buka Terminal di VS Code (Ctrl + `) lalu jalankan:

       php -S localhost:8000

5. Buka browser ke:   http://localhost:8000

   (Alternatif: install ekstensi "PHP Server" di VS Code, klik kanan
    index.php > "PHP Server: Serve project".)

Catatan: Tampilan memakai font dari Google Fonts, jadi butuh internet
agar hurufnya tampil sempurna. Tanpa internet tetap jalan dengan font
bawaan sistem.


AKUN DEMO  (password semua: rojul)
----------------------------------
  SA    : sa     / rojul   -> semua fitur + kelola data siswa
  Admin : admin  / rojul   -> kelola semua project
  Siswa : siswa  / rojul   -> kelola project milik sendiri


FITUR
-----
  - Login 3 level akses (SA, Admin, Siswa)
  - Tambah / edit / hapus project (judul, deskripsi, gambar, link, demo)
  - Preview gambar sebelum diunggah
  - SA dapat menambah, mengedit, dan menghapus akun pengguna
  - Dashboard dengan statistik dan project terbaru
  - Efek 3D: kubus berputar yang mengikuti kursor, kartu tilt 3D,
    cincin & bola melayang, partikel interaktif di latar belakang
  - Teks hero mengetik otomatis, angka statistik berhitung naik
  - Tampilan responsif (HP, tablet, laptop)

Peningkatan keamanan:
  - Token CSRF pada form & tombol hapus
  - Link hanya boleh http/https
  - Upload divalidasi sebagai gambar asli
  - Session di-regenerate setelah login


MASALAH UMUM
------------
* "could not find driver"
    -> Ekstensi pdo_sqlite belum aktif (lihat langkah 2).

* Gambar gagal diunggah
    -> Ukuran file terlalu besar. Naikkan upload_max_filesize dan
       post_max_size di php.ini, lalu restart server.

* Ingin mengulang dari awal (reset data)
    -> Hentikan server, hapus file database.sqlite dan isi folder
       uploads, lalu jalankan lagi.

* Port 8000 sudah dipakai
    -> Gunakan port lain:  php -S localhost:8080
