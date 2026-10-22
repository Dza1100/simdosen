<?php
// pengguna/index.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../koneksi.php';

require_role('ADMIN');

$page_title = 'Manajemen Pengguna & RBAC';
$user = current_user();
$errors = [];
$success = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!csrf_validate(is_string($token) ? $token : null)) {
        $errors[] = 'Token keamanan tidak valid atau telah kedaluwarsa.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $nama = trim((string)($_POST['nama_lengkap'] ?? ''));
            $username = trim((string)($_POST['username'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $peran = (string)($_POST['peran'] ?? 'DOSEN');
            $aktif = isset($_POST['aktif']) ? 1 : 0;

            if ($nama === '' || $username === '' || $password === '') {
                $errors[] = 'Nama lengkap, username, dan password wajib diisi.';
            } elseif (strlen($password) < 8) {
                $errors[] = 'Password minimal 8 karakter.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO sd_pengguna (nama_lengkap, username, email, password_hash, peran, aktif) VALUES (?, ?, NULLIF(?, ''), ?, ?, ?)");
                $stmt->bind_param('sssssi', $nama, $username, $email, $hash, $peran, $aktif);
                if ($stmt->execute()) {
                    $success = "Pengguna '$username' ($peran) berhasil ditambahkan!";
                } else {
                    $errors[] = 'Gagal menambah pengguna: username atau email mungkin sudah digunakan.';
                }
                $stmt->close();
            }
        } elseif ($action === 'update') {
            $id = (int)($_POST['id_pengguna'] ?? 0);
            $nama = trim((string)($_POST['nama_lengkap'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $peran = (string)($_POST['peran'] ?? 'DOSEN');
            $aktif = isset($_POST['aktif']) ? 1 : 0;

            if ($id === $user['id'] && $aktif === 0) {
                $errors[] = 'Anda tidak dapat menonaktifkan akun Anda sendiri yang sedang aktif.';
            } elseif ($id <= 0 || $nama === '') {
                $errors[] = 'Data tidak lengkap.';
            } else {
                if ($password !== '') {
                    if (strlen($password) < 8) {
                        $errors[] = 'Password baru minimal 8 karakter.';
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE sd_pengguna SET nama_lengkap = ?, email = NULLIF(?, ''), password_hash = ?, peran = ?, aktif = ? WHERE id_pengguna = ?");
                        $stmt->bind_param('ssssii', $nama, $email, $hash, $peran, $aktif, $id);
                        $stmt->execute();
                        $stmt->close();
                        $success = 'Data pengguna dan kata sandi berhasil diperbarui!';
                    }
                } else {
                    $stmt = $conn->prepare("UPDATE sd_pengguna SET nama_lengkap = ?, email = NULLIF(?, ''), peran = ?, aktif = ? WHERE id_pengguna = ?");
                    $stmt->bind_param('sssii', $nama, $email, $peran, $aktif, $id);
                    $stmt->execute();
                    $stmt->close();
                    $success = 'Data pengguna berhasil diperbarui!';
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id_pengguna'] ?? 0);
            if ($id === $user['id']) {
                $errors[] = 'Anda tidak dapat menghapus akun Anda sendiri!';
            } else {
                $del = $conn->prepare("DELETE FROM sd_pengguna WHERE id_pengguna = ?");
                $del->bind_param('i', $id);
                if ($del->execute()) {
                    $success = 'Akun pengguna berhasil dihapus!';
                } else {
                    $errors[] = 'Gagal menghapus pengguna: ' . $del->error;
                }
                $del->close();
            }
        }
    }
}

$resUsers = $conn->query("SELECT id_pengguna, nama_lengkap, username, email, peran, aktif, dibuat_pada FROM sd_pengguna ORDER BY id_pengguna ASC");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="app-main">
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="app-content">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Manajemen Pengguna & RBAC</h4>
            <div class="text-muted small">Kelola hak akses akun pengguna sistem SIM Dosen (Admin, Operator, Fakultas, Pimpinan, Dosen).</div>
        </div>
        <button type="button" class="btn btn-primary rounded-3" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-person-plus-fill me-1"></i>Tambah Pengguna
        </button>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show alert-auto-dismiss border-0 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= e($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Peringatan:</div>
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span class="fw-bold">Daftar Akun Pengguna</span>
            <span class="badge bg-light text-dark border"><?= $resUsers ? $resUsers->num_rows : 0 ?> Pengguna</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-muted">
                    <tr>
                        <th class="ps-3" style="width: 50px;">No</th>
                        <th>Nama & Username</th>
                        <th>Email</th>
                        <th>Peran / Hak Akses</th>
                        <th>Status</th>
                        <th>Terdaftar</th>
                        <th class="text-end pe-3" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resUsers && $resUsers->num_rows > 0): $no = 1; ?>
                        <?php while ($u = $resUsers->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-3 fw-semibold text-muted"><?= $no++ ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($u['nama_lengkap']) ?></div>
                                    <small class="text-muted">@<?= e($u['username']) ?></small>
                                </td>
                                <td><?= e($u['email'] ?: '-') ?></td>
                                <td>
                                    <span class="badge <?= e(role_badge_class($u['peran'])) ?> px-3 py-1 rounded-pill">
                                        <?= e(role_label($u['peran'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($u['aktif']): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small"><?= date('d M Y', strtotime($u['dibuat_pada'])) ?></td>
                                <td class="text-end pe-3">
                                    <button type="button" class="btn btn-light btn-sm border me-1 btn-edit"
                                            data-id="<?= $u['id_pengguna'] ?>"
                                            data-nama="<?= e($u['nama_lengkap']) ?>"
                                            data-username="<?= e($u['username']) ?>"
                                            data-email="<?= e($u['email'] ?? '') ?>"
                                            data-peran="<?= e($u['peran']) ?>"
                                            data-aktif="<?= $u['aktif'] ?>"
                                            title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($u['id_pengguna'] !== $user['id']): ?>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun ini?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id_pengguna" value="<?= $u['id_pengguna'] ?>">
                                            <button type="submit" class="btn btn-light btn-sm border text-danger" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah Pengguna -->
    <div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="create">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Tambah Akun Pengguna</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lengkap" class="form-control" required placeholder="Contoh: Rahmat Hidayat, M.Kom.">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" required placeholder="Contoh: rahmat.hidayat">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Alamat Email</label>
                            <input type="email" name="email" class="form-control" placeholder="Contoh: rahmat@umb.ac.id">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Kata Sandi Awal <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" minlength="8" required placeholder="Minimal 8 karakter">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Peran / Hak Akses (RBAC) <span class="text-danger">*</span></label>
                            <select name="peran" class="form-select" required>
                                <option value="ADMIN">Administrator (Akses Penuh)</option>
                                <option value="OPERATOR_PRODI">Operator Prodi</option>
                                <option value="FAKULTAS">Fakultas</option>
                                <option value="PIMPINAN">Pimpinan</option>
                                <option value="DOSEN" selected>Dosen</option>
                            </select>
                        </div>
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" name="aktif" id="aktifTambahU" checked>
                            <label class="form-check-label small" for="aktifTambahU">Akun Aktif</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4">Simpan Pengguna</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Pengguna -->
    <div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id_pengguna" id="editId">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Edit Akun Pengguna</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Username</label>
                            <input type="text" id="editUsername" class="form-control bg-light" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lengkap" id="editNama" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Alamat Email</label>
                            <input type="email" name="email" id="editEmail" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Reset Password (Kosongkan jika tidak diubah)</label>
                            <input type="password" name="password" class="form-control" placeholder="Isi untuk mereset kata sandi">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Peran / Hak Akses (RBAC) <span class="text-danger">*</span></label>
                            <select name="peran" id="editPeran" class="form-select" required>
                                <option value="ADMIN">Administrator</option>
                                <option value="OPERATOR_PRODI">Operator Prodi</option>
                                <option value="FAKULTAS">Fakultas</option>
                                <option value="PIMPINAN">Pimpinan</option>
                                <option value="DOSEN">Dosen</option>
                            </select>
                        </div>
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" name="aktif" id="editAktif">
                            <label class="form-check-label small" for="editAktif">Akun Aktif</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editModal = new bootstrap.Modal(document.getElementById('modalEdit'));
    document.querySelectorAll('.btn-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('editId').value = this.dataset.id;
            document.getElementById('editNama').value = this.dataset.nama;
            document.getElementById('editUsername').value = this.dataset.username;
            document.getElementById('editEmail').value = this.dataset.email;
            document.getElementById('editPeran').value = this.dataset.peran;
            document.getElementById('editAktif').checked = (this.dataset.aktif === '1');
            editModal.show();
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
