<?php
// master/kepangkatan.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../koneksi.php';

require_role('ADMIN');

$page_title = 'Master Kepangkatan & Golongan';
$errors = [];
$success = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!csrf_validate(is_string($token) ? $token : null)) {
        $errors[] = 'Token keamanan tidak valid atau telah kedaluwarsa.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $nama = trim((string)($_POST['nama_pangkat'] ?? ''));
            $gol = trim((string)($_POST['golongan'] ?? ''));
            $ruang = trim((string)($_POST['ruang'] ?? ''));

            if ($nama === '') {
                $errors[] = 'Nama pangkat wajib diisi.';
            } else {
                $stmt = $conn->prepare("INSERT INTO kepangkatan (nama_pangkat, golongan, ruang) VALUES (?, NULLIF(?, ''), NULLIF(?, ''))");
                $stmt->bind_param('sss', $nama, $gol, $ruang);
                if ($stmt->execute()) {
                    $success = 'Pangkat & Golongan berhasil ditambahkan!';
                } else {
                    $errors[] = 'Gagal menambah data: kemungkinan kombinasi pangkat & golongan sudah terdaftar.';
                }
                $stmt->close();
            }
        } elseif ($action === 'update') {
            $id = (int)($_POST['id_kepangkatan'] ?? 0);
            $nama = trim((string)($_POST['nama_pangkat'] ?? ''));
            $gol = trim((string)($_POST['golongan'] ?? ''));
            $ruang = trim((string)($_POST['ruang'] ?? ''));

            if ($id <= 0 || $nama === '') {
                $errors[] = 'Nama pangkat wajib diisi.';
            } else {
                $stmt = $conn->prepare("UPDATE kepangkatan SET nama_pangkat = ?, golongan = NULLIF(?, ''), ruang = NULLIF(?, '') WHERE id_kepangkatan = ?");
                $stmt->bind_param('sssi', $nama, $gol, $ruang, $id);
                if ($stmt->execute()) {
                    $success = 'Pangkat & Golongan berhasil diperbarui!';
                } else {
                    $errors[] = 'Gagal memperbarui data: ' . $stmt->error;
                }
                $stmt->close();
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id_kepangkatan'] ?? 0);
            $chk = $conn->prepare("SELECT COUNT(*) AS total FROM dosen WHERE id_kepangkatan = ?");
            $chk->bind_param('i', $id);
            $chk->execute();
            $terpakai = (int)$chk->get_result()->fetch_assoc()['total'];
            $chk->close();

            if ($terpakai > 0) {
                $errors[] = "Pangkat tidak dapat dihapus karena sedang dipakai oleh $terpakai dosen.";
            } else {
                $del = $conn->prepare("DELETE FROM kepangkatan WHERE id_kepangkatan = ?");
                $del->bind_param('i', $id);
                if ($del->execute()) {
                    $success = 'Data kepangkatan berhasil dihapus!';
                } else {
                    $errors[] = 'Gagal menghapus data: ' . $del->error;
                }
                $del->close();
            }
        }
    }
}

$sql = "SELECT k.*, (SELECT COUNT(*) FROM dosen d WHERE d.id_kepangkatan = k.id_kepangkatan) AS total_dosen
        FROM kepangkatan k
        ORDER BY k.golongan ASC, k.ruang ASC";
$resList = $conn->query($sql);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="app-main">
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="app-content">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Master Kepangkatan & Golongan</h4>
            <div class="text-muted small">Kelola data pangkat PNS / Kepegawaian (Penata, Pembina, Golongan III/IV, dsb).</div>
        </div>
        <button type="button" class="btn btn-primary rounded-3" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-lg me-1"></i>Tambah Pangkat
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
            <span class="fw-bold">Daftar Pangkat & Golongan</span>
            <span class="badge bg-light text-dark border"><?= $resList ? $resList->num_rows : 0 ?> Data</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-muted">
                    <tr>
                        <th class="ps-3" style="width: 50px;">No</th>
                        <th>Nama Pangkat</th>
                        <th>Golongan / Ruang</th>
                        <th>Dosen Terdaftar</th>
                        <th class="text-end pe-3" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resList && $resList->num_rows > 0): $no = 1; ?>
                        <?php while ($r = $resList->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-3 fw-semibold text-muted"><?= $no++ ?></td>
                                <td><span class="fw-bold text-dark"><?= e($r['nama_pangkat']) ?></span></td>
                                <td>
                                    <?php if ($r['golongan'] || $r['ruang']): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                            <?= e($r['golongan']) ?>/<?= e($r['ruang']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-primary border">
                                        <i class="bi bi-people me-1"></i><?= (int)$r['total_dosen'] ?> Dosen
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <button type="button" class="btn btn-light btn-sm border me-1 btn-edit"
                                            data-id="<?= $r['id_kepangkatan'] ?>"
                                            data-nama="<?= e($r['nama_pangkat']) ?>"
                                            data-gol="<?= e($r['golongan'] ?? '') ?>"
                                            data-ruang="<?= e($r['ruang'] ?? '') ?>"
                                            title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pangkat ini?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id_kepangkatan" value="<?= $r['id_kepangkatan'] ?>">
                                        <button type="submit" class="btn btn-light btn-sm border text-danger" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada data kepangkatan.</td></tr>
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
                        <h5 class="modal-title fw-bold">Tambah Pangkat & Golongan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nama Pangkat <span class="text-danger">*</span></label>
                            <input type="text" name="nama_pangkat" class="form-control" placeholder="Contoh: Penata Muda Tk. I" required>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Golongan</label>
                                <input type="text" name="golongan" class="form-control" placeholder="Contoh: III">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Ruang</label>
                                <input type="text" name="ruang" class="form-control" placeholder="Contoh: b">
                            </div>
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
                    <input type="hidden" name="id_kepangkatan" id="editId">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Edit Pangkat & Golongan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nama Pangkat <span class="text-danger">*</span></label>
                            <input type="text" name="nama_pangkat" id="editNama" class="form-control" required>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Golongan</label>
                                <input type="text" name="golongan" id="editGol" class="form-control">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Ruang</label>
                                <input type="text" name="ruang" id="editRuang" class="form-control">
                            </div>
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
            document.getElementById('editGol').value = this.dataset.gol;
            document.getElementById('editRuang').value = this.dataset.ruang;
            editModal.show();
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
