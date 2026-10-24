<?php
// dashboard.php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/koneksi.php';

require_login();

$user = current_user();
$page_title = 'Dashboard';
$isDosen = has_role('DOSEN');

// Data spesifik jika login sebagai DOSEN
$dosenData = null;
$jadwalDosen = [];

if ($isDosen) {
    $stmtU = $conn->prepare("SELECT id_dosen, nama_lengkap FROM sd_pengguna WHERE id_pengguna = ? LIMIT 1");
    $stmtU->bind_param('i', $user['id']);
    $stmtU->execute();
    $uRow = $stmtU->get_result()->fetch_assoc();
    $stmtU->close();

    $idDosen = (int)($uRow['id_dosen'] ?? 0);
    if ($idDosen > 0) {
        $stmtD = $conn->prepare(
            "SELECT d.*, 
                    j.nama_jabatan, 
                    k.nama_pangkat, k.golongan, k.ruang,
                    pd.jenjang AS pendidikan_terakhir,
                    p.fakultas, p.program_studi, p.universitas,
                    s.status_pegawai, s.ikatan_kerja
             FROM dosen d
             LEFT JOIN jabatan_fungsional j   ON d.id_jabatan            = j.id_jabatan
             LEFT JOIN kepangkatan k          ON d.id_kepangkatan        = k.id_kepangkatan
             LEFT JOIN pendidikan pd         ON d.id_pendidikan         = pd.id_pendidikan
             LEFT JOIN status_kepegawaian s   ON d.id_status_kepegawaian = s.id_status_kepegawaian
             LEFT JOIN penempatan p          ON d.id_penempatan         = p.id_penempatan
             WHERE d.id_dosen = ? LIMIT 1"
        );
        $stmtD->bind_param('i', $idDosen);
        $stmtD->execute();
        $dosenData = $stmtD->get_result()->fetch_assoc();
        $stmtD->close();

        // Ambil jadwal mengajar pribadi dosen
        $stmtJ = $conn->prepare("SELECT * FROM jadwal_mengajar WHERE id_dosen = ? ORDER BY FIELD(hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), jam_mulai LIMIT 5");
        $stmtJ->bind_param('i', $idDosen);
        $stmtJ->execute();
        $resJ = $stmtJ->get_result();
        while ($jRow = $resJ->fetch_assoc()) {
            $jadwalDosen[] = $jRow;
        }
        $stmtJ->close();
    }
} else {
    // Query ringkasan statistik untuk ADMIN / OPERATOR / PIMPINAN
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
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<div class="app-main">
<?php include __DIR__ . '/includes/navbar.php'; ?>
<div class="app-content">

    <!-- ── Banner Selamat Datang (Sesuai Desain Biru) ── -->
    <div class="card border-0 mb-4 text-white overflow-hidden shadow-sm" style="background: linear-gradient(135deg, #0b2d59 0%, #1458b8 100%); border-radius: 16px; position: relative;">
        <div class="card-body p-4 p-md-5 position-relative" style="z-index: 2;">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h3 class="fw-bold mb-2">Selamat Datang, <?= e($isDosen ? ($dosenData['nama'] ?? $user['nama']) : $user['nama']) ?>!</h3>
                    <p class="text-white-50 mb-0" style="max-width: 650px;">
                        <?= $isDosen
                            ? 'Selamat datang di Sistem Informasi Manajemen Dosen (SIM Dosen) Universitas Muhammadiyah Bengkulu. Pantau data kepegawaian dan jadwal mengajar pribadi Anda.'
                            : 'Selamat datang di Sistem Informasi Manajemen Dosen (SIM Dosen) Universitas Muhammadiyah Bengkulu. Kelola data pokok, master referensi, dan akun pengguna.' ?>
                    </p>
                    <?php if ($isDosen && $dosenData): ?>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="badge rounded-pill px-3 py-1 text-white border border-light-subtle" style="background: rgba(255,255,255,0.18);">
                            <i class="bi bi-card-text me-1"></i>NIDN: <strong><?= e($dosenData['nomor_registrasi'] ?: '-') ?></strong>
                        </span>
                        <span class="badge rounded-pill px-3 py-1 text-white border border-light-subtle" style="background: rgba(255,255,255,0.18);">
                            <i class="bi bi-person-badge me-1"></i>NIP: <strong><?= e($dosenData['nip'] ?: '-') ?></strong>
                        </span>
                        <span class="badge bg-<?= ($dosenData['status_aktivitas'] ?? '') === 'Aktif' ? 'success' : 'warning text-dark' ?> rounded-pill px-3 py-1">
                            <i class="bi bi-check-circle-fill me-1"></i>Status: <?= e($dosenData['status_aktivitas'] ?? 'Aktif') ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($isDosen): ?>
                <div class="d-flex gap-2">
                    <a href="<?= e(app_url('dosen/profil_saya.php')) ?>" class="btn btn-light text-primary fw-bold rounded-3 px-3 py-2 shadow-sm flex-shrink-0">
                        <i class="bi bi-person-circle me-1"></i>Profil Saya
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Watermark Topi Toga (Sesuai Gambar) -->
        <div style="position: absolute; right: 30px; bottom: 5px; opacity: 0.16; pointer-events: none; z-index: 1;">
            <i class="bi bi-mortarboard" style="font-size: 8rem; line-height: 1;"></i>
        </div>
    </div>

    <!-- ── 4 Kotak Putih ── -->
    <?php if ($isDosen): ?>
    <!-- 4 KOTAK PUTIH KHUSUS DOSEN: Prodi, Fakultas, Jabatan Fungsional, Pendidikan Terakhir -->
    <div class="row g-3 mb-4">
        <!-- 1. Program Studi -->
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 p-3 shadow-sm border-0">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Program Studi</span>
                    <div class="rounded-3 d-grid place-items-center" style="width: 40px; height: 40px; background: #eaf2ff; color: #1458b8; display: grid; place-items: center;">
                        <i class="bi bi-buildings-fill fs-5"></i>
                    </div>
                </div>
                <div class="fs-5 fw-bold text-dark text-truncate" title="<?= e($dosenData['program_studi'] ?? 'Belum ditentukan') ?>">
                    <?= e($dosenData['program_studi'] ?? 'Belum ditentukan') ?>
                </div>
                <div class="text-muted small mt-1">
                    <i class="bi bi-check-circle-fill text-success me-1"></i>Homebase Akademik
                </div>
            </div>
        </div>

        <!-- 2. Fakultas -->
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 p-3 shadow-sm border-0">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Fakultas</span>
                    <div class="rounded-3 d-grid place-items-center" style="width: 40px; height: 40px; background: #e6f9f0; color: #0d9488; display: grid; place-items: center;">
                        <i class="bi bi-bank fs-5"></i>
                    </div>
                </div>
                <div class="fs-5 fw-bold text-dark text-truncate" title="<?= e($dosenData['fakultas'] ?? 'Belum ditentukan') ?>">
                    <?= e($dosenData['fakultas'] ?? 'Belum ditentukan') ?>
                </div>
                <div class="text-muted small mt-1">
                    <?= e($dosenData['universitas'] ?? 'UM Bengkulu') ?>
                </div>
            </div>
        </div>

        <!-- 3. Jabatan Fungsional -->
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 p-3 shadow-sm border-0">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Jabatan Fungsional</span>
                    <div class="rounded-3 d-grid place-items-center" style="width: 40px; height: 40px; background: #fef3c7; color: #d97706; display: grid; place-items: center;">
                        <i class="bi bi-award-fill fs-5"></i>
                    </div>
                </div>
                <div class="fs-5 fw-bold text-dark text-truncate" title="<?= e($dosenData['nama_jabatan'] ?? 'Belum Memiliki') ?>">
                    <?= e($dosenData['nama_jabatan'] ?? 'Belum Memiliki') ?>
                </div>
                <div class="text-muted small mt-1">
                    <?php if (!empty($dosenData['nama_pangkat'])): ?>
                        <?= e($dosenData['nama_pangkat']) ?> (<?= e($dosenData['golongan'] ?? '') ?>/<?= e($dosenData['ruang'] ?? '') ?>)
                    <?php else: ?>
                        Jabatan Akademik Aktif
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 4. Pendidikan Terakhir -->
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 p-3 shadow-sm border-0">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Pendidikan Terakhir</span>
                    <div class="rounded-3 d-grid place-items-center" style="width: 40px; height: 40px; background: #f3e8ff; color: #7c3aed; display: grid; place-items: center;">
                        <i class="bi bi-mortarboard-fill fs-5"></i>
                    </div>
                </div>
                <div class="fs-5 fw-bold text-dark">
                    <?= e($dosenData['pendidikan_terakhir'] ?? '-') ?>
                </div>
                <div class="text-muted small mt-1">
                    <?= ($dosenData['status_serdos'] ?? '') === 'Sudah' ? '<span class="text-success"><i class="bi bi-patch-check-fill me-1"></i>Tersertifikasi Dosen</span>' : 'Jenjang Pendidikan Terdaftar' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ── KONTEN BAWAH KHUSUS DOSEN (JADWAL SAYA & STATUS KEPEGAWAIAN, TANPA TABEL DOSEN LAIN) ── -->
    <div class="row g-4">
        <!-- Jadwal Mengajar Pribadi -->
        <div class="col-lg-8">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-calendar3-week text-primary fs-5"></i>
                        <span class="fw-bold text-dark">Jadwal Mengajar Saya</span>
                    </div>
                    <a href="<?= e(app_url('dosen/jadwal.php')) ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                        Lihat Seluruh Jadwal
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($jadwalDosen)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light text-muted">
                                    <tr>
                                        <th class="ps-3">Hari</th>
                                        <th>Waktu</th>
                                        <th>Mata Kuliah</th>
                                        <th>Kelas</th>
                                        <th>Ruang</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jadwalDosen as $jd): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark"><?= e($jd['hari']) ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?= date('H:i', strtotime($jd['jam_mulai'])) ?> – <?= date('H:i', strtotime($jd['jam_selesai'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= e($jd['mata_kuliah']) ?></div>
                                            <?php if (!empty($jd['kode_mk'])): ?><small class="text-muted"><?= e($jd['kode_mk']) ?></small><?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill"><?= e($jd['kelas'] ?: '-') ?></span></td>
                                        <td><i class="bi bi-door-open me-1 text-muted"></i><?= e($jd['ruang'] ?: '-') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-calendar-check display-4 text-muted d-block mb-2"></i>
                            <h6 class="fw-bold text-dark">Belum Ada Sesi Jadwal Tersedia</h6>
                            <p class="text-muted small mb-0">Jadwal mengajar untuk akun Anda belum tercatat atau belum dijadwalkan oleh bagian akademik.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Rangkuman Kepegawaian & Akses Cepat -->
        <div class="col-lg-4">
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex align-items-center gap-2 border-bottom">
                    <i class="bi bi-person-badge text-primary fs-5"></i>
                    <span class="fw-bold text-dark">Status Kepegawaian</span>
                </div>
                <div class="card-body p-3">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span class="text-muted">Status Pegawai</span>
                            <span class="fw-bold text-dark"><?= e($dosenData['status_pegawai'] ?? '-') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span class="text-muted">Ikatan Kerja</span>
                            <span class="fw-bold text-dark"><?= e($dosenData['ikatan_kerja'] ?? '-') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span class="text-muted">Sertifikasi Dosen</span>
                            <span class="fw-bold"><?= ($dosenData['status_serdos'] ?? '') === 'Sudah' ? '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Sudah</span>' : '<span class="text-muted">Belum</span>' ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span class="text-muted">NUPTK</span>
                            <span class="text-dark"><?= e($dosenData['nuptk'] ?: '-') ?></span>
                        </li>
                    </ul>
                    <div class="mt-3">
                        <a href="<?= e(app_url('dosen/profil_saya.php')) ?>" class="btn btn-primary w-100 btn-sm rounded-3">
                            <i class="bi bi-person-lines-fill me-1"></i>Buka Profil Pribadi Lengkap
                        </a>
                    </div>
                </div>
            </div>

            <!-- Kartu Layanan Mandiri -->
            <div class="card bg-light border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="fw-bold text-dark small mb-2"><i class="bi bi-shield-check text-primary me-1"></i> Layanan Mandiri Dosen</div>
                    <p class="text-muted small mb-3" style="line-height: 1.5;">
                        Anda dapat memperbarui kontak pribadi (No. HP, Email, Alamat) dan mengganti kata sandi secara berkala demi keamanan akun Anda.
                    </p>
                    <a href="<?= e(app_url('dosen/profil_saya.php')) ?>" class="btn btn-outline-secondary btn-sm w-100 rounded-3">
                        <i class="bi bi-key me-1"></i>Ganti Password / Edit Kontak
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- ── 4 KOTAK PUTIH UNTUK ADMIN / OPERATOR / PIMPINAN ── -->
    <div class="row g-3 mb-4">
        <!-- Total Dosen -->
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 p-3 shadow-sm border-0">
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
            <div class="card h-100 p-3 shadow-sm border-0">
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
            <div class="card h-100 p-3 shadow-sm border-0">
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
            <div class="card h-100 p-3 shadow-sm border-0">
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

    <!-- Main Section: 2 Columns (Khusus Admin / Operator) -->
    <div class="row g-4">
        <!-- Recent Dosen Table -->
        <div class="col-lg-8">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history text-primary fs-5"></i>
                        <span class="fw-bold text-dark">Data Dosen Terbaru</span>
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
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex align-items-center gap-2 border-bottom">
                    <i class="bi bi-mortarboard text-primary fs-5"></i>
                    <span class="fw-bold text-dark">Sebaran Jenjang Pendidikan</span>
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
            <div class="card bg-light border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="fw-bold text-dark small mb-2"><i class="bi bi-info-circle-fill text-primary me-1"></i> Informasi Hak Akses RBAC</div>
                    <ul class="list-unstyled small text-muted mb-0" style="line-height: 1.6;">
                        <li>• <strong>Administrator:</strong> Akses penuh kelola data dosen, master referensi, akun pengguna &amp; API.</li>
                        <li>• <strong>Operator Prodi:</strong> Kelola data dosen di program studi terkait.</li>
                        <li>• <strong>Pimpinan:</strong> Melihat statistik eksekutif dan mengunduh laporan.</li>
                        <li>• <strong>Dosen:</strong> Mengakses data profil kepegawaian pribadi &amp; jadwal mengajar.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
