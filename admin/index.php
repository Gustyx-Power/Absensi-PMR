<?php
require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Dashboard - Sistem Absensi PMR';
$baseUrl = '../';
$currentPage = 'dashboard';

$jabatan = $_SESSION['jabatan'];
$user_id = $_SESSION['user_id'];
$isAdmin = in_array($jabatan, ['Pembina', 'Pengurus']);

if ($isAdmin) {
    // ========================================
    // ADMIN DATA
    // ========================================
    $stats = [
        'members' => $conn->query("SELECT COUNT(*) as t FROM users WHERE jabatan IN ('Anggota','Pengurus')")->fetch_assoc()['t'],
        'events' => $conn->query("SELECT COUNT(*) as t FROM events")->fetch_assoc()['t'],
        'today_attendance' => $conn->query("
            SELECT COUNT(*) as t FROM attendance a 
            JOIN events e ON a.event_id = e.id 
            WHERE e.tanggal = CURDATE() AND a.status IN ('Hadir','Terlambat')
        ")->fetch_assoc()['t']
    ];

    $recentEvents = $conn->query("SELECT * FROM events ORDER BY tanggal DESC, jam_mulai DESC LIMIT 5");

} else {
    // ========================================
    // MEMBER DATA
    // ========================================
    $myStats = $conn->query("
        SELECT 
            SUM(CASE WHEN status IN ('Hadir','Terlambat') THEN 1 ELSE 0 END) as hadir,
            SUM(CASE WHEN status = 'Alpha' THEN 1 ELSE 0 END) as alpha
        FROM attendance WHERE user_id = $user_id
    ")->fetch_assoc();

    $myHistory = $conn->query("
        SELECT a.status, a.waktu_absen, e.nama_kegiatan, e.tanggal
        FROM attendance a
        JOIN events e ON a.event_id = e.id
        WHERE a.user_id = $user_id
        ORDER BY e.tanggal DESC, a.created_at DESC
        LIMIT 5
    ");

    // Get today's event status
    $todayEvent = $conn->query("
        SELECT e.*, 
               (SELECT status FROM attendance WHERE user_id = $user_id AND event_id = e.id) as my_status
        FROM events e 
        WHERE e.tanggal = CURDATE()
        ORDER BY e.jam_mulai ASC
        LIMIT 1
    ")->fetch_assoc();
}

include __DIR__ . '/../views/header.php';
?>

<?php if ($isAdmin): ?>
    <!-- ========================================
     ADMIN DASHBOARD
======================================== -->
    <section class="py-4">
        <div class="container">
            <div class="mb-4">
                <h2><i class="bi bi-speedometer2 text-danger me-2"></i>Dashboard Admin</h2>
                <p class="text-muted">Selamat datang, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong>!</p>
            </div>

            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body text-center py-4">
                            <i class="bi bi-people display-4 opacity-50"></i>
                            <h2 class="my-2"><?= $stats['members'] ?></h2>
                            <p class="mb-0">Total Anggota</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body text-center py-4">
                            <i class="bi bi-calendar-event display-4 opacity-50"></i>
                            <h2 class="my-2"><?= $stats['events'] ?></h2>
                            <p class="mb-0">Total Kegiatan</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-warning text-dark h-100">
                        <div class="card-body text-center py-4">
                            <i class="bi bi-check-circle display-4 opacity-50"></i>
                            <h2 class="my-2"><?= $stats['today_attendance'] ?></h2>
                            <p class="mb-0">Kehadiran Hari Ini</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Quick Actions -->
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header bg-danger text-white">
                            <i class="bi bi-lightning me-2"></i>Aksi Cepat
                        </div>
                        <div class="card-body d-grid gap-2">
                            <a href="kegiatan.php?action=add" class="btn btn-outline-danger">
                                <i class="bi bi-plus-circle me-2"></i>Tambah Kegiatan
                            </a>
                            <a href="absensi.php" class="btn btn-outline-primary">
                                <i class="bi bi-clipboard-check me-2"></i>Kelola Absensi
                            </a>
                            <a href="laporan.php" class="btn btn-outline-success">
                                <i class="bi bi-file-earmark-excel me-2"></i>Download Laporan
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Recent Events -->
                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-list-ul me-2"></i>5 Kegiatan Terakhir</span>
                            <a href="kegiatan.php" class="btn btn-sm btn-light">Lihat Semua</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if ($recentEvents->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Kegiatan</th>
                                                <th>Tanggal</th>
                                                <th>Waktu</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($e = $recentEvents->fetch_assoc()):
                                                $d = strtotime($e['tanggal']);
                                                $t = strtotime(date('Y-m-d'));
                                                $badge = $d < $t ? 'secondary' : ($d == $t ? 'success' : 'primary');
                                                $label = $d < $t ? 'Selesai' : ($d == $t ? 'Hari Ini' : 'Mendatang');
                                                ?>
                                                <tr>
                                                    <td><strong><?= htmlspecialchars($e['nama_kegiatan']) ?></strong></td>
                                                    <td><?= date('d M Y', $d) ?></td>
                                                    <td><?= date('H:i', strtotime($e['jam_mulai'])) ?></td>
                                                    <td><span class="badge bg-<?= $badge ?>"><?= $label ?></span></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">Belum ada kegiatan</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php else: ?>
    <!-- ========================================
     MEMBER DASHBOARD (Mobile-Friendly)
======================================== -->
    <section class="py-4">
        <div class="container">
            <!-- Greeting Card -->
            <div class="card bg-danger bg-gradient text-white shadow mb-4">
                <div class="card-body text-center py-4">
                    <h3 class="mb-2 fw-bold">Halo, <?= htmlspecialchars($_SESSION['nama']) ?>!</h3>
                    <p class="mb-2 fs-5">Semangat Pagi! 💪</p>
                    <?php if (!empty($_SESSION['kelas'])): ?>
                        <span class="badge bg-light text-danger fs-6"><?= htmlspecialchars($_SESSION['kelas']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Today's Event Status -->
            <?php if ($todayEvent): ?>
                <div class="card mb-4 border-<?= $todayEvent['my_status'] ? 'success' : 'warning' ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">Kegiatan Hari Ini</small>
                                <h5 class="mb-0"><?= htmlspecialchars($todayEvent['nama_kegiatan']) ?></h5>
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i>
                                    <?= date('H:i', strtotime($todayEvent['jam_mulai'])) ?> -
                                    <?= date('H:i', strtotime($todayEvent['jam_selesai'])) ?>
                                </small>
                            </div>
                            <?php if ($todayEvent['my_status']): ?>
                                <span class="badge bg-<?= match ($todayEvent['my_status']) {
                                    'Hadir' => 'success', 'Terlambat' => 'warning', default => 'danger'
                                } ?> fs-6"><?= $todayEvent['my_status'] ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Belum Absen</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Main Action: SCAN QR -->
            <div class="d-grid mb-4">
                <a href="../scan.php" class="btn btn-danger btn-lg py-4 shadow">
                    <i class="bi bi-qr-code-scan display-4 d-block mb-2"></i>
                    <span class="fs-4 fw-bold">SCAN QR CODE</span>
                </a>
            </div>

            <!-- Personal Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="card text-center py-3 border-success">
                        <h2 class="text-success mb-0"><?= $myStats['hadir'] ?? 0 ?></h2>
                        <small class="text-muted">Kali Hadir</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card text-center py-3 border-danger">
                        <h2 class="text-danger mb-0"><?= $myStats['alpha'] ?? 0 ?></h2>
                        <small class="text-muted">Kali Alpha</small>
                    </div>
                </div>
            </div>

            <!-- My History -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock-history me-2"></i>Riwayat Absensi Saya
                </div>
                <div class="list-group list-group-flush">
                    <?php if ($myHistory->num_rows > 0): ?>
                        <?php while ($h = $myHistory->fetch_assoc()): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= htmlspecialchars($h['nama_kegiatan']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= date('d M Y', strtotime($h['tanggal'])) ?></small>
                                </div>
                                <span class="badge bg-<?= match ($h['status']) {
                                    'Hadir' => 'success',
                                    'Terlambat' => 'warning',
                                    'Izin' => 'info',
                                    'Sakit' => 'secondary',
                                    default => 'danger'
                                } ?>"><?= $h['status'] ?></span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="list-group-item text-center text-muted py-4">
                            <i class="bi bi-inbox display-4"></i>
                            <p class="mt-2 mb-0">Belum ada riwayat absensi</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php include __DIR__ . '/../views/footer.php'; ?>