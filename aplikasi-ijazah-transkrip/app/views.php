<?php
declare(strict_types=1);

function ui_icon(string $name): string
{
    $paths = [
        'home' => '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/>',
        'people' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'upload' => '<path d="M12 16V3m-5 5 5-5 5 5M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>',
        'template' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 9v12M13 13h5M13 17h5"/>',
        'document' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/>',
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
    ];
    return '<svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['document']) . '</svg>';
}

function page_header(string $title, string $active = '', bool $public = false): void
{
    $admin = $public ? null : current_admin();
    $items = ['dashboard'=>['Beranda','home'],'graduates'=>['Data lulusan','people'],'import'=>['Impor lulusan','upload']];
    if ($admin && (int) db()->query('SELECT COUNT(*) FROM graduates')->fetchColumn() > 0) $items['grades'] = ['Nilai','document'];
    $items['templates'] = ['Template','template'];
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . h($title) . ' — STIE Nusantara</title><link rel="stylesheet" href="assets/bootstrap/bootstrap.min.css"><link rel="stylesheet" href="assets/app.css?v=12"></head><body' . ($admin ? ' class="has-admin"' : ' class="public-page"') . '>';
    if ($admin) {
        echo '<header class="app-header"><div class="app-header-inner"><a class="app-brand" href="' . h(route_url('dashboard')) . '"><span class="brand-mark">SN</span><span class="brand-copy"><strong>STIE Nusantara</strong><span>Ijazah &amp; Transkrip</span></span></a><button class="navbar-toggler app-menu-toggle d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#main-navigation" aria-controls="main-navigation" aria-expanded="false" aria-label="Buka menu"><span aria-hidden="true">☰</span></button><nav class="collapse navbar-collapse app-navigation" id="main-navigation" aria-label="Menu utama"><div class="navbar-nav app-nav-links">';
        foreach ($items as $route => [$label, $icon]) echo '<a class="nav-link ' . ($active === $route ? 'active' : '') . '"' . ($active === $route ? ' aria-current="page"' : '') . ' href="' . h(route_url($route)) . '">' . ui_icon($icon) . '<span>' . h($label) . '</span></a>';
        echo '</div><div class="app-account"><span class="app-username" title="Administrator">' . h($admin['username']) . '</span><form method="post" action="' . h(route_url('logout')) . '">' . csrf_field() . '<button class="btn btn-sm btn-outline-secondary" type="submit">Keluar</button></form></div></nav></div></header>';
        echo '<div class="app-main"><main class="container-fluid app-content px-3 px-lg-4 py-4 py-lg-5">';
    } else {
        echo '<main class="container py-5 public-content">';
    }
    foreach (take_flashes() as $message) echo '<div class="alert alert-' . h($message['type']) . '" role="alert">' . h($message['message']) . '</div>';
}

function page_footer(bool $public = false): void
{
    echo '</main>' . (!$public && current_admin() ? '</div>' : '') . '<script src="assets/bootstrap/bootstrap.bundle.min.js"></script></body></html>';
}

function login_page(): void
{
    page_header('Masuk Admin');
    echo '<div class="login-shell"><div class="login-brand"><div class="brand-mark">SN</div><div><strong>STIE Nusantara Makassar</strong><span>Administrasi dokumen kelulusan</span></div></div><div class="card login-card"><div class="card-body p-4 p-md-5"><div class="eyebrow mb-2">AKSES ADMIN</div><h1 class="h3 mb-2">Selamat datang</h1><p class="text-secondary mb-4">Masuk untuk mengelola ijazah dan transkrip nilai.</p><form method="post" action="' . h(route_url('login')) . '">' . csrf_field() . '<div class="mb-3"><label class="form-label" for="username">Nama akun</label><input class="form-control" id="username" name="username" autocomplete="username" required></div><div class="mb-4"><label class="form-label" for="password">Kata sandi</label><input class="form-control" id="password" type="password" name="password" autocomplete="current-password" required></div><button class="btn btn-primary w-100" type="submit">Masuk ke aplikasi ' . ui_icon('arrow') . '</button></form></div></div><p class="login-caption">Ijazah dan transkrip dikelola dari satu data lulusan.</p></div>';
    page_footer();
}

