<?php
// master/penempatan.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../koneksi.php';

require_role('ADMIN');

$page_title = 'Master Penempatan & Program Studi';
$errors = [];
$success = '';

// Handle Tambah / Edit / Hapus
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!csrf_validate(is_string($token) ? $token : null)) {
        $errors[] = 'Token keamanan tidak valid atau telah kedaluwarsa.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $univ = trim((string)($_POST['universitas'] ?? 'Universitas Muhammadiyah Bengkulu'));
            $fakultas = trim((string)($_POST['fakultas'] ?? ''));
            $prodi = trim((string)($_POST['program_studi'] ?? ''));
            $aktif = isset($_POST['aktif']) ? 1 : 0;

            if ($univ === '' || $fakultas === '' || $prodi === '') {
                $errors[] = 'Universitas, Fakultas, dan Program Studi wajib diisi.';
            } else {
                $stmt = $conn->prepare("INSERT INTO penempatan (universitas, fakultas, program_studi, aktif) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('sssi', $univ, $fakultas, $prodi, $aktif);
                if ($stmt->execute()) {
                    $success = 'Unit penempatan / Program Studi berhasil ditambahkan!';
                } else {
                    $errors[] = 'Gagal menambah data: kemungkinan kombinasi prodi sudah terdaftar.';
                }
                $stmt->close();
            }
        } elseif ($action === 'update') {
            $id = (int)($_POST['id_penempatan'] ?? 0);
            $univ = trim((string)($_POST['universitas'] ?? 'Universitas Muhammadiyah Bengkulu'));
            $fakultas = trim((string)($_POST['fakultas'] ?? ''));
            $prodi = trim((string)($_POST['program_studi'] ?? ''));
            $aktif = isset($_POST['aktif']) ? 1 : 0;

            if ($id <= 0 || $univ === '' || $fakultas === '' || $prodi === '') {
                $errors[] = 'Data yang dikirim tidak lengkap.';
            } else {
                $stmt = $conn->prepare("UPDATE penempatan SET universitas = ?, fakultas = ?, program_studi = ?, aktif = ? WHERE id_penempatan = ?");
                $stmt->bind_param('sssii', $univ, $fakultas, $prodi, $aktif, $id);
                if ($stmt->execute()) {
                    $success = 'Unit penempatan / Program Studi berhasil diperbarui!';
                } else {
                    $errors[] = 'Gagal memperbarui data: ' . $stmt->error;
                }
                $stmt->close();
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id_penempatan'] ?? 0);
            // Cek apakah dipakai di data dosen
            $chk = $conn->prepare("SELECT COUNT(*) AS total FROM dosen WHERE id_penempatan = ?");
            $chk->bind_param('i', $id);
            $chk->execute();
            $terpakai = (int)$chk->get_result()->fetch_assoc()['total'];
            $chk->close();

            if ($terpakai > 0) {
                $errors[] = "Penempatan tidak dapat dihapus karena masih digunakan oleh $terpakai dosen.";
            } else {
                $del = $conn->prepare("DELETE FROM penempatan WHERE id_penempatan = ?");
                $del->bind_param('i', $id);
                if ($del->execute()) {
                    $success = 'Unit penempatan berhasil dihapus!';
                } else {
                    $errors[] = 'Gagal menghapus penempatan: ' . $del->error;
                }
                $del->close();
            }
        }
    }
}

// Ambil daftar penempatan
$sql = "SELECT p.*, (SELECT COUNT(*) FROM dosen d WHERE d.id_penempatan = p.id_penempatan) AS total_dosen
        FROM penempatan p
        ORDER BY p.fakultas ASC, p.program_studi ASC";
$resList = $conn->query($sql);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="app-main">
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="app-content">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Master Penempatan & Program Studi</h4>
            <div class="text-muted small">Kelola data fakultas dan program studi untuk penempatan homebase dosen.</div>
        </div>
        <button type="button" class="btn btn-primary rounded-3" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-lg me-1"></i>Tambah Penempatan
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
            <span class="fw-bold">Daftar Penempatan / Program Studi</span>
            <span class="badge bg-light text-dark border"><?= $resList ? $resList->num_rows : 0 ?> Data</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-muted">
                    <tr>
                        <th class="ps-3" style="width: 50px;">No</th>
                        <th>Program Studi</th>
                        <th>Fakultas</th>
                        <th>Universitas</th>
                        <th>Dosen Terdaftar</th>
                        <th>Status</th>
                        <th class="text-end pe-3" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resList && $resList->num_rows > 0): $no = 1; ?>
                        <?php while ($r = $resList->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-3 fw-semibold text-muted"><?= $no++ ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($r['program_studi']) ?></div>
                                </td>
                                <td><?= e($r['fakultas']) ?></td>
                                <td class="text-muted small"><?= e($r['universitas']) ?></td>
                                <td>
                                    <span class="badge bg-light text-primary border">
                                        <i class="bi bi-people me-1"></i><?= (int)$r['total_dosen'] ?> Dosen
                                    </span>
                                </td>
                                <td>
                                    <?php if ($r['aktif']): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <button type="button" class="btn btn-light btn-sm border me-1 btn-edit"
                                            data-id="<?= $r['id_penempatan'] ?>"
                                            data-univ="<?= e($r['universitas']) ?>"
                                            data-fakultas="<?= e($r['fakultas']) ?>"
                                            data-prodi="<?= e($r['program_studi']) ?>"
                                            data-aktif="<?= $r['aktif'] ?>"
                                            title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus prodi ini?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id_penempatan" value="<?= $r['id_penempatan'] ?>">
                                        <button type="submit" class="btn btn-light btn-sm border text-danger" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Belum ada data penempatan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah Penempatan -->
    <div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="create">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Tambah Program Studi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Universitas</label>
                            <input type="text" name="universitas" class="form-control" value="Universitas Muhammadiyah Bengkulu" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Fakultas <span class="text-danger">*</span></label>
                            <input type="text" name="fakultas" class="form-control" placeholder="Contoh: Teknik" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Program Studi <span class="text-danger">*</span></label>
                            <input type="text" name="program_studi" class="form-control" placeholder="Contoh: Sistem Informasi" required>
                        </div>
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" name="aktif" id="aktifTambah" checked>
                            <label class="form-check-label small" for="aktifTambah">Status Aktif</label>
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

    <!-- Modal Edit Penempatan -->
    <div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id_penempatan" id="editId">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Edit Program Studi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Universitas</label>
                            <input type="text" name="universitas" id="editUniv" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Fakultas <span class="text-danger">*</span></label>
                            <input type="text" name="fakultas" id="editFakultas" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Program Studi <span class="text-danger">*</span></label>
                            <input type="text" name="program_studi" id="editProdi" class="form-control" required>
                        </div>
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" name="aktif" id="editAktif">
                            <label class="form-check-label small" for="editAktif">Status Aktif</label>
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
            document.getElementById('editUniv').value = this.dataset.univ;
            document.getElementById('editFakultas').value = this.dataset.fakultas;
            document.getElementById('editProdi').value = this.dataset.prodi;
            document.getElementById('editAktif').checked = (this.dataset.aktif === '1');
            editModal.show();
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
