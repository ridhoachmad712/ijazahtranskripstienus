<?php
declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

function qr_data_uri(string $token): string
{
    $qr = QrCode::create(verify_url($token))->setSize(320)->setMargin(8);
    $result = (new PngWriter())->write($qr);
    return $result->getDataUri();
}

function render_pdf(string $kind, array $snapshot, ?string $token = null, bool $preview = false): string
{
    $html = $kind === 'diploma'
        ? render_diploma_html($snapshot, $token, $preview)
        : render_transcript_html($snapshot, $preview);
    $pdf = new Dompdf(dompdf_options());
    register_document_fonts($pdf->getFontMetrics());
    $pdf->loadHtml($html, 'UTF-8');
    $pdf->setPaper('A4', $kind === 'diploma' ? 'landscape' : 'portrait');
    $pdf->render();
    return $pdf->output();
}

function dompdf_options(): Options
{
    $storage = (string) app_config()['storage_root'];
    $options = new Options();
    $options->set('isRemoteEnabled', false);
    $options->set('isPhpEnabled', false);
    $options->set('chroot', [APP_ROOT, $storage]);
    $options->set('tempDir', $storage);
    $options->set('fontCache', $storage);
    $options->set('fontDir', $storage);
    return $options;
}

// Cambria milik Microsoft dan tidak boleh disebarkan bersama kode: Admin menaruh berkasnya di storage/fonts.
// Tanpa berkas itu transkrip dicetak dengan Times.
function document_font_files(): array
{
    $dir = rtrim((string) app_config()['storage_root'], '/\\') . '/fonts';
    return [
        'normal' => [$dir . '/cambria.ttf', 'normal', 'normal'],
        'bold' => [$dir . '/cambriab.ttf', 'bold', 'normal'],
        'italic' => [$dir . '/cambriai.ttf', 'normal', 'italic'],
        'bold_italic' => [$dir . '/cambriaz.ttf', 'bold', 'italic'],
    ];
}

function cambria_installed(): bool
{
    return is_file(document_font_files()['normal'][0]);
}

function register_document_fonts(\Dompdf\FontMetrics $metrics): void
{
    if (!cambria_installed()) return;
    $families = $metrics->getFontFamilies();
    foreach (document_font_files() as $variant => [$path, $weight, $style]) {
        // registerFont memproses ulang font setiap kali dipanggil, jadi lewati varian yang sudah terdaftar.
        if (!is_file($path) || isset($families['cambria'][$variant])) continue;
        $metrics->registerFont(['family'=>'Cambria', 'weight'=>$weight, 'style'=>$style], (string) realpath($path));
    }
}

function transcript_font_family(): string
{
    return cambria_installed() ? '"Cambria","Times New Roman",serif' : '"Times New Roman",serif';
}

function sample_template_snapshot(array $template): array
{
    $program = $template['program_code'];
    $sample = ['nim'=>'0012345678','name'=>'Contoh Lulusan','birth_place'=>'Makassar','birth_date'=>'2002-04-18','program_code'=>$program,'graduation_date'=>'2026-08-15','diploma_number'=>'CONTOH-NOMOR-001','transcript_number'=>'CONTOH-TR-001','issue_date'=>'2026-09-01','thesis_title'=>'Contoh judul skripsi','official_credits'=>'6','official_gpa'=>'3.50'];
    $grades = [
        ['course_code'=>'MK001','course_name'=>'Pengantar Akuntansi','credits'=>3,'grade_letter'=>'A','grade_weight'=>4],
        ['course_code'=>'MK002','course_name'=>'Mata Kuliah Kedua','credits'=>3,'grade_letter'=>'B','grade_weight'=>3],
    ];
    if ($template['kind'] === 'transcript') {
        // Transkrip dicontohkan dengan daftar penuh agar kepadatan tabel satu lembar terlihat.
        $grades = sample_transcript_grades($program);
        $summary = grade_summary($grades);
        $sample['official_credits'] = (string) $summary['credits'];
        $sample['official_gpa'] = number_format((float) $summary['gpa'], 2, '.', '');
        $sample['thesis_title'] = 'Contoh judul skripsi yang cukup panjang untuk melihat pemisahan baris pada transkrip';
    }
    return ['graduate'=>$sample,'grades'=>$grades,'template'=>$template,'summary'=>grade_summary($grades)];
}

