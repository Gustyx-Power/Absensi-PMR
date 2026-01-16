<?php
require_once '../config/auth_check.php';
require_once '../config/database.php';

requireRole(['Pembina', 'Pengurus']);

$pageTitle = 'Kelola Kegiatan - Sistem Absensi PMR';
$baseUrl = '../';
$currentPage = 'kegiatan';

$action = $_GET['action'] ?? 'list';
$editEvent = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kegiatan = trim($_POST['nama_kegiatan'] ?? '');
    $tanggal = $_POST['tanggal'] ?? '';
    $jam_mulai = $_POST['jam_mulai'] ?? '';
    $tolerance_time = $_POST['tolerance_time'] ?? '';
    $jam_selesai = $_POST['jam_selesai'] ?? '';
    $batas_pulang = $_POST['batas_pulang'] ?? '';
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if (empty($nama_kegiatan) || empty($tanggal) || empty($jam_mulai) || empty($jam_selesai) || empty($tolerance_time) || empty($batas_pulang)) {
        $_SESSION['error'] = 'Semua field wajib harus diisi.';
    } else {
        if (isset($_POST['event_id']) && !empty($_POST['event_id'])) {
            $event_id = (int) $_POST['event_id'];
            $stmt = $conn->prepare("UPDATE events SET nama_kegiatan=?, tanggal=?, jam_mulai=?, tolerance_time=?, jam_selesai=?, batas_pulang=?, deskripsi=? WHERE id=?");
            $stmt->bind_param("sssssssi", $nama_kegiatan, $tanggal, $jam_mulai, $tolerance_time, $jam_selesai, $batas_pulang, $deskripsi, $event_id);
            $result = $stmt->execute();
            $_SESSION[$result ? 'success' : 'error'] = $result ? 'Kegiatan diperbarui.' : 'Gagal memperbarui.';
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO events (nama_kegiatan, tanggal, jam_mulai, tolerance_time, jam_selesai, batas_pulang, deskripsi) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $nama_kegiatan, $tanggal, $jam_mulai, $tolerance_time, $jam_selesai, $batas_pulang, $deskripsi);
            $result = $stmt->execute();
            $_SESSION[$result ? 'success' : 'error'] = $result ? 'Kegiatan ditambahkan.' : 'Gagal menambahkan.';
            $stmt->close();
        }
        header('Location: kegiatan.php');
        exit;
    }
}



// Handle delete
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete']);
    $result = $stmt->execute();
    $_SESSION[$result ? 'success' : 'error'] = $result ? 'Kegiatan dihapus.' : 'Gagal menghapus.';
    $stmt->close();
    header('Location: kegiatan.php');
    exit;
}

// Get event for editing
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $editEvent = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$editEvent) {
        $_SESSION['error'] = 'Kegiatan tidak ditemukan.';
        header('Location: kegiatan.php');
        exit;
    }
}

// Get all events
$events = $conn->query("SELECT * FROM events ORDER BY tanggal DESC, jam_mulai DESC");

include '../views/header.php';
?>