function dashboard_page(): void
{
    $db = db();
    $graduateCount = (int) $db->query('SELECT COUNT(*) FROM graduates')->fetchColumn();
    $gradeCount = (int) $db->query('SELECT COUNT(*) FROM grades')->fetchColumn();
    $issuedCount = (int) $db->query('SELECT COUNT(*) FROM documents WHERE status="issued"')->fetchColumn();
    page_header('Beranda', 'dashboard');
    echo '<section class="dashboard-hero"><div class="hero-copy"><div class="eyebrow">RUANG KERJA ADMIN</div><h1>Dokumen kelulusan,<br>lebih tertata.</h1><p>Masukkan data lulusan terlebih dahulu, lalu kelola nilai untuk menyiapkan transkrip.</p><div class="d-flex flex-wrap gap-2"><a class="btn btn-primary" href="' . h(route_url('graduate-form')) . '">' . ui_icon('plus') . 'Tambah lulusan</a><a class="btn btn-outline-secondary" href="' . h(route_url('import')) . '">' . ui_icon('upload') . 'Impor lulusan</a></div></div><div class="hero-decoration" aria-hidden="true"><div class="hero-paper hero-paper-back"></div><div class="hero-paper hero-paper-front"><div class="hero-paper-seal">SN</div><div class="hero-paper-line long"></div><div class="hero-paper-line"></div><div class="hero-paper-line short"></div></div></div></section>';
    echo '<div class="row g-3 dashboard-stats"><div class="col-md-4"><div class="stat-card"><div class="stat-icon">' . ui_icon('people') . '</div><div class="stat-number">' . $graduateCount . '</div><div class="stat-label">Lulusan tercatat</div></div></div><div class="col-md-4"><div class="stat-card"><div class="stat-icon">' . ui_icon('template') . '</div><div class="stat-number">' . $gradeCount . '</div><div class="stat-label">Baris nilai tersimpan</div></div></div><div class="col-md-4"><div class="stat-card"><div class="stat-icon">' . ui_icon('document') . '</div><div class="stat-number">' . $issuedCount . '</div><div class="stat-label">Dokumen aktif</div></div></div></div>';
    echo '<div class="row g-3 mt-1"><div class="col-xl-8"><section class="card dashboard-section h-100"><div class="card-body"><div class="section-heading"><div><div class="eyebrow">ALUR KERJA</div><h2 class="h5 mb-0">Mulai dari sini</h2></div></div><a class="workflow-link" href="' . h(route_url('templates')) . '"><span class="workflow-number">01</span><span class="workflow-copy"><strong>Atur template dokumen</strong><small>Sesuaikan teks ijazah, transkrip, dan posisi QR.</small></span>' . ui_icon('arrow') . '</a><a class="workflow-link" href="' . h(route_url('graduates')) . '"><span class="workflow-number">02</span><span class="workflow-copy"><strong>Masukkan data lulusan</strong><small>Tambah satu per satu atau gunakan template Excel lulusan.</small></span>' . ui_icon('arrow') . '</a>' . ($graduateCount > 0 ? '<a class="workflow-link" href="' . h(route_url('grades')) . '"><span class="workflow-number">03</span><span class="workflow-copy"><strong>Input atau impor nilai</strong><small>Menu Nilai tersedia setelah data lulusan tersimpan.</small></span>' . ui_icon('arrow') . '</a>' : '<div class="workflow-link"><span class="workflow-number">03</span><span class="workflow-copy"><strong>Input atau impor nilai</strong><small>Menu Nilai muncul setelah data lulusan tersimpan.</small></span></div>') . '</div></section></div><div class="col-xl-4"><aside class="card dashboard-note h-100"><div class="card-body"><div class="note-icon">' . ui_icon('document') . '</div><h2 class="h5">Siap untuk cetak?</h2><p class="text-secondary">Periksa pratinjau PDF dan cocokkan posisi teks serta QR dengan blanko fisik sebelum menerbitkan ijazah.</p><a class="text-link" href="' . h(route_url('templates')) . '">Buka pengaturan template ' . ui_icon('arrow') . '</a></div></aside></div></div>';
    page_footer();
}

function graduates_page(string $search): void
{
    $rows = list_graduates($search);
    page_header('Data lulusan', 'graduates');
    echo '<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4"><div><h1 class="h4 mb-1">Data lulusan</h1><p class="text-secondary mb-0">Identitas bersama untuk kedua dokumen.</p></div><a class="btn btn-primary" href="' . h(route_url('graduate-form')) . '">Tambah lulusan</a></div><form class="row g-2 mb-3" method="get"><input type="hidden" name="r" value="graduates"><div class="col-sm-5"><input class="form-control" name="q" value="' . h($search) . '" placeholder="Cari nama atau NIM" aria-label="Cari nama atau NIM"></div><div class="col-auto"><button class="btn btn-outline-secondary">Cari</button></div></form><div class="table-responsive border rounded"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>NIM</th><th>Nama</th><th>Prodi</th><th>Nilai</th><th>Ijazah</th><th>Transkrip</th><th></th></tr></thead><tbody>';
    foreach ($rows as $row) {
        echo '<tr><td>' . h($row['nim']) . '</td><td>' . h($row['name']) . '</td><td>' . h(programs()[$row['program_code']]['label'] ?? $row['program_code']) . '</td><td>' . (int) $row['grade_count'] . ' MK</td><td>' . status_badge($row['diploma_status']) . '</td><td>' . status_badge($row['transcript_status']) . '</td><td><a class="btn btn-sm btn-outline-secondary" href="' . h(route_url('graduate', ['id'=>$row['id']])) . '">Buka</a></td></tr>';
    }
    if (!$rows) echo '<tr><td colspan="7" class="empty-table-cell"><div class="empty-state"><div class="empty-state-icon">' . ui_icon('people') . '</div><strong>' . ($search === '' ? 'Belum ada data lulusan' : 'Lulusan tidak ditemukan') . '</strong><span>' . ($search === '' ? 'Tambahkan satu lulusan atau impor data dari Excel.' : 'Coba kata kunci nama atau NIM yang lain.') . '</span>' . ($search === '' ? '<a class="btn btn-sm btn-primary mt-2" href="' . h(route_url('graduate-form')) . '">Tambah lulusan</a>' : '') . '</div></td></tr>';
    echo '</tbody></table></div>';
    page_footer();
}

