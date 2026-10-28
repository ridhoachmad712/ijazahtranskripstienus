<?php
declare(strict_types=1);

function programs(): array
{
    return [
        'AKUNTANSI' => ['label' => 'S-1 Akuntansi', 'degree' => 'SARJANA AKUNTANSI (S.Ak.)', 'suffix'=>'S.Ak.'],
        'MANAJEMEN' => ['label' => 'S-1 Manajemen', 'degree' => 'SARJANA MANAJEMEN (S.M.)', 'suffix'=>'S.M.'],
    ];
}

function default_diploma_settings(string $program): array
{
    $programs = programs();
    $spec = $programs[$program];
    return [
        'texts' => [
            'institution_accreditation' => '',
            'opening' => 'Dengan ini menyatakan bahwa :',
            'study' => 'Telah menyelesaikan studi dengan baik dan memenuhi segala syarat Pendidikan Sarjana (S-1) pada :',
            'program' => 'Program Studi ' . $spec['label'],
            'program_accreditation' => '',
            'confer' => 'oleh sebab itu kepadanya diberikan Ijazah dan Gelar :',
            'degree' => $spec['degree'],
            'rights' => 'beserta segala hak dan kewajiban yang melekat pada gelar tersebut.',
            'left_role' => 'Ketua STIE Nusantara Makassar,',
            'right_role' => 'Wakil Ketua Bidang Akademik,',
            'left_name' => '',
            'right_name' => '',
            'left_nidn' => '',
            'right_nidn' => '',
            'issue_place' => 'Makassar',
        ],
        'fields' => [
            'number' => ['label'=>'Nomor ijazah nasional','x'=>145,'y'=>12,'w'=>140,'pt'=>13,'align'=>'right'],
            'institution_accreditation' => ['label'=>'Akreditasi institusi','x'=>45,'y'=>53,'w'=>207,'pt'=>10,'align'=>'center'],
            'opening' => ['label'=>'Kalimat pembuka','x'=>36,'y'=>64,'w'=>225,'pt'=>14,'align'=>'center'],
            'name' => ['label'=>'Nama lulusan','x'=>32,'y'=>73,'w'=>233,'pt'=>32,'align'=>'center'],
            'identity' => ['label'=>'Lahir dan NIM','x'=>34,'y'=>88,'w'=>229,'pt'=>14,'align'=>'center'],
            'study' => ['label'=>'Kalimat studi','x'=>20,'y'=>100,'w'=>257,'pt'=>13,'align'=>'center'],
            'program' => ['label'=>'Program studi','x'=>40,'y'=>110,'w'=>217,'pt'=>24,'align'=>'center'],
            'program_accreditation' => ['label'=>'Akreditasi prodi','x'=>45,'y'=>120,'w'=>207,'pt'=>10,'align'=>'center'],
            'confer' => ['label'=>'Kalimat pemberian gelar','x'=>35,'y'=>130,'w'=>227,'pt'=>14,'align'=>'center'],
            'degree' => ['label'=>'Gelar','x'=>37,'y'=>139,'w'=>223,'pt'=>23,'align'=>'center'],
            'rights' => ['label'=>'Hak dan kewajiban','x'=>38,'y'=>149,'w'=>221,'pt'=>13,'align'=>'center'],
            'issue' => ['label'=>'Tanggal penerbitan','x'=>58,'y'=>162,'w'=>181,'pt'=>14,'align'=>'center'],
            'left_role' => ['label'=>'Jabatan kiri','x'=>30,'y'=>171,'w'=>105,'pt'=>12,'align'=>'center'],
            'right_role' => ['label'=>'Jabatan kanan','x'=>162,'y'=>171,'w'=>105,'pt'=>12,'align'=>'center'],
            'left_name' => ['label'=>'Nama pejabat kiri','x'=>30,'y'=>190,'w'=>105,'pt'=>13,'align'=>'center'],
            'right_name' => ['label'=>'Nama pejabat kanan','x'=>162,'y'=>190,'w'=>105,'pt'=>13,'align'=>'center'],
            'left_nidn' => ['label'=>'NIDN kiri','x'=>30,'y'=>198,'w'=>105,'pt'=>10,'align'=>'center'],
            'right_nidn' => ['label'=>'NIDN kanan','x'=>162,'y'=>198,'w'=>105,'pt'=>10,'align'=>'center'],
        ],
        'qr' => ['enabled'=>false,'x'=>12,'y'=>13,'size'=>22],
    ];
}

