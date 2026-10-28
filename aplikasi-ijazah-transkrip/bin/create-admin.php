<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$username = trim((string) ($argv[1] ?? ''));
if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
    fwrite(STDERR, "Penggunaan: php bin/create-admin.php nama_admin < password-dari-stdin\nNama akun 3-50 karakter: huruf, angka, titik, garis bawah, atau tanda minus.\n");
    exit(1);
}
$password = rtrim((string) fgets(STDIN), "\r\n");
if (strlen($password) < 12) {
    fwrite(STDERR, "Kata sandi minimal 12 karakter.\n");
    exit(1);
}
$stmt = db()->prepare('INSERT INTO admins(username,password_hash,created_at) VALUES(?,?,?) ON CONFLICT(username) DO UPDATE SET password_hash=excluded.password_hash');
$stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), now_local()]);
fwrite(STDOUT, "Akun Admin siap: {$username}\n");