function grades_page(string $search): void
{
    $rows = list_graduates($search);
    page_header('Nilai', 'grades');
    echo '<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4"><div><h1 class="h4 mb-1">Nilai mahasiswa</h1><p class="text-secondary mb-0">Pilih lulusan untuk input nilai satu per satu, atau impor nilai dari Excel.</p></div><a class="btn btn-primary" href="' . h(route_url('import-grades')) . '">' . ui_icon('upload') . 'Impor nilai</a></div>';
    echo '<form class="row g-2 mb-3" method="get"><input type="hidden" name="r" value="grades"><div class="col-sm-5"><input class="form-control" name="q" value="' . h($search) . '" placeholder="Cari nama atau NIM" aria-label="Cari nama atau NIM"></div><div class="col-auto"><button class="btn btn-outline-secondary">Cari</button></div></form>';
    echo '<div class="table-responsive border rounded"><table class="table table-hover align-middle mb-0"><thead><tr><th>NIM</th><th>Nama</th><th>Prodi</th><th>Nilai tersimpan</th><th></th></tr></thead><tbody>';
    foreach ($rows as $row) {
        $url = route_url('graduate', ['id'=>$row['id'], 'nilai'=>1]) . '#nilai';
        echo '<tr><td>' . h($row['nim']) . '</td><td>' . h($row['name']) . '</td><td>' . h(programs()[$row['program_code']]['label'] ?? $row['program_code']) . '</td><td>' . (int) $row['grade_count'] . ' mata kuliah</td><td><a class="btn btn-sm btn-outline-secondary" href="' . h($url) . '">Input nilai</a></td></tr>';
    }
    if (!$rows) echo '<tr><td colspan="5" class="text-secondary text-center py-4">Lulusan tidak ditemukan.</td></tr>';
    echo '</tbody></table></div>';
    page_footer();
}

function status_badge(?string $status): string
{
    return match ($status) {
        'issued' => '<span class="badge text-bg-success">Terbit</span>',
        'revoked' => '<span class="badge text-bg-danger">Dibatalkan</span>',
        default => '<span class="text-secondary small">Draf</span>',
    };
}

function graduate_form_page(?array $graduate = null): void
{
    $edit = $graduate !== null;
    $title = $edit ? 'Ubah lulusan' : 'Tambah lulusan';
    page_header($title, 'graduates');
    echo '<div class="d-flex align-items-center gap-3 mb-4"><a class="btn btn-sm btn-outline-secondary" href="' . h($edit ? route_url('graduate', ['id'=>$graduate['id']]) : route_url('graduates')) . '">Kembali</a><h1 class="h4 mb-0">' . h($title) . '</h1></div><div class="card"><div class="card-body"><form method="post" action="' . h(route_url('graduate-save')) . '">' . csrf_field() . '<input type="hidden" name="id" value="' . h($graduate['id'] ?? '') . '"><div class="row g-3">';
    $fields = [
        'nim'=>['NIM','text',true], 'name'=>['Nama resmi','text',true], 'birth_place'=>['Tempat lahir','text',false],
        'birth_date'=>['Tanggal lahir','date',false], 'education_program'=>['Program pendidikan','text',false],
        'graduation_date'=>['Tanggal lulus','date',false], 'diploma_number'=>['Nomor ijazah nasional','text',false],
        'transcript_number'=>['Nomor transkrip','text',false], 'issue_date'=>['Tanggal penerbitan','date',false],
        'official_credits'=>['Total SKS resmi','number',false], 'official_gpa'=>['IPK resmi','number',false],
        'thesis_title'=>['Judul skripsi','text',false],
    ];
    foreach ($fields as $key => [$label,$type,$required]) {
        $step = $key === 'official_gpa' ? ' step="0.01" min="0" max="4"' : ($key === 'official_credits' ? ' step="0.5" min="0"' : '');
        $col = $key === 'thesis_title' ? 'col-12' : 'col-md-4';
        echo '<div class="' . $col . '"><label class="form-label" for="' . h($key) . '">' . h($label) . '</label><input class="form-control" id="' . h($key) . '" name="' . h($key) . '" type="' . h($type) . '" value="' . h($graduate[$key] ?? '') . '"' . ($required ? ' required' : '') . $step . '></div>';
    }
    echo '<div class="col-md-4"><label class="form-label" for="program_code">Program studi</label><select class="form-select" id="program_code" name="program_code" required>';
    foreach (programs() as $code => $program) echo '<option value="' . h($code) . '"' . (($graduate['program_code'] ?? '') === $code ? ' selected' : '') . '>' . h($program['label']) . '</option>';
    echo '</select></div></div><p class="form-text mt-3">NIM dan nomor dokumen disimpan sebagai teks agar angka nol di depan tidak hilang. Total SKS/IPK resmi dibandingkan dengan nilai sebelum transkrip diterbitkan.</p><button class="btn btn-primary mt-2" type="submit">Simpan data lulusan</button></form></div></div>';
    page_footer();
}

