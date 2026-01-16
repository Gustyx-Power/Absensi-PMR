</main>

<!-- Footer -->
<footer class="bg-dark text-white py-4 mt-auto">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h6 class="mb-2">
                    <i class="bi bi-plus-lg text-danger me-2"></i>PMR Attendance System
                </h6>
                <p class="text-muted mb-0 small">
                    Sistem Absensi Digital untuk Palang Merah Remaja
                </p>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <p class="text-muted mb-0 small">
                    &copy;
                    <?= date('Y') ?> PMR. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="<?= $baseUrl ?? '' ?>assets/js/script.js"></script>
</body>

</html>