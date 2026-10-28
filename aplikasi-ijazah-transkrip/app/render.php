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
    $options = new Options();
    $options->set('isRemoteEnabled', false);
    $options->set('isPhpEnabled', false);
    $options->set('chroot', APP_ROOT);
    $options->set('tempDir', app_config()['storage_root']);
    $options->set('fontCache', app_config()['storage_root']);
    $pdf = new Dompdf($options);
    $pdf->loadHtml($html, 'UTF-8');
    $pdf->setPaper('A4', $kind === 'diploma' ? 'landscape' : 'portrait');
    $pdf->render();
    return $pdf->output();
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
    $settings = $snapshot['template']['settings'];
    $texts = $settings['texts'];
    $official = has_official_totals($graduate);
    $credits = $official ? (float) $graduate['official_credits'] : (float) $snapshot['summary']['credits'];
    $gpa = $official ? (float) $graduate['official_gpa'] : $snapshot['summary']['gpa'];
    return [
        'title' => $texts['title'],
        'number' => trim((string) $graduate['transcript_number']) !== '' ? 'Nomor : ' . $graduate['transcript_number'] : '',
        'program_accreditation' => $texts['program_accreditation'],
        'total_credits' => 'Jumlah SKS : ' . format_credits($credits),
        'gpa' => 'Indeks Prestasi Kumulatif (IPK) : ' . ($gpa === null ? '-' : number_format((float) $gpa, 2, ',', '.')),
        'thesis' => !empty($settings['show_thesis']) && trim((string) $graduate['thesis_title']) !== '' ? 'Judul Skripsi : ' . $graduate['thesis_title'] : '',
        'issue' => (trim((string) $texts['issue_place']) ?: 'Makassar') . ', ' . format_date_id((string) $graduate['issue_date']),
        'left_role' => $texts['left_role'],
        'right_role' => $texts['right_role'],
        'left_name' => $texts['left_name'],
        'right_name' => $texts['right_name'],
        'left_nidn' => $texts['left_nidn'],
        'right_nidn' => $texts['right_nidn'],
    ];
}

function format_credits(float $credits): string
{
    return floor($credits) == $credits ? number_format($credits, 0, ',', '.') : rtrim(rtrim(number_format($credits, 2, ',', '.'), '0'), ',');
}

function transcript_identity_html(array $graduate): string
{
    $cell = static fn(string $label, string $value) => '<td class="id-label">' . h($label) . '</td><td class="id-colon">:</td><td class="id-value">' . h($value) . '</td>';
    $program = programs()[$graduate['program_code']]['label'] ?? $graduate['program_code'];
    $rows = [
        [['Nama', $graduate['name']], ['Program Studi', $program]],
        [['NIM', $graduate['nim']], ['Tanggal Lulus', format_date_id((string) $graduate['graduation_date'])]],
        [['Tempat, Tanggal Lahir', trim($graduate['birth_place'] . ', ' . format_date_id((string) $graduate['birth_date']), ', ')], ['Nomor Ijazah', $graduate['diploma_number']]],
    ];
    $html = '<table class="tr-identity">';
    foreach ($rows as [$left, $right]) $html .= '<tr>' . $cell(...$left) . '<td class="id-gap"></td>' . $cell(...$right) . '</tr>';
    return $html . '</table>';
}

