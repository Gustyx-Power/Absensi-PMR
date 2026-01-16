<?php
require_once '../config/auth_check.php';
require_once '../config/database.php';

$pageTitle = 'Profil Saya - Sistem Absensi PMR';
$baseUrl = '../';
$currentPage = 'profil';

// Get current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $kelas = trim($_POST['kelas'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($nama)) {
        $_SESSION['error'] = 'Nama tidak boleh kosong.';
    } else {
        // Update basic info
        $stmt = $conn->prepare("UPDATE users SET nama = ?, kelas = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nama, $kelas, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();

        $_SESSION['nama'] = $nama;
        $_SESSION['success'] = 'Profil berhasil diperbarui.';

        // Change password if provided
        if (!empty($current_password) && !empty($new_password)) {
            if (!password_verify($current_password, $user['password'])) {
                $_SESSION['error'] = 'Password lama salah.';
            } elseif ($new_password !== $confirm_password) {
                $_SESSION['error'] = 'Konfirmasi password tidak cocok.';
            } elseif (strlen($new_password) < 6) {
                $_SESSION['error'] = 'Password baru minimal 6 karakter.';
            } else {
                $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashedPassword, $_SESSION['user_id']);
                $stmt->execute();
                $stmt->close();
                $_SESSION['success'] = 'Profil dan password berhasil diperbarui.';
            }
        }

        header('Location: profil.php');
        exit;
    }
}

// Get attendance stats
$attendanceStats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN status = 'Izin' THEN 1 ELSE 0 END) as izin,
        SUM(CASE WHEN status = 'Sakit' THEN 1 ELSE 0 END) as sakit,
        SUM(CASE WHEN status = 'Alpha' THEN 1 ELSE 0 END) as alpha
    FROM attendance WHERE user_id = {$_SESSION['user_id']}
")->fetch_assoc();

$hadirPercent = $attendanceStats['total'] > 0 ? round(($attendanceStats['hadir'] / $attendanceStats['total']) * 100, 1) : 0;

include '../views/header.php';
?>

<section class="py-4">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <!-- Profile Card -->
                <div class="card text-center">
                    <div class="card-body py-4">
                        <div class="bg-danger text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                            style="width:80px;height:80px;">
                            <i class="bi bi-person-fill display-4"></i>
                        </div>
                        <h4 class="mb-1">
                            <?= htmlspecialchars($user['nama']) ?>
                        </h4>
                        <p class="text-muted mb-2">
                            <?= htmlspecialchars($user['nis']) ?>
                        </p>
                        <span
                            class="badge bg-<?= match ($user['jabatan']) { 'Pembina' => 'danger', 'Pengurus' => 'warning', default => 'primary'} ?> fs-6">
                            <?= $user['jabatan'] ?>
                        </span>
                        <hr>
                        <div class="row text-center">
                            <div class="col-6">
                                <h5 class="mb-0 text-success">
                                    <?= $hadirPercent ?>%
                                </h5>
                                <small class="text-muted">Kehadiran</small>
                            </div>
                            <div class="col-6">
                                <h5 class="mb-0">
                                    <?= $attendanceStats['total'] ?>
                                </h5>
                                <small class="text-muted">Total Absen</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Card -->
                <div class="card mt-3">
                    <div class="card-header"><i class="bi bi-bar-chart me-2"></i>Statistik Kehadiran</div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Hadir</span><span class="badge bg-success">
                                <?= $attendanceStats['hadir'] ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Izin</span><span class="badge bg-info">
                                <?= $attendanceStats['izin'] ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Sakit</span><span class="badge bg-warning">
                                <?= $attendanceStats['sakit'] ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Alpha</span><span class="badge bg-danger">
                                <?= $attendanceStats['alpha'] ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <!-- Edit Profile -->
                <div class="card mb-4">
                    <div class="card-header bg-danger text-white"><i class="bi bi-pencil me-2"></i>Edit Profil</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">NIS</label>
                                    <input type="text" class="form-control"
                                        value="<?= htmlspecialchars($user['nis']) ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Jabatan</label>
                                    <input type="text" class="form-control" value="<?= $user['jabatan'] ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nama"
                                        value="<?= htmlspecialchars($user['nama']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Kelas</label>
                                    <input type="text" class="form-control" name="kelas"
                                        value="<?= htmlspecialchars($user['kelas']) ?>">
                                </div>
                                <div class="col-12"><button type="submit" class="btn btn-danger"><i
                                            class="bi bi-check-lg me-2"></i>Simpan Profil</button></div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="card">
                    <div class="card-header"><i class="bi bi-lock me-2"></i>Ubah Password</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="nama" value="<?= htmlspecialchars($user['nama']) ?>">
                            <input type="hidden" name="kelas" value="<?= htmlspecialchars($user['kelas']) ?>">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Password Lama</label>
                                    <input type="password" class="form-control" name="current_password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Password Baru</label>
                                    <input type="password" class="form-control" name="new_password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Konfirmasi Password</label>
                                    <input type="password" class="form-control" name="confirm_password">
                                </div>
                                <div class="col-12"><button type="submit" class="btn btn-warning"><i
                                            class="bi bi-key me-2"></i>Ubah Password</button></div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../views/footer.php'; ?>