<?php
// api/prodi.php
// RESTful Web Service API untuk Data Program Studi & Fakultas SIM Dosen UMB
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/../koneksi.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'GET') {
    send_json(false, 405, "Metode HTTP '$method' tidak diizinkan. Gunakan GET.");
}

$fakultas = trim((string)($_GET['fakultas'] ?? ''));

$where = [];
$params = [];
$types = '';

if ($fakultas !== '') {
    $where[] = "p.fakultas LIKE ?";
    $params[] = '%' . $fakultas . '%';
    $types .= 's';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT p.id_penempatan, p.universitas, p.fakultas, p.program_studi, p.aktif,
               COUNT(d.id_dosen) AS total_dosen
        FROM penempatan p
        LEFT JOIN dosen d ON p.id_penempatan = d.id_penempatan
        $whereClause
        GROUP BY p.id_penempatan, p.universitas, p.fakultas, p.program_studi, p.aktif
        ORDER BY p.fakultas ASC, p.program_studi ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

$prodiList = [];
while ($row = $res->fetch_assoc()) {
    $row['aktif'] = (bool)$row['aktif'];
    $row['total_dosen'] = (int)$row['total_dosen'];
    $prodiList[] = $row;
}
$stmt->close();

send_json(true, 200, 'Data program studi berhasil dimuat.', $prodiList, [
    'total_prodi' => count($prodiList),
]);
