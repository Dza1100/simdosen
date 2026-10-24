<?php
// includes/navbar.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';

$user = current_user();
?>
<header class="app-navbar d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
        <button class="sidebar-hamburger" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
            <i class="bi bi-list"></i>
        </button>
        <div>
            <span class="fw-bold text-dark fs-6 d-none d-sm-inline">Sistem Informasi Manajemen Dosen</span>
            <span class="badge bg-light text-primary border ms-2 d-none d-md-inline" style="font-size: 0.72rem;">UM Bengkulu</span>
        </div>
    </div>

    <div class="d-flex align-items-center gap-3">
        <!-- Date indicator -->
        <div class="text-muted small d-none d-lg-flex align-items-center gap-1">
            <i class="bi bi-calendar3"></i>
            <span><?= date('d M Y') ?></span>
        </div>

        <!-- User Dropdown -->
        <div class="dropdown">
            <button class="btn btn-light border d-flex align-items-center gap-2 py-1 px-2 rounded-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="rounded-circle bg-primary text-white d-grid place-items-center fw-bold" style="width: 30px; height: 30px; font-size: 0.8rem; display: grid; place-items: center;">
                    <?= strtoupper(substr($user['nama'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="text-start d-none d-md-block lh-1">
                    <div class="fw-semibold small text-truncate" style="max-width: 140px;"><?= e($user['nama']) ?></div>
                    <small class="text-muted" style="font-size: 0.7rem;"><?= e(role_label($user['role'])) ?></small>
                </div>
                <i class="bi bi-chevron-down small text-muted"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-slate-200 mt-2" style="min-width: 200px;">
                <li class="px-3 py-2 border-bottom">
                    <div class="fw-bold small"><?= e($user['nama']) ?></div>
                    <div class="text-muted" style="font-size: 0.75rem;">@<?= e($user['username']) ?></div>
                    <span class="badge <?= e(role_badge_class($user['role'])) ?> rounded-pill mt-1" style="font-size: 0.65rem;">
                        <?= e(role_label($user['role'])) ?>
                    </span>
                </li>
                <li>
                    <a class="dropdown-item py-2 small" href="<?= e(app_url('profil.php')) ?>">
                        <i class="bi bi-person-gear me-2 text-primary"></i>Pengaturan Akun
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2 small" href="<?= e(app_url('index.php')) ?>" target="_blank">
                        <i class="bi bi-box-arrow-up-right me-2 text-secondary"></i>Lihat Landing Page
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <form method="post" action="<?= e(app_url('logout.php')) ?>" onsubmit="return confirm('Apakah Anda yakin ingin logout?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="dropdown-item py-2 small text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i>Keluar (Logout)
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
