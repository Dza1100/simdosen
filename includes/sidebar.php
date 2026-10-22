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
    <a href="<?= e(app_url('dashboard.php')) ?>" class="sidebar-brand">
        <div class="brand-icon">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <div>
            <div class="fw-bold fs-6 lh-sm">SIM Dosen</div>
            <small class="text-muted" style="font-size: 0.72rem;">UM Bengkulu</small>
        </div>
    </a>

    <!-- User Profile Badge in Sidebar -->
    <div class="px-3 py-3 mx-3 my-2 rounded-3 bg-light border border-slate-200">
        <div class="d-flex align-items-center gap-2">
            <div class="avatar-sm rounded-circle bg-primary text-white d-grid place-items-center fw-bold" style="width: 34px; height: 34px; display: grid; place-items: center; font-size: 0.85rem;">
                <?= strtoupper(substr($user['nama'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="overflow-hidden">
                <div class="text-truncate fw-bold small text-dark"><?= e($user['nama']) ?></div>
                <span class="badge <?= e(role_badge_class($user['role'])) ?> rounded-pill" style="font-size: 0.65rem;">
                    <?= e(role_label($user['role'])) ?>
                </span>
            </div>
        </div>
    </div>

    <div class="flex-grow-1 overflow-auto">
        <div class="sidebar-heading">Menu Utama</div>
        <nav class="nav flex-column nav-sidebar">
            <a class="nav-link <?= is_active('dashboard.php') ?>" href="<?= e(app_url('dashboard.php')) ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>
        </nav>

        <div class="sidebar-heading">Data Dosen</div>
        <nav class="nav flex-column nav-sidebar">
            <a class="nav-link <?= is_active('dosen/') ?>" href="<?= e(app_url('dosen/index.php')) ?>">
                <i class="bi bi-people-fill"></i>
                <span>Data Dosen</span>
            </a>
            <?php if (has_role('ADMIN', 'OPERATOR_PRODI')): ?>
            <a class="nav-link <?= is_active('dosen/tambah.php') ?>" href="<?= e(app_url('dosen/tambah.php')) ?>">
                <i class="bi bi-person-plus-fill"></i>
                <span>Tambah Dosen</span>
            </a>
            <?php endif; ?>
        </nav>

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
            <a class="nav-link <?= is_active('laporan/') ?>" href="<?= e(app_url('laporan/index.php')) ?>">
                <i class="bi bi-file-earmark-bar-graph-fill"></i>
                <span>Rekap & Cetak</span>
            </a>
        </nav>
        <?php endif; ?>

        <?php if (has_role('ADMIN')): ?>
        <div class="sidebar-heading">Pengaturan & RBAC</div>
        <nav class="nav flex-column nav-sidebar">
            <a class="nav-link <?= is_active('pengguna/') ?>" href="<?= e(app_url('pengguna/index.php')) ?>">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Manajemen Pengguna</span>
            </a>
            <a class="nav-link <?= is_active('setupadminsimdosen.php') ?>" href="<?= e(app_url('setupadminsimdosen.php')) ?>">
                <i class="bi bi-sliders"></i>
                <span>Konfigurasi API</span>
            </a>
            <a class="nav-link <?= is_active('api/') ?>" href="<?= e(app_url('api/index.php')) ?>" target="_blank">
                <i class="bi bi-code-slash"></i>
                <span>Dokumentasi API</span>
            </a>
        </nav>
        <?php endif; ?>
    </div>

    <!-- Sidebar Footer -->
    <div class="p-3 border-top border-slate-200">
        <form method="post" action="<?= e(app_url('logout.php')) ?>" onsubmit="return confirm('Apakah Anda yakin ingin logout?');">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center justify-content-center gap-2 py-2 rounded-3">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout Keluar</span>
            </button>
        </form>
    </div>
</aside>
