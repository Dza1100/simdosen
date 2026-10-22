<?php
// dosen/index.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../koneksi.php';

require_login();

$page_title = 'Data Pokok Dosen';
$user = current_user();

// Flash message
$success = $_SESSION['flash_success'] ?? '';
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Filter & Pencarian
$q = trim((string)($_GET['q'] ?? ''));
$filterProdi = (int)($_GET['prodi'] ?? 0);
$filterJabatan = (int)($_GET['jabatan'] ?? 0);
$filterStatus = trim((string)($_GET['status'] ?? ''));
$filterSerdos = trim((string)($_GET['serdos'] ?? ''));

// Pagination
$limit = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Bangun klausa WHERE
$where = [];
$params = [];
$types = '';

if ($q !== '') {
    $where[] = "(d.nama LIKE ? OR d.nomor_registrasi LIKE ? OR d.nip LIKE ?)";
    $likeQ = '%' . $q . '%';
    $params[] = $likeQ;
    $params[] = $likeQ;
    $params[] = $likeQ;
    $types .= 'sss';
}
if ($filterProdi > 0) {
    $where[] = "d.id_penempatan = ?";
    $params[] = $filterProdi;
    $types .= 'i';
}
if ($filterJabatan > 0) {
    $where[] = "d.id_jabatan = ?";
    $params[] = $filterJabatan;
    $types .= 'i';
}
if ($filterStatus !== '') {
    $where[] = "d.status_aktivitas = ?";
    $params[] = $filterStatus;
    $types .= 's';
}
if ($filterSerdos !== '') {
    $where[] = "d.status_serdos = ?";
    $params[] = $filterSerdos;
    $types .= 's';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Hitung total data
$sqlCount = "SELECT COUNT(*) AS total FROM dosen d $whereClause";
$stmtCount = $conn->prepare($sqlCount);
if (!empty($params)) {
    $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$totalData = (int)$stmtCount->get_result()->fetch_assoc()['total'];
$stmtCount->close();

$totalPages = max(1, (int)ceil($totalData / $limit));

// Query Data Dosen
$sqlData = "SELECT d.id_dosen, d.nama, d.nomor_registrasi, d.nip, d.status_aktivitas, d.status_serdos,
                   j.nama_jabatan, p.program_studi, p.fakultas, pd.jenjang, k.nama_pangkat, k.golongan, k.ruang
            FROM dosen d
            LEFT JOIN jabatan_fungsional j ON d.id_jabatan = j.id_jabatan
            LEFT JOIN penempatan p ON d.id_penempatan = p.id_penempatan
            LEFT JOIN pendidikan pd ON d.id_pendidikan = pd.id_pendidikan
            LEFT JOIN kepangkatan k ON d.id_kepangkatan = k.id_kepangkatan
            $whereClause
            ORDER BY d.nama ASC
            LIMIT ? OFFSET ?";

$dataParams = $params;
$dataParams[] = $limit;
$dataParams[] = $offset;
$dataTypes = $types . 'ii';

$stmtData = $conn->prepare($sqlData);
$stmtData->bind_param($dataTypes, ...$dataParams);
$stmtData->execute();
$resDosen = $stmtData->get_result();
$stmtData->close();

// Ambil referensi untuk opsi filter
$listProdi = $conn->query("SELECT id_penempatan, program_studi, fakultas FROM penempatan ORDER BY fakultas, program_studi");
$listJabatan = $conn->query("SELECT id_jabatan, nama_jabatan FROM jabatan_fungsional ORDER BY id_jabatan");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="app-main">
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="app-content">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Data Pokok Dosen</h4>
            <div class="text-muted small">Kelola informasi data pengajar, jabatan fungsional, dan status kepegawaian.</div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= e(app_url('laporan/cetak.php?' . http_build_query($_GET))) ?>" target="_blank" class="btn btn-outline-secondary rounded-3">
                <i class="bi bi-printer me-1"></i>Cetak Daftar
            </a>
            <?php if (has_role('ADMIN', 'OPERATOR_PRODI')): ?>
                <a href="<?= e(app_url('dosen/tambah.php')) ?>" class="btn btn-primary rounded-3">
                    <i class="bi bi-person-plus-fill me-1"></i>Tambah Dosen Baru
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show alert-auto-dismiss border-0 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= e($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filter Card -->
    <div class="card mb-4">
        <div class="card-body p-3">
            <form method="get" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control border-start-0" placeholder="Cari Nama, NIDN, atau NIP..." value="<?= e($q) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="prodi" class="form-select">
                        <option value="">Semua Prodi</option>
                        <?php if ($listProdi): while ($p = $listProdi->fetch_assoc()): ?>
                            <option value="<?= $p['id_penempatan'] ?>" <?= $filterProdi === (int)$p['id_penempatan'] ? 'selected' : '' ?>>
                                <?= e($p['program_studi']) ?>
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="jabatan" class="form-select">
                        <option value="">Semua Jabatan</option>
                        <?php if ($listJabatan): while ($j = $listJabatan->fetch_assoc()): ?>
                            <option value="<?= $j['id_jabatan'] ?>" <?= $filterJabatan === (int)$j['id_jabatan'] ? 'selected' : '' ?>>
                                <?= e($j['nama_jabatan']) ?>
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="Aktif" <?= $filterStatus === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="Cuti" <?= $filterStatus === 'Cuti' ? 'selected' : '' ?>>Cuti</option>
                        <option value="Pensiun" <?= $filterStatus === 'Pensiun' ? 'selected' : '' ?>>Pensiun</option>
                        <option value="Tidak Aktif" <?= $filterStatus === 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        Filter
                    </button>
                    <?php if ($q !== '' || $filterProdi > 0 || $filterJabatan > 0 || $filterStatus !== '' || $filterSerdos !== ''): ?>
                        <a href="<?= e(app_url('dosen/index.php')) ?>" class="btn btn-outline-secondary" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Dosen -->
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span class="fw-bold">Total Ditemukan: <?= number_format($totalData) ?> Dosen</span>
            <span class="small text-muted">Halaman <?= $page ?> dari <?= $totalPages ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-muted">
                    <tr>
                        <th class="ps-3" style="width: 50px;">No</th>
                        <th>Nama Dosen & Gelar</th>
                        <th>NIDN / NIP</th>
                        <th>Jabatan & Pangkat</th>
                        <th>Program Studi</th>
                        <th>Status Aktivitas</th>
                        <th>Serdos</th>
                        <th class="text-end pe-3" style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resDosen && $resDosen->num_rows > 0): $no = $offset + 1; ?>
                        <?php while ($row = $resDosen->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-3 fw-semibold text-muted"><?= $no++ ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($row['nama']) ?></div>
                                    <small class="badge bg-light text-secondary border">Pendidikan: <?= e($row['jenjang'] ?? '-') ?></small>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= e($row['nomor_registrasi'] ?: '-') ?></div>
                                    <small class="text-muted">NIP: <?= e($row['nip'] ?: '-') ?></small>
                                </td>
                                <td>
                                    <div class="small fw-bold text-primary"><?= e($row['nama_jabatan'] ?? 'Belum ada') ?></div>
                                    <small class="text-muted">
                                        <?= e($row['nama_pangkat'] ?? '-') ?> <?= ($row['golongan'] ? '(' . e($row['golongan']) . '/' . e($row['ruang']) . ')' : '') ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= e($row['program_studi'] ?? 'Belum ada prodi') ?></div>
                                    <small class="text-muted"><?= e($row['fakultas'] ?? '') ?></small>
                                </td>
                                <td>
                                    <?php if ($row['status_aktivitas'] === 'Aktif'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Aktif</span>
                                    <?php elseif ($row['status_aktivitas'] === 'Cuti'): ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">Cuti</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill"><?= e($row['status_aktivitas']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['status_serdos'] === 'Sudah'): ?>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill">Tersertifikasi</span>
                                    <?php else: ?>
                                        <span class="text-muted small">Belum</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= e(app_url('dosen/detail.php?id=' . $row['id_dosen'])) ?>" class="btn btn-light border" title="Detail Profil">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (has_role('ADMIN', 'OPERATOR_PRODI')): ?>
                                            <a href="<?= e(app_url('dosen/edit.php?id=' . $row['id_dosen'])) ?>" class="btn btn-light border" title="Edit Data">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="post" action="<?= e(app_url('dosen/hapus.php')) ?>" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data dosen ini?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id_dosen" value="<?= $row['id_dosen'] ?>">
                                                <button type="submit" class="btn btn-light border text-danger" title="Hapus Data">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary"></i>
                                Tidak ditemukan data dosen yang sesuai dengan kriteria pencarian.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white d-flex align-items-center justify-content-between py-3">
                <span class="small text-muted">Menampilkan <?= min($totalData, $offset + 1) ?> - <?= min($totalData, $offset + $limit) ?> dari <?= $totalData ?> data</span>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Sebelumnya</a>
                        </li>
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Berikutnya</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
