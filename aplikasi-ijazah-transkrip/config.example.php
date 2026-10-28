<?php
return [
    // URL publik HTTPS yang akan tertanam di QR, tanpa garis miring di akhir.
    'base_url' => 'https://ijazah.example.ac.id',
    'db_path' => __DIR__ . '/storage/app.sqlite',
    'timezone' => 'Asia/Makassar',
    // Kontak resmi yang tampil di halaman verifikasi QR, misalnya bagian akademik. Kosongkan jika tidak ingin ditampilkan.
    'verify_contact' => 'Bagian Akademik STIE Nusantara Makassar · akademik@example.ac.id',
];