function graduate_detail_page(array $graduate): void
{
    $id = (int) $graduate['id'];
    $grades = grades_for($id);
    $summary = grade_summary($grades);
    $docs = documents_for($id);
    $fromGrades = (string) ($_GET['nilai'] ?? '') === '1';
    $profileLocked = false;
    $gradesLocked = false;
    foreach ($docs as $doc) {
        if ($doc['status'] !== 'issued') continue;
        $profileLocked = true;
        if ($doc['kind'] === 'transcript') $gradesLocked = true;
    }
    page_header('Detail lulusan', $fromGrades ? 'grades' : 'graduates');
    echo '<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-4"><div><a class="small text-decoration-none" href="' . h(route_url($fromGrades ? 'grades' : 'graduates')) . '">← ' . ($fromGrades ? 'Nilai' : 'Data lulusan') . '</a><h1 class="h4 mt-2 mb-1">' . h($graduate['name']) . '</h1><div class="text-secondary">NIM ' . h($graduate['nim']) . ' · ' . h(programs()[$graduate['program_code']]['label'] ?? $graduate['program_code']) . '</div></div>' . ($profileLocked ? '<span class="small text-secondary">Identitas terkunci selama ada dokumen aktif</span>' : '<a class="btn btn-outline-secondary" href="' . h(route_url('graduate-form',['id'=>$id])) . '">Ubah identitas</a>') . '</div>';
    echo '<div class="row g-3 mb-3"><div class="col-lg-6"><div class="card h-100"><div class="card-body"><h2 class="h6">Ijazah</h2><p class="small text-secondary">Nomor: ' . h($graduate['diploma_number'] ?: 'Belum diisi') . '</p><div class="d-flex flex-wrap gap-2"><a class="btn btn-sm btn-outline-secondary" target="_blank" href="' . h(route_url('preview',['id'=>$id,'kind'=>'diploma'])) . '">Pratinjau PDF</a>' . issue_button($id,'diploma',$docs) . '</div></div></div></div><div class="col-lg-6"><div class="card h-100"><div class="card-body"><h2 class="h6">Transkrip nilai</h2><p class="small text-secondary">' . count($grades) . ' mata kuliah · ' . number_format($summary['credits'], 0, ',', '.') . ' SKS · IPK hitung ' . ($summary['gpa'] !== null ? number_format($summary['gpa'], 2, ',', '.') : '—') . '</p><div class="d-flex flex-wrap gap-2"><a class="btn btn-sm btn-outline-secondary" target="_blank" href="' . h(route_url('preview',['id'=>$id,'kind'=>'transcript'])) . '">Pratinjau PDF</a>' . issue_button($id,'transcript',$docs) . '</div></div></div></div></div>';
    echo '<div class="card mb-3" id="nilai"><div class="card-body"><div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3"><h2 class="h6 mb-0">Mata kuliah dan nilai</h2><span class="small text-secondary">Bobot nilai mengikuti data resmi yang dimasukkan Admin</span></div><div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Urut</th><th>Kode</th><th>Nama mata kuliah</th><th>SKS</th><th>Nilai</th><th>Bobot</th><th></th></tr></thead><tbody>';
    foreach ($grades as $grade) echo '<tr><td>' . h($grade['position']) . '</td><td>' . h($grade['course_code']) . '</td><td>' . h($grade['course_name']) . '</td><td>' . h($grade['credits']) . '</td><td>' . h($grade['grade_letter']) . '</td><td>' . h($grade['grade_weight']) . '</td><td>' . ($gradesLocked ? '' : '<form method="post" action="' . h(route_url('grade-delete')) . '" onsubmit="return confirm(\'Hapus mata kuliah ini?\')">' . csrf_field() . '<input type="hidden" name="graduate_id" value="' . $id . '"><input type="hidden" name="grade_id" value="' . (int) $grade['id'] . '"><button class="btn btn-sm btn-link text-danger" type="submit">Hapus</button></form>') . '</td></tr>';
    if (!$grades) echo '<tr><td colspan="7" class="text-secondary text-center py-3">Belum ada nilai.</td></tr>';
    if ($gradesLocked) {
        echo '</tbody></table></div><p class="small text-secondary mt-3 mb-0">Nilai terkunci selama transkrip aktif. Batalkan transkrip untuk mengubah nilai.</p></div></div>';
    } else {
        echo '</tbody></table></div><details class="mt-3"' . ($fromGrades ? ' open' : '') . '><summary class="link-primary">Tambah atau perbarui mata kuliah</summary><form class="row g-2 mt-2" method="post" action="' . h(route_url('grade-save')) . '">' . csrf_field() . '<input type="hidden" name="graduate_id" value="' . $id . '">';
        foreach (['position'=>'Urut','course_code'=>'Kode MK','course_name'=>'Mata kuliah','group_name'=>'Kelompok','credits'=>'SKS','grade_letter'=>'Nilai huruf','grade_weight'=>'Bobot nilai'] as $key=>$label) echo '<div class="col-sm-' . ($key === 'course_name' ? '4' : '2') . '"><label class="form-label small" for="grade_' . h($key) . '">' . h($label) . '</label><input class="form-control form-control-sm" id="grade_' . h($key) . '" name="' . h($key) . '"' . (in_array($key,['position','course_code','course_name','credits','grade_letter','grade_weight'],true) ? ' required' : '') . '></div>';
        echo '<div class="col-12"><button class="btn btn-sm btn-primary" type="submit">Simpan nilai</button></div></form></details></div></div>';
    }
    echo '<div class="card"><div class="card-body"><h2 class="h6 mb-3">Riwayat dokumen</h2><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Jenis</th><th>Nomor</th><th>Status</th><th>Terbit</th><th>Tindakan</th></tr></thead><tbody>';
    foreach ($docs as $doc) {
        echo '<tr><td>' . ($doc['kind']==='diploma'?'Ijazah':'Transkrip') . '</td><td>' . h($doc['document_number']) . '</td><td>' . status_badge($doc['status']) . '</td><td>' . h($doc['issued_at']) . '</td><td><div class="d-flex flex-wrap gap-2"><a class="btn btn-sm btn-outline-secondary" href="' . h(route_url('download',['id'=>$doc['id']])) . '">PDF</a>';
        if ($doc['kind']==='diploma' && $doc['qr_token']) echo '<a class="btn btn-sm btn-outline-secondary" target="_blank" href="' . h(route_url('verify',['t'=>$doc['qr_token']])) . '">Verifikasi</a>';
        if ($doc['status']==='issued') echo '<form class="d-flex gap-1" method="post" action="' . h(route_url('revoke')) . '">' . csrf_field() . '<input type="hidden" name="id" value="' . (int) $doc['id'] . '"><input class="form-control form-control-sm" name="reason" aria-label="Alasan pembatalan" placeholder="Alasan pembatalan" required><button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm(\'Batalkan dokumen terbit ini?\')">Batalkan</button></form>';
        echo '</div></td></tr>';
    }
    if (!$docs) echo '<tr><td colspan="5" class="text-secondary text-center py-3">Belum ada dokumen terbit.</td></tr>';
    echo '</tbody></table></div></div></div>';
    page_footer();
}

