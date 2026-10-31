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
        'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
    ];
    return '<svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['document']) . '</svg>';
}

function page_header(string $title, string $active = '', bool $public = false): void
{
    $admin = $public ? null : current_admin();
    $items = ['dashboard'=>['Beranda','home'],'graduates'=>['Data lulusan','people'],'import'=>['Impor lulusan','upload']];
    $items['curriculum'] = ['Kurikulum','book'];
    if ($admin && (int) db()->query('SELECT COUNT(*) FROM graduates')->fetchColumn() > 0) $items['grades'] = ['Nilai','document'];
    $items['templates'] = ['Template','template'];
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . h($title) . ' — STIE Nusantara</title><link rel="stylesheet" href="assets/bootstrap/bootstrap.min.css"><link rel="stylesheet" href="assets/app.css?v=18"></head><body' . ($admin ? ' class="has-admin"' : ' class="public-page"') . '>';
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
    $issuedCount = (int) $db->query('SELECT COUNT(*) FROM documents WHERE status=\'issued\'')->fetchColumn();
    page_header('Beranda', 'dashboard');
    echo '<section class="dashboard-hero"><div class="hero-copy"><div class="eyebrow">RUANG KERJA ADMIN</div><h1>Dokumen kelulusan,<br>lebih tertata.</h1><p>Masukkan data lulusan terlebih dahulu, lalu kelola nilai untuk menyiapkan transkrip.</p><div class="d-flex flex-wrap gap-2"><a class="btn btn-primary" href="' . h(route_url('graduate-form')) . '">' . ui_icon('plus') . 'Tambah lulusan</a><a class="btn btn-outline-secondary" href="' . h(route_url('import')) . '">' . ui_icon('upload') . 'Impor lulusan</a></div></div><div class="hero-decoration" aria-hidden="true"><div class="hero-paper hero-paper-back"></div><div class="hero-paper hero-paper-front"><div class="hero-paper-seal">SN</div><div class="hero-paper-line long"></div><div class="hero-paper-line"></div><div class="hero-paper-line short"></div></div></div></section>';
    echo '<div class="row g-3 dashboard-stats"><div class="col-md-4"><div class="stat-card"><div class="stat-icon">' . ui_icon('people') . '</div><div class="stat-number">' . $graduateCount . '</div><div class="stat-label">Lulusan tercatat</div></div></div><div class="col-md-4"><div class="stat-card"><div class="stat-icon">' . ui_icon('template') . '</div><div class="stat-number">' . $gradeCount . '</div><div class="stat-label">Baris nilai tersimpan</div></div></div><div class="col-md-4"><div class="stat-card"><div class="stat-icon">' . ui_icon('document') . '</div><div class="stat-number">' . $issuedCount . '</div><div class="stat-label">Dokumen aktif</div></div></div></div>';
    echo '<div class="row g-3 mt-1"><div class="col-xl-8"><section class="card dashboard-section h-100"><div class="card-body"><div class="section-heading"><div><div class="eyebrow">ALUR KERJA</div><h2 class="h5 mb-0">Mulai dari sini</h2></div></div><a class="workflow-link" href="' . h(route_url('templates')) . '"><span class="workflow-number">01</span><span class="workflow-copy"><strong>Atur template dokumen</strong><small>Sesuaikan teks ijazah, transkrip, dan posisi QR.</small></span>' . ui_icon('arrow') . '</a><a class="workflow-link" href="' . h(route_url('graduates')) . '"><span class="workflow-number">02</span><span class="workflow-copy"><strong>Masukkan data lulusan</strong><small>Tambah satu per satu atau gunakan template Excel lulusan.</small></span>' . ui_icon('arrow') . '</a>' . ($graduateCount > 0 ? '<a class="workflow-link" href="' . h(route_url('grades')) . '"><span class="workflow-number">03</span><span class="workflow-copy"><strong>Isi nilai per mahasiswa</strong><small>Atur kurikulum prodi sekali, lalu isi atau impor nilai huruf tiap mahasiswa.</small></span>' . ui_icon('arrow') . '</a>' : '<div class="workflow-link"><span class="workflow-number">03</span><span class="workflow-copy"><strong>Isi nilai per mahasiswa</strong><small>Menu Nilai muncul setelah data lulusan tersimpan. Kurikulum prodi dapat diatur lebih dulu.</small></span></div>') . '</div></section></div><div class="col-xl-4"><aside class="card dashboard-note h-100"><div class="card-body"><div class="note-icon">' . ui_icon('document') . '</div><h2 class="h5">Siap untuk cetak?</h2><p class="text-secondary">Periksa pratinjau PDF dan cocokkan posisi teks serta QR dengan blanko fisik sebelum menerbitkan ijazah.</p><a class="text-link" href="' . h(route_url('templates')) . '">Buka pengaturan template ' . ui_icon('arrow') . '</a></div></aside></div></div>';
    page_footer();
}

function pagination_html(string $route, array $query, int $page, int $total): string
{
    $pages = (int) ceil($total / LIST_PAGE_SIZE);
    $from = $total ? ($page - 1) * LIST_PAGE_SIZE + 1 : 0;
    $to = min($total, $page * LIST_PAGE_SIZE);
    $html = '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3"><div class="small text-secondary">Menampilkan ' . $from . '–' . $to . ' dari ' . $total . '</div>';
    if ($pages > 1) {
        $html .= '<nav aria-label="Halaman"><ul class="pagination pagination-sm mb-0">';
        $link = static fn(int $target, string $label, bool $disabled = false, bool $active = false) => '<li class="page-item' . ($disabled ? ' disabled' : '') . ($active ? ' active' : '') . '"><a class="page-link" href="' . h(route_url($route, $query + ['page'=>$target])) . '"' . ($active ? ' aria-current="page"' : '') . '>' . $label . '</a></li>';
        $html .= $link(max(1, $page - 1), '‹', $page <= 1);
        foreach (array_unique([1, max(1, $page - 2), $page - 1, $page, $page + 1, min($pages, $page + 2), $pages]) as $target) {
            if ($target >= 1 && $target <= $pages) $html .= $link($target, (string) $target, false, $target === $page);
        }
        $html .= $link(min($pages, $page + 1), '›', $page >= $pages) . '</ul></nav>';
    }
    return $html . '</div>';
}

function graduates_page(string $search, int $page = 1): void
{
    [$rows, $total] = list_graduates($search, $page);
    page_header('Data lulusan', 'graduates');
    echo '<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4"><div><h1 class="h4 mb-1">Data lulusan</h1><p class="text-secondary mb-0">Identitas bersama untuk kedua dokumen.</p></div><a class="btn btn-primary" href="' . h(route_url('graduate-form')) . '">Tambah lulusan</a></div><form class="row g-2 mb-3" method="get"><input type="hidden" name="r" value="graduates"><div class="col-sm-5"><input class="form-control" name="q" value="' . h($search) . '" placeholder="Cari nama atau NIM" aria-label="Cari nama atau NIM"></div><div class="col-auto"><button class="btn btn-outline-secondary">Cari</button></div></form><div class="table-responsive border rounded"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>NIM</th><th>Nama</th><th>Prodi</th><th>Nilai</th><th>Ijazah</th><th>Transkrip</th><th></th></tr></thead><tbody>';
    foreach ($rows as $row) {
        echo '<tr><td>' . h($row['nim']) . '</td><td>' . h($row['name']) . '</td><td>' . h(programs()[$row['program_code']]['label'] ?? $row['program_code']) . '</td><td>' . (int) $row['grade_count'] . ' MK</td><td>' . status_badge($row['diploma_status']) . '</td><td>' . status_badge($row['transcript_status']) . '</td><td><a class="btn btn-sm btn-outline-secondary" href="' . h(route_url('graduate', ['id'=>$row['id']])) . '">Buka</a></td></tr>';
    }
    if (!$rows) echo '<tr><td colspan="7" class="empty-table-cell"><div class="empty-state"><div class="empty-state-icon">' . ui_icon('people') . '</div><strong>' . ($search === '' ? 'Belum ada data lulusan' : 'Lulusan tidak ditemukan') . '</strong><span>' . ($search === '' ? 'Tambahkan satu lulusan atau impor data dari Excel.' : 'Coba kata kunci nama atau NIM yang lain.') . '</span>' . ($search === '' ? '<a class="btn btn-sm btn-primary mt-2" href="' . h(route_url('graduate-form')) . '">Tambah lulusan</a>' : '') . '</div></td></tr>';
    echo '</tbody></table></div>' . pagination_html('graduates', $search === '' ? [] : ['q'=>$search], $page, $total);
    page_footer();
}

