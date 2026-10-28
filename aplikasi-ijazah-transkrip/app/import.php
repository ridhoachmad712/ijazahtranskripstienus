<?php
declare(strict_types=1);

function import_headers(string $kind): array
{
    return match ($kind) {
        'graduates' => ['nim','nama_lengkap','tempat_lahir','tanggal_lahir','kode_prodi','program_pendidikan','tanggal_lulus','nomor_ijazah_nasional','nomor_transkrip','tanggal_terbit','judul_skripsi','total_sks_resmi','ipk_resmi'],
        default => throw new DomainException('Template impor tidak ditemukan.'),
    };
}

const XLS_TEXT_COLUMNS = ['nim','kode_mk','nomor_ijazah_nasional','nomor_transkrip','tanggal_lahir','tanggal_lulus','tanggal_terbit','nilai_huruf'];

// Menulis berkas .xls dengan sheet Data (baris 1 = nama kolom) dan sheet Petunjuk.
function write_xls_book(string $path, array $headers, array $rows, string $title, array $instructions): void
{
    $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    try {
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Data');
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        foreach ($headers as $index => $header) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValueExplicit($column . '1', $header, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->getColumnDimension($column)->setWidth($header === 'nama_mk' ? 44 : min(28, max(12, strlen($header) + 3)));
            if (in_array($header, XLS_TEXT_COLUMNS, true)) {
                $sheet->getStyle($column . '2:' . $column . '10001')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
            }
        }
        foreach ($rows as $rowIndex => $row) {
            foreach ($headers as $index => $header) {
                $value = $row[$header] ?? '';
                if ($value === '' || $value === null) continue;
                $cell = [$index + 1, $rowIndex + 2];
                if (in_array($header, XLS_TEXT_COLUMNS, true) || !is_numeric($value)) $sheet->setCellValueExplicit($cell, (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                else $sheet->setCellValue($cell, $value + 0);
            }
        }
        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => ['bold'=>true, 'color'=>['rgb'=>'FFFFFF']],
            'fill' => ['fillType'=>\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor'=>['rgb'=>'1D6971']],
            'alignment' => ['vertical'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, 'wrapText'=>true],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(34);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $lastColumn . '1');

        $guide = $book->createSheet();
        $guide->setTitle('Petunjuk');
        $guide->getColumnDimension('A')->setWidth(7);
        $guide->getColumnDimension('B')->setWidth(92);
        $guide->setCellValue('B2', $title);
        $guide->getStyle('B2')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('1D6971');
        foreach ($instructions as $index => $instruction) {
            $guide->setCellValue('A' . ($index + 4), $index + 1);
            $guide->setCellValue('B' . ($index + 4), $instruction);
        }
        $book->setActiveSheetIndex(0);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xls($book))->save($path);
    } finally {
        $book->disconnectWorksheets();
    }
}

function send_xls(string $filename, callable $writer): void
{
    $temporary = tempnam(sys_get_temp_dir(), 'stie-xls-');
    if ($temporary === false) throw new RuntimeException('Template Excel gagal disiapkan.');
    try {
        $writer($temporary);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($temporary));
        readfile($temporary);
    } finally {
        unlink($temporary);
    }
}

function write_xls_template(string $kind, string $path): void
{
    write_xls_book($path, import_headers($kind), [], 'Template data lulusan', [
        'Isi sheet Data mulai baris 2; jangan ubah nama kolom pada baris 1.',
        'NIM dan nomor dokumen harus tetap berupa teks agar nol di depan tidak hilang.',
        'Tanggal ditulis YYYY-MM-DD. Angka desimal memakai titik, misalnya 3.50.',
        'Satu baris untuk setiap lulusan.',
        'Setelah lulusan tersimpan, nilai diisi per mahasiswa melalui menu Nilai.',
    ]);
}

function write_curriculum_template(string $program, string $path): void
{
    $rows = array_map(static fn($course) => ['kode_mk'=>$course['course_code'], 'nama_mk'=>$course['course_name'], 'sks'=>$course['credits']], curriculum_for($program));
    write_xls_book($path, ['kode_mk','nama_mk','sks'], $rows, 'Kurikulum ' . (programs()[$program]['label'] ?? $program), [
        'Satu baris untuk setiap mata kuliah. Urutan baris menjadi urutan mata kuliah pada transkrip.',
        'Kolom kode_mk berupa teks agar nol di depan tidak hilang. SKS berupa angka, misalnya 3.',
        'Jangan ubah nama kolom pada baris 1. Hapus baris yang tidak dipakai.',
        'Mengimpor berkas ini mengganti seluruh daftar mata kuliah prodi setelah Anda memeriksa dan menekan Simpan kurikulum.',
    ]);
}

