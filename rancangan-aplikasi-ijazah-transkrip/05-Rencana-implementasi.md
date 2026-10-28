# Rencana implementasi dan kriteria penerimaan

## Arsitektur yang disarankan

- **Aplikasi web mandiri** dengan backend, database relasional, dan penyimpanan berkas privat. Tidak menumpang database LMS/SIAKAD/Website Kampus.
- Backend PHP/Laravel versi yang didukung saat pengerjaan; antarmuka server-rendered/Livewire cukup untuk satu alur Admin. Database MySQL/MariaDB untuk hosting kampus; SQLite dapat dipakai untuk pengembangan lokal.
- Generator dokumen deterministik: data dan versi template sama menghasilkan tampilan sama. Simpan berkas PDF final dan hash-nya saat penerbitan, lalu cetak ulang dari arsip final.
- Halaman verifikasi publik di domain HTTPS stabil milik kampus. Bila domain aplikasi terpisah, tautan QR tetap harus menuju domain yang dikuasai kampus.
- Impor CSV berjalan di server dalam batch teridentifikasi; simpan laporan kesalahan dan keputusan Admin untuk tiap batch.

## Urutan pengerjaan

| Tahap | Hasil yang dapat diuji |
|---|---|
| 1. Fondasi | Login Admin, prodi, profil lulusan bersama, backup dan audit dasar. |
| 2. Impor | Dua CSV dalam satu proses, pratinjau, pemetaan kolom, validasi, pembaruan draf, laporan kesalahan. |
| 3. Template ijazah | Editor posisi mm dan redaksi per prodi, lapisan blanko untuk pratinjau, cetak teks saja A4 lanskap, uji kalibrasi. |
| 4. Transkrip | Template STIE, nilai per mata kuliah, validasi total/IPK, PDF banyak halaman. |
| 5. Penerbitan | Snapshot final, nomor unik, PDF arsip, cetak ulang, pembatalan/penggantian, riwayat. |
| 6. QR | Token acak, halaman publik privat-data-minimal, status terbit/batal, tes perangkat seluler dan HTTPS. |
| 7. Uji operasional | Impor contoh resmi yang dianonimkan, cetak fisik, uji nama panjang/transkrip panjang, pemulihan backup. |

## Kriteria penerimaan utama

1. Satu perubahan identitas draf terlihat di pratinjau **kedua** dokumen tanpa input ulang.
2. Impor mempertahankan NIM/kode/nomor dengan nol di depan, melaporkan baris salah, dan tidak menimpa penerbitan lama.
3. Cetakan ijazah berisi teks/QR yang disetujui saja; tidak ada logo, nama kampus, watermark, gambar blanko, atau garis bantu tercetak ulang.
4. PDF ijazah satu halaman pada ukuran blanko yang disahkan; posisi lulus uji cetak fisik pada sedikitnya satu printer kampus.
5. Transkrip panjang tetap terbaca dan tidak kehilangan mata kuliah, total, IPK, atau tanda tangan saat berpindah halaman.
6. Tautan QR terbit menampilkan status benar; token acak tidak membuka data pribadi/nilai; token lama menunjukkan pembatalan setelah penggantian.
7. Dokumen terbit dan riwayatnya tetap sama ketika profil, template, atau aturan tampilan draf berubah.
8. Backup dapat dipulihkan ke lingkungan uji tanpa kehilangan keterkaitan lulusan, nilai, dokumen, dan QR.

## Keputusan yang memerlukan bahan resmi sebelum produksi

- Blanko ijazah kosong, ukuran terukur, ruang QR, jenis/ukuran huruf, dan printer yang digunakan.
- Template transkrip STIE: logo/header, alamat, nomor/kode PT dan prodi, pejabat penandatangan, stempel/tanda tangan, serta ukuran kertas.
- Nama dan kode prodi, gelar, akreditasi yang berlaku pada tiap angkatan, dan redaksi resmi. Jangan menyalin identitas universitas pada sampel transkrip.
- Contoh CSV keluaran SIAKAD yang dianonimkan serta aturan nilai ulang, konversi bobot, pembulatan IPK, predikat, dan judul skripsi.
- Kebijakan tampilan nomor ijazah di halaman verifikasi dan domain HTTPS yang akan dipakai.

## Catatan peraturan

Rancangan mengacu pada [Permendikbudristek 50/2024](https://www.peraturan.go.id/files/permendikbudristek-no-50-tahun-2024.pdf), terutama isi minimal ijazah/transkrip dan asal nomor ijazah nasional, serta informasi pada [PISN](https://pisn.kemdiktisaintek.go.id/). Periksa kembali peraturan yang berlaku dan penerapannya bersama pihak kampus sebelum fitur penerbitan dipakai secara resmi.
