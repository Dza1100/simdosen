<?php
// api/statistik.php
// RESTful Web Service API untuk Statistik & Rekapitulasi Dosen SIM Dosen UMB
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/../koneksi.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'GET') {
    send_json(false, 405, "Metode HTTP '$method' tidak diizinkan. Gunakan GET.");
}

// 1. Total dosen & status
$totalDosen = 0;
$dosenAktif = 0;
$dosenCuti = 0;
$dosenPensiun = 0;
$dosenSerdos = 0;

$r1 = $conn->query("SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN status_aktivitas = 'Aktif' THEN 1 ELSE 0 END) AS aktif,
    SUM(CASE WHEN status_aktivitas = 'Cuti' THEN 1 ELSE 0 END) AS cuti,
    SUM(CASE WHEN status_aktivitas = 'Pensiun' THEN 1 ELSE 0 END) AS pensiun,
    SUM(CASE WHEN status_serdos = 'Sudah' THEN 1 ELSE 0 END) AS serdos
    FROM dosen");
if ($r1) {
    $row = $r1->fetch_assoc();
    $totalDosen = (int)$row['total'];
    $dosenAktif = (int)$row['aktif'];
    $dosenCuti = (int)$row['cuti'];
    $dosenPensiun = (int)$row['pensiun'];
    $dosenSerdos = (int)$row['serdos'];
}

// 2. Sebaran Jabatan Fungsional
$sebaranJabatan = [];
$r2 = $conn->query("SELECT j.nama_jabatan, COUNT(d.id_dosen) AS jumlah
    FROM jabatan_fungsional j
    LEFT JOIN dosen d ON j.id_jabatan = d.id_jabatan
    GROUP BY j.id_jabatan, j.nama_jabatan
    ORDER BY j.id_jabatan ASC");
if ($r2) {
    while ($j = $r2->fetch_assoc()) {
        $sebaranJabatan[$j['nama_jabatan']] = (int)$j['jumlah'];
    }
}

// 3. Sebaran Jenjang Pendidikan
$sebaranPendidikan = [];
$r3 = $conn->query("SELECT pd.jenjang, COUNT(d.id_dosen) AS jumlah
    FROM pendidikan pd
    LEFT JOIN dosen d ON pd.id_pendidikan = d.id_pendidikan
    GROUP BY pd.id_pendidikan, pd.jenjang
    ORDER BY pd.urutan ASC");
if ($r3) {
    while ($p = $r3->fetch_assoc()) {
        $sebaranPendidikan[$p['jenjang']] = (int)$p['jumlah'];
    }
}

// 4. Total Unit Penempatan
$totalProdi = 0;
$r4 = $conn->query("SELECT COUNT(*) AS total FROM penempatan WHERE aktif = 1");
if ($r4) {
    $totalProdi = (int)$r4->fetch_assoc()['total'];
}

$data = [
    'ringkasan' => [
        'total_dosen' => $totalDosen,
        'dosen_aktif' => $dosenAktif,
        'dosen_cuti' => $dosenCuti,
        'dosen_pensiun' => $dosenPensiun,
        'dosen_tersertifikasi' => $dosenSerdos,
        'persentase_serdos' => $totalDosen > 0 ? round(($dosenSerdos / $totalDosen) * 100, 2) : 0,
        'total_program_studi' => $totalProdi,
    ],
    'sebaran_jabatan_fungsional' => $sebaranJabatan,
    'sebaran_pendidikan' => $sebaranPendidikan,
    'institusi' => 'Universitas Muhammadiyah Bengkulu',
    'timestamp' => date('Y-m-d H:i:s'),
];

send_json(true, 200, 'Statistik data dosen berhasil dimuat.', $data);
