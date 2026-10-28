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
    if ($kind === 'transcript') {
        $font = $pdf->getFontMetrics()->getFont('DejaVu Sans');
        $pdf->getCanvas()->page_text(455, 822, 'Halaman {PAGE_NUM} / {PAGE_COUNT}', $font, 8);
    }
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
    return ['graduate'=>$sample,'grades'=>$grades,'template'=>$template,'summary'=>grade_summary($grades)];
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

function render_transcript_html(array $snapshot, bool $preview): string
{
    $graduate = $snapshot['graduate'];
    $template = $snapshot['template']['settings'];
    $grades = $snapshot['grades'];
    $rows = '';
    foreach ($grades as $i => $grade) {
        $quality = (float) $grade['credits'] * (float) $grade['grade_weight'];
        $rows .= '<tr><td class="num">' . ($i + 1) . '</td><td>' . h($grade['course_code']) . '</td><td>' . h($grade['course_name']) . '</td><td class="num">' . h(number_format((float) $grade['credits'], 0, ',', '.')) . '</td><td class="num">' . h($grade['grade_letter']) . '</td><td class="num">' . h(number_format($quality, 2, ',', '.')) . '</td></tr>';
    }
    $summary = $snapshot['summary'];
    $thesis = !empty($template['show_thesis']) && $graduate['thesis_title'] !== '' ? '<div class="thesis"><strong>Judul skripsi:</strong> ' . h($graduate['thesis_title']) . '</div>' : '';
    $draft = $preview ? '<div class="draft">PRATINJAU</div>' : '';
    return '<!doctype html><html><head><meta charset="utf-8"><style>
        @page{size:A4 portrait;margin:30mm 15mm 15mm}
        body{font-family:DejaVu Sans,Arial,sans-serif;font-size:9pt;color:#111;margin:0}
        .head{position:fixed;top:-24mm;left:0;right:0;text-align:center;border-bottom:1pt solid #111;padding-bottom:2mm}
        .school{font-size:11pt;font-weight:bold;letter-spacing:.1pt}
        .address{font-size:7pt;margin-top:1mm}
        .running-id{font-size:7pt;margin-top:1mm}
        .title{font-size:12pt;font-weight:bold;text-align:center;margin:0 0 5mm}
        .meta{width:100%;margin-bottom:4mm;border-collapse:collapse}
        .meta td{padding:1mm 1mm;vertical-align:top;width:50%}
        .grades{width:100%;border-collapse:collapse;font-size:8pt}
        .grades thead{display:table-header-group}
        .grades tr{page-break-inside:avoid}
        .grades th,.grades td{border:.5pt solid #333;padding:1.2mm 1.5mm;vertical-align:top}
        .grades th{background:#efefef}
        .grades .table-context th{background:#fff;text-align:left;border:0;border-bottom:1pt solid #555;padding:3mm 1mm;font-size:7pt;font-weight:normal}
        .num{text-align:center;white-space:nowrap}
        .totals{margin-top:5mm;line-height:1.7;page-break-inside:avoid}
        .thesis{margin-top:3mm;page-break-inside:avoid}
        .closing{page-break-inside:avoid}
        .closing-title{font-size:8pt;font-weight:bold;border-bottom:.5pt solid #777;padding-bottom:2mm;margin-top:4mm}
        .sign{margin-top:8mm;margin-left:auto;width:70mm;text-align:center;page-break-inside:avoid}
        .sign-space{height:19mm}
        .draft{position:fixed;top:110mm;left:40mm;font:32pt Arial;color:#e2e2e2;z-index:-1;transform:rotate(-25deg)}
        </style></head><body>' . $draft . '
        <div class="head"><div class="school">' . h($template['institution']) . '</div><div class="address">' . h($template['address']) . '</div><div class="running-id">' . h($template['title']) . ' · ' . h($graduate['name']) . ' · NIM ' . h($graduate['nim']) . '</div></div>
        <div class="title">' . h($template['title']) . '</div>
        <table class="meta"><tr><td>Nomor transkrip: <strong>' . h($graduate['transcript_number']) . '</strong></td><td>Program studi: ' . h($template['program_label']) . '</td></tr>
        <tr><td>Nama: <strong>' . h($graduate['name']) . '</strong></td><td>NIM: ' . h($graduate['nim']) . '</td></tr>
        <tr><td>Tempat/tanggal lahir: ' . h($graduate['birth_place']) . ', ' . h(format_date_id($graduate['birth_date'])) . '</td><td>Tanggal lulus: ' . h(format_date_id($graduate['graduation_date'])) . '</td></tr>
        <tr><td>Nomor ijazah nasional: ' . h($graduate['diploma_number']) . '</td><td>' . h($template['accreditation']) . '</td></tr></table>
        <table class="grades"><thead><tr class="table-context"><th colspan="6">' . h($template['title']) . ' · ' . h($graduate['name']) . ' · NIM ' . h($graduate['nim']) . ' · No. ' . h($graduate['transcript_number']) . '</th></tr><tr><th>No.</th><th>Kode MK</th><th>Mata kuliah</th><th>SKS</th><th>Nilai</th><th>Mutu</th></tr></thead><tbody>' . $rows . '</tbody></table>
        <div class="closing"><div class="closing-title">Ringkasan dan pengesahan · ' . h($graduate['name']) . ' · NIM ' . h($graduate['nim']) . '</div><div class="totals">Jumlah SKS: <strong>' . ($graduate['official_credits'] === null || $graduate['official_credits'] === '' ? 'Belum diisi' : h(number_format((float) $graduate['official_credits'], 0, ',', '.'))) . '</strong><br>
        Jumlah mutu: <strong>' . h(number_format((float) $summary['quality'], 2, ',', '.')) . '</strong><br>
        Indeks Prestasi Kumulatif: <strong>' . ($graduate['official_gpa'] === null || $graduate['official_gpa'] === '' ? 'Belum diisi' : h(number_format((float) $graduate['official_gpa'], 2, ',', '.'))) . '</strong></div>' . $thesis . '
        <div class="sign">' . h($template['issue_place']) . ', ' . h(format_date_id($graduate['issue_date'])) . '<br>' . h($template['signer_role']) . ',<div class="sign-space"></div><strong><u>' . h($template['signer_name']) . '</u></strong><br>' . h($template['signer_nidn']) . '</div></div>
        </body></html>';
}

function render_transcript_browser_html(array $snapshot): string
{
    $html = render_transcript_html($snapshot, true);
    $screenStyle = '<style>html{background:#eef1f3}body{box-sizing:border-box;width:210mm;min-height:297mm;margin:0 auto;padding:15mm;background:#fff;box-shadow:0 1px 8px #cbd5da}.head{position:static;margin-bottom:5mm}.running-id{display:none}.draft{position:absolute;top:130mm;left:55mm}@media(max-width:210mm){body{width:100%;padding:18px}.meta td{display:block;width:auto}.sign{max-width:70mm}}</style>';
    return str_replace('</head>', $screenStyle . '</head>', $html);
}
