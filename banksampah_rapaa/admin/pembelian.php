<?php
session_start();

include __DIR__ . "/../koneksi.php";
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$tabelAda = false;
$cekTabel = mysqli_query($conn, "SHOW TABLES LIKE 'pembelian'");
if ($cekTabel && mysqli_num_rows($cekTabel) > 0) {
    $tabelAda = true;
}

$data = null;
$stat = ['pending' => 0, 'disetujui' => 0, 'berat' => 0, 'nilai' => 0];

if ($tabelAda) {

    $data = mysqli_query($conn, "
        SELECT
            pembelian.*,
            users.nama,
            users.usernama,
            sampah.jenis_sampah,
            sampah.stok_kg
        FROM pembelian
        LEFT JOIN users ON pembelian.id_user = users.id_user
        LEFT JOIN sampah ON pembelian.id_sampah = sampah.id_sampah
        ORDER BY (pembelian.status = 'pending') DESC, pembelian.id_beli DESC
    ");

    $q = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT
            SUM(status = 'pending')   AS pending,
            SUM(status = 'disetujui') AS disetujui,
            COALESCE(SUM(CASE WHEN status = 'disetujui' THEN berat_kg END), 0) AS berat,
            COALESCE(SUM(CASE WHEN status = 'disetujui' THEN total_rp END), 0) AS nilai
        FROM pembelian
    "));

    $stat = [
        'pending'   => (int)($q['pending'] ?? 0),
        'disetujui' => (int)($q['disetujui'] ?? 0),
        'berat'     => (float)($q['berat'] ?? 0),
        'nilai'     => (float)($q['nilai'] ?? 0),
    ];
}

function kgA($angka): string
{
    return rtrim(rtrim(number_format((float)$angka, 2, ',', '.'), '0'), ',');
}

$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pembelian Pengepul - Bank Sampah rapa</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { margin: 0; background: #f8fafc; color: #0f172a; font-family: 'Inter', sans-serif; font-size: 13px; }

        .top-navbar {
            background: #fff; border-bottom: 1px solid #e2e8f0;
            padding: 0 40px; height: 64px;
            display: flex; align-items: center; justify-content: space-between; gap: 20px;
        }
        .brand-logo { display: flex; align-items: center; gap: 10px; font-weight: 700; color: #0f172a; text-decoration: none; }
        .brand-logo i { background: #0f172a; color: #fff; padding: 6px; border-radius: 8px; }
        .nav-menu { display: flex; gap: 4px; flex-wrap: wrap; }
        .nav-menu a { padding: 8px 12px; border-radius: 8px; color: #64748b; text-decoration: none; font-weight: 500; }
        .nav-menu a:hover { background: #f1f5f9; color: #0f172a; }
        .nav-menu a.active { background: #f1f5f9; color: #0f172a; font-weight: 600; }
        .btn-logout { color: #64748b; text-decoration: none; font-size: 18px; }
        .btn-logout:hover { color: #b91c1c; }

        .main-container { max-width: 1200px; margin: 32px auto; padding: 0 20px; }
        .page-title h2 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .page-title p { color: #64748b; margin-bottom: 22px; }

        .stat-card { background: #fff; border: 1px solid #f1f5f9; border-radius: 12px; padding: 18px; height: 100%; }
        .stat-card .k { color: #64748b; font-size: 12px; }
        .stat-card .v { font-size: 22px; font-weight: 700; margin-top: 2px; }

        .table-card { background: #fff; border: 1px solid #f1f5f9; border-radius: 12px; overflow: hidden; }
        .table-card .head { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; color: #64748b; font-weight: 500; font-size: 12px; padding: 11px 20px; background: #f8fafc; border-bottom: 1px solid #f1f5f9; }
        td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        .table-wrap { overflow-x: auto; }

        .badge-status { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; white-space: nowrap; }
        .pending   { background: #fefce8; color: #a16207; }
        .disetujui { background: #f0fdf4; color: #15803d; }
        .ditolak   { background: #fef2f2; color: #b91c1c; }
        .warn-stok { color: #b91c1c; font-size: 11px; }
        .empty { text-align: center; color: #64748b; padding: 40px 20px; }
        .empty i { font-size: 28px; display: block; margin-bottom: 6px; }

        @media (max-width: 900px) { .top-navbar { padding: 10px 16px; height: auto; flex-wrap: wrap; } }
    </style>
</head>
<body>

<div class="top-navbar">
    <a href="dashboard.php" class="brand-logo"><i class="bi bi-recycle"></i> Bank Sampah rapa</a>

    <nav class="nav-menu">
        <a href="dashboard.php"><i class="bi bi-grid me-1"></i> Dashboard</a>
        <a href="users.php"><i class="bi bi-people me-1"></i> Pengguna</a>
        <a href="sampah.php"><i class="bi bi-recycle me-1"></i> Sampah</a>
        <a href="transaksi.php"><i class="bi bi-arrow-left-right me-1"></i> Transaksi</a>
        <a href="tarik.php"><i class="bi bi-wallet2 me-1"></i> Tarik Saldo</a>
        <a href="pembelian.php" class="active"><i class="bi bi-bag-check me-1"></i> Pembelian</a>
    </nav>

    <a href="../logout.php" class="btn-logout" title="Keluar"><i class="bi bi-box-arrow-right"></i></a>
</div>

<div class="main-container">

    <div class="page-title">
        <h2>Pembelian Pengepul</h2>
        <p>Setujui atau tolak pengajuan pembelian sampah dari pengepul. Stok berkurang saat disetujui.</p>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
    <?php endif; ?>

    <?php if (!$tabelAda): ?>

        <div class="alert alert-warning">
            <strong>Tabel <code>pembelian</code> belum ada.</strong>
            Jalankan file <code>database_update.sql</code> pada database <code>banksampah_rapa</code>
            (phpMyAdmin &rarr; tab SQL), lalu muat ulang halaman ini.
        </div>

    <?php else: ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><div class="stat-card"><div class="k">Menunggu persetujuan</div><div class="v"><?= $stat['pending'] ?></div></div></div>
        <div class="col-6 col-lg-3"><div class="stat-card"><div class="k">Disetujui</div><div class="v"><?= $stat['disetujui'] ?></div></div></div>
        <div class="col-6 col-lg-3"><div class="stat-card"><div class="k">Total berat terjual</div><div class="v"><?= e(kgA($stat['berat'])) ?> kg</div></div></div>
        <div class="col-6 col-lg-3"><div class="stat-card"><div class="k">Nilai penjualan</div><div class="v"><?= e(rupiah($stat['nilai'])) ?></div></div></div>
    </div>

    <div class="table-card">
        <div class="head">Daftar pengajuan</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Pengepul</th>
                        <th>Jenis sampah</th>
                        <th class="num">Berat</th>
                        <th class="num">Harga / kg</th>
                        <th class="num">Total</th>
                        <th>Status</th>
                        <th class="num">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($data && mysqli_num_rows($data) > 0): ?>
                    <?php while ($r = mysqli_fetch_assoc($data)): ?>
                        <?php $kurang = ($r['status'] === 'pending' && (float)$r['berat_kg'] > (float)($r['stok_kg'] ?? 0)); ?>
                        <tr>
                            <td><?= e(date('d-m-Y', strtotime($r['tanggal']))) ?></td>
                            <td>
                                <strong><?= e($r['nama'] ?? '-') ?></strong><br>
                                <span class="text-muted">@<?= e($r['usernama'] ?? '-') ?></span>
                            </td>
                            <td>
                                <?= e($r['jenis_sampah'] ?? '-') ?>
                                <?php if ($r['status'] === 'pending'): ?>
                                    <br><span class="text-muted">stok <?= e(kgA($r['stok_kg'] ?? 0)) ?> kg</span>
                                <?php endif; ?>
                            </td>
                            <td class="num"><?= e(kgA($r['berat_kg'])) ?> kg</td>
                            <td class="num"><?= e(rupiah($r['harga_per_kg'])) ?></td>
                            <td class="num fw-semibold"><?= e(rupiah($r['total_rp'])) ?></td>
                            <td><span class="badge-status <?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td>
                            <td class="num">
                                <?php if ($r['status'] === 'pending'): ?>
                                    <form method="POST" action="pembelian_proses.php" class="d-inline-flex gap-1">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$r['id_beli'] ?>">
                                        <button type="submit" name="aksi" value="setujui"
                                                class="btn btn-success btn-sm" <?= $kurang ? 'disabled title="Stok tidak cukup"' : '' ?>
                                                onclick="return confirm('Setujui pembelian ini? Stok akan dikurangi.')">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                        <button type="submit" name="aksi" value="tolak"
                                                class="btn btn-danger btn-sm"
                                                onclick="return confirm('Tolak pembelian ini?')">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                    <?php if ($kurang): ?><div class="warn-stok mt-1">Stok kurang</div><?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8"><div class="empty"><i class="bi bi-inbox"></i>Belum ada pengajuan pembelian.</div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php endif; ?>

</div>

</body>
</html>