function issue_button(int $graduateId, string $kind, array $docs): string
{
    foreach ($docs as $doc) if ($doc['kind']===$kind && $doc['status']==='issued') return status_badge('issued');
    return '<form method="post" action="' . h(route_url('issue')) . '" onsubmit="return confirm(\'Data dan pratinjau sudah diperiksa terhadap sumber resmi?\')">' . csrf_field() . '<input type="hidden" name="graduate_id" value="' . $graduateId . '"><input type="hidden" name="kind" value="' . h($kind) . '"><button class="btn btn-sm btn-primary" type="submit">Terbitkan</button></form>';
}

function import_page(string $kind): void
{
    $isGrades = $kind === 'grades';
    $preview = load_import_preview();
    if (($preview['kind'] ?? '') !== $kind) $preview = null;
    page_header($isGrades ? 'Impor nilai' : 'Impor lulusan', $isGrades ? 'grades' : 'import');
    echo '<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4"><div><h1 class="h4 mb-2">' . ($isGrades ? 'Impor nilai' : 'Impor data lulusan') . '</h1><p class="text-secondary mb-0">' . ($isGrades ? 'NIM pada berkas nilai harus sudah tersimpan di Data lulusan. Nilai tiap NIM yang diimpor akan mengganti daftar nilai sebelumnya.' : 'Simpan identitas lulusan terlebih dahulu. Setelah ada lulusan, menu Nilai akan tersedia untuk input atau impor nilai.') . '</p></div>' . ($isGrades ? '<a class="btn btn-sm btn-outline-secondary" href="' . h(route_url('grades')) . '">← Menu Nilai</a>' : '') . '</div>';
    echo '<div class="row g-3"><div class="col-xl-5"><div class="card"><div class="card-body"><h2 class="h6 mb-3">Pilih berkas ' . ($isGrades ? 'nilai' : 'lulusan') . '</h2><form method="post" action="' . h(route_url('import-preview')) . '" enctype="multipart/form-data">' . csrf_field() . '<input type="hidden" name="kind" value="' . h($kind) . '"><div class="mb-3"><label class="form-label" for="import_file">Berkas ' . ($isGrades ? 'nilai' : 'lulusan') . '</label><input class="form-control" id="import_file" name="import_file" type="file" accept=".xls,.xlsx,.csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required></div><p class="form-text mb-3">Format .xls, .xlsx, atau .csv; maksimal 5 MB.</p><button class="btn btn-primary" type="submit">Periksa data</button></form><hr><a class="btn btn-sm btn-outline-secondary" href="' . h(route_url('xls-sample',['kind'=>$kind])) . '">Unduh template ' . ($isGrades ? 'nilai' : 'lulusan') . ' (.xls)</a><p class="form-text mb-0 mt-2">Isi sheet Data mulai baris 2 tanpa mengubah judul kolom. Baca sheet Petunjuk untuk format pengisian.</p></div></div></div><div class="col-xl-7"><div class="card"><div class="card-body"><h2 class="h6 mb-3">Pratinjau impor</h2>';
    if (!$preview) {
        echo '<p class="text-secondary mb-0">Belum ada berkas yang diperiksa.</p>';
    } else {
        echo '<div class="mb-3"><strong>' . (int) ($isGrades ? $preview['grade_count'] : $preview['graduate_count']) . '</strong><div class="small text-secondary">' . ($isGrades ? 'Baris nilai' : 'Lulusan') . '</div></div>';
        if ($preview['errors']) {
            echo '<div class="alert alert-danger"><strong>' . count($preview['errors']) . ' masalah ditemukan.</strong><ul class="mb-0 mt-2">';
            foreach ($preview['errors'] as $error) echo '<li>' . h($error) . '</li>';
            echo '</ul></div><p class="small text-secondary mb-0">Perbaiki berkas lalu unggah kembali. Tidak ada data yang disimpan.</p>';
        } else {
            echo '<div class="alert alert-success">Semua baris lolos pemeriksaan awal. Data dengan dokumen aktif tidak dapat ditimpa.</div><form method="post" action="' . h(route_url('import-commit')) . '">' . csrf_field() . '<input type="hidden" name="kind" value="' . h($kind) . '"><button class="btn btn-primary" type="submit">Simpan hasil impor</button></form>';
        }
    }
    echo '</div></div></div></div>';
    page_footer();
}

function templates_page(): void
{
    page_header('Template', 'templates');
    echo '<h1 class="h4 mb-2">Template dokumen</h1><p class="text-secondary mb-4">Perubahan disimpan sebagai versi baru. Dokumen yang sudah terbit tetap memakai versi lama.</p><div class="table-responsive border rounded"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Dokumen</th><th>Prodi</th><th>Versi</th><th>Nama versi</th><th></th></tr></thead><tbody>';
    foreach (array_keys(programs()) as $program) foreach (['diploma','transcript'] as $kind) {
        $template = active_template($kind,$program);
        echo '<tr><td>' . ($kind==='diploma'?'Ijazah':'Transkrip') . '</td><td>' . h(programs()[$program]['label']) . '</td><td>' . (int) $template['version'] . '</td><td>' . h($template['label']) . '</td><td><a class="btn btn-sm btn-outline-secondary" href="' . h(route_url('template-edit',['kind'=>$kind,'program'=>$program])) . '">Atur template</a></td></tr>';
    }
    echo '</tbody></table></div>';
    page_footer();
}

