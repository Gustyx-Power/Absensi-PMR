</main>

<!-- Footer -->
<footer class="bg-dark text-white py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5 class="mb-3">
                    <i class="bi bi-plus-lg text-danger me-2"></i>PMR Attendance System
                </h5>
                <p class="text-muted mb-0">
                    Sistem Absensi Digital untuk Palang Merah Remaja
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="text-muted mb-0 mt-3 mt-md-4">
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