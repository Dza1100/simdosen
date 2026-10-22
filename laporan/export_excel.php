<?php
// laporan/export_excel.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../koneksi.php';

require_role('ADMIN', 'OPERATOR_PRODI', 'FAKULTAS', 'PIMPINAN');

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

$filename = 'Daftar_Dosen_Aktif_' . date('Ymd_His') . '.csv';

if (!headers_sent()) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
}

$output = fopen('php://output', 'w');

// UTF-8 BOM agar Microsoft Excel membuka file tanpa kendala karakter
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Header Metadata persis seperti di gambar
fputcsv($output, ['Daftar Dosen Aktif Universitas']);
fputcsv($output, []);
fputcsv($output, ['Universitas : ' . $namaUniv]);
fputcsv($output, ['Fakultas : ' . $namaFakultas]);
fputcsv($output, ['Program Studi : ' . $namaProdi]);
fputcsv($output, []);

// 14 Kolom Persis Sesuai Gambar
fputcsv($output, [
    'No',
    'Nomor Registrasi',
    'NUPTK',
    'Nama',
    'NIP',
    'Jabatan Fungsional',
    'Kepangkatan',
    'Pendidikan Terakhir',
    'Rumpun Ilmu',
    'Status Serdos',
    'Status Pegawai',
    'Ikatan Kerja',
    'Status Aktivitas',
    'Penempatan'
]);

if ($resDosen && $resDosen->num_rows > 0) {
    $no = 1;
    while ($r = $resDosen->fetch_assoc()) {
        $pangkatStr = $r['nama_pangkat'] ?: '-';
        if (!empty($r['golongan'])) {
            $pangkatStr .= ' (' . $r['golongan'] . '/' . $r['ruang'] . ')';
        }
        $penempatanStr = $r['program_studi'] ?: '-';
        if (!empty($r['fakultas'])) {
            $penempatanStr = $r['fakultas'] . ' / ' . $penempatanStr;
        }

        fputcsv($output, [
            $no++,
            $r['nomor_registrasi'] ?: '-',
            $r['nuptk'] ?: '-',
            $r['nama'],
            $r['nip'] ?: '-',
            $r['jabatan_fungsional'] ?: 'Tenaga Pengajar',
            $pangkatStr,
            $r['pendidikan_terakhir'] ?: '-',
            $r['rumpun_ilmu'] ?: '-',
            $r['status_serdos'],
            $r['status_pegawai'] ?: '-',
            $r['ikatan_kerja'] ?: '-',
            $r['status_aktivitas'],
            $penempatanStr
        ]);
    }
}

fclose($output);
exit;
