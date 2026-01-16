<?php
require_once __DIR__ . '/config/session_handler.php';
require_once 'config/database.php';

// Check if logged in
if (!isset($_SESSION['is_login']) || $_SESSION['is_login'] !== true) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Scan QR Absensi - Sistem Absensi PMR';
$baseUrl = '';
$currentPage = 'scan';

include 'views/header.php';
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Html5-QRCode -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<style>
    #reader {
        width: 100%;
        max-width: 500px;
        margin: 0 auto;
    }

    #reader video {
        border-radius: 12px;
    }

    .scan-overlay {
        position: relative;
    }

    .scan-frame {
        border: 3px solid #dc3545;
        border-radius: 12px;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4);
        }

        50% {
            box-shadow: 0 0 0 15px rgba(220, 53, 69, 0);
        }
    }
</style>

<section class="py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="text-center mb-4">
                    <h2><i class="bi bi-qr-code-scan text-danger me-2"></i>Scan QR Absensi</h2>
                    <p class="text-muted">Arahkan kamera ke QR Code kegiatan</p>
                </div>

                <div class="card shadow">
                    <div class="card-body p-4">
                        <div id="reader" class="scan-frame mb-3"></div>
                        <div id="scan-status" class="text-center">
                            <div class="spinner-border text-danger mb-2" role="status"></div>
                            <p class="text-muted mb-0">Memulai kamera...</p>
                        </div>
                        <div id="scan-result" class="text-center d-none"></div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <p class="text-muted small">
                        <i class="bi bi-person me-1"></i>Login sebagai: <strong>
                            <?= htmlspecialchars($_SESSION['nama']) ?>
                        </strong>
                    </p>
                    <a href="absen.php" class="btn btn-outline-secondary">
                        <i class="bi bi-keyboard me-2"></i>Absen Manual
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    const html5QrCode = new Html5Qrcode("reader");
    let isProcessing = false;

    const config = { fps: 10, qrbox: { width: 250, height: 250 } };

    html5QrCode.start(
        { facingMode: "environment" },
        config,
        onScanSuccess,
        onScanFailure
    ).then(() => {
        document.getElementById('scan-status').innerHTML = `
        <i class="bi bi-camera-video text-success fs-1"></i>
        <p class="text-success mb-0">Kamera aktif - Scan QR Code</p>
    `;
    }).catch(err => {
        document.getElementById('scan-status').innerHTML = `
        <i class="bi bi-exclamation-triangle text-danger fs-1"></i>
        <p class="text-danger">Gagal mengakses kamera: ${err}</p>
        <a href="absen.php" class="btn btn-danger mt-2">Gunakan Absen Manual</a>
    `;
    });

    function onScanSuccess(decodedText) {
        if (isProcessing) return;
        isProcessing = true;

        html5QrCode.stop();

        document.getElementById('scan-status').innerHTML = `
        <div class="spinner-border text-success mb-2"></div>
        <p class="text-success mb-0">QR Terdeteksi! Memproses...</p>
    `;

        // Parse QR data
        let eventId;
        try {
            const data = JSON.parse(decodedText);
            eventId = data.event_id;
        } catch {
            eventId = decodedText;
        }

        // Send attendance via AJAX
        fetch('process_absen.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                event_id: eventId
            })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    let icon = data.type === 'checkout' ? 'success' : (data.status === 'Terlambat' ? 'warning' : 'success');
                    let title = data.type === 'checkout' ? 'Absen Pulang Berhasil!' : 'Absen Masuk Berhasil!';

                    Swal.fire({
                        icon: icon,
                        title: title,
                        html: `<strong>${data.event_name}</strong><br><span class="badge bg-${data.status === 'Terlambat' ? 'warning' : 'success'}">${data.status}</span><br><small>${data.message}</small>`,
                        confirmButtonColor: '#dc3545'
                    }).then(() => {
                        window.location.href = 'admin/index.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: data.message,
                        confirmButtonColor: '#dc3545'
                    }).then(() => {
                        isProcessing = false;
                        html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess, onScanFailure);
                    });
                }
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Terjadi kesalahan. Silakan coba lagi.',
                    confirmButtonColor: '#dc3545'
                }).then(() => {
                    isProcessing = false;
                    html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess, onScanFailure);
                });
            });
    }

    function onScanFailure(error) {
        // Silent - waiting for valid QR
    }
</script>

<?php include 'views/footer.php'; ?>