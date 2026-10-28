# Template dan pencetakan

## Ijazah: teks di atas blanko fisik

- Ukuran awal dari sampel: **A4 lanskap, 297 × 210 mm**. Konfirmasi ukuran blanko fisik dengan penggaris sebelum mengunci ukuran produksi.
- Dalam editor, gambar blanko dapat dipakai sebagai **lapisan panduan**. Lapisan ini, logo, nama yayasan/kampus, latar, dan tulisan `CONTOH` tidak muncul pada cetakan.
- Koordinat setiap bidang disimpan dalam mm dari pojok kiri atas: `x`, `y`, lebar maksimum, ukuran huruf pt, jenis huruf, perataan, dan tinggi baris.
- Admin dapat menggeser bidang pada pratinjau dan memasukkan angka mm secara presisi. Tersedia garis tengah 148,5 mm dan cetak uji pada kertas biasa.
- Cetak ukuran **100%**, tanpa header/footer browser dan tanpa margin tambahan. Aplikasi memperingatkan jika setelan cetak belum dapat dikendalikan dari browser.
- PDF pratinjau/arsip bisa dihasilkan, tetapi hasil akhir untuk blanko tetap harus diuji pada printer dan tray yang benar. Kalibrasi `offset_x`/`offset_y` disimpan per printer atau profil cetak, tidak mengubah versi template dokumen.

### Bidang yang tampak pada sampel ijazah

| Bidang | Sumber | Catatan |
|---|---|---|
| Nomor ijazah nasional | Profil lulusan | Di kanan atas; wajib berasal dari sumber resmi. |
| Akreditasi institusi | Template umum | Cetak hanya bila belum menjadi bagian dari blanko dan redaksinya disahkan. |
| Kalimat pembuka/penyelesaian | Template umum/prodi | Versi redaksi dapat berubah antarangkatan. |
| Nama lulusan dan gelar | Profil + prodi | Gunakan nama resmi; aturan penempatan gelar disepakati kampus. |
| Tempat/tanggal lahir dan NIM | Profil lulusan | Format tanggal tampilan, NIM tetap teks. |
| Program studi dan akreditasi | Template prodi | Akreditasi Manajemen belum boleh diisi dengan perkiraan. |
| Gelar dan kalimat hak | Template prodi | Gelar sesuai keputusan resmi prodi. |
| Tempat/tanggal penerbitan | Profil/dokumen terbit | Tanggal kelulusan disimpan terpisah. |
| Jabatan, nama, NIDN pejabat | Template versi/pejabat | Dibekukan pada versi dokumen terbit. |
| QR | Dokumen terbit | Posisi ditetapkan setelah tersedia blanko kosong dan tes fisik; QR tidak boleh menimpa unsur cetak/tanda tangan. |

Nama kampus pada sampel mungkin tampak sebagai teks di halaman, tetapi pengguna telah memastikan nama kampus **sudah ada pada blanko**. Karena itu, bidang tersebut harus dinonaktifkan dalam template cetak agar tidak tercetak dua kali. Ketentuan yang sama berlaku untuk logo dan nama yayasan.

## Transkrip: dokumen lengkap

Asumsi rancangan: transkrip dicetak lengkap di kertas kosong. Ini perlu dikonfirmasi sebelum kalibrasi akhir. Sampel dari universitas lain hanya acuan pembagian area, bukan sumber logo, alamat, redaksi akreditasi, nilai, atau pejabat STIE.

1. **Header**: logo STIE, nama resmi, alamat/kontak, nomor transkrip, judul. Nama/kode PT, program, dan prodi ditampilkan sesuai ketentuan yang berlaku.
2. **Profil lulusan**: nama, NIM, tempat/tanggal lahir, prodi, jenjang, tanggal lulus, dan nomor ijazah nasional bila kampus menetapkannya pada transkrip.
3. **Tabel nilai**: nomor, kode MK, nama MK, SKS, nilai huruf, bobot/mutu; kelompok mata kuliah opsional. Urutan berasal dari kurikulum atau urutan cetak yang disimpan, bukan urutan impor kebetulan.
4. **Ringkasan**: total SKS, total mutu, IPK, predikat, judul skripsi jika dipakai dan disahkan.
5. **Pengesahan**: tempat/tanggal penerbitan, jabatan, nama, dan ruang tanda tangan/stempel sesuai mekanisme kampus.

Tabel dua kolom seperti sampel boleh dipilih **jika tetap terbaca**. Bila jumlah mata kuliah bertambah, tabel berlanjut ke halaman berikutnya. Setiap halaman punya nomor halaman, identitas lulusan ringkas, dan header tabel. Jangan mengecilkan huruf tanpa batas hanya untuk memaksa satu halaman. Ringkasan akhir dan tanda tangan ditempatkan pada halaman terakhir; hindari memisahkan tanda tangan dari konteks dokumen.

## Versi template dan pemeriksaan visual

- Versi template aktif digunakan hanya untuk draf baru. Dokumen terbit mengacu pada versi yang dibekukan.
- Perubahan template meminta nama versi, alasan, dan tanggal mulai berlaku.
- Pemeriksaan wajib mencakup nama panjang, nomor panjang, mata kuliah panjang, jumlah mata kuliah maksimum yang realistis, transkrip banyak halaman, serta cetak ijazah tanpa lapisan blanko.
- Bandingkan cetak uji kertas biasa dengan blanko fisik menggunakan meja cahaya/ditumpuk sebelum mencetak blanko asli.
