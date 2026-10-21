<?php
session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

include __DIR__ . "/../koneksi.php";
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

if (!function_exists('rupiah')) {
    function rupiah($angka) {
        return 'Rp ' . number_format((float)$angka, 0, ',', '.');
    }
}

$pesan = '';
$pesanTipe = '';

/* =====================================================
   TAMBAH PENGAJUAN PENARIKAN
===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_penarikan'])) {

    $idUser = (int)($_POST['id_user'] ?? 0);
    $nominal = (float)($_POST['nominal'] ?? 0);

    if ($idUser <= 0) {

        $pesan = 'Pilih nasabah terlebih dahulu.';
        $pesanTipe = 'danger';

    } elseif ($nominal < 1000) {

        $pesan = 'Minimal penarikan adalah Rp 1.000.';
        $pesanTipe = 'danger';

    } elseif (fmod($nominal, 1000) != 0) {

        $pesan = 'Nominal penarikan harus kelipatan Rp 1.000.';
        $pesanTipe = 'danger';

    } else {

        /* Ambil saldo user */
        $stmtSaldo = mysqli_prepare(
            $conn,
            "SELECT saldo FROM users
             WHERE id_user = ?
             AND role IN ('siswa', 'guru')
             LIMIT 1"
        );

        mysqli_stmt_bind_param($stmtSaldo, "i", $idUser);
        mysqli_stmt_execute($stmtSaldo);

        $resultSaldo = mysqli_stmt_get_result($stmtSaldo);
        $dataSaldo = mysqli_fetch_assoc($resultSaldo);

        mysqli_stmt_close($stmtSaldo);

        $saldoUser = (float)($dataSaldo['saldo'] ?? 0);


        /* Cek pengajuan pending */
        $stmtPending = mysqli_prepare(
            $conn,
            "SELECT COUNT(*) AS jumlah
             FROM tarik_saldo
             WHERE id_user = ?
             AND status = 'pending'"
        );

        mysqli_stmt_bind_param($stmtPending, "i", $idUser);
        mysqli_stmt_execute($stmtPending);

        $resultPending = mysqli_stmt_get_result($stmtPending);
        $dataPending = mysqli_fetch_assoc($resultPending);

        mysqli_stmt_close($stmtPending);

        $pendingUser = (int)($dataPending['jumlah'] ?? 0);


        if ($nominal > $saldoUser) {

            $pesan = 'Saldo tidak mencukupi. Saldo tersedia: ' .
                     rupiah($saldoUser) . '.';

            $pesanTipe = 'danger';

        } elseif ($pendingUser > 0) {

            $pesan = 'Masih ada pengajuan penarikan yang menunggu persetujuan.';
            $pesanTipe = 'warning';

        } else {

            /* Simpan pengajuan */
            $stmtInsert = mysqli_prepare(
                $conn,
                "INSERT INTO tarik_saldo
                (id_user, nominal, tanggal, status)
                VALUES (?, ?, NOW(), 'pending')"
            );

            if ($stmtInsert) {

                mysqli_stmt_bind_param(
                    $stmtInsert,
                    "id",
                    $idUser,
                    $nominal
                );

                if (mysqli_stmt_execute($stmtInsert)) {

                    $pesan = 'Pengajuan penarikan berhasil dibuat dan menunggu persetujuan.';
                    $pesanTipe = 'success';

                } else {

                    $pesan = 'Pengajuan penarikan gagal disimpan.';
                    $pesanTipe = 'danger';
                }

                mysqli_stmt_close($stmtInsert);

            } else {

                $pesan = 'Tidak dapat memproses pengajuan penarikan.';
                $pesanTipe = 'danger';
            }
        }
    }
}


