# Voting Pemilihan Kakang Senduk Ponorogo

Aplikasi PHP native dengan MySQLi, Bootstrap 5, dan JavaScript. QRIS di halaman dukungan ekstra masih simulasi; transaksi berbayar hanya masuk klasemen setelah admin memverifikasinya.

## Instalasi lokal (XAMPP)

1. Salin folder proyek ke `C:\xampp\htdocs\voting-kakang-senduk`.
2. Jalankan Apache dan MySQL dari XAMPP.
3. Untuk instalasi baru, buka phpMyAdmin lalu impor `database.sql`. File ini membuat database `db_voting_kakang_senduk` dan tabel-tabel yang diperlukan. Jika Anda sudah memiliki database dari versi proyek sebelumnya, **jangan impor SQL ini untuk upgrade**; buat backup terlebih dahulu, lalu jalankan `migration_existing_install.sql` pada database tersebut agar akun dan data lama dipertahankan.
4. Sesuaikan host, nama database, user, dan password MySQL pada `config/koneksi.php` bila berbeda dari nilai XAMPP bawaan.
5. Buka `http://localhost/voting-kakang-senduk/`.
6. Daftarkan akun dari halaman **Daftar**, lalu promosikan akun pertama menjadi admin melalui phpMyAdmin:

   ```sql
   UPDATE users SET role = 'admin' WHERE email = 'email-admin-anda@example.com';
   ```

7. Masuk sebagai admin, buat kontestan dan atur tanggal periode pada **Manajemen periode**. Aktifkan periode untuk membuka voting. Periode hanya menerima suara di antara waktu mulai dan selesai.

Bootstrap dan Chart.js dimuat dari CDN, sehingga browser perlu akses internet untuk memuat aset tersebut. Path dasar aplikasi dideteksi otomatis dari document root web server.

## Catatan skema dan voting

- Tabel `voting` menyimpan suara gratis dan suara berbayar yang telah disetujui. Indeks unik menggunakan kolom hasil `gratis_user_id` bersama kategori dan periode: ini membatasi satu suara gratis per akun/kategori/periode sambil tetap mengizinkan beberapa dukungan berbayar.
- Migrasi versi lama menempatkan suara lama (jika ada) pada periode khusus nonaktif bernama “Data voting sebelum pembaruan”.
- `pembayaran_vote` menyimpan permintaan dukungan yang masih menunggu, termasuk jumlah, nilai simulasi Rp3.500 per suara, serta pesan dukungan. Permintaan tidak menambah klasemen sampai admin memilih **Setujui**.
- Password menggunakan `password_hash()` dan `password_verify()`. Formulir perubahan memakai token CSRF; query data memakai prepared statements MySQLi.
- Untuk menambahkan akun admin berikutnya, buat akun melalui registrasi lalu ubah rolenya oleh admin database tepercaya.

## Kebutuhan

- PHP 8.0+ dengan ekstensi `mysqli` dan `mbstring`
- MySQL 5.7.6+ atau MariaDB yang mendukung kolom generated tersimpan dan indeks unik
- Apache/PHP (atau server web PHP lain)