function write_grade_sheet_template(array $graduate, string $path): void
{
    $letters = [];
    foreach (grades_for((int) $graduate['id']) as $grade) $letters[$grade['course_code']] = $grade['grade_letter'];
    $rows = array_map(static fn($course) => [
        'nim'=>$graduate['nim'], 'urutan'=>$course['position'], 'kode_mk'=>$course['course_code'], 'nama_mk'=>$course['course_name'],
        'sks'=>$course['credits'], 'nilai_huruf'=>$letters[$course['course_code']] ?? '',
    ], curriculum_for($graduate['program_code']));
    write_xls_book($path, ['nim','urutan','kode_mk','nama_mk','sks','nilai_huruf'], $rows, 'Nilai ' . $graduate['name'] . ' (NIM ' . $graduate['nim'] . ')', [
        'Cukup isi kolom nilai_huruf. Kolom lain berasal dari kurikulum prodi dan tidak dipakai saat impor.',
        'Nilai huruf yang diterima: ' . implode(', ', array_keys(grade_scale())) . '. Kosongkan jika mata kuliah tidak diambil.',
        'Berkas ini khusus untuk NIM ' . $graduate['nim'] . '. Berkas dengan NIM lain akan ditolak.',
        'Hasil impor tampil di halaman nilai mahasiswa dan baru tersimpan setelah Anda menekan Simpan nilai.',
    ]);
}

function curriculum_rows_from_upload(array $file): array
{
    return curriculum_rows_from_import(read_import_upload($file, ['kode_mk','nama_mk','sks']));
}

function curriculum_rows_from_import(array $rows): array
{
    if (!$rows) throw new DomainException('Berkas kurikulum belum berisi mata kuliah.');
    return validated_curriculum_rows(array_map(static fn($row) => ['course_code'=>$row['kode_mk'], 'course_name'=>$row['nama_mk'], 'credits'=>$row['sks'], '_line'=>$row['_line']], $rows));
}

// Mengembalikan nilai huruf per kode MK dari berkas; kode yang tidak ada di berkas tidak diubah.
function grade_letters_from_upload(array $graduate, array $file): array
{
    return grade_letters_from_import($graduate, read_import_upload($file, ['kode_mk','nilai_huruf']));
}

function grade_letters_from_import(array $graduate, array $rows): array
{
    $curriculum = array_column(curriculum_for($graduate['program_code']), null, 'course_code');
    $scale = grade_scale();
    $letters = [];
    $errors = [];
    foreach ($rows as $row) {
        $line = $row['_line'];
        if (($row['nim'] ?? '') !== '' && $row['nim'] !== $graduate['nim']) {
            $errors[] = 'Baris ' . $line . ': NIM ' . $row['nim'] . ' bukan milik ' . $graduate['name'] . '.';
            continue;
        }
        $code = $row['kode_mk'];
        $letter = strtoupper(trim($row['nilai_huruf']));
        if (!isset($curriculum[$code])) $errors[] = 'Baris ' . $line . ': kode ' . $code . ' tidak ada di kurikulum prodi.';
        elseif (array_key_exists($code, $letters)) $errors[] = 'Baris ' . $line . ': kode ' . $code . ' ganda.';
        elseif ($letter !== '' && !array_key_exists($letter, $scale)) $errors[] = 'Baris ' . $line . ': nilai huruf "' . $letter . '" tidak ada di skala nilai.';
        else $letters[$code] = $letter;
    }
    if (!$rows) $errors[] = 'Berkas nilai belum berisi data.';
    if ($errors) throw new DomainException(implode(' ', array_slice($errors, 0, 20)) . (count($errors) > 20 ? ' (dan ' . (count($errors) - 20) . ' masalah lain)' : ''));
    return $letters;
}

function read_import_upload(array $file, array $requiredHeaders): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
        throw new DomainException('Pilih berkas yang akan diimpor.');
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) throw new DomainException('Ukuran setiap berkas maksimal 5 MB.');
    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    return match ($extension) {
        'xls', 'xlsx' => read_excel_file((string) $file['tmp_name'], $extension, $requiredHeaders),
        'csv' => read_csv_upload($file, $requiredHeaders),
        default => throw new DomainException('Gunakan berkas .xls, .xlsx, atau .csv.'),
    };
}