/* =====================================================
   DATA PENARIKAN
===================================================== */
$query = mysqli_query($conn, "
    SELECT
        tarik_saldo.*,
        users.nama,
        users.usernama,
        users.role
    FROM tarik_saldo
    LEFT JOIN users
        ON tarik_saldo.id_user = users.id_user
    ORDER BY tarik_saldo.id_tarik DESC
");


/* =====================================================
   STATISTIK
===================================================== */

/* Total permintaan */
$qTotal = mysqli_query($conn, "
    SELECT COUNT(*) AS jumlah
    FROM tarik_saldo
");

$totalTarik = (int)(mysqli_fetch_assoc($qTotal)['jumlah'] ?? 0);


/* Total dicairkan */
$qNominal = mysqli_query($conn, "
    SELECT COALESCE(SUM(nominal), 0) AS total
    FROM tarik_saldo
    WHERE status = 'disetujui'
");

$totalNominal = (float)(mysqli_fetch_assoc($qNominal)['total'] ?? 0);


/* Pending */
$qPending = mysqli_query($conn, "
    SELECT COUNT(*) AS jumlah
    FROM tarik_saldo
    WHERE status = 'pending'
");

$totalPending = (int)(mysqli_fetch_assoc($qPending)['jumlah'] ?? 0);


/* Dicairkan hari ini */
$qHariIni = mysqli_query($conn, "
    SELECT COUNT(*) AS jumlah
    FROM tarik_saldo
    WHERE status = 'disetujui'
    AND DATE(tanggal) = CURDATE()
");

$tarikHariIni = (int)(mysqli_fetch_assoc($qHariIni)['jumlah'] ?? 0);

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Penarikan Saldo - Bank Sampah rapa</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
rel="stylesheet">

<link
href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
rel="stylesheet">


<style>

:root {
    --bg-body: #f8fafc;
    --text-dark: #0f172a;
    --text-muted: #64748b;
    --border: #e2e8f0;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: var(--bg-body);
    color: var(--text-dark);
    font-family: 'Inter', sans-serif;
    font-size: 13px;
}


/* =========================
   NAVBAR
========================= */

.top-navbar {
    height: 64px;
    background: white;
    border-bottom: 1px solid var(--border);
    padding: 0 40px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    position: sticky;
    top: 0;
    z-index: 1000;
}

.brand-logo {
    display: flex;
    align-items: center;
    gap: 10px;

    text-decoration: none;
    color: var(--text-dark);

    font-size: 15px;
    font-weight: 700;
}

.brand-logo i {
    background: #0f172a;
    color: white;

    width: 30px;
    height: 30px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 8px;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 5px;
}

.nav-links a {
    color: var(--text-muted);
    text-decoration: none;

    padding: 8px 14px;
    border-radius: 8px;

    font-weight: 500;

    display: flex;
    align-items: center;
    gap: 7px;

    transition: .2s;
}

.nav-links a:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.nav-links a.active {
    background: #0f172a;
    color: white;
}


.user-profile {
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-info {
    text-align: right;
}

.user-name {
    font-weight: 600;
    font-size: 12px;
}

.user-role {
    color: var(--text-muted);
    font-size: 10px;
}

.user-avatar {
    width: 32px;
    height: 32px;

    background: #e2e8f0;
    border-radius: 50%;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #64748b;
}

.btn-logout {
    color: #ef4444;
    text-decoration: none;
    padding: 6px;
}


/* =========================
   MAIN
========================= */

.main-container {
    max-width: 1140px;
    margin: 32px auto;
    padding: 0 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;

    margin-bottom: 24px;
}

.page-title h2 {
    margin: 0;

    font-size: 22px;
    font-weight: 700;
}

.page-title p {
    margin: 4px 0 0;

    color: var(--text-muted);
    font-size: 13px;
}


/* =========================
   BUTTON TAMBAH
========================= */

.btn-add-withdraw {
    background: #0f172a !important;
    border-color: #0f172a !important;

    color: white !important;

    border-radius: 8px;

    font-size: 12px;
    font-weight: 600;

    padding: 9px 14px;

    transition: .2s;
}

.btn-add-withdraw:hover {
    background: #000000 !important;
    border-color: #000000 !important;

    transform: translateY(-1px);
}


/* =========================
   STAT CARD
========================= */

.stat-card {
    background: white;

    border: 1px solid #f1f5f9;
    border-radius: 12px;

    padding: 20px;

    height: 100%;

    box-shadow:
        0 1px 3px rgba(0,0,0,.02);
}

.stat-icon {
    width: 36px;
    height: 36px;

    border-radius: 8px;

    display: flex;
    align-items: center;
    justify-content: center;

    margin-bottom: 15px;

    font-size: 16px;
}

.icon-total {
    background: #f1f5f9;
    color: #0f172a;
}

.icon-nominal {
    background: #f0fdf4;
    color: #15803d;
}

.icon-pending {
    background: #fefce8;
    color: #ca8a04;
}

.icon-hari {
    background: #eff6ff;
    color: #2563eb;
}

.stat-label {
    color: var(--text-muted);
    font-size: 11px;
    font-weight: 500;
}

.stat-value {
    font-size: 20px;
    font-weight: 700;

    margin-top: 3px;
}

.stat-desc {
    margin-top: 6px;

    color: #94a3b8;
    font-size: 11px;
}


/* =========================
   TABLE
========================= */

.table-card {
    background: white;

    border: 1px solid #f1f5f9;
    border-radius: 12px;

    margin-top: 24px;

    overflow: hidden;

    box-shadow:
        0 1px 3px rgba(0,0,0,.02);
}

.table-header {
    padding: 20px;

    display: flex;
    justify-content: space-between;
    align-items: center;

    border-bottom: 1px solid #f1f5f9;
}

.table-title {
    font-size: 14px;
    font-weight: 700;
}

.table-subtitle {
    margin-top: 2px;

    color: var(--text-muted);
    font-size: 12px;
}

.search-box {
    width: 220px;
    position: relative;
}

.search-box input {
    height: 36px;

    padding-left: 32px;

    border-radius: 8px;
    border: 1px solid var(--border);

    font-size: 12px;
}

.search-box i {
    position: absolute;

    left: 10px;
    top: 50%;

    transform: translateY(-50%);

    color: #94a3b8;
}


/* TABLE */

.table {
    margin: 0;
}

.table thead th {
    background: #f8fafc;

    color: var(--text-muted);

    font-size: 11px;
    font-weight: 600;

    text-transform: uppercase;

    letter-spacing: .5px;

    padding: 12px 20px;

    border-bottom: 1px solid var(--border);
}

.table tbody td {
    padding: 16px 20px;

    vertical-align: middle;

    border-bottom: 1px solid #f1f5f9;

    font-size: 13px;
}


/* =========================
   STATUS
========================= */

.badge-status {
    padding: 5px 10px;

    border-radius: 6px;

    font-size: 11px;
    font-weight: 600;

    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.badge-pending {
    background: #fefce8;
    color: #a16207;

    border: 1px solid #fef08a;
}

.badge-success {
    background: #f0fdf4;
    color: #15803d;

    border: 1px solid #bbf7d0;
}

.badge-danger {
    background: #fef2f2;
    color: #b91c1c;

    border: 1px solid #fecaca;
}


/* ACTION */

.btn-action {
    width: 30px;
    height: 30px;

    padding: 0;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    border-radius: 6px;
}


/* =========================
   MODAL
========================= */

.withdraw-modal .modal-content {
    border: 0;
    border-radius: 14px;

    box-shadow:
        0 18px 50px rgba(15,23,42,.14);
}

.withdraw-modal .modal-header {
    padding: 18px 20px;

    border-bottom: 1px solid #f1f5f9;
}

.withdraw-modal .modal-title {
    font-size: 15px;
    font-weight: 700;
}

.withdraw-modal .modal-body {
    padding: 20px;
}

.withdraw-modal label {
    display: block;

    margin-bottom: 7px;

    font-size: 12px;
    font-weight: 600;
}

.withdraw-modal .form-control {
    height: 42px;

    border-radius: 8px;

    font-size: 13px;
}

.withdraw-info {
    background: #f8fafc;

    border: 1px solid #e2e8f0;

    color: #475569;

    border-radius: 9px;

    padding: 11px 12px;

    font-size: 11px;

    margin-bottom: 16px;
}

.withdraw-info strong {
    color: #0f172a;
}


/* =========================
   FOOTER
========================= */

.site-footer {
    margin-top: 35px;

    padding: 22px;

    text-align: center;

    color: #94a3b8;

    font-size: 12px;
}

.site-footer strong {
    color: #0f172a;
}


/* =========================
   RESPONSIVE
========================= */

@media (max-width: 992px) {

    .top-navbar {
        padding: 0 20px;
    }

    .user-info {
        display: none;
    }

}

@media (max-width: 768px) {

    .top-navbar {
        height: auto;

        padding: 12px 15px;

        flex-wrap: wrap;
        gap: 10px;
    }

    .nav-links {
        order: 3;

        width: 100%;

        overflow-x: auto;
    }

    .main-container {
        margin-top: 24px;
    }

    .page-header {
        flex-direction: column;

        gap: 15px;
    }

    .table-header {
        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
    }

    .search-box {
        width: 100%;
    }

}

</style>

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<div class="top-navbar">

    <a href="dashboard.php" class="brand-logo">

        <i class="bi bi-recycle"></i>

        <span>Bank Sampah rapa</span>

    </a>


    <div class="nav-links">

        <a href="dashboard.php">
            <i class="bi bi-grid"></i>
            Dashboard
        </a>

        <a href="users.php">
            <i class="bi bi-people"></i>
            Pengguna
        </a>

        <a href="sampah.php">
            <i class="bi bi-recycle"></i>
            Sampah
        </a>

        <a href="transaksi.php">
            <i class="bi bi-arrow-left-right"></i>
            Transaksi
        </a>

        <a href="tarik.php" class="active">
            <i class="bi bi-wallet2"></i>
            Tarik Saldo
        </a>
        <a href="pembelian.php">
            <i class="bi bi-bag-check"></i>
            Pembelian
        </a>

    </div>


    <div class="user-profile">

        <div class="user-info">

            <div class="user-name">
                <?= htmlspecialchars($_SESSION['nama'] ?? 'Admin'); ?>
            </div>

            <div class="user-role">
                <?= ucfirst(
                    htmlspecialchars($_SESSION['role'] ?? 'admin')
                ); ?>
            </div>

        </div>


        <div class="user-avatar">

            <i class="bi bi-person"></i>

        </div>


        <a
            href="../logout.php"
            class="btn-logout"
            title="Logout">

            <i class="bi bi-box-arrow-right"></i>

        </a>

    </div>

</div>



<!-- =====================================================
     MAIN
===================================================== -->

<div class="main-container">


    <?php if ($pesan !== ''): ?>

        <div
            class="alert alert-<?= htmlspecialchars($pesanTipe); ?> alert-dismissible fade show"
            style="font-size:12px;border-radius:10px;">

            <?= htmlspecialchars($pesan); ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    <?php endif; ?>


    <!-- HEADER -->

    <div class="page-header">

        <div class="page-title">

            <h2>Penarikan Saldo</h2>

            <p>
                Kelola permintaan penarikan saldo nasabah bank sampah.
            </p>

        </div>


        <button
            type="button"
            class="btn btn-add-withdraw"
            data-bs-toggle="modal"
            data-bs-target="#modalTambahTarik">

            <i class="bi bi-plus-lg me-1"></i>

            Tambah Penarikan

        </button>

    </div>



    <!-- =================================================
         STATISTIK
    ================================================= -->

    <div class="row g-3">


        <div class="col-md-3">

            <div class="stat-card">

                <div class="stat-icon icon-total">
                    <i class="bi bi-wallet2"></i>
                </div>

                <div class="stat-label">
                    Total Permintaan
                </div>

                <div class="stat-value">
                    <?= number_format($totalTarik, 0, ',', '.'); ?>
                </div>

                <div class="stat-desc">
                    Seluruh pengajuan penarikan
                </div>

            </div>

        </div>



        <div class="col-md-3">

            <div class="stat-card">

                <div class="stat-icon icon-nominal">
                    <i class="bi bi-cash-stack"></i>
                </div>

                <div class="stat-label">
                    Total Dicairkan
                </div>

                <div class="stat-value">
                    <?= rupiah($totalNominal); ?>
                </div>

                <div class="stat-desc">
                    Penarikan yang disetujui
                </div>

            </div>

        </div>



        <div class="col-md-3">

            <div class="stat-card">

                <div class="stat-icon icon-pending">
                    <i class="bi bi-hourglass-split"></i>
                </div>

                <div class="stat-label">
                    Menunggu Persetujuan
                </div>

                <div class="stat-value">
                    <?= number_format($totalPending, 0, ',', '.'); ?>
                </div>

                <div class="stat-desc">
                    Status transaksi pending
                </div>

            </div>

        </div>



        <div class="col-md-3">

            <div class="stat-card">

                <div class="stat-icon icon-hari">
                    <i class="bi bi-calendar-check"></i>
                </div>

                <div class="stat-label">
                    Dicairkan Hari Ini
                </div>

                <div class="stat-value">
                    <?= number_format($tarikHariIni, 0, ',', '.'); ?>
                </div>

                <div class="stat-desc">
                    Persetujuan hari ini
                </div>

            </div>

        </div>


    </div>



    <!-- =================================================
         TABLE
    ================================================= -->

    <div class="table-card">


        <div class="table-header">

            <div>

                <div class="table-title">
                    Daftar Penarikan Saldo
                </div>

                <div class="table-subtitle">
                    Data riwayat pengajuan penarikan dana nasabah
                </div>

            </div>


            <div class="search-box">

                <i class="bi bi-search"></i>

                <input
                    type="text"
                    id="searchInput"
                    class="form-control"
                    placeholder="Cari penarikan..."
                >

            </div>

        </div>



        <div class="table-responsive">

            <table
                class="table"
                id="penarikanTable">

                <thead>

                    <tr>

                        <th width="60">
                            NO
                        </th>

                        <th>
                            NASABAH
                        </th>

                        <th>
                            NOMINAL
                        </th>

                        <th>
                            TANGGAL
                        </th>

                        <th>
                            STATUS
                        </th>

                        <th
                            width="100"
                            class="text-center">

                            AKSI

                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php

                $no = 1;

                if ($query && mysqli_num_rows($query) > 0):

                    while ($row = mysqli_fetch_assoc($query)):

                ?>

                    <tr>

                        <td class="text-muted">
                            <?= $no++; ?>
                        </td>


                        <td>

                            <div class="fw-semibold">

                                <?= htmlspecialchars(
                                    $row['nama'] ?? '-'
                                ); ?>

                            </div>

                            <div
                                class="text-muted"
                                style="font-size:11px;">

                                @<?= htmlspecialchars(
                                    $row['usernama'] ?? '-'
                                ); ?>

                            </div>

                        </td>


                        <td class="fw-bold">

                            <?= rupiah($row['nominal']); ?>

                        </td>


                        <td class="text-muted">

                            <?= date(
                                'd-m-Y',
                                strtotime($row['tanggal'])
                            ); ?>

                        </td>


                        <td>

                        <?php if ($row['status'] === 'pending'): ?>

                            <span class="badge-status badge-pending">

                                <i class="bi bi-clock"></i>

                                Pending

                            </span>


                        <?php elseif ($row['status'] === 'disetujui'): ?>

                            <span class="badge-status badge-success">

                                <i class="bi bi-check-circle"></i>

                                Disetujui

                            </span>


                        <?php else: ?>

                            <span class="badge-status badge-danger">

                                <i class="bi bi-x-circle"></i>

                                Ditolak

                            </span>

                        <?php endif; ?>

                        </td>


                        <td class="text-center">

                        <?php if ($row['status'] === 'pending'): ?>

                            <div
                                class="d-flex justify-content-center gap-1">

                                <a
                                    href="tarik_proses.php?aksi=setujui&id=<?= (int)$row['id_tarik']; ?>"
                                    class="btn btn-success btn-action"
                                    title="Setujui"
                                    onclick="return confirm('Setujui penarikan saldo ini? Saldo nasabah akan dikurangi.')">

                                    <i class="bi bi-check-lg"></i>

                                </a>


                                <a
                                    href="tarik_proses.php?aksi=tolak&id=<?= (int)$row['id_tarik']; ?>"
                                    class="btn btn-danger btn-action"
                                    title="Tolak"
                                    onclick="return confirm('Tolak penarikan saldo ini?')">

                                    <i class="bi bi-x-lg"></i>

                                </a>

                            </div>

                        <?php else: ?>

                            <span class="text-muted">
                                -
                            </span>

                        <?php endif; ?>

                        </td>

                    </tr>


                <?php

                    endwhile;

                else:

                ?>

                    <tr>

                        <td colspan="6">

                            <div
                                class="text-center text-muted"
                                style="padding:40px;">

                                <i
                                    class="bi bi-inbox fs-2 d-block mb-2">
                                </i>

                                Belum ada riwayat penarikan saldo.

                            </div>

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>



<!-- =====================================================
     MODAL TAMBAH PENARIKAN
===================================================== -->

<div
    class="modal fade withdraw-modal"
    id="modalTambahTarik"
    tabindex="-1">

    <div class="modal-dialog modal-dialog-centered modal-sm">

        <div class="modal-content">

            <form method="POST" action="">


                <div class="modal-header">

                    <h5 class="modal-title">

                        <i
                            class="bi bi-wallet2 me-2"
                            style="color:#0f172a;">
                        </i>

                        Tambah Penarikan

                    </h5>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                    </button>

                </div>



                <div class="modal-body">


                    <div class="withdraw-info">

                        <i class="bi bi-info-circle me-1"></i>

                        Pengajuan akan berstatus
                        <strong>Pending</strong>
                        sampai disetujui admin.

                    </div>



                    <label for="id_user_tarik">
                        Nasabah
                    </label>

                    <select
                        name="id_user"
                        id="id_user_tarik"
                        class="form-select form-select-sm mb-3"
                        required>

                        <option value="">-- Pilih Nasabah --</option>

                        <?php
                        $qNasabah = mysqli_query(
                            $conn,
                            "SELECT id_user, nama, role, saldo
                             FROM users
                             WHERE role IN ('siswa', 'guru')
                             ORDER BY nama ASC"
                        );
                        while ($n = mysqli_fetch_assoc($qNasabah)):
                        ?>
                            <option value="<?= (int)$n['id_user']; ?>">
                                <?= htmlspecialchars($n['nama']); ?>
                                - <?= ucfirst($n['role']); ?>
                                (<?= rupiah($n['saldo']); ?>)
                            </option>
                        <?php endwhile; ?>

                    </select>


                    <label for="nominal">
                        Nominal Penarikan
                    </label>


                    <div class="input-group">

                        <span
                            class="input-group-text"
                            style="font-size:12px;">

                            Rp

                        </span>


                        <input
                            type="number"
                            name="nominal"
                            id="nominal"
                            class="form-control"
                            min="1000"
                            step="1000"
                            placeholder="Contoh: 50000"
                            required
                        >

                    </div>


                    <div
                        class="text-muted mt-2"
                        style="font-size:10px;">

                        Nominal harus kelipatan
                        <strong>Rp 1.000</strong>
                        dan tidak melebihi saldo yang tersedia.

                    </div>

                </div>



                <div
                    class="modal-footer"
                    style="
                        border-top:1px solid #f1f5f9;
                        padding:13px 20px;
                    ">

                    <button
                        type="button"
                        class="btn btn-light btn-sm"
                        data-bs-dismiss="modal">

                        Batal

                    </button>


                    <button
                        type="submit"
                        name="tambah_penarikan"
                        class="btn btn-sm"
                        style="
                            background:#0f172a;
                            border-color:#0f172a;
                            color:#fff;
                            font-size:12px;
                            font-weight:600;
                        ">

                        <i class="bi bi-send me-1"></i>

                        Ajukan Penarikan

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>



<footer class="site-footer">

    <strong>
        Bank Sampah Akbar
    </strong>

</footer>


<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


<script>

const searchInput =
    document.getElementById('searchInput');

searchInput.addEventListener('keyup', function() {

    const filter =
        this.value.toLowerCase();

    const rows =
        document.querySelectorAll(
            '#penarikanTable tbody tr'
        );

    rows.forEach(function(row) {

        const text =
            row.textContent.toLowerCase();

        row.style.display =
            text.includes(filter)
                ? ''
                : 'none';

    });

});

</script>

</body>
</html>