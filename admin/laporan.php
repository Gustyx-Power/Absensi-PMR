<?php
require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['Pembina', 'Pengurus']);

$pageTitle = 'Laporan Absensi - Sistem Absensi PMR';
$baseUrl = '../';
$currentPage = 'laporan';

// Get all events for filter
$allEvents = $conn->query("SELECT id, nama_kegiatan, tanggal FROM events ORDER BY tanggal DESC");

// Get statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN status = 'Izin' THEN 1 ELSE 0 END) as izin,
        SUM(CASE WHEN status = 'Sakit' THEN 1 ELSE 0 END) as sakit,
        SUM(CASE WHEN status = 'Alpha' THEN 1 ELSE 0 END) as alpha
    FROM attendance
")->fetch_assoc();

$hadirPercent = $stats['total'] > 0 ? round(($stats['hadir'] / $stats['total']) * 100, 1) : 0;

include __DIR__ . '/../views/header.php';
?>

<section class="py-4">
    <div class="container">
        <div class="mb-4">
            <h2><i class="bi bi-graph-up text-danger me-2"></i>Laporan Absensi</h2>
            <p class="text-muted">Export dan analisis data kehadiran anggota PMR</p>
        </div>

        <!-- Statistics Overview -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-2">
                <div class="card text-center py-3">
                    <h3 class="text-primary mb-0">
                        <?= $stats['total'] ?>
                    </h3>
                    <small class="text-muted">Total</small>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card text-center py-3">
                    <h3 class="text-success mb-0">
                        <?= $stats['hadir'] ?>
                    </h3>
                    <small class="text-muted">Hadir</small>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card text-center py-3">
                    <h3 class="text-info mb-0">
                        <?= $stats['izin'] ?>
                    </h3>
                    <small class="text-muted">Izin</small>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card text-center py-3">
                    <h3 class="text-warning mb-0">
                        <?= $stats['sakit'] ?>
                    </h3>
                    <small class="text-muted">Sakit</small>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card text-center py-3">
                    <h3 class="text-danger mb-0">
                        <?= $stats['alpha'] ?>
                    </h3>
                    <small class="text-muted">Alpha</small>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card text-center py-3 bg-success text-white">
                    <h3 class="mb-0">
                        <?= $hadirPercent ?>%
                    </h3>
                    <small>Kehadiran</small>
                </div>
            </div>
        </div>

        <!-- Export Card -->
        <div class="card">
            <div class="card-header bg-danger text-white">
                <i class="bi bi-file-earmark-excel me-2"></i>Export ke Excel
            </div>
            <div class="card-body">
                <form action="export_excel.php" method="GET" target="_blank">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Filter Kegiatan</label>
                            <select class="form-select" name="event_id">
                                <option value="">Semua Kegiatan</option>
                                <?php while ($e = $allEvents->fetch_assoc()): ?>
                                    <option value="<?= $e['id'] ?>">
                                        <?= htmlspecialchars($e['nama_kegiatan']) ?> (
                                        <?= date('d M Y', strtotime($e['tanggal'])) ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Filter Status</label>
                            <select class="form-select" name="status">
                                <option value="">Semua Status</option>
                                <option value="Hadir">Hadir</option>
                                <option value="Izin">Izin</option>
                                <option value="Sakit">Sakit</option>
                                <option value="Alpha">Alpha</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Dari Tanggal</label>
                            <input type="date" class="form-control" name="date_from">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Sampai Tanggal</label>
                            <input type="date" class="form-control" name="date_to">
                        </div>

                        <div class="col-12">
                            <hr>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-download me-2"></i>Download Excel
                            </button>
                            <span class="text-muted ms-3">
                                <i class="bi bi-info-circle me-1"></i>
                                File akan diunduh dalam format .xlsx
                            </span>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Monthly Recap Link -->
        <div class="card mt-4 border-primary">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1"><i class="bi bi-calendar-range text-primary me-2"></i>Rekap Bulanan (Matrix)</h5>
                    <small class="text-muted">Download rekap absensi bulanan dengan format tabel matrix per
                        anggota</small>
                </div>
                <a href="laporan_bulanan.php" class="btn btn-primary">
                    <i class="bi bi-table me-2"></i>Buka Rekap Bulanan
                </a>
            </div>
        </div>

        <!-- Instructions -->
        <div class="card mt-4">
            <div class="card-header"><i class="bi bi-question-circle me-2"></i>Panduan Export</div>
            <div class="card-body">
                <ol class="mb-0">
                    <li>Pilih <strong>Kegiatan</strong> untuk export data kegiatan tertentu, atau kosongkan untuk semua.
                    </li>
                    <li>Pilih <strong>Status</strong> untuk filter berdasarkan status kehadiran.</li>
                    <li>Gunakan <strong>Dari/Sampai Tanggal</strong> untuk filter rentang waktu.</li>
                    <li>Klik <strong>Download Excel</strong> untuk mengunduh file laporan.</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../views/footer.php'; ?>