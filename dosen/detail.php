<?php
// dosen/detail.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../koneksi.php';

require_login();

$idDosen = (int)($_GET['id'] ?? 0);
if ($idDosen <= 0) {
    redirect(app_url('dosen/index.php'));
}

$sql = "SELECT d.*,
               j.nama_jabatan,
               k.nama_pangkat, k.golongan, k.ruang,
               pd.jenjang,
               r.nama_rumpun,
               s.status_pegawai, s.ikatan_kerja,
               p.universitas, p.fakultas, p.program_studi
        FROM dosen d
        LEFT JOIN jabatan_fungsional j ON d.id_jabatan = j.id_jabatan
        LEFT JOIN kepangkatan k ON d.id_kepangkatan = k.id_kepangkatan
        LEFT JOIN pendidikan pd ON d.id_pendidikan = pd.id_pendidikan
        LEFT JOIN rumpun_ilmu r ON d.id_rumpun_ilmu = r.id_rumpun_ilmu
        LEFT JOIN status_kepegawaian s ON d.id_status_kepegawaian = s.id_status_kepegawaian
        LEFT JOIN penempatan p ON d.id_penempatan = p.id_penempatan
        WHERE d.id_dosen = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $idDosen);
$stmt->execute();
$d = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$d) {
    $_SESSION['flash_error'] = 'Data dosen tidak ditemukan.';
    redirect(app_url('dosen/index.php'));
}

$page_title = 'Profil Dosen - ' . $d['nama'];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="app-main">
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="app-content">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Profil Lengkap Dosen</h4>
            <div class="text-muted small">Rincian data pokok, riwayat fungsional, dan unit penempatan dosen.</div>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print();" class="btn btn-outline-secondary btn-sm rounded-3">
                <i class="bi bi-printer me-1"></i>Cetak Profil
            </button>
            <?php if (has_role('ADMIN', 'OPERATOR_PRODI')): ?>
                <a href="<?= e(app_url('dosen/edit.php?id=' . $d['id_dosen'])) ?>" class="btn btn-primary btn-sm rounded-3">
                    <i class="bi bi-pencil me-1"></i>Edit Data
                </a>
            <?php endif; ?>
            <a href="<?= e(app_url('dosen/index.php')) ?>" class="btn btn-light border btn-sm rounded-3">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    <!-- Header Card -->
    <div class="card border-0 mb-4 text-white overflow-hidden" style="background: linear-gradient(135deg, #0b2d59 0%, #1458b8 100%); border-radius: 16px;">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-4">
                <div class="rounded-circle bg-white text-primary d-grid place-items-center fw-bold shadow" style="width: 80px; height: 80px; font-size: 2rem; flex-shrink: 0; display: grid; place-items: center;">
                    <?= strtoupper(substr($d['nama'], 0, 1)) ?>
                </div>
                <div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <?php if ($d['status_aktivitas'] === 'Aktif'): ?>
                            <span class="badge bg-success rounded-pill px-3 py-1">Status: Aktif</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark rounded-pill px-3 py-1">Status: <?= e($d['status_aktivitas']) ?></span>
                        <?php endif; ?>

                        <?php if ($d['status_serdos'] === 'Sudah'): ?>
                            <span class="badge bg-info text-dark rounded-pill px-3 py-1"><i class="bi bi-patch-check-fill me-1"></i>Tersertifikasi (Serdos)</span>
                        <?php endif; ?>
                    </div>
                    <h3 class="fw-bold mb-1 text-white"><?= e($d['nama']) ?></h3>
                    <div class="text-white-50 small d-flex flex-wrap gap-3">
                        <span><i class="bi bi-card-text me-1"></i>NIDN: <strong><?= e($d['nomor_registrasi'] ?: '-') ?></strong></span>
                        <span><i class="bi bi-person-badge me-1"></i>NIP: <strong><?= e($d['nip'] ?: '-') ?></strong></span>
                        <span><i class="bi bi-buildings me-1"></i><?= e($d['program_studi'] ?: 'Belum ada prodi') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2 Column Details -->
    <div class="row g-4">
        <!-- Kolom Kiri: Akademik & Homebase -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-mortarboard text-primary"></i>
                    <span>Informasi Akademik & Homebase</span>
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

        <!-- Kolom Kanan: Kepegawaian & Riwayat -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-award text-primary"></i>
                    <span>Status Kepegawaian & Kepangkatan</span>
                </div>
                <div class="card-body p-4">
                    <dl class="row mb-0 gy-3">
                        <dt class="col-sm-5 text-muted small">Pangkat / Golongan</dt>
                        <dd class="col-sm-7 fw-bold text-dark mb-0">
                            <?= e($d['nama_pangkat'] ?: '-') ?>
                            <?php if ($d['golongan']): ?>
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
                            <?= $d['status_serdos'] === 'Sudah' ? '<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Sudah Tersertifikasi</span>' : '<span class="text-muted">Belum</span>' ?>
                        </dd>

                        <dt class="col-sm-5 text-muted small">Terdaftar Pada</dt>
                        <dd class="col-sm-7 text-muted small mb-0"><?= date('d F Y, H:i', strtotime($d['created_at'])) ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Catatan Tambahan -->
        <?php if (!empty($d['keterangan'])): ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="bi bi-chat-left-text text-primary"></i>
                        <span>Keterangan / Catatan</span>
                    </div>
                    <div class="card-body p-4">
                        <p class="mb-0 text-dark"><?= nl2br(e($d['keterangan'])) ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
