# Aplikasi Ijazah & Transkrip STIE Nusantara Makassar

Aplikasi web mandiri untuk **satu Admin**. Profil lulusan disimpan sekali dan digunakan oleh ijazah serta transkrip nilai. Dibangun dengan PHP 8.3+, SQLite, Bootstrap 5.3.8, Dompdf, dan Endroid QR Code.

## Akun Admin

Jangan menuliskan kata sandi di README atau berkas lain di repositori. Buat atau ganti kata sandi Admin dengan `bin/create-admin.php` (langkah 3 di bawah); menjalankannya lagi untuk nama akun yang sama akan mengganti kata sandinya.

## Menjalankan lokal

1. Pastikan PHP memiliki `pdo_sqlite`, `gd`, `mbstring`, `dom`, `zip`, `xmlreader`, `xmlwriter`, dan `fileinfo`.
2. Jalankan `composer install` pada folder ini.
3. Pada instalasi baru, buat akun Admin. Kata sandi dikirim melalui stdin, bukan argumen perintah:

   ```powershell
   $password = Read-Host 'Kata sandi Admin (minimal 12 karakter)' -AsSecureString
   $plain = [System.Net.NetworkCredential]::new('', $password).Password
   $plain | php bin/create-admin.php admin
   Remove-Variable plain,password
   ```

4. Jalankan `php -S 127.0.0.1:8000 -t public` lalu buka `http://127.0.0.1:8000/`.

Database dibuat otomatis di `storage/app.sqlite`. Folder `storage` harus dapat ditulis oleh proses PHP. Pada server, atur **Document Root ke folder `public`** dan blok akses web langsung ke `app`, `storage`, `vendor`, dan `config.local.php`.

## URL QR sebelum penerbitan resmi

Salin `config.example.php` menjadi `config.local.php`, lalu isi `base_url` dengan alamat **HTTPS publik yang stabil** untuk aplikasi ini. Selama opsi cetak QR aktif, aplikasi menolak menerbitkan ijazah jika `base_url` kosong, bukan HTTPS, atau masih menunjuk localhost. QR memakai token acak dan membuka `index.php?r=verify&t=...`.

## Alur Admin

1. Atur template ijazah dan transkrip per prodi, termasuk pejabat penandatangan. Editor menampilkan perubahan sebelum disimpan; pilih satu bidang teks untuk mengatur X/Y, lebar, ukuran huruf, dan perataan, atau geser bidang pada pratinjau A4. Pratinjau ijazah menyediakan grid 10 mm dan garis tengah. Saat digeser mendekati garis, bidang menempel otomatis; penanda hijau menyatakan teks sudah tepat di tengah. Nonaktifkan **Grid 10 mm + magnet** atau gunakan tombol panah untuk penyesuaian halus. Garis bantu hanya terlihat di editor. PDF hanya dibuat saat tombol **Buka PDF saat ini** diklik, menggunakan isian yang belum disimpan. Logo/nama kampus/latar blanko **tidak dicetak ulang**.
2. Tambah profil lulusan satu per satu atau impor template lulusan melalui menu **Impor lulusan**. Nomor dan NIM diperlakukan sebagai teks. Nilai belum diperlukan pada tahap ini.
3. Atur **Kurikulum** setiap prodi sekali saja: daftar mata kuliah (kode, nama, SKS) dengan urutan yang sama seperti di transkrip, serta **skala nilai** (huruf → bobot, awalnya A=4, B=3, C=2, D=1, E=0).
4. Buka menu **Nilai**, pilih mahasiswa, lalu isi **nilai huruf** untuk setiap mata kuliah kurikulum, atau unduh template nilai mahasiswa tersebut, isi kolom `nilai_huruf`, dan muat kembali. Bobot dan mutu dihitung dari skala; SKS dan IPK hasil hitung langsung dibandingkan dengan **total SKS dan IPK resmi** pada profil lulusan.
5. Buka pratinjau PDF, cocokkan dengan data resmi dan blanko fisik, lalu terbitkan. Penerbitan menyimpan snapshot data, versi template, PDF final, dan hash PDF.
6. PDF dapat dibuka/cetak ulang. Jika salah, batalkan dengan alasan lalu terbitkan pengganti. QR lama tetap menunjukkan status dibatalkan.

Selama ada dokumen aktif (ijazah atau transkrip), identitas lulusan terkunci. Selama transkrip aktif, daftar nilai terkunci. Untuk memperbaiki data, batalkan dokumen terkait lebih dahulu.

### Kurikulum dan nilai per mahasiswa

