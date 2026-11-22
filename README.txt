=====================================================
 ETALASE DIGITAL SKILLS  -  UKK Level 3 • XI RPL
=====================================================

Aplikasi portfolio siswa: tampilkan karya digital, link project, dan demo.
Dibuat dengan PHP + MySQL/MariaDB, tampilan gelap modern dengan animasi 3D.


ISI FOLDER
----------
  index.php   -> seluruh logika aplikasi (login, project, user) + tampilan
  database.sql -> skema database MySQL/MariaDB
  style.css   -> desain, tema gelap, kubus 3D, animasi
  script.js   -> partikel, tilt 3D, animasi scroll, efek mengetik
  README.txt  -> petunjuk ini

Folder "uploads" dibuat otomatis saat upload gambar pertama.
File database.sqlite lama tidak digunakan oleh aplikasi dan dibiarkan sebagai
cadangan; datanya tidak otomatis dipindahkan ke database MySQL/MariaDB.


CARA MENJALANKAN DI VISUAL STUDIO CODE
--------------------------------------
1. Install XAMPP atau Laragon, lalu jalankan Apache dan MySQL/MariaDB.
   PHP harus memiliki ekstensi pdo_mysql aktif.

2. Buat database dan tabel dengan mengimpor file database.sql:
   - XAMPP: buka http://localhost/phpmyadmin
   - Pilih menu Import, pilih file database.sql, lalu jalankan.

   Koneksi bawaan aplikasi: host 127.0.0.1, port 3306, database
   etalase_digital, username root, password kosong. Atur kredensial
   melalui environment variable berikut jika konfigurasi berbeda:
       ETALASE_DB_HOST
       ETALASE_DB_PORT
       ETALASE_DB_NAME
       ETALASE_DB_USER
       ETALASE_DB_PASSWORD

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
    -> Aktifkan ekstensi pdo_mysql di php.ini lalu restart Apache.

* Database tidak ditemukan / koneksi gagal
    -> Pastikan MySQL/MariaDB aktif, database.sql sudah diimpor, dan
       kredensial koneksi sesuai.

* Gambar gagal diunggah
    -> Ukuran file terlalu besar. Naikkan upload_max_filesize dan
       post_max_size di php.ini, lalu restart server.

* Ingin mengulang dari awal (reset data)
    -> Hapus tabel users dan projects di database etalase_digital, lalu
       muat ulang database.sql. Hapus isi folder uploads jika gambar juga
       ingin dihapus.

* Port 8000 sudah dipakai
    -> Gunakan port lain:  php -S localhost:8080
