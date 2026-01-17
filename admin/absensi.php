<?php
require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Manajemen Absensi - Sistem Absensi PMR';
$baseUrl = '../';
$currentPage = 'absensi';

$filterEvent = $_GET['event_id'] ?? '';
$filterStatus = $_GET['status'] ?? '';

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

$sql = "SELECT a.*, u.nama, u.nis, u.kelas, e.nama_kegiatan, e.tanggal
        FROM attendance a
        JOIN users u ON a.user_id = u.id
        JOIN events e ON a.event_id = e.id
        WHERE $whereClause ORDER BY a.created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $attendanceRecords = $stmt->get_result();
} else {
    $attendanceRecords = $conn->query($sql);
}

$allEvents = $conn->query("SELECT id, nama_kegiatan, tanggal FROM events ORDER BY tanggal DESC");
$allUsers = $conn->query("SELECT id, nis, nama, kelas FROM users WHERE jabatan IN ('Anggota', 'Pengurus') ORDER BY nama");

// Handle manual entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_attendance'])) {
    requireRole(['Pembina', 'Pengurus']);
    $user_id = (int) $_POST['user_id'];
    $event_id = (int) $_POST['event_id'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("SELECT id FROM attendance WHERE user_id = ? AND event_id = ?");
    $stmt->bind_param("ii", $user_id, $event_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        $stmt = $conn->prepare("UPDATE attendance SET status = ?, waktu_absen = NOW() WHERE user_id = ? AND event_id = ?");
        $stmt->bind_param("sii", $status, $user_id, $event_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO attendance (user_id, event_id, status, waktu_absen) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $user_id, $event_id, $status);
    }

    $result = $stmt->execute();
    $_SESSION[$result ? 'success' : 'error'] = $result ? 'Absensi berhasil dicatat.' : 'Gagal mencatat absensi.';
    $stmt->close();
    header('Location: absensi.php?event_id=' . $event_id);
    exit;
}

// Handle delete
if (isset($_GET['delete']) && hasRole(['Pembina', 'Pengurus'])) {
    $delete_id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM attendance WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $result = $stmt->execute();
    $_SESSION[$result ? 'success' : 'error'] = $result ? 'Data dihapus.' : 'Gagal menghapus.';
    $stmt->close();
    header('Location: absensi.php' . ($filterEvent ? '?event_id=' . $filterEvent : ''));
    exit;
}

include __DIR__ . '/../views/header.php';
?>

<section class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1"><i class="bi bi-clipboard-check text-danger me-2"></i>Manajemen Absensi</h2>
                <p class="text-muted mb-0">Kelola kehadiran anggota PMR</p>
            </div>
            <?php if (hasRole(['Pembina', 'Pengurus'])): ?>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-circle me-2"></i>Input Manual
                </button>
            <?php endif; ?>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">Kegiatan</label>
                        <select class="form-select" name="event_id">
                            <option value="">Semua</option>
                            <?php while ($e = $allEvents->fetch_assoc()): ?>
                                <option value="<?= $e['id'] ?>" <?= $filterEvent == $e['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($e['nama_kegiatan']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">Semua</option>
                            <?php foreach (['Hadir', 'Izin', 'Sakit', 'Alpha'] as $s): ?>
                                <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>>
                                    <?= $s ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="absensi.php" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-table me-2"></i>Data Absensi</div>
            <div class="card-body p-0">
                <?php if ($attendanceRecords->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama</th>
                                    <th>NIS</th>
                                    <th>Kegiatan</th>
                                    <th>Status</th>
                                    <th>Waktu</th>
                                    <?php if (hasRole(['Pembina', 'Pengurus'])): ?>
                                        <th>Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1;
                                while ($a = $attendanceRecords->fetch_assoc()):
                                    $badge = match ($a['status']) { 'Hadir' => 'success', 'Izin' => 'info', 'Sakit' => 'warning', 'Alpha' => 'danger', default => 'secondary'}; ?>
                                    <tr>
                                        <td>
                                            <?= $no++ ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($a['nama']) ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($a['nis']) ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($a['nama_kegiatan']) ?>
                                        </td>
                                        <td><span class="badge bg-<?= $badge ?>">
                                                <?= $a['status'] ?>
                                            </span></td>
                                        <td>
                                            <?= $a['waktu_absen'] ? date('H:i', strtotime($a['waktu_absen'])) : '-' ?>
                                        </td>
                                        <?php if (hasRole(['Pembina', 'Pengurus'])): ?>
                                            <td><a href="?delete=<?= $a['id'] ?>" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted"><i class="bi bi-clipboard-x display-1"></i>
                        <p>Tidak ada data</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php if (hasRole(['Pembina', 'Pengurus'])): ?>
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Input Absensi</h5><button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Kegiatan</label><select class="form-select" name="event_id"
                            required>
                            <?php $allEvents->data_seek(0);
                            while ($e = $allEvents->fetch_assoc()): ?>
                                <option value="<?= $e['id'] ?>">
                                    <?= htmlspecialchars($e['nama_kegiatan']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select></div>
                    <div class="mb-3"><label class="form-label">Anggota</label><select class="form-select" name="user_id"
                            required>
                            <?php while ($u = $allUsers->fetch_assoc()): ?>
                                <option value="<?= $u['id'] ?>">
                                    <?= htmlspecialchars($u['nama']) ?> (
                                    <?= $u['nis'] ?>)
                                </option>
                            <?php endwhile; ?>
                        </select></div>
                    <div class="mb-3"><label class="form-label">Status</label><select class="form-select" name="status"
                            required>
                            <option value="Hadir">Hadir</option>
                            <option value="Izin">Izin</option>
                            <option value="Sakit">Sakit</option>
                            <option value="Alpha">Alpha</option>
                        </select></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Batal</button><button type="submit" name="add_attendance"
                        class="btn btn-danger">Simpan</button></div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../views/footer.php'; ?>