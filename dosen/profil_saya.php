<?php
// dosen/profil_saya.php  — Profil Pribadi Dosen
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../koneksi.php';

require_role('DOSEN');

$user    = current_user();
$page_title = 'Profil Saya';
$errors  = [];
$success = '';

// Ambil id_dosen yang terhubung ke akun ini
$stmtU = $conn->prepare("SELECT id_dosen, nama_lengkap, username, email FROM sd_pengguna WHERE id_pengguna = ? LIMIT 1");
$stmtU->bind_param('i', $user['id']);
$stmtU->execute();
$akun = $stmtU->get_result()->fetch_assoc();
$stmtU->close();

$idDosen = (int)($akun['id_dosen'] ?? 0);

// Ambil data pokok dosen jika akun terhubung
$d = null;
if ($idDosen > 0) {
    $stmtD = $conn->prepare(
        "SELECT d.*,
                j.nama_jabatan,
                k.nama_pangkat, k.golongan, k.ruang,
                pd.jenjang,
                r.nama_rumpun,
                s.status_pegawai, s.ikatan_kerja,
                p.universitas, p.fakultas, p.program_studi
         FROM dosen d
         LEFT JOIN jabatan_fungsional j      ON d.id_jabatan           = j.id_jabatan
         LEFT JOIN kepangkatan k             ON d.id_kepangkatan       = k.id_kepangkatan
         LEFT JOIN pendidikan pd             ON d.id_pendidikan        = pd.id_pendidikan
         LEFT JOIN rumpun_ilmu r             ON d.id_rumpun_ilmu       = r.id_rumpun_ilmu
         LEFT JOIN status_kepegawaian s      ON d.id_status_kepegawaian= s.id_status_kepegawaian
         LEFT JOIN penempatan p              ON d.id_penempatan        = p.id_penempatan
         WHERE d.id_dosen = ? LIMIT 1"
    );
    $stmtD->bind_param('i', $idDosen);
    $stmtD->execute();
    $d = $stmtD->get_result()->fetch_assoc();
    $stmtD->close();
}