// Lebar teks (mm) menurut metrik font Times yang dipakai Dompdf saat mencetak.
function text_width_mm(string $text, float $pt): float
{
    static $metrics = null;
    static $font = null;
    if ($metrics === null) {
        $options = new Options();
        $options->set('chroot', APP_ROOT);
        $options->set('fontCache', app_config()['storage_root']);
        $metrics = (new Dompdf($options))->getFontMetrics();
        $font = $metrics->getFont('serif');
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
function grades_table_height(array $left, array $right, float $widthMm, float $pt): float
{
    $line = $pt * 25.4 / 72 * 1.15;
    $nameWidth = $widthMm * 0.49 * 0.62 - 1.9;
    $lines = static fn(?array $grade) => $grade ? wrapped_line_count((string) $grade['course_name'], $nameWidth, $pt) : 1;
    $height = $line + 0.5;
    for ($i = 0, $rows = max(count($left), count($right)); $i < $rows; $i++) {
        $height += max($lines($left[$i] ?? null), $lines($right[$i] ?? null)) * $line + 0.5;
    }
    return $height;
}

// Mengembalikan [HTML tabel dua kolom, ukuran huruf yang dipakai]; huruf diperkecil agar muat di area yang disediakan.
function transcript_grades_html(array $grades, array $field): array
{
    $half = (int) ceil(count($grades) / 2);
    $left = array_slice($grades, 0, $half);
    $right = array_slice($grades, $half);
    $pt = (float) $field['pt'];
    // Sisakan 3% ruang agar perbedaan kecil hasil cetak tidak memotong baris terakhir.
    while ($pt > 5 && grades_table_height($left, $right, (float) $field['w'], $pt) > (float) $field['h'] * 0.97) $pt -= 0.25;
    $table = static function (array $rows, int $count): string {
        $html = '<table class="tr-grades"><thead><tr><th class="g-code">Kode</th><th class="g-name">Mata Kuliah</th><th class="g-sks">SKS</th><th class="g-grade">Nilai</th></tr></thead><tbody>';
        for ($i = 0; $i < $count; $i++) {
            $grade = $rows[$i] ?? null;
            $html .= $grade
                ? '<tr><td class="g-code">' . h($grade['course_code']) . '</td><td class="g-name">' . h($grade['course_name']) . '</td><td class="g-sks">' . h(format_credits((float) $grade['credits'])) . '</td><td class="g-grade">' . h($grade['grade_letter']) . '</td></tr>'
                : '<tr><td class="g-code">&nbsp;</td><td class="g-name"></td><td class="g-sks"></td><td class="g-grade"></td></tr>';
        }
        return $html . '</tbody></table>';
    };
    $html = '<table class="tr-grades-wrap"><tr><td class="g-half">' . $table($left, max(1, $half)) . '</td><td class="g-gap"></td><td class="g-half">' . ($right ? $table($right, $half) : '') . '</td></tr></table>';
    return [$html, $pt];
}

function transcript_block_html(array $snapshot): array
{
    $field = $snapshot['template']['settings']['fields']['grades'];
    [$grades, $pt] = transcript_grades_html($snapshot['grades'], $field);
    return ['identity'=>transcript_identity_html($snapshot['graduate']), 'grades'=>$grades, 'grades_pt'=>$pt];
}

const TRANSCRIPT_BLOCK_CSS = '.tr-identity{width:100%;border-collapse:collapse}.tr-identity td{padding:.2mm 0;vertical-align:top}.tr-identity .id-label{width:22%;white-space:nowrap}.tr-identity .id-colon{width:2%}.tr-identity .id-value{width:25%}.tr-identity .id-gap{width:2%}
.tr-grades-wrap{width:100%;border-collapse:collapse}.tr-grades-wrap td.g-half{width:49%;vertical-align:top;padding:0}.tr-grades-wrap td.g-gap{width:2%;padding:0}
.tr-grades{width:100%;border-collapse:collapse;line-height:1.15}.tr-grades th,.tr-grades td{border:.4pt solid #222;padding:.2mm .8mm;vertical-align:top}.tr-grades th{font-weight:bold;text-align:center}
.tr-grades .g-code{width:19%;white-space:nowrap}.tr-grades .g-name{width:62%;text-align:left}.tr-grades .g-sks{width:9%;text-align:center}.tr-grades .g-grade{width:10%;text-align:center}';

function render_transcript_html(array $snapshot, bool $preview): string
{
    $template = $snapshot['template']['settings'];
    $values = transcript_field_values($snapshot);
    $blocks = transcript_block_html($snapshot);
    $fieldHtml = '';
    foreach ($template['fields'] as $key => $field) {
        if (isset($blocks[$key])) {
            $size = $key === 'grades' ? $blocks['grades_pt'] : (float) $field['pt'];
            $style = sprintf('left:%smm;top:%smm;width:%smm;font-size:%spt;text-align:%s;', (float) $field['x'], (float) $field['y'], (float) $field['w'], $size, $field['align']) . (isset($field['h']) ? sprintf('height:%smm;', (float) $field['h']) : '');
            $fieldHtml .= '<div class="field block" style="' . h($style) . '">' . $blocks[$key] . '</div>';
            continue;
        }
        $value = trim((string) ($values[$key] ?? ''));
        if ($value === '') continue;
        $style = sprintf('left:%smm;top:%smm;width:%smm;font-size:%spt;text-align:%s;', (float) $field['x'], (float) $field['y'], (float) $field['w'], (float) $field['pt'], $field['align']);
        $fieldHtml .= '<div class="field' . ($key === 'title' ? ' title' : '') . '" style="' . h($style) . '">' . h($value) . '</div>';
    }
    $draft = $preview ? '<div class="draft">PRATINJAU</div>' : '';
    return '<!doctype html><html><head><meta charset="utf-8"><style>
        @page{size:A4 portrait;margin:0}
        html,body{margin:0;padding:0;width:210mm;height:297mm;background:#fff;color:#080808}
        body{font-family:"Times New Roman",serif}
        .page{position:relative;width:210mm;height:297mm;overflow:hidden}
        .field{position:absolute;line-height:1.15;white-space:normal;overflow-wrap:break-word}
        .field.block{overflow:hidden}
        .title{font-weight:bold;letter-spacing:.3pt}
        .draft{position:absolute;top:140mm;left:30mm;width:150mm;text-align:center;font:32pt Arial;color:#e2e2e2;z-index:-1}
        ' . TRANSCRIPT_BLOCK_CSS . '
        </style></head><body><div class="page">' . $draft . $fieldHtml . '</div></body></html>';
}