function read_excel_file(string $path, string $extension, array $requiredHeaders): array
{
    $format = $extension === 'xls' ? 'Xls' : 'Xlsx';
    try {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($format);
        if (!$reader->canRead($path)) throw new DomainException('Berkas Excel tidak sesuai dengan ekstensi .'. $extension . '.');
        $book = $reader->load($path);
    } catch (DomainException $e) {
        throw $e;
    } catch (Throwable $e) {
        throw new DomainException('Berkas Excel tidak dapat dibaca.', 0, $e);
    }
    try {
        $sheet = $book->getSheetByName('Data') ?? $book->getSheet(0);
        $columnCount = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        if ($columnCount > 40) throw new DomainException('Berkas Excel memiliki terlalu banyak kolom.');
        $headers = [];
        for ($column = 1; $column <= $columnCount; $column++) {
            $headers[] = trim((string) $sheet->getCell([$column, 1])->getValue());
        }
        if (!$headers || $headers === ['']) throw new DomainException('Header Excel kosong.');
        $missing = array_diff($requiredHeaders, $headers);
        if ($missing) throw new DomainException('Kolom Excel tidak ada: ' . implode(', ', $missing));
        if (in_array('', $headers, true) || count($headers) !== count(array_unique($headers))) throw new DomainException('Header Excel kosong atau ganda.');
        $rows = [];
        $lastRow = $sheet->getHighestDataRow();
        for ($line = 2; $line <= $lastRow; $line++) {
            $row = [];
            $hasValue = false;
            foreach ($headers as $index => $header) {
                $cell = $sheet->getCell([$index + 1, $line]);
                if ($cell->getDataType() === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA) throw new DomainException('Rumus Excel tidak diperbolehkan pada baris ' . $line . '.');
                $raw = $cell->getValue();
                if ($raw === null || $raw === '') {
                    $value = '';
                } elseif (in_array($header, ['tanggal_lahir','tanggal_lulus','tanggal_terbit'], true) && is_numeric($raw) && \PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
                    $value = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $raw)->format('Y-m-d');
                } elseif (is_numeric($raw) && in_array($header, ['nim','kode_mk','nomor_ijazah_nasional','nomor_transkrip'], true)) {
                    $value = trim($cell->getFormattedValue());
                } else {
                    $value = trim((string) $raw);
                }
                $row[$header] = $value;
                $hasValue = $hasValue || $value !== '';
            }
            if (!$hasValue) continue;
            $row['_line'] = $line;
            $rows[] = $row;
            if (count($rows) > 10000) throw new DomainException('Maksimal 10.000 baris per berkas.');
        }
        return $rows;
    } finally {
        $book->disconnectWorksheets();
    }
}

function read_csv_upload(array $file, array $requiredHeaders): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
        throw new DomainException('Pilih berkas CSV yang akan diimpor.');
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) throw new DomainException('Ukuran setiap CSV maksimal 5 MB.');
    $handle = fopen($file['tmp_name'], 'rb');
    if (!$handle) throw new DomainException('CSV tidak dapat dibaca.');
    try {
        $first = fgets($handle);
        if ($first === false) throw new DomainException('CSV kosong.');
        $first = preg_replace('/^\xEF\xBB\xBF/', '', $first);
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        rewind($handle);
        $headers = fgetcsv($handle, 0, $delimiter, '"', '');
        if (!$headers) throw new DomainException('Header CSV kosong.');
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        $headers = array_map(static fn($value) => trim((string) $value), $headers);
        $missing = array_diff($requiredHeaders, $headers);
        if ($missing) throw new DomainException('Kolom CSV tidak ada: ' . implode(', ', $missing));
        if (count($headers) !== count(array_unique($headers))) throw new DomainException('Header CSV ganda.');
        $rows = [];
        $line = 1;
        while (($values = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            $line++;
            if ($values === [null]) continue;
            if (count($values) !== count($headers)) throw new DomainException('Jumlah kolom berbeda pada baris ' . $line . '.');
            $row = array_combine($headers, array_map(static fn($value) => trim((string) $value), $values));
            $row['_line'] = $line;
            $rows[] = $row;
            if (count($rows) > 10000) throw new DomainException('Maksimal 10.000 baris per CSV.');
        }
        return $rows;
    } finally {
        fclose($handle);
    }
}

