<?php
// laporan/cetak.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../koneksi.php';

require_role('ADMIN', 'OPERATOR_PRODI', 'FAKULTAS', 'PIMPINAN');

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
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Daftar Dosen Aktif Universitas - <?= e($namaUniv) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #000;
            background: #fff;
            padding: 24px;
        }
        .report-header {
            margin-bottom: 20px;
        }
        .report-title {
            font-size: 13pt;
            font-weight: bold;
            margin-bottom: 14px;
            color: #111;
        }
        .meta-table {
            font-size: 10pt;
            margin-bottom: 14px;
            font-weight: 500;
        }
        .meta-table td {
            padding: 2px 6px;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
        }
        .table-data th, .table-data td {
            border: 1px solid #444;
            padding: 5px 6px;
            vertical-align: middle;
        }
        .table-data th {
            background-color: #f2f4f7;
            text-align: center;
            font-weight: bold;
            color: #111;
            white-space: nowrap;
        }
        .ttd-box {
            margin-top: 36px;
            float: right;
            width: 280px;
            text-align: center;
            font-size: 9.5pt;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            @page { size: landscape; margin: 1cm; }
        }
    </style>
</head>
<body>

    <!-- Bar Tombol Aksi (Tidak tercetak) -->
    <div class="no-print d-flex gap-2 mb-4 p-3 bg-light border rounded align-items-center justify-content-between">
        <div>
            <strong>Contoh Laporan Daftar Dosen (Sesuai Format Bahan Normalisasi)</strong>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary btn-sm px-3">
                <i class="bi bi-printer me-1"></i>Cetak Laporan
            </button>
            <a href="export_excel.php?<?= http_build_query($_GET) ?>" class="btn btn-success btn-sm px-3">
                <i class="bi bi-file-earmark-excel me-1"></i>Ekspor Excel
            </a>
            <button onclick="window.close()" class="btn btn-outline-secondary btn-sm px-3">
                Tutup
            </button>
        </div>
    </div>

    <!-- Header Dokumen Sesuai Format Gambar -->
    <div class="report-header">
        <div class="report-title">Daftar Dosen Aktif Universitas</div>
        <table class="meta-table">
            <tr>
                <td style="width: 120px;">Universitas</td>
                <td style="width: 15px;">:</td>
                <td><strong><?= e($namaUniv) ?></strong></td>
            </tr>
            <tr>
                <td>Fakultas</td>
                <td>:</td>
                <td><strong><?= e($namaFakultas) ?></strong></td>
            </tr>
            <tr>
                <td>Program Studi</td>
                <td>:</td>
                <td><strong><?= e($namaProdi) ?></strong></td>
            </tr>
        </table>
    </div>

    <!-- Tabel 14 Kolom Persis Sesuai Gambar -->
    <table class="table-data">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
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
                    // Format kepangkatan: e.g. Penata (III/c) atau nama pangkat
                    $pangkatStr = $r['nama_pangkat'] ?: '-';
                    if (!empty($r['golongan'])) {
                        $pangkatStr .= ' (' . $r['golongan'] . '/' . $r['ruang'] . ')';
                    }
                    // Format penempatan
                    $penempatanStr = $r['program_studi'] ?: '-';
                    if (!empty($r['fakultas'])) {
                        $penempatanStr = $r['fakultas'] . ' / ' . $penempatanStr;
                    }
                ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= e($r['nomor_registrasi'] ?: '-') ?></td>
                        <td><?= e($r['nuptk'] ?: '-') ?></td>
                        <td style="font-weight: 600;"><?= e($r['nama']) ?></td>
                        <td><?= e($r['nip'] ?: '-') ?></td>
                        <td><?= e($r['jabatan_fungsional'] ?: 'Tenaga Pengajar') ?></td>
                        <td><?= e($pangkatStr) ?></td>
                        <td class="text-center"><?= e($r['pendidikan_terakhir'] ?: '-') ?></td>
                        <td><?= e($r['rumpun_ilmu'] ?: '-') ?></td>
                        <td class="text-center"><?= e($r['status_serdos']) ?></td>
                        <td><?= e($r['status_pegawai'] ?: '-') ?></td>
                        <td><?= e($r['ikatan_kerja'] ?: '-') ?></td>
                        <td class="text-center"><?= e($r['status_aktivitas']) ?></td>
                        <td><?= e($penempatanStr) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="14" class="text-center py-3">Tidak ada data dosen yang sesuai dengan kriteria filter.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Tanda Tangan -->
    <div class="clearfix">
        <div class="ttd-box">
            <div>Bengkulu, <?= date('d F Y') ?></div>
            <div class="mb-5">Mengetahui,<br><strong>Dekan Fakultas <?= e($namaFakultas) ?></strong></div>
            <br><br>
            <div class="fw-bold" style="text-decoration: underline;">Dr. Ir. Ahmad Dahlan, M.T.</div>
            <div>NIP. 198005122005011002</div>
        </div>
    </div>

</body>
</html>