function format_number(float $value, int $decimals = 2): string
{
    $text = number_format($value, $decimals, ',', '.');
    return str_contains($text, ',') ? rtrim(rtrim($text, '0'), ',') : $text;
}

// Status kelengkapan nilai satu mahasiswa: [kunci filter, label, kelas badge].
function grade_status(array $row): array
{
    if ((int) $row['transcript_issued']) return ['issued', 'Transkrip terbit', 'text-bg-success'];
    if ((int) $row['curriculum_count'] === 0) return ['no-curriculum', 'Kurikulum belum diatur', 'text-bg-secondary'];
    if ((int) $row['grade_count'] === 0) return ['empty', 'Belum ada nilai', 'text-bg-light border'];
    $summary = ['credits'=>(float) $row['credits'], 'gpa'=>(float) $row['credits'] > 0 ? (float) $row['quality'] / (float) $row['credits'] : null];
    return match (official_totals_match($summary, $row)) {
        true => ['match', 'Cocok dengan data resmi', 'text-bg-success-subtle text-success-emphasis border border-success-subtle'],
        false => ['mismatch', 'Belum cocok', 'text-bg-warning'],
        null => ['no-official', 'SKS/IPK resmi kosong', 'text-bg-light border'],
    };
}

function grades_page(string $search, string $filter = '', int $page = 1): void
{
    $rows = list_grade_overview($search);
    $filters = ['' => 'Semua', 'empty' => 'Belum ada nilai', 'mismatch' => 'Belum cocok', 'match' => 'Cocok', 'issued' => 'Transkrip terbit'];
    if (!array_key_exists($filter, $filters)) $filter = '';
    page_header('Nilai', 'grades');
    echo '<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4"><div><h1 class="h4 mb-1">Nilai mahasiswa</h1><p class="text-secondary mb-0">Pilih mahasiswa, lalu isi atau impor nilai huruf sesuai kurikulum prodinya.</p></div><a class="btn btn-outline-secondary" href="' . h(route_url('curriculum')) . '">' . ui_icon('book') . 'Atur kurikulum</a></div>';
    echo '<form class="row g-2 mb-3" method="get"><input type="hidden" name="r" value="grades"><div class="col-sm-5"><input class="form-control" name="q" value="' . h($search) . '" placeholder="Cari nama atau NIM" aria-label="Cari nama atau NIM"></div><div class="col-sm-3"><select class="form-select" name="status" aria-label="Saring status">';
    foreach ($filters as $key => $label) echo '<option value="' . h($key) . '"' . ($key === $filter ? ' selected' : '') . '>' . h($label) . '</option>';
    echo '</select></div><div class="col-auto"><button class="btn btn-outline-secondary">Tampilkan</button></div></form>';
    echo '<div class="table-responsive border rounded"><table class="table table-hover align-middle mb-0"><thead><tr><th>NIM</th><th>Nama</th><th>Prodi</th><th>Nilai terisi</th><th>SKS</th><th>IPK</th><th>Status</th><th></th></tr></thead><tbody>';
    $rows = array_values(array_filter($rows, static fn($row) => $filter === '' || grade_status($row)[0] === $filter));
    $total = count($rows);
    foreach (array_slice($rows, ($page - 1) * LIST_PAGE_SIZE, LIST_PAGE_SIZE) as $row) {
        [$key, $label, $class] = grade_status($row);
        $credits = (float) $row['credits'];
        echo '<tr><td>' . h($row['nim']) . '</td><td>' . h($row['name']) . '</td><td>' . h(programs()[$row['program_code']]['label'] ?? $row['program_code']) . '</td><td>' . (int) $row['grade_count'] . ' / ' . (int) $row['curriculum_count'] . '</td><td>' . h(format_number($credits)) . '</td><td>' . ($credits > 0 ? h(number_format((float) $row['quality'] / $credits, 2, ',', '.')) : '—') . '</td><td><span class="badge ' . h($class) . '">' . h($label) . '</span></td><td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="' . h(route_url('grade-sheet', ['id'=>$row['id']])) . '">' . ($key === 'issued' ? 'Lihat nilai' : 'Isi nilai') . '</a></td></tr>';
    }
    if (!$total) echo '<tr><td colspan="8" class="text-secondary text-center py-4">Tidak ada mahasiswa yang sesuai.</td></tr>';
    echo '</tbody></table></div>' . pagination_html('grades', array_filter(['q'=>$search, 'status'=>$filter], 'strlen'), $page, $total);
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
        'issue_date'=>['Tanggal penerbitan','date',false],
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
    $match = official_totals_match($summary, $graduate);
    echo '<div class="card mb-3" id="nilai"><div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3"><div><h2 class="h6 mb-1">Mata kuliah dan nilai</h2><div class="small text-secondary">' . count($grades) . ' mata kuliah · ' . h(format_number($summary['credits'])) . ' SKS · IPK hitung ' . ($summary['gpa'] !== null ? h(number_format($summary['gpa'], 2, ',', '.')) : '—') . ($match === null ? ' · SKS/IPK resmi belum diisi' : ($match ? ' · <span class="text-success">cocok dengan data resmi</span>' : ' · <span class="text-danger">belum cocok dengan data resmi</span>')) . '</div></div><a class="btn btn-outline-secondary" href="' . h(route_url('grade-sheet', ['id'=>$id])) . '">' . ($gradesLocked ? 'Lihat nilai' : 'Isi nilai') . '</a></div></div>';
    echo '<div class="card"><div class="card-body"><h2 class="h6 mb-3">Riwayat dokumen</h2><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Jenis</th><th>Nomor</th><th>Status</th><th>Terbit</th><th>Tindakan</th></tr></thead><tbody>';
    foreach ($docs as $doc) {
        echo '<tr><td>' . ($doc['kind']==='diploma'?'Ijazah':'Transkrip') . '</td><td>' . h($doc['document_number']) . '</td><td>' . status_badge($doc['status']) . '</td><td>' . h($doc['issued_at']) . ($doc['kind'] === 'diploma' && $doc['qr_token'] ? '<div class="small text-secondary">' . ((int) $doc['verify_count'] ? 'QR dipindai ' . (int) $doc['verify_count'] . '× · terakhir ' . h($doc['last_verified_at']) : 'QR belum pernah dipindai') . '</div>' : '') . '</td><td><div class="d-flex flex-wrap gap-2"><a class="btn btn-sm btn-outline-secondary" href="' . h(route_url('download',['id'=>$doc['id']])) . '">PDF</a>';
        if ($doc['kind']==='diploma' && $doc['qr_token']) echo '<a class="btn btn-sm btn-outline-secondary" target="_blank" href="' . h(route_url('verify',['t'=>$doc['qr_token']])) . '">Verifikasi</a>';
        if ($doc['status']==='issued') echo '<form class="d-flex gap-1" method="post" action="' . h(route_url('revoke')) . '">' . csrf_field() . '<input type="hidden" name="id" value="' . (int) $doc['id'] . '"><input class="form-control form-control-sm" name="reason" aria-label="Alasan pembatalan" placeholder="Alasan pembatalan" required><button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm(\'Batalkan dokumen terbit ini?\')">Batalkan</button></form>';
        echo '</div></td></tr>';
    }
    if (!$docs) echo '<tr><td colspan="5" class="text-secondary text-center py-3">Belum ada dokumen terbit.</td></tr>';
    echo '</tbody></table></div></div></div>';
    if (!$docs) echo '<div class="card mt-3 border-danger-subtle"><div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2"><div><h2 class="h6 mb-1">Hapus lulusan</h2><div class="small text-secondary">Untuk data yang salah input. Identitas dan seluruh nilai lulusan ini akan dihapus permanen.</div></div><form method="post" action="' . h(route_url('graduate-delete')) . '" onsubmit="return confirm(\'Hapus permanen ' . h(addslashes($graduate['name'])) . ' (NIM ' . h(addslashes($graduate['nim'])) . ') beserta seluruh nilainya?\')">' . csrf_field() . '<input type="hidden" name="id" value="' . $id . '"><button class="btn btn-outline-danger" type="submit">Hapus lulusan</button></form></div></div>';
    page_footer();
}

