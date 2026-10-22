<?php
// laporan/index.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../koneksi.php';

require_role('ADMIN', 'OPERATOR_PRODI', 'FAKULTAS', 'PIMPINAN');

$page_title = 'Bahan Normalisasi / Contoh Laporan Daftar Dosen';
$user = current_user();

// Filter
$filterProdi = (int)($_GET['prodi'] ?? 0);
$filterJabatan = (int)($_GET['jabatan'] ?? 0);
$filterStatus = trim((string)($_GET['status'] ?? ''));
$filterSerdos = trim((string)($_GET['serdos'] ?? ''));

// Ambil info nama universitas, fakultas, dan prodi
$namaUniv = "Universitas Muhammadiyah Bengkulu";
$namaFakultas = "Teknik";
$namaProdi = "Teknik Informatika";

if ($filterProdi > 0) {
    $stmtP = $conn->prepare("SELECT universitas, fakultas, program_studi FROM penempatan WHERE id_penempatan = ? LIMIT 1");
    $stmtP->bind_param('i', $filterProdi);
    $stmtP->execute();
    $infoP = $stmtP->get_result()->fetch_assoc();
    $stmtP->close();
    if ($infoP) {
        $namaUniv = $infoP['universitas'] ?: $namaUniv;
        $namaFakultas = $infoP['fakultas'] ?: $namaFakultas;
        $namaProdi = $infoP['program_studi'] ?: $namaProdi;
    }
}

$where = [];
$params = [];
$types = '';

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

// Query Rekap Data (14 Atribut Lengkap)
$sql = "SELECT d.id_dosen, d.nomor_registrasi, d.nuptk, d.nama, d.nip,
               j.nama_jabatan AS jabatan_fungsional,
               k.nama_pangkat, k.golongan, k.ruang,
               pd.jenjang AS pendidikan_terakhir,
               r.nama_rumpun AS rumpun_ilmu,
               d.status_serdos,
               s.status_pegawai, s.ikatan_kerja,
               d.status_aktivitas,
               p.universitas, p.fakultas, p.program_studi
        FROM dosen d
        LEFT JOIN jabatan_fungsional j ON d.id_jabatan = j.id_jabatan
        LEFT JOIN kepangkatan k ON d.id_kepangkatan = k.id_kepangkatan
        LEFT JOIN pendidikan pd ON d.id_pendidikan = pd.id_pendidikan
        LEFT JOIN rumpun_ilmu r ON d.id_rumpun_ilmu = r.id_rumpun_ilmu
        LEFT JOIN status_kepegawaian s ON d.id_status_kepegawaian = s.id_status_kepegawaian
        LEFT JOIN penempatan p ON d.id_penempatan = p.id_penempatan
        $whereClause
        ORDER BY d.nama ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resDosen = $stmt->get_result();
$stmt->close();

$totalRekap = $resDosen ? $resDosen->num_rows : 0;

