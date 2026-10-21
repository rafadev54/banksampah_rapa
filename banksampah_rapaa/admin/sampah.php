<?php
session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

include __DIR__ . "/../koneksi.php";
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

// Helper Format Rupiah
if (!function_exists('rupiah')) {
    function rupiah($angka) {
        return "Rp " . number_format($angka, 0, ',', '.');
    }
}

// Query Ambil Seluruh Data Sampah
$data = mysqli_query($conn, "SELECT * FROM sampah ORDER BY id_sampah DESC");

/* ================================
   HITUNG DATA SUMMARY / STATISTIK
================================ */
$jumlahJenis = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM sampah")
)['total'] ?? 0;

$totalStok = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COALESCE(SUM(stok_kg), 0) AS total FROM sampah")
)['total'] ?? 0;

$nilaiStok = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COALESCE(SUM(stok_kg * harga_per_kg), 0) AS total FROM sampah")
)['total'] ?? 0;

$hargaTertinggi = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COALESCE(MAX(harga_per_kg), 0) AS total FROM sampah")
)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sampah - Bank Sampah Akbar</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f7f8fc;
            font-family: "Segoe UI", Arial, sans-serif;
            color: #202733;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* NAVBAR */
        .topbar {
            height: 70px;
            background: #ffffff;
            border-bottom: 1px solid #e8ebef;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .brand {
            text-decoration: none;
            color: #17202a;
            display: flex;
            align-items: center;
            gap: 11px;
            font-weight: 700;
            font-size: 18px;
        }
        .brand-icon {
            width: 40px;
            height: 40px;
            border-radius: 11px;
            background: #17202a;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .menu-link {
            text-decoration: none;
            color: #727b87;
            font-size: 13px;
            font-weight: 500;
            padding: 9px 13px;
            border-radius: 8px;
            margin-left: 4px;
            transition: .2s;
        }
        .menu-link:hover { color: #17202a; background: #f0f2f5; }
        .menu-link.active { background: #17202a; color: white; }
        .user-area {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-left: 18px;
            border-left: 1px solid #e5e7eb;
        }
        .avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #e9edf2;
            color: #17202a;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* CONTENT */
        .container-main { max-width: 1400px; margin: 0 auto; padding: 32px; flex: 1; width: 100%; }
        .page-heading { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-heading h1 { font-size: 28px; font-weight: 700; margin: 0 0 5px; }
        .page-heading p { margin: 0; color: #89919c; font-size: 14px; }
        .btn-add { background: #17202a; border: none; color: white; padding: 11px 18px; border-radius: 9px; font-size: 13px; font-weight: 600; }
        .btn-add:hover { background: #303b48; color: white; }

        /* STATISTIC CARDS */
        .summary-card { background: white; border: 1px solid #e7e9ed; border-radius: 14px; padding: 19px; height: 100%; }
        .summary-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; font-size: 18px; }
        .summary-jenis { background: #edf0f4; color: #17202a; }
        .summary-stok { background: #e9f2ff; color: #0d6efd; }
        .summary-nilai { background: #fff3dc; color: #d58b00; }
        .summary-harga { background: #e5f6ed; color: #198754; }
        .summary-title { font-size: 12px; color: #89919c; margin-bottom: 3px; }
        .summary-number { font-size: 24px; font-weight: 700; }

        /* TABLE CONTAINER CARD */
        .users-card { margin-top: 25px; background: white; border: 1px solid #e5e8ec; border-radius: 16px; overflow: hidden; }
        .users-header { padding: 21px 23px; border-bottom: 1px solid #e9ebee; display: flex; justify-content: space-between; align-items: center; gap: 15px; }
        .users-header h5 { font-size: 17px; font-weight: 700; margin: 0; }
        .users-header small { color: #929aa4; }
        .tools { display: flex; gap: 8px; }
        .search-box { width: 230px; position: relative; }
        .search-box i { position: absolute; left: 12px; top: 10px; color: #9aa2ab; }
        .search-box input { width: 100%; height: 38px; border: 1px solid #dfe3e8; border-radius: 8px; padding-left: 35px; font-size: 12px; outline: none; }
        .search-box input:focus { border-color: #17202a; }
        .filter-select { height: 38px; border: 1px solid #dfe3e8; border-radius: 8px; padding: 0 12px; font-size: 12px; color: #555e68; outline: none; }

        /* TABLE */
        .table { margin: 0; }
        .table thead th { background: #fafbfc; border-bottom: 1px solid #e7e9ec; color: #8a929d; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; padding: 14px 20px; }
        .table tbody td { padding: 15px 20px; border-color: #eef0f2; font-size: 13px; vertical-align: middle; }
        .table tbody tr:hover { background: #fafbfd; }
        .number { color: #9ba3ad; font-size: 12px; }
        
        .sampah-item { display: flex; align-items: center; gap: 11px; }
        .sampah-icon { width: 38px; height: 38px; border-radius: 10px; background: #edf0f4; color: #17202a; display: flex; align-items: center; justify-content: center; font-size: 16px; }
        .sampah-name { font-weight: 600; color: #252c35; }
        .sampah-id { font-size: 11px; color: #9aa1a9; margin-top: 2px; }

        /* BADGES */
        .stok-badge { display: inline-flex; align-items: center; gap: 5px; padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; }
        .stok-ada { background: #e7f0ff; color: #0d6efd; }
        .stok-habis { background: #fde9ec; color: #dc3545; }

        .price { font-weight: 700; color: #198754; }
        .action-btn { width: 34px; height: 34px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; border: none; text-decoration: none; font-size: 14px; }
        .delete-btn { background: #fff0f1; color: #dc3545; }
        .delete-btn:hover { background: #dc3545; color: white; }

        /* MODAL */
        .modal-content { border: none; border-radius: 15px; overflow: hidden; }
        .modal-header { background: #17202a; color: white; padding: 18px 22px; border: none; }
        .modal-title { font-size: 17px; font-weight: 600; }
        .modal-header .btn-close { filter: invert(1); }
        .modal-body { padding: 23px; }
        .form-label { font-size: 13px; font-weight: 600; }
        .form-control { border-radius: 8px; font-size: 13px; padding: 10px 12px; }
        .form-control:focus { border-color: #17202a; box-shadow: 0 0 0 .2rem rgba(23,32,42,.08); }

        /* FOOTER SIMPEL (Sesuai Gambar) */
        .site-footer {
            text-align: center;
            padding: 25px 0 35px;
            color: #17202a;
            font-size: 13px;
            font-weight: 700;
        }

        /* RESPONSIVE */
        @media(max-width: 900px) {
            .menu-link { display: none; }
            .container-main { padding: 20px; }
            .users-header { align-items: stretch; flex-direction: column; }
            .tools { width: 100%; }
            .search-box { width: 100%; }
        }
        @media(max-width: 600px) {
            .container-main { padding: 15px; }
            .page-heading { align-items: flex-start; flex-direction: column; gap: 15px; }
            .page-heading .btn-add { width: 100%; }
            .user-area .user-name { display: none; }
            .table { min-width: 650px; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="topbar">
    <div class="container-fluid px-4 h-100">
        <div class="d-flex align-items-center justify-content-between h-100">
            <a href="dashboard.php" class="brand">
                <div class="brand-icon"><i class="bi bi-recycle"></i></div>
                <span>Bank Sampah rapaa</span>
            </a>

            <div class="d-flex align-items-center">
                <a href="dashboard.php" class="menu-link"><i class="bi bi-grid me-1"></i> Dashboard</a>
                <a href="users.php" class="menu-link"><i class="bi bi-people me-1"></i> Pengguna</a>
                <a href="sampah.php" class="menu-link active"><i class="bi bi-recycle me-1"></i> Sampah</a>
                <a href="transaksi.php" class="menu-link"><i class="bi bi-arrow-left-right me-1"></i> Transaksi</a>
                <a href="tarik.php" class="menu-link"><i class="bi bi-wallet2 me-1"></i> Tarik Saldo</a>
                <a href="pembelian.php" class="menu-link"><i class="bi bi-bag-check me-1"></i> Pembelian</a>
            </div>

            <div class="user-area">
                <div class="text-end user-name">
                    <div class="fw-semibold small"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin'); ?></div>
                    <div class="text-muted" style="font-size:10px"><?= htmlspecialchars($_SESSION['role'] ?? 'admin'); ?></div>
                </div>
                <div class="avatar"><i class="bi bi-person-fill"></i></div>
                <a href="../logout.php" class="text-danger ms-1" title="Logout"><i class="bi bi-box-arrow-right"></i></a>
            </div>
        </div>
    </div>
</nav>

<!-- MAIN CONTENT -->
<main class="container-main">

    <div class="page-heading">
        <div>
            <h1>Data Sampah</h1>
            <p>Kelola jenis sampah, harga per kilogram, dan stok bank sampah.</p>
        </div>
        <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-lg me-1"></i> Tambah Sampah
        </button>
    </div>

    <!-- STATISTIK -->
    <div class="row g-3">
        <div class="col-xl-3 col-md-6">
            <div class="summary-card">
                <div class="summary-icon summary-jenis"><i class="bi bi-trash3-fill"></i></div>
                <div class="summary-title">Jenis Sampah</div>
                <div class="summary-number"><?= $jumlahJenis; ?></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="summary-card">
                <div class="summary-icon summary-stok"><i class="bi bi-box-seam-fill"></i></div>
                <div class="summary-title">Total Stok (Kg)</div>
                <div class="summary-number"><?= number_format($totalStok, 2, ',', '.'); ?></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="summary-card">
                <div class="summary-icon summary-nilai"><i class="bi bi-cash-stack"></i></div>
                <div class="summary-title">Estimasi Nilai Stok</div>
                <div class="summary-number"><?= rupiah($nilaiStok); ?></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="summary-card">
                <div class="summary-icon summary-harga"><i class="bi bi-tags-fill"></i></div>
                <div class="summary-title">Harga Tertinggi / Kg</div>
                <div class="summary-number"><?= rupiah($hargaTertinggi); ?></div>
            </div>
        </div>
    </div>

    <!-- TABEL CARD -->
    <div class="users-card">
        <div class="users-header">
            <div>
                <h5>Daftar Jenis Sampah</h5>
                <small>Data komoditas sampah terdaftar dan ketersediaannya</small>
            </div>
            <div class="tools">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchSampah" placeholder="Cari sampah...">
                </div>
                <select id="filterStok" class="filter-select">
                    <option value="semua">Semua Stok</option>
                    <option value="ada">Stok Tersedia</option>
                    <option value="habis">Stok Habis</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle" id="tableSampah">
                <thead>
                    <tr>
                        <th width="60">NO</th>
                        <th>JENIS SAMPAH</th>
                        <th>HARGA / KG</th>
                        <th>STOK (KG)</th>
                        <th width="80">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $no = 1;
                if (mysqli_num_rows($data) > 0):
                    while ($row = mysqli_fetch_assoc($data)):
                        $stok = (float)$row['stok_kg'];
                        $statusStok = $stok > 0 ? 'ada' : 'habis';
                ?>
                    <tr data-stok="<?= $statusStok; ?>">
                        <td><span class="number"><?= $no++; ?></span></td>
                        <td>
                            <div class="sampah-item">
                                <div class="sampah-icon"><i class="bi bi-recycle"></i></div>
                                <div>
                                    <div class="sampah-name"><?= htmlspecialchars($row['jenis_sampah']); ?></div>
                                    <div class="sampah-id">ID #<?= $row['id_sampah']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="price"><?= rupiah($row['harga_per_kg']); ?></span>
                        </td>
                        <td>
                            <?php if ($stok > 0): ?>
                                <span class="stok-badge stok-ada">
                                    <i class="bi bi-box-seam me-1"></i>
                                    <?= number_format($stok, 2, ',', '.'); ?> Kg
                                </span>
                            <?php else: ?>
                                <span class="stok-badge stok-habis">
                                    <i class="bi bi-x-circle me-1"></i>
                                    Habis
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="sampah_proses.php?hapus=<?= $row['id_sampah']; ?>" 
                               class="action-btn delete-btn" 
                               title="Hapus"
                               onclick="return confirm('Yakin ingin menghapus data sampah ini?');">
                                <i class="bi bi-trash3"></i>
                            </a>
                        </td>
                    </tr>
                <?php 
                    endwhile; 
                else:
                ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">Belum ada data sampah terdaftar.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- FOOTER (Identik dengan Gambar) -->
    <div class="site-footer">
        Bank Sampah Akbar
    </div>

</main>

<!-- MODAL TAMBAH SAMPAH -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="sampah_proses.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i> Tambah Jenis Sampah</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Jenis Sampah</label>
                        <input type="text" name="jenis_sampah" class="form-control" placeholder="Contoh: Plastik BOTOL, Kardus, Besi" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga per Kg (Rp)</label>
                        <input type="number" name="harga_per_kg" class="form-control" placeholder="Contoh: 3000" min="0" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Stok Awal (Kg)</label>
                        <input type="number" name="stok_kg" class="form-control" placeholder="Contoh: 10" min="0" step="0.01" value="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-dark">
                        <i class="bi bi-check-lg me-1"></i> Simpan Sampah
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- JAVASCRIPT FILTER & SEARCH -->
<script>
const searchInput = document.getElementById("searchSampah");
const filterStok = document.getElementById("filterStok");
const rows = document.querySelectorAll("#tableSampah tbody tr[data-stok]");

function filterTable() {
    const keyword = searchInput.value.toLowerCase().trim();
    const filter = filterStok.value;

    rows.forEach(function(row) {
        const text = row.innerText.toLowerCase();
        const status = row.getAttribute("data-stok");

        const matchesSearch = text.includes(keyword);
        const matchesStok = (filter === "semua" || status === filter);

        if (matchesSearch && matchesStok) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}

searchInput.addEventListener("keyup", filterTable);
filterStok.addEventListener("change", filterTable);
</script>

</body>
</html>