function issue_button(int $graduateId, string $kind, array $docs): string
{
    foreach ($docs as $doc) if ($doc['kind']===$kind && $doc['status']==='issued') return status_badge('issued');
    return '<form method="post" action="' . h(route_url('issue')) . '" onsubmit="return confirm(\'Data dan pratinjau sudah diperiksa terhadap sumber resmi?\')">' . csrf_field() . '<input type="hidden" name="graduate_id" value="' . $graduateId . '"><input type="hidden" name="kind" value="' . h($kind) . '"><button class="btn btn-sm btn-primary" type="submit">Terbitkan</button></form>';
}

function import_page(string $kind): void
{
    $preview = load_import_preview();
    if (($preview['kind'] ?? '') !== $kind) $preview = null;
    page_header('Impor lulusan', 'import');
    echo '<div class="mb-4"><h1 class="h4 mb-2">Impor data lulusan</h1><p class="text-secondary mb-0">Simpan identitas lulusan terlebih dahulu. Setelah itu nilai diisi per mahasiswa melalui menu Nilai, berdasarkan kurikulum prodi.</p></div>';
    echo '<div class="row g-3"><div class="col-xl-5"><div class="card"><div class="card-body"><h2 class="h6 mb-3">Pilih berkas lulusan</h2><form method="post" action="' . h(route_url('import-preview')) . '" enctype="multipart/form-data">' . csrf_field() . '<input type="hidden" name="kind" value="' . h($kind) . '"><div class="mb-3"><label class="form-label" for="import_file">Berkas lulusan</label><input class="form-control" id="import_file" name="import_file" type="file" accept=".xls,.xlsx,.csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required></div><p class="form-text mb-3">Format .xls, .xlsx, atau .csv; maksimal 5 MB.</p><button class="btn btn-primary" type="submit">Periksa data</button></form><hr><a class="btn btn-sm btn-outline-secondary" href="' . h(route_url('xls-sample',['kind'=>$kind])) . '">Unduh template lulusan (.xls)</a><p class="form-text mb-0 mt-2">Isi sheet Data mulai baris 2 tanpa mengubah judul kolom. Baca sheet Petunjuk untuk format pengisian.</p></div></div></div><div class="col-xl-7"><div class="card"><div class="card-body"><h2 class="h6 mb-3">Pratinjau impor</h2>';
    if (!$preview) {
        echo '<p class="text-secondary mb-0">Belum ada berkas yang diperiksa.</p>';
    } else {
        echo '<div class="mb-3"><strong>' . (int) $preview['graduate_count'] . '</strong><div class="small text-secondary">Lulusan</div></div>';
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

function curriculum_row_html(array $row): string
{
    $credits = $row['credits'] === '' || $row['credits'] === null ? '' : (is_numeric($row['credits']) ? rtrim(rtrim(number_format((float) $row['credits'], 2, '.', ''), '0'), '.') : (string) $row['credits']);
    return '<tr><td class="row-number text-secondary small"></td><td><input class="form-control form-control-sm" name="course_code[]" value="' . h($row['course_code']) . '" aria-label="Kode MK" maxlength="30"></td><td><input class="form-control form-control-sm" name="course_name[]" value="' . h($row['course_name']) . '" aria-label="Nama mata kuliah" maxlength="200"></td><td><input class="form-control form-control-sm" type="number" min="0" max="20" step="0.5" name="credits[]" value="' . h($credits) . '" aria-label="SKS"></td><td class="text-nowrap text-end"><button class="btn btn-sm btn-link px-1" type="button" data-move="-1" aria-label="Naikkan urutan" title="Naikkan">↑</button><button class="btn btn-sm btn-link px-1" type="button" data-move="1" aria-label="Turunkan urutan" title="Turunkan">↓</button><button class="btn btn-sm btn-link text-danger px-1" type="button" data-remove aria-label="Hapus mata kuliah" title="Hapus">✕</button></td></tr>';
}

function curriculum_page(string $program): void
{
    $draft = $_SESSION['curriculum_draft'][$program] ?? null;
    $rows = $draft ? $draft['rows'] : curriculum_for($program);
    $label = programs()[$program]['label'];
    page_header('Kurikulum', 'curriculum');
    echo '<div class="mb-3"><h1 class="h4 mb-1">Kurikulum dan skala nilai</h1><p class="text-secondary mb-0">Tentukan daftar mata kuliah setiap prodi sekali saja. Setelah itu nilai mahasiswa cukup diisi nilai hurufnya.</p></div>';
    echo '<ul class="nav nav-pills mb-3">';
    foreach (programs() as $code => $info) echo '<li class="nav-item"><a class="nav-link' . ($code === $program ? ' active' : '') . '" href="' . h(route_url('curriculum', ['program'=>$code])) . '">' . h($info['label']) . '</a></li>';
    echo '</ul><div class="row g-3"><div class="col-xl-8">';
    if ($draft) {
        echo '<div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2"><div>' . ($draft['file'] !== '' ? 'Daftar dari berkas <strong>' . h($draft['file']) . '</strong> belum disimpan. Periksa lalu tekan <strong>Simpan kurikulum</strong>.' : 'Isian belum tersimpan karena ada kesalahan. Perbaiki lalu simpan lagi.') . '</div><form method="post" action="' . h(route_url('curriculum-discard')) . '">' . csrf_field() . '<input type="hidden" name="program" value="' . h($program) . '"><button class="btn btn-sm btn-outline-secondary" type="submit">Buang dan kembali ke data tersimpan</button></form></div>';
    }
    echo '<form class="card" id="curriculum-form" method="post" action="' . h(route_url('curriculum-save')) . '"' . ($draft ? ' data-draft="1"' : '') . '>' . csrf_field() . '<input type="hidden" name="program" value="' . h($program) . '"><div class="card-body"><div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-2"><h2 class="h6 mb-0">Mata kuliah ' . h($label) . '</h2><div class="small text-secondary"><span id="curriculum-count">0</span> mata kuliah · <span id="curriculum-credits">0</span> SKS</div></div><p class="small text-secondary">Urutan baris menjadi urutan mata kuliah pada transkrip. Gunakan ↑ ↓ untuk mengubah urutan.</p><div class="table-responsive"><table class="table table-sm align-middle curriculum-table mb-2"><thead><tr><th class="col-no">No.</th><th class="col-code">Kode MK</th><th>Nama mata kuliah</th><th class="col-sks">SKS</th><th class="col-actions"><span class="visually-hidden">Aksi</span></th></tr></thead><tbody id="curriculum-rows">';
    foreach ($rows as $row) echo curriculum_row_html($row);
    echo '</tbody></table></div><p class="small text-secondary mb-2" id="curriculum-empty"' . ($rows ? ' hidden' : '') . '>Belum ada mata kuliah. Tambahkan satu per satu atau muat dari berkas Excel.</p><button class="btn btn-sm btn-outline-secondary" type="button" id="add-course">' . ui_icon('plus') . 'Tambah mata kuliah</button></div><div class="card-footer bg-white d-flex flex-wrap align-items-center gap-2 sticky-save"><button class="btn btn-primary" type="submit">Simpan kurikulum</button><span class="small text-secondary" id="curriculum-dirty" role="status">' . ($draft ? 'Belum disimpan.' : 'Belum ada perubahan.') . '</span></div></form><template id="course-row-template">' . curriculum_row_html(['course_code'=>'','course_name'=>'','credits'=>'']) . '</template>';
    echo '</div><div class="col-xl-4">';
    echo '<div class="card mb-3"><div class="card-body"><h2 class="h6">Muat dari Excel</h2><p class="small text-secondary">Unduh template (berisi daftar yang tersimpan sekarang), ubah di Excel, lalu muat kembali. Hasilnya tampil di tabel dulu sebelum disimpan.</p><a class="btn btn-sm btn-outline-secondary mb-3" href="' . h(route_url('curriculum-template', ['program'=>$program])) . '">Unduh template kurikulum (.xls)</a><form method="post" enctype="multipart/form-data" action="' . h(route_url('curriculum-import')) . '" data-leave-check>' . csrf_field() . '<input type="hidden" name="program" value="' . h($program) . '"><label class="form-label small" for="curriculum-file">Berkas kurikulum</label><input class="form-control form-control-sm mb-2" id="curriculum-file" type="file" name="import_file" accept=".xls,.xlsx,.csv" required><button class="btn btn-sm btn-primary" type="submit">Muat ke tabel</button><div class="form-text">Kolom: kode_mk, nama_mk, sks. Format .xls, .xlsx, atau .csv.</div></form></div></div>';
    echo '<form class="card mb-3" method="post" action="' . h(route_url('grade-scale-save')) . '" id="scale-form" data-leave-check>' . csrf_field() . '<input type="hidden" name="program" value="' . h($program) . '"><div class="card-body"><h2 class="h6">Skala nilai</h2><p class="small text-secondary">Berlaku untuk semua prodi. Bobot terisi otomatis dari nilai huruf.</p><table class="table table-sm align-middle mb-2"><thead><tr><th>Huruf</th><th>Bobot</th><th></th></tr></thead><tbody id="scale-rows">';
    foreach (grade_scale() as $letter => $weight) echo '<tr><td><input class="form-control form-control-sm" name="scale_letter[]" value="' . h((string) $letter) . '" maxlength="5" aria-label="Nilai huruf"></td><td><input class="form-control form-control-sm" type="number" min="0" max="4" step="0.01" name="scale_weight[]" value="' . h(rtrim(rtrim(number_format($weight, 2, '.', ''), '0'), '.')) . '" aria-label="Bobot"></td><td class="text-end"><button class="btn btn-sm btn-link text-danger px-1" type="button" data-remove aria-label="Hapus nilai huruf">✕</button></td></tr>';
    echo '</tbody></table><div class="d-flex flex-wrap gap-2"><button class="btn btn-sm btn-outline-secondary" type="button" id="add-scale">Tambah huruf</button><button class="btn btn-sm btn-primary" type="submit">Simpan skala</button></div><div class="form-text">Mengubah skala tidak mengubah nilai yang sudah tersimpan sampai nilai mahasiswa disimpan ulang.</div></div></form>';
    echo '<div class="small text-secondary">Mengubah kurikulum tidak mengubah transkrip yang sudah terbit. Nama dan SKS mata kuliah mahasiswa mengikuti kurikulum terbaru saat nilainya disimpan ulang.</div>';
    echo '</div></div><script src="assets/grades.js?v=1" defer></script>';
    page_footer();
}

function grade_sheet_page(array $graduate): void
{
    $id = (int) $graduate['id'];
    $curriculum = curriculum_for($graduate['program_code']);
    $grades = grades_for($id);
    $codes = array_column($curriculum, 'course_code');
    $saved = array_column($grades, 'grade_letter', 'course_code');
    $draft = $_SESSION['grade_draft'][$id] ?? null;
    $letters = $draft ? array_replace($saved, $draft['letters']) : $saved;
    $outside = array_values(array_filter($grades, static fn($grade) => !in_array($grade['course_code'], $codes, true)));
    $scale = grade_scale();
    $locked = graduate_has_issued_document($id, 'transcript');
    $program = programs()[$graduate['program_code']]['label'] ?? $graduate['program_code'];
    page_header('Nilai ' . $graduate['name'], 'grades');
    echo '<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3"><div><a class="small text-decoration-none" href="' . h(route_url('grades')) . '">← Nilai mahasiswa</a><h1 class="h4 mt-2 mb-1">' . h($graduate['name']) . '</h1><div class="text-secondary">NIM ' . h($graduate['nim']) . ' · ' . h($program) . '</div></div><div class="d-flex flex-wrap gap-2"><a class="btn btn-sm btn-outline-secondary" target="_blank" href="' . h(route_url('preview', ['id'=>$id, 'kind'=>'transcript'])) . '">Pratinjau transkrip</a><a class="btn btn-sm btn-outline-secondary" href="' . h(route_url('graduate', ['id'=>$id])) . '">Detail lulusan</a></div></div>';
    if ($locked) echo '<div class="alert alert-info">Nilai terkunci karena transkrip aktif sudah terbit. Batalkan transkrip di halaman detail lulusan untuk mengubah nilai.</div>';
    if ($draft) echo '<div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2"><div>Nilai dari berkas <strong>' . h($draft['file']) . '</strong> sudah dimuat (' . count($draft['letters']) . ' mata kuliah) tetapi <strong>belum disimpan</strong>. Periksa lalu tekan Simpan nilai.</div><form method="post" action="' . h(route_url('grade-sheet-discard')) . '">' . csrf_field() . '<input type="hidden" name="graduate_id" value="' . $id . '"><button class="btn btn-sm btn-outline-secondary" type="submit">Buang hasil impor</button></form></div>';
    if (!$curriculum) {
        echo '<div class="alert alert-warning">Kurikulum ' . h($program) . ' belum diatur. <a href="' . h(route_url('curriculum', ['program'=>$graduate['program_code']])) . '">Isi daftar mata kuliah</a> lebih dahulu, lalu kembali ke halaman ini.</div>';
        page_footer();
        return;
    }
    $officialCredits = has_official_totals($graduate) ? (float) $graduate['official_credits'] : null;
    $officialGpa = has_official_totals($graduate) ? (float) $graduate['official_gpa'] : null;
    echo '<div class="row g-3"><div class="col-xl-8"><form class="card" id="grade-sheet-form" method="post" action="' . h(route_url('grade-sheet-save')) . '"' . ($draft ? ' data-draft="1"' : '') . ' data-scale="' . h(json_encode($scale, JSON_THROW_ON_ERROR)) . '">' . csrf_field() . '<input type="hidden" name="graduate_id" value="' . $id . '"><div class="card-body"><div class="table-responsive"><table class="table table-sm align-middle grade-sheet-table mb-0"><thead><tr><th class="col-no">No.</th><th class="col-code">Kode</th><th>Mata kuliah</th><th class="col-sks text-center">SKS</th><th class="col-letter">Nilai</th><th class="text-center">Bobot</th><th class="text-center">Mutu</th></tr></thead><tbody>';
    foreach ($curriculum as $index => $course) {
        $current = $letters[$course['course_code']] ?? '';
        $changed = $draft && array_key_exists($course['course_code'], $draft['letters']) && $draft['letters'][$course['course_code']] !== ($saved[$course['course_code']] ?? '');
        echo '<tr data-credits="' . h((string) (float) $course['credits']) . '"' . ($changed ? ' class="grade-changed"' : '') . '><td class="text-secondary small">' . ($index + 1) . '</td><td>' . h($course['course_code']) . '</td><td>' . h($course['course_name']) . '</td><td class="text-center">' . h(format_number((float) $course['credits'])) . '</td><td><input type="hidden" name="code[]" value="' . h($course['course_code']) . '"><select class="form-select form-select-sm grade-letter" name="letter[]" aria-label="Nilai ' . h($course['course_name']) . '"' . ($locked ? ' disabled' : '') . '><option value="">—</option>';
        foreach ($scale as $letter => $weight) echo '<option value="' . h((string) $letter) . '"' . ((string) $letter === $current ? ' selected' : '') . '>' . h((string) $letter) . '</option>';
        if ($current !== '' && !array_key_exists($current, $scale)) echo '<option value="' . h($current) . '" selected>' . h($current) . ' (tidak ada di skala)</option>';
        echo '</select></td><td class="text-center grade-weight">—</td><td class="text-center grade-quality">—</td></tr>';
    }
    echo '</tbody></table></div></div>';
    if (!$locked) echo '<div class="card-footer bg-white d-flex flex-wrap align-items-center gap-2 sticky-save"><button class="btn btn-primary" type="submit">Simpan nilai</button><span class="small text-secondary" id="grade-dirty" role="status">' . ($draft ? 'Hasil impor belum disimpan.' : 'Belum ada perubahan.') . '</span></div>';
    echo '</form>';
    if ($outside) {
        echo '<div class="card mt-3"><div class="card-body"><h2 class="h6">Nilai di luar kurikulum</h2><p class="small text-secondary">Mata kuliah ini tersimpan untuk mahasiswa tetapi tidak ada di kurikulum ' . h($program) . ' saat ini. Nilainya tetap ikut transkrip sampai dihapus.</p><div class="table-responsive"><table class="table table-sm align-middle mb-0"><tbody>';
        foreach ($outside as $grade) {
            echo '<tr><td>' . h($grade['course_code']) . '</td><td>' . h($grade['course_name']) . '</td><td>' . h(format_number((float) $grade['credits'])) . ' SKS</td><td>' . h($grade['grade_letter']) . '</td><td class="text-end">';
            if (!$locked) echo '<form method="post" action="' . h(route_url('grade-delete')) . '" onsubmit="return confirm(\'Hapus nilai mata kuliah ini?\')">' . csrf_field() . '<input type="hidden" name="graduate_id" value="' . $id . '"><input type="hidden" name="grade_id" value="' . (int) $grade['id'] . '"><button class="btn btn-sm btn-link text-danger" type="submit">Hapus</button></form>';
            echo '</td></tr>';
        }
        echo '</tbody></table></div></div></div>';
    }
    echo '</div><div class="col-xl-4"><div class="grade-side">';
    echo '<div class="card mb-3" id="grade-summary" data-official-credits="' . h($officialCredits === null ? '' : (string) $officialCredits) . '" data-official-gpa="' . h($officialGpa === null ? '' : (string) $officialGpa) . '" data-outside-credits="' . h((string) array_sum(array_map(static fn($g) => (float) $g['credits'], $outside))) . '" data-outside-quality="' . h((string) array_sum(array_map(static fn($g) => (float) $g['credits'] * (float) $g['grade_weight'], $outside))) . '"><div class="card-body"><h2 class="h6">Ringkasan</h2><dl class="row small mb-2"><dt class="col-6 fw-normal text-secondary">Mata kuliah dinilai</dt><dd class="col-6 text-end mb-1"><span id="sum-count">0</span> / ' . count($curriculum) . '</dd><dt class="col-6 fw-normal text-secondary">SKS hitung</dt><dd class="col-6 text-end mb-1" id="sum-credits">0</dd><dt class="col-6 fw-normal text-secondary">SKS resmi</dt><dd class="col-6 text-end mb-1">' . ($officialCredits === null ? '<span class="text-secondary">belum diisi</span>' : h(format_number($officialCredits))) . '</dd><dt class="col-6 fw-normal text-secondary">IPK hitung</dt><dd class="col-6 text-end mb-1" id="sum-gpa">—</dd><dt class="col-6 fw-normal text-secondary">IPK resmi</dt><dd class="col-6 text-end mb-1">' . ($officialGpa === null ? '<span class="text-secondary">belum diisi</span>' : h(number_format($officialGpa, 2, ',', '.'))) . '</dd></dl><div id="sum-status" class="small"></div>' . ($officialCredits === null ? '<div class="form-text">Isi total SKS dan IPK resmi di <a href="' . h(route_url('graduate-form', ['id'=>$id])) . '">data lulusan</a> agar dapat dibandingkan.</div>' : '') . '</div></div>';
    if (!$locked) echo '<div class="card"><div class="card-body"><h2 class="h6">Impor nilai mahasiswa ini</h2><p class="small text-secondary">Unduh template berisi mata kuliah kurikulum, isi kolom <strong>nilai_huruf</strong>, lalu muat kembali. Hasilnya tampil di tabel dulu sebelum disimpan.</p><a class="btn btn-sm btn-outline-secondary mb-3" href="' . h(route_url('grade-sheet-template', ['id'=>$id])) . '">Unduh template nilai (.xls)</a><form method="post" enctype="multipart/form-data" action="' . h(route_url('grade-sheet-import')) . '" data-leave-check>' . csrf_field() . '<input type="hidden" name="graduate_id" value="' . $id . '"><label class="form-label small" for="grade-file">Berkas nilai</label><input class="form-control form-control-sm mb-2" id="grade-file" type="file" name="import_file" accept=".xls,.xlsx,.csv" required><button class="btn btn-sm btn-primary" type="submit">Muat ke tabel</button><div class="form-text">Kolom wajib: kode_mk, nilai_huruf. Berkas dengan NIM lain ditolak.</div></form></div></div>';
    echo '</div></div></div><script src="assets/grades.js?v=1" defer></script>';
    page_footer();
}

function templates_page(): void
{
    page_header('Template', 'templates');
    echo '<h1 class="h4 mb-2">Template dokumen</h1><p class="text-secondary mb-4">Satu template untuk setiap jenis dokumen, dipakai semua program studi. Teks yang berbeda per prodi (misalnya gelar dan akreditasi) diisi di dalam template yang sama. Perubahan disimpan sebagai versi baru; dokumen yang sudah terbit tetap memakai versi lama.</p><div class="table-responsive border rounded"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Dokumen</th><th>Berlaku untuk</th><th>Versi</th><th>Nama versi</th><th></th></tr></thead><tbody>';
    $labels = implode(', ', array_column(programs(), 'label'));
    foreach (['diploma','transcript'] as $kind) {
        $template = active_template($kind);
        echo '<tr><td>' . ($kind === 'diploma' ? 'Ijazah' : 'Transkrip') . '</td><td>' . h($labels) . '</td><td>' . (int) $template['version'] . '</td><td>' . h($template['label']) . '</td><td><a class="btn btn-sm btn-outline-secondary" href="' . h(route_url('template-edit', ['kind'=>$kind])) . '">Atur template</a></td></tr>';
    }
    echo '</tbody></table></div>';
    page_footer();
}
// Grid editor digambar sebagai SVG dalam satuan mm agar garisnya tetap tajam saat kertas diperkecil.
function sheet_grid_svg(float $width = 297, float $height = 210): string
{
    $lines = '';
    for ($x = 10; $x < $width; $x += 10) $lines .= '<line class="' . ($x % 50 === 0 ? 'grid-major' : 'grid-minor') . '" x1="' . $x . '" y1="0" x2="' . $x . '" y2="' . $height . '"/>';
    for ($y = 10; $y < $height; $y += 10) $lines .= '<line class="' . ($y % 50 === 0 ? 'grid-major' : 'grid-minor') . '" x1="0" y1="' . $y . '" x2="' . $width . '" y2="' . $y . '"/>';
    $lines .= '<line class="grid-center grid-center-x" x1="' . ($width / 2) . '" y1="0" x2="' . ($width / 2) . '" y2="' . $height . '"/><line class="grid-center" x1="0" y1="' . ($height / 2) . '" x2="' . $width . '" y2="' . ($height / 2) . '"/>';
    return '<svg class="sheet-grid" viewBox="0 0 ' . $width . ' ' . $height . '" preserveAspectRatio="none" aria-hidden="true">' . $lines . '</svg>';
}
function template_edit_page(array $template, string $previewProgram): void
{
    $kind = $template['kind'];
    $isDiploma = $kind === 'diploma';
    $settings = $template['settings'];
    $programKeys = program_text_keys($kind);
    $page = page_size($kind);
    $defaultLabel = 'Revisi ' . format_date_id(date('Y-m-d')) . ' ' . date('H.i');
    $textLabels = ['title'=>'Judul transkrip','institution_accreditation'=>'Akreditasi institusi','opening'=>'Kalimat pembuka','study'=>'Kalimat studi','program'=>'Program studi','program_accreditation'=>'Akreditasi prodi','confer'=>'Kalimat pemberian gelar','degree'=>'Gelar','rights'=>'Hak dan kewajiban','left_role'=>'Jabatan pejabat kiri','right_role'=>'Jabatan pejabat kanan','left_name'=>'Nama pejabat kiri','right_name'=>'Nama pejabat kanan','left_nidn'=>'NIDN pejabat kiri','right_nidn'=>'NIDN pejabat kanan','issue_place'=>'Tempat penerbitan','signer_role'=>'Jabatan penandatangan','signer_name'=>'Nama penandatangan','signer_nidn'=>'NIDN penandatangan'];
    $positionGroups = $isDiploma ? [
        'Nomor dan identitas' => ['number', 'name', 'identity'],
        'Isi ijazah' => ['institution_accreditation', 'opening', 'study', 'program', 'program_accreditation', 'confer', 'degree', 'rights'],
        'Penerbitan dan pejabat' => ['issue', 'left_role', 'right_role', 'left_name', 'right_name', 'left_nidn', 'right_nidn'],
    ] : [
        'Judul dan identitas' => ['title', 'number', 'identity', 'program_accreditation'],
        'Nilai' => ['grades', 'summary', 'thesis'],
        'Pengesahan' => ['issue', 'signer_role', 'signer_name', 'signer_nidn'],
    ];
    page_header('Atur template', 'templates');
    echo '<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4"><div><a class="small text-decoration-none" href="' . h(route_url('templates')) . '">← Template</a><h1 class="h4 mt-2 mb-1">' . ($isDiploma ? 'Ijazah' : 'Transkrip') . ' · Semua program studi</h1><p class="text-secondary mb-0">Satu template untuk semua prodi. Edit sambil melihat hasil; perubahan berlaku setelah disimpan sebagai versi baru.</p></div><span class="badge text-bg-light border">Versi aktif ' . (int) $template['version'] . '</span></div><div class="template-editor row g-3"><div class="col-12 col-xl-5 order-2 order-xl-1"><form id="template-form" data-kind="' . h($kind) . '" data-program-keys="' . h(implode(',', $programKeys)) . '" data-preview-program="' . h($previewProgram) . '" method="post" action="' . h(route_url('template-save')) . '">' . csrf_field() . '<input type="hidden" name="kind" value="' . h($kind) . '"><input type="hidden" name="program" value="' . h($previewProgram) . '">';
    echo '<div class="card mb-3"><div class="card-body"><h2 class="h6">Teks per program studi</h2><p class="small text-secondary">Hanya bagian ini yang berbeda antarprodi. Pratinjau menampilkan prodi yang dipilih di atas kertas.</p>';
    foreach (programs() as $code => $info) {
        echo '<fieldset class="program-texts' . ($code === $previewProgram ? ' is-preview' : '') . '"><legend>' . h($info['label']) . ($code === $previewProgram ? ' <span class="badge text-bg-light border">sedang dipratinjau</span>' : '') . '</legend><div class="row g-2">';
        foreach ($settings['program_texts'][$code] as $key => $value) echo '<div class="col-md-6"><label class="form-label small" for="program_text_' . h($code . '_' . $key) . '">' . h($textLabels[$key] ?? $key) . '</label><input class="form-control" id="program_text_' . h($code . '_' . $key) . '" name="program_text[' . h($code) . '][' . h($key) . ']" value="' . h($value) . '"></div>';
        echo '</div></fieldset>';
    }
    echo '</div></div>';
    echo '<div class="card mb-3"><div class="card-body"><h2 class="h6">Redaksi dan pejabat</h2><p class="small text-secondary">Berlaku untuk semua program studi.</p><div class="row g-3">';
    foreach ($settings['texts'] as $key => $value) echo '<div class="col-md-6"><label class="form-label small" for="text_' . h($key) . '">' . h($textLabels[$key] ?? $key) . '</label><input class="form-control" id="text_' . h($key) . '" name="text[' . h($key) . ']" value="' . h($value) . '"></div>';
    echo '</div>';
    if (!$isDiploma) echo '<div class="form-check mt-3"><input class="form-check-input" type="checkbox" id="show_thesis" name="show_thesis"' . (!empty($settings['show_thesis']) ? ' checked' : '') . '><label class="form-check-label" for="show_thesis">Tampilkan judul skripsi jika ada</label></div>';
    echo '</div></div>';
    if ($isDiploma) {
        echo '<div class="card mb-3" id="qr-placement"><div class="card-body"><h2 class="h6">Lokasi QR verifikasi</h2><p class="small text-secondary">Kotak panduan QR selalu terlihat pada editor. Geser kotaknya di kertas atau isi koordinat dan ukuran di bawah.</p><div class="row g-2">';
        foreach (['x'=>'X (mm)','y'=>'Y (mm)','size'=>'Ukuran (mm)'] as $part => $label) echo '<div class="col-4"><label class="form-label small" for="qr_' . $part . '">' . $label . '</label><input class="form-control form-control-sm" id="qr_' . $part . '" type="number" min="0" step="0.1" name="qr[' . $part . ']" value="' . h($settings['qr'][$part]) . '" required></div>';
        echo '</div><div class="form-check mt-3"><input class="form-check-input" type="checkbox" id="qr_enabled" name="qr_enabled"' . (!empty($settings['qr']['enabled']) ? ' checked' : '') . '><label class="form-check-label" for="qr_enabled">Cetak QR pada ijazah final</label></div><div class="form-text">Biarkan tidak dicentang sampai posisi QR disetujui setelah uji blanko fisik.</div></div></div>';
    }
    echo '<div class="card mb-3" id="text-position-settings"><div class="card-body"><h2 class="h6 mb-2">Posisi bagian pada blanko</h2><p class="small text-secondary mb-3">Cara tercepat: klik bagian pada pratinjau lalu atur di panel atas kertas. Daftar ini membantu memilih bidang yang sulit diklik, misalnya bidang kosong.</p>';
    $firstField = array_key_first($settings['fields']);
    $listedFields = [];
    echo '<label class="form-label" for="position-field-select">Bidang</label><select class="form-select mb-3" id="position-field-select">';
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
        echo '<div class="position-field-panel" data-field-panel="' . h($key) . '"' . ($key === $firstField ? '' : ' hidden') . '><div class="position-field-heading"><strong>' . h($field['label']) . '</strong><span>' . h($page['label']) . '</span></div><div class="row g-3">';
        $parts = ['x'=>'Posisi X dari kiri (mm)','y'=>'Posisi Y dari atas (mm)','w'=>'Lebar area (mm)','pt'=>'Ukuran huruf (pt)'] + (isset($field['h']) ? ['h'=>'Tinggi area maksimal (mm)'] : []) + (isset($field['row']) ? ['row'=>'Tinggi baris (mm)'] : []);
        foreach ($parts as $part => $label) echo '<div class="col-6"><label class="form-label" for="position_' . h($key) . '_' . $part . '">' . $label . '</label><input class="form-control position-input" id="position_' . h($key) . '_' . $part . '" type="number" step="0.1" name="field[' . h($key) . '][' . $part . ']" value="' . h($field[$part]) . '" required></div>';
        echo '<div class="col-12"><label class="form-label" for="position_' . h($key) . '_align">Perataan teks</label><select class="form-select" id="position_' . h($key) . '_align" name="field[' . h($key) . '][align]">';
        foreach (['left'=>'Kiri','center'=>'Tengah','right'=>'Kanan'] as $value => $label) echo '<option value="' . $value . '"' . ($field['align'] === $value ? ' selected' : '') . '>' . $label . '</option>';
        echo '</select></div></div></div>';
    }
    echo '<p class="form-text mb-0 mt-3">Koordinat dihitung dari pojok kiri atas blanko. Uji cetak pada kertas biasa sebelum memakai blanko asli.</p></div></div>';
    echo '<div class="template-save-bar"><label class="form-label small mb-1" for="label">Nama versi baru</label><input class="form-control form-control-sm mb-2" id="label" name="label" value="' . h($defaultLabel) . '" required><div class="d-flex flex-wrap align-items-center gap-2"><button class="btn btn-primary" type="submit">Simpan versi baru</button><button class="btn btn-outline-secondary" type="button" id="reset-changes" disabled>Batalkan perubahan</button><span id="dirty-status" class="small text-secondary" role="status">Belum ada perubahan.</span></div><div class="form-text mb-0">Versi lama tetap tersimpan untuk dokumen yang sudah diterbitkan.</div></div></form></div>';

    $sample = sample_template_snapshot(template_for_program($template, $previewProgram));
    $values = $isDiploma ? diploma_field_values($sample) : transcript_field_values($sample);
    $blocks = $isDiploma ? [] : transcript_block_html($sample);
    $backgroundPath = template_background_path($kind);
    $backgroundHidden = '<input type="hidden" name="kind" value="' . h($kind) . '"><input type="hidden" name="program" value="' . h($previewProgram) . '">';
    $backgroundTools = '<div class="background-tools"><form id="background-form" method="post" enctype="multipart/form-data" action="' . h(route_url('template-background-upload')) . '">' . csrf_field() . $backgroundHidden . '<input class="visually-hidden" type="file" id="background-file" name="background" accept="image/jpeg,image/png,image/webp" tabindex="-1"></form>';
    if ($backgroundPath !== null) {
        $backgroundTools .= '<div class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" id="toggle-background" checked><label class="form-check-label small" for="toggle-background">Latar blanko</label></div><label class="background-opacity small" for="background-opacity">Kepekatan <input class="form-range" type="range" id="background-opacity" min="10" max="100" step="5" value="60"><span id="background-opacity-value">60%</span></label><button class="btn btn-sm btn-outline-secondary" type="button" data-background-pick>Ganti latar</button><form id="background-delete-form" method="post" action="' . h(route_url('template-background-delete')) . '">' . csrf_field() . $backgroundHidden . '<button class="btn btn-sm btn-outline-danger" type="submit">Hapus latar</button></form>';
    } else {
        $backgroundTools .= '<button class="btn btn-sm btn-outline-secondary" type="button" data-background-pick>Unggah latar blanko</button><span class="small text-secondary">Pindaian/foto blanko kosong, ' . ($isDiploma ? 'lanskap' : 'potret') . ', JPG/PNG/WebP, maks. 10 MB. Hanya tampil di editor.</span>';
    }
    $backgroundTools .= '</div>';
    $backgroundImage = $backgroundPath !== null ? '<img class="sheet-background" id="sheet-background" src="' . h(route_url('template-background', ['kind'=>$kind, 'v'=>filemtime($backgroundPath)])) . '" alt="" aria-hidden="true">' : '';
    $help = ['Klik ' . ($isDiploma ? 'teks atau kotak QR' : 'teks, blok identitas, atau tabel nilai') . ' pada kertas, lalu atur posisi, lebar, ukuran huruf, perataan, dan isinya di panel atas kertas.',
        'Geser dengan mouse untuk memindahkan. Saat grid aktif, bagian yang digeser menempel ke garis 10 mm dan ke tengah halaman.',
        'Tombol panah menggeser 1 mm; tahan Shift untuk 0,1 mm.'];
    if (!$isDiploma) $help[] = 'Tabel nilai dibagi dua kolom sama banyak. Atur tinggi baris sesuai selera; jika tabel tidak muat di tinggi area, tinggi baris lalu ukuran huruf diperkecil otomatis agar transkrip tetap satu lembar.';
    if (!$isDiploma && !cambria_installed()) $help[] = 'Font Cambria belum dipasang di server (storage/fonts), sehingga PDF memakai Times. Lihat README untuk cara memasangnya.';
    $help[] = 'Unggah pindaian blanko kosong sebagai latar untuk mencocokkan posisi. Latar, bidang kosong, dan garis bantu hanya terlihat di editor, tidak ikut tercetak.';
    $help[] = 'Gunakan Buka PDF saat ini untuk uji cetak sebelum menyimpan.';
    echo '<div class="col-12 col-xl-7 order-1 order-xl-2"><div class="template-preview-panel card"><div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2"><div><strong>Pratinjau langsung</strong><div class="small text-secondary">Data lulusan pada contoh ini fiktif.</div></div><span id="preview-status" class="small text-secondary" role="status"></span></div><div class="card-body">';
    echo '<nav class="preview-programs mb-3" aria-label="Prodi pada pratinjau"><span class="small text-secondary">Pratinjau sebagai:</span>';
    foreach (programs() as $code => $info) echo '<a class="btn btn-sm ' . ($code === $previewProgram ? 'btn-secondary' : 'btn-outline-secondary') . '" href="' . h(route_url('template-edit', ['kind'=>$kind, 'program'=>$code])) . '"' . ($code === $previewProgram ? ' aria-current="true"' : '') . '>' . h($info['label']) . '</a>';
    echo '</nav>';
    echo '<div class="editor-toolbar"><div class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" id="toggle-grid" checked><label class="form-check-label small" for="toggle-grid">Grid 10 mm + magnet</label></div><button class="btn btn-sm btn-outline-secondary" type="button" id="toggle-zoom" aria-pressed="false">Perbesar pratinjau</button><details class="editor-help"><summary aria-label="Bantuan editor" title="Bantuan">?</summary><div class="editor-help-body small"><ul><li>' . implode('</li><li>', array_map('h', $help)) . '</li></ul></div></details></div>' . $backgroundTools;
    echo '<div class="field-inspector" id="field-inspector"><div class="field-inspector-empty" id="inspector-empty">Klik bagian mana pun pada kertas untuk mengaturnya di sini.</div><div id="inspector-body" hidden><div class="field-inspector-head"><strong id="inspector-label"></strong><span id="alignment-status" class="small text-secondary" role="status"></span></div><div class="field-inspector-grid"><label>X dari kiri (mm)<input class="form-control form-control-sm" type="number" step="0.1" data-inspect="x"></label><label>Y dari atas (mm)<input class="form-control form-control-sm" type="number" step="0.1" data-inspect="y"></label><label><span id="inspector-width-label">Lebar (mm)</span><input class="form-control form-control-sm" type="number" step="0.1" data-inspect="w"></label><label data-inspect-wrap="pt">Huruf (pt)<input class="form-control form-control-sm" type="number" step="0.5" data-inspect="pt"></label><div data-inspect-wrap="align"><span class="inspector-caption">Perataan</span><div class="btn-group btn-group-sm" role="group" aria-label="Perataan teks"><button class="btn btn-outline-secondary" type="button" data-align="left">Kiri</button><button class="btn btn-outline-secondary" type="button" data-align="center">Tengah</button><button class="btn btn-outline-secondary" type="button" data-align="right">Kanan</button></div></div></div><div class="field-inspector-grid mt-2" data-inspect-wrap="h"><label>Tinggi area maksimal (mm)<input class="form-control form-control-sm" type="number" step="0.5" data-inspect="h"></label><label data-inspect-wrap="row">Tinggi baris (mm)<input class="form-control form-control-sm" type="number" step="0.1" min="3" max="20" data-inspect="row"></label></div><label class="field-inspector-text" data-inspect-wrap="text">Isi teks<input class="form-control form-control-sm" data-inspect="text"></label><div class="small text-secondary mt-2" data-inspect-wrap="auto">Isi bidang ini diambil otomatis dari data lulusan.</div><div class="small text-secondary mt-2" data-inspect-wrap="fit" id="inspector-fit-note"></div><div class="small text-secondary mt-2" data-inspect-wrap="qr" id="inspector-qr-note"></div></div></div>';
    if ($isDiploma) echo '<p id="qr-preview-note" class="small text-danger mb-2" role="status" hidden></p>';
    echo '<div class="preview-stage" id="preview-stage"><div class="diploma-sheet sheet-' . h($kind) . '" id="diploma-sheet" data-page-w="' . $page['w'] . '" data-page-h="' . $page['h'] . '" style="width:' . $page['w'] . 'mm;height:' . $page['h'] . 'mm' . ($isDiploma ? '' : ';font-family:' . h(transcript_font_family())) . '">' . $backgroundImage . sheet_grid_svg($page['w'], $page['h']);
    foreach ($settings['fields'] as $key => $field) {
        $common = ' data-field="' . h($key) . '" data-empty-label="' . h($field['label']) . '" tabindex="0" role="button" aria-label="Geser ' . h($field['label']) . '"';
        if (isset($blocks[$key])) {
            echo '<div class="canvas-field canvas-block"' . $common . ($key === 'grades' ? ' data-fit="1"' : '') . '>' . $blocks[$key] . '</div>';
            continue;
        }
        $value = trim((string) ($values[$key] ?? ''));
        $style = $isDiploma ? '' : ['title'=>' canvas-title', 'number'=>' canvas-muted', 'program_accreditation'=>' canvas-muted', 'signer_name'=>' canvas-signer'][$key] ?? '';
        echo '<div class="canvas-field' . ($value === '' ? ' canvas-empty' : '') . $style . '"' . $common . '>' . h($value === '' ? '[' . $field['label'] . ' belum diisi]' : $value) . '</div>';
    }
    if ($isDiploma) echo '<div class="canvas-qr" id="canvas-qr" tabindex="0" role="button" aria-label="Geser area QR" title="Panduan lokasi QR; drag untuk memindahkan">QR</div>';
    echo '</div></div><div class="d-flex flex-wrap gap-2 align-items-center mt-3"><button class="btn btn-sm btn-outline-secondary" type="submit" form="template-form" formaction="' . h(route_url('template-live-preview')) . '" formtarget="_blank" formnovalidate>Buka PDF saat ini</button><span class="small text-secondary">PDF dibuat saat tombol diklik untuk kalibrasi cetak.</span></div>';
    echo '</div></div></div></div><style>' . TRANSCRIPT_BLOCK_CSS . '</style><script src="assets/template-editor.js?v=12" defer></script>';
    page_footer();
}
function verify_page(?array $document): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, private');
    header('X-Robots-Tag: noindex, nofollow');
    page_header('Verifikasi ijazah', '', true);
    echo '<div class="verify-shell"><div class="verify-brand"><span class="brand-mark">SN</span><div><strong>STIE Nusantara Makassar</strong><span>Layanan verifikasi ijazah</span></div></div>';
    if (!$document) {
        echo '<section class="verify-card"><div class="verify-status verify-status-unknown"><span class="verify-icon" aria-hidden="true">?</span><div><h1>Data tidak ditemukan</h1><p>Kode QR ini tidak terdaftar sebagai ijazah STIE Nusantara Makassar.</p></div></div><div class="verify-body"><p class="mb-0">Pastikan QR dipindai langsung dari ijazah asli. Jika data tetap tidak ditemukan, konfirmasikan keaslian dokumen kepada kampus.</p></div></section>';
    } else {
        $snapshot = json_decode($document['snapshot_json'], true, 512, JSON_THROW_ON_ERROR);
        $graduate = $snapshot['graduate'];
        $active = $document['status'] === 'issued';
        $program = programs()[$graduate['program_code']] ?? null;
        $degree = trim((string) ($snapshot['template']['settings']['texts']['degree'] ?? ($program['degree'] ?? '')));
        echo '<section class="verify-card">';
        if ($active) {
            echo '<div class="verify-status verify-status-valid"><span class="verify-icon" aria-hidden="true">✓</span><div><h1>Ijazah tercatat sah</h1><p>Ijazah ini tercatat diterbitkan oleh STIE Nusantara Makassar.</p></div></div>';
        } else {
            $replacement = replacement_document((int) $document['id']);
            echo '<div class="verify-status verify-status-revoked"><span class="verify-icon" aria-hidden="true">✕</span><div><h1>Ijazah ini telah dibatalkan</h1><p>Dibatalkan pada ' . h(format_date_id(substr((string) $document['revoked_at'], 0, 10))) . ' dan tidak berlaku lagi.' . ($replacement ? ' Telah diganti dengan ijazah nomor <strong>' . h($replacement['document_number']) . '</strong>.' : '') . '</p></div></div>';
        }
        $rows = [
            'Nama lulusan' => $graduate['name'],
            'NIM' => mask_nim((string) $graduate['nim']),
            'Program studi' => $program['label'] ?? $graduate['program_code'],
            'Gelar' => $degree,
            'Tanggal lulus' => format_date_id((string) ($graduate['graduation_date'] ?? '')),
            'Nomor ijazah nasional' => $document['document_number'],
            'Tanggal terbit' => format_date_id((string) $graduate['issue_date']),
        ];
        echo '<dl class="verify-body verify-details">';
        foreach ($rows as $label => $value) {
            if (trim((string) $value) === '') continue;
            echo '<div><dt>' . h($label) . '</dt><dd' . ($label === 'Nama lulusan' ? ' class="verify-name"' : '') . '>' . h($value) . '</dd></div>';
        }
        echo '</dl></section>';
        echo '<section class="verify-guide"><h2>Cara memeriksa</h2><p>Cocokkan nama, nomor ijazah, dan tanggal di atas dengan ijazah fisik. NIM sengaja disamarkan sebagian. Jika ada perbedaan, konfirmasikan kepada kampus sebelum menerima dokumen. Nomor ijazah nasional juga dapat diperiksa melalui <a href="https://pisn.kemdiktisaintek.go.id/" rel="noopener" target="_blank">PISN</a>.</p></section>';
    }
    $contact = trim((string) (app_config()['verify_contact'] ?? ''));
    if ($contact !== '') echo '<section class="verify-guide"><h2>Kontak kampus</h2><p>' . h($contact) . '</p></section>';
    $zone = (string) app_config()['timezone'] === 'Asia/Makassar' ? ' WITA' : '';
    echo '<p class="verify-footer">Diperiksa pada ' . h(format_date_id(date('Y-m-d')) . ' pukul ' . date('H.i') . $zone) . '</p></div>';
    page_footer(true);
}
