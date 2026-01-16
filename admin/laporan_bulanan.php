<?php
require_once '../config/auth_check.php';
require_once '../config/database.php';

requireRole(['Pembina', 'Pengurus']);

$pageTitle = 'Laporan Bulanan - Sistem Absensi PMR';
$baseUrl = '../';
$currentPage = 'laporan';

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

$currentMonth = (int) date('m');
$currentYear = (int) date('Y');

include '../views/header.php';
?>

<section class="py-4">
    <div class="container">
        <div class="mb-4">
            <h2><i class="bi bi-calendar-range text-danger me-2"></i>Laporan Rekap Bulanan</h2>
            <p class="text-muted">Download rekap absensi dalam format Excel matrix</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <i class="bi bi-file-earmark-excel me-2"></i>Generate Rekap Bulanan
                    </div>
                    <div class="card-body">
                        <form action="export_rekap_bulanan.php" method="GET">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Bulan <span class="text-danger">*</span></label>
                                    <select name="month" class="form-select" required>
                                        <?php foreach ($months as $num => $name): ?>
                                            <option value="<?= $num ?>" <?= $num == $currentMonth ? 'selected' : '' ?>>
                                                <?= $name ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tahun <span class="text-danger">*</span></label>
                                    <select name="year" class="form-select" required>
                                        <?php for ($y = $currentYear; $y >= $currentYear - 5; $y--): ?>
                                            <option value="<?= $y ?>" <?= $y == $currentYear ? 'selected' : '' ?>>
                                                <?= $y ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="bi bi-download me-2"></i>Download Rekap Excel
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h6><i class="bi bi-info-circle text-primary me-2"></i>Format Excel</h6>
                        <ul class="mb-0 small text-muted">
                            <li><strong>Baris:</strong> Daftar semua anggota</li>
                            <li><strong>Kolom:</strong> Tanggal kegiatan dalam bulan tersebut</li>
                            <li><strong>Kode Status:</strong>
                                <span class="badge bg-success">H</span> Hadir,
                                <span class="badge bg-warning text-dark">T</span> Terlambat,
                                <span class="badge bg-info">I</span> Izin,
                                <span class="badge bg-secondary">S</span> Sakit,
                                <span class="badge bg-danger">A</span> Alpha
                            </li>
                            <li><strong>Summary:</strong> Total per status di akhir kolom</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../views/footer.php'; ?>