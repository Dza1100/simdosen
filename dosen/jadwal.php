<?php
// dosen/jadwal.php  — Jadwal Mengajar Pribadi Dosen
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../koneksi.php';

require_role('DOSEN');

$user    = current_user();
$page_title = 'Jadwal Mengajar Saya';

// Ambil id_dosen yang terhubung ke akun ini
$stmtU = $conn->prepare("SELECT id_dosen FROM sd_pengguna WHERE id_pengguna = ? LIMIT 1");
$stmtU->bind_param('i', $user['id']);
$stmtU->execute();
$akun = $stmtU->get_result()->fetch_assoc();
$stmtU->close();

$idDosen = (int)($akun['id_dosen'] ?? 0);

// Ambil data dosen
$namaDosen = '';
if ($idDosen > 0) {
    $stmtN = $conn->prepare("SELECT nama FROM dosen WHERE id_dosen = ? LIMIT 1");
    $stmtN->bind_param('i', $idDosen);
    $stmtN->execute();
    $row = $stmtN->get_result()->fetch_assoc();
    $stmtN->close();
    $namaDosen = $row['nama'] ?? '';
}

// Ambil filter
$filterTahun    = trim((string)($_GET['tahun_akademik'] ?? ''));
$filterSemester = trim((string)($_GET['semester'] ?? ''));

// Ambil jadwal
$jadwal = [];
$hariList = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

