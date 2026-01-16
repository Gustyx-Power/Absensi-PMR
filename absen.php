<?php
// Vercel/Serverless session fix
if (isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL'])) {
    session_save_path('/tmp');
}
session_start();
require_once 'config/database.php';

$pageTitle = 'Absensi - Sistem Absensi PMR';
$baseUrl = '';
$currentPage = 'absensi';

$message = '';
$messageType = '';
$hasAttended = false;
$selectedEvent = null;

// Get today's active events
$todayEvents = $conn->query("
    SELECT * FROM events 
    WHERE tanggal = CURDATE() 
    ORDER BY jam_mulai ASC
");

// Check if user selected an event
$selectedEventId = $_GET['event'] ?? $_POST['event_id'] ?? null;

if ($selectedEventId) {
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ? AND tanggal = CURDATE()");
    $stmt->bind_param("i", $selectedEventId);
    $stmt->execute();
    $selectedEvent = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Check if user already attended this event
if (isset($_SESSION['user_id']) && $selectedEvent) {
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE user_id = ? AND event_id = ?");
    $stmt->bind_param("ii", $_SESSION['user_id'], $selectedEvent['id']);
    $stmt->execute();
    $existingAttendance = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existingAttendance) {
        $hasAttended = true;
    }
}

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
    if (!isset($_SESSION['is_login']) || $_SESSION['is_login'] !== true) {
        $message = 'Anda harus login terlebih dahulu untuk melakukan absensi.';
        $messageType = 'danger';
    } elseif (!$selectedEvent) {
        $message = 'Kegiatan tidak valid atau tidak tersedia hari ini.';
        $messageType = 'danger';
    } elseif ($hasAttended) {
        $message = 'Anda sudah melakukan absensi untuk kegiatan ini.';
        $messageType = 'warning';
    } else {
        $status = $_POST['status'] ?? 'Hadir';
        $validStatuses = ['Hadir', 'Izin', 'Sakit'];

        if (!in_array($status, $validStatuses)) {
            $status = 'Hadir';
        }

        $stmt = $conn->prepare("INSERT INTO attendance (user_id, event_id, status, waktu_absen) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $_SESSION['user_id'], $selectedEvent['id'], $status);

        if ($stmt->execute()) {
            $message = 'Absensi berhasil dicatat! Status: ' . $status;
            $messageType = 'success';
            $hasAttended = true;
        } else {
            $message = 'Gagal mencatat absensi. Silakan coba lagi.';
            $messageType = 'danger';
        }
        $stmt->close();
    }
}

include 'views/header.php';
?>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Page Header -->
                <div class="text-center mb-4">
                    <h2 class="mb-2">
                        <i class="bi bi-clipboard-check text-danger me-2"></i>Absensi Kegiatan
                    </h2>
                    <p class="text-muted">Catat kehadiran Anda pada kegiatan PMR hari ini</p>
                    <p class="mb-0">
                        <i class="bi bi-calendar3 me-1"></i>
                        <strong><?= strftime('%A, %d %B %Y', strtotime('today')) ?></strong>
                    </p>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                        <i
                            class="bi bi-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'x-circle') ?> me-2"></i>
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!isset($_SESSION['is_login']) || $_SESSION['is_login'] !== true): ?>
                    <!-- Not Logged In -->
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-person-lock display-1 text-muted mb-3"></i>
                            <h4>Login Diperlukan</h4>
                            <p class="text-muted mb-4">Silakan login terlebih dahulu untuk melakukan absensi.</p>
                            <a href="login.php" class="btn btn-danger btn-lg">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Login Sekarang
                            </a>
                        </div>
                    </div>
                <?php elseif ($todayEvents->num_rows === 0): ?>
                    <!-- No Events Today -->
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-calendar-x display-1 text-muted mb-3"></i>
                            <h4>Tidak Ada Kegiatan</h4>
                            <p class="text-muted mb-0">Tidak ada kegiatan PMR yang dijadwalkan untuk hari ini.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Event Selection -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-danger text-white">
                            <i class="bi bi-calendar-event me-2"></i>Pilih Kegiatan
                        </div>
                        <div class="card-body">
                            <form action="" method="GET" id="eventSelectForm">
                                <div class="mb-3">
                                    <label for="event" class="form-label fw-semibold">Kegiatan Hari Ini</label>
                                    <select class="form-select form-select-lg" name="event" id="event"
                                        onchange="this.form.submit()">
                                        <option value="">-- Pilih Kegiatan --</option>
                                        <?php while ($event = $todayEvents->fetch_assoc()): ?>
                                            <option value="<?= $event['id'] ?>" <?= ($selectedEventId == $event['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($event['nama_kegiatan']) ?>
                                                (<?= date('H:i', strtotime($event['jam_mulai'])) ?> -
                                                <?= date('H:i', strtotime($event['jam_selesai'])) ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>

                    <?php if ($selectedEvent): ?>
                        <!-- Attendance Card -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="bi bi-calendar-check me-2"></i>
                                    <?= htmlspecialchars($selectedEvent['nama_kegiatan']) ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-6">
                                        <small class="text-muted">Waktu</small>
                                        <p class="mb-0 fw-semibold">
                                            <i class="bi bi-clock me-1"></i>
                                            <?= date('H:i', strtotime($selectedEvent['jam_mulai'])) ?> -
                                            <?= date('H:i', strtotime($selectedEvent['jam_selesai'])) ?>
                                        </p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Tanggal</small>
                                        <p class="mb-0 fw-semibold">
                                            <i class="bi bi-calendar3 me-1"></i>
                                            <?= date('d M Y', strtotime($selectedEvent['tanggal'])) ?>
                                        </p>
                                    </div>
                                </div>

                                <?php if ($selectedEvent['deskripsi']): ?>
                                    <p class="text-muted mb-4"><?= htmlspecialchars($selectedEvent['deskripsi']) ?></p>
                                <?php endif; ?>

                                <?php if ($hasAttended): ?>
                                    <!-- Already Attended -->
                                    <div class="text-center py-4">
                                        <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                            style="width: 100px; height: 100px;">
                                            <i class="bi bi-check-lg display-4"></i>
                                        </div>
                                        <h4 class="text-success">Absensi Tercatat!</h4>
                                        <p class="text-muted mb-0">
                                            Anda sudah melakukan absensi untuk kegiatan ini.
                                            <br>
                                            <small>Status: <strong><?= $existingAttendance['status'] ?></strong> | Waktu:
                                                <?= date('H:i:s', strtotime($existingAttendance['waktu_absen'])) ?></small>
                                        </p>
                                    </div>
                                <?php else: ?>
                                    <!-- Attendance Form -->
                                    <form action="?event=<?= $selectedEvent['id'] ?>" method="POST">
                                        <input type="hidden" name="event_id" value="<?= $selectedEvent['id'] ?>">

                                        <div class="text-center mb-4">
                                            <p class="mb-3"><strong>Pilih Status Kehadiran:</strong></p>
                                            <div class="btn-group btn-group-lg" role="group">
                                                <input type="radio" class="btn-check" name="status" id="status_hadir" value="Hadir"
                                                    checked>
                                                <label class="btn btn-outline-success" for="status_hadir">
                                                    <i class="bi bi-check-circle me-1"></i>Hadir
                                                </label>

                                                <input type="radio" class="btn-check" name="status" id="status_izin" value="Izin">
                                                <label class="btn btn-outline-info" for="status_izin">
                                                    <i class="bi bi-envelope me-1"></i>Izin
                                                </label>

                                                <input type="radio" class="btn-check" name="status" id="status_sakit" value="Sakit">
                                                <label class="btn btn-outline-warning" for="status_sakit">
                                                    <i class="bi bi-thermometer me-1"></i>Sakit
                                                </label>
                                            </div>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" name="submit_attendance" class="btn btn-danger btn-lg py-3">
                                                <i class="bi bi-check2-circle me-2 fs-4"></i>
                                                <span class="fs-5">Konfirmasi Absensi</span>
                                            </button>
                                        </div>

                                        <p class="text-center text-muted mt-3 small">
                                            <i class="bi bi-person me-1"></i>Login sebagai:
                                            <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong>
                                            (<?= htmlspecialchars($_SESSION['nis']) ?>)
                                        </p>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include 'views/footer.php'; ?>