// ── POST: Update profil akun (nama & email)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!csrf_validate(is_string($token) ? $token : null)) {
        $errors[] = 'Token keamanan tidak valid.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profil') {
            $namaBaru  = trim((string)($_POST['nama_lengkap'] ?? ''));
            $emailBaru = trim((string)($_POST['email'] ?? ''));
            if ($namaBaru === '') {
                $errors[] = 'Nama lengkap tidak boleh kosong.';
            } else {
                $stmtUp = $conn->prepare(
                    "UPDATE sd_pengguna SET nama_lengkap = ?, email = NULLIF(?, '') WHERE id_pengguna = ?"
                );
                $stmtUp->bind_param('ssi', $namaBaru, $emailBaru, $user['id']);
                if ($stmtUp->execute()) {
                    $_SESSION['nama_lengkap'] = $namaBaru;
                    $akun['nama_lengkap']     = $namaBaru;
                    $akun['email']            = $emailBaru;
                    $success = 'Profil akun berhasil diperbarui.';
                } else {
                    $errors[] = 'Gagal memperbarui profil. Email mungkin sudah digunakan.';
                }
                $stmtUp->close();
            }

        } elseif ($action === 'ganti_password') {
            $passLama  = (string)($_POST['password_lama'] ?? '');
            $passBaru  = (string)($_POST['password_baru'] ?? '');
            $passUlang = (string)($_POST['password_ulang'] ?? '');

            // Ambil hash saat ini
            $stmtH = $conn->prepare("SELECT password_hash FROM sd_pengguna WHERE id_pengguna = ? LIMIT 1");
            $stmtH->bind_param('i', $user['id']);
            $stmtH->execute();
            $hashRow = $stmtH->get_result()->fetch_assoc();
            $stmtH->close();

            if (!password_verify($passLama, $hashRow['password_hash'] ?? '')) {
                $errors[] = 'Password lama tidak sesuai.';
            } elseif (strlen($passBaru) < 8) {
                $errors[] = 'Password baru minimal 8 karakter.';
            } elseif ($passBaru !== $passUlang) {
                $errors[] = 'Konfirmasi password tidak cocok.';
            } else {
                $newHash = password_hash($passBaru, PASSWORD_DEFAULT);
                $stmtPw = $conn->prepare("UPDATE sd_pengguna SET password_hash = ? WHERE id_pengguna = ?");
                $stmtPw->bind_param('si', $newHash, $user['id']);
                if ($stmtPw->execute()) {
                    $success = 'Password berhasil diubah.';
                } else {
                    $errors[] = 'Gagal mengubah password.';
                }
                $stmtPw->close();
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="app-main">
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="app-content">

    <!-- Header Judul Halaman -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Profil Saya</h4>
            <div class="text-muted small">Informasi kepegawaian dan pengaturan akun pribadi Anda.</div>
        </div>
        <?php if ($d): ?>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= e(app_url('dosen/jadwal.php')) ?>" class="btn btn-outline-primary rounded-3 px-3">
                <i class="bi bi-calendar3-week me-2"></i>Lihat Jadwal Mengajar
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show alert-auto-dismiss border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= e($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!$d): ?>
    <!-- Akun belum terhubung ke data dosen -->
    <div class="card border-0 text-center py-5 shadow-sm">
        <div class="card-body">
            <i class="bi bi-person-x display-1 text-muted d-block mb-3"></i>
            <h5 class="fw-bold">Akun Belum Terhubung ke Data Dosen</h5>
            <p class="text-muted">Akun Anda belum dihubungkan ke data dosen oleh Administrator.<br>
            Silakan hubungi Administrator untuk menghubungkan akun dengan data dosen Anda.</p>
        </div>
    </div>
    <?php else: ?>

    <!-- ── Header Banner Identitas Dosen (Full Desktop Width) ── -->
    <div class="card border-0 mb-4 text-white overflow-hidden shadow-sm" style="background: linear-gradient(135deg, #0b2d59 0%, #1458b8 100%); border-radius: 18px;">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center g-4">
                <!-- Avatar & Identitas Pokok -->
                <div class="col-lg-8">
                    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-3 gap-sm-4">
                        <div class="rounded-circle bg-white text-primary d-grid fw-bold shadow flex-shrink-0"
                             style="width: 86px; height: 86px; font-size: 2.3rem; display: grid; place-items: center; border: 4px solid rgba(255,255,255,0.25);">
                            <?= strtoupper(substr($d['nama'] ?? 'D', 0, 1)) ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <span class="badge bg-<?= ($d['status_aktivitas'] ?? '') === 'Aktif' ? 'success' : 'warning text-dark' ?> rounded-pill px-3 py-1">
                                    <i class="bi bi-check-circle-fill me-1"></i>Status: <?= e($d['status_aktivitas'] ?? 'Aktif') ?>
                                </span>
                                <?php if (($d['status_serdos'] ?? '') === 'Sudah'): ?>
                                <span class="badge bg-info text-dark rounded-pill px-3 py-1">
                                    <i class="bi bi-patch-check-fill me-1"></i>Tersertifikasi Dosen
                                </span>
                                <?php endif; ?>
                                <?php if (!empty($d['nama_jabatan'])): ?>
                                <span class="badge rounded-pill px-3 py-1 text-white border border-light-subtle" style="background: rgba(255,255,255,0.18);">
                                    <i class="bi bi-award me-1"></i><?= e($d['nama_jabatan']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <h3 class="fw-bold mb-2 text-white text-break" style="font-size: clamp(1.35rem, 2.2vw, 1.85rem); line-height: 1.3;"><?= e($d['nama']) ?></h3>
                            <div class="text-white-50 small d-flex flex-wrap gap-3">
                                <span><i class="bi bi-card-text me-1 text-white"></i>NIDN: <strong class="text-white"><?= e($d['nomor_registrasi'] ?: '-') ?></strong></span>
                                <span><i class="bi bi-person-badge me-1 text-white"></i>NIP: <strong class="text-white"><?= e($d['nip'] ?: '-') ?></strong></span>
                                <span><i class="bi bi-buildings me-1 text-white"></i><strong class="text-white"><?= e($d['program_studi'] ?: 'Prodi belum ditentukan') ?></strong></span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Sisi Kanan Desktop: Quick Action -->
                <div class="col-lg-4 text-lg-end">
                    <div class="d-inline-flex flex-column gap-2 text-lg-end w-100 w-lg-auto">
                        <a href="<?= e(app_url('dosen/jadwal.php')) ?>" class="btn btn-light text-primary fw-bold px-4 py-2 rounded-3 shadow-sm">
                            <i class="bi bi-calendar3-week me-2"></i>Jadwal Mengajar
                        </a>
                        <div class="text-white-50 small">
                            <i class="bi bi-mortarboard me-1"></i><?= e($d['fakultas'] ?: 'Universitas Muhammadiyah Bengkulu') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Baris Informasi Detail (2 Kolom Seimbang di Desktop) ── -->
    <div class="row g-4 mb-4">
        <!-- Kolom Kiri: Informasi Akademik & Homebase -->
        <div class="col-12 col-lg-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex align-items-center gap-2 border-bottom">
                    <i class="bi bi-mortarboard text-primary fs-5"></i>
                    <span class="fw-bold text-dark">Informasi Akademik &amp; Homebase</span>
                </div>
                <div class="card-body p-4">
                    <dl class="row mb-0 gy-3">
                        <dt class="col-sm-5 text-muted small">Program Studi</dt>
                        <dd class="col-sm-7 fw-bold text-dark mb-0"><?= e($d['program_studi'] ?: '-') ?></dd>

                        <dt class="col-sm-5 text-muted small">Fakultas</dt>
                        <dd class="col-sm-7 fw-bold text-dark mb-0"><?= e($d['fakultas'] ?: '-') ?></dd>

                        <dt class="col-sm-5 text-muted small">Perguruan Tinggi</dt>
                        <dd class="col-sm-7 text-dark mb-0"><?= e($d['universitas'] ?: 'Universitas Muhammadiyah Bengkulu') ?></dd>

                        <dt class="col-sm-5 text-muted small">Jabatan Fungsional</dt>
                        <dd class="col-sm-7 mb-0">
                            <span class="badge bg-primary px-3 py-1"><?= e($d['nama_jabatan'] ?: 'Belum Memiliki') ?></span>
                        </dd>

                        <dt class="col-sm-5 text-muted small">Pendidikan Terakhir</dt>
                        <dd class="col-sm-7 fw-bold text-dark mb-0"><?= e($d['jenjang'] ?: '-') ?></dd>

                        <dt class="col-sm-5 text-muted small">Rumpun Ilmu</dt>
                        <dd class="col-sm-7 text-dark mb-0"><?= e($d['nama_rumpun'] ?: '-') ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Status Kepegawaian & Kepangkatan -->
        <div class="col-12 col-lg-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex align-items-center gap-2 border-bottom">
                    <i class="bi bi-award text-primary fs-5"></i>
                    <span class="fw-bold text-dark">Status Kepegawaian &amp; Kepangkatan</span>
                </div>
                <div class="card-body p-4">
                    <dl class="row mb-0 gy-3">
                        <dt class="col-sm-5 text-muted small">Pangkat / Golongan</dt>
                        <dd class="col-sm-7 fw-bold text-dark mb-0">
                            <?= e($d['nama_pangkat'] ?: '-') ?>
                            <?php if (!empty($d['golongan'])): ?>
                            <span class="badge bg-light text-primary border ms-1"><?= e($d['golongan']) ?>/<?= e($d['ruang']) ?></span>
                            <?php endif; ?>
                        </dd>

                        <dt class="col-sm-5 text-muted small">Status Pegawai</dt>
                        <dd class="col-sm-7 text-dark mb-0"><?= e($d['status_pegawai'] ?: '-') ?></dd>

                        <dt class="col-sm-5 text-muted small">Ikatan Kerja</dt>
                        <dd class="col-sm-7 text-dark mb-0"><?= e($d['ikatan_kerja'] ?: '-') ?></dd>

                        <dt class="col-sm-5 text-muted small">NUPTK</dt>
                        <dd class="col-sm-7 text-dark mb-0"><?= e($d['nuptk'] ?: '-') ?></dd>

                        <dt class="col-sm-5 text-muted small">Sertifikasi Dosen</dt>
                        <dd class="col-sm-7 mb-0">
                            <?= ($d['status_serdos'] ?? '') === 'Sudah'
                                ? '<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Sudah Tersertifikasi</span>'
                                : '<span class="text-muted">Belum</span>' ?>
                        </dd>

                        <dt class="col-sm-5 text-muted small">Terdaftar Pada</dt>
                        <dd class="col-sm-7 text-muted small mb-0">
                            <?= !empty($d['created_at']) ? date('d F Y', strtotime($d['created_at'])) : '-' ?>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Pengaturan Akun (2 Kolom Seimbang di Desktop) ── -->
    <div class="row g-4">
        <!-- Edit Profil Akun -->
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex align-items-center gap-2 border-bottom">
                    <i class="bi bi-person-gear text-primary fs-5"></i>
                    <span class="fw-bold text-dark">Edit Data Akun</span>
                </div>
                <div class="card-body p-4">
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_profil">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lengkap" class="form-control"
                                   value="<?= e($akun['nama_lengkap'] ?? $user['nama']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Username</label>
                            <input type="text" class="form-control bg-light" value="<?= e($akun['username'] ?? $user['username']) ?>" disabled>
                            <div class="form-text small">Username/NIDN tidak dapat diubah sendiri.</div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold small">Alamat Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= e($akun['email'] ?? '') ?>" placeholder="email@umb.ac.id">
                        </div>
                        <button type="submit" class="btn btn-primary rounded-3 px-4">
                            <i class="bi bi-check-lg me-1"></i>Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Ganti Password -->
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex align-items-center gap-2 border-bottom">
                    <i class="bi bi-shield-lock text-primary fs-5"></i>
                    <span class="fw-bold text-dark">Keamanan Akun (Ganti Password)</span>
                </div>
                <div class="card-body p-4">
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="ganti_password">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Password Lama <span class="text-danger">*</span></label>
                            <input type="password" name="password_lama" class="form-control" required autocomplete="current-password"
                                   placeholder="Masukkan password lama saat ini">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Password Baru <span class="text-danger">*</span></label>
                            <input type="password" name="password_baru" class="form-control" required autocomplete="new-password"
                                   minlength="8" placeholder="Minimal 8 karakter">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold small">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                            <input type="password" name="password_ulang" class="form-control" required autocomplete="new-password"
                                   minlength="8" placeholder="Ulangi password baru">
                        </div>
                        <button type="submit" class="btn btn-warning text-dark fw-bold rounded-3 px-4">
                            <i class="bi bi-key me-1"></i>Ubah Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
