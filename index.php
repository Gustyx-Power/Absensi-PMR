<?php
// Vercel/Serverless session fix
if (isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL'])) {
    session_save_path('/tmp');
}
session_start();

$pageTitle = 'Sistem Absensi PMR - Home';
$baseUrl = '';

include 'views/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1 class="display-5 fw-bold mb-4">
                    <i class="bi bi-plus-lg me-2"></i>Sistem Absensi PMR
                </h1>
                <p class="lead mb-4">
                    Sistem manajemen kehadiran digital untuk Palang Merah Remaja.
                    Kelola absensi kegiatan dengan mudah, cepat, dan efisien.
                </p>
                <div class="d-flex gap-3">
                    <a href="login.php" class="btn btn-light btn-lg px-4">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login
                    </a>
                    <a href="#features" class="btn btn-outline-light btn-lg px-4">
                        <i class="bi bi-info-circle me-2"></i>Pelajari Lebih
                    </a>
                </div>
            </div>
            <div class="col-lg-5 text-center mt-5 mt-lg-0">
                <i class="bi bi-calendar-check display-1" style="font-size: 12rem; opacity: 0.2;"></i>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Fitur Unggulan</h2>
            <p class="text-muted">Semua yang Anda butuhkan untuk mengelola absensi PMR</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card feature-card h-100">
                    <div class="icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <h5>Manajemen Anggota</h5>
                    <p class="text-muted mb-0">
                        Kelola data anggota, pengurus, dan pembina PMR dengan mudah.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card feature-card h-100">
                    <div class="icon">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <h5>Jadwal Kegiatan</h5>
                    <p class="text-muted mb-0">
                        Buat dan kelola jadwal latihan rutin, pelatihan, dan kegiatan lainnya.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card feature-card h-100">
                    <div class="icon">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                    <h5>Absensi Digital</h5>
                    <p class="text-muted mb-0">
                        Catat kehadiran secara digital dengan status Hadir, Izin, Sakit, atau Alpha.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card feature-card h-100">
                    <div class="icon">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <h5>Laporan & Statistik</h5>
                    <p class="text-muted mb-0">
                        Pantau statistik kehadiran dan unduh laporan dalam berbagai format.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card feature-card h-100">
                    <div class="icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h5>Keamanan Data</h5>
                    <p class="text-muted mb-0">
                        Data tersimpan aman dengan sistem login dan hak akses terproteksi.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card feature-card h-100">
                    <div class="icon">
                        <i class="bi bi-phone"></i>
                    </div>
                    <h5>Responsive Design</h5>
                    <p class="text-muted mb-0">
                        Akses dari perangkat apapun - desktop, tablet, atau smartphone.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="bg-danger text-white py-5">
    <div class="container text-center">
        <h3 class="fw-bold mb-3">Siap Memulai?</h3>
        <p class="mb-4">Login untuk mengakses dashboard dan mulai mengelola absensi PMR</p>
        <a href="login.php" class="btn btn-light btn-lg px-5">
            <i class="bi bi-box-arrow-in-right me-2"></i>Login Sekarang
        </a>
    </div>
</section>

<?php include 'views/footer.php'; ?>