// Grid editor digambar sebagai SVG dalam satuan mm agar garisnya tetap tajam saat kertas diperkecil.
function sheet_grid_svg(): string
{
    $lines = '';
    for ($x = 10; $x < 297; $x += 10) $lines .= '<line class="' . ($x % 50 === 0 ? 'grid-major' : 'grid-minor') . '" x1="' . $x . '" y1="0" x2="' . $x . '" y2="210"/>';
    for ($y = 10; $y < 210; $y += 10) $lines .= '<line class="' . ($y % 50 === 0 ? 'grid-major' : 'grid-minor') . '" x1="0" y1="' . $y . '" x2="297" y2="' . $y . '"/>';
    $lines .= '<line class="grid-center grid-center-x" x1="148.5" y1="0" x2="148.5" y2="210"/><line class="grid-center" x1="0" y1="105" x2="297" y2="105"/>';
    return '<svg class="sheet-grid" viewBox="0 0 297 210" preserveAspectRatio="none" aria-hidden="true">' . $lines . '</svg>';
}

function template_edit_page(array $template): void
{
    $kind = $template['kind'];
    $program = $template['program_code'];
    $settings = $template['settings'];
    $defaultLabel = 'Revisi ' . format_date_id(date('Y-m-d')) . ' ' . date('H.i');
    page_header('Atur template', 'templates');
    echo '<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4"><div><a class="small text-decoration-none" href="' . h(route_url('templates')) . '">← Template</a><h1 class="h4 mt-2 mb-1">' . ($kind==='diploma'?'Ijazah':'Transkrip') . ' · ' . h(programs()[$program]['label']) . '</h1><p class="text-secondary mb-0">Edit sambil melihat hasil. Perubahan baru berlaku setelah disimpan sebagai versi baru.</p></div><span class="badge text-bg-light border">Versi aktif ' . (int) $template['version'] . '</span></div><div class="template-editor row g-3"><div class="col-12 col-xl-5 order-2 order-xl-1"><form id="template-form" data-preview-url="' . h(route_url('template-live-preview')) . '" data-kind="' . h($kind) . '" method="post" action="' . h(route_url('template-save')) . '">' . csrf_field() . '<input type="hidden" name="kind" value="' . h($kind) . '"><input type="hidden" name="program" value="' . h($program) . '">';
    if ($kind === 'diploma') {
        echo '<div class="card mb-3"><div class="card-body"><h2 class="h6">Redaksi dan pejabat</h2><div class="row g-3">';
        $textLabels = ['institution_accreditation'=>'Akreditasi institusi','opening'=>'Kalimat pembuka','study'=>'Kalimat studi','program'=>'Program studi','program_accreditation'=>'Akreditasi prodi','confer'=>'Kalimat pemberian gelar','degree'=>'Gelar','rights'=>'Hak dan kewajiban','left_role'=>'Jabatan pejabat kiri','right_role'=>'Jabatan pejabat kanan','left_name'=>'Nama pejabat kiri','right_name'=>'Nama pejabat kanan','left_nidn'=>'NIDN pejabat kiri','right_nidn'=>'NIDN pejabat kanan'];
        foreach ($settings['texts'] as $key=>$value) echo '<div class="col-md-6"><label class="form-label small" for="text_' . h($key) . '">' . h($textLabels[$key] ?? $key) . '</label><input class="form-control" id="text_' . h($key) . '" name="text[' . h($key) . ']" value="' . h($value) . '"></div>';
        echo '</div></div></div><div class="card mb-3" id="qr-placement"><div class="card-body"><h2 class="h6">Lokasi QR verifikasi</h2><p class="small text-secondary">Kotak panduan QR selalu terlihat pada editor. Geser kotaknya di kertas atau isi koordinat dan ukuran di bawah.</p><div class="row g-2">';
        foreach (['x'=>'X (mm)','y'=>'Y (mm)','size'=>'Ukuran (mm)'] as $part=>$label) echo '<div class="col-4"><label class="form-label small" for="qr_' . $part . '">' . $label . '</label><input class="form-control form-control-sm" id="qr_' . $part . '" type="number" min="0" step="0.1" name="qr[' . $part . ']" value="' . h($settings['qr'][$part]) . '" required></div>';
        echo '</div><div class="form-check mt-3"><input class="form-check-input" type="checkbox" id="qr_enabled" name="qr_enabled"' . (!empty($settings['qr']['enabled'])?' checked':'') . '><label class="form-check-label" for="qr_enabled">Cetak QR pada ijazah final</label></div><div class="form-text">Biarkan tidak dicentang sampai posisi QR disetujui setelah uji blanko fisik.</div></div></div><div class="card mb-3" id="text-position-settings"><div class="card-body"><h2 class="h6 mb-2">Posisi teks pada blanko</h2><p class="small text-secondary mb-3">Cara tercepat: klik teks pada pratinjau lalu atur di panel atas kertas. Daftar ini membantu memilih bidang yang sulit diklik, misalnya bidang kosong.</p>';
        $positionGroups = [
            'Nomor dan identitas' => ['number', 'name', 'identity'],
            'Isi ijazah' => ['institution_accreditation', 'opening', 'study', 'program', 'program_accreditation', 'confer', 'degree', 'rights'],
            'Penerbitan dan pejabat' => ['issue', 'left_role', 'right_role', 'left_name', 'right_name', 'left_nidn', 'right_nidn'],
        ];
        $firstField = array_key_first($settings['fields']);
        $listedFields = [];
        echo '<label class="form-label" for="position-field-select">Bidang teks</label><select class="form-select mb-3" id="position-field-select">';
        foreach ($positionGroups as $groupLabel => $keys) {
            echo '<optgroup label="' . h($groupLabel) . '">';
            foreach ($keys as $key) {
                if (!isset($settings['fields'][$key])) continue;
                $listedFields[$key] = true;
                echo '<option value="' . h($key) . '"' . ($key === $firstField ? ' selected' : '') . '>' . h($settings['fields'][$key]['label']) . '</option>';
            }
            echo '</optgroup>';
        }
        $otherFields = array_diff_key($settings['fields'], $listedFields);
        if ($otherFields) {
            echo '<optgroup label="Bidang lainnya">';
            foreach ($otherFields as $key => $field) echo '<option value="' . h($key) . '"' . ($key === $firstField ? ' selected' : '') . '>' . h($field['label']) . '</option>';
            echo '</optgroup>';
        }
        echo '</select>';
        foreach ($settings['fields'] as $key => $field) {
            echo '<div class="position-field-panel" data-field-panel="' . h($key) . '"' . ($key === $firstField ? '' : ' hidden') . '><div class="position-field-heading"><strong>' . h($field['label']) . '</strong><span>A4 lanskap</span></div><div class="row g-3">';
            foreach (['x'=>'Posisi X dari kiri (mm)','y'=>'Posisi Y dari atas (mm)','w'=>'Lebar area teks (mm)','pt'=>'Ukuran huruf (pt)'] as $part => $label) {
                echo '<div class="col-6"><label class="form-label" for="position_' . h($key) . '_' . $part . '">' . $label . '</label><input class="form-control position-input" id="position_' . h($key) . '_' . $part . '" type="number" step="0.1" name="field[' . h($key) . '][' . $part . ']" value="' . h($field[$part]) . '" required></div>';
            }
            echo '<div class="col-12"><label class="form-label" for="position_' . h($key) . '_align">Perataan teks</label><select class="form-select" id="position_' . h($key) . '_align" name="field[' . h($key) . '][align]">';
            foreach (['left'=>'Kiri','center'=>'Tengah','right'=>'Kanan'] as $value=>$label) echo '<option value="' . $value . '"' . ($field['align']===$value?' selected':'') . '>' . $label . '</option>';
            echo '</select></div></div></div>';
        }
        echo '<p class="form-text mb-0 mt-3">Koordinat dihitung dari pojok kiri atas blanko. Uji cetak pada kertas biasa sebelum memakai blanko asli.</p></div></div>';
    } else {
        echo '<div class="card mb-3"><div class="card-body"><h2 class="h6 mb-3">Header dan pengesahan transkrip</h2><div class="row g-3">';
        $settingLabels = ['institution'=>'Nama kampus','address'=>'Alamat kampus','title'=>'Judul transkrip','program_label'=>'Nama program studi','accreditation'=>'Akreditasi prodi','signer_role'=>'Jabatan penandatangan','signer_name'=>'Nama penandatangan','signer_nidn'=>'NIDN penandatangan','issue_place'=>'Tempat penerbitan'];
        foreach ($settings as $key=>$value) {
            if (is_bool($value)) continue;
            echo '<div class="col-md-6"><label class="form-label" for="setting_' . h($key) . '">' . h($settingLabels[$key] ?? $key) . '</label><input class="form-control" id="setting_' . h($key) . '" name="' . h($key) . '" value="' . h($value) . '"></div>';
        }
        echo '</div><div class="form-check mt-3"><input class="form-check-input" type="checkbox" id="show_thesis" name="show_thesis"' . (!empty($settings['show_thesis'])?' checked':'') . '><label class="form-check-label" for="show_thesis">Tampilkan judul skripsi jika ada</label></div></div></div>';
    }
    echo '<div class="template-save-bar"><label class="form-label small mb-1" for="label">Nama versi baru</label><input class="form-control form-control-sm mb-2" id="label" name="label" value="' . h($defaultLabel) . '" required><div class="d-flex flex-wrap align-items-center gap-2"><button class="btn btn-primary" type="submit">Simpan versi baru</button><button class="btn btn-outline-secondary" type="button" id="reset-changes" disabled>Batalkan perubahan</button><span id="dirty-status" class="small text-secondary" role="status">Belum ada perubahan.</span></div><div class="form-text mb-0">Versi lama tetap tersimpan untuk dokumen yang sudah diterbitkan.</div></div></form></div><div class="col-12 col-xl-7 order-1 order-xl-2"><div class="template-preview-panel card"><div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2"><div><strong>Pratinjau langsung</strong><div class="small text-secondary">Data lulusan pada contoh ini fiktif.</div></div><span id="preview-status" class="small text-secondary" role="status">Menyiapkan pratinjau…</span></div><div class="card-body">';
    if ($kind === 'diploma') {
        $sample = sample_template_snapshot($template);
        $values = diploma_field_values($sample);
        echo '<div class="editor-toolbar"><div class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" id="toggle-grid" checked><label class="form-check-label small" for="toggle-grid">Grid 10 mm + magnet</label></div><button class="btn btn-sm btn-outline-secondary" type="button" id="toggle-zoom" aria-pressed="false">Perbesar pratinjau</button><details class="editor-help"><summary aria-label="Bantuan editor" title="Bantuan">?</summary><div class="editor-help-body small"><ul><li>Klik teks atau kotak QR pada kertas, lalu atur posisi, lebar, ukuran huruf, perataan, dan isinya di panel atas kertas.</li><li>Geser dengan mouse untuk memindahkan. Saat grid aktif, teks menempel ke garis 10 mm dan ke tengah halaman.</li><li>Tombol panah menggeser 1 mm; tahan Shift untuk 0,1 mm.</li><li>Bidang kosong dan garis bantu hanya terlihat di editor, tidak ikut tercetak.</li><li>Gunakan Buka PDF saat ini untuk uji cetak sebelum menyimpan.</li></ul></div></details></div><div class="field-inspector" id="field-inspector"><div class="field-inspector-empty" id="inspector-empty">Klik teks atau kotak QR pada kertas untuk mengaturnya di sini.</div><div id="inspector-body" hidden><div class="field-inspector-head"><strong id="inspector-label"></strong><span id="alignment-status" class="small text-secondary" role="status"></span></div><div class="field-inspector-grid"><label>X dari kiri (mm)<input class="form-control form-control-sm" type="number" step="0.1" data-inspect="x"></label><label>Y dari atas (mm)<input class="form-control form-control-sm" type="number" step="0.1" data-inspect="y"></label><label><span id="inspector-width-label">Lebar (mm)</span><input class="form-control form-control-sm" type="number" step="0.1" data-inspect="w"></label><label data-inspect-wrap="pt">Huruf (pt)<input class="form-control form-control-sm" type="number" step="0.5" data-inspect="pt"></label><div data-inspect-wrap="align"><span class="inspector-caption">Perataan</span><div class="btn-group btn-group-sm" role="group" aria-label="Perataan teks"><button class="btn btn-outline-secondary" type="button" data-align="left">Kiri</button><button class="btn btn-outline-secondary" type="button" data-align="center">Tengah</button><button class="btn btn-outline-secondary" type="button" data-align="right">Kanan</button></div></div></div><label class="field-inspector-text" data-inspect-wrap="text">Isi teks<input class="form-control form-control-sm" data-inspect="text"></label><div class="small text-secondary mt-2" data-inspect-wrap="auto">Isi bidang ini diambil otomatis dari data lulusan.</div><div class="small text-secondary mt-2" data-inspect-wrap="qr" id="inspector-qr-note"></div></div></div><p id="qr-preview-note" class="small text-danger mb-2" role="status" hidden></p><div class="preview-stage" id="preview-stage"><div class="diploma-sheet" id="diploma-sheet">' . sheet_grid_svg() . '';
        foreach ($settings['fields'] as $key => $field) {
            $value = trim((string) ($values[$key] ?? ''));
            echo '<div class="canvas-field' . ($value === '' ? ' canvas-empty' : '') . '" data-field="' . h($key) . '" data-empty-label="' . h($field['label']) . '" tabindex="0" role="button" aria-label="Geser ' . h($field['label']) . '">' . h($value === '' ? '[' . $field['label'] . ' belum diisi]' : $value) . '</div>';
        }
        echo '<div class="canvas-qr" id="canvas-qr" tabindex="0" role="button" aria-label="Geser area QR" title="Panduan lokasi QR; drag untuk memindahkan">QR</div></div></div><div class="d-flex flex-wrap gap-2 align-items-center mt-3"><button class="btn btn-sm btn-outline-secondary" type="submit" form="template-form" formaction="' . h(route_url('template-live-preview')) . '" formtarget="_blank" formnovalidate>Buka PDF saat ini</button><span class="small text-secondary">PDF dibuat saat tombol diklik untuk kalibrasi cetak.</span></div>';
    } else {
        echo '<p class="small text-secondary mb-3">Header, identitas, tabel nilai contoh, dan pengesahan mengikuti perubahan formulir secara langsung.</p><div id="pdf-wrap"><iframe id="live-pdf" class="live-pdf" title="Pratinjau template" loading="lazy"></iframe><button class="btn btn-sm btn-outline-secondary" type="submit" form="template-form" formaction="' . h(route_url('template-live-preview')) . '" formtarget="_blank" formnovalidate>Buka PDF saat ini di tab baru</button></div>';
    }
    echo '</div></div></div></div><script src="assets/template-editor.js?v=7" defer></script>';
    page_footer();
}