function sample_transcript_grades(string $program): array
{
    $scale = grade_scale();
    $letters = array_slice(array_keys($scale), 0, 3) ?: ['A'];
    $courses = curriculum_for($program);
    if (!$courses) {
        $names = ['Pendidikan Agama','Pendidikan Pancasila','Bahasa Indonesia','Bahasa Inggris','Pengantar Akuntansi','Pengantar Ekonomi','Pengantar Bisnis','Pengantar Manajemen','Kewirausahaan','Statistik Bisnis','Manajemen Sumber Daya Manusia','Manajemen Keuangan','Manajemen Pemasaran','Sistem Informasi Manajemen','Metode Penelitian'];
        for ($i = 1; $i <= 50; $i++) $courses[] = ['course_code'=>sprintf('MK%03d', $i), 'course_name'=>$names[($i - 1) % count($names)] . ($i > count($names) ? ' ' . intdiv($i - 1, count($names)) + 1 : ''), 'credits'=>$i === 50 ? 6 : 3];
    }
    $grades = [];
    foreach (array_values($courses) as $index => $course) {
        $letter = (string) $letters[$index % count($letters)];
        $grades[] = ['course_code'=>$course['course_code'], 'course_name'=>$course['course_name'], 'credits'=>(float) $course['credits'], 'grade_letter'=>$letter, 'grade_weight'=>$scale[$letter] ?? 4];
    }
    return $grades;
}
function diploma_field_values(array $snapshot): array
{
    $graduate = $snapshot['graduate'];
    $texts = $snapshot['template']['settings']['texts'];
    $program = programs()[$graduate['program_code']] ?? ['suffix'=>''];
    $suffix = $program['suffix'] ? ', ' . $program['suffix'] : '';
    return [
        'number' => 'Nomor Ijazah Nasional : ' . $graduate['diploma_number'],
        'institution_accreditation' => $texts['institution_accreditation'],
        'opening' => $texts['opening'],
        'name' => $graduate['name'] . $suffix,
        'identity' => 'Lahir di ' . $graduate['birth_place'] . ', ' . format_date_id($graduate['birth_date']) . ' | NIM : ' . $graduate['nim'],
        'study' => $texts['study'],
        'program' => $texts['program'],
        'program_accreditation' => $texts['program_accreditation'],
        'confer' => $texts['confer'],
        'degree' => $texts['degree'],
        'rights' => $texts['rights'],
        'issue' => 'Diberikan di ' . (trim((string) ($texts['issue_place'] ?? '')) ?: 'Makassar') . ', ' . format_date_id($graduate['issue_date']),
        'left_role' => $texts['left_role'],
        'right_role' => $texts['right_role'],
        'left_name' => $texts['left_name'],
        'right_name' => $texts['right_name'],
        'left_nidn' => $texts['left_nidn'],
        'right_nidn' => $texts['right_nidn'],
    ];
}