<section class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1"><i class="bi bi-calendar-event text-danger me-2"></i>Kelola Kegiatan</h2>
                <p class="text-muted mb-0">Tambah, edit, dan kelola kegiatan PMR</p>
            </div>
            <?php if ($action === 'list'): ?>
                <a href="?action=add" class="btn btn-danger"><i class="bi bi-plus-circle me-2"></i>Tambah Kegiatan</a>
            <?php else: ?>
                <a href="kegiatan.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Kembali</a>
            <?php endif; ?>
        </div>

        <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit Form -->
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <i class="bi bi-<?= $action === 'edit' ? 'pencil' : 'plus-circle' ?> me-2"></i>
                    <?= $action === 'edit' ? 'Edit Kegiatan' : 'Tambah Kegiatan Baru' ?>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($editEvent): ?><input type="hidden" name="event_id"
                                value="<?= $editEvent['id'] ?>"><?php endif; ?>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nama Kegiatan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nama_kegiatan"
                                    value="<?= htmlspecialchars($editEvent['nama_kegiatan'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="tanggal"
                                    value="<?= $editEvent['tanggal'] ?? date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jam Mulai <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" name="jam_mulai" id="jam_mulai"
                                    value="<?= $editEvent['jam_mulai'] ?? '08:00' ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Toleransi Masuk <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" name="tolerance_time" id="tolerance_time"
                                    value="<?= $editEvent['tolerance_time'] ?? '08:15' ?>" required>
                                <small class="text-muted">Batas tidak terlambat</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jam Selesai <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" name="jam_selesai" id="jam_selesai"
                                    value="<?= $editEvent['jam_selesai'] ?? '11:00' ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Batas Pulang <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" name="batas_pulang" id="batas_pulang"
                                    value="<?= $editEvent['batas_pulang'] ?? '10:45' ?>" required>
                                <small class="text-muted">Absen pulang mulai jam</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Deskripsi</label>
                                <textarea class="form-control" name="deskripsi"
                                    rows="2"><?= htmlspecialchars($editEvent['deskripsi'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-danger"><i
                                        class="bi bi-check-lg me-2"></i>Simpan</button>
                                <a href="kegiatan.php" class="btn btn-outline-secondary ms-2">Batal</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Events List -->
            <div class="card">
                <div class="card-header"><i class="bi bi-list-ul me-2"></i>Daftar Kegiatan</div>
                <div class="card-body p-0">
                    <?php if ($events->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th width="40">#</th>
                                        <th>Nama Kegiatan</th>
                                        <th width="110">Tanggal</th>
                                        <th width="120">Waktu</th>
                                        <th width="90">Status</th>
                                        <th width="180">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1;
                                    while ($e = $events->fetch_assoc()):
                                        $eventDate = strtotime($e['tanggal']);
                                        $today = strtotime(date('Y-m-d'));
                                        if ($eventDate < $today) {
                                            $status = '<span class="badge bg-secondary">Selesai</span>';
                                        } elseif ($eventDate == $today) {
                                            $status = '<span class="badge bg-success">Hari Ini</span>';
                                        } else {
                                            $status = '<span class="badge bg-primary">Mendatang</span>';
                                        }
                                        ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><strong><?= htmlspecialchars($e['nama_kegiatan']) ?></strong></td>
                                            <td><?= date('d M Y', strtotime($e['tanggal'])) ?></td>
                                            <td><?= date('H:i', strtotime($e['jam_mulai'])) ?> -
                                                <?= date('H:i', strtotime($e['jam_selesai'])) ?>
                                            </td>
                                            <td><?= $status ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary"
                                                    onclick="showQR(<?= $e['id'] ?>, '<?= htmlspecialchars(addslashes($e['nama_kegiatan'])) ?>')"
                                                    title="Show QR">
                                                    <i class="bi bi-qr-code"></i>
                                                </button>
                                                <a href="absensi.php?event_id=<?= $e['id'] ?>" class="btn btn-sm btn-success"
                                                    title="Lihat Absensi"><i class="bi bi-clipboard-check"></i></a>
                                                <a href="?action=edit&id=<?= $e['id'] ?>" class="btn btn-sm btn-warning"
                                                    title="Edit"><i class="bi bi-pencil"></i></a>
                                                <a href="?delete=<?= $e['id'] ?>" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Hapus kegiatan ini?')" title="Hapus"><i
                                                        class="bi bi-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-calendar-x display-1 text-muted"></i>
                            <p class="text-muted mt-3">Belum ada kegiatan</p>
                            <a href="?action=add" class="btn btn-danger"><i class="bi bi-plus-circle me-2"></i>Tambah
                                Kegiatan</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
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
                <p class="text-muted mt-3 mb-0 small">
                    <i class="bi bi-info-circle me-1"></i>Tampilkan QR ini kepada anggota untuk scan
                </p>
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

<!-- QRCode Library (different CDN that works better) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    let qrCodeInstance = null;
    let currentEventName = '';

    function showQR(eventId, eventName) {
        currentEventName = eventName;
        document.getElementById('qrEventName').textContent = eventName;

        // Clear previous QR
        const container = document.getElementById('qrcode-container');
        container.innerHTML = '';

        // Generate QR data
        const qrData = JSON.stringify({ event_id: eventId });

        // Create new QR code
        qrCodeInstance = new QRCode(container, {
            text: qrData,
            width: 256,
            height: 256,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        // Show modal
        new bootstrap.Modal(document.getElementById('modalQR')).show();
    }

    function downloadQR() {
        const container = document.getElementById('qrcode-container');
        const canvas = container.querySelector('canvas');
        const img = container.querySelector('img');

        let dataURL;
        if (canvas) {
            dataURL = canvas.toDataURL('image/png');
        } else if (img) {
            // Create canvas from image
            const tempCanvas = document.createElement('canvas');
            tempCanvas.width = img.width;
            tempCanvas.height = img.height;
            const ctx = tempCanvas.getContext('2d');
            ctx.drawImage(img, 0, 0);
            dataURL = tempCanvas.toDataURL('image/png');
        } else {
            alert('QR Code tidak tersedia');
            return;
        }

        // Download
        const link = document.createElement('a');
        link.download = 'QR_' + currentEventName.replace(/[^a-zA-Z0-9]/g, '_') + '.png';
        link.href = dataURL;
        link.click();
    }
</script>

<?php include '../views/footer.php'; ?>