function verify_page(?array $document): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, private');
    header('X-Robots-Tag: noindex, nofollow');
    page_header('Verifikasi ijazah', '', true);
    echo '<div class="row justify-content-center"><div class="col-lg-7"><div class="small text-secondary mb-2">STIE NUSANTARA MAKASSAR</div><h1 class="h4 mb-4">Verifikasi ijazah</h1>';
    if (!$document) {
        echo '<div class="alert alert-secondary">Data verifikasi tidak tersedia. Periksa kembali QR pada ijazah atau hubungi kampus.</div>';
    } else {
        $snapshot = json_decode($document['snapshot_json'], true, 512, JSON_THROW_ON_ERROR);
        $graduate = $snapshot['graduate'];
        $active = $document['status'] === 'issued';
        echo '<div class="card"><div class="card-body p-4"><div class="mb-3">' . ($active ? '<span class="badge text-bg-success">Tercatat terbit</span>' : '<span class="badge text-bg-danger">Dibatalkan</span>') . '</div><h2 class="h5 mb-3">' . h($graduate['name']) . '</h2><dl class="row mb-0"><dt class="col-sm-4">Program studi</dt><dd class="col-sm-8">' . h(programs()[$graduate['program_code']]['label'] ?? $graduate['program_code']) . '</dd><dt class="col-sm-4">Nomor ijazah</dt><dd class="col-sm-8">' . h($document['document_number']) . '</dd><dt class="col-sm-4">Tanggal terbit</dt><dd class="col-sm-8">' . h(format_date_id($graduate['issue_date'])) . '</dd></dl>';
        if (!$active) echo '<div class="alert alert-danger mb-0">Catatan penerbitan ini telah dibatalkan. Hubungi STIE Nusantara Makassar untuk informasi dokumen pengganti.</div>';
        echo '</div></div>';
    }
    echo '<p class="small text-secondary mt-4">Cocokkan rincian ini dengan ijazah fisik. QR menunjukkan catatan penerbitan kampus; nomor nasional dapat diperiksa melalui <a href="https://pisn.kemdiktisaintek.go.id/" rel="noopener" target="_blank">PISN</a>.</p></div></div>';
    page_footer(true);
}
