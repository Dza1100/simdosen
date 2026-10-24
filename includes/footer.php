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
    const sidebar    = document.getElementById('appSidebar');
    const appMain    = document.querySelector('.app-main');
    const toggleBtn  = document.getElementById('sidebarToggle');
    const innerBtn   = document.getElementById('sidebarToggleInner');
    const backdrop   = document.getElementById('sidebarBackdrop');
    const STORAGE_KEY = 'simdosen_sidebar_collapsed';

    function isMobile() {
        return window.innerWidth < 992;
    }

    function openSidebar() {
        if (isMobile()) {
            sidebar.classList.remove('collapsed');
            sidebar.classList.add('show');
            backdrop.classList.add('show');
        } else {
            sidebar.classList.remove('collapsed');
            appMain.classList.remove('sidebar-collapsed');
            localStorage.setItem(STORAGE_KEY, '0');
        }
    }

    function closeSidebar() {
        if (isMobile()) {
            sidebar.classList.remove('show');
            backdrop.classList.remove('show');
        } else {
            sidebar.classList.add('collapsed');
            appMain.classList.add('sidebar-collapsed');
            localStorage.setItem(STORAGE_KEY, '1');
        }
    }

    function toggleSidebar() {
        if (isMobile()) {
            if (sidebar.classList.contains('show')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        } else {
            if (sidebar.classList.contains('collapsed')) {
                openSidebar();
            } else {
                closeSidebar();
            }
        }
    }

    // Restore desktop state from localStorage
    if (!isMobile()) {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved === '1') {
            sidebar.classList.add('collapsed');
            appMain.classList.add('sidebar-collapsed');
        }
    }

    if (toggleBtn)  toggleBtn.addEventListener('click', toggleSidebar);
    if (innerBtn)   innerBtn.addEventListener('click', closeSidebar);
    if (backdrop)   backdrop.addEventListener('click', closeSidebar);

    // On resize: clean up classes appropriately
    window.addEventListener('resize', function () {
        if (!isMobile()) {
            sidebar.classList.remove('show');
            backdrop.classList.remove('show');
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved === '1') {
                sidebar.classList.add('collapsed');
                appMain.classList.add('sidebar-collapsed');
            } else {
                sidebar.classList.remove('collapsed');
                appMain.classList.remove('sidebar-collapsed');
            }
        } else {
            sidebar.classList.remove('collapsed');
            appMain.classList.remove('sidebar-collapsed');
        }
    });

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
