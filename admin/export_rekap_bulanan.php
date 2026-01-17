<?php
// Start output buffering immediately to prevent any output before headers
ob_start();

require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

requireRole(['Pembina', 'Pengurus']);

// Helper function to convert column index (1-based) to Excel column letter
function getColLetter($colIndex)
{
    $letter = '';
    while ($colIndex > 0) {
        $colIndex--;
        $letter = chr(65 + ($colIndex % 26)) . $letter;
        $colIndex = intval($colIndex / 26);
    }
    return $letter;
}

$month = (int) ($_GET['month'] ?? date('m'));
$year = (int) ($_GET['year'] ?? date('Y'));

$months = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember'
];
$monthName = $months[$month] ?? 'Unknown';

// Get all events in the selected month
$startDate = sprintf('%04d-%02d-01', $year, $month);
$endDate = date('Y-m-t', strtotime($startDate));

$eventsResult = $conn->query("
    SELECT id, nama_kegiatan, tanggal 
    FROM events 
    WHERE tanggal BETWEEN '$startDate' AND '$endDate'
    ORDER BY tanggal ASC, jam_mulai ASC
");
$events = [];
while ($e = $eventsResult->fetch_assoc()) {
    $events[] = $e;
}

// Get all members
$usersResult = $conn->query("
    SELECT id, nis, nama, kelas 
    FROM users 
    WHERE jabatan IN ('Anggota', 'Pengurus')
    ORDER BY kelas, nama
");
$users = [];
while ($u = $usersResult->fetch_assoc()) {
    $users[] = $u;
}

// Get all attendance records for this month
$attendanceMap = [];
$attResult = $conn->query("
    SELECT a.user_id, a.event_id, a.status
    FROM attendance a
    JOIN events e ON a.event_id = e.id
    WHERE e.tanggal BETWEEN '$startDate' AND '$endDate'
");
while ($a = $attResult->fetch_assoc()) {
    $attendanceMap[$a['user_id']][$a['event_id']] = $a['status'];
}

// Status code mapping
$statusCode = [
    'Hadir' => 'H',
    'Terlambat' => 'T',
    'Izin' => 'I',
    'Sakit' => 'S',
    'Alpha' => 'A'
];

$statusColorMap = [
    'H' => '008000', // Green
    'T' => 'FF8C00', // Orange
    'I' => '0066CC', // Blue
    'S' => '666666', // Gray
    'A' => 'FF0000'  // Red
];

// Create Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Rekap ' . $monthName);

// Calculate last column
$totalCols = 3 + count($events) + 5; // Fixed(3) + Events + Summary(5)
$lastColLetter = getColLetter($totalCols);

// ========================================
// HEADER (Row 1-2)
// ========================================
$sheet->mergeCells("A1:{$lastColLetter}1");
$sheet->setCellValue('A1', 'REKAP ABSENSI PMR - ' . strtoupper($monthName) . ' ' . $year);
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '800000']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);
$sheet->getRowDimension(1)->setRowHeight(25);

$sheet->mergeCells("A2:{$lastColLetter}2");
$sheet->setCellValue('A2', 'Diekspor: ' . date('d F Y, H:i') . ' | H=Hadir, T=Terlambat, I=Izin, S=Sakit, A=Alpha');
$sheet->getStyle('A2')->applyFromArray([
    'font' => ['italic' => true, 'size' => 10],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

// ========================================
// TABLE HEADER (Row 4)
// ========================================
$headerRow = 4;
$colIndex = 1;

// Fixed columns
$sheet->setCellValue(getColLetter($colIndex) . $headerRow, 'No');
$colIndex++;
$sheet->setCellValue(getColLetter($colIndex) . $headerRow, 'Nama');
$colIndex++;
$sheet->setCellValue(getColLetter($colIndex) . $headerRow, 'Kelas');
$colIndex++;

// Dynamic event columns
$eventCols = [];
foreach ($events as $event) {
    $colLetter = getColLetter($colIndex);
    $eventCols[$event['id']] = $colLetter;
    $sheet->setCellValue($colLetter . $headerRow, date('d', strtotime($event['tanggal'])));
    $sheet->getColumnDimension($colLetter)->setWidth(5);
    $colIndex++;
}

// Summary columns
$summaryStartIndex = $colIndex;
$summaryLabels = ['H', 'T', 'I', 'S', 'A'];
foreach ($summaryLabels as $label) {
    $sheet->setCellValue(getColLetter($colIndex) . $headerRow, $label);
    $colIndex++;
}

// Style header row
$sheet->getStyle("A{$headerRow}:{$lastColLetter}{$headerRow}")->applyFromArray([
    'font' => ['bold' => true, 'size' => 10],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);
$sheet->getRowDimension($headerRow)->setRowHeight(20);

// ========================================
// DATA ROWS (Row 5+)
// ========================================
$row = 5;
$no = 1;

foreach ($users as $user) {
    $colIndex = 1;

    // Fixed columns
    $sheet->setCellValue(getColLetter($colIndex) . $row, $no);
    $no++;
    $colIndex++;
    $sheet->setCellValue(getColLetter($colIndex) . $row, $user['nama']);
    $colIndex++;
    $sheet->setCellValue(getColLetter($colIndex) . $row, $user['kelas'] ?? '-');

    // Status counts
    $counts = ['H' => 0, 'T' => 0, 'I' => 0, 'S' => 0, 'A' => 0];

    // Event columns
    foreach ($events as $event) {
        $status = $attendanceMap[$user['id']][$event['id']] ?? null;
        $code = $status ? ($statusCode[$status] ?? '-') : '-';
        $cellRef = $eventCols[$event['id']] . $row;
        $sheet->setCellValue($cellRef, $code);

        // Color coding
        if (isset($statusColorMap[$code])) {
            $sheet->getStyle($cellRef)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => $statusColorMap[$code]]]
            ]);
            $counts[$code]++;
        }

        $sheet->getStyle($cellRef)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    // Summary columns
    $summaryColors = ['008000', 'FF8C00', '0066CC', '666666', 'FF0000'];
    $summaryKeys = ['H', 'T', 'I', 'S', 'A'];
    for ($i = 0; $i < 5; $i++) {
        $sumColLetter = getColLetter($summaryStartIndex + $i);
        $sheet->setCellValue($sumColLetter . $row, $counts[$summaryKeys[$i]]);
        $sheet->getStyle($sumColLetter . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($summaryColors[$i]));
    }

    // Borders for row
    $sheet->getStyle("A{$row}:{$lastColLetter}{$row}")->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
    ]);

    $row++;
}

// ========================================
// EVENT LEGEND (Below data)
// ========================================
$legendRow = $row + 2;
$sheet->setCellValue('A' . $legendRow, 'Keterangan Kegiatan:');
$sheet->getStyle('A' . $legendRow)->getFont()->setBold(true);

$legendRow++;
foreach ($events as $event) {
    $sheet->setCellValue('A' . $legendRow, date('d', strtotime($event['tanggal'])) . ' = ' . $event['nama_kegiatan'] . ' (' . date('d/m/Y', strtotime($event['tanggal'])) . ')');
    $legendRow++;
}

// ========================================
// COLUMN WIDTHS
// ========================================
$sheet->getColumnDimension('A')->setWidth(5);
$sheet->getColumnDimension('B')->setWidth(25);
$sheet->getColumnDimension('C')->setWidth(12);

// Summary columns width
for ($i = 0; $i < 5; $i++) {
    $sheet->getColumnDimension(getColLetter($summaryStartIndex + $i))->setWidth(5);
}

// ========================================
// OUTPUT
// ========================================
$filename = "Rekap_Absensi_{$monthName}_{$year}.xlsx";

// Clean any previous output
if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>