<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require APP_ROOT . '/app/views.php';
ini_set('display_errors', '0');
start_web_session();
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

$route = (string) ($_GET['r'] ?? 'dashboard');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($route === 'verify' && $method === 'GET') {
        $token = (string) ($_GET['t'] ?? '');
        $document = preg_match('/^[a-f0-9]{48}$/', $token) ? find_document_by_token($token) : null;
        verify_page($document);
        exit;
    }
    if ($route === 'login') {
        if ($method === 'POST') {
            check_csrf();
            $username = trim((string) ($_POST['username'] ?? ''));
            if (!login_allowed($username)) throw new DomainException('Terlalu banyak percobaan. Coba lagi beberapa menit kemudian.');
            $stmt = db()->prepare('SELECT * FROM admins WHERE username=?');
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            if (!$admin || !password_verify((string) ($_POST['password'] ?? ''), $admin['password_hash'])) {
                record_login_failure($username);
                throw new DomainException('Nama akun atau kata sandi salah.');
            }
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            clear_login_failure($username);
            redirect_to('dashboard');
        }
        if (current_admin()) redirect_to('dashboard');
        login_page();
        exit;
    }
    $admin = require_admin();
    if ($method === 'POST') check_csrf();

    switch ($route) {
        case 'logout':
            if ($method !== 'POST') break;
            $_SESSION = [];
            session_regenerate_id(true);
            redirect_to('login');
        case 'dashboard':
            dashboard_page();
            break;
        case 'graduates':
            graduates_page(trim((string) ($_GET['q'] ?? '')));
            break;
        case 'grades':
            if ((int) db()->query('SELECT COUNT(*) FROM graduates')->fetchColumn() === 0) {
                flash('warning', 'Masukkan data lulusan lebih dahulu sebelum mengelola nilai.');
                redirect_to('import');
            }
            grades_page(trim((string) ($_GET['q'] ?? '')));
            break;
        case 'graduate-form':
            $id = (int) ($_GET['id'] ?? 0);
            $graduate = $id ? find_graduate($id) : null;
            if ($id && !$graduate) throw new DomainException('Lulusan tidak ditemukan.');
            graduate_form_page($graduate);
            break;
        case 'graduate-save':
            if ($method !== 'POST') break;
            $id = (int) ($_POST['id'] ?? 0);
            $savedId = save_graduate($_POST, $id ?: null);
            flash('success', 'Data lulusan tersimpan.');
            redirect_to('graduate', ['id'=>$savedId]);
        case 'graduate':
            $graduate = find_graduate((int) ($_GET['id'] ?? 0));
            if (!$graduate) throw new DomainException('Lulusan tidak ditemukan.');
            graduate_detail_page($graduate);
            break;
        case 'grade-save':
            if ($method !== 'POST') break;
            $id = (int) ($_POST['graduate_id'] ?? 0);
            save_grade($id, $_POST);
            flash('success', 'Nilai mata kuliah tersimpan.');
            redirect_to('graduate', ['id'=>$id, 'nilai'=>1]);
        case 'grade-delete':
            if ($method !== 'POST') break;
            $id = (int) ($_POST['graduate_id'] ?? 0);
            delete_grade($id, (int) ($_POST['grade_id'] ?? 0));
            flash('success', 'Nilai mata kuliah dihapus.');
            redirect_to('graduate', ['id'=>$id, 'nilai'=>1]);
        case 'import':
            import_page('graduates');
            break;
        case 'import-grades':
            if ((int) db()->query('SELECT COUNT(*) FROM graduates')->fetchColumn() === 0) {
                flash('warning', 'Masukkan data lulusan lebih dahulu sebelum mengimpor nilai.');
                redirect_to('import');
            }
            import_page('grades');
            break;
        case 'import-preview':
            if ($method !== 'POST') break;
            $kind = (string) ($_POST['kind'] ?? '');
            if ($kind === 'grades' && (int) db()->query('SELECT COUNT(*) FROM graduates')->fetchColumn() === 0) {
                flash('warning', 'Masukkan data lulusan lebih dahulu sebelum mengimpor nilai.');
                redirect_to('import');
            }
            $preview = make_import_preview($_FILES['import_file'] ?? [], $kind);
            save_import_preview($preview);
            flash($preview['errors'] ? 'warning' : 'success', $preview['errors'] ? 'Berkas perlu diperbaiki sebelum disimpan.' : 'Data siap diperiksa dan disimpan.');
            redirect_to($kind === 'grades' ? 'import-grades' : 'import');
        case 'import-commit':
            if ($method !== 'POST') break;
            $kind = (string) ($_POST['kind'] ?? '');
            if ($kind === 'grades' && (int) db()->query('SELECT COUNT(*) FROM graduates')->fetchColumn() === 0) {
                flash('warning', 'Masukkan data lulusan lebih dahulu sebelum mengimpor nilai.');
                redirect_to('import');
            }
            $result = commit_import($kind);
            flash('success', $kind === 'grades' ? $result['grade_count'] . ' nilai berhasil diimpor.' : $result['graduate_count'] . ' lulusan berhasil diimpor. Sekarang Anda dapat menginput atau mengimpor nilai.');
            redirect_to($kind === 'grades' ? 'grades' : 'graduates');
        case 'xls-sample':
            if ($method !== 'GET') break;
            $kind = (string) ($_GET['kind'] ?? '');
            import_headers($kind);
            $filename = $kind === 'graduates' ? 'template-lulusan.xls' : 'template-nilai.xls';
            $temporary = tempnam(sys_get_temp_dir(), 'stie-xls-');
            if ($temporary === false) throw new RuntimeException('Template Excel gagal disiapkan.');
            try {
                write_xls_template($kind, $temporary);
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . filesize($temporary));
                readfile($temporary);
            } finally {
                unlink($temporary);
            }
            break;
        case 'csv-sample':
            $kind = (string) ($_GET['kind'] ?? '');
            if ($kind === 'graduates') {
                $filename = 'contoh-lulusan.csv';
                $csv = "nim,nama_lengkap,tempat_lahir,tanggal_lahir,kode_prodi,program_pendidikan,tanggal_lulus,nomor_ijazah_nasional,nomor_transkrip,tanggal_terbit,judul_skripsi,total_sks_resmi,ipk_resmi\r\n0012345678,Contoh Lulusan,Sungguminasa,2002-04-18,AKUNTANSI,S1,2026-08-15,CONTOH-001,CONTOH-TR-001,2026-09-01,Contoh judul skripsi,6,3.50\r\n";
            } elseif ($kind === 'grades') {
                $filename = 'contoh-nilai.csv';
                $csv = "nim,urutan,kode_mk,nama_mk,kelompok_mk,sks,nilai_huruf,bobot_nilai\r\n0012345678,1,AKT001,Pengantar Akuntansi,Inti,3,A,4.00\r\n0012345678,2,AKT002,Akuntansi Keuangan,Inti,3,B,3.00\r\n";
            } else throw new DomainException('Contoh CSV tidak ditemukan.');
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $csv;
            break;
        case 'templates':
            templates_page();
            break;
        case 'template-edit':
            $kind = (string) ($_GET['kind'] ?? '');
            $program = (string) ($_GET['program'] ?? '');
            $template = active_template($kind,$program);
            if (!$template) throw new DomainException('Template tidak ditemukan.');
            template_edit_page($template);
            break;
        case 'template-save':
            if ($method !== 'POST') break;
            $kind = (string) ($_POST['kind'] ?? '');
            $program = (string) ($_POST['program'] ?? '');
            save_template_version($kind,$program,$_POST);
            flash('success', 'Versi template baru aktif.');
            redirect_to('template-edit', ['kind'=>$kind,'program'=>$program]);
        case 'template-background':
            $path = template_background_path((string) ($_GET['program'] ?? ''));
            if ($path === null) {
                http_response_code(404);
                break;
            }
            $mime = array_search(pathinfo($path, PATHINFO_EXTENSION), TEMPLATE_BACKGROUND_TYPES, true);
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($path));
            header('Cache-Control: private, no-cache');
            readfile($path);
            break;
        case 'template-background-upload':
            if ($method !== 'POST') break;
            $program = (string) ($_POST['program'] ?? '');
            $file = $_FILES['background'] ?? [];
            $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
            if (in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) throw new DomainException('Ukuran gambar latar maksimal 10 MB.');
            if ($error !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) throw new DomainException('Pilih gambar blanko yang akan diunggah.');
            $warning = save_template_background($program, (string) $file['tmp_name']);
            flash('success', 'Latar blanko tersimpan. Latar hanya tampil di editor dan tidak ikut dicetak.');
            if ($warning !== null) flash('warning', $warning);
            redirect_to('template-edit', ['kind'=>'diploma','program'=>$program]);
        case 'template-background-delete':
            if ($method !== 'POST') break;
            $program = (string) ($_POST['program'] ?? '');
            delete_template_background($program);
            flash('success', 'Latar blanko dihapus.');
            redirect_to('template-edit', ['kind'=>'diploma','program'=>$program]);
        case 'template-live-preview':
            if ($method !== 'POST') break;
            try {
                $kind = (string) ($_POST['kind'] ?? '');
                $program = (string) ($_POST['program'] ?? '');
                $template = active_template($kind,$program);
                if (!$template) throw new DomainException('Template tidak ditemukan.');
                $template['settings'] = template_settings_from_source($template, $_POST);
                $snapshot = sample_template_snapshot($template);
                if ($kind === 'transcript' && ($_POST['preview_format'] ?? '') === 'html') {
                    header('Content-Type: text/html; charset=utf-8');
                    header('X-Robots-Tag: noindex, nofollow');
                    header('Cache-Control: no-store');
                    echo render_transcript_browser_html($snapshot);
                    break;
                }
                $pdf = render_pdf($kind, $snapshot, null, true);
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="pratinjau-template.pdf"');
                header('Cache-Control: no-store');
                echo $pdf;
            } catch (DomainException $e) {
                http_response_code(422);
                header('Content-Type: text/plain; charset=utf-8');
                echo $e->getMessage();
            }
            break;
        case 'template-preview':
            $kind = (string) ($_GET['kind'] ?? '');
            $program = (string) ($_GET['program'] ?? '');
            $template = active_template($kind,$program);
            if (!$template) throw new DomainException('Template tidak ditemukan.');
            $pdf = render_pdf($kind,sample_template_snapshot($template),null,true);
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="template-contoh-' . $kind . '.pdf"');
            header('Cache-Control: no-store');
            echo $pdf;
            break;
        case 'preview':
            $kind = (string) ($_GET['kind'] ?? '');
            $graduate = find_graduate((int) ($_GET['id'] ?? 0));
            if (!$graduate || !in_array($kind,['diploma','transcript'],true)) throw new DomainException('Pratinjau tidak tersedia.');
            $template = active_template($kind,$graduate['program_code']);
            if (!$template) throw new DomainException('Template belum tersedia.');
            $grades = grades_for((int) $graduate['id']);
            $snapshot = ['graduate'=>$graduate,'grades'=>$grades,'template'=>$template,'summary'=>grade_summary($grades)];
            $pdf = render_pdf($kind,$snapshot,null,true);
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="pratinjau-' . $kind . '.pdf"');
            header('Cache-Control: no-store');
            echo $pdf;
            break;
        case 'issue':
            if ($method !== 'POST') break;
            $id = (int) ($_POST['graduate_id'] ?? 0);
            $kind = (string) ($_POST['kind'] ?? '');
            issue_document($id,$kind);
            flash('success', ($kind==='diploma'?'Ijazah':'Transkrip') . ' diterbitkan dan PDF final diarsipkan.');
            redirect_to('graduate',['id'=>$id]);
        case 'download':
            $document = find_document((int) ($_GET['id'] ?? 0));
            if (!$document || !is_file($document['pdf_path'])) throw new DomainException('PDF tidak ditemukan.');
            if (!hash_equals($document['pdf_sha256'], hash_file('sha256',$document['pdf_path']))) throw new RuntimeException('Integritas PDF arsip tidak cocok.');
            record_reprint((int) $document['id']);
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . ($document['kind']==='diploma'?'ijazah':'transkrip') . '-' . (int) $document['id'] . '.pdf"');
            header('Cache-Control: no-store');
            readfile($document['pdf_path']);
            break;
        case 'revoke':
            if ($method !== 'POST') break;
            $doc = find_document((int) ($_POST['id'] ?? 0));
            if (!$doc) throw new DomainException('Dokumen tidak ditemukan.');
            revoke_document((int) $doc['id'], (string) ($_POST['reason'] ?? ''));
            flash('success', 'Dokumen dibatalkan. Riwayat dan tautan verifikasi tetap tersimpan.');
            redirect_to('graduate',['id'=>$doc['graduate_id']]);
        default:
            http_response_code(404);
            page_header('Halaman tidak ditemukan');
            echo '<p>Halaman tidak ditemukan.</p>';
            page_footer();
    }
} catch (DomainException $e) {
    flash('danger', $e->getMessage());
    $back = match ($route) {
        'login' => 'login',
        'graduate-save' => 'graduate-form',
        'grade-save','grade-delete','issue','revoke' => 'graduate',
        'import-preview','import-commit' => ($_POST['kind'] ?? '') === 'grades' ? 'import-grades' : 'import',
        'xls-sample','csv-sample' => 'import',
        'template-save','template-background-upload','template-background-delete' => 'template-edit',
        default => 'dashboard',
    };
    $query = match ($back) {
        'graduate-form' => ['id'=>(int) ($_POST['id'] ?? 0)],
        'graduate' => ['id'=>(int) ($_POST['graduate_id'] ?? $doc['graduate_id'] ?? 0)] + (in_array($route, ['grade-save','grade-delete'], true) ? ['nilai'=>1] : []),
        'template-edit' => ['kind'=>(string) ($_POST['kind'] ?? ''),'program'=>(string) ($_POST['program'] ?? '')],
        default => [],
    };
    redirect_to($back,$query);
} catch (Throwable $e) {
    error_log((string) $e);
    http_response_code(500);
    echo 'Terjadi kesalahan aplikasi. Periksa log server.';
}
