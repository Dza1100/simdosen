<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/helpers.php';

function is_logged_in(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect(app_url('login.php'));
    }
}

function has_role(string ...$allowedRoles): bool
{
    if (!is_logged_in()) {
        return false;
    }
    $role = (string)($_SESSION['role'] ?? '');
    return in_array($role, $allowedRoles, true);
}

function require_role(string ...$allowedRoles): void
{
    require_login();

    if (!has_role(...$allowedRoles)) {
        http_response_code(403);
        include __DIR__ . '/header.php';
        echo '<div class="container py-5 text-center">
            <div class="card shadow-sm border-0 p-5 mx-auto" style="max-width: 500px;">
                <div class="display-1 text-danger mb-3"><i class="bi bi-shield-x"></i></div>
                <h3 class="fw-bold text-dark">403 - Akses Ditolak</h3>
                <p class="text-muted">Peran akun Anda (<strong>' . e(role_label($_SESSION['role'] ?? '')) . '</strong>) tidak memiliki izin untuk mengakses halaman ini.</p>
                <div class="mt-3">
                    <a href="' . e(app_url('dashboard.php')) . '" class="btn btn-primary px-4">Kembali ke Dashboard</a>
                </div>
            </div>
        </div>';
        include __DIR__ . '/footer.php';
        exit;
    }
}

function current_user(): array
{
    return [
        'id' => (int)($_SESSION['user_id'] ?? 0),
        'username' => (string)($_SESSION['username'] ?? ''),
        'nama' => (string)($_SESSION['nama_lengkap'] ?? 'Pengguna'),
        'role' => (string)($_SESSION['role'] ?? ''),
    ];
}

function role_label(?string $role = null): string
{
    $r = $role ?? ($_SESSION['role'] ?? '');
    return match ($r) {
        'ADMIN' => 'Administrator',
        'OPERATOR_PRODI' => 'Operator Prodi',
        'FAKULTAS' => 'Fakultas',
        'PIMPINAN' => 'Pimpinan',
        'DOSEN' => 'Dosen',
        default => $r !== '' ? $r : 'Tamu',
    };
}

function role_badge_class(?string $role = null): string
{
    $r = $role ?? ($_SESSION['role'] ?? '');
    return match ($r) {
        'ADMIN' => 'bg-danger',
        'OPERATOR_PRODI' => 'bg-primary',
        'FAKULTAS' => 'bg-info text-dark',
        'PIMPINAN' => 'bg-success',
        'DOSEN' => 'bg-secondary',
        default => 'bg-dark',
    };
}