// Transkrip satu lembar A4 potret; posisi setiap bagian diatur seperti ijazah (mm dari pojok kiri atas).
function default_transcript_settings(string $program): array
{
    return [
        'texts' => [
            'title' => 'TRANSKRIP NILAI',
            'program_accreditation' => '',
            'issue_place' => 'Makassar',
            'signer_role' => 'Ketua STIE Nusantara Makassar,',
            'signer_name' => '',
            'signer_nidn' => '',
        ],
        'fields' => [
            'title' => ['label'=>'Judul','x'=>20,'y'=>36,'w'=>170,'pt'=>16,'align'=>'center'],
            'number' => ['label'=>'Nomor transkrip','x'=>20,'y'=>44,'w'=>170,'pt'=>9.5,'align'=>'center'],
            'identity' => ['label'=>'Identitas lulusan','x'=>12,'y'=>51,'w'=>186,'pt'=>9,'align'=>'left'],
            'program_accreditation' => ['label'=>'Akreditasi prodi','x'=>12,'y'=>72,'w'=>186,'pt'=>8.5,'align'=>'left'],
            'grades' => ['label'=>'Tabel nilai','x'=>12,'y'=>78,'w'=>186,'pt'=>9,'align'=>'left','h'=>145,'row'=>4.6],
            'summary' => ['label'=>'Jumlah SKS dan IPK','x'=>12,'y'=>226,'w'=>186,'pt'=>9.5,'align'=>'left'],
            'thesis' => ['label'=>'Judul skripsi','x'=>12,'y'=>238,'w'=>186,'pt'=>9,'align'=>'left'],
            'issue' => ['label'=>'Tempat dan tanggal','x'=>118,'y'=>254,'w'=>80,'pt'=>10,'align'=>'center'],
            'signer_role' => ['label'=>'Jabatan penandatangan','x'=>118,'y'=>259,'w'=>80,'pt'=>10,'align'=>'center'],
            'signer_name' => ['label'=>'Nama penandatangan','x'=>118,'y'=>280,'w'=>80,'pt'=>10.5,'align'=>'center'],
            'signer_nidn' => ['label'=>'NIDN penandatangan','x'=>118,'y'=>285,'w'=>80,'pt'=>9,'align'=>'center'],
        ],
        'show_thesis' => true,
    ];
}
function page_size(string $kind): array
{
    return $kind === 'diploma' ? ['w'=>297, 'h'=>210, 'label'=>'A4 lanskap'] : ['w'=>210, 'h'=>297, 'label'=>'A4 potret'];
}

// Template transkrip sebelum tata letak berposisi: satu penandatangan dan header teks.
function legacy_transcript_settings(array $saved): array
{
    $texts = [];
    foreach (['title'=>'title', 'accreditation'=>'program_accreditation', 'issue_place'=>'issue_place', 'signer_role'=>'signer_role', 'signer_name'=>'signer_name', 'signer_nidn'=>'signer_nidn'] as $old => $new) {
        if (trim((string) ($saved[$old] ?? '')) !== '') $texts[$new] = trim((string) $saved[$old]);
    }
    if (isset($texts['signer_role']) && !str_ends_with($texts['signer_role'], ',')) $texts['signer_role'] .= ',';
    return ['texts'=>$texts, 'show_thesis'=>(bool) ($saved['show_thesis'] ?? true)];
}

// Tata letak sementara dengan dua pejabat: pejabat kiri menjadi satu-satunya penandatangan, posisi kembali ke bawaan.
function two_signer_transcript_settings(array $saved): array
{
    $texts = array_diff_key($saved['texts'], array_flip(['left_role','right_role','left_name','right_name','left_nidn','right_nidn']));
    foreach (['role','name','nidn'] as $part) {
        if (trim((string) ($saved['texts']['left_' . $part] ?? '')) !== '') $texts['signer_' . $part] = trim((string) $saved['texts']['left_' . $part]);
    }
    return ['texts'=>$texts, 'show_thesis'=>(bool) ($saved['show_thesis'] ?? true)];
}function seed_templates(PDO $db): void
{
    $count = (int) $db->query('SELECT COUNT(*) FROM templates')->fetchColumn();
    if ($count > 0) {
        return;
    }
    $stmt = $db->prepare('INSERT INTO templates(kind,program_code,version,label,settings_json,active,created_at) VALUES(?,?,?,?,?,1,?)');
    foreach (array_keys(programs()) as $program) {
        foreach (['diploma', 'transcript'] as $kind) {
            $settings = $kind === 'diploma' ? default_diploma_settings($program) : default_transcript_settings($program);
            $stmt->execute([$kind, $program, 1, 'Versi awal', json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), now_local()]);
        }
    }
}

