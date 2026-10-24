<?php
// dosen/tambah.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../koneksi.php';

require_role('ADMIN', 'OPERATOR_PRODI');

$page_title = 'Tambah Data Dosen';
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

        // Cek duplikasi NIDN jika diisi
        if ($nidn !== '') {
            $chk = $conn->prepare("SELECT id_dosen FROM dosen WHERE nomor_registrasi = ? LIMIT 1");
            $chk->bind_param('s', $nidn);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $errors[] = 'Nomor registrasi / NIDN sudah terdaftar pada dosen lain.';
            }
            $chk->close();
        }

        if (!$errors) {
            $sql = "INSERT INTO dosen (
                        nomor_registrasi, nuptk, nama, nip,
                        id_jabatan, id_kepangkatan, id_pendidikan, id_rumpun_ilmu,
                        id_status_kepegawaian, id_penempatan, status_serdos, status_aktivitas, keterangan
                    ) VALUES (
                        NULLIF(?, ''), NULLIF(?, ''), ?, NULLIF(?, ''),
                        NULLIF(?, 0), NULLIF(?, 0), NULLIF(?, 0), NULLIF(?, 0),
                        NULLIF(?, 0), NULLIF(?, 0), ?, ?, NULLIF(?, '')
                    )";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                'ssssiiiiiisss',
                $nidn, $nuptk, $nama, $nip,
                $idJabatan, $idKepangkatan, $idPendidikan, $idRumpun,
                $idStatus, $idPenempatan, $statusSerdos, $statusAktivitas, $keterangan
            );

            if ($stmt->execute()) {
                $newDosenId = $conn->insert_id;

                // Otomatis buatkan akun pengguna untuk dosen baru ini
                $akunUsername = '';
                if ($nidn !== '') {
                    $akunUsername = $nidn;
                } elseif ($nip !== '') {
                    $akunUsername = $nip;
                } else {
                    $clean = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(',', $nama)[0]));
                    $akunUsername = ($clean ?: 'dosen') . '_' . $newDosenId;
                }

                // Cek keunikan username
                $chkU = $conn->prepare("SELECT id_pengguna FROM sd_pengguna WHERE username = ? LIMIT 1");
                $chkU->bind_param('s', $akunUsername);
                $chkU->execute();
                if ($chkU->get_result()->num_rows > 0) {
                    $akunUsername .= '_' . $newDosenId;
                }
                $chkU->close();

                $defaultPass = 'dosen123#';
                $passHash = password_hash($defaultPass, PASSWORD_DEFAULT);
                $insU = $conn->prepare("INSERT INTO sd_pengguna (nama_lengkap, username, password_hash, peran, aktif, id_dosen) VALUES (?, ?, ?, 'DOSEN', 1, ?)");
                $insU->bind_param('sssi', $nama, $akunUsername, $passHash, $newDosenId);
                $insU->execute();
                $insU->close();

                $_SESSION['flash_success'] = "Data dosen '$nama' berhasil ditambahkan! Akun login telah dibuat (Username: $akunUsername | Password: $defaultPass).";
                redirect(app_url('dosen/index.php'));
            } else {
                $errors[] = 'Gagal menyimpan data dosen: ' . $stmt->error;
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
            <h4 class="fw-bold text-dark mb-1">Tambah Data Dosen Baru</h4>
            <div class="text-muted small">Lengkapi formulir di bawah ini untuk mencatatkan data pokok dosen baru.</div>
        </div>
        <a href="<?= e(app_url('dosen/index.php')) ?>" class="btn btn-outline-secondary btn-sm rounded-3">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar
        </a>
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
                    <input type="text" name="nama" class="form-control" placeholder="Contoh: Dr. Ir. Ahmad Dahlan, M.T." value="<?= e($_POST['nama'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">NIDN / Nomor Registrasi</label>
                    <input type="text" name="nomor_registrasi" class="form-control" placeholder="Nomor Induk Dosen Nasional (10 digit)" value="<?= e($_POST['nomor_registrasi'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">NIP (Nomor Induk Pegawai)</label>
                    <input type="text" name="nip" class="form-control" placeholder="Nomor Induk Pegawai" value="<?= e($_POST['nip'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">NUPTK (Jika Ada)</label>
                    <input type="text" name="nuptk" class="form-control" placeholder="Nomor Unik Pendidik dan Tenaga Kependidikan" value="<?= e($_POST['nuptk'] ?? '') ?>">
                </div>
            </div>

            <hr class="text-muted">

            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-mortarboard me-2"></i>2. Jenjang Akademik & Homebase Penempatan</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Program Studi (Homebase) <span class="text-danger">*</span></label>
                    <select name="id_penempatan" class="form-select" required>
                        <option value="">-- Pilih Program Studi --</option>
                        <?php if ($resPenempatan): while ($p = $resPenempatan->fetch_assoc()): ?>
                            <option value="<?= $p['id_penempatan'] ?>" <?= (int)($_POST['id_penempatan'] ?? 0) === (int)$p['id_penempatan'] ? 'selected' : '' ?>>
                                <?= e($p['fakultas']) ?> - <?= e($p['program_studi']) ?>
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Jabatan Fungsional</label>
                    <select name="id_jabatan" class="form-select">
                        <option value="">-- Belum Memiliki Jabatan --</option>
                        <?php if ($resJabatan): while ($j = $resJabatan->fetch_assoc()): ?>
                            <option value="<?= $j['id_jabatan'] ?>" <?= (int)($_POST['id_jabatan'] ?? 0) === (int)$j['id_jabatan'] ? 'selected' : '' ?>>
                                <?= e($j['nama_jabatan']) ?>
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Kepangkatan / Golongan</label>
                    <select name="id_kepangkatan" class="form-select">
                        <option value="">-- Pilih Pangkat --</option>
                        <?php if ($resPangkat): while ($k = $resPangkat->fetch_assoc()): ?>
                            <option value="<?= $k['id_kepangkatan'] ?>" <?= (int)($_POST['id_kepangkatan'] ?? 0) === (int)$k['id_kepangkatan'] ? 'selected' : '' ?>>
                                <?= e($k['nama_pangkat']) ?> (<?= e($k['golongan']) ?>/<?= e($k['ruang']) ?>)
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Pendidikan Terakhir</label>
                    <select name="id_pendidikan" class="form-select">
                        <option value="">-- Pilih Pendidikan --</option>
                        <?php if ($resPendidikan): while ($pd = $resPendidikan->fetch_assoc()): ?>
                            <option value="<?= $pd['id_pendidikan'] ?>" <?= (int)($_POST['id_pendidikan'] ?? 0) === (int)$pd['id_pendidikan'] ? 'selected' : '' ?>>
                                <?= e($pd['jenjang']) ?>
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Rumpun Ilmu</label>
                    <select name="id_rumpun_ilmu" class="form-select">
                        <option value="">-- Pilih Rumpun Ilmu --</option>
                        <?php if ($resRumpun): while ($r = $resRumpun->fetch_assoc()): ?>
                            <option value="<?= $r['id_rumpun_ilmu'] ?>" <?= (int)($_POST['id_rumpun_ilmu'] ?? 0) === (int)$r['id_rumpun_ilmu'] ? 'selected' : '' ?>>
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
                        <?php if ($resStatus): while ($s = $resStatus->fetch_assoc()): ?>
                            <option value="<?= $s['id_status_kepegawaian'] ?>" <?= (int)($_POST['id_status_kepegawaian'] ?? 0) === (int)$s['id_status_kepegawaian'] ? 'selected' : '' ?>>
                                <?= e($s['status_pegawai']) ?> (<?= e($s['ikatan_kerja']) ?>)
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Sertifikasi Dosen (Serdos)</label>
                    <select name="status_serdos" class="form-select">
                        <option value="Belum" <?= ($_POST['status_serdos'] ?? '') === 'Belum' ? 'selected' : '' ?>>Belum</option>
                        <option value="Sudah" <?= ($_POST['status_serdos'] ?? '') === 'Sudah' ? 'selected' : '' ?>>Sudah Tersertifikasi</option>
                        <option value="Tidak Berlaku" <?= ($_POST['status_serdos'] ?? '') === 'Tidak Berlaku' ? 'selected' : '' ?>>Tidak Berlaku</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Status Aktivitas Dosen</label>
                    <select name="status_aktivitas" class="form-select">
                        <option value="Aktif" <?= ($_POST['status_aktivitas'] ?? 'Aktif') === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="Cuti" <?= ($_POST['status_aktivitas'] ?? '') === 'Cuti' ? 'selected' : '' ?>>Cuti</option>
                        <option value="Pensiun" <?= ($_POST['status_aktivitas'] ?? '') === 'Pensiun' ? 'selected' : '' ?>>Pensiun</option>
                        <option value="Tidak Aktif" <?= ($_POST['status_aktivitas'] ?? '') === 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Keterangan / Catatan Tambahan</label>
                    <textarea name="keterangan" class="form-control" rows="3" placeholder="Informasi tambahan terkait dosen..."><?= e($_POST['keterangan'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= e(app_url('dosen/index.php')) ?>" class="btn btn-light border px-4">Batal</a>
                <button type="submit" class="btn btn-primary px-4 fw-bold">
                    <i class="bi bi-save me-1"></i>Simpan Data Dosen
                </button>
            </div>
        </div>
    </form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