function make_import_preview(array $file, string $kind): array
{
    if ($kind !== 'graduates') throw new DomainException('Jenis impor tidak dikenal.');
    $rows = read_import_upload($file, ['nim','nama_lengkap','tempat_lahir','tanggal_lahir','kode_prodi','tanggal_lulus','nomor_ijazah_nasional','nomor_transkrip','tanggal_terbit','total_sks_resmi','ipk_resmi']);
    return make_import_preview_from_rows($rows, $kind);
}

function make_import_preview_from_rows(array $rows, string $kind): array
{
    if ($kind !== 'graduates') throw new DomainException('Jenis impor tidak dikenal.');
    $errors = [];
    if (!$rows) $errors[] = 'Berkas lulusan belum berisi data.';
    $mapped = [];
    $seen = [];
    foreach ($rows as $row) {
        $data = graduate_input([
            'nim'=>$row['nim'], 'name'=>$row['nama_lengkap'], 'birth_place'=>$row['tempat_lahir'],
            'birth_date'=>$row['tanggal_lahir'], 'program_code'=>strtoupper($row['kode_prodi']),
            'education_program'=>$row['program_pendidikan'] ?? 'S1', 'graduation_date'=>$row['tanggal_lulus'],
            'diploma_number'=>$row['nomor_ijazah_nasional'], 'transcript_number'=>$row['nomor_transkrip'],
            'issue_date'=>$row['tanggal_terbit'], 'thesis_title'=>$row['judul_skripsi'] ?? '',
            'official_credits'=>$row['total_sks_resmi'], 'official_gpa'=>$row['ipk_resmi'],
        ]);
        $nim = $data['nim'];
        if (isset($seen[$nim])) $errors[] = 'Lulusan baris ' . $row['_line'] . ': NIM ganda dalam berkas.';
        $seen[$nim] = true;
        $existing = find_graduate_by_nim($nim);
        foreach (graduate_errors($data, $existing ? (int) $existing['id'] : null) as $error) $errors[] = 'Lulusan baris ' . $row['_line'] . ': ' . $error;
        if ($existing && graduate_has_issued_document((int) $existing['id'])) $errors[] = 'Lulusan baris ' . $row['_line'] . ': ada dokumen aktif; impor tidak boleh mengubah data ini.';
        $mapped[$nim] = $data;
    }
    return ['kind'=>$kind,'graduates'=>$mapped,'errors'=>array_slice($errors,0,200),'graduate_count'=>count($mapped),'created_at'=>now_local()];
}

function save_import_preview(array $preview): string
{
    $token = bin2hex(random_bytes(20));
    $directory = rtrim((string) app_config()['storage_root'], '/\\') . '/imports';
    if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) throw new RuntimeException('Direktori impor tidak dapat dibuat.');
    $path = $directory . '/' . $token . '.json';
    if (file_put_contents($path, json_encode($preview, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), LOCK_EX) === false) throw new RuntimeException('Pratinjau impor gagal disimpan.');
    $_SESSION['import_token'] = $token;
    return $token;
}

function load_import_preview(): ?array
{
    $token = $_SESSION['import_token'] ?? '';
    if (!preg_match('/^[a-f0-9]{40}$/', $token)) return null;
    $path = rtrim((string) app_config()['storage_root'], '/\\') . '/imports/' . $token . '.json';
    if (!is_file($path)) return null;
    return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
}

function commit_import(string $kind): array
{
    $preview = load_import_preview();
    if (!$preview || ($preview['kind'] ?? '') !== $kind || $preview['errors']) throw new DomainException('Impor belum siap. Perbaiki semua kesalahan pada berkas.');
    $db = db();
    $db->beginTransaction();
    try {
        foreach ($preview['graduates'] as $nim => $graduateData) {
            $existing = find_graduate_by_nim((string) $nim);
            if ($existing && graduate_has_issued_document((int) $existing['id'])) throw new DomainException('NIM ' . $nim . ': ada dokumen aktif; data tidak dapat ditimpa.');
            save_graduate($graduateData, $existing ? (int) $existing['id'] : null);
        }
        audit('import', null, 'committed', $kind . ': ' . $preview['graduate_count'] . ' lulusan');
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
    $token = $_SESSION['import_token'];
    unset($_SESSION['import_token']);
    $path = rtrim((string) app_config()['storage_root'], '/\\') . '/imports/' . $token . '.json';
    if (is_file($path)) unlink($path);
    return $preview;
}
