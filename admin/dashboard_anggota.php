<?php
require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Dashboard - Sistem Absensi PMR';
$baseUrl = '../';
$currentPage = 'dashboard';

$user_id = $_SESSION['user_id'];
$jabatan = $_SESSION['jabatan'];

// Get user's attendance stats
$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN status = 'Terlambat' THEN 1 ELSE 0 END) as terlambat,
        SUM(CASE WHEN status = 'Izin' THEN 1 ELSE 0 END) as izin,
        SUM(CASE WHEN status = 'Sakit' THEN 1 ELSE 0 END) as sakit,
        SUM(CASE WHEN status = 'Alpha' THEN 1 ELSE 0 END) as alpha
    FROM attendance WHERE user_id = $user_id
")->fetch_assoc();

// Calculate attendance percentage
$totalEvents = $conn->query("SELECT COUNT(*) as count FROM events WHERE tanggal <= CURDATE()")->fetch_assoc()['count'];
$attendanceRate = $totalEvents > 0 ? round((($stats['hadir'] + $stats['terlambat']) / $totalEvents) * 100) : 0;

// Get today's events
$todayEvents = $conn->query("
    SELECT e.*, 
           (SELECT status FROM attendance WHERE user_id = $user_id AND event_id = e.id) as my_status,
           (SELECT waktu_absen FROM attendance WHERE user_id = $user_id AND event_id = e.id) as my_checkin,
           (SELECT clock_out FROM attendance WHERE user_id = $user_id AND event_id = e.id) as my_checkout
    FROM events e 
    WHERE e.tanggal = CURDATE() 
    ORDER BY e.jam_mulai ASC
");

// Get recent attendance history
$recentAttendance = $conn->query("
    SELECT a.*, e.nama_kegiatan, e.tanggal
    FROM attendance a
    JOIN events e ON a.event_id = e.id
    WHERE a.user_id = $user_id
    ORDER BY e.tanggal DESC, a.created_at DESC
    LIMIT 10
");

// Get upcoming events
$upcomingEvents = $conn->query("
    SELECT * FROM events 
    WHERE tanggal > CURDATE() 
    ORDER BY tanggal ASC, jam_mulai ASC 
    LIMIT 5
");

include __DIR__ . '/../views/header.php';
?>

<section class="py-4">
    <div class="container">
        <!-- Welcome -->
        <div class="mb-4">
            <h2 class="mb-1">
                <i class="bi bi-person-circle text-danger me-2"></i>
                Halo, <?= htmlspecialchars($_SESSION['nama']) ?>!
            </h2>
            <p class="text-muted mb-0">
                <span class="badge bg-secondary"><?= $_SESSION['jabatan'] ?></span>
                <?php if (!empty($_SESSION['kelas'])): ?>
                    <span class="ms-2"><?= htmlspecialchars($_SESSION['kelas']) ?></span>
                <?php endif; ?>
            </p>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card text-center py-3 border-success">
                    <h2 class="text-success mb-0"><?= $stats['hadir'] ?></h2>
                    <small class="text-muted">Hadir</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center py-3 border-warning">
                    <h2 class="text-warning mb-0"><?= $stats['terlambat'] ?></h2>
                    <small class="text-muted">Terlambat</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center py-3 border-info">
                    <h2 class="text-info mb-0"><?= $stats['izin'] + $stats['sakit'] ?></h2>
                    <small class="text-muted">Izin/Sakit</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center py-3 border-danger">
                    <h2 class="text-danger mb-0"><?= $stats['alpha'] ?></h2>
                    <small class="text-muted">Alpha</small>
                </div>
            </div>
        </div>

        <!-- Attendance Rate -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold">Tingkat Kehadiran</span>
                    <span
                        class="badge bg-<?= $attendanceRate >= 80 ? 'success' : ($attendanceRate >= 60 ? 'warning' : 'danger') ?>"><?= $attendanceRate ?>%</span>
                </div>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar bg-<?= $attendanceRate >= 80 ? 'success' : ($attendanceRate >= 60 ? 'warning' : 'danger') ?>"
                        style="width: <?= $attendanceRate ?>%"></div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Today's Events -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-danger text-white">
                        <i class="bi bi-calendar-check me-2"></i>Kegiatan Hari Ini
                    </div>
                    <div class="card-body p-0">
                        <?php if ($todayEvents->num_rows > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while ($e = $todayEvents->fetch_assoc()): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong><?= htmlspecialchars($e['nama_kegiatan']) ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="bi bi-clock me-1"></i>
                                                    <?= date('H:i', strtotime($e['jam_mulai'])) ?> -
                                                    <?= date('H:i', strtotime($e['jam_selesai'])) ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <?php if ($e['my_status']): ?>
                                                    <span class="badge bg-<?= match ($e['my_status']) {
                                                        'Hadir' => 'success',
                                                        'Terlambat' => 'warning',
                                                        'Izin' => 'info',
                                                        'Sakit' => 'secondary',
                                                        default => 'danger'
                                                    } ?>"><?= $e['my_status'] ?></span>
                                                    <br>
                                                    <small class="text-muted">
                                                        Masuk: <?= date('H:i', strtotime($e['my_checkin'])) ?>
                                                        <?php if ($e['my_checkout']): ?>
                                                            <br>Pulang: <?= date('H:i', strtotime($e['my_checkout'])) ?>
                                                        <?php endif; ?>
                                                    </small>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Belum Absen</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-calendar-x display-4"></i>
                                <p class="mt-2 mb-0">Tidak ada kegiatan hari ini</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-center">
                        <a href="../scan.php" class="btn btn-danger">
                            <i class="bi bi-qr-code-scan me-2"></i>Scan QR Absensi
                        </a>
                    </div>
                </div>
            </div>

            <!-- Attendance History -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="bi bi-clock-history me-2"></i>Riwayat Absensi
                    </div>
                    <div class="card-body p-0">
                        <?php if ($recentAttendance->num_rows > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while ($a = $recentAttendance->fetch_assoc()): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?= htmlspecialchars($a['nama_kegiatan']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= date('d M Y', strtotime($a['tanggal'])) ?></small>
                                        </div>
                                        <span class="badge bg-<?= match ($a['status']) {
                                            'Hadir' => 'success',
                                            'Terlambat' => 'warning',
                                            'Izin' => 'info',
                                            'Sakit' => 'secondary',
                                            default => 'danger'
                                        } ?>"><?= $a['status'] ?></span>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-inbox display-4"></i>
                                <p class="mt-2 mb-0">Belum ada riwayat absensi</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Events -->
        <?php if ($upcomingEvents->num_rows > 0): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <i class="bi bi-calendar-event me-2"></i>Kegiatan Mendatang
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Kegiatan</th>
                                    <th>Tanggal</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($e = $upcomingEvents->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($e['nama_kegiatan']) ?></td>
                                        <td><?= date('d M Y', strtotime($e['tanggal'])) ?></td>
                                        <td><?= date('H:i', strtotime($e['jam_mulai'])) ?> -
                                            <?= date('H:i', strtotime($e['jam_selesai'])) ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../views/footer.php'; ?>