<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Absensi PMR - SMKS Bina Nasional Informatika' ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?= $baseUrl ?? '' ?>assets/css/style.css" rel="stylesheet">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="<?= $baseUrl ?? '' ?>index.php">
                <img src="<?= $baseUrl ?? '' ?>assets/images/logo_smk_bni.png" alt="Logo"
                    style="height: 35px; margin-right: 10px;" onerror="this.style.display='none'">
                <span><i class="bi bi-plus-lg me-1"></i>PMR SMKS BNI</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage ?? '') === 'home' ? 'active' : '' ?>"
                            href="<?= $baseUrl ?? '' ?>index.php">
                            <i class="bi bi-house-door me-1"></i>Home
                        </a>
                    </li>

                    <?php if (isset($_SESSION['is_login']) && $_SESSION['is_login'] === true): ?>
                        <?php $isAdmin = isset($_SESSION['jabatan']) && in_array($_SESSION['jabatan'], ['Pembina', 'Pengurus']); ?>

                        <!-- Home/Dashboard - Visible to ALL -->
                        <li class="nav-item">
                            <a class="nav-link <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>"
                                href="<?= $baseUrl ?? '' ?>admin/index.php">
                                <i class="bi bi-house me-1"></i>Home
                            </a>
                        </li>

                        <?php if ($isAdmin): ?>
                            <!-- ADMIN ONLY MENUS -->
                            <li class="nav-item">
                                <a class="nav-link <?= ($currentPage ?? '') === 'absensi' ? 'active' : '' ?>"
                                    href="<?= $baseUrl ?? '' ?>admin/absensi.php">
                                    <i class="bi bi-clipboard-check me-1"></i>Absensi
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= ($currentPage ?? '') === 'kegiatan' ? 'active' : '' ?>"
                                    href="<?= $baseUrl ?? '' ?>admin/kegiatan.php">
                                    <i class="bi bi-calendar-event me-1"></i>Kegiatan
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= ($currentPage ?? '') === 'anggota' ? 'active' : '' ?>"
                                    href="<?= $baseUrl ?? '' ?>admin/anggota.php">
                                    <i class="bi bi-people me-1"></i>Anggota
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= ($currentPage ?? '') === 'laporan' ? 'active' : '' ?>"
                                    href="<?= $baseUrl ?? '' ?>admin/laporan.php">
                                    <i class="bi bi-graph-up me-1"></i>Laporan
                                </a>
                            </li>
                        <?php else: ?>
                            <!-- MEMBER ONLY: Scan QR -->
                            <li class="nav-item">
                                <a class="nav-link <?= ($currentPage ?? '') === 'scan' ? 'active' : '' ?>"
                                    href="<?= $baseUrl ?? '' ?>scan.php">
                                    <i class="bi bi-qr-code-scan me-1"></i>Scan QR
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['is_login']) && $_SESSION['is_login'] === true): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i>
                                <?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?>
                                <span class="badge bg-light text-danger ms-1">
                                    <?= htmlspecialchars($_SESSION['jabatan'] ?? '') ?>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <span class="dropdown-item-text text-muted small">
                                        <i class="bi bi-credit-card me-1"></i>NIS:
                                        <?= htmlspecialchars($_SESSION['nis'] ?? '') ?>
                                    </span>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= $baseUrl ?? '' ?>admin/profil.php">
                                        <i class="bi bi-person me-2"></i>Profil Saya
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?= $baseUrl ?? '' ?>logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $baseUrl ?? '' ?>login.php">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Login
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="container mt-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="container mt-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Main Content -->
    <main>