<?php
// profil.php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/koneksi.php';

require_login();

$user = current_user();
$page_title = 'Pengaturan Akun';

$errors = [];
$success = '';

// Ambil data terkini pengguna
$stmt = $conn->prepare("SELECT id_pengguna, nama_lengkap, username, email, peran FROM sd_pengguna WHERE id_pengguna = ? LIMIT 1");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$curr = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$curr) {
    exit('Pengguna tidak ditemukan.');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {

    $token = $_POST['csrf_token'] ?? null;
    if (!csrf_validate(is_string($token) ? $token : null)) {
        $errors[] = 'Token keamanan tidak valid atau telah kedaluwarsa.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $nama = trim((string)($_POST['nama_lengkap'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));

            if ($nama === '') {
                $errors[] = 'Nama lengkap wajib diisi.';
            }
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Format email tidak valid.';
            }

            if (!$errors) {
                $upd = $conn->prepare("UPDATE sd_pengguna SET nama_lengkap = ?, email = NULLIF(?, '') WHERE id_pengguna = ?");
                $upd->bind_param('ssi', $nama, $email, $user['id']);
                if ($upd->execute()) {
                    $_SESSION['nama_lengkap'] = $nama;
                    $success = 'Profil Anda berhasil diperbarui!';
                    $curr['nama_lengkap'] = $nama;
                    $curr['email'] = $email;
                } else {
                    $errors[] = 'Gagal memperbarui profil: ' . $upd->error;
                }
                $upd->close();
            }
        } elseif ($action === 'change_password') {
            $passLama = (string)($_POST['password_lama'] ?? '');
            $passBaru = (string)($_POST['password_baru'] ?? '');
            $passKonf = (string)($_POST['password_konfirmasi'] ?? '');

            // Cek password lama
            $chk = $conn->prepare("SELECT password_hash FROM sd_pengguna WHERE id_pengguna = ?");
            $chk->bind_param('i', $user['id']);
            $chk->execute();
            $hashCurrent = $chk->get_result()->fetch_assoc()['password_hash'] ?? '';
            $chk->close();

            if (!password_verify($passLama, $hashCurrent)) {
                $errors[] = 'Kata sandi lama Anda tidak sesuai.';
            } elseif (strlen($passBaru) < 8) {
                $errors[] = 'Kata sandi baru minimal 8 karakter.';
            } elseif ($passBaru !== $passKonf) {
                $errors[] = 'Konfirmasi kata sandi baru tidak cocok.';
            } else {
                $newHash = password_hash($passBaru, PASSWORD_DEFAULT);
                $updPass = $conn->prepare("UPDATE sd_pengguna SET password_hash = ? WHERE id_pengguna = ?");
                $updPass->bind_param('si', $newHash, $user['id']);
                if ($updPass->execute()) {
                    $success = 'Kata sandi Anda berhasil diubah!';
                } else {
                    $errors[] = 'Gagal mengubah kata sandi: ' . $updPass->error;
                }
                $updPass->close();
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<div class="app-main">
<?php include __DIR__ . '/includes/navbar.php'; ?>
<div class="app-content">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Pengaturan Profil & Akun</h4>
            <div class="text-muted small">Kelola informasi data akun dan keamanan kata sandi Anda.</div>
        </div>
        <a href="<?= e(app_url('dashboard.php')) ?>" class="btn btn-outline-secondary btn-sm rounded-3">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Dashboard
        </a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show alert-auto-dismiss border-0 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= e($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Perhatian:</div>
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Form Update Data Pribadi -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-person-circle text-primary"></i>
                    <span>Informasi Akun</span>
                </div>
                <div class="card-body p-4">
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_profile">

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Username</label>
                            <input type="text" class="form-control bg-light" value="<?= e($curr['username']) ?>" disabled>
                            <div class="form-text small">Username tidak dapat diubah setelah dibuat.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Peran / Hak Akses</label>
                            <div>
                                <span class="badge <?= e(role_badge_class($curr['peran'])) ?> px-3 py-2 rounded-pill">
                                    <?= e(role_label($curr['peran'])) ?>
                                </span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lengkap" class="form-control" value="<?= e($curr['nama_lengkap']) ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Alamat Email</label>
                            <input type="email" name="email" class="form-control" value="<?= e($curr['email'] ?? '') ?>" placeholder="contoh@umb.ac.id">
                        </div>

                        <button type="submit" class="btn btn-primary rounded-3 px-4">
                            <i class="bi bi-save me-1"></i>Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Form Ubah Password -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-key-fill text-warning"></i>
                    <span>Ganti Kata Sandi</span>
                </div>
                <div class="card-body p-4">
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="change_password">

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Kata Sandi Saat Ini <span class="text-danger">*</span></label>
                            <input type="password" name="password_lama" class="form-control" required placeholder="Masukkan kata sandi lama">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Kata Sandi Baru <span class="text-danger">*</span></label>
                            <input type="password" name="password_baru" class="form-control" minlength="8" required placeholder="Minimal 8 karakter">
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Ulangi Kata Sandi Baru <span class="text-danger">*</span></label>
                            <input type="password" name="password_konfirmasi" class="form-control" minlength="8" required placeholder="Konfirmasi kata sandi baru">
                        </div>

                        <button type="submit" class="btn btn-warning text-dark fw-bold rounded-3 px-4">
                            <i class="bi bi-shield-lock me-1"></i>Perbarui Kata Sandi
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/includes/footer.php'; ?>
