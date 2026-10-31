<?php
declare(strict_types=1);

$root = sys_get_temp_dir() . '/stie-doc-smoke-' . bin2hex(random_bytes(5));
mkdir($root, 0700, true);
putenv('STIE_DB_PATH=:memory:');
putenv('STIE_STORAGE_ROOT=' . $root);
putenv('STIE_BASE_URL=https://ijazah.example.test');
require dirname(__DIR__) . '/vendor/autoload.php';
require APP_ROOT . '/app/views.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

try {
    expect((int) db()->query('SELECT COUNT(*) FROM templates')->fetchColumn() === 2, 'Template awal harus satu ijazah dan satu transkrip.');
    db()->prepare('INSERT INTO admins(username,password_hash,created_at) VALUES(?,?,?)')->execute(['uji-admin', password_hash('uji-saja', PASSWORD_DEFAULT), now_local()]);
    $_SESSION['admin_id'] = (int) db()->lastInsertId();
    ob_start();
    page_header('Uji menu', 'import');
    page_footer();
    $emptyMenu = (string) ob_get_clean();
    expect(!str_contains($emptyMenu, 'r=grades'), 'Menu Nilai muncul sebelum ada lulusan.');
    $sample = ['nim'=>'0012345678','nama_lengkap'=>'Contoh Lulusan','tempat_lahir'=>'Makassar','tanggal_lahir'=>'2002-04-18','kode_prodi'=>'AKUNTANSI','program_pendidikan'=>'S1','tanggal_lulus'=>'2026-08-15','nomor_ijazah_nasional'=>'CONTOH-001','nomor_transkrip'=>'CONTOH-TR-001','tanggal_terbit'=>'2026-09-01','judul_skripsi'=>'Contoh judul skripsi','total_sks_resmi'=>'6','ipk_resmi'=>'3.50'];
    $path = $root . '/template-lulusan.xls';
    write_xls_template('graduates', $path);
    expect(bin2hex((string) file_get_contents($path, false, null, 0, 8)) === 'd0cf11e0a1b11ae1', 'Template harus berupa XLS asli.');
    $workbook = (new \PhpOffice\PhpSpreadsheet\Reader\Xls())->load($path);
    $sheet = $workbook->getSheetByName('Data');
    expect($sheet !== null && $sheet->getStyle('A2')->getNumberFormat()->getFormatCode() === '@', 'Kolom NIM tidak disiapkan sebagai teks.');
    foreach ($sample as $header => $value) $sheet->setCellValueExplicit([array_search($header, import_headers('graduates'), true) + 1, 2], $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    (new \PhpOffice\PhpSpreadsheet\Writer\Xls($workbook))->save($path);
    $workbook->disconnectWorksheets();
    $rows = read_excel_file($path, 'xls', import_headers('graduates'));
    expect(count($rows) === 1 && $rows[0]['nim'] === '0012345678', 'XLS gagal dibaca atau nol awal NIM hilang.');

    $newGraduate = array_replace($sample, ['nim'=>'0088888888','nomor_ijazah_nasional'=>'CONTOH-IMP-001','nomor_transkrip'=>'CONTOH-IMP-TR-001','total_sks_resmi'=>'5','ipk_resmi'=>'3.60']);
    $graduatePreview = make_import_preview_from_rows([array_replace($newGraduate, ['_line'=>2])], 'graduates');
    expect(!$graduatePreview['errors'] && $graduatePreview['graduate_count'] === 1, 'Pratinjau impor lulusan gagal.');
    save_import_preview($graduatePreview);
    commit_import('graduates');
    $imported = find_graduate_by_nim('0088888888');
    expect($imported !== null, 'Lulusan belum tersimpan sesudah impor.');
    $importedId = (int) $imported['id'];
    ob_start();
    page_header('Uji menu', 'grades');
    page_footer();
    $readyMenu = (string) ob_get_clean();
    expect(str_contains($readyMenu, 'r=grades') && str_contains($readyMenu, 'r=curriculum'), 'Menu Nilai/Kurikulum belum muncul setelah lulusan tersimpan.');

    // Kurikulum per prodi: urutan baris, validasi, dan template Excel.
    expect(grade_scale() === ['A'=>4.0,'B'=>3.0,'C'=>2.0,'D'=>1.0,'E'=>0.0], 'Skala nilai awal tidak sesuai.');
    foreach ([
        'kode ganda' => ['course_code'=>['MK1','MK1'], 'course_name'=>['Satu','Dua'], 'credits'=>['2','3']],
        'SKS nol' => ['course_code'=>['MK1'], 'course_name'=>['Satu'], 'credits'=>['0']],
        'nama kosong' => ['course_code'=>['MK1'], 'course_name'=>[''], 'credits'=>['2']],
    ] as $label => $source) {
        try {
            curriculum_rows_from_form($source);
            throw new RuntimeException('Kurikulum dengan ' . $label . ' seharusnya ditolak.');
        } catch (DomainException) {
        }
    }
    $curriculumRows = curriculum_rows_from_form(['course_code'=>['001AKT','AKT102',''], 'course_name'=>['Pengantar Akuntansi','Akuntansi Biaya',''], 'credits'=>['3','2','']]);
    expect(count($curriculumRows) === 2 && $curriculumRows[1]['position'] === 2, 'Baris kosong atau urutan kurikulum salah.');
    save_curriculum('AKUNTANSI', $curriculumRows);
    expect(array_column(curriculum_for('AKUNTANSI'), 'course_code') === ['001AKT','AKT102'] && curriculum_for('MANAJEMEN') === [], 'Kurikulum tersimpan pada prodi yang salah.');
    $curriculumPath = $root . '/kurikulum.xls';
    write_curriculum_template('AKUNTANSI', $curriculumPath);
    $fromFile = curriculum_rows_from_import(read_excel_file($curriculumPath, 'xls', ['kode_mk','nama_mk','sks']));
    expect(array_column($fromFile, 'course_code') === ['001AKT','AKT102'] && $fromFile[0]['credits'] === 3.0, 'Template kurikulum gagal dibaca ulang atau nol awal kode hilang.');

    // Nilai per mahasiswa: hanya nilai huruf, bobot dari skala.
    save_grade($importedId, ['position'=>1,'course_code'=>'LAMA01','course_name'=>'Mata kuliah lama','credits'=>2,'grade_letter'=>'B','grade_weight'=>3]);
    expect(save_grade_sheet($importedId, ['001AKT'=>'a', 'AKT102'=>'B']) === 2, 'Lembar nilai tidak menyimpan nilai huruf.');
    $saved = array_column(grades_for($importedId), null, 'course_code');
    expect($saved['001AKT']['grade_weight'] == 4 && $saved['001AKT']['grade_letter'] === 'A' && $saved['AKT102']['credits'] == 2 && isset($saved['LAMA01']), 'Bobot dari skala atau nilai di luar kurikulum tidak sesuai.');
    expect(array_column(grades_for($importedId), 'course_code') === ['001AKT','AKT102','LAMA01'], 'Urutan nilai tidak mengikuti kurikulum.');
    foreach (['huruf di luar skala' => static fn() => save_grade_sheet($importedId, ['001AKT'=>'Z']), 'prodi tanpa kurikulum' => static fn() => save_grade_sheet((int) save_graduate(['nim'=>'0066666666','name'=>'Mahasiswa Manajemen','program_code'=>'MANAJEMEN']), [])] as $label => $action) {
        try {
            $action();
            throw new RuntimeException('Lembar nilai dengan ' . $label . ' seharusnya ditolak.');
        } catch (DomainException) {
        }
    }
    $gradePath = $root . '/nilai.xls';
    write_grade_sheet_template($imported, $gradePath);
    $gradeRows = read_excel_file($gradePath, 'xls', ['kode_mk','nilai_huruf']);
    expect(count($gradeRows) === 2 && $gradeRows[0]['nim'] === '0088888888' && $gradeRows[0]['nilai_huruf'] === 'A', 'Template nilai mahasiswa tidak berisi kurikulum dan nilai tersimpan.');
    $gradeRows[1]['nilai_huruf'] = 'c';
    expect(grade_letters_from_import($imported, $gradeRows) === ['001AKT'=>'A', 'AKT102'=>'C'], 'Impor nilai huruf tidak terbaca.');
    foreach (['NIM lain' => array_replace($gradeRows[0], ['nim'=>'0011111111']), 'kode di luar kurikulum' => array_replace($gradeRows[0], ['kode_mk'=>'XYZ']), 'huruf di luar skala' => array_replace($gradeRows[0], ['nilai_huruf'=>'Z'])] as $label => $row) {
        try {
            grade_letters_from_import($imported, [$row]);
            throw new RuntimeException('Impor nilai dengan ' . $label . ' seharusnya ditolak.');
        } catch (DomainException) {
        }
    }
    try {
        save_grade_scale(['A','A'], ['4','3']);
        throw new RuntimeException('Skala nilai ganda seharusnya ditolak.');
    } catch (DomainException) {
    }
    ob_start();
    grades_page('');
    grade_sheet_page($imported);
    $gradesPages = (string) ob_get_clean();
    expect(str_contains($gradesPages, 'Isi nilai') && str_contains($gradesPages, 'name="letter[]"') && str_contains($gradesPages, 'Nilai di luar kurikulum'), 'Halaman nilai per mahasiswa tidak lengkap.');

    save_import_preview(make_import_preview_from_rows([array_replace($newGraduate, ['_line'=>2])], 'graduates'));
    commit_import('graduates');
    expect(count(grades_for($importedId)) === 3, 'Impor ulang lulusan menghapus nilai yang sudah tersimpan.');
    $blankTotals = array_replace($newGraduate, ['nim'=>'0077777777','nomor_ijazah_nasional'=>'CONTOH-IMP-002','nomor_transkrip'=>'CONTOH-IMP-TR-002','total_sks_resmi'=>'','ipk_resmi'=>'','_line'=>2]);
    save_import_preview(make_import_preview_from_rows([$blankTotals], 'graduates'));
    commit_import('graduates');
    $blankGraduate = find_graduate_by_nim('0077777777');
    expect($blankGraduate['official_credits'] === null && $blankGraduate['official_gpa'] === null, 'SKS/IPK resmi kosong harus tersimpan sebagai NULL.');
    expect(save_grade_sheet((int) $blankGraduate['id'], ['001AKT'=>'B']) === 1, 'Nilai ditolak padahal SKS/IPK resmi belum diisi.');
    expect(official_totals_match(grade_summary(grades_for((int) $blankGraduate['id'])), $blankGraduate) === null, 'Total resmi kosong harus dianggap belum dapat dibandingkan.');
    // Satu template per jenis dokumen; teks khusus prodi digabung saat dipakai.
    $templateRows = db()->query('SELECT kind, program_code FROM templates WHERE active=1 ORDER BY kind')->fetchAll();
    expect($templateRows === [['kind'=>'diploma','program_code'=>'*'], ['kind'=>'transcript','program_code'=>'*']], 'Harus ada tepat satu template aktif per jenis dokumen.');
    expect(active_template('diploma', 'AKUNTANSI')['settings']['texts']['program'] === 'Program Studi S-1 Akuntansi' && active_template('diploma', 'MANAJEMEN')['settings']['texts']['degree'] === 'SARJANA MANAJEMEN (S.M.)' && !isset(active_template('diploma', 'MANAJEMEN')['settings']['program_texts']), 'Teks khusus prodi tidak digabung ke template.');
    $legacyDb = new PDO('sqlite::memory:');
    $legacyDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $legacyDb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    schema($legacyDb);
    $legacyDb->exec("DELETE FROM templates");
    $legacyInsert = $legacyDb->prepare('INSERT INTO templates(kind,program_code,version,label,settings_json,active,created_at) VALUES(?,?,?,?,?,?,?)');
    $legacyInsert->execute(['diploma', 'AKUNTANSI', 3, 'Lama', json_encode(['texts'=>['opening'=>'Pembuka lama', 'degree'=>'GELAR AKUNTANSI LAMA']]), 1, now_local()]);
    $legacyInsert->execute(['diploma', 'MANAJEMEN', 2, 'Lama', json_encode(['texts'=>['opening'=>'Pembuka terbaru', 'degree'=>'GELAR MANAJEMEN LAMA'], 'fields'=>['name'=>['x'=>40]]]), 1, now_local()]);
    $legacyInsert->execute(['transcript', 'MANAJEMEN', 1, 'Lama', json_encode(['title'=>'TRANSKRIP LAMA', 'signer_name'=>'Dr. Lama', 'accreditation'=>'Baik']), 1, now_local()]);
    seed_templates($legacyDb);
    $merged = $legacyDb->query("SELECT kind, settings_json FROM templates WHERE program_code='*' AND active=1 ORDER BY kind")->fetchAll(PDO::FETCH_KEY_PAIR);
    $mergedDiploma = json_decode($merged['diploma'], true);
    $mergedTranscript = json_decode($merged['transcript'], true);
    expect(count($merged) === 2 && (int) $legacyDb->query("SELECT COUNT(*) FROM templates WHERE active=1")->fetchColumn() === 2, 'Template lama per prodi harus digabung menjadi satu per jenis dokumen.');
    expect($mergedDiploma['texts']['opening'] === 'Pembuka terbaru' && $mergedDiploma['fields']['name']['x'] === 40 && $mergedDiploma['program_texts']['AKUNTANSI']['degree'] === 'GELAR AKUNTANSI LAMA' && $mergedDiploma['program_texts']['MANAJEMEN']['degree'] === 'GELAR MANAJEMEN LAMA' && !isset($mergedDiploma['texts']['degree']), 'Gabungan ijazah harus memakai tata letak terbaru dan gelar masing-masing prodi.');
    expect($mergedTranscript['texts']['title'] === 'TRANSKRIP LAMA' && $mergedTranscript['texts']['signer_name'] === 'Dr. Lama' && $mergedTranscript['program_texts']['MANAJEMEN']['program_accreditation'] === 'Baik' && $mergedTranscript['program_texts']['AKUNTANSI']['program_accreditation'] === '', 'Gabungan transkrip tidak sesuai.');
    $makeImage = static function (string $path, int $width, int $height): string {
        $image = imagecreatetruecolor($width, $height);
        imagepng($image, $path);
        imagedestroy($image);
        return $path;
    };
    expect(template_background_path('diploma') === null, 'Latar blanko tidak boleh ada sebelum diunggah.');
    expect(save_template_background('diploma', $makeImage($root . '/latar.png', 1169, 827)) === null, 'Latar A4 lanskap tidak boleh diberi peringatan rasio.');
    expect(str_ends_with((string) template_background_path('diploma'), 'diploma.png') && template_background_path('transcript') === null, 'Latar blanko tersimpan pada jenis dokumen yang salah.');
    expect(save_template_background('diploma', $makeImage($root . '/latar-lebar.png', 1400, 700)) !== null, 'Latar dengan rasio berbeda harus diberi peringatan.');
    foreach (['potret' => $makeImage($root . '/latar-potret.png', 827, 1169), 'bukan gambar' => $root . '/import-bukan-gambar.txt'] as $label => $path) {
        if (!is_file($path)) file_put_contents($path, 'bukan gambar');
        try {
            save_template_background('diploma', $path);
            throw new RuntimeException('Latar ' . $label . ' seharusnya ditolak.');
        } catch (DomainException) {
        }
    }
    expect(!str_contains(render_diploma_html(sample_template_snapshot(active_template('diploma', 'AKUNTANSI')), null, true), 'sheet-background'), 'Latar blanko tidak boleh masuk ke PDF.');
    expect(save_template_background('transcript', $makeImage($root . '/latar-transkrip.png', 827, 1169)) === null && str_ends_with((string) template_background_path('transcript'), 'transcript.png'), 'Latar transkrip potret harus diterima.');
    try {
        save_template_background('transcript', $root . '/latar.png');
        throw new RuntimeException('Latar transkrip lanskap seharusnya ditolak.');
    } catch (DomainException) {
    }
    delete_template_background('transcript');
    delete_template_background('diploma');
    expect(template_background_path('diploma') === null, 'Latar blanko belum terhapus.');
    $previewTemplate = active_template('diploma', 'AKUNTANSI');
    $previewSource = ['text'=>$previewTemplate['settings']['texts'], 'field'=>$previewTemplate['settings']['fields'], 'qr'=>$previewTemplate['settings']['qr']];
    $previewSource['text']['opening'] = 'Redaksi pratinjau yang belum disimpan';
    $previewSource['field']['opening']['x'] = 37;
    $previewSettings = template_settings_from_source($previewTemplate, $previewSource);
    expect($previewSettings['texts']['opening'] === 'Redaksi pratinjau yang belum disimpan' && $previewSettings['fields']['opening']['x'] === 37.0, 'Pratinjau tidak memakai isian terbaru.');
    expect(active_template('diploma', 'AKUNTANSI')['settings']['texts']['opening'] !== $previewSettings['texts']['opening'], 'Pratinjau mengubah template aktif.');
    expect(str_contains(render_diploma_html(sample_template_snapshot(array_replace($previewTemplate, ['settings'=>$previewSettings])), null, true), 'Redaksi pratinjau yang belum disimpan'), 'PDF pratinjau tidak memakai redaksi terbaru.');
    $qrPreview = render_diploma_html(sample_template_snapshot($previewTemplate), null, true);
    $qrFinalWithoutActivation = render_diploma_html(sample_template_snapshot($previewTemplate), null, false);
    expect(str_contains($qrPreview, 'PANDUAN QR') && !str_contains($qrFinalWithoutActivation, 'PANDUAN QR'), 'Panduan QR harus terlihat hanya pada pratinjau.');
    expect(!str_contains($qrPreview, 'sheet-grid') && !str_contains($qrFinalWithoutActivation, 'sheet-grid'), 'Grid editor tidak boleh masuk ke PDF.');
    $qrTemplate = active_template('diploma');
    $qrSource = ['label'=>'Uji lokasi QR', 'text'=>$qrTemplate['settings']['texts'], 'program_text'=>$qrTemplate['settings']['program_texts'], 'field'=>$qrTemplate['settings']['fields'], 'qr'=>['x'=>24,'y'=>28,'size'=>25]];
    $qrSource['program_text']['MANAJEMEN']['degree'] = 'SARJANA MANAJEMEN (S.M.) UJI';
    save_template_version('diploma', $qrSource);
    $savedQr = active_template('diploma', 'MANAJEMEN');
    expect($savedQr['settings']['texts']['degree'] === 'SARJANA MANAJEMEN (S.M.) UJI' && active_template('diploma', 'AKUNTANSI')['settings']['texts']['degree'] === 'SARJANA AKUNTANSI (S.Ak.)' && (float) active_template('diploma', 'AKUNTANSI')['settings']['qr']['x'] === 24.0, 'Teks khusus prodi atau tata letak bersama tidak tersimpan.');
    expect((int) $savedQr['version'] === 2 && (float) $savedQr['settings']['qr']['x'] === 24.0 && (float) $savedQr['settings']['qr']['y'] === 28.0 && (float) $savedQr['settings']['qr']['size'] === 25.0 && !$savedQr['settings']['qr']['enabled'], 'Lokasi QR tidak tersimpan saat pencetakan belum aktif.');
    $transcriptTemplate = active_template('transcript', 'AKUNTANSI');
    $transcriptSource = ['text'=>$transcriptTemplate['settings']['texts'], 'field'=>$transcriptTemplate['settings']['fields'], 'show_thesis'=>'1'];
    $transcriptSource['text']['title'] = 'JUDUL TRANSKRIP PRATINJAU';
    $transcriptSettings = template_settings_from_source($transcriptTemplate, $transcriptSource);
    expect(str_contains(render_transcript_html(sample_template_snapshot(array_replace($transcriptTemplate, ['settings'=>$transcriptSettings])), true), 'JUDUL TRANSKRIP PRATINJAU'), 'Pratinjau transkrip tidak mengikuti isian.');
    foreach (['diploma','transcript'] as $kind) {
        $template = active_template($kind);
        $settings = $template['settings'];
        if ($kind === 'diploma') {
            $settings['texts']['left_name'] = 'Pejabat Kiri';
            $settings['texts']['right_name'] = 'Pejabat Kanan';
            $settings['program_texts']['AKUNTANSI']['program_accreditation'] = 'Akreditasi prodi contoh untuk pengujian';
            $settings['qr'] = ['enabled'=>true,'x'=>12,'y'=>13,'size'=>22];
        } else {
            $settings['texts']['signer_name'] = 'Pejabat Transkrip';
        }
        db()->prepare('UPDATE templates SET settings_json=? WHERE id=?')->execute([json_encode($settings, JSON_UNESCAPED_UNICODE), $template['id']]);
    }
    $id = save_graduate([
        'nim'=>'0012345678','name'=>'Contoh Lulusan','birth_place'=>'Makassar','birth_date'=>'2002-04-18',
        'program_code'=>'AKUNTANSI','education_program'=>'S1','graduation_date'=>'2026-08-15',
        'diploma_number'=>'CONTOH-NOMOR-001','transcript_number'=>'CONTOH-TR-001','issue_date'=>'2026-09-01',
        'thesis_title'=>'Contoh judul skripsi','official_credits'=>'6','official_gpa'=>'3.50',
    ]);
    save_grade($id, ['position'=>1,'course_code'=>'AKT001','course_name'=>'Pengantar Akuntansi','credits'=>3,'grade_letter'=>'A','grade_weight'=>4,'group_name'=>'Inti']);
    save_grade($id, ['position'=>2,'course_code'=>'AKT002','course_name'=>'Akuntansi Keuangan','credits'=>3,'grade_letter'=>'B','grade_weight'=>3,'group_name'=>'Inti']);
    $summary = grade_summary(grades_for($id));
    expect($summary['credits'] === 6.0 && abs($summary['gpa'] - 3.5) < .0001, 'Perhitungan nilai salah.');
    $diplomaId = issue_document($id, 'diploma');
    $transcriptId = issue_document($id, 'transcript');
    $diploma = find_document($diplomaId);
    $transcript = find_document($transcriptId);
    expect(str_starts_with((string) file_get_contents(document_pdf_file($diploma)), '%PDF'), 'PDF ijazah tidak terbentuk.');
    expect(str_starts_with((string) file_get_contents(document_pdf_file($transcript)), '%PDF'), 'PDF transkrip tidak terbentuk.');
    expect(find_document_by_token($diploma['qr_token'])['id'] == $diplomaId, 'Token verifikasi tidak cocok.');
    $changed = find_graduate($id);
    $changed['name'] = 'Nama Setelah Terbit';
    $lockedActions = [
        'Identitas' => static fn() => save_graduate($changed, $id),
        'Simpan nilai' => static fn() => save_grade($id, ['position'=>3,'course_code'=>'AKT003','course_name'=>'Tambahan','credits'=>3,'grade_letter'=>'A','grade_weight'=>4]),
        'Hapus nilai' => static fn() => delete_grade($id, (int) grades_for($id)[0]['id']),
    ];
    foreach ($lockedActions as $label => $action) {
        try {
            $action();
            throw new RuntimeException($label . ' dapat diubah walau dokumen aktif.');
        } catch (DomainException $e) {
            expect(str_contains($e->getMessage(), 'terkunci'), $label . ' ditolak dengan pesan yang tidak sesuai.');
        }
    }
    expect(find_graduate($id)['name'] === 'Contoh Lulusan' && count(grades_for($id)) === 2, 'Data berubah walau dokumen aktif.');
    revoke_document($diplomaId, 'Uji pembatalan');
    revoke_document($transcriptId, 'Uji pembatalan');
    save_graduate($changed, $id);
    $snapshot = json_decode(find_document($diplomaId)['snapshot_json'], true);
    expect(find_graduate($id)['name'] === 'Nama Setelah Terbit' && $snapshot['graduate']['name'] === 'Contoh Lulusan', 'Snapshot terbit berubah.');
    expect(find_document_by_token($diploma['qr_token'])['status'] === 'revoked', 'Token lama tidak menampilkan status batal.');

    // Halaman verifikasi QR: status, NIM tersamar, dokumen pengganti, dan hitungan pindai.
    $replacementDiploma = find_document(issue_document($id, 'diploma'));
    $verifyHtml = static function (?array $document): string {
        ob_start();
        verify_page($document);
        return (string) ob_get_clean();
    };
    $oldPage = $verifyHtml(find_document($diplomaId));
    expect(str_contains($oldPage, 'telah dibatalkan') && str_contains($oldPage, 'Telah diganti dengan ijazah nomor <strong>CONTOH-NOMOR-001</strong>'), 'Halaman QR lama tidak menunjukkan pembatalan dan pengganti.');
    $newPage = $verifyHtml($replacementDiploma);
    expect(str_contains($newPage, 'Ijazah tercatat sah') && str_contains($newPage, '0012••••78') && !str_contains($newPage, '0012345678') && str_contains($newPage, 'SARJANA AKUNTANSI') && str_contains($newPage, '15 Agustus 2026'), 'Halaman QR aktif tidak lengkap atau NIM tidak disamarkan.');
    expect(str_contains($verifyHtml(null), 'Data tidak ditemukan'), 'Halaman QR untuk kode tidak dikenal salah.');
    record_verification((int) $replacementDiploma['id']);
    record_verification((int) $replacementDiploma['id']);
    expect((int) find_document((int) $replacementDiploma['id'])['verify_count'] === 2, 'Pemindaian QR belum tercatat.');
    revoke_document((int) $replacementDiploma['id'], 'Uji pembersihan');
    $longId = save_graduate([
        'nim'=>'0099999999','name'=>'Lulusan Dengan Banyak Mata Kuliah','birth_place'=>'Makassar','birth_date'=>'2002-01-01',
        'program_code'=>'AKUNTANSI','education_program'=>'S1','graduation_date'=>'2026-08-15',
        'diploma_number'=>'CONTOH-NOMOR-002','transcript_number'=>'CONTOH-TR-002','issue_date'=>'2026-09-01',
        'thesis_title'=>'Contoh judul skripsi panjang untuk menguji pemisahan halaman','official_credits'=>'180','official_gpa'=>'4.00',
    ]);
    for ($i=1; $i<=60; $i++) save_grade($longId, [
        'position'=>$i,'course_code'=>sprintf('MK%03d',$i),
        'course_name'=>'Mata Kuliah Contoh dengan Nama yang Cukup Panjang Nomor ' . $i,
        'credits'=>3,'grade_letter'=>'A','grade_weight'=>4,'group_name'=>'Inti',
    ]);
    $longDoc = find_document(issue_document($longId,'transcript'));
    expect(str_starts_with((string) file_get_contents(document_pdf_file($longDoc)), '%PDF'), 'PDF transkrip panjang tidak terbentuk.');
    expect($longDoc['pdf_path'] === 'documents/' . $longDoc['id'] . '.pdf', 'Lokasi PDF arsip harus relatif terhadap folder storage.');

    // Transkrip satu lembar: tabel dua kolom, huruf menyesuaikan, template lama tetap terbaca.
    $pageCount = static fn(string $pdf) => preg_match_all('#/Type\s*/Page[^s]#', $pdf);
    expect($pageCount((string) file_get_contents(document_pdf_file($longDoc))) === 1, 'Transkrip 60 mata kuliah harus tetap satu lembar.');
    $longSnapshot = json_decode($longDoc['snapshot_json'], true);
    expect(transcript_block_html($longSnapshot)['grades_pt'] < 9, 'Huruf tabel nilai harus diperkecil saat mata kuliah sangat banyak.');
    $fifty = array_map(static fn($i) => ['course_code'=>sprintf('K%02d', $i), 'course_name'=>'Mata Kuliah ' . $i, 'credits'=>3, 'grade_letter'=>'A', 'grade_weight'=>4], range(1, 50));
    $gradesField = default_transcript_settings('AKUNTANSI')['fields']['grades'];
    [$gradesHtml, $gradesPt, $gradesRow] = transcript_grades_html($fifty, $gradesField);
    expect(substr_count($gradesHtml, '<table class="tr-grades">') === 2 && preg_match_all('#<td class="g-code" style="height:4.6mm">K#', $gradesHtml) === 50 && strpos($gradesHtml, 'K25') < strpos($gradesHtml, 'K26') && $gradesPt === 9.0 && $gradesRow === 4.6, 'Tabel nilai harus dua kolom 25 dan 25 dengan tinggi baris dan huruf sesuai template.');
    [, $tallPt, $tallRow] = transcript_grades_html($fifty, array_replace($gradesField, ['row'=>8]));
    expect($tallPt === 9.0 && $tallRow < 8 && $tallRow > 4.6, 'Tinggi baris harus diturunkan lebih dahulu sebelum ukuran huruf.');
    [, $shortPt, $shortRow] = transcript_grades_html($fifty, array_replace($gradesField, ['row'=>3.5]));
    expect($shortPt === 9.0 && $shortRow === 3.5, 'Tinggi baris yang lebih kecil harus dipakai apa adanya.');
    $leftTable = substr($gradesHtml, 0, strpos($gradesHtml, '<td class="g-gap">'));
    expect(str_contains($leftTable, '>K25<') && !str_contains($leftTable, '>K26<'), 'Kolom kiri harus berisi 25 mata kuliah pertama.');
    expect(!str_contains($gradesHtml, 'Mutu') && preg_match('#>Kode</th><th class="g-name"[^>]*>Mata Kuliah</th><th class="g-sks"[^>]*>SKS</th><th class="g-grade"[^>]*>Nilai</th></tr></thead>#', $gradesHtml) === 1, 'Tabel nilai hanya berisi kode, mata kuliah, SKS, dan nilai.');
    $legacy = normalized_template_settings('transcript', 'AKUNTANSI', ['title'=>'TRANSKRIP AKADEMIK','signer_role'=>'Ketua','signer_name'=>'Dr. Lama','signer_nidn'=>'NIDN 1','issue_place'=>'Gowa','show_thesis'=>false,'institution'=>'X']);
    expect($legacy['texts']['title'] === 'TRANSKRIP AKADEMIK' && $legacy['texts']['signer_role'] === 'Ketua,' && $legacy['texts']['signer_name'] === 'Dr. Lama' && $legacy['show_thesis'] === false && isset($legacy['fields']['grades']['row']) && !isset($legacy['institution']), 'Template transkrip lama tidak termigrasi ke tata letak baru.');
    $twoSigners = normalized_template_settings('transcript', 'AKUNTANSI', ['texts'=>['title'=>'T','left_role'=>'Ketua,','left_name'=>'Dr. Kiri','left_nidn'=>'N1','right_role'=>'Wakil,','right_name'=>'Dr. Kanan','right_nidn'=>'N2','issue_place'=>'Gowa'], 'fields'=>['left_name'=>['label'=>'x','x'=>1,'y'=>1,'w'=>50,'pt'=>9,'align'=>'left'], 'gpa'=>['label'=>'y','x'=>1,'y'=>1,'w'=>50,'pt'=>9,'align'=>'left']], 'show_thesis'=>true]);
    expect($twoSigners['texts']['signer_name'] === 'Dr. Kiri' && $twoSigners['texts']['signer_nidn'] === 'N1' && $twoSigners['texts']['issue_place'] === 'Gowa' && !isset($twoSigners['texts']['right_name']) && !isset($twoSigners['fields']['left_name']) && !isset($twoSigners['fields']['gpa']) && isset($twoSigners['fields']['signer_name'], $twoSigners['fields']['summary']), 'Template transkrip dua pejabat tidak termigrasi ke satu penandatangan.');
    expect(!cambria_installed() && transcript_font_family() === '"Times New Roman",serif', 'Tanpa berkas Cambria, transkrip harus memakai Times.');

    // Paket perbaikan cepat: hapus lulusan, paginasi, SKS bulat, tempat terbit, pratinjau impor.
    try {
        delete_graduate($longId);
        throw new RuntimeException('Lulusan dengan dokumen terbit tidak boleh dihapus.');
    } catch (DomainException) {
    }
    $mistakeId = save_graduate(['nim'=>'0099990000','name'=>'Salah Input','program_code'=>'AKUNTANSI']);
    save_grade($mistakeId, ['position'=>1,'course_code'=>'X1','course_name'=>'Uji','credits'=>2,'grade_letter'=>'A','grade_weight'=>4]);
    delete_graduate($mistakeId);
    expect(find_graduate($mistakeId) === null && grades_for($mistakeId) === [], 'Lulusan salah input beserta nilainya belum terhapus.');
    for ($i = 1; $i <= LIST_PAGE_SIZE; $i++) save_graduate(['nim'=>sprintf('PAGE%04d', $i),'name'=>'Paginasi ' . $i,'program_code'=>'MANAJEMEN']);
    [$firstPage, $total] = list_graduates('', 1);
    [$secondPage] = list_graduates('', 2);
    expect(count($firstPage) === LIST_PAGE_SIZE && $total > LIST_PAGE_SIZE && count($secondPage) === $total - LIST_PAGE_SIZE, 'Paginasi daftar lulusan salah.');
    [$searchPage, $searchTotal] = list_graduates('Paginasi 1', 1);
    expect($searchTotal === 11 && count($searchPage) === 11, 'Pencarian dengan paginasi salah.');
    try {
        curriculum_rows_from_form(['course_code'=>['MK1'], 'course_name'=>['Satu'], 'credits'=>['2.5']]);
        throw new RuntimeException('SKS pecahan seharusnya ditolak.');
    } catch (DomainException) {
    }
    $placeTemplate = active_template('diploma', 'AKUNTANSI');
    $placeTemplate['settings']['texts']['issue_place'] = 'Sungguminasa';
    expect(str_contains(render_diploma_html(sample_template_snapshot($placeTemplate), null, true), 'Diberikan di Sungguminasa, 1 September 2026'), 'Tempat penerbitan ijazah belum mengikuti template.');
    expect(str_contains(render_diploma_html(sample_template_snapshot(active_template('diploma', 'MANAJEMEN')), null, true), 'Diberikan di Makassar,'), 'Tempat penerbitan bawaan harus Makassar.');
    save_import_preview(make_import_preview_from_rows([array_replace($newGraduate, ['_line'=>2])], 'graduates'));
    save_import_preview(make_import_preview_from_rows([array_replace($newGraduate, ['_line'=>2])], 'graduates'));
    expect(count(glob($root . '/imports/*.json') ?: []) === 1, 'Pratinjau impor lama tidak dibersihkan.');
    if (getenv('STIE_KEEP_QA') === '1') {
        $diplomaTarget = dirname(__DIR__) . '/storage/qa-diploma-with-qr.pdf';
        copy(document_pdf_file($diploma), $diplomaTarget);
        $target = dirname(__DIR__) . '/storage/qa-long-transcript.pdf';
        copy(document_pdf_file($longDoc), $target);
        echo "PDF QA: {$diplomaTarget}\n";
        echo "PDF QA: {$target}\n";
    }
    echo "SMOKE OK: XLS impor lulusan, kurikulum, nilai per mahasiswa, 2 PDF, QR, snapshot, pembatalan, transkrip panjang.\n";
} finally {
    foreach (glob($root . '/documents/*') ?: [] as $file) unlink($file);
    if (is_dir($root . '/documents')) rmdir($root . '/documents');
    foreach (glob($root . '/imports/*') ?: [] as $file) unlink($file);
    if (is_dir($root . '/imports')) rmdir($root . '/imports');
    foreach (glob($root . '/backgrounds/*') ?: [] as $file) unlink($file);
    if (is_dir($root . '/backgrounds')) rmdir($root . '/backgrounds');
    foreach (glob($root . '/*') ?: [] as $file) if (is_file($file)) unlink($file);
    rmdir($root);
}