// Opsi Filter
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
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-1">
                Format Bahan Normalisasi Basis Data
            </span>
            <h4 class="fw-bold text-dark mb-1">Contoh Laporan Daftar Dosen</h4>
            <div class="text-muted small">Tampilan laporan 14 kolom terstandarisasi sesuai format dokumen akademik kampus.</div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= e(app_url('laporan/export_excel.php?' . http_build_query($_GET))) ?>" class="btn btn-success rounded-3">
                <i class="bi bi-file-earmark-excel me-1"></i>Ekspor Excel (CSV)
            </a>
            <a href="<?= e(app_url('laporan/cetak.php?' . http_build_query($_GET))) ?>" target="_blank" class="btn btn-primary rounded-3">
                <i class="bi bi-printer me-1"></i>Cetak Laporan
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card mb-4">
        <div class="card-header bg-white fw-bold d-flex align-items-center gap-2">
            <i class="bi bi-funnel text-primary"></i>
            <span>Filter Kriteria Laporan</span>
        </div>
        <div class="card-body p-3">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Program Studi</label>
                    <select name="prodi" class="form-select">
                        <option value="">-- Semua Prodi --</option>
                        <?php if ($listProdi): while ($p = $listProdi->fetch_assoc()): ?>
                            <option value="<?= $p['id_penempatan'] ?>" <?= $filterProdi === (int)$p['id_penempatan'] ? 'selected' : '' ?>>
                                <?= e($p['program_studi']) ?> (<?= e($p['fakultas']) ?>)
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Jabatan Fungsional</label>
                    <select name="jabatan" class="form-select">
                        <option value="">-- Semua Jabatan --</option>
                        <?php if ($listJabatan): while ($j = $listJabatan->fetch_assoc()): ?>
                            <option value="<?= $j['id_jabatan'] ?>" <?= $filterJabatan === (int)$j['id_jabatan'] ? 'selected' : '' ?>>
                                <?= e($j['nama_jabatan']) ?>
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Status Serdos</label>
                    <select name="serdos" class="form-select">
                        <option value="">-- Semua --</option>
                        <option value="Sudah" <?= $filterSerdos === 'Sudah' ? 'selected' : '' ?>>Sudah</option>
                        <option value="Belum" <?= $filterSerdos === 'Belum' ? 'selected' : '' ?>>Belum</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Status Aktivitas</label>
                    <select name="status" class="form-select">
                        <option value="">-- Semua --</option>
                        <option value="Aktif" <?= $filterStatus === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="Cuti" <?= $filterStatus === 'Cuti' ? 'selected' : '' ?>>Cuti</option>
                        <option value="Pensiun" <?= $filterStatus === 'Pensiun' ? 'selected' : '' ?>>Pensiun</option>
                        <option value="Tidak Aktif" <?= $filterStatus === 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i>Terapkan
                    </button>
                    <?php if ($filterProdi > 0 || $filterJabatan > 0 || $filterStatus !== '' || $filterSerdos !== ''): ?>
                        <a href="<?= e(app_url('laporan/index.php')) ?>" class="btn btn-outline-secondary" title="Reset">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Preview Tabel Sesuai Format Normalisasi Gambar -->
    <div class="card">
        <div class="card-header bg-white p-4 border-bottom">
            <h5 class="fw-bold mb-3">Daftar Dosen Aktif Universitas</h5>
            <div class="row g-1 small text-dark">
                <div class="col-sm-3 text-muted">Universitas</div>
                <div class="col-sm-9 fw-bold">: <?= e($namaUniv) ?></div>
                <div class="col-sm-3 text-muted">Fakultas</div>
                <div class="col-sm-9 fw-bold">: <?= e($namaFakultas) ?></div>
                <div class="col-sm-3 text-muted">Program Studi</div>
                <div class="col-sm-9 fw-bold">: <?= e($namaProdi) ?></div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 0.85rem;">
                <thead class="table-light text-center" style="white-space: nowrap;">
                    <tr>
                        <th style="width: 35px;">No</th>
                        <th>Nomor Registrasi</th>
                        <th>NUPTK</th>
                        <th>Nama</th>
                        <th>NIP</th>
                        <th>Jabatan Fungsional</th>
                        <th>Kepangkatan</th>
                        <th>Pendidikan Terakhir</th>
                        <th>Rumpun Ilmu</th>
                        <th>Status Serdos</th>
                        <th>Status Pegawai</th>
                        <th>Ikatan Kerja</th>
                        <th>Status Aktivitas</th>
                        <th>Penempatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resDosen && $resDosen->num_rows > 0): $no = 1; ?>
                        <?php while ($r = $resDosen->fetch_assoc()): 
                            $pangkatStr = $r['nama_pangkat'] ?: '-';
                            if (!empty($r['golongan'])) {
                                $pangkatStr .= ' (' . $r['golongan'] . '/' . $r['ruang'] . ')';
                            }
                            $penempatanStr = $r['program_studi'] ?: '-';
                            if (!empty($r['fakultas'])) {
                                $penempatanStr = $r['fakultas'] . ' / ' . $penempatanStr;
                            }
                        ?>
                            <tr>
                                <td class="text-center fw-bold text-muted"><?= $no++ ?></td>
                                <td><?= e($r['nomor_registrasi'] ?: '-') ?></td>
                                <td><?= e($r['nuptk'] ?: '-') ?></td>
                                <td class="fw-bold text-dark"><?= e($r['nama']) ?></td>
                                <td><?= e($r['nip'] ?: '-') ?></td>
                                <td><?= e($r['jabatan_fungsional'] ?: 'Tenaga Pengajar') ?></td>
                                <td><?= e($pangkatStr) ?></td>
                                <td class="text-center"><?= e($r['pendidikan_terakhir'] ?: '-') ?></td>
                                <td><?= e($r['rumpun_ilmu'] ?: '-') ?></td>
                                <td class="text-center"><?= e($r['status_serdos']) ?></td>
                                <td><?= e($r['status_pegawai'] ?: '-') ?></td>
                                <td><?= e($r['ikatan_kerja'] ?: '-') ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $r['status_aktivitas'] === 'Aktif' ? 'bg-success' : 'bg-warning text-dark' ?> rounded-pill">
                                        <?= e($r['status_aktivitas']) ?>
                                    </span>
                                </td>
                                <td><?= e($penempatanStr) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="14" class="text-center py-4 text-muted">Tidak ada data dosen yang sesuai dengan kriteria filter.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-light text-muted small py-2 d-flex justify-content-between align-items-center">
            <span>Menampilkan total <strong><?= $totalRekap ?></strong> dosen terdata.</span>
            <span>Sesuai Format Bahan Normalisasi Basis Data</span>
        </div>
    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
