<?php
// dosen/hapus.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../koneksi.php';

require_role('ADMIN', 'OPERATOR_PRODI');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit('405 Method Not Allowed');
}

$token = $_POST['csrf_token'] ?? null;
if (!csrf_validate(is_string($token) ? $token : null)) {
    $_SESSION['flash_error'] = 'Token keamanan tidak valid atau telah kedaluwarsa.';
    redirect(app_url('dosen/index.php'));
}

$idDosen = (int)($_POST['id_dosen'] ?? 0);
if ($idDosen <= 0) {
    $_SESSION['flash_error'] = 'ID Dosen tidak valid.';
    redirect(app_url('dosen/index.php'));
}

// Ambil nama dosen untuk pesan feedback
$chk = $conn->prepare("SELECT nama FROM dosen WHERE id_dosen = ? LIMIT 1");
$chk->bind_param('i', $idDosen);
$chk->execute();
$nama = $chk->get_result()->fetch_assoc()['nama'] ?? 'Dosen';
$chk->close();

// Hapus juga akun login dosen yang terkait
$conn->query("DELETE FROM sd_pengguna WHERE id_dosen = " . (int)$idDosen);

$del = $conn->prepare("DELETE FROM dosen WHERE id_dosen = ?");
$del->bind_param('i', $idDosen);

if ($del->execute()) {
    $_SESSION['flash_success'] = "Data dosen '$nama' beserta akun loginnya berhasil dihapus dari sistem.";
} else {
    $_SESSION['flash_error'] = 'Gagal menghapus data dosen: ' . $del->error;
}
$del->close();

redirect(app_url('dosen/index.php'));
