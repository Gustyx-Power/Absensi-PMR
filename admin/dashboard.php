<?php
require_once '../config/auth_check.php';
require_once '../config/database.php';

$pageTitle = 'Dashboard - Sistem Absensi PMR';
$baseUrl = '../';
$currentPage = 'dashboard';

// Get statistics
$totalAnggota = $conn->query("SELECT COUNT(*) as total FROM users WHERE jabatan = 'Anggota'")->fetch_assoc()['total'];
$totalPengurus = $conn->query("SELECT COUNT(*) as total FROM users WHERE jabatan = 'Pengurus'")->fetch_assoc()['total'];
$totalKegiatan = $conn->query("SELECT COUNT(*) as total FROM events")->fetch_assoc()['total'];
$totalHadir = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE status = 'Hadir'")->fetch_assoc()['total'];

// Get upcoming events (next 7 days)
$upcomingEvents = $conn->query("
    SELECT * FROM events 
    WHERE tanggal >= CURDATE() AND tanggal <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY tanggal ASC, jam_mulai ASC
    LIMIT 5
");

// Get recent attendance
$recentAttendance = $conn->query("
    SELECT a.*, u.nama, u.nis, e.nama_kegiatan, e.tanggal
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    JOIN events e ON a.event_id = e.id
    ORDER BY a.created_at DESC
    LIMIT 10
");

include '../views/header.php';
?>

<section class="py-4">
    <div class="container">
        <!-- Welcome Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">
                    <i class="bi bi-speedometer2 text-danger me-2"></i>Dashboard
                </h2>
                <p class="text-muted mb-0">
                    Selamat datang, <strong>
                        <?= htmlspecialchars($_SESSION['nama']) ?>
                    </strong>!
                </p>
            </div>
            <div class="text-end">
                <small class="text-muted">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= date('l, d F Y') ?>
                </small>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card bg-primary position-relative overflow-hidden">
                    <div class="card-body">
                        <i class="bi bi-people-fill stat-icon"></i>
                        <h3>
                            <?= $totalAnggota ?>
                        </h3>
                        <p class="mb-0">Total Anggota</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card bg-success position-relative overflow-hidden">
                    <div class="card-body">
                        <i class="bi bi-person-badge stat-icon"></i>
                        <h3>
                            <?= $totalPengurus ?>
                        </h3>
                        <p class="mb-0">Pengurus</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card bg-warning position-relative overflow-hidden">
                    <div class="card-body">
                        <i class="bi bi-calendar-event stat-icon"></i>
                        <h3>
                            <?= $totalKegiatan ?>
                        </h3>
                        <p class="mb-0">Total Kegiatan</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card bg-danger position-relative overflow-hidden">
                    <div class="card-body">
                        <i class="bi bi-check-circle stat-icon"></i>
                        <h3>
                            <?= $totalHadir ?>
                        </h3>
                        <p class="mb-0">Total Kehadiran</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Upcoming Events -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-calendar-week me-2"></i>Kegiatan Mendatang</span>
                        <a href="kegiatan.php" class="btn btn-sm btn-light">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <?php if ($upcomingEvents->num_rows > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while ($event = $upcomingEvents->fetch_assoc()): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">
                                                    <?= htmlspecialchars($event['nama_kegiatan']) ?>
                                                </h6>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar3 me-1"></i>
                                                    <?= date('d M Y', strtotime($event['tanggal'])) ?>
                                                    <i class="bi bi-clock ms-2 me-1"></i>
                                                    <?= date('H:i', strtotime($event['jam_mulai'])) ?> -
                                                    <?= date('H:i', strtotime($event['jam_selesai'])) ?>
                                                </small>
                                            </div>
                                            <span class="badge bg-danger">
                                                <?= date('d M', strtotime($event['tanggal'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-calendar-x display-4 mb-2"></i>
                                <p class="mb-0">Tidak ada kegiatan dalam 7 hari ke depan</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Attendance -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-clipboard-check me-2"></i>Absensi Terbaru</span>
                        <a href="absensi.php" class="btn btn-sm btn-light">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <?php if ($recentAttendance->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nama</th>
                                            <th>Kegiatan</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($att = $recentAttendance->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <small class="fw-medium">
                                                        <?= htmlspecialchars($att['nama']) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <small>
                                                        <?= htmlspecialchars($att['nama_kegiatan']) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $badgeClass = match ($att['status']) {
                                                        'Hadir' => 'bg-success',
                                                        'Izin' => 'bg-info',
                                                        'Sakit' => 'bg-warning text-dark',
                                                        'Alpha' => 'bg-danger',
                                                        default => 'bg-secondary'
                                                    };
                                                    ?>
                                                    <span class="badge <?= $badgeClass ?>">
                                                        <?= $att['status'] ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-clipboard-x display-4 mb-2"></i>
                                <p class="mb-0">Belum ada data absensi</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <?php if (hasRole(['Pembina', 'Pengurus'])): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <i class="bi bi-lightning me-2"></i>Aksi Cepat
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6 col-md-3">
                            <a href="kegiatan.php?action=add" class="btn btn-outline-primary w-100">
                                <i class="bi bi-plus-circle me-2"></i>Tambah Kegiatan
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <a href="absensi.php?action=add" class="btn btn-outline-success w-100">
                                <i class="bi bi-clipboard-plus me-2"></i>Input Absensi
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <a href="anggota.php?action=add" class="btn btn-outline-info w-100">
                                <i class="bi bi-person-plus me-2"></i>Tambah Anggota
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <a href="laporan.php" class="btn btn-outline-warning w-100">
                                <i class="bi bi-download me-2"></i>Unduh Laporan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include '../views/footer.php'; ?>