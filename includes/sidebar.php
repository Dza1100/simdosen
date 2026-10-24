<?php
// includes/sidebar.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';

if (!function_exists('is_active')) {
    function is_active(string $path): string {
        $currUri = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        return (str_ends_with($currUri, $path) || strpos($currUri, $path) !== false) ? 'active' : '';
    }
}
$user = current_user();
?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<aside class="app-sidebar" id="appSidebar">

    <!-- Brand + Hamburger Toggle -->
    <div class="sidebar-brand-row">
        <a href="<?= e(app_url('dashboard.php')) ?>" class="sidebar-brand">
            <div class="brand-icon">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
            <div class="sidebar-brand-text">
                <div class="fw-bold fs-6 lh-sm">SIM Dosen</div>
                <small class="text-muted" style="font-size: 0.72rem;">UM Bengkulu</small>
            </div>
        </a>
        <button class="sidebar-close-btn" id="sidebarToggleInner" type="button" aria-label="Tutup sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <!-- Nav Menu -->
    <div class="flex-grow-1 overflow-auto">
        <div class="sidebar-heading">Menu Utama</div>
        <nav class="nav flex-column nav-sidebar">
            <a class="nav-link <?= is_active('dashboard.php') ?>" href="<?= e(app_url('dashboard.php')) ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>
        </nav>

        <?php if (has_role('DOSEN')): ?>
        <!-- Menu khusus DOSEN -->
        <div class="sidebar-heading">Kepegawaian Saya</div>
        <nav class="nav flex-column nav-sidebar">
            <a class="nav-link <?= is_active('dosen/profil_saya.php') ?>" href="<?= e(app_url('dosen/profil_saya.php')) ?>">
                <i class="bi bi-person-circle"></i>
                <span>Profil Saya</span>
            </a>
            <a class="nav-link <?= is_active('dosen/jadwal.php') ?>" href="<?= e(app_url('dosen/jadwal.php')) ?>">
                <i class="bi bi-calendar3-week"></i>
                <span>Jadwal Mengajar</span>
            </a>
        </nav>
        <?php else: ?>
        <!-- Menu untuk ADMIN / OPERATOR / dll -->
        <div class="sidebar-heading">Data Dosen</div>
        <nav class="nav flex-column nav-sidebar">
            <a class="nav-link <?= is_active('dosen/index.php') ?>" href="<?= e(app_url('dosen/index.php')) ?>">
                <i class="bi bi-people-fill"></i>
                <span>Data Dosen</span>
            </a>
        </nav>
        <?php endif; ?>

        <?php if (has_role('ADMIN')): ?>
        <div class="sidebar-heading">Master Data</div>
        <nav class="nav flex-column nav-sidebar">
            <a class="nav-link <?= is_active('master/penempatan.php') ?>" href="<?= e(app_url('master/penempatan.php')) ?>">
                <i class="bi bi-buildings-fill"></i>
                <span>Penempatan / Prodi</span>
            </a>
            <a class="nav-link <?= is_active('master/jabatan.php') ?>" href="<?= e(app_url('master/jabatan.php')) ?>">
                <i class="bi bi-briefcase-fill"></i>
                <span>Jabatan Fungsional</span>
            </a>
            <a class="nav-link <?= is_active('master/kepangkatan.php') ?>" href="<?= e(app_url('master/kepangkatan.php')) ?>">
                <i class="bi bi-award-fill"></i>
                <span>Kepangkatan</span>
            </a>
            <a class="nav-link <?= is_active('master/status_kerja.php') ?>" href="<?= e(app_url('master/status_kerja.php')) ?>">
                <i class="bi bi-person-badge-fill"></i>
                <span>Status Kepegawaian</span>
            </a>
        </nav>
        <?php endif; ?>

        <?php if (has_role('ADMIN', 'OPERATOR_PRODI', 'FAKULTAS', 'PIMPINAN')): ?>
        <div class="sidebar-heading">Laporan</div>
        <nav class="nav flex-column nav-sidebar">
            <a class="nav-link <?= is_active('laporan/index.php') ?>" href="<?= e(app_url('laporan/index.php')) ?>">
                <i class="bi bi-file-earmark-bar-graph-fill"></i>
                <span>Rekap &amp; Cetak</span>
            </a>
        </nav>
        <?php endif; ?>

        <?php if (has_role('ADMIN')): ?>
        <div class="sidebar-heading">Pengaturan &amp; RBAC</div>
        <nav class="nav flex-column nav-sidebar">
            <a class="nav-link <?= is_active('pengguna/index.php') ?>" href="<?= e(app_url('pengguna/index.php')) ?>">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Manajemen Pengguna</span>
            </a>
            <a class="nav-link <?= is_active('setupadminsimdosen.php') ?>" href="<?= e(app_url('setupadminsimdosen.php')) ?>">
                <i class="bi bi-sliders"></i>
                <span>Konfigurasi API</span>
            </a>
            <a class="nav-link <?= is_active('api/index.php') ?>" href="<?= e(app_url('api/index.php')) ?>" target="_blank">
                <i class="bi bi-code-slash"></i>
                <span>Dokumentasi API</span>
            </a>
        </nav>
        <?php endif; ?>
    </div>

    <!-- Sidebar Footer: Logout -->
    <div class="p-3 border-top">
        <form method="post" action="<?= e(app_url('logout.php')) ?>" onsubmit="return confirm('Apakah Anda yakin ingin logout?');">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center justify-content-center gap-2 py-2 rounded-3">
                <i class="bi bi-box-arrow-right"></i>
                <span class="sidebar-label">Logout Keluar</span>
            </button>
        </form>
    </div>
</aside>
