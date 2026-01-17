<?php
require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['Pembina', 'Pengurus']);

$pageTitle = 'Kelola Anggota - Sistem Absensi PMR';
$baseUrl = '../';
$currentPage = 'anggota';

$action = $_GET['action'] ?? 'list';
$editUser = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis = trim($_POST['nis'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $kelas = trim($_POST['kelas'] ?? '');
    $jabatan = $_POST['jabatan'] ?? 'Anggota';
    $password = $_POST['password'] ?? '';

    if (empty($nis) || empty($nama) || empty($kelas)) {
        $_SESSION['error'] = 'NIS, Nama, dan Kelas wajib diisi.';
    } else {
        if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
            // Update
            $user_id = (int) $_POST['user_id'];
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET nis=?, nama=?, kelas=?, jabatan=?, password=? WHERE id=?");
                $stmt->bind_param("sssssi", $nis, $nama, $kelas, $jabatan, $hashedPassword, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET nis=?, nama=?, kelas=?, jabatan=? WHERE id=?");
                $stmt->bind_param("ssssi", $nis, $nama, $kelas, $jabatan, $user_id);
            }
            $_SESSION[$stmt->execute() ? 'success' : 'error'] = $stmt->execute() ? 'Anggota berhasil diperbarui.' : 'Gagal memperbarui.';
            $stmt->close();
        } else {
            // Insert
            if (empty($password)) {
                $_SESSION['error'] = 'Password wajib diisi untuk anggota baru.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (nis, nama, kelas, jabatan, password) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $nis, $nama, $kelas, $jabatan, $hashedPassword);
                $_SESSION[$stmt->execute() ? 'success' : 'error'] = $stmt->execute() ? 'Anggota berhasil ditambahkan.' : 'NIS sudah terdaftar.';
                $stmt->close();
            }
        }
        header('Location: anggota.php');
        exit;
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND id != ?");
    $currentUserId = $_SESSION['user_id'];
    $deleteId = (int) $_GET['delete'];
    $stmt->bind_param("ii", $deleteId, $currentUserId);
    $_SESSION[$stmt->execute() && $stmt->affected_rows > 0 ? 'success' : 'error'] = $stmt->affected_rows > 0 ? 'Anggota dihapus.' : 'Gagal menghapus.';
    $stmt->close();
    header('Location: anggota.php');
    exit;
}

// Get user for editing
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $editUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Get all users
$users = $conn->query("SELECT * FROM users ORDER BY jabatan DESC, nama ASC");

include __DIR__ . '/../views/header.php';
?>

<section class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="bi bi-people text-danger me-2"></i>Kelola Anggota</h2>
                <p class="text-muted mb-0">Tambah, edit, dan hapus anggota PMR</p>
            </div>
            <?php if ($action === 'list'): ?>
                <a href="?action=add" class="btn btn-danger"><i class="bi bi-plus-circle me-2"></i>Tambah Anggota</a>
            <?php else: ?>
                <a href="anggota.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Kembali</a>
            <?php endif; ?>
        </div>

        <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <i class="bi bi-<?= $action === 'edit' ? 'pencil' : 'plus-circle' ?> me-2"></i>
                    <?= $action === 'edit' ? 'Edit Anggota' : 'Tambah Anggota Baru' ?>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($editUser): ?><input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">
                        <?php endif; ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">NIS <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nis"
                                    value="<?= htmlspecialchars($editUser['nis'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nama"
                                    value="<?= htmlspecialchars($editUser['nama'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kelas <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="kelas"
                                    value="<?= htmlspecialchars($editUser['kelas'] ?? '') ?>" placeholder="cth: X-1"
                                    required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jabatan</label>
                                <select class="form-select" name="jabatan">
                                    <option value="Anggota" <?= ($editUser['jabatan'] ?? '') === 'Anggota' ? 'selected' : '' ?>>Anggota</option>
                                    <option value="Pengurus" <?= ($editUser['jabatan'] ?? '') === 'Pengurus' ? 'selected' : '' ?>>Pengurus</option>
                                    <?php if ($_SESSION['jabatan'] === 'Pembina'): ?>
                                        <option value="Pembina" <?= ($editUser['jabatan'] ?? '') === 'Pembina' ? 'selected' : '' ?>>Pembina</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Password
                                    <?= $action === 'add' ? '<span class="text-danger">*</span>' : '<small class="text-muted">(kosongkan jika tidak diubah)</small>' ?>
                                </label>
                                <input type="password" class="form-control" name="password" <?= $action === 'add' ? 'required' : '' ?>>
                            </div>
                            <div class="col-12">
                                <hr>
                                <button type="submit" class="btn btn-danger"><i
                                        class="bi bi-check-lg me-2"></i>Simpan</button>
                                <a href="anggota.php" class="btn btn-outline-secondary ms-2">Batal</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header"><i class="bi bi-list-ul me-2"></i>Daftar Anggota</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>NIS</th>
                                    <th>Nama</th>
                                    <th>Kelas</th>
                                    <th>Jabatan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1;
                                while ($u = $users->fetch_assoc()):
                                    $badge = match ($u['jabatan']) { 'Pembina' => 'danger', 'Pengurus' => 'warning', 'Anggota' => 'primary', default => 'secondary'}; ?>
                                    <tr>
                                        <td>
                                            <?= $no++ ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($u['nis']) ?>
                                        </td>
                                        <td><strong>
                                                <?= htmlspecialchars($u['nama']) ?>
                                            </strong></td>
                                        <td>
                                            <?= htmlspecialchars($u['kelas']) ?>
                                        </td>
                                        <td><span class="badge bg-<?= $badge ?>">
                                                <?= $u['jabatan'] ?>
                                            </span></td>
                                        <td>
                                            <a href="?action=edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-warning"><i
                                                    class="bi bi-pencil"></i></a>
                                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                                <a href="?delete=<?= $u['id'] ?>" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Hapus anggota ini?')"><i class="bi bi-trash"></i></a>
                                            <?php endif; ?>
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