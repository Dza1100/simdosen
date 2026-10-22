<?php
// includes/footer.php
?>
    </div> <!-- end .app-content -->
    
    <footer class="py-3 px-4 bg-white border-top text-center text-muted small mt-auto">
        <span>&copy; <?= date('Y') ?> <strong>Universitas Muhammadiyah Bengkulu</strong>. Sistem Informasi Manajemen Dosen (SIM Dosen).</span>
    </footer>
</div> <!-- end .app-main -->
</div> <!-- end .app-layout -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('appSidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const backdrop = document.getElementById('sidebarBackdrop');

    if (toggleBtn && sidebar && backdrop) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            backdrop.classList.toggle('show');
        });

        backdrop.addEventListener('click', function () {
            sidebar.classList.remove('show');
            backdrop.classList.remove('show');
        });
    }

    // Auto-dismiss alert success/info after 5 seconds if marked
    const alerts = document.querySelectorAll('.alert-auto-dismiss');
    alerts.forEach(function (el) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            bsAlert.close();
        }, 5000);
    });
});
</script>
</body>
</html>
