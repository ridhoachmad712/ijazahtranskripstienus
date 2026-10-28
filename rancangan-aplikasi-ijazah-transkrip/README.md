# Rancangan Aplikasi Ijazah dan Transkrip Nilai STIE Nusantara Makassar

Status: **rancangan untuk ditinjau, belum aplikasi produksi**. Disusun 27 Oktober 2026.

## Tujuan

Satu profil lulusan dipakai untuk membuat **ijazah** dan **transkrip nilai**. Admin mengelola data, template, impor, penerbitan, cetak, dan pembatalan. QR pada ijazah membuka halaman verifikasi publik milik kampus.

## Isi folder

- [01-Kebutuhan-dan-alur.md](01-Kebutuhan-dan-alur.md): ruang lingkup, alur kerja, dan rancangan layar.
- [02-Data-dan-impor.md](02-Data-dan-impor.md): model data bersama, kolom CSV, validasi, dan aturan nilai yang perlu ditetapkan.
- [03-Template-dan-cetak.md](03-Template-dan-cetak.md): template ijazah untuk blanko fisik dan template transkrip.
- [04-Penerbitan-dan-QR.md](04-Penerbitan-dan-QR.md): status dokumen, verifikasi publik, audit, dan keamanan.
- [05-Rencana-implementasi.md](05-Rencana-implementasi.md): arsitektur, urutan pengerjaan, dan kriteria penerimaan.
- [prototype.html](prototype.html): sketsa halaman admin dan verifikasi menggunakan **data fiktif**. Antarmukanya minimalis dengan Bootstrap 5.3.8 dari `assets/bootstrap/` sehingga dapat dibuka tanpa CDN; belum menyimpan data atau menerbitkan dokumen.
- [contoh-csv/lulusan.csv](contoh-csv/lulusan.csv) dan [contoh-csv/nilai.csv](contoh-csv/nilai.csv): contoh struktur impor dengan data fiktif. Bukan data resmi STIE.

## Keputusan yang sudah disepakati

1. Hanya ada **satu peran internal, Admin**. Tidak ada akun pemeriksa atau pemberi persetujuan terpisah.
2. Aplikasi berdiri sendiri dan mengerjakan ijazah serta transkrip nilai.
3. Identitas lulusan dimasukkan **satu kali** dan dipakai kedua dokumen.
4. Ijazah dicetak sebagai **teks saja di atas blanko fisik**. Logo, nama yayasan/kampus, dan latar sudah tercetak di blanko.
5. Transkrip memakai contoh yang diberikan sebagai acuan struktur; identitas universitas pada contoh tidak dipakai.
6. Admin dapat mengelola template dan mengimpor data CSV. QR dipakai untuk verifikasi catatan ijazah yang telah diterbitkan.

## Bahan acuan

- Contoh ijazah: `D:\STIE Nusantara\Cetak Ijazah 2026\Sampel Ijazah 2026.jpg` (gambar pengguna). Tulisan CONTOH tidak masuk cetakan final.
- Contoh transkrip: gambar yang dilampirkan pengguna dalam percakapan, dari universitas lain. Dipakai untuk struktur, bukan identitas lembaga atau aturan nilai.
- [Permendikbudristek Nomor 50 Tahun 2024](https://www.peraturan.go.id/files/permendikbudristek-no-50-tahun-2024.pdf): rujukan isi minimal ijazah/transkrip dan penerbitan nomor ijazah nasional. Status dan penerapan aturan harus diperiksa kembali sebelum produksi.
- [Portal PISN](https://pisn.kemdiktisaintek.go.id/): rujukan penomoran dan verifikasi nasional. Aplikasi kampus tidak menerbitkan nomor ijazah nasional.
- [Bootstrap 5.3.8](https://getbootstrap.com/docs/5.3/): komponen antarmuka sketsa; lisensi MIT disertakan di `assets/bootstrap/LICENSE`.

## Batas rancangan

SKPI, pengelolaan nilai semester, yudisium, pelaporan PDDikti, dan penerbitan nomor ijazah nasional berada di luar aplikasi ini. Data lulusan dan nilai masuk dari sumber akademik yang telah disahkan; aplikasi ini mengelola persiapan dan keluaran kedua dokumen.
