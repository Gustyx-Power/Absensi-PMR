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

// Get filter parameters
$filterEvent = $_GET['event_id'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';

// Build query
$whereClause = "1=1";
$params = [];
$types = "";

if ($filterEvent) {
    $whereClause .= " AND a.event_id = ?";
    $params[] = $filterEvent;
    $types .= "i";
}

if ($filterStatus) {
    $whereClause .= " AND a.status = ?";
    $params[] = $filterStatus;
    $types .= "s";
}

if ($filterDateFrom) {
    $whereClause .= " AND e.tanggal >= ?";
    $params[] = $filterDateFrom;
    $types .= "s";
}

if ($filterDateTo) {
    $whereClause .= " AND e.tanggal <= ?";
    $params[] = $filterDateTo;
    $types .= "s";
}

$sql = "
    SELECT 
        u.nis,
        u.nama,
        u.kelas,
        e.nama_kegiatan,
        e.tanggal,
        a.waktu_absen,
        a.clock_out,
        a.status
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    JOIN events e ON a.event_id = e.id
    WHERE $whereClause
    ORDER BY e.tanggal DESC, u.nama ASC
";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Create Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Laporan Absensi');

// ========================================
// REPORT HEADER (Row 1-3)
// ========================================

$sheet->mergeCells('A1:I1');
$sheet->setCellValue('A1', 'LAPORAN ABSENSI PMR');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '800000']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
]);
$sheet->getRowDimension(1)->setRowHeight(30);

$sheet->mergeCells('A2:I2');
$sheet->setCellValue('A2', 'Palang Merah Remaja - Diekspor: ' . date('d F Y, H:i'));
$sheet->getStyle('A2')->applyFromArray([
    'font' => ['italic' => true, 'size' => 11],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

$sheet->getRowDimension(3)->setRowHeight(10);

// ========================================
// TABLE HEADER (Row 4)
// ========================================
$headerRow = 4;
$headers = ['No', 'NIS', 'Nama', 'Kelas', 'Kegiatan', 'Tanggal', 'Jam Masuk', 'Jam Pulang', 'Status'];

$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . $headerRow, $header);
    $col++;
}

$sheet->getStyle('A' . $headerRow . ':I' . $headerRow)->applyFromArray([
    'font' => ['bold' => true, 'size' => 11],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
]);
$sheet->getRowDimension($headerRow)->setRowHeight(22);

// ========================================
// TABLE BODY (Row 5+)
// ========================================
$row = 5;
$no = 1;

while ($data = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $row, $no);
    $sheet->setCellValue('B' . $row, $data['nis']);
    $sheet->setCellValue('C' . $row, $data['nama']);
    $sheet->setCellValue('D' . $row, $data['kelas']);
    $sheet->setCellValue('E' . $row, $data['nama_kegiatan']);
    $sheet->setCellValue('F' . $row, date('d/m/Y', strtotime($data['tanggal'])));
    $sheet->setCellValue('G' . $row, $data['waktu_absen'] ? date('H:i', strtotime($data['waktu_absen'])) : '-');
    $sheet->setCellValue('H' . $row, $data['clock_out'] ? date('H:i', strtotime($data['clock_out'])) : '-');
    $sheet->setCellValue('I' . $row, $data['status']);

    // Apply borders
    $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
    ]);

    // Center align specific columns
    foreach (['A', 'B', 'D', 'F', 'G', 'H', 'I'] as $c) {
        $sheet->getStyle($c . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    // Conditional Formatting for Status
    $statusColor = match ($data['status']) {
        'Hadir' => '008000',      // Green
        'Terlambat' => 'FF8C00',  // Orange
        'Izin' => '0066CC',       // Blue
        'Sakit' => '666666',      // Gray
        'Alpha' => 'FF0000',      // Red
        default => '000000'
    };

    $sheet->getStyle('I' . $row)->applyFromArray([
        'font' => ['color' => ['rgb' => $statusColor], 'bold' => true]
    ]);

    $row++;
    $no++;
}

// ========================================
// SUMMARY FOOTER
// ========================================
$summaryRow = $row + 1;
$totalData = $no - 1;

$sheet->mergeCells('A' . $summaryRow . ':E' . $summaryRow);
$sheet->setCellValue('A' . $summaryRow, 'Total Data: ' . $totalData . ' record(s)');
$sheet->getStyle('A' . $summaryRow)->applyFromArray([
    'font' => ['bold' => true, 'italic' => true]
]);

$infoRow = $summaryRow + 1;
$sheet->mergeCells('A' . $infoRow . ':I' . $infoRow);
$sheet->setCellValue('A' . $infoRow, 'Diekspor oleh: ' . $_SESSION['nama'] . ' (' . $_SESSION['jabatan'] . ')');
$sheet->getStyle('A' . $infoRow)->applyFromArray([
    'font' => ['size' => 9, 'italic' => true, 'color' => ['rgb' => '666666']]
]);

// ========================================
// COLUMN WIDTHS
// ========================================
$sheet->getColumnDimension('A')->setWidth(6);   // No
$sheet->getColumnDimension('B')->setWidth(14);  // NIS
$sheet->getColumnDimension('C')->setWidth(25);  // Nama
$sheet->getColumnDimension('D')->setWidth(10);  // Kelas
$sheet->getColumnDimension('E')->setWidth(28);  // Kegiatan
$sheet->getColumnDimension('F')->setWidth(12);  // Tanggal
$sheet->getColumnDimension('G')->setWidth(12);  // Jam Masuk
$sheet->getColumnDimension('H')->setWidth(12);  // Jam Pulang
$sheet->getColumnDimension('I')->setWidth(12);  // Status

// ========================================
// OUTPUT FILE
// ========================================
$filename = 'Laporan_Absensi_PMR_' . date('Y-m-d_His') . '.xlsx';

ob_end_clean();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

exit;
?>