<?php
// dosen/edit.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../koneksi.php';

require_role('ADMIN', 'OPERATOR_PRODI');

$idDosen = (int)($_GET['id'] ?? 0);
if ($idDosen <= 0) {
    redirect(app_url('dosen/index.php'));
}

// Ambil data dosen yang akan diedit
$stmt = $conn->prepare("SELECT * FROM dosen WHERE id_dosen = ? LIMIT 1");
$stmt->bind_param('i', $idDosen);
$stmt->execute();
$dosen = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$dosen) {
    $_SESSION['flash_error'] = 'Data dosen tidak ditemukan.';
    redirect(app_url('dosen/index.php'));
}

$page_title = 'Edit Data Dosen: ' . $dosen['nama'];
$errors = [];

// Handle Submit Form
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!csrf_validate(is_string($token) ? $token : null)) {
        $errors[] = 'Token keamanan tidak valid atau telah kedaluwarsa.';
    } else {
        $nama = trim((string)($_POST['nama'] ?? ''));
        $nidn = trim((string)($_POST['nomor_registrasi'] ?? ''));
        $nuptk = trim((string)($_POST['nuptk'] ?? ''));
        $nip = trim((string)($_POST['nip'] ?? ''));

        $idJabatan = (int)($_POST['id_jabatan'] ?? 0);
        $idKepangkatan = (int)($_POST['id_kepangkatan'] ?? 0);
        $idPendidikan = (int)($_POST['id_pendidikan'] ?? 0);
        $idRumpun = (int)($_POST['id_rumpun_ilmu'] ?? 0);
        $idStatus = (int)($_POST['id_status_kepegawaian'] ?? 0);
        $idPenempatan = (int)($_POST['id_penempatan'] ?? 0);

        $statusSerdos = (string)($_POST['status_serdos'] ?? 'Belum');
        $statusAktivitas = (string)($_POST['status_aktivitas'] ?? 'Aktif');
        $keterangan = trim((string)($_POST['keterangan'] ?? ''));

        if ($nama === '') {
            $errors[] = 'Nama lengkap dosen wajib diisi.';
        }

        // Cek duplikasi NIDN selain data ini
        if ($nidn !== '') {
            $chk = $conn->prepare("SELECT id_dosen FROM dosen WHERE nomor_registrasi = ? AND id_dosen != ? LIMIT 1");
            $chk->bind_param('si', $nidn, $idDosen);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $errors[] = 'Nomor registrasi / NIDN sudah terdaftar pada dosen lain.';
            }
            $chk->close();
        }

        if (!$errors) {
            $sql = "UPDATE dosen SET
                        nomor_registrasi = NULLIF(?, ''),
                        nuptk = NULLIF(?, ''),
                        nama = ?,
                        nip = NULLIF(?, ''),
                        id_jabatan = NULLIF(?, 0),
                        id_kepangkatan = NULLIF(?, 0),
                        id_pendidikan = NULLIF(?, 0),
                        id_rumpun_ilmu = NULLIF(?, 0),
                        id_status_kepegawaian = NULLIF(?, 0),
                        id_penempatan = NULLIF(?, 0),
                        status_serdos = ?,
                        status_aktivitas = ?,
                        keterangan = NULLIF(?, '')
                    WHERE id_dosen = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                'ssssiiiiiisssi',
                $nidn, $nuptk, $nama, $nip,
                $idJabatan, $idKepangkatan, $idPendidikan, $idRumpun,
                $idStatus, $idPenempatan, $statusSerdos, $statusAktivitas, $keterangan,
                $idDosen
            );

            if ($stmt->execute()) {
                // Sinkronkan nama lengkap ke tabel akun pengguna jika akun terhubung
                $updU = $conn->prepare("UPDATE sd_pengguna SET nama_lengkap = ? WHERE id_dosen = ?");
                $updU->bind_param('si', $nama, $idDosen);
                $updU->execute();
                $updU->close();

                $_SESSION['flash_success'] = "Data dosen '$nama' berhasil diperbarui!";
                redirect(app_url('dosen/index.php'));
            } else {
                $errors[] = 'Gagal memperbarui data dosen: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Opsi Data Referensi
$resJabatan = $conn->query("SELECT id_jabatan, nama_jabatan FROM jabatan_fungsional WHERE aktif = 1 ORDER BY id_jabatan");
$resPangkat = $conn->query("SELECT id_kepangkatan, nama_pangkat, golongan, ruang FROM kepangkatan ORDER BY golongan, ruang");
$resPendidikan = $conn->query("SELECT id_pendidikan, jenjang FROM pendidikan ORDER BY urutan");
$resRumpun = $conn->query("SELECT id_rumpun_ilmu, nama_rumpun FROM rumpun_ilmu ORDER BY nama_rumpun");
$resStatus = $conn->query("SELECT id_status_kepegawaian, status_pegawai, ikatan_kerja FROM status_kepegawaian ORDER BY id_status_kepegawaian");
$resPenempatan = $conn->query("SELECT id_penempatan, fakultas, program_studi FROM penempatan WHERE aktif = 1 ORDER BY fakultas, program_studi");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="app-main">
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="app-content">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Edit Data Dosen</h4>
            <div class="text-muted small">Perbarui rincian informasi dan status kepegawaian dosen.</div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= e(app_url('dosen/detail.php?id=' . $idDosen)) ?>" class="btn btn-outline-primary btn-sm rounded-3">
                <i class="bi bi-eye me-1"></i>Lihat Detail
            </a>
            <a href="<?= e(app_url('dosen/index.php')) ?>" class="btn btn-outline-secondary btn-sm rounded-3">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Perhatian:</div>
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="post" class="card shadow-sm">
        <?= csrf_field() ?>
        <div class="card-body p-4">
            
            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-person-vcard me-2"></i>1. Identitas Pokok Dosen</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" value="<?= e($_POST['nama'] ?? $dosen['nama']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">NIDN / Nomor Registrasi</label>
                    <input type="text" name="nomor_registrasi" class="form-control" value="<?= e($_POST['nomor_registrasi'] ?? $dosen['nomor_registrasi']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">NIP (Nomor Induk Pegawai)</label>
                    <input type="text" name="nip" class="form-control" value="<?= e($_POST['nip'] ?? $dosen['nip']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">NUPTK (Jika Ada)</label>
                    <input type="text" name="nuptk" class="form-control" value="<?= e($_POST['nuptk'] ?? $dosen['nuptk']) ?>">
                </div>
            </div>

            <hr class="text-muted">

            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-mortarboard me-2"></i>2. Jenjang Akademik & Homebase Penempatan</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Program Studi (Homebase) <span class="text-danger">*</span></label>
                    <select name="id_penempatan" class="form-select" required>
                        <option value="">-- Pilih Program Studi --</option>
                        <?php 
                        $curPenempatan = (int)($_POST['id_penempatan'] ?? $dosen['id_penempatan']);
                        if ($resPenempatan): while ($p = $resPenempatan->fetch_assoc()): ?>
                            <option value="<?= $p['id_penempatan'] ?>" <?= $curPenempatan === (int)$p['id_penempatan'] ? 'selected' : '' ?>>
                                <?= e($p['fakultas']) ?> - <?= e($p['program_studi']) ?>
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Jabatan Fungsional</label>
                    <select name="id_jabatan" class="form-select">
                        <option value="">-- Belum Memiliki Jabatan --</option>
                        <?php 
                        $curJabatan = (int)($_POST['id_jabatan'] ?? $dosen['id_jabatan']);
                        if ($resJabatan): while ($j = $resJabatan->fetch_assoc()): ?>
                            <option value="<?= $j['id_jabatan'] ?>" <?= $curJabatan === (int)$j['id_jabatan'] ? 'selected' : '' ?>>
                                <?= e($j['nama_jabatan']) ?>
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Kepangkatan / Golongan</label>
                    <select name="id_kepangkatan" class="form-select">
                        <option value="">-- Pilih Pangkat --</option>
                        <?php 
                        $curPangkat = (int)($_POST['id_kepangkatan'] ?? $dosen['id_kepangkatan']);
                        if ($resPangkat): while ($k = $resPangkat->fetch_assoc()): ?>
                            <option value="<?= $k['id_kepangkatan'] ?>" <?= $curPangkat === (int)$k['id_kepangkatan'] ? 'selected' : '' ?>>
                                <?= e($k['nama_pangkat']) ?> (<?= e($k['golongan']) ?>/<?= e($k['ruang']) ?>)
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Pendidikan Terakhir</label>
                    <select name="id_pendidikan" class="form-select">
                        <option value="">-- Pilih Pendidikan --</option>
                        <?php 
                        $curPendidikan = (int)($_POST['id_pendidikan'] ?? $dosen['id_pendidikan']);
                        if ($resPendidikan): while ($pd = $resPendidikan->fetch_assoc()): ?>
                            <option value="<?= $pd['id_pendidikan'] ?>" <?= $curPendidikan === (int)$pd['id_pendidikan'] ? 'selected' : '' ?>>
                                <?= e($pd['jenjang']) ?>
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Rumpun Ilmu</label>
                    <select name="id_rumpun_ilmu" class="form-select">
                        <option value="">-- Pilih Rumpun Ilmu --</option>
                        <?php 
                        $curRumpun = (int)($_POST['id_rumpun_ilmu'] ?? $dosen['id_rumpun_ilmu']);
                        if ($resRumpun): while ($r = $resRumpun->fetch_assoc()): ?>
                            <option value="<?= $r['id_rumpun_ilmu'] ?>" <?= $curRumpun === (int)$r['id_rumpun_ilmu'] ? 'selected' : '' ?>>
                                <?= e($r['nama_rumpun']) ?>
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
            </div>

            <hr class="text-muted">

            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-person-badge me-2"></i>3. Status Kepegawaian & Sertifikasi</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Status Kepegawaian</label>
                    <select name="id_status_kepegawaian" class="form-select">
                        <option value="">-- Pilih Status --</option>
                        <?php 
                        $curStatus = (int)($_POST['id_status_kepegawaian'] ?? $dosen['id_status_kepegawaian']);
                        if ($resStatus): while ($s = $resStatus->fetch_assoc()): ?>
                            <option value="<?= $s['id_status_kepegawaian'] ?>" <?= $curStatus === (int)$s['id_status_kepegawaian'] ? 'selected' : '' ?>>
                                <?= e($s['status_pegawai']) ?> (<?= e($s['ikatan_kerja']) ?>)
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Sertifikasi Dosen (Serdos)</label>
                    <?php $curSerdos = (string)($_POST['status_serdos'] ?? $dosen['status_serdos']); ?>
                    <select name="status_serdos" class="form-select">
                        <option value="Belum" <?= $curSerdos === 'Belum' ? 'selected' : '' ?>>Belum</option>
                        <option value="Sudah" <?= $curSerdos === 'Sudah' ? 'selected' : '' ?>>Sudah Tersertifikasi</option>
                        <option value="Tidak Berlaku" <?= $curSerdos === 'Tidak Berlaku' ? 'selected' : '' ?>>Tidak Berlaku</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Status Aktivitas Dosen</label>
                    <?php $curAktivitas = (string)($_POST['status_aktivitas'] ?? $dosen['status_aktivitas']); ?>
                    <select name="status_aktivitas" class="form-select">
                        <option value="Aktif" <?= $curAktivitas === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="Cuti" <?= $curAktivitas === 'Cuti' ? 'selected' : '' ?>>Cuti</option>
                        <option value="Pensiun" <?= $curAktivitas === 'Pensiun' ? 'selected' : '' ?>>Pensiun</option>
                        <option value="Tidak Aktif" <?= $curAktivitas === 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Keterangan / Catatan Tambahan</label>
                    <textarea name="keterangan" class="form-control" rows="3"><?= e($_POST['keterangan'] ?? $dosen['keterangan']) ?></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= e(app_url('dosen/index.php')) ?>" class="btn btn-light border px-4">Batal</a>
                <button type="submit" class="btn btn-primary px-4 fw-bold">
                    <i class="bi bi-save me-1"></i>Simpan Perubahan
                </button>
            </div>
        </div>
    </form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