function find_graduate(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM graduates WHERE id=?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function find_graduate_by_nim(string $nim): ?array
{
    $stmt = db()->prepare('SELECT * FROM graduates WHERE nim=?');
    $stmt->execute([$nim]);
    return $stmt->fetch() ?: null;
}

const LIST_PAGE_SIZE = 50;

// Mengembalikan [baris halaman ini, jumlah seluruh hasil].
function list_graduates(string $search = '', int $page = 1): array
{
    $like = '%' . $search . '%';
    $count = db()->prepare('SELECT COUNT(*) FROM graduates g WHERE ?=\'\' OR g.nim LIKE ? OR g.name LIKE ?');
    $count->execute([$search, $like, $like]);
    $stmt = db()->prepare('SELECT g.*, (SELECT COUNT(*) FROM grades WHERE graduate_id=g.id) AS grade_count, (SELECT status FROM documents WHERE graduate_id=g.id AND kind=\'diploma\' ORDER BY id DESC LIMIT 1) AS diploma_status, (SELECT status FROM documents WHERE graduate_id=g.id AND kind=\'transcript\' ORDER BY id DESC LIMIT 1) AS transcript_status FROM graduates g WHERE ?=\'\' OR g.nim LIKE ? OR g.name LIKE ? ORDER BY g.id DESC LIMIT ? OFFSET ?');
    foreach ([$search, $like, $like] as $index => $value) $stmt->bindValue($index + 1, $value);
    $stmt->bindValue(4, LIST_PAGE_SIZE, PDO::PARAM_INT);
    $stmt->bindValue(5, (max(1, $page) - 1) * LIST_PAGE_SIZE, PDO::PARAM_INT);
    $stmt->execute();
    return [$stmt->fetchAll(), (int) $count->fetchColumn()];
}

function graduate_input(array $source): array
{
    $keys = ['nim','name','birth_place','birth_date','program_code','education_program','graduation_date','diploma_number','transcript_number','issue_date','thesis_title','official_credits','official_gpa'];
    $out = [];
    foreach ($keys as $key) {
        $out[$key] = trim((string) ($source[$key] ?? ''));
    }
    return $out;
}

function graduate_errors(array $data, ?int $ignoreId = null): array
{
    $errors = [];
    if ($data['nim'] === '' || mb_strlen($data['nim']) > 30) $errors[] = 'NIM wajib diisi (maksimal 30 karakter).';
    if ($data['name'] === '' || mb_strlen($data['name']) > 180) $errors[] = 'Nama wajib diisi (maksimal 180 karakter).';
    if (!array_key_exists($data['program_code'], programs())) $errors[] = 'Program studi tidak dikenal.';
    foreach (['birth_date','graduation_date','issue_date'] as $key) {
        if (!valid_iso_date($data[$key])) $errors[] = 'Tanggal ' . $key . ' harus YYYY-MM-DD.';
    }
    foreach (['diploma_number','transcript_number'] as $key) {
        if (mb_strlen($data[$key]) > 100) $errors[] = $key . ' terlalu panjang.';
    }
    foreach (['official_credits','official_gpa'] as $key) {
        if ($data[$key] !== '' && (!is_numeric($data[$key]) || (float) $data[$key] < 0 || (float) $data[$key] > ($key === 'official_gpa' ? 4 : 500))) $errors[] = $key . ' tidak valid.';
    }
    $stmt = db()->prepare('SELECT id FROM graduates WHERE nim=? AND id<>?');
    $stmt->execute([$data['nim'], $ignoreId ?? 0]);
    if ($stmt->fetch()) $errors[] = 'NIM sudah dipakai lulusan lain.';
    return $errors;
}

function save_graduate(array $data, ?int $id = null): int
{
    $data = graduate_input($data);
    $errors = graduate_errors($data, $id);
    if ($errors) throw new DomainException(implode(' ', $errors));
    if ($id !== null && graduate_has_issued_document($id)) throw new DomainException('Identitas lulusan terkunci karena ada dokumen aktif. Batalkan dokumen tersebut lebih dahulu.');
    foreach (['official_credits','official_gpa'] as $key) {
        if ($data[$key] === '') $data[$key] = null;
    }
    $db = db();
    if ($id !== null) {
        $stmt = $db->prepare('UPDATE graduates SET nim=?,name=?,birth_place=?,birth_date=?,program_code=?,education_program=?,graduation_date=?,diploma_number=?,transcript_number=?,issue_date=?,thesis_title=?,official_credits=?,official_gpa=?,updated_at=? WHERE id=?');
        $stmt->execute([...array_values($data), now_local(), $id]);
        audit('graduate', $id, 'updated');
        return $id;
    }
    $stmt = $db->prepare('INSERT INTO graduates(nim,name,birth_place,birth_date,program_code,education_program,graduation_date,diploma_number,transcript_number,issue_date,thesis_title,official_credits,official_gpa,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([...array_values($data), now_local(), now_local()]);
    $newId = (int) $db->lastInsertId();
    audit('graduate', $newId, 'created');
    return $newId;
}

function delete_graduate(int $id): void
{
    $graduate = find_graduate($id);
    if (!$graduate) throw new DomainException('Lulusan tidak ditemukan.');
    $stmt = db()->prepare('SELECT COUNT(*) FROM documents WHERE graduate_id=?');
    $stmt->execute([$id]);
    if ((int) $stmt->fetchColumn() > 0) throw new DomainException('Lulusan yang pernah memiliki dokumen terbit tidak dapat dihapus agar riwayat penerbitan tetap utuh.');
    $db = db();
    $db->beginTransaction();
    try {
        $db->prepare('DELETE FROM grades WHERE graduate_id=?')->execute([$id]);
        $db->prepare('DELETE FROM graduates WHERE id=?')->execute([$id]);
        audit('graduate', $id, 'deleted', $graduate['nim'] . ' ' . $graduate['name']);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
    unset($_SESSION['grade_draft'][$id]);
}

function grades_for(int $graduateId): array
{
    $stmt = db()->prepare('SELECT * FROM grades WHERE graduate_id=? ORDER BY position,id');
    $stmt->execute([$graduateId]);
    return $stmt->fetchAll();
}

function grade_summary(array $grades): array
{
    $credits = 0.0;
    $quality = 0.0;
    foreach ($grades as $grade) {
        $credits += (float) $grade['credits'];
        $quality += (float) $grade['credits'] * (float) $grade['grade_weight'];
    }
    return ['credits'=>$credits, 'quality'=>$quality, 'gpa'=>$credits > 0 ? $quality / $credits : null];
}

// null berarti total resmi belum diisi sehingga belum dapat dibandingkan.
function official_totals_match(array $summary, array $graduate): ?bool
{
    if (!has_official_totals($graduate)) return null;
    return abs($summary['credits'] - (float) $graduate['official_credits']) <= 0.001
        && $summary['gpa'] !== null && abs($summary['gpa'] - (float) $graduate['official_gpa']) <= 0.011;
}

function seed_grade_scale(PDO $db): void
{
    if ((int) $db->query('SELECT COUNT(*) FROM grade_scale')->fetchColumn() > 0) return;
    $stmt = $db->prepare('INSERT INTO grade_scale(letter,weight,position) VALUES(?,?,?)');
    foreach ([['A',4],['B',3],['C',2],['D',1],['E',0]] as $index => [$letter, $weight]) $stmt->execute([$letter, $weight, $index + 1]);
}

function grade_scale(): array
{
    $scale = [];
    foreach (db()->query('SELECT letter, weight FROM grade_scale ORDER BY position') as $row) $scale[$row['letter']] = (float) $row['weight'];
    return $scale;
}

function save_grade_scale(array $letters, array $weights): void
{
    $rows = [];
    $errors = [];
    foreach (array_values($letters) as $index => $letter) {
        $letter = strtoupper(trim((string) $letter));
        $weight = trim((string) (array_values($weights)[$index] ?? ''));
        if ($letter === '' && $weight === '') continue;
        $number = filter_var($weight, FILTER_VALIDATE_FLOAT);
        if ($letter === '' || mb_strlen($letter) > 5) $errors[] = 'Baris ' . ($index + 1) . ': nilai huruf wajib diisi (maksimal 5 karakter).';
        elseif (isset($rows[$letter])) $errors[] = 'Nilai huruf ' . $letter . ' ganda.';
        if ($number === false || $number < 0 || $number > 4) $errors[] = 'Baris ' . ($index + 1) . ': bobot harus angka 0 sampai 4.';
        $rows[$letter] = (float) $number;
    }
    if (!$rows && !$errors) $errors[] = 'Skala nilai minimal berisi satu nilai huruf.';
    if ($errors) throw new DomainException(implode(' ', $errors));
    $db = db();
    $db->beginTransaction();
    try {
        $db->exec('DELETE FROM grade_scale');
        $stmt = $db->prepare('INSERT INTO grade_scale(letter,weight,position) VALUES(?,?,?)');
        $position = 0;
        foreach ($rows as $letter => $weight) $stmt->execute([(string) $letter, $weight, ++$position]);
        audit('grade_scale', null, 'saved', implode(', ', array_map(static fn($l, $w) => $l . '=' . $w, array_keys($rows), $rows)));
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function curriculum_for(string $program): array
{
    $stmt = db()->prepare('SELECT * FROM curriculum_courses WHERE program_code=? ORDER BY position, id');
    $stmt->execute([$program]);
    return $stmt->fetchAll();
}

// Baris kurikulum dari formulir atau berkas; urutan baris menjadi urutan mata kuliah di transkrip.
function validated_curriculum_rows(array $rows): array
{
    $clean = [];
    $errors = [];
    $seen = [];
    foreach ($rows as $index => $row) {
        $code = trim((string) ($row['course_code'] ?? ''));
        $name = trim((string) ($row['course_name'] ?? ''));
        $creditsText = trim((string) ($row['credits'] ?? ''));
        if ($code === '' && $name === '' && $creditsText === '') continue;
        $line = $row['_line'] ?? ($index + 1);
        $credits = filter_var($creditsText, FILTER_VALIDATE_FLOAT);
        if ($code === '' || mb_strlen($code) > 30) $errors[] = 'Baris ' . $line . ': kode mata kuliah wajib diisi (maksimal 30 karakter).';
        elseif (isset($seen[$code])) $errors[] = 'Baris ' . $line . ': kode ' . $code . ' ganda.';
        if ($name === '' || mb_strlen($name) > 200) $errors[] = 'Baris ' . $line . ': nama mata kuliah wajib diisi (maksimal 200 karakter).';
        if ($credits === false || $credits <= 0 || $credits > 20 || floor($credits) != $credits) $errors[] = 'Baris ' . $line . ': SKS harus bilangan bulat 1 sampai 20.';
        $seen[$code] = true;
        $clean[] = ['position'=>count($clean) + 1, 'course_code'=>$code, 'course_name'=>$name, 'credits'=>(float) $credits];
    }
    if ($errors) throw new DomainException(implode(' ', array_slice($errors, 0, 20)) . (count($errors) > 20 ? ' (dan ' . (count($errors) - 20) . ' masalah lain)' : ''));
    return $clean;
}

function raw_curriculum_rows(array $source): array
{
    $codes = array_values((array) ($source['course_code'] ?? []));
    $names = array_values((array) ($source['course_name'] ?? []));
    $credits = array_values((array) ($source['credits'] ?? []));
    $rows = [];
    foreach ($codes as $index => $code) $rows[] = ['course_code'=>trim((string) $code), 'course_name'=>trim((string) ($names[$index] ?? '')), 'credits'=>trim((string) ($credits[$index] ?? ''))];
    return $rows;
}

function curriculum_rows_from_form(array $source): array
{
    return validated_curriculum_rows(raw_curriculum_rows($source));
}

function save_curriculum(string $program, array $rows): void
{
    if (!array_key_exists($program, programs())) throw new DomainException('Program studi tidak dikenal.');
    $db = db();
    $db->beginTransaction();
    try {
        $db->prepare('DELETE FROM curriculum_courses WHERE program_code=?')->execute([$program]);
        $stmt = $db->prepare('INSERT INTO curriculum_courses(program_code,position,course_code,course_name,credits) VALUES(?,?,?,?,?)');
        foreach ($rows as $row) $stmt->execute([$program, $row['position'], $row['course_code'], $row['course_name'], $row['credits']]);
        audit('curriculum', null, 'saved', $program . ': ' . count($rows) . ' mata kuliah');
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

// Menyimpan nilai huruf satu mahasiswa berdasarkan kurikulum prodinya. Nilai di luar kurikulum dipertahankan di akhir daftar.
function save_grade_sheet(int $graduateId, array $letters): int
{
    $graduate = find_graduate($graduateId);
    if (!$graduate) throw new DomainException('Lulusan tidak ditemukan.');
    assert_grades_editable($graduateId);
    $curriculum = curriculum_for($graduate['program_code']);
    if (!$curriculum) throw new DomainException('Kurikulum prodi ini belum diatur. Isi daftar mata kuliah di menu Kurikulum lebih dahulu.');
    $scale = grade_scale();
    $rows = [];
    $errors = [];
    foreach ($curriculum as $course) {
        $letter = strtoupper(trim((string) ($letters[$course['course_code']] ?? '')));
        if ($letter === '') continue;
        if (!array_key_exists($letter, $scale)) {
            $errors[] = $course['course_code'] . ': nilai huruf "' . $letter . '" tidak ada di skala nilai.';
            continue;
        }
        $rows[] = [$course['position'], $course['course_code'], $course['course_name'], $course['credits'], $letter, $scale[$letter]];
    }
    if ($errors) throw new DomainException(implode(' ', $errors));
    $codes = array_column($curriculum, 'course_code');
    $outside = array_values(array_filter(grades_for($graduateId), static fn($grade) => !in_array($grade['course_code'], $codes, true)));
    $db = db();
    $db->beginTransaction();
    try {
        $db->prepare('DELETE FROM grades WHERE graduate_id=?')->execute([$graduateId]);
        $stmt = $db->prepare('INSERT INTO grades(graduate_id,position,course_code,course_name,group_name,credits,grade_letter,grade_weight) VALUES(?,?,?,?,?,?,?,?)');
        foreach ($rows as [$position, $code, $name, $credits, $letter, $weight]) $stmt->execute([$graduateId, $position, $code, $name, '', $credits, $letter, $weight]);
        foreach ($outside as $index => $grade) $stmt->execute([$graduateId, 10000 + $index, $grade['course_code'], $grade['course_name'], $grade['group_name'], $grade['credits'], $grade['grade_letter'], $grade['grade_weight']]);
        audit('graduate', $graduateId, 'grade_sheet_saved', count($rows) . ' nilai');
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
    return count($rows);
}

function list_grade_overview(string $search = ''): array
{
    $stmt = db()->prepare('SELECT g.*, COUNT(gr.id) AS grade_count, COALESCE(SUM(gr.credits),0) AS credits, COALESCE(SUM(gr.credits*gr.grade_weight),0) AS quality,
        (SELECT COUNT(*) FROM curriculum_courses c WHERE c.program_code=g.program_code) AS curriculum_count,
        EXISTS(SELECT 1 FROM documents d WHERE d.graduate_id=g.id AND d.kind=\'transcript\' AND d.status=\'issued\') AS transcript_issued
        FROM graduates g LEFT JOIN grades gr ON gr.graduate_id=g.id
        WHERE ?=\'\' OR g.nim LIKE ? OR g.name LIKE ? GROUP BY g.id ORDER BY g.name');
    $like = '%' . $search . '%';
    $stmt->execute([$search, $like, $like]);
    return $stmt->fetchAll();
}

function has_official_totals(array $graduate): bool
{
    foreach (['official_credits','official_gpa'] as $key) {
        if (($graduate[$key] ?? null) === null || $graduate[$key] === '') return false;
    }
    return true;
}

function graduate_has_issued_document(int $graduateId, ?string $kind = null): bool
{
    $sql = 'SELECT 1 FROM documents WHERE graduate_id=? AND status=\'issued\'' . ($kind !== null ? ' AND kind=?' : '') . ' LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->execute($kind !== null ? [$graduateId, $kind] : [$graduateId]);
    return (bool) $stmt->fetchColumn();
}

function assert_grades_editable(int $graduateId): void
{
    if (graduate_has_issued_document($graduateId, 'transcript')) throw new DomainException('Nilai terkunci karena transkrip aktif sudah terbit. Batalkan transkrip lebih dahulu.');
}

function save_grade(int $graduateId, array $source): void
{
    if (!find_graduate($graduateId)) throw new DomainException('Lulusan tidak ditemukan.');
    assert_grades_editable($graduateId);
    $code = trim((string) ($source['course_code'] ?? ''));
    $name = trim((string) ($source['course_name'] ?? ''));
    $letter = trim((string) ($source['grade_letter'] ?? ''));
    $credits = filter_var($source['credits'] ?? null, FILTER_VALIDATE_FLOAT);
    $weight = filter_var($source['grade_weight'] ?? null, FILTER_VALIDATE_FLOAT);
    $position = filter_var($source['position'] ?? null, FILTER_VALIDATE_INT);
    if ($code === '' || $name === '' || $letter === '' || $credits === false || $credits <= 0 || $credits > 20 || $weight === false || $weight < 0 || $weight > 4 || $position === false || $position < 1) {
        throw new DomainException('Kode, nama, urutan, SKS, nilai, atau bobot tidak valid.');
    }
    $stmt = db()->prepare('INSERT INTO grades(graduate_id,position,course_code,course_name,group_name,credits,grade_letter,grade_weight) VALUES(?,?,?,?,?,?,?,?) ON CONFLICT(graduate_id,course_code) DO UPDATE SET position=excluded.position,course_name=excluded.course_name,group_name=excluded.group_name,credits=excluded.credits,grade_letter=excluded.grade_letter,grade_weight=excluded.grade_weight');
    $stmt->execute([$graduateId, $position, $code, $name, trim((string) ($source['group_name'] ?? '')), $credits, $letter, $weight]);
    audit('graduate', $graduateId, 'grade_saved', $code);
}

function delete_grade(int $graduateId, int $gradeId): void
{
    assert_grades_editable($graduateId);
    $stmt = db()->prepare('DELETE FROM grades WHERE id=? AND graduate_id=?');
    $stmt->execute([$gradeId, $graduateId]);
    if ($stmt->rowCount()) audit('graduate', $graduateId, 'grade_deleted', (string) $gradeId);
}

function active_template(string $kind, string $program): ?array
{
    $stmt = db()->prepare('SELECT * FROM templates WHERE kind=? AND program_code=? AND active=1 ORDER BY version DESC LIMIT 1');
    $stmt->execute([$kind, $program]);
    $row = $stmt->fetch();
    if (!$row) return null;
    $row['settings'] = normalized_template_settings($kind, $program, json_decode($row['settings_json'], true, 512, JSON_THROW_ON_ERROR));
    return $row;
}

function template_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM templates WHERE id=?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) return null;
    $row['settings'] = normalized_template_settings($row['kind'], $row['program_code'], json_decode($row['settings_json'], true, 512, JSON_THROW_ON_ERROR));
    return $row;
}

function normalized_template_settings(string $kind, string $program, array $saved): array
{
    $defaults = $kind === 'diploma' ? default_diploma_settings($program) : default_transcript_settings($program);
    if ($kind === 'transcript' && !isset($saved['fields'])) $saved = legacy_transcript_settings($saved);
    elseif ($kind === 'transcript' && isset($saved['texts']['left_role'])) $saved = two_signer_transcript_settings($saved);
    $settings = array_replace_recursive($defaults, $saved);
    // Bidang yang tidak lagi dipakai tata letak saat ini dibuang.
    $settings['texts'] = array_intersect_key($settings['texts'], $defaults['texts']);
    $settings['fields'] = array_intersect_key($settings['fields'], $defaults['fields']);
    return $settings;
}

function template_settings_from_source(array $current, array $source): array
{
    $settings = $current['settings'];
    $page = page_size($current['kind']);
    foreach ($settings['texts'] as $key => $_) $settings['texts'][$key] = trim((string) ($source['text'][$key] ?? ''));
    foreach ($settings['fields'] as $key => $field) {
        foreach (array_merge(['x','y','w','pt'], array_intersect(['h','row'], array_keys($field))) as $part) {
            $value = filter_var($source['field'][$key][$part] ?? null, FILTER_VALIDATE_FLOAT);
            $max = match ($part) { 'y', 'h' => $page['h'], 'pt' => 48, 'row' => 20, default => $page['w'] };
            $min = match ($part) { 'w', 'h' => 10, 'pt' => 5, 'row' => 3, default => 0 };
            if ($value === false || $value < $min || $value > $max) throw new DomainException('Posisi atau ukuran bidang ' . $field['label'] . ' tidak valid.');
            $settings['fields'][$key][$part] = (float) $value;
        }
        if ($settings['fields'][$key]['x'] + $settings['fields'][$key]['w'] > $page['w']) throw new DomainException('Bidang ' . $field['label'] . ' melewati tepi kanan kertas.');
        if (isset($settings['fields'][$key]['h']) && $settings['fields'][$key]['y'] + $settings['fields'][$key]['h'] > $page['h']) throw new DomainException('Bidang ' . $field['label'] . ' melewati tepi bawah kertas.');
        $align = (string) ($source['field'][$key]['align'] ?? 'center');
        if (!in_array($align, ['left','center','right'], true)) throw new DomainException('Perataan teks tidak valid.');
        $settings['fields'][$key]['align'] = $align;
    }
    if (isset($settings['qr'])) {
        $settings['qr']['enabled'] = isset($source['qr_enabled']);
        foreach (['x','y','size'] as $part) {
            $value = filter_var($source['qr'][$part] ?? null, FILTER_VALIDATE_FLOAT);
            if ($value === false || $value < 0 || $value > ($part === 'y' ? $page['h'] : $page['w'])) throw new DomainException('Posisi QR tidak valid.');
            $settings['qr'][$part] = (float) $value;
        }
        if ($settings['qr']['size'] < 15 || $settings['qr']['size'] > 40 || $settings['qr']['x'] + $settings['qr']['size'] > $page['w'] || $settings['qr']['y'] + $settings['qr']['size'] > $page['h']) throw new DomainException('Ukuran atau posisi QR melewati blanko.');
    }
    foreach ($settings as $key => $value) {
        if (is_bool($value)) $settings[$key] = isset($source[$key]);
    }
    return $settings;
}
function save_template_version(string $kind, string $program, array $source): int
{
    $current = active_template($kind, $program);
    if (!$current) throw new DomainException('Template tidak ditemukan.');
    $settings = template_settings_from_source($current, $source);
    $label = trim((string) ($source['label'] ?? ''));
    if ($label === '') throw new DomainException('Nama versi wajib diisi.');
    $db = db();
    $db->beginTransaction();
    try {
        $db->prepare('UPDATE templates SET active=0 WHERE kind=? AND program_code=?')->execute([$kind, $program]);
        $stmt = $db->prepare('INSERT INTO templates(kind,program_code,version,label,settings_json,active,created_at) VALUES(?,?,?,?,?,1,?)');
        $stmt->execute([$kind, $program, (int) $current['version'] + 1, $label, json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), now_local()]);
        $id = (int) $db->lastInsertId();
        audit('template', $id, 'version_created', $kind . ':' . $program);
        $db->commit();
        return $id;
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

// Latar blanko hanya alat bantu kalibrasi di editor; tidak ikut versi template dan tidak dicetak.
const TEMPLATE_BACKGROUND_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
const TEMPLATE_BACKGROUND_MAX_BYTES = 10 * 1024 * 1024;

function template_background_dir(): string
{
    return rtrim((string) app_config()['storage_root'], '/\\') . '/backgrounds';
}

function template_background_path(string $kind, string $program): ?string
{
    if (!in_array($kind, ['diploma','transcript'], true) || !array_key_exists($program, programs())) return null;
    foreach (TEMPLATE_BACKGROUND_TYPES as $extension) {
        $path = template_background_dir() . '/' . $kind . '-' . $program . '.' . $extension;
        if (is_file($path)) return $path;
    }
    return null;
}

function save_template_background(string $kind, string $program, string $sourcePath): ?string
{
    if (!in_array($kind, ['diploma','transcript'], true) || !array_key_exists($program, programs())) throw new DomainException('Template tidak dikenal.');
    $page = page_size($kind);
    $size = is_file($sourcePath) ? filesize($sourcePath) : false;
    if ($size === false || $size === 0) throw new DomainException('Pilih gambar blanko yang akan diunggah.');
    if ($size > TEMPLATE_BACKGROUND_MAX_BYTES) throw new DomainException('Ukuran gambar latar maksimal 10 MB.');
    $info = @getimagesize($sourcePath);
    $extension = TEMPLATE_BACKGROUND_TYPES[$info['mime'] ?? ''] ?? null;
    if (!$info || $extension === null) throw new DomainException('Gunakan gambar JPG, PNG, atau WebP.');
    [$width, $height] = $info;
    if ($page['w'] > $page['h'] ? $width <= $height : $height <= $width) throw new DomainException($kind === 'diploma' ? 'Gunakan gambar lanskap (lebar lebih besar dari tinggi), sesuai blanko ijazah.' : 'Gunakan gambar potret (tinggi lebih besar dari lebar), sesuai blanko transkrip.');
    if (min($width, $height) < 560 || max($width, $height) < 800) throw new DomainException('Resolusi gambar terlalu kecil. Pindai blanko minimal 100 dpi.');
    $directory = template_background_dir();
    if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) throw new RuntimeException('Direktori latar tidak dapat dibuat.');
    $old = template_background_path($kind, $program);
    $target = $directory . '/' . $kind . '-' . $program . '.' . $extension;
    $temporary = $target . '.tmp';
    if (!copy($sourcePath, $temporary)) throw new RuntimeException('Gambar latar gagal disimpan.');
    if ($old !== null && $old !== $target) unlink($old);
    if (!rename($temporary, $target)) throw new RuntimeException('Gambar latar gagal disimpan.');
    audit('template', null, 'background_uploaded', $kind . ':' . $program);
    $ratio = ($width / $height) / ($page['w'] / $page['h']);
    return abs($ratio - 1) > 0.03 ? 'Perbandingan ukuran gambar berbeda dari ' . $page['label'] . ', jadi gambar direntangkan agar pas. Pastikan pindaian mencakup seluruh tepi blanko.' : null;
}

function delete_template_background(string $kind, string $program): void
{
    $path = template_background_path($kind, $program);
    if ($path === null) return;
    unlink($path);
    audit('template', null, 'background_deleted', $kind . ':' . $program);
}

function documents_for(int $graduateId): array
{
    $stmt = db()->prepare('SELECT * FROM documents WHERE graduate_id=? ORDER BY id DESC');
    $stmt->execute([$graduateId]);
    return $stmt->fetchAll();
}

function find_document(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM documents WHERE id=?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function find_document_by_token(string $token): ?array
{
    $stmt = db()->prepare('SELECT * FROM documents WHERE qr_token=? AND kind=\'diploma\'');
    $stmt->execute([$token]);
    return $stmt->fetch() ?: null;
}

// Pemindaian oleh pengunjung umum; Admin yang sedang masuk tidak dihitung.
function record_verification(int $documentId): void
{
    db()->prepare('UPDATE documents SET verify_count=verify_count+1,last_verified_at=? WHERE id=?')->execute([now_local(), $documentId]);
}

function replacement_document(int $documentId): ?array
{
    $stmt = db()->prepare('SELECT * FROM documents WHERE replaces_id=? AND status=\'issued\' ORDER BY id DESC LIMIT 1');
    $stmt->execute([$documentId]);
    return $stmt->fetch() ?: null;
}

function mask_nim(string $nim): string
{
    $length = mb_strlen($nim);
    $start = $length >= 8 ? 4 : intdiv($length, 3);
    $end = $length >= 8 ? 2 : ($length > 3 ? 1 : 0);
    return mb_substr($nim, 0, $start) . str_repeat('•', max(0, $length - $start - $end)) . ($end ? mb_substr($nim, -$end) : '');
}

function issue_document(int $graduateId, string $kind): int
{
    if (!in_array($kind, ['diploma','transcript'], true)) throw new DomainException('Jenis dokumen tidak valid.');
    $graduate = find_graduate($graduateId);
    if (!$graduate) throw new DomainException('Lulusan tidak ditemukan.');
    $number = trim((string) $graduate[$kind === 'diploma' ? 'diploma_number' : 'transcript_number']);
    if ($graduate['nim'] === '' || $graduate['name'] === '' || $graduate['birth_place'] === '' || $graduate['birth_date'] === '' || $graduate['graduation_date'] === '' || $graduate['issue_date'] === '' || $number === '') throw new DomainException('Identitas, tanggal, dan nomor dokumen harus lengkap sebelum diterbitkan.');
    if ($graduate['issue_date'] < $graduate['graduation_date']) throw new DomainException('Tanggal penerbitan tidak boleh lebih awal dari tanggal kelulusan.');
    $template = active_template($kind, $graduate['program_code']);
    if (!$template) throw new DomainException('Template aktif belum tersedia.');
    $grades = grades_for($graduateId);
    if ($kind === 'transcript' && !$grades) throw new DomainException('Nilai mata kuliah belum diisi.');
    if ($kind === 'transcript' && !has_official_totals($graduate)) throw new DomainException('Total SKS dan IPK resmi belum diisi.');
    if ($kind === 'transcript') {
        if (official_totals_match(grade_summary($grades), $graduate) === false) throw new DomainException('Jumlah SKS/IPK resmi tidak cocok dengan nilai mata kuliah. Periksa kembali data dan aturan nilai.');
    }
    if ($kind === 'transcript' && trim((string) ($template['settings']['texts']['signer_name'] ?? '')) === '') throw new DomainException('Nama penandatangan transkrip belum diisi di template.');
    if ($kind === 'diploma' && (($template['settings']['texts']['left_name'] ?? '') === '' || ($template['settings']['texts']['right_name'] ?? '') === '')) throw new DomainException('Nama kedua penandatangan ijazah belum diisi di template.');
    if ($kind === 'diploma' && trim((string) ($template['settings']['texts']['program_accreditation'] ?? '')) === '') throw new DomainException('Redaksi akreditasi program studi belum diisi di template ijazah.');
    if ($kind === 'diploma' && !empty($template['settings']['qr']['enabled']) && ($problem = qr_base_url_problem((string) app_config()['base_url'])) !== null) throw new DomainException($problem . ' Ijazah dengan QR belum dapat diterbitkan.');
    $db = db();
    $db->beginTransaction();
    $pdfPath = null;
    try {
        $stmt = $db->prepare('SELECT id FROM documents WHERE graduate_id=? AND kind=? AND status=\'issued\' LIMIT 1');
        $stmt->execute([$graduateId, $kind]);
        if ($stmt->fetch()) throw new DomainException('Dokumen aktif sudah ada. Batalkan dahulu jika perlu penggantian.');
        $stmt = $db->prepare('SELECT id FROM documents WHERE kind=? AND document_number=? AND status=\'issued\' LIMIT 1');
        $stmt->execute([$kind, $number]);
        if ($stmt->fetch()) throw new DomainException('Nomor dokumen sudah dipakai dokumen aktif lain.');
        $replaced = $db->prepare('SELECT id FROM documents WHERE graduate_id=? AND kind=? AND status=\'revoked\' ORDER BY id DESC LIMIT 1');
        $replaced->execute([$graduateId, $kind]);
        $replacesId = $replaced->fetchColumn() ?: null;
        $snapshot = ['graduate'=>$graduate, 'grades'=>$grades, 'template'=>$template, 'summary'=>grade_summary($grades)];
        $token = $kind === 'diploma' ? bin2hex(random_bytes(24)) : null;
        $stmt = $db->prepare('INSERT INTO documents(graduate_id,kind,document_number,status,template_id,snapshot_json,qr_token,issued_at,replaces_id) VALUES(?,?,?,\'issued\',?,?,?,?,?)');
        $stmt->execute([$graduateId,$kind,$number,$template['id'],json_encode($snapshot,JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),$token,now_local(),$replacesId]);
        $id = (int) $db->lastInsertId();
        $pdfBytes = render_pdf($kind, $snapshot, $token);
        $documentsDir = rtrim((string) app_config()['storage_root'], '/\\') . '/documents';
        if (!is_dir($documentsDir) && !mkdir($documentsDir, 0770, true) && !is_dir($documentsDir)) throw new RuntimeException('Direktori dokumen tidak dapat dibuat.');
        $pdfPath = $documentsDir . '/' . $id . '.pdf';
        if (file_put_contents($pdfPath, $pdfBytes, LOCK_EX) === false) throw new RuntimeException('PDF final gagal disimpan.');
        $db->prepare('UPDATE documents SET pdf_path=?,pdf_sha256=? WHERE id=?')->execute(['documents/' . $id . '.pdf', hash('sha256', $pdfBytes), $id]);
        $db->prepare('INSERT INTO document_events(document_id,admin_id,action,created_at) VALUES(?,?,\'issued\',?)')->execute([$id,$_SESSION['admin_id'] ?? null,now_local()]);
        audit('document', $id, 'issued', $kind);
        $db->commit();
        return $id;
    } catch (Throwable $e) {
        $db->rollBack();
        if ($pdfPath && is_file($pdfPath)) unlink($pdfPath);
        throw $e;
    }
}

// PDF arsip disimpan relatif terhadap storage_root; dokumen lama mungkin masih memakai path lengkap.
function document_pdf_file(array $document): string
{
    $path = (string) $document['pdf_path'];
    if ($path === '' || is_file($path)) return $path;
    return rtrim((string) app_config()['storage_root'], '/\\') . '/' . ltrim($path, '/\\');
}

function revoke_document(int $id, string $reason): void
{
    $reason = trim($reason);
    if ($reason === '') throw new DomainException('Alasan pembatalan wajib diisi.');
    $db = db();
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('UPDATE documents SET status=\'revoked\',revoked_at=?,revoked_reason=? WHERE id=? AND status=\'issued\'');
        $stmt->execute([now_local(),$reason,$id]);
        if (!$stmt->rowCount()) throw new DomainException('Dokumen aktif tidak ditemukan.');
        $db->prepare('INSERT INTO document_events(document_id,admin_id,action,reason,created_at) VALUES(?,?,\'revoked\',?,?)')->execute([$id,$_SESSION['admin_id'] ?? null,$reason,now_local()]);
        audit('document', $id, 'revoked', $reason);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function record_reprint(int $id): void
{
    db()->prepare('INSERT INTO document_events(document_id,admin_id,action,created_at) VALUES(?,?,\'downloaded\',?)')->execute([$id,$_SESSION['admin_id'] ?? null,now_local()]);
}
