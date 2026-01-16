<?php
require_once '../config/auth_check.php';
require_once '../config/database.php';

requireRole(['Pembina', 'Pengurus']);

$event_id = (int) ($_GET['id'] ?? 0);
if (!$event_id) {
    $_SESSION['error'] = 'Event ID tidak valid.';
    header('Location: kegiatan.php');
    exit;
}

// Get event details
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    $_SESSION['error'] = 'Kegiatan tidak ditemukan.';
    header('Location: kegiatan.php');
    exit;
}

$pageTitle = $event['nama_kegiatan'] . ' - Sistem Absensi PMR';
$baseUrl = '../';
$currentPage = 'kegiatan';

// Handle Generate Alpha
if (isset($_POST['generate_alpha'])) {
    // Find all users who don't have attendance for this event
    $sql = "INSERT INTO attendance (user_id, event_id, status, waktu_absen)
            SELECT u.id, ?, 'Alpha', NOW()
            FROM users u
            WHERE u.jabatan IN ('Anggota', 'Pengurus')
            AND u.id NOT IN (SELECT user_id FROM attendance WHERE event_id = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $event_id, $event_id);
    $result = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($result) {
        $_SESSION['success'] = "Berhasil menandai $affected anggota sebagai Alpha.";
    } else {
        $_SESSION['error'] = 'Gagal generate Alpha.';
    }
    header("Location: event_detail.php?id=$event_id");
    exit;
}

// Get attendance stats
$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN status = 'Terlambat' THEN 1 ELSE 0 END) as terlambat,
        SUM(CASE WHEN status = 'Izin' THEN 1 ELSE 0 END) as izin,
        SUM(CASE WHEN status = 'Sakit' THEN 1 ELSE 0 END) as sakit,
        SUM(CASE WHEN status = 'Alpha' THEN 1 ELSE 0 END) as alpha
    FROM attendance WHERE event_id = $event_id
")->fetch_assoc();

