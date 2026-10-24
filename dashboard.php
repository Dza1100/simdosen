<?php
// dashboard.php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/koneksi.php';

require_login();

$user = current_user();
$page_title = 'Dashboard';

// Query ringkasan statistik
$totalDosen = 0;
$dosenAktif = 0;
$dosenSerdos = 0;
$totalProdi = 0;
$totalUser = 0;

$res1 = $conn->query("SELECT COUNT(*) AS total FROM dosen");
if ($res1) $totalDosen = (int)$res1->fetch_assoc()['total'];

$res2 = $conn->query("SELECT COUNT(*) AS total FROM dosen WHERE status_aktivitas = 'Aktif'");
if ($res2) $dosenAktif = (int)$res2->fetch_assoc()['total'];

$res3 = $conn->query("SELECT COUNT(*) AS total FROM dosen WHERE status_serdos = 'Sudah'");
if ($res3) $dosenSerdos = (int)$res3->fetch_assoc()['total'];

$res4 = $conn->query("SELECT COUNT(*) AS total FROM penempatan");
if ($res4) $totalProdi = (int)$res4->fetch_assoc()['total'];

$res5 = $conn->query("SELECT COUNT(*) AS total FROM sd_pengguna WHERE aktif = 1");
if ($res5) $totalUser = (int)$res5->fetch_assoc()['total'];

// Query 5 Dosen Terbaru
$sqlRecent = "SELECT d.id_dosen, d.nama, d.nip, d.nomor_registrasi, d.status_aktivitas, d.status_serdos,
                     j.nama_jabatan, p.program_studi
              FROM dosen d
              LEFT JOIN jabatan_fungsional j ON d.id_jabatan = j.id_jabatan
              LEFT JOIN penempatan p ON d.id_penempatan = p.id_penempatan
              ORDER BY d.id_dosen DESC
              LIMIT 5";
$recentDosen = $conn->query($sqlRecent);

// Query Sebaran Pendidikan Terakhir
$sqlPendidikan = "SELECT pd.jenjang, COUNT(d.id_dosen) AS jumlah
                  FROM pendidikan pd
                  LEFT JOIN dosen d ON pd.id_pendidikan = d.id_pendidikan
                  GROUP BY pd.id_pendidikan, pd.jenjang
                  ORDER BY pd.urutan ASC";
