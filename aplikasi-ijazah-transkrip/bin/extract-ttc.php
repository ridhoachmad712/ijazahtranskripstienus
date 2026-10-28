<?php
declare(strict_types=1);

// Mengambil satu font dari TrueType Collection (.ttc), misalnya Cambria Regular dari C:\Windows\Fonts\cambria.ttc,
// menjadi .ttf yang dapat dipakai Dompdf. Penggunaan: php bin/extract-ttc.php cambria.ttc cambria.ttf [Cambria]

[$script, $input, $output, $family] = $argv + [null, null, null, 'Cambria'];
if ($input === null || $output === null || !is_file($input)) {
    fwrite(STDERR, "Penggunaan: php bin/extract-ttc.php berkas.ttc hasil.ttf [nama keluarga, bawaan Cambria]\n");
    exit(1);
}
$data = (string) file_get_contents($input);
if (substr($data, 0, 4) !== 'ttcf') {
    fwrite(STDERR, "Berkas bukan TrueType Collection (.ttc).\n");
    exit(1);
}

$u16 = static fn(int $at): int => unpack('n', $data, $at)[1];
$u32 = static fn(int $at): int => unpack('N', $data, $at)[1];
$tables = static function (int $offset) use ($data, $u16, $u32): array {
    $list = [];
    for ($i = 0, $count = $u16($offset + 4); $i < $count; $i++) {
        $entry = $offset + 12 + $i * 16;
        $list[substr($data, $entry, 4)] = [$u32($entry + 4), $u32($entry + 8), $u32($entry + 12)];
    }
    return $list;
};
$familyName = static function (int $offset) use ($data, $u16, $tables): string {
    $name = $tables($offset)['name'][1] ?? null;
    if ($name === null) return '';
    for ($i = 0, $count = $u16($name + 2); $i < $count; $i++) {
        $record = $name + 6 + $i * 12;
        if ($u16($record) === 3 && $u16($record + 6) === 1) {
            $raw = substr($data, $name + $u16($name + 4) + $u16($record + 10), $u16($record + 8));
            return mb_convert_encoding($raw, 'UTF-8', 'UTF-16BE');
        }
    }
    return '';
};

for ($i = 0, $fonts = $u32(8); $i < $fonts; $i++) {
    $offset = $u32(12 + $i * 4);
    if ($familyName($offset) !== $family) continue;
    // Tabel di dalam koleksi memakai posisi relatif terhadap seluruh berkas; susun ulang menjadi .ttf mandiri.
    $entries = $tables($offset);
    $header = substr($data, $offset, 12);
    $position = 12 + 16 * count($entries);
    $directory = $body = '';
    foreach ($entries as $tag => [$checksum, $tableOffset, $length]) {
        $directory .= $tag . pack('NNN', $checksum, $position + strlen($body), $length);
        $body .= substr($data, $tableOffset, $length) . str_repeat("\0", (4 - $length % 4) % 4);
    }
    file_put_contents($output, $header . $directory . $body);
    fwrite(STDOUT, "Font {$family} disimpan ke {$output}\n");
    exit(0);
}
fwrite(STDERR, "Font {$family} tidak ditemukan di dalam {$input}.\n");
exit(1);
