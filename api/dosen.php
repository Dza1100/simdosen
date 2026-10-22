<?php
// api/dosen.php
// RESTful Web Service API untuk Data Dosen SIM Dosen UMB
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/../koneksi.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    // 1. Ambil detail 1 dosen berdasarkan ID atau NIDN
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $nidn = isset($_GET['nidn']) ? trim((string)$_GET['nidn']) : '';

    if ($id > 0 || $nidn !== '') {
        $whereDetail = $id > 0 ? "d.id_dosen = ?" : "d.nomor_registrasi = ?";
        $paramDetail = $id > 0 ? $id : $nidn;
        $typeDetail = $id > 0 ? "i" : "s";

        $sql = "SELECT d.id_dosen, d.nomor_registrasi AS nidn, d.nip, d.nuptk, d.nama,
                       d.status_serdos, d.status_aktivitas, d.keterangan,
                       j.nama_jabatan,
                       k.nama_pangkat, k.golongan, k.ruang,
                       pd.jenjang AS pendidikan_terakhir,
                       r.nama_rumpun AS rumpun_ilmu,
                       s.status_pegawai, s.ikatan_kerja,
                       p.universitas, p.fakultas, p.program_studi,
                       d.created_at, d.updated_at
                FROM dosen d
                LEFT JOIN jabatan_fungsional j ON d.id_jabatan = j.id_jabatan
                LEFT JOIN kepangkatan k ON d.id_kepangkatan = k.id_kepangkatan
                LEFT JOIN pendidikan pd ON d.id_pendidikan = pd.id_pendidikan
                LEFT JOIN rumpun_ilmu r ON d.id_rumpun_ilmu = r.id_rumpun_ilmu
                LEFT JOIN status_kepegawaian s ON d.id_status_kepegawaian = s.id_status_kepegawaian
                LEFT JOIN penempatan p ON d.id_penempatan = p.id_penempatan
                WHERE $whereDetail
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($typeDetail, $paramDetail);
        $stmt->execute();
        $dosen = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$dosen) {
            send_json(false, 404, 'Data dosen tidak ditemukan.');
        }

        send_json(true, 200, 'Data detail dosen berhasil dimuat.', $dosen);
    }

    // 2. Ambil daftar dosen (dengan pencarian, filter, dan pagination)
    $q = trim((string)($_GET['q'] ?? ''));
    $filterProdi = (int)($_GET['prodi'] ?? 0);
    $filterStatus = trim((string)($_GET['status'] ?? ''));
    $filterSerdos = trim((string)($_GET['serdos'] ?? ''));

    $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
    $page = max(1, (int)($_GET['page'] ?? 1));
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];
    $types = '';

    if ($q !== '') {
        $where[] = "(d.nama LIKE ? OR d.nomor_registrasi LIKE ? OR d.nip LIKE ?)";
        $likeQ = '%' . $q . '%';
        $params[] = $likeQ; $params[] = $likeQ; $params[] = $likeQ;
        $types .= 'sss';
    }
    if ($filterProdi > 0) {
        $where[] = "d.id_penempatan = ?";
        $params[] = $filterProdi;
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

    // Hitung total record
    $sqlCount = "SELECT COUNT(*) AS total FROM dosen d $whereClause";
    $stmtC = $conn->prepare($sqlCount);
    if (!empty($params)) {
        $stmtC->bind_param($types, ...$params);
    }
    $stmtC->execute();
    $totalData = (int)$stmtC->get_result()->fetch_assoc()['total'];
    $stmtC->close();

    $totalPages = max(1, (int)ceil($totalData / $limit));

    // Ambil data
    $sqlList = "SELECT d.id_dosen, d.nomor_registrasi AS nidn, d.nip, d.nama,
                       d.status_serdos, d.status_aktivitas,
                       j.nama_jabatan, p.program_studi, p.fakultas, pd.jenjang
                FROM dosen d
                LEFT JOIN jabatan_fungsional j ON d.id_jabatan = j.id_jabatan
                LEFT JOIN penempatan p ON d.id_penempatan = p.id_penempatan
                LEFT JOIN pendidikan pd ON d.id_pendidikan = pd.id_pendidikan
                $whereClause
                ORDER BY d.nama ASC
                LIMIT ? OFFSET ?";

    $dataParams = $params;
    $dataParams[] = $limit;
    $dataParams[] = $offset;
    $dataTypes = $types . 'ii';

    $stmtL = $conn->prepare($sqlList);
    $stmtL->bind_param($dataTypes, ...$dataParams);
    $stmtL->execute();
    $res = $stmtL->get_result();
    
    $dosenList = [];
    while ($row = $res->fetch_assoc()) {
        $dosenList[] = $row;
    }
    $stmtL->close();

    $meta = [
        'page' => $page,
        'per_page' => $limit,
        'total_data' => $totalData,
        'total_pages' => $totalPages,
    ];

    send_json(true, 200, 'Daftar dosen berhasil dimuat.', $dosenList, $meta);

} elseif ($method === 'POST') {
    // 3. Tambah dosen via API
    $input = get_json_input();

    $nama = trim((string)($input['nama'] ?? ''));
    $nidn = trim((string)($input['nidn'] ?? ($input['nomor_registrasi'] ?? '')));
    $nip = trim((string)($input['nip'] ?? ''));
    $nuptk = trim((string)($input['nuptk'] ?? ''));

    $idJabatan = (int)($input['id_jabatan'] ?? 0);
    $idKepangkatan = (int)($input['id_kepangkatan'] ?? 0);
    $idPendidikan = (int)($input['id_pendidikan'] ?? 0);
    $idRumpun = (int)($input['id_rumpun_ilmu'] ?? 0);
    $idStatus = (int)($input['id_status_kepegawaian'] ?? 0);
    $idPenempatan = (int)($input['id_penempatan'] ?? 0);

    $statusSerdos = (string)($input['status_serdos'] ?? 'Belum');
    $statusAktivitas = (string)($input['status_aktivitas'] ?? 'Aktif');
    $keterangan = trim((string)($input['keterangan'] ?? ''));

    if ($nama === '') {
        send_json(false, 400, 'Parameter wajib: nama dosen tidak boleh kosong.');
    }

    if ($nidn !== '') {
        $chk = $conn->prepare("SELECT id_dosen FROM dosen WHERE nomor_registrasi = ? LIMIT 1");
        $chk->bind_param('s', $nidn);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $chk->close();
            send_json(false, 409, 'Konflik: NIDN / nomor_registrasi sudah terdaftar pada dosen lain.');
        }
        $chk->close();
    }

    $sqlInsert = "INSERT INTO dosen (
                    nomor_registrasi, nuptk, nama, nip,
                    id_jabatan, id_kepangkatan, id_pendidikan, id_rumpun_ilmu,
                    id_status_kepegawaian, id_penempatan, status_serdos, status_aktivitas, keterangan
                ) VALUES (
                    NULLIF(?, ''), NULLIF(?, ''), ?, NULLIF(?, ''),
                    NULLIF(?, 0), NULLIF(?, 0), NULLIF(?, 0), NULLIF(?, 0),
                    NULLIF(?, 0), NULLIF(?, 0), ?, ?, NULLIF(?, '')
                )";

    $stmt = $conn->prepare($sqlInsert);
    $stmt->bind_param(
        'ssssiiiiiisss',
        $nidn, $nuptk, $nama, $nip,
        $idJabatan, $idKepangkatan, $idPendidikan, $idRumpun,
        $idStatus, $idPenempatan, $statusSerdos, $statusAktivitas, $keterangan
    );

    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $stmt->close();
        send_json(true, 201, "Data dosen '$nama' berhasil ditambahkan melalui API.", [
            'id_dosen' => $newId,
            'nama' => $nama,
            'nidn' => $nidn,
            'nip' => $nip,
            'status_aktivitas' => $statusAktivitas,
        ]);
    } else {
        $err = $stmt->error;
        $stmt->close();
        send_json(false, 500, 'Gagal menyimpan data ke database: ' . $err);
    }

} else {
    send_json(false, 405, "Metode HTTP '$method' tidak diizinkan. Gunakan GET atau POST.");
}