$resPendidikan = $conn->query($sqlPendidikan);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<div class="app-main">
<?php include __DIR__ . '/includes/navbar.php'; ?>
<div class="app-content">

    <!-- Stat Metric Cards -->
    <div class="row g-3 mb-4">
        <!-- Total Dosen -->
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Total Dosen</span>
                    <div class="rounded-3 d-grid place-items-center" style="width: 40px; height: 40px; background: #eaf2ff; color: #1458b8; display: grid; place-items: center;">
                        <i class="bi bi-people-fill fs-5"></i>
                    </div>
                </div>
                <div class="fs-2 fw-bold text-dark"><?= number_format($totalDosen) ?></div>
                <div class="text-muted small mt-1">
                    <span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i><?= $dosenAktif ?></span> berstatus aktif
                </div>
            </div>
        </div>

        <!-- Dosen Tersertifikasi (Serdos) -->
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Tersertifikasi (Serdos)</span>
                    <div class="rounded-3 d-grid place-items-center" style="width: 40px; height: 40px; background: #e6f9f0; color: #0d9488; display: grid; place-items: center;">
                        <i class="bi bi-patch-check-fill fs-5"></i>
                    </div>
                </div>
                <div class="fs-2 fw-bold text-dark"><?= number_format($dosenSerdos) ?></div>
                <div class="text-muted small mt-1">
                    <?= $totalDosen > 0 ? round(($dosenSerdos / $totalDosen) * 100, 1) : 0 ?>% dari total dosen
                </div>
            </div>
        </div>

        <!-- Program Studi -->
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Unit / Prodi Terdata</span>
                    <div class="rounded-3 d-grid place-items-center" style="width: 40px; height: 40px; background: #fef3c7; color: #d97706; display: grid; place-items: center;">
                        <i class="bi bi-buildings-fill fs-5"></i>
                    </div>
                </div>
                <div class="fs-2 fw-bold text-dark"><?= number_format($totalProdi) ?></div>
                <div class="text-muted small mt-1">Program Studi aktif</div>
            </div>
        </div>

        <!-- Akun Pengguna -->
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Pengguna Sistem</span>
                    <div class="rounded-3 d-grid place-items-center" style="width: 40px; height: 40px; background: #f1f5f9; color: #475569; display: grid; place-items: center;">
                        <i class="bi bi-shield-lock-fill fs-5"></i>
                    </div>
                </div>
                <div class="fs-2 fw-bold text-dark"><?= number_format($totalUser) ?></div>
                <div class="text-muted small mt-1">Pengguna aktif terdaftar</div>
            </div>
        </div>
    </div>

    <!-- Main Section: 2 Columns -->
    <div class="row g-4">
        <!-- Recent Dosen Table -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history text-primary"></i>
                        <span>Data Dosen Terbaru</span>
                    </div>
                    <a href="<?= e(app_url('dosen/index.php')) ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                        Lihat Semua
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th class="ps-3">Nama Dosen</th>
                                <th>NIDN / NIP</th>
                                <th>Jabatan</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recentDosen && $recentDosen->num_rows > 0): ?>
                                <?php while ($row = $recentDosen->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold text-dark"><?= e($row['nama']) ?></div>
                                            <small class="text-muted"><?= e($row['program_studi'] ?? 'Belum ditentukan') ?></small>
                                        </td>
                                        <td>
                                            <div class="small fw-semibold"><?= e($row['nomor_registrasi'] ?: '-') ?></div>
                                            <small class="text-muted">NIP: <?= e($row['nip'] ?: '-') ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?= e($row['nama_jabatan'] ?? 'Belum ada jabatan') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($row['status_aktivitas'] === 'Aktif'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill"><?= e($row['status_aktivitas']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="<?= e(app_url('dosen/detail.php?id=' . $row['id_dosen'])) ?>" class="btn btn-light btn-sm border" title="Lihat Profil">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                        Belum ada data dosen terdaftar.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Side: Educational Level Breakdown & Info -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-mortarboard text-primary"></i>
                    <span>Sebaran Jenjang Pendidikan</span>
                </div>
                <div class="card-body p-3">
                    <?php if ($resPendidikan && $resPendidikan->num_rows > 0): ?>
                        <div class="d-flex flex-column gap-3">
                            <?php while ($p = $resPendidikan->fetch_assoc()): 
                                $pct = $totalDosen > 0 ? round(($p['jumlah'] / $totalDosen) * 100) : 0;
                            ?>
                                <div>
                                    <div class="d-flex justify-content-between small fw-bold mb-1">
                                        <span><?= e($p['jenjang']) ?></span>
                                        <span class="text-muted"><?= $p['jumlah'] ?> Dosen (<?= $pct ?>%)</span>
                                    </div>
                                    <div class="progress" style="height: 7px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $pct ?>%" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-0">Belum ada data jenjang pendidikan.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Info System Card -->
            <div class="card bg-light border-dashed">
                <div class="card-body p-3">
                    <div class="fw-bold text-dark small mb-2"><i class="bi bi-info-circle-fill text-primary me-1"></i> Informasi Hak Akses RBAC</div>
                    <ul class="list-unstyled small text-muted mb-0" style="line-height: 1.6;">
                        <li>• <strong>Administrator:</strong> Akses penuh kelola data dosen, master referensi, akun pengguna & API.</li>
                        <li>• <strong>Operator Prodi:</strong> Kelola data dosen di program studi terkait.</li>
                        <li>• <strong>Pimpinan:</strong> Melihat statistik eksekutif dan mengunduh laporan.</li>
                        <li>• <strong>Dosen:</strong> Mengakses data profil kepegawaian pribadi.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/includes/footer.php'; ?>