if ($idDosen > 0) {
    $where  = ["id_dosen = ?"];
    $params = [$idDosen];
    $types  = 'i';

    if ($filterTahun !== '') {
        $where[]  = "tahun_akademik = ?";
        $params[] = $filterTahun;
        $types   .= 's';
    }
    if ($filterSemester !== '') {
        $where[]  = "semester = ?";
        $params[] = $filterSemester;
        $types   .= 's';
    }

    $sql  = "SELECT * FROM jadwal_mengajar WHERE " . implode(' AND ', $where) . " ORDER BY FIELD(hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), jam_mulai";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($j = $res->fetch_assoc()) {
        $jadwal[$j['hari']][] = $j;
    }
    $stmt->close();

    // Ambil opsi tahun akademik unik
    $resTahun = $conn->query("SELECT DISTINCT tahun_akademik FROM jadwal_mengajar WHERE id_dosen = $idDosen AND tahun_akademik IS NOT NULL ORDER BY tahun_akademik DESC");
    $listTahun = [];
    if ($resTahun) while ($t = $resTahun->fetch_assoc()) $listTahun[] = $t['tahun_akademik'];
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="app-main">
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="app-content">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Jadwal Mengajar Saya</h4>
            <div class="text-muted small">
                <?= $namaDosen ? 'Dosen: <strong>' . e($namaDosen) . '</strong>' : 'Akun belum terhubung ke data dosen.' ?>
            </div>
        </div>
        <a href="<?= e(app_url('dosen/profil_saya.php')) ?>" class="btn btn-light border rounded-3">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Profil
        </a>
    </div>

    <?php if ($idDosen <= 0): ?>
    <div class="card border-0 text-center py-5">
        <div class="card-body">
            <i class="bi bi-calendar-x display-1 text-muted d-block mb-3"></i>
            <h5 class="fw-bold">Akun Belum Terhubung ke Data Dosen</h5>
            <p class="text-muted">Hubungi Administrator untuk menghubungkan akun dengan data dosen Anda.</p>
        </div>
    </div>
    <?php else: ?>

    <!-- Filter Card Responsif -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-3 p-md-4">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <label class="form-label small fw-semibold text-muted mb-1"><i class="bi bi-calendar3 me-1"></i>Tahun Akademik</label>
                    <select name="tahun_akademik" class="form-select">
                        <option value="">Semua Tahun Akademik</option>
                        <?php foreach ($listTahun as $ta): ?>
                        <option value="<?= e($ta) ?>" <?= $filterTahun === $ta ? 'selected' : '' ?>><?= e($ta) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <label class="form-label small fw-semibold text-muted mb-1"><i class="bi bi-clock-history me-1"></i>Semester</label>
                    <select name="semester" class="form-select">
                        <option value="">Semua Semester</option>
                        <option value="Ganjil" <?= $filterSemester === 'Ganjil' ? 'selected' : '' ?>>Ganjil</option>
                        <option value="Genap"  <?= $filterSemester === 'Genap'  ? 'selected' : '' ?>>Genap</option>
                        <option value="Pendek" <?= $filterSemester === 'Pendek' ? 'selected' : '' ?>>Pendek</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4 flex-grow-1">
                        <i class="bi bi-funnel-fill me-1"></i>Filter
                    </button>
                    <?php if ($filterTahun || $filterSemester): ?>
                    <a href="<?= e(app_url('dosen/jadwal.php')) ?>" class="btn btn-outline-secondary" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <?php
    // Jadwal tersusun per hari
    $totalJadwal = 0;
    foreach ($jadwal as $h) $totalJadwal += count($h);
    ?>

    <?php if ($totalJadwal === 0): ?>
    <div class="card border-0 text-center py-5 shadow-sm">
        <div class="card-body">
            <i class="bi bi-calendar-event display-1 text-muted d-block mb-3"></i>
            <h5 class="fw-bold">Belum Ada Jadwal Mengajar Terdaftar</h5>
            <p class="text-muted mb-0">Jadwal perkuliahan Anda untuk periode ini belum diinputkan oleh Bagian Akademik / Administrator.</p>
        </div>
    </div>
    <?php else: ?>

    <!-- Grid Jadwal per Hari (Responsive Desktop 3 Columns) -->
    <div class="row g-4 mb-4">
        <?php
        $hariColors = [
            'Senin'  => ['bg' => '#eaf2ff', 'border' => '#1458b8', 'text' => '#1458b8'],
            'Selasa' => ['bg' => '#e3f7f0', 'border' => '#0d9488', 'text' => '#0d7a69'],
            'Rabu'   => ['bg' => '#fff3cd', 'border' => '#d97706', 'text' => '#b45309'],
            'Kamis'  => ['bg' => '#f0e8ff', 'border' => '#7950bd', 'text' => '#6d28d9'],
            'Jumat'  => ['bg' => '#ffe4e6', 'border' => '#e11d48', 'text' => '#be123c'],
            'Sabtu'  => ['bg' => '#f1f5f9', 'border' => '#475569', 'text' => '#334155'],
        ];
        foreach ($hariList as $hari):
            if (empty($jadwal[$hari])) continue;
            $c = $hariColors[$hari] ?? $hariColors['Sabtu'];
        ?>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card h-100 shadow-sm border-0" style="border-top: 4px solid <?= $c['border'] ?> !important;">
                <div class="card-header fw-bold py-3 d-flex align-items-center justify-content-between"
                     style="background: <?= $c['bg'] ?>; color: <?= $c['text'] ?>;">
                    <span><i class="bi bi-calendar-day me-2"></i><?= $hari ?></span>
                    <span class="badge text-white rounded-pill px-3" style="background: <?= $c['border'] ?>;">
                        <?= count($jadwal[$hari]) ?> Mata Kuliah
                    </span>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($jadwal[$hari] as $idx => $mk): ?>
                    <div class="p-3 <?= $idx < count($jadwal[$hari]) - 1 ? 'border-bottom' : '' ?>">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                            <div class="fw-bold text-dark small"><?= e($mk['mata_kuliah']) ?></div>
                            <?php if (!empty($mk['kode_mk'])): ?>
                            <span class="badge bg-light text-muted border small flex-shrink-0"><?= e($mk['kode_mk']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <span class="badge rounded-pill px-2 py-1" style="background: <?= $c['bg'] ?>; color: <?= $c['text'] ?>; border: 1px solid <?= $c['border'] ?>33;">
                                <i class="bi bi-clock me-1"></i>
                                <?= date('H:i', strtotime($mk['jam_mulai'])) ?> – <?= date('H:i', strtotime($mk['jam_selesai'])) ?>
                            </span>
                            <?php if (!empty($mk['ruang'])): ?>
                            <span class="badge bg-light text-dark border rounded-pill px-2 py-1">
                                <i class="bi bi-door-open me-1"></i><?= e($mk['ruang']) ?>
                            </span>
                            <?php endif; ?>
                            <?php if (!empty($mk['kelas'])): ?>
                            <span class="badge bg-light text-dark border rounded-pill px-2 py-1">
                                <i class="bi bi-people me-1"></i>Kelas <?= e($mk['kelas']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($mk['semester']) || !empty($mk['tahun_akademik'])): ?>
                        <div class="text-muted mt-2 small">
                            <?= e($mk['semester'] ?? '') ?> <?= e($mk['tahun_akademik'] ?? '') ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Tabel Ringkasan Lengkap -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 fw-bold d-flex align-items-center justify-content-between border-bottom">
            <span><i class="bi bi-table text-primary me-2"></i>Ringkasan Seluruh Jadwal Mengajar</span>
            <span class="badge bg-light text-primary border rounded-pill px-3 py-1"><?= $totalJadwal ?> Sesi Perkuliahan</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light text-muted">
                    <tr>
                        <th class="ps-3" style="width: 100px;">Hari</th>
                        <th style="width: 150px;">Jam</th>
                        <th>Mata Kuliah</th>
                        <th>Kelas</th>
                        <th>Ruang</th>
                        <th>Periode</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hariList as $hari): ?>
                    <?php if (empty($jadwal[$hari])) continue; ?>
                    <?php foreach ($jadwal[$hari] as $mk): ?>
                    <tr>
                        <td class="ps-3 fw-bold text-dark"><?= e($hari) ?></td>
                        <td class="text-nowrap">
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($mk['jam_mulai'])) ?> – <?= date('H:i', strtotime($mk['jam_selesai'])) ?>
                            </span>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= e($mk['mata_kuliah']) ?></div>
                            <?php if (!empty($mk['kode_mk'])): ?><small class="text-muted"><?= e($mk['kode_mk']) ?></small><?php endif; ?>
                        </td>
                        <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill"><?= e($mk['kelas'] ?: '-') ?></span></td>
                        <td><i class="bi bi-door-open me-1 text-muted"></i><?= e($mk['ruang'] ?: '-') ?></td>
                        <td class="text-muted"><?= e(($mk['semester'] ?? '') . ' ' . ($mk['tahun_akademik'] ?? '')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
