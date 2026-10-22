<?php
// master/status_kerja.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../koneksi.php';

require_role('ADMIN');

$page_title = 'Master Status Kepegawaian';
$errors = [];
$success = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!csrf_validate(is_string($token) ? $token : null)) {
        $errors[] = 'Token keamanan tidak valid atau telah kedaluwarsa.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $status = trim((string)($_POST['status_pegawai'] ?? ''));
            $ikatan = trim((string)($_POST['ikatan_kerja'] ?? ''));

            if ($status === '' || $ikatan === '') {
                $errors[] = 'Status pegawai dan ikatan kerja wajib diisi.';
            } else {
                $stmt = $conn->prepare("INSERT INTO status_kepegawaian (status_pegawai, ikatan_kerja) VALUES (?, ?)");
                $stmt->bind_param('ss', $status, $ikatan);
                if ($stmt->execute()) {
                    $success = 'Status kepegawaian berhasil ditambahkan!';
                } else {
                    $errors[] = 'Gagal menambah data: kemungkinan kombinasi status sudah ada.';
                }
                $stmt->close();
            }
        } elseif ($action === 'update') {
            $id = (int)($_POST['id_status_kepegawaian'] ?? 0);
            $status = trim((string)($_POST['status_pegawai'] ?? ''));
            $ikatan = trim((string)($_POST['ikatan_kerja'] ?? ''));

            if ($id <= 0 || $status === '' || $ikatan === '') {
                $errors[] = 'Data tidak lengkap.';
            } else {
                $stmt = $conn->prepare("UPDATE status_kepegawaian SET status_pegawai = ?, ikatan_kerja = ? WHERE id_status_kepegawaian = ?");
                $stmt->bind_param('ssi', $status, $ikatan, $id);
                if ($stmt->execute()) {
                    $success = 'Status kepegawaian berhasil diperbarui!';
                } else {
                    $errors[] = 'Gagal memperbarui data: ' . $stmt->error;
                }
                $stmt->close();
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id_status_kepegawaian'] ?? 0);
            $chk = $conn->prepare("SELECT COUNT(*) AS total FROM dosen WHERE id_status_kepegawaian = ?");
            $chk->bind_param('i', $id);
            $chk->execute();
            $terpakai = (int)$chk->get_result()->fetch_assoc()['total'];
            $chk->close();

            if ($terpakai > 0) {
                $errors[] = "Status ini tidak dapat dihapus karena masih digunakan oleh $terpakai dosen.";
            } else {
                $del = $conn->prepare("DELETE FROM status_kepegawaian WHERE id_status_kepegawaian = ?");
                $del->bind_param('i', $id);
                if ($del->execute()) {
                    $success = 'Status kepegawaian berhasil dihapus!';
                } else {
                    $errors[] = 'Gagal menghapus data: ' . $del->error;
                }
                $del->close();
            }
        }
    }
}

$sql = "SELECT s.*, (SELECT COUNT(*) FROM dosen d WHERE d.id_status_kepegawaian = s.id_status_kepegawaian) AS total_dosen
        FROM status_kepegawaian s
        ORDER BY s.id_status_kepegawaian ASC";
$resList = $conn->query($sql);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="app-main">
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="app-content">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Master Status Kepegawaian</h4>
            <div class="text-muted small">Kelola status ikatan kerja dosen (Dosen Tetap, Dosen LB, Penuh Waktu, dsb).</div>
        </div>
        <button type="button" class="btn btn-primary rounded-3" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-lg me-1"></i>Tambah Status
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
            <span class="fw-bold">Daftar Status Kepegawaian</span>
            <span class="badge bg-light text-dark border"><?= $resList ? $resList->num_rows : 0 ?> Data</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-muted">
                    <tr>
                        <th class="ps-3" style="width: 50px;">No</th>
                        <th>Status Pegawai</th>
                        <th>Ikatan Kerja</th>
                        <th>Dosen Terdaftar</th>
                        <th class="text-end pe-3" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resList && $resList->num_rows > 0): $no = 1; ?>
                        <?php while ($r = $resList->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-3 fw-semibold text-muted"><?= $no++ ?></td>
                                <td><span class="fw-bold text-dark"><?= e($r['status_pegawai']) ?></span></td>
                                <td>
                                    <span class="badge bg-light text-secondary border px-2 py-1">
                                        <?= e($r['ikatan_kerja']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-primary border">
                                        <i class="bi bi-people me-1"></i><?= (int)$r['total_dosen'] ?> Dosen
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <button type="button" class="btn btn-light btn-sm border me-1 btn-edit"
                                            data-id="<?= $r['id_status_kepegawaian'] ?>"
                                            data-status="<?= e($r['status_pegawai']) ?>"
                                            data-ikatan="<?= e($r['ikatan_kerja']) ?>"
                                            title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus status ini?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id_status_kepegawaian" value="<?= $r['id_status_kepegawaian'] ?>">
                                        <button type="submit" class="btn btn-light btn-sm border text-danger" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada data status kepegawaian.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah -->
    <div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="create">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Tambah Status Kepegawaian</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Status Pegawai <span class="text-danger">*</span></label>
                            <input type="text" name="status_pegawai" class="form-control" placeholder="Contoh: Dosen Tetap Yayasan" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Ikatan Kerja <span class="text-danger">*</span></label>
                            <select name="ikatan_kerja" class="form-select" required>
                                <option value="Penuh Waktu">Penuh Waktu</option>
                                <option value="Paruh Waktu">Paruh Waktu</option>
                                <option value="Kontrak">Kontrak</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id_status_kepegawaian" id="editId">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Edit Status Kepegawaian</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Status Pegawai <span class="text-danger">*</span></label>
                            <input type="text" name="status_pegawai" id="editStatus" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Ikatan Kerja <span class="text-danger">*</span></label>
                            <select name="ikatan_kerja" id="editIkatan" class="form-select" required>
                                <option value="Penuh Waktu">Penuh Waktu</option>
                                <option value="Paruh Waktu">Paruh Waktu</option>
                                <option value="Kontrak">Kontrak</option>
                            </select>
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
            document.getElementById('editStatus').value = this.dataset.status;
            document.getElementById('editIkatan').value = this.dataset.ikatan;
            editModal.show();
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