- Kurikulum disimpan per prodi. Urutan baris menjadi urutan mata kuliah pada transkrip; ubah urutan dengan tombol ↑ ↓. Daftar dapat dimuat dari Excel (`kode_mk, nama_mk, sks`) melalui template kurikulum; hasilnya tampil di tabel dan baru tersimpan setelah **Simpan kurikulum**.
- Nilai mahasiswa hanya berisi nilai huruf. Mata kuliah yang dikosongkan dianggap tidak diambil dan tidak ikut transkrip. Kode, nama, dan SKS selalu diambil dari kurikulum saat nilai disimpan.
- Template nilai per mahasiswa berisi NIM dan seluruh mata kuliah kurikulum. Saat dimuat, berkas dengan NIM lain, kode di luar kurikulum, atau huruf di luar skala ditolak. Hasil impor tampil di tabel lebih dahulu dan baru tersimpan setelah **Simpan nilai**.
- Mengubah kurikulum atau skala tidak mengubah transkrip yang sudah terbit. Nilai mahasiswa memakai kurikulum dan skala terbaru saat nilainya disimpan ulang. Nilai lama yang kodenya tidak lagi ada di kurikulum tampil sebagai **Nilai di luar kurikulum** dan tetap ikut transkrip sampai dihapus.

### Latar blanko di editor

Pada editor ijazah, tombol **Unggah latar blanko** menerima pindaian/foto blanko kosong (JPG, PNG, atau WebP, lanskap, maks. 10 MB). Gambar direntangkan memenuhi kertas A4 lanskap di bawah teks sehingga posisi bidang dapat dicocokkan dengan kolom isian blanko. Kepekatan latar dapat diatur dan latar dapat disembunyikan. Latar disimpan per program studi di `storage/backgrounds`, tidak ikut versi template, dan **tidak pernah dicetak** pada PDF pratinjau maupun final. Untuk hasil paling akurat, pindai seluruh lembar hingga tepinya tanpa margin atau kemiringan.

### QR pada blanko

Template awal **belum mencetak QR** karena posisi kosong pada blanko fisik belum disahkan. Editor selalu menampilkan kotak panduan QR yang dapat digeser; X, Y, dan ukuran disimpan bersama versi template meskipun opsi cetak QR belum dicentang. PDF pratinjau juga menampilkan kotak **PANDUAN QR** untuk kalibrasi, sedangkan PDF final hanya mencetak QR jika opsi cetak diaktifkan. Halaman verifikasi tetap tersedia untuk dokumen yang diterbitkan. Token QR adalah verifikasi catatan kampus, bukan pengganti [PISN](https://pisn.kemdiktisaintek.go.id/).

## Template Excel (.xls) dan impor data

Menu **Impor lulusan** menyediakan template lulusan, menu **Kurikulum** menyediakan template kurikulum per prodi, dan halaman nilai setiap mahasiswa menyediakan template nilai mahasiswa tersebut. Semuanya berkas `.xls` asli. Isi sheet `Data` mulai baris 2 tanpa mengubah header. Sheet `Petunjuk` menjelaskan pengisian. NIM, kode mata kuliah, nomor dokumen, dan tanggal sudah disiapkan sebagai teks agar angka nol di depan tetap utuh. Berkas `.xlsx` dan CSV lama juga dapat diimpor.

Data lulusan wajib memiliki kolom:

```text
nim,nama_lengkap,tempat_lahir,tanggal_lahir,kode_prodi,tanggal_lulus,nomor_ijazah_nasional,nomor_transkrip,tanggal_terbit,total_sks_resmi,ipk_resmi
```

Kolom opsional: `program_pendidikan`, `judul_skripsi`. Tanggal memakai `YYYY-MM-DD`; angka desimal memakai titik. `kode_prodi` saat ini `AKUNTANSI` atau `MANAJEMEN`.

Template nilai mahasiswa memakai kolom `nim, urutan, kode_mk, nama_mk, sks, nilai_huruf`; saat dimuat hanya `kode_mk` dan `nilai_huruf` yang wajib, dan `nim` (jika ada) harus sama dengan mahasiswa yang dibuka.

Impor lulusan maksimal 5 MB dan 10.000 baris per berkas. Impor mempratinjau kesalahan dan baru menyimpan setelah Admin menekan **Simpan hasil impor**. Seluruh batch ditolak jika ada kesalahan; lulusan dengan dokumen aktif tidak ditimpa. Impor ulang lulusan tidak menghapus nilai.

## Batas saat ini

- Aturan nilai ulang, transfer, predikat, dan pembulatan IPK STIE belum diberikan. Karena itu, data nilai yang dipakai untuk transkrip harus merupakan **daftar akhir** dari sistem akademik dan total/IPK resmi harus cocok dengan hasil hitung aplikasi. Predikat belum dicetak.
- Logo dan header transkrip resmi belum tersedia. Header teks, alamat, serta pejabat bisa diatur Admin; cetak final harus ditinjau kampus.
- Posisi cetak ijazah berasal dari acuan sampel, **belum terkalibrasi dengan blanko kosong dan printer kampus**.
- Aplikasi menggunakan SQLite untuk instalasi mandiri kecil. Untuk hosting banyak petugas atau beban tinggi, migrasi database dan backup operasional perlu disiapkan.
- Tidak ada integrasi otomatis PISN/PDDikti. Nomor ijazah nasional diisi dari sumber resmi.

## Pemeriksaan pengembang

```powershell
php tests/smoke.php
composer audit --no-interaction
```

Uji cetak fisik tetap wajib sebelum penggunaan resmi.
