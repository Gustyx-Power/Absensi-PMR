<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['is_login']) && $_SESSION['is_login'] === true) {
    header('Location: admin/dashboard.php');
    exit;
}

$pageTitle = 'Login - Sistem Absensi PMR';
$baseUrl = '';

// Get error/success messages from session
$error = $_SESSION['login_error'] ?? '';
$success = $_SESSION['login_success'] ?? '';
unset($_SESSION['login_error'], $_SESSION['login_success']);

include 'views/header.php';
?>

<section class="py-5">
    <div class="container">
        <div class="auth-container">
            <div class="card auth-card shadow">
                <div class="card-header bg-danger text-white text-center py-4">
                    <i class="bi bi-plus-lg display-6 mb-2"></i>
                    <h4 class="mb-0">Login PMR</h4>
                    <small class="opacity-75">Masuk ke sistem absensi</small>
                </div>
                <div class="card-body p-4">
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form action="auth_process.php" method="POST">
                        <div class="mb-3">
                            <label for="nis" class="form-label fw-semibold">
                                <i class="bi bi-person me-1"></i>NIS / ID Pembina
                            </label>
                            <input 
                                type="text" 
                                class="form-control form-control-lg" 
                                id="nis" 
                                name="nis" 
                                placeholder="Masukkan NIS Anda"
                                required 
                                autofocus
                            >
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold">
                                <i class="bi bi-lock me-1"></i>Password
                            </label>
                            <div class="input-group">
                                <input 
                                    type="password" 
                                    class="form-control form-control-lg" 
                                    id="password" 
                                    name="password" 
                                    placeholder="Masukkan password"
                                    required
                                >
                                <button 
                                    class="btn btn-outline-secondary" 
                                    type="button" 
                                    id="togglePassword"
                                    onclick="togglePasswordVisibility()"
                                >
                                    <i class="bi bi-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Login
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer bg-light text-center py-3">
                    <a href="index.php" class="text-decoration-none text-muted">
                        <i class="bi bi-arrow-left me-1"></i>Kembali ke Beranda
                    </a>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <small class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    Hubungi Pembina jika lupa password
                </small>
            </div>
        </div>
    </div>
</section>

<script>
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('bi-eye');
        toggleIcon.classList.add('bi-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('bi-eye-slash');
        toggleIcon.classList.add('bi-eye');
    }
}
</script>

<?php include 'views/footer.php'; ?>