// Get attendance records
$attendances = $conn->query("
    SELECT a.*, u.nis, u.nama, u.kelas
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    WHERE a.event_id = $event_id
    ORDER BY a.status, u.nama
");

// Count members without attendance
$notAttended = $conn->query("
    SELECT COUNT(*) as count FROM users 
    WHERE jabatan IN ('Anggota', 'Pengurus')
    AND id NOT IN (SELECT user_id FROM attendance WHERE event_id = $event_id)
")->fetch_assoc()['count'];

include '../views/header.php';
?>

<section class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <a href="kegiatan.php" class="text-muted text-decoration-none small">
                    <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar Kegiatan
                </a>
                <h2 class="mt-2 mb-1">
                    <?= htmlspecialchars($event['nama_kegiatan']) ?>
                </h2>
                <p class="text-muted mb-0">
                    <i class="bi bi-calendar me-1"></i>
                    <?= date('d F Y', strtotime($event['tanggal'])) ?>
                    <span class="mx-2">|</span>
                    <i class="bi bi-clock me-1"></i>
                    <?= date('H:i', strtotime($event['jam_mulai'])) ?> -
                    <?= date('H:i', strtotime($event['jam_selesai'])) ?>
                    <span class="mx-2">|</span>
                    <i class="bi bi-alarm me-1"></i>Toleransi:
                    <?= date('H:i', strtotime($event['tolerance_time'])) ?>
                </p>
            </div>
            <div>
                <button type="button" class="btn btn-primary"
                    onclick="showQR(<?= $event['id'] ?>, '<?= htmlspecialchars(addslashes($event['nama_kegiatan'])) ?>')">
                    <i class="bi bi-qr-code me-2"></i>Tampilkan QR
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-4 col-md-2">
                <div class="card text-center py-2 border-success">
                    <h3 class="text-success mb-0">
                        <?= $stats['hadir'] ?>
                    </h3>
                    <small class="text-muted">Hadir</small>
                </div>
            </div>
            <div class="col-4 col-md-2">
                <div class="card text-center py-2 border-warning">
                    <h3 class="text-warning mb-0">
                        <?= $stats['terlambat'] ?>
                    </h3>
                    <small class="text-muted">Terlambat</small>
                </div>
            </div>
            <div class="col-4 col-md-2">
                <div class="card text-center py-2 border-info">
                    <h3 class="text-info mb-0">
                        <?= $stats['izin'] ?>
                    </h3>
                    <small class="text-muted">Izin</small>
                </div>
            </div>
            <div class="col-4 col-md-2">
                <div class="card text-center py-2 border-secondary">
                    <h3 class="text-secondary mb-0">
                        <?= $stats['sakit'] ?>
                    </h3>
                    <small class="text-muted">Sakit</small>
                </div>
            </div>
            <div class="col-4 col-md-2">
                <div class="card text-center py-2 border-danger">
                    <h3 class="text-danger mb-0">
                        <?= $stats['alpha'] ?>
                    </h3>
                    <small class="text-muted">Alpha</small>
                </div>
            </div>
            <div class="col-4 col-md-2">
                <div class="card text-center py-2 bg-dark text-white">
                    <h3 class="mb-0">
                        <?= $stats['total'] ?>
                    </h3>
                    <small>Total</small>
                </div>
            </div>
        </div>

        <!-- Generate Alpha Button -->
        <?php if ($notAttended > 0): ?>
            <div class="alert alert-warning d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>
                        <?= $notAttended ?>
                    </strong> anggota belum tercatat absensi.
                </div>
                <form method="POST" onsubmit="return confirm('Tandai <?= $notAttended ?> anggota sebagai Alpha?')">
                    <button type="submit" name="generate_alpha" class="btn btn-danger">
                        <i class="bi bi-person-x me-2"></i>Tutup Absensi / Generate Alpha
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Attendance Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-check me-2"></i>Daftar Hadir</span>
            </div>
            <div class="card-body p-0">
                <?php if ($attendances->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>NIS</th>
                                    <th>Nama</th>
                                    <th>Kelas</th>
                                    <th>Status</th>
                                    <th>Jam Masuk</th>
                                    <th>Jam Pulang</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1;
                                while ($a = $attendances->fetch_assoc()):
                                    $statusBadge = match ($a['status']) {
                                        'Hadir' => 'success',
                                        'Terlambat' => 'warning',
                                        'Izin' => 'info',
                                        'Sakit' => 'secondary',
                                        'Alpha' => 'danger',
                                        default => 'dark'
                                    };
                                    ?>
                                    <tr>
                                        <td>
                                            <?= $no++ ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($a['nis']) ?>
                                        </td>
                                        <td><strong>
                                                <?= htmlspecialchars($a['nama']) ?>
                                            </strong></td>
                                        <td>
                                            <?= htmlspecialchars($a['kelas']) ?>
                                        </td>
                                        <td><span class="badge bg-<?= $statusBadge ?>">
                                                <?= $a['status'] ?>
                                            </span></td>
                                        <td>
                                            <?= $a['waktu_absen'] ? date('H:i:s', strtotime($a['waktu_absen'])) : '-' ?>
                                        </td>
                                        <td>
                                            <?= $a['clock_out'] ? date('H:i:s', strtotime($a['clock_out'])) : '-' ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox display-1"></i>
                        <p class="mt-3">Belum ada data absensi</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- QR Code Modal -->
<div class="modal fade" id="modalQR" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-qr-code me-2"></i>QR Code Absensi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <h5 id="qrEventName" class="text-danger mb-3"></h5>
                <div id="qrcode-container" style="display:inline-block;"></div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-success" onclick="downloadQR()">
                    <i class="bi bi-download me-2"></i>Download PNG
                </button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    let qrCodeInstance = null;
    let currentEventName = '';

    function showQR(eventId, eventName) {
        currentEventName = eventName;
        document.getElementById('qrEventName').textContent = eventName;
        const container = document.getElementById('qrcode-container');
        container.innerHTML = '';

        qrCodeInstance = new QRCode(container, {
            text: JSON.stringify({ event_id: eventId }),
            width: 256,
            height: 256,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        new bootstrap.Modal(document.getElementById('modalQR')).show();
    }

    function downloadQR() {
        const container = document.getElementById('qrcode-container');
        const img = container.querySelector('img') || container.querySelector('canvas');
        if (!img) return;

        let dataURL;
        if (img.tagName === 'CANVAS') {
            dataURL = img.toDataURL('image/png');
        } else {
            const canvas = document.createElement('canvas');
            canvas.width = img.width;
            canvas.height = img.height;
            canvas.getContext('2d').drawImage(img, 0, 0);
            dataURL = canvas.toDataURL('image/png');
        }

        const link = document.createElement('a');
        link.download = 'QR_' + currentEventName.replace(/[^a-zA-Z0-9]/g, '_') + '.png';
        link.href = dataURL;
        link.click();
    }
</script>

<?php include '../views/footer.php'; ?>