function render_diploma_html(array $snapshot, ?string $token, bool $preview): string
{
    $template = $snapshot['template']['settings'];
    $values = diploma_field_values($snapshot);
    $fieldHtml = '';
    foreach ($template['fields'] as $key => $field) {
        $value = trim((string) ($values[$key] ?? ''));
        if ($value === '') continue;
        $style = sprintf('left:%smm;top:%smm;width:%smm;font-size:%spt;text-align:%s;', (float) $field['x'], (float) $field['y'], (float) $field['w'], (float) $field['pt'], $field['align']);
        $fieldHtml .= '<div class="field" style="' . h($style) . '">' . h($value) . '</div>';
    }
    $qrHtml = '';
    $qr = $template['qr'] ?? ['enabled'=>false];
    if (!$preview && $token && !empty($qr['enabled'])) {
        $style = sprintf('left:%smm;top:%smm;width:%smm;height:%smm;', (float) $qr['x'], (float) $qr['y'], (float) $qr['size'], (float) $qr['size']);
        $qrHtml = '<img alt="QR verifikasi" class="qr" style="' . h($style) . '" src="' . h(qr_data_uri($token)) . '">';
    } elseif ($preview) {
        $style = sprintf('left:%smm;top:%smm;width:%smm;height:%smm;', (float) $qr['x'], (float) $qr['y'], (float) $qr['size'], (float) $qr['size']);
        $qrHtml = '<div class="qr qr-preview" style="' . h($style) . '">PANDUAN QR</div>';
    }
    $draft = $preview ? '<div class="draft">PRATINJAU</div>' : '';
    return '<!doctype html><html><head><meta charset="utf-8"><style>
        @page{size:A4 landscape;margin:0}
        html,body{margin:0;padding:0;width:297mm;height:210mm;background:#fff;color:#080808}
        body{font-family:"Times New Roman",serif}
        .page{position:relative;width:297mm;height:210mm;overflow:hidden}
        .field{position:absolute;line-height:1.08;white-space:normal;overflow-wrap:break-word}
        .qr{position:absolute}
        .qr-preview{box-sizing:border-box;border:.5pt dashed #555;color:#555;font:8pt Arial;display:table;text-align:center;line-height:1}
        .draft{position:absolute;top:92mm;left:70mm;width:157mm;text-align:center;font:32pt Arial;color:#d9d9d9;z-index:-1}
        </style></head><body><div class="page">' . $draft . $fieldHtml . $qrHtml . '</div></body></html>';
}

function transcript_field_values(array $snapshot): array
{
    $graduate = $snapshot['graduate'];
    $texts = $snapshot['template']['settings']['texts'];
    return [
        'title' => $texts['title'],
        'number' => trim((string) $graduate['transcript_number']) !== '' ? 'Nomor ' . $graduate['transcript_number'] : '',
        'program_accreditation' => $texts['program_accreditation'],
        'issue' => (trim((string) $texts['issue_place']) ?: 'Makassar') . ', ' . format_date_id((string) $graduate['issue_date']),
        'signer_role' => $texts['signer_role'],
        'signer_name' => $texts['signer_name'],
        'signer_nidn' => $texts['signer_nidn'],
    ];
}
function format_credits(float $credits): string
{
    return floor($credits) == $credits ? number_format($credits, 0, ',', '.') : rtrim(rtrim(number_format($credits, 2, ',', '.'), '0'), ',');
}

function transcript_identity_html(array $graduate): string
{
    $cell = static fn(string $label, string $value) => '<td><span class="id-label">' . h($label) . '</span><span class="id-value">' . h($value !== '' ? $value : '-') . '</span></td>';
    $program = programs()[$graduate['program_code']]['label'] ?? $graduate['program_code'];
    return '<table class="tr-identity"><tr>' . $cell('Nama', $graduate['name']) . $cell('NIM', $graduate['nim']) . $cell('Program Studi', $program) . '</tr><tr>'
        . $cell('Tempat, Tanggal Lahir', trim($graduate['birth_place'] . ', ' . format_date_id((string) $graduate['birth_date']), ', ')) . $cell('Tanggal Lulus', format_date_id((string) $graduate['graduation_date'])) . $cell('Nomor Ijazah', $graduate['diploma_number']) . '</tr></table>';
}

function transcript_summary_html(array $snapshot): string
{
    $graduate = $snapshot['graduate'];
    $official = has_official_totals($graduate);
    $credits = $official ? (float) $graduate['official_credits'] : (float) $snapshot['summary']['credits'];
    $gpa = $official ? (float) $graduate['official_gpa'] : $snapshot['summary']['gpa'];
    return '<table class="tr-summary"><tr><td><span class="s-label">Jumlah SKS</span> <span class="s-value">' . h(format_credits($credits)) . '</span></td><td><span class="s-label">Indeks Prestasi Kumulatif</span> <span class="s-value">' . h($gpa === null ? '-' : number_format((float) $gpa, 2, ',', '.')) . '</span></td></tr></table>';
}

function transcript_thesis_html(array $snapshot): string
{
    $title = trim((string) $snapshot['graduate']['thesis_title']);
    if (empty($snapshot['template']['settings']['show_thesis']) || $title === '') return '';
    return '<div class="tr-thesis"><span class="t-label">Judul Skripsi</span><span class="t-text">' . h($title) . '</span></div>';
}
// Lebar teks (mm) menurut metrik font Times yang dipakai Dompdf saat mencetak.
function text_width_mm(string $text, float $pt): float
{
    static $metrics = null;
    static $font = null;
    if ($metrics === null) {
        $metrics = (new Dompdf(dompdf_options()))->getFontMetrics();
        register_document_fonts($metrics);
        $font = $metrics->getFont(cambria_installed() ? 'cambria' : 'serif');
    }
    return $metrics->getTextWidth($text, $font, $pt) * 25.4 / 72;
}
function wrapped_line_count(string $text, float $widthMm, float $pt): int
{
    $lines = 1;
    $current = '';
    foreach (preg_split('/\s+/', trim($text)) ?: [] as $word) {
        $candidate = $current === '' ? $word : $current . ' ' . $word;
        if ($current !== '' && text_width_mm($candidate, $pt) > $widthMm) {
            $lines++;
            $current = $word;
        } else {
            $current = $candidate;
        }
    }
    return $lines;
}

// Tinggi tabel nilai (mm) untuk ukuran huruf tertentu, termasuk nama mata kuliah yang terbungkus beberapa baris.
const GRADE_ROW_PADDING_MM = 0.7;

// Tinggi tabel nilai (mm): setiap baris setinggi $row, atau lebih tinggi jika nama mata kuliahnya terbungkus.
function grades_table_height(array $left, array $right, float $widthMm, float $pt, float $row): float
{
    $line = $pt * 25.4 / 72 * 1.15;
    $nameWidth = $widthMm * 0.49 * 0.61 - 1.9;
    $lines = static fn(?array $grade) => $grade ? wrapped_line_count((string) $grade['course_name'], $nameWidth, $pt) : 1;
    $height = max($row, $line + GRADE_ROW_PADDING_MM);
    for ($i = 0, $rows = max(count($left), count($right)); $i < $rows; $i++) {
        $height += max($row, max($lines($left[$i] ?? null), $lines($right[$i] ?? null)) * $line + GRADE_ROW_PADDING_MM);
    }
    return $height;
}

// Jika tabel tidak muat: tinggi baris diturunkan lebih dahulu, baru ukuran huruf. Mengembalikan [huruf pt, tinggi baris mm].
function fit_grades_table(array $left, array $right, array $field): array
{
    $limit = (float) $field['h'] * 0.97;
    $wantedRow = (float) ($field['row'] ?? 0);
    for ($pt = (float) $field['pt']; $pt >= 5; $pt -= 0.25) {
        if (grades_table_height($left, $right, (float) $field['w'], $pt, 0) > $limit) continue;
        if (grades_table_height($left, $right, (float) $field['w'], $pt, $wantedRow) <= $limit) return [$pt, $wantedRow];
        [$low, $high] = [0.0, $wantedRow];
        for ($i = 0; $i < 20; $i++) {
            $middle = ($low + $high) / 2;
            if (grades_table_height($left, $right, (float) $field['w'], $pt, $middle) <= $limit) $low = $middle;
            else $high = $middle;
        }
        return [$pt, floor($low * 10) / 10];
    }
    return [5.0, 0.0];
}
// Mengembalikan [HTML tabel dua kolom, ukuran huruf yang dipakai]; huruf diperkecil agar muat di area yang disediakan.
// Mengembalikan [HTML tabel dua kolom, ukuran huruf, tinggi baris] yang dipakai agar muat di area tabel.
function transcript_grades_html(array $grades, array $field): array
{
    $half = (int) ceil(count($grades) / 2);
    $left = array_slice($grades, 0, $half);
    $right = array_slice($grades, $half);
    [$pt, $row] = fit_grades_table($left, $right, $field);
    $height = $row > 0 ? ' style="height:' . $row . 'mm"' : '';
    $table = static function (array $rows, int $count) use ($height): string {
        $html = '<table class="tr-grades"><thead><tr><th class="g-code"' . $height . '>Kode</th><th class="g-name"' . $height . '>Mata Kuliah</th><th class="g-sks"' . $height . '>SKS</th><th class="g-grade"' . $height . '>Nilai</th></tr></thead><tbody>';
        for ($i = 0; $i < $count; $i++) {
            $grade = $rows[$i] ?? null;
            $class = $i % 2 ? ' class="alt"' : '';
            $html .= $grade
                ? '<tr' . $class . '><td class="g-code"' . $height . '>' . h($grade['course_code']) . '</td><td class="g-name"' . $height . '>' . h($grade['course_name']) . '</td><td class="g-sks"' . $height . '>' . h(format_credits((float) $grade['credits'])) . '</td><td class="g-grade"' . $height . '>' . h($grade['grade_letter']) . '</td></tr>'
                : '<tr' . $class . '><td class="g-code"' . $height . '>&nbsp;</td><td class="g-name"' . $height . '></td><td class="g-sks"' . $height . '></td><td class="g-grade"' . $height . '></td></tr>';
        }
        return $html . '</tbody></table>';
    };
    $html = '<table class="tr-grades-wrap"><tr><td class="g-half">' . $table($left, max(1, $half)) . '</td><td class="g-gap"></td><td class="g-half">' . ($right ? $table($right, $half) : '') . '</td></tr></table>';
    return [$html, $pt, $row];
}
function transcript_block_html(array $snapshot): array
{
    [$grades, $pt, $row] = transcript_grades_html($snapshot['grades'], $snapshot['template']['settings']['fields']['grades']);
    return [
        'identity' => transcript_identity_html($snapshot['graduate']),
        'grades' => $grades,
        'summary' => transcript_summary_html($snapshot),
        'thesis' => transcript_thesis_html($snapshot),
        'grades_pt' => $pt,
        'grades_row' => $row,
    ];
}
const TRANSCRIPT_BLOCK_CSS = '.tr-identity{width:100%;border-collapse:collapse;border-top:.8pt solid #1f2d33;border-bottom:.8pt solid #1f2d33}.tr-identity td{width:33.3%;padding:1.1mm 1.5mm .9mm;vertical-align:top}
.id-label,.s-label,.t-label{font-size:.72em;letter-spacing:.08em;text-transform:uppercase;color:#5b6b71}.id-label{display:block}.id-value{display:block;font-weight:bold;margin-top:.3mm}
.tr-grades-wrap{width:100%;border-collapse:collapse}.tr-grades-wrap td.g-half{width:49%;vertical-align:top;padding:0}.tr-grades-wrap td.g-gap{width:2%;padding:0}
.tr-grades{width:100%;border-collapse:collapse;line-height:1.15;border-top:.8pt solid #1f2d33;border-bottom:.8pt solid #1f2d33}
.tr-grades th{background:#e6ecee;color:#1f2d33;font-size:.78em;letter-spacing:.06em;text-transform:uppercase;font-weight:bold;padding:.3mm .9mm;text-align:left;vertical-align:middle}
.tr-grades td{padding:.3mm .9mm;border-bottom:.3pt solid #d3dadd;vertical-align:middle}.tr-grades tr.alt td{background:#f5f7f8}
.tr-grades .g-code{width:19%;white-space:nowrap}.tr-grades td.g-code{color:#3d4a50}.tr-grades .g-name{width:61%}.tr-grades .g-sks{width:9%;text-align:center}.tr-grades .g-grade{width:11%;text-align:center}.tr-grades td.g-grade{font-weight:bold}
.tr-summary{width:100%;border-collapse:collapse;background:#e6ecee}.tr-summary td{width:50%;padding:1.6mm 3mm;vertical-align:middle}.s-value{font-weight:bold;font-size:1.3em;padding-left:2mm}
.t-label{display:block;margin-bottom:.6mm}.t-text{font-style:italic}';

function render_transcript_html(array $snapshot, bool $preview): string
{
    $template = $snapshot['template']['settings'];
    $values = transcript_field_values($snapshot);
    $blocks = transcript_block_html($snapshot);
    $classes = ['title'=>'title', 'number'=>'muted', 'program_accreditation'=>'muted', 'signer_name'=>'signer'];
    $fieldHtml = '';
    foreach ($template['fields'] as $key => $field) {
        $size = $key === 'grades' ? $blocks['grades_pt'] : (float) $field['pt'];
        $style = sprintf('left:%smm;top:%smm;width:%smm;font-size:%spt;text-align:%s;', (float) $field['x'], (float) $field['y'], (float) $field['w'], $size, $field['align']);
        if (isset($blocks[$key])) {
            if ($blocks[$key] === '') continue;
            $fieldHtml .= '<div class="field block" style="' . h($style . (isset($field['h']) ? sprintf('height:%smm;', (float) $field['h']) : '')) . '">' . $blocks[$key] . '</div>';
            continue;
        }
        $value = trim((string) ($values[$key] ?? ''));
        if ($value === '') continue;
        $fieldHtml .= '<div class="field ' . ($classes[$key] ?? '') . '" style="' . h($style) . '">' . h($value) . '</div>';
    }
    $draft = $preview ? '<div class="draft">PRATINJAU</div>' : '';
    return '<!doctype html><html><head><meta charset="utf-8"><style>
        @page{size:A4 portrait;margin:0}
        html,body{margin:0;padding:0;width:210mm;height:297mm;background:#fff;color:#141c1f}
        body{font-family:' . transcript_font_family() . '}
        .page{position:relative;width:210mm;height:297mm;overflow:hidden}
        .field{position:absolute;line-height:1.2;white-space:normal;overflow-wrap:break-word}
        .field.block{overflow:hidden}
        .title{font-weight:bold;letter-spacing:.15em}
        .muted{color:#5b6b71;letter-spacing:.03em}
        .signer{font-weight:bold}
        .draft{position:absolute;top:140mm;left:30mm;width:150mm;text-align:center;font:32pt Arial;color:#e2e2e2;z-index:-1}
        ' . TRANSCRIPT_BLOCK_CSS . '
        </style></head><body><div class="page">' . $draft . $fieldHtml . '</div></body></html>';
}