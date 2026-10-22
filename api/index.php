<?php
// api/index.php
// Halaman Dokumentasi & Konsol Pengujian Interaktif Web Service API SIM Dosen UMB
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dokumentasi Web Service API | SIM Dosen UMB</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; }
        .hero-api { background: linear-gradient(135deg, #0b2d59 0%, #1458b8 100%); color: #fff; padding: 48px 0; }
        .code-box { font-family: 'Fira Code', monospace; font-size: 0.85rem; background: #0f172a; color: #38bdf8; padding: 14px; border-radius: 10px; overflow-x: auto; }
        .badge-method { font-family: 'Fira Code', monospace; font-size: 0.75rem; padding: 4px 8px; border-radius: 6px; }
        .badge-get { background: #0284c7; color: #fff; }
        .badge-post { background: #16a34a; color: #fff; }
        .endpoint-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
        .endpoint-header { padding: 18px 24px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        .endpoint-body { padding: 24px; }
    </style>
</head>
<body>

    <!-- Header Hero -->
    <header class="hero-api mb-5 shadow-sm">
        <div class="container">
            <div class="d-flex align-items-center gap-3 mb-2">
                <span class="badge bg-white text-primary px-3 py-2 rounded-pill fw-bold">RESTful Web Service</span>
                <span class="text-white-50 small">Format Data: JSON</span>
            </div>
            <h1 class="fw-bold mb-2">Dokumentasi Web Service API</h1>
            <p class="text-white-50 mb-4" style="max-width: 650px;">
                Layanan Application Programming Interface (API) SIM Dosen Universitas Muhammadiyah Bengkulu untuk pertukaran data dosen, program studi, dan statistik akademik dengan aplikasi eksternal (seperti SIM Mahasiswa).
            </p>
            <div class="d-flex flex-wrap gap-2">
                <a href="../dashboard.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i>Ke Dashboard SIM Dosen
                </a>
                <a href="../index.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="bi bi-house me-1"></i>Landing Page
                </a>
            </div>
        </div>
    </header>

    <div class="container mb-5">
        <div class="alert alert-info border-0 shadow-sm mb-4">
            <i class="bi bi-info-circle-fill me-2"></i><strong>Base URL API:</strong> <code>http://localhost/simdosen/api/</code>
            <span class="ms-2 text-muted small">(Semua endpoint mendukung CORS dan menghasilkan format standar JSON).</span>
        </div>

        <!-- Endpoint 1: GET /api/dosen.php (Daftar Dosen) -->
        <div class="endpoint-card">
            <div class="endpoint-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge-method badge-get">GET</span>
                    <strong class="fs-6">/api/dosen.php</strong>
                    <span class="text-muted small ms-2">- Ambil Daftar Data Dosen</span>
                </div>
                <a href="dosen.php" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Uji Endpoint
                </a>
            </div>
            <div class="endpoint-body">
                <p class="text-muted small mb-3">Mengambil daftar dosen terdata dengan dukungan parameter filter, pencarian, dan paginasi.</p>
                
                <h6 class="fw-bold small text-dark mb-2">Parameter Query (Opsional):</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered small mb-0">
                        <thead class="table-light">
                            <tr><th>Parameter</th><th>Tipe</th><th>Contoh</th><th>Keterangan</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><code>q</code></td><td>string</td><td><code>Ahmad</code></td><td>Kata kunci pencarian Nama, NIDN, atau NIP</td></tr>
                            <tr><td><code>prodi</code></td><td>integer</td><td><code>1</code></td><td>ID Penempatan / Program Studi</td></tr>
                            <tr><td><code>status</code></td><td>string</td><td><code>Aktif</code></td><td>Status aktivitas (Aktif, Cuti, Pensiun)</td></tr>
                            <tr><td><code>serdos</code></td><td>string</td><td><code>Sudah</code></td><td>Status sertifikasi (Sudah / Belum)</td></tr>
                            <tr><td><code>page</code></td><td>integer</td><td><code>1</code></td><td>Nomor halaman</td></tr>
                            <tr><td><code>limit</code></td><td>integer</td><td><code>20</code></td><td>Jumlah data per halaman (Maksimal 100)</td></tr>
                        </tbody>
                    </table>
                </div>

                <h6 class="fw-bold small text-dark mb-2">Contoh Response JSON:</h6>
                <div class="code-box">
{
  "success": true,
  "code": 200,
  "message": "Daftar dosen berhasil dimuat.",
  "data": [
    {
      "id_dosen": 6,
      "nidn": "0012058001",
      "nip": "198005122005011002",
      "nama": "Dr. Ir. Ahmad Dahlan, M.T.",
      "status_serdos": "Sudah",
      "status_aktivitas": "Aktif",
      "nama_jabatan": "Lektor",
      "program_studi": "Teknik Informatika",
      "fakultas": "Teknik",
      "jenjang": "S3"
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total_data": 5,
    "total_pages": 1
  }
}
                </div>
            </div>
        </div>

        <!-- Endpoint 2: GET /api/dosen.php?nidn={nidn} (Detail Dosen) -->
        <div class="endpoint-card">
            <div class="endpoint-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge-method badge-get">GET</span>
                    <strong class="fs-6">/api/dosen.php?nidn={nidn}</strong>
                    <span class="text-muted small ms-2">- Ambil Detail Profil Dosen</span>
                </div>
                <a href="dosen.php?nidn=0012058001" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Uji Endpoint
                </a>
            </div>
            <div class="endpoint-body">
                <p class="text-muted small mb-3">Mengambil profil lengkap satu orang dosen berdasarkan nomor registrasi NIDN atau ID Dosen (<code>?id=6</code>).</p>
                <div class="code-box">
{
  "success": true,
  "code": 200,
  "message": "Data detail dosen berhasil dimuat.",
  "data": {
    "id_dosen": 6,
    "nidn": "0012058001",
    "nip": "198005122005011002",
    "nama": "Dr. Ir. Ahmad Dahlan, M.T.",
    "status_serdos": "Sudah",
    "status_aktivitas": "Aktif",
    "nama_jabatan": "Lektor",
    "nama_pangkat": "Penata",
    "golongan": "III",
    "ruang": "c",
    "pendidikan_terakhir": "S3",
    "rumpun_ilmu": "Ilmu Komputer dan Informatika",
    "status_pegawai": "Dosen Tetap",
    "ikatan_kerja": "Penuh Waktu",
    "program_studi": "Teknik Informatika",
    "fakultas": "Teknik"
  }
}
                </div>
            </div>
        </div>

        <!-- Endpoint 3: POST /api/dosen.php (Tambah Dosen) -->
        <div class="endpoint-card">
            <div class="endpoint-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge-method badge-post">POST</span>
                    <strong class="fs-6">/api/dosen.php</strong>
                    <span class="text-muted small ms-2">- Tambah Dosen Baru (Web Service)</span>
                </div>
                <span class="badge bg-light text-dark border">Content-Type: application/json</span>
            </div>
            <div class="endpoint-body">
                <p class="text-muted small mb-3">Endpoint untuk mengizinkan aplikasi eksternal menyuntikkan data dosen baru secara otomatis.</p>
                <h6 class="fw-bold small text-dark mb-2">Contoh Payload JSON (Body Request):</h6>
                <div class="code-box">
{
  "nama": "Rina Marlina, M.Kom.",
  "nidn": "0018049201",
  "nip": "199204182020122001",
  "id_penempatan": 1,
  "id_jabatan": 2,
  "id_pendidikan": 3,
  "status_serdos": "Belum",
  "status_aktivitas": "Aktif",
  "keterangan": "Didaftarkan melalui Web Service API"
}
                </div>
            </div>
        </div>

        <!-- Endpoint 4: GET /api/prodi.php (Data Prodi & Fakultas) -->
        <div class="endpoint-card">
            <div class="endpoint-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge-method badge-get">GET</span>
                    <strong class="fs-6">/api/prodi.php</strong>
                    <span class="text-muted small ms-2">- Data Program Studi & Fakultas</span>
                </div>
                <a href="prodi.php" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Uji Endpoint
                </a>
            </div>
            <div class="endpoint-body">
                <p class="text-muted small mb-3">Mengambil data program studi aktif beserta jumlah dosen pengajar di setiap prodi.</p>
                <div class="code-box">
{
  "success": true,
  "code": 200,
  "message": "Data program studi berhasil dimuat.",
  "data": [
    {
      "id_penempatan": 1,
      "universitas": "Universitas Muhammadiyah Bengkulu",
      "fakultas": "Teknik",
      "program_studi": "Teknik Informatika",
      "aktif": true,
      "total_dosen": 3
    }
  ],
  "meta": {
    "total_prodi": 5
  }
}
                </div>
            </div>
        </div>

        <!-- Endpoint 5: GET /api/statistik.php (Statistik Ringkas) -->
        <div class="endpoint-card">
            <div class="endpoint-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge-method badge-get">GET</span>
                    <strong class="fs-6">/api/statistik.php</strong>
                    <span class="text-muted small ms-2">- Statistik & Rekapitulasi Dosen</span>
                </div>
                <a href="statistik.php" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Uji Endpoint
                </a>
            </div>
            <div class="endpoint-body">
                <p class="text-muted small mb-3">Menyediakan data agregat statistik dosen (total dosen, sertifikasi, sebaran pendidikan, sebaran fungsional) untuk diintegrasikan ke widget dashboard portal lain.</p>
                <div class="code-box">
{
  "success": true,
  "code": 200,
  "message": "Statistik data dosen berhasil dimuat.",
  "data": {
    "ringkasan": {
      "total_dosen": 5,
      "dosen_aktif": 4,
      "dosen_cuti": 1,
      "dosen_pensiun": 0,
      "dosen_tersertifikasi": 3,
      "persentase_serdos": 60.0
    },
    "sebaran_jabatan_fungsional": {
      "Tenaga Pengajar": 1,
      "Asisten Ahli": 2,
      "Lektor": 1,
      "Profesor": 1
    },
    "sebaran_pendidikan": {
      "S2": 3,
      "S3": 2
    },
    "institusi": "Universitas Muhammadiyah Bengkulu"
  }
}
                </div>
            </div>
        </div>

    </div>

    <footer class="py-4 text-center text-muted small bg-white border-top">
        &copy; <?= date('Y') ?> Universitas Muhammadiyah Bengkulu &middot; SIM Dosen Web Service
    </footer>

</body>
</html>
