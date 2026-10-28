<?php
declare(strict_types=1);

function import_headers(string $kind): array
{
    return match ($kind) {
        'graduates' => ['nim','nama_lengkap','tempat_lahir','tanggal_lahir','kode_prodi','program_pendidikan','tanggal_lulus','nomor_ijazah_nasional','nomor_transkrip','tanggal_terbit','judul_skripsi','total_sks_resmi','ipk_resmi'],
        'grades' => ['nim','urutan','kode_mk','nama_mk','kelompok_mk','sks','nilai_huruf','bobot_nilai'],
        default => throw new DomainException('Template impor tidak ditemukan.'),
    };
}

function write_xls_template(string $kind, string $path): void
{
    $headers = import_headers($kind);
    $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    try {
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Data');
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        foreach ($headers as $index => $header) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValueExplicit($column . '1', $header, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->getColumnDimension($column)->setWidth(min(28, max(16, strlen($header) + 3)));
            if (in_array($header, ['nim','kode_mk','nomor_ijazah_nasional','nomor_transkrip','tanggal_lahir','tanggal_lulus','tanggal_terbit'], true)) {
                $sheet->getStyle($column . '2:' . $column . '10001')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
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
        $guide->setCellValue('B2', $kind === 'graduates' ? 'Template data lulusan' : 'Template nilai mata kuliah');
        $guide->getStyle('B2')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('1D6971');
        $instructions = [
            'Isi sheet Data mulai baris 2; jangan ubah nama kolom pada baris 1.',
            'NIM dan nomor dokumen/kode mata kuliah harus tetap berupa teks agar nol di depan tidak hilang.',
            'Tanggal ditulis YYYY-MM-DD. Angka desimal memakai titik, misalnya 3.50.',
            $kind === 'graduates' ? 'Satu baris untuk setiap lulusan.' : 'Satu baris untuk setiap mata kuliah per lulusan.',
            $kind === 'graduates' ? 'Impor data lulusan lebih dahulu melalui menu Impor lulusan.' : 'NIM harus sudah tersimpan di Data lulusan sebelum berkas nilai diimpor.',
        ];
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
    if (!in_array($kind, ['graduates', 'grades'], true)) throw new DomainException('Jenis impor tidak dikenal.');
    $required = $kind === 'graduates'
        ? ['nim','nama_lengkap','tempat_lahir','tanggal_lahir','kode_prodi','tanggal_lulus','nomor_ijazah_nasional','nomor_transkrip','tanggal_terbit','total_sks_resmi','ipk_resmi']
        : ['nim','urutan','kode_mk','nama_mk','sks','nilai_huruf','bobot_nilai'];
    $rows = read_import_upload($file, $required);
    return make_import_preview_from_rows($rows, $kind);
}

function make_import_preview_from_rows(array $rows, string $kind): array
{
    if (!in_array($kind, ['graduates', 'grades'], true)) throw new DomainException('Jenis impor tidak dikenal.');
    $errors = [];
    if (!$rows) $errors[] = $kind === 'graduates' ? 'Berkas lulusan belum berisi data.' : 'Berkas nilai belum berisi data.';
    $mapped = [];
    $seen = [];
    if ($kind === 'graduates') {
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
    }
    $gradeMap = [];
    $gradeSeen = [];
    if ($kind === 'grades') {
        $graduateCache = [];
        foreach ($rows as $row) {
            $nim = $row['nim'];
            if (!array_key_exists($nim, $graduateCache)) {
                $graduateCache[$nim] = find_graduate_by_nim($nim);
                if (!$graduateCache[$nim]) $errors[] = 'Nilai baris ' . $row['_line'] . ': NIM belum ada di Data lulusan.';
                elseif (graduate_has_issued_document((int) $graduateCache[$nim]['id'], 'transcript')) $errors[] = 'NIM ' . $nim . ': ada transkrip aktif; nilai tidak boleh diimpor ulang.';
            }
            $key = $nim . ':' . $row['kode_mk'];
            if (isset($gradeSeen[$key])) $errors[] = 'Nilai baris ' . $row['_line'] . ': kode MK ganda untuk NIM tersebut.';
            $gradeSeen[$key] = true;
            $credits = filter_var($row['sks'], FILTER_VALIDATE_FLOAT);
            $weight = filter_var($row['bobot_nilai'], FILTER_VALIDATE_FLOAT);
            $position = filter_var($row['urutan'], FILTER_VALIDATE_INT);
            if ($nim === '' || $row['kode_mk'] === '' || $row['nama_mk'] === '' || $row['nilai_huruf'] === '' || $position === false || $position < 1 || $credits === false || $credits <= 0 || $credits > 20 || $weight === false || $weight < 0 || $weight > 4) $errors[] = 'Nilai baris ' . $row['_line'] . ': data mata kuliah/nilai tidak valid.';
            $gradeMap[$nim][] = [
                'position'=>$row['urutan'],'course_code'=>$row['kode_mk'],'course_name'=>$row['nama_mk'],
                'group_name'=>$row['kelompok_mk'] ?? '', 'credits'=>$row['sks'],
                'grade_letter'=>$row['nilai_huruf'],'grade_weight'=>$row['bobot_nilai'],
            ];
        }
        foreach ($gradeMap as $nim => $studentGrades) {
            $graduate = $graduateCache[$nim];
            if ($graduate && has_official_totals($graduate)) {
                $summary = grade_summary($studentGrades);
                if (abs($summary['credits'] - (float) $graduate['official_credits']) > 0.001 || abs($summary['gpa'] - (float) $graduate['official_gpa']) > 0.011) $errors[] = 'NIM ' . $nim . ': total SKS/IPK resmi tidak cocok dengan berkas nilai.';
            }
        }
    }
    return ['kind'=>$kind,'graduates'=>$mapped,'grades'=>$gradeMap,'errors'=>array_slice($errors,0,200),'graduate_count'=>count($mapped),'grade_count'=>$kind === 'grades' ? count($rows) : 0,'created_at'=>now_local()];
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
        if ($kind === 'graduates') {
            foreach ($preview['graduates'] as $nim => $graduateData) {
                $existing = find_graduate_by_nim((string) $nim);
                if ($existing && graduate_has_issued_document((int) $existing['id'])) throw new DomainException('NIM ' . $nim . ': ada dokumen aktif; data tidak dapat ditimpa.');
                save_graduate($graduateData, $existing ? (int) $existing['id'] : null);
            }
        } else {
            foreach ($preview['grades'] as $nim => $grades) {
                $graduate = find_graduate_by_nim((string) $nim);
                if (!$graduate) throw new DomainException('NIM ' . $nim . ' belum ada di Data lulusan. Periksa kembali berkas nilai.');
                $id = (int) $graduate['id'];
                if (graduate_has_issued_document($id, 'transcript')) throw new DomainException('NIM ' . $nim . ': ada transkrip aktif; nilai tidak dapat ditimpa.');
                if (has_official_totals($graduate)) {
                    $summary = grade_summary($grades);
                    if (abs($summary['credits'] - (float) $graduate['official_credits']) > 0.001 || abs($summary['gpa'] - (float) $graduate['official_gpa']) > 0.011) throw new DomainException('NIM ' . $nim . ': total SKS/IPK resmi tidak cocok dengan berkas nilai.');
                }
                $db->prepare('DELETE FROM grades WHERE graduate_id=?')->execute([$id]);
                foreach ($grades as $grade) save_grade($id, $grade);
            }
        }
        audit('import', null, 'committed', $kind . ': ' . $preview['graduate_count'] . ' lulusan; ' . $preview['grade_count'] . ' nilai');
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
