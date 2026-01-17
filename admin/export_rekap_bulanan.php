<?php
require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

requireRole(['Pembina', 'Pengurus']);

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

$statusColor = [
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

// ========================================
// HEADER (Row 1-2)
// ========================================
$lastEventCol = count($events) > 0 ? chr(ord('D') + count($events) + 4) : 'H';

$sheet->mergeCells("A1:{$lastEventCol}1");
$sheet->setCellValue('A1', 'REKAP ABSENSI PMR - ' . strtoupper($monthName) . ' ' . $year);
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '800000']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);
$sheet->getRowDimension(1)->setRowHeight(25);

$sheet->mergeCells("A2:{$lastEventCol}2");
$sheet->setCellValue('A2', 'Diekspor: ' . date('d F Y, H:i') . ' | H=Hadir, T=Terlambat, I=Izin, S=Sakit, A=Alpha');
$sheet->getStyle('A2')->applyFromArray([
    'font' => ['italic' => true, 'size' => 10],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

// ========================================
// TABLE HEADER (Row 4)
// ========================================
$headerRow = 4;
$col = 'A';

// Fixed columns
$sheet->setCellValue($col++ . $headerRow, 'No');
$sheet->setCellValue($col++ . $headerRow, 'Nama');
$sheet->setCellValue($col++ . $headerRow, 'Kelas');

// Dynamic event columns
$eventCols = [];
foreach ($events as $event) {
    $cellRef = $col . $headerRow;
    $eventCols[$event['id']] = $col;
    $sheet->setCellValue($cellRef, date('d', strtotime($event['tanggal'])));
    $sheet->getColumnDimension($col)->setWidth(5);
    $col++;
}

// Summary columns
$summaryStart = $col;
$sheet->setCellValue($col++ . $headerRow, 'H');
$sheet->setCellValue($col++ . $headerRow, 'T');
$sheet->setCellValue($col++ . $headerRow, 'I');
$sheet->setCellValue($col++ . $headerRow, 'S');
$sheet->setCellValue($col++ . $headerRow, 'A');
$lastCol = chr(ord($col) - 1);

// Style header row
$sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
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
    $col = 'A';

    // Fixed columns
    $sheet->setCellValue($col++ . $row, $no++);
    $sheet->setCellValue($col++ . $row, $user['nama']);
    $sheet->setCellValue($col++ . $row, $user['kelas'] ?? '-');

    // Status counts
    $counts = ['H' => 0, 'T' => 0, 'I' => 0, 'S' => 0, 'A' => 0];

    // Event columns
    foreach ($events as $event) {
        $status = $attendanceMap[$user['id']][$event['id']] ?? null;
        $code = $status ? ($statusCode[$status] ?? '-') : '-';
        $cellRef = $eventCols[$event['id']] . $row;
        $sheet->setCellValue($cellRef, $code);

        // Color coding
        if (isset($statusColor[$code])) {
            $sheet->getStyle($cellRef)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => $statusColor[$code]]]
            ]);
            $counts[$code]++;
        }

        $sheet->getStyle($cellRef)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    // Summary columns
    $sheet->setCellValue($summaryStart . $row, $counts['H']);
    $sheet->setCellValue(chr(ord($summaryStart) + 1) . $row, $counts['T']);
    $sheet->setCellValue(chr(ord($summaryStart) + 2) . $row, $counts['I']);
    $sheet->setCellValue(chr(ord($summaryStart) + 3) . $row, $counts['S']);
    $sheet->setCellValue(chr(ord($summaryStart) + 4) . $row, $counts['A']);

    // Style summary with colors
    $sheet->getStyle($summaryStart . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('008000'));
    $sheet->getStyle(chr(ord($summaryStart) + 1) . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF8C00'));
    $sheet->getStyle(chr(ord($summaryStart) + 2) . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0066CC'));
    $sheet->getStyle(chr(ord($summaryStart) + 3) . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('666666'));
    $sheet->getStyle(chr(ord($summaryStart) + 4) . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0000'));

    // Borders for row
    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
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
foreach ($events as $idx => $event) {
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
    $sheet->getColumnDimension(chr(ord($summaryStart) + $i))->setWidth(5);
}

// ========================================
// OUTPUT
// ========================================
$filename = "Rekap_Absensi_{$monthName}_{$year}.xlsx";

ob_end_clean();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>