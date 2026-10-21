<?php

session_start();
include __DIR__ . "/../koneksi.php";
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}


/* =====================================================
   FUNGSI RUPIAH
===================================================== */

if (!function_exists('rupiah')) {
    function rupiah($angka)
    {
        return 'Rp ' . number_format((float)$angka, 0, ',', '.');
    }
}


/* =====================================================
   FUNGSI TOTAL DATA
===================================================== */

function totalData($conn, $query)
{
    $result = mysqli_query($conn, $query);

    if (!$result) {
        return 0;
    }

    $data = mysqli_fetch_assoc($result);

    return $data['total'] ?? 0;
}


/* =====================================================
   DATA DASHBOARD
===================================================== */

$totalUser = totalData(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role != 'admin'"
);

$totalSampah = totalData(
    $conn,
    "SELECT COUNT(*) AS total
     FROM sampah"
);

$totalTransaksi = totalData(
    $conn,
    "SELECT COUNT(*) AS total
     FROM transaksi"
);

$totalSaldo = totalData(
    $conn,
    "SELECT COALESCE(SUM(saldo),0) AS total
     FROM users
     WHERE role != 'admin'"
);


/* =====================================================
   GRAFIK 7 HARI
===================================================== */

$labelGrafik = [];
$dataGrafik = [];

$labelPendapatan = [];
$dataPendapatan = [];

$labelBerat = [];
$dataBerat = [];


for ($i = 6; $i >= 0; $i--) {

    $tanggal = date(
        'Y-m-d',
        strtotime("-$i days")
    );


    $labelGrafik[] = date(
        'd M',
        strtotime($tanggal)
    );

    $labelPendapatan[] = date(
        'd M',
        strtotime($tanggal)
    );

    $labelBerat[] = date(
        'd M',
        strtotime($tanggal)
    );


    /* -----------------------------------------------
       JUMLAH TRANSAKSI
    ------------------------------------------------ */

    $queryGrafik = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM transaksi
         WHERE DATE(tanggal) = '$tanggal'"
    );

    if ($queryGrafik) {

        $hasilGrafik =
            mysqli_fetch_assoc($queryGrafik);

        $dataGrafik[] =
            (int)($hasilGrafik['total'] ?? 0);

    } else {

        $dataGrafik[] = 0;
    }


    /* -----------------------------------------------
       PENDAPATAN
    ------------------------------------------------ */

    $queryPendapatan = mysqli_query(
        $conn,
        "SELECT COALESCE(SUM(total_rp),0) AS total
         FROM transaksi
         WHERE DATE(tanggal) = '$tanggal'"
    );

    if ($queryPendapatan) {

        $hasilPendapatan =
            mysqli_fetch_assoc($queryPendapatan);

        $dataPendapatan[] =
            (float)($hasilPendapatan['total'] ?? 0);

    } else {

        $dataPendapatan[] = 0;
    }


    /* -----------------------------------------------
       BERAT SAMPAH
    ------------------------------------------------ */

    $queryBerat = mysqli_query(
        $conn,
        "SELECT COALESCE(SUM(berat_kg),0) AS total
         FROM transaksi
         WHERE DATE(tanggal) = '$tanggal'"
    );

    if ($queryBerat) {

        $hasilBerat =
            mysqli_fetch_assoc($queryBerat);

        $dataBerat[] =
            (float)($hasilBerat['total'] ?? 0);

    } else {

        $dataBerat[] = 0;
    }
}


/* =====================================================
   GRAFIK JENIS SAMPAH
===================================================== */

$labelJenis = [];
$dataJenis = [];

$queryJenis = mysqli_query(
    $conn,
    "SELECT
        sampah.jenis_sampah,
        COUNT(transaksi.id_transaksi) AS total
     FROM transaksi
     LEFT JOIN sampah
        ON transaksi.id_sampah = sampah.id_sampah
     GROUP BY
        sampah.id_sampah,
        sampah.jenis_sampah
     ORDER BY total DESC
     LIMIT 6"
);

if ($queryJenis) {

    while ($rowJenis =
        mysqli_fetch_assoc($queryJenis)
    ) {

        $labelJenis[] =
            $rowJenis['jenis_sampah']
            ?? 'Tidak diketahui';

        $dataJenis[] =
            (int)($rowJenis['total'] ?? 0);
    }
}


/* =====================================================
   TRANSAKSI TERBARU
===================================================== */

$queryTransaksi = mysqli_query(
    $conn,
    "SELECT
        transaksi.*,
        users.nama,
        sampah.jenis_sampah
     FROM transaksi
     LEFT JOIN users
        ON transaksi.id_user = users.id_user
     LEFT JOIN sampah
        ON transaksi.id_sampah = sampah.id_sampah
     ORDER BY transaksi.id_transaksi DESC
     LIMIT 5"
);

?>

<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>
    Dashboard - Bank Sampah rapaa
</title>


<!-- BOOTSTRAP -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<!-- BOOTSTRAP ICON -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    rel="stylesheet"
>


<!-- CHART JS -->

<script
    src="https://cdn.jsdelivr.net/npm/chart.js">
</script>


<style>

/* =====================================================
   GLOBAL
===================================================== */

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    background: #f6f8fb;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

    color: #17202a;
}


/* =====================================================
   NAVBAR
===================================================== */

.navbar-custom {

    height: 72px;

    background: white;

    border-bottom:
        1px solid #e9ecef;

    position: sticky;

    top: 0;

    z-index: 1000;
}


.brand {

    display: flex;

    align-items: center;

    gap: 12px;

    text-decoration: none;

    color: #17202a;

    font-weight: 700;

    font-size: 19px;
}


.brand-logo {

    width: 42px;

    height: 42px;

    border-radius: 12px;

    background:
        linear-gradient(
            135deg,
            #198754,
            #20c997
        );

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 22px;

    box-shadow:
        0 5px 15px
        rgba(25,135,84,.18);
}


.nav-link-custom {

    color: #6c757d;

    text-decoration: none;

    font-size: 14px;

    font-weight: 500;

    margin: 0 8px;

    padding: 8px 12px;

    border-radius: 8px;

    transition: .2s;
}


.nav-link-custom:hover {

    background: #f0f7f3;

    color: #198754;
}


.nav-link-custom.active {

    color: #198754;

    background: #eaf6ef;
}


.profile-button {

    display: flex;

    align-items: center;

    gap: 10px;

    border: none;

    background: transparent;

    padding: 5px 8px;

    border-radius: 10px;
}


.profile-button:hover {

    background: #f5f5f5;
}


.profile-avatar {

    width: 40px;

    height: 40px;

    border-radius: 50%;

    background: #dff3e8;

    color: #198754;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 19px;
}


/* =====================================================
   MAIN
===================================================== */

.main-container {

    max-width: 1400px;

    margin: auto;

    padding: 32px;
}


/* =====================================================
   PAGE TITLE
===================================================== */

.page-title {

    margin-bottom: 25px;
}


.page-title h1 {

    font-size: 28px;

    font-weight: 700;

    margin-bottom: 5px;
}


.page-title p {

    margin: 0;

    color: #7b8490;

    font-size: 14px;
}


/* =====================================================
   WELCOME
===================================================== */

.welcome-card {

    background: white;

    border-radius: 20px;

    padding: 30px;

    margin-bottom: 25px;

    border: 1px solid #e9ecef;

    position: relative;

    overflow: hidden;
}


.welcome-card::after {

    content: "";

    position: absolute;

    width: 180px;

    height: 180px;

    border-radius: 50%;

    background: #e8f6ef;

    right: -50px;

    top: -60px;
}


.welcome-card::before {

    content: "";

    position: absolute;

    width: 100px;

    height: 100px;

    border-radius: 50%;

    background: #f1faf5;

    right: 80px;

    bottom: -50px;
}


.welcome-content {

    position: relative;

    z-index: 2;
}


.welcome-label {

    color: #198754;

    font-size: 13px;

    font-weight: 600;

    text-transform: uppercase;

    letter-spacing: 1px;

    margin-bottom: 8px;
}


.welcome-card h2 {

    font-size: 27px;

    font-weight: 700;

    margin-bottom: 8px;
}


.welcome-card p {

    color: #6c757d;

    margin-bottom: 20px;
}


.date-badge {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    background: #f4f6f8;

    padding: 8px 13px;

    border-radius: 8px;

    color: #66707a;

    font-size: 13px;
}


/* =====================================================
   STATISTIK
===================================================== */

.stat-card {

    background: white;

    border: 1px solid #e9ecef;

    border-radius: 16px;

    padding: 22px;

    height: 100%;

    position: relative;

    overflow: hidden;

    transition: .25s;
}


.stat-card:hover {

    transform:
        translateY(-4px);

    box-shadow:
        0 12px 30px
        rgba(0,0,0,.07);
}


.stat-top {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 20px;
}


.stat-icon {

    width: 48px;

    height: 48px;

    border-radius: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;
}


.green {

    background: #e2f4e9;

    color: #198754;
}


.blue {

    background: #e5efff;

    color: #0d6efd;
}


.orange {

    background: #fff2dc;

    color: #d88900;
}


.purple {

    background: #eee7fa;

    color: #6f42c1;
}


.stat-arrow {

    color: #adb5bd;

    font-size: 17px;
}


.stat-label {

    color: #7c8792;

    font-size: 13px;

    margin-bottom: 5px;
}


.stat-number {

    font-size: 25px;

    font-weight: 700;

    margin-bottom: 5px;
}


.stat-description {

    color: #9aa1a8;

    font-size: 12px;
}


/* =====================================================
   GRAFIK
===================================================== */

.chart-card {

    background: white;

    border: 1px solid #e9ecef;

    border-radius: 18px;

    overflow: hidden;

    transition: .25s;

    height: 100%;
}


.chart-card:hover {

    transform:
        translateY(-3px);

    box-shadow:
        0 14px 35px
        rgba(0,0,0,.07);
}


.chart-header {

    padding: 22px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    border-bottom:
        1px solid #edf0f2;
}


.chart-header h5 {

    margin: 0;

    font-weight: 700;

    font-size: 17px;
}


.chart-header small {

    color: #9299a0;

    font-size: 12px;
}


.chart-area {

    position: relative;

    padding: 25px;

    height: 350px;
}


.chart-icon {

    width: 40px;

    height: 40px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 10px;

    background: #eaf6ef;

    color: #198754;
}


.type-badge {

    display: inline-flex;

    align-items: center;

    padding: 6px 10px;

    border-radius: 7px;

    background: #eaf6ef;

    color: #198754;

    font-size: 11px;

    font-weight: 600;
}


/* =====================================================
   QUICK MENU
===================================================== */

.section-title {

    font-size: 18px;

    font-weight: 700;

    margin-bottom: 15px;
}


.quick-card {

    background: white;

    border: 1px solid #e9ecef;

    border-radius: 14px;

    padding: 18px;

    text-decoration: none;

    color: #212529;

    display: flex;

    align-items: center;

    gap: 14px;

    height: 100%;

    transition: .2s;
}


.quick-card:hover {

    border-color: #198754;

    transform:
        translateY(-2px);

    color: #198754;

    box-shadow:
        0 8px 20px
        rgba(25,135,84,.08);
}


.quick-icon {

    width: 44px;

    height: 44px;

    border-radius: 11px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #eef7f2;

    color: #198754;

    font-size: 19px;
}


.quick-card strong {

    display: block;

    font-size: 14px;

    margin-bottom: 2px;
}


.quick-card small {

    color: #9299a0;

    font-size: 11px;
}


/* =====================================================
   TRANSACTION
===================================================== */

.transaction-card {

    background: white;

    border: 1px solid #e9ecef;

    border-radius: 18px;

    overflow: hidden;
}


.transaction-header {

    padding: 22px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    border-bottom:
        1px solid #edf0f2;
}


.transaction-header h5 {

    margin: 0;

    font-weight: 700;

    font-size: 17px;
}


.transaction-header small {

    color: #9299a0;
}


.btn-see {

    border: 1px solid #198754;

    color: #198754;

    background: white;

    border-radius: 8px;

    padding: 7px 13px;

    font-size: 12px;

    font-weight: 600;

    text-decoration: none;
}


.btn-see:hover {

    background: #198754;

    color: white;
}


.table {

    margin: 0;
}


.table thead th {

    background: #fafbfc;

    border-bottom:
        1px solid #edf0f2;

    border-top: none;

    color: #8b949e;

    font-size: 11px;

    font-weight: 600;

    text-transform: uppercase;

    padding: 14px 20px;
}


.table tbody td {

    padding: 16px 20px;

    vertical-align: middle;

    border-color: #f0f1f2;

    font-size: 13px;
}


.user-info {

    display: flex;

    align-items: center;

    gap: 10px;
}


.user-avatar {

    width: 35px;

    height: 35px;

    border-radius: 50%;

    background: #e2f4e9;

    color: #198754;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 15px;
}


.user-name {

    font-weight: 600;

    color: #343a40;
}


.user-role {

    font-size: 11px;

    color: #9aa1a8;
}


.amount {

    color: #198754;

    font-weight: 700;
}


.date-text {

    color: #747d86;

    font-size: 12px;
}


/* =====================================================
   SEARCH
===================================================== */

.transaction-search {

    display: flex;

    align-items: center;

    gap: 7px;

    background: #f8f9fa;

    border: 1px solid #e9ecef;

    border-radius: 8px;

    padding: 7px 10px;

    transition: .2s;
}


.transaction-search:focus-within {

    background: white;

    border-color: #198754;

    box-shadow:
        0 0 0 3px
        rgba(25,135,84,.08);
}


.transaction-search i {

    color: #8b949e;

    font-size: 12px;
}


.transaction-search input {

    width: 155px;

    border: none;

    outline: none;

    background: transparent;

    color: #343a40;

    font-size: 12px;
}


.transaction-search input::placeholder {

    color: #9aa1a8;
}


.transaction-row-hidden {

    display: none !important;
}


/* =====================================================
   EMPTY
===================================================== */

.empty-state {

    padding: 50px;

    text-align: center;
}


.empty-icon {

    width: 60px;

    height: 60px;

    border-radius: 50%;

    background: #f1f3f5;

    display: flex;

    align-items: center;

    justify-content: center;

    margin: auto;

    color: #adb5bd;

    font-size: 25px;
}


/* =====================================================
   FOOTER
===================================================== */

.footer {

    text-align: center;

    padding: 25px;

    color: #000;

    font-size: 12px;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width: 991px) {

    .navbar-menu {

        display: none;

    }

    .main-container {

        padding: 22px;

    }

}


@media(max-width: 576px) {

    .main-container {

        padding: 15px;

    }

    .navbar-custom {

        height: 65px;

    }

    .brand span {

        font-size: 16px;

    }

    .profile-text {

        display: none;

    }

    .chart-area {

        height: 280px;

        padding: 15px;

    }

    .chart-header {

        padding: 17px;

    }

    .transaction-header {

        padding: 17px;

        gap: 12px;

        flex-wrap: wrap;

    }

    .transaction-search {

        width: 100%;

    }

    .transaction-search input {

        width: 100%;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar-custom">

<div class="container-fluid px-4 h-100">

<div class="d-flex align-items-center justify-content-between h-100">


<a href="dashboard.php" class="brand">

    <div class="brand-logo">

        <i class="bi bi-recycle"></i>

    </div>

    <span>
        Bank Sampah Rapa
    </span>

</a>


<div class="navbar-menu d-flex align-items-center">

    <a
        href="dashboard.php"
        class="nav-link-custom active"
    >

        <i class="bi bi-grid me-1"></i>

        Dashboard

    </a>


    <a
        href="users.php"
        class="nav-link-custom"
    >

        <i class="bi bi-people me-1"></i>

        Pengguna

    </a>


    <a
        href="sampah.php"
        class="nav-link-custom"
    >

        <i class="bi bi-recycle me-1"></i>

        Sampah

    </a>


    <a
        href="transaksi.php"
        class="nav-link-custom"
    >

        <i class="bi bi-arrow-left-right me-1"></i>

        Transaksi

    </a>


    <a
        href="tarik.php"
        class="nav-link-custom"
    >

        <i class="bi bi-wallet2 me-1"></i>

        Tarik Saldo

    </a>
    <a
        href="pembelian.php"
        class="nav-link-custom"
    >

        <i class="bi bi-bag-check me-1"></i>

        Pembelian

    </a>

</div>


<div class="dropdown">

<button
    class="profile-button"
    data-bs-toggle="dropdown"
>

    <div class="profile-text text-end">

        <div class="fw-semibold small">

            <?= htmlspecialchars(
                $_SESSION['nama'] ?? 'Admin'
            ); ?>

        </div>

        <small class="text-muted">

            <?= htmlspecialchars(
                $_SESSION['role'] ?? 'admin'
            ); ?>

        </small>

    </div>


    <div class="profile-avatar">

        <i class="bi bi-person-fill"></i>

    </div>


    <i class="bi bi-chevron-down small"></i>

</button>


<ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">

<li>

<a
    class="dropdown-item"
    href="dashboard.php"
>

    <i class="bi bi-grid me-2"></i>

    Dashboard

</a>

</li>


<li>

<hr class="dropdown-divider">

</li>


<li>

<a
    class="dropdown-item text-danger"
    href="../logout.php"
>

    <i class="bi bi-box-arrow-right me-2"></i>

    Logout

</a>

</li>

</ul>

</div>

</div>

</div>

</nav>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="main-container">


<div class="page-title">

    <h1>
        Dashboard
    </h1>

    <p>
        Ringkasan aktivitas Bank Sampah rapaa
    </p>

</div>


<!-- WELCOME -->

<div class="welcome-card">

<div class="welcome-content">

    <div class="welcome-label">

        <i class="bi bi-stars me-1"></i>

        Admin Panel

    </div>


    <h2>

        Halo,
        <?= htmlspecialchars(
            $_SESSION['nama'] ?? 'Admin'
        ); ?>

        👋

    </h2>


    <p>

        Selamat datang kembali.
        Pantau seluruh aktivitas bank sampah
        melalui dashboard ini.

    </p>


    <div class="date-badge">

        <i class="bi bi-calendar3"></i>

        <?= date('d F Y'); ?>

    </div>

</div>

</div>


<!-- =====================================================
     STATISTIK
===================================================== -->

<div class="row g-3 mb-4">


<div class="col-xl-3 col-md-6">

<div class="stat-card">

<div class="stat-top">

<div class="stat-icon green">

    <i class="bi bi-people-fill"></i>

</div>

<div class="stat-arrow">

    <i class="bi bi-arrow-up-right"></i>

</div>

</div>


<div class="stat-label">
    Total Pengguna
</div>


<div class="stat-number">

    <?= number_format($totalUser); ?>

</div>


<div class="stat-description">
    Pengguna terdaftar
</div>

</div>

</div>


<div class="col-xl-3 col-md-6">

<div class="stat-card">

<div class="stat-top">

<div class="stat-icon blue">

    <i class="bi bi-recycle"></i>

</div>

<div class="stat-arrow">

    <i class="bi bi-arrow-up-right"></i>

</div>

</div>


<div class="stat-label">
    Jenis Sampah
</div>


<div class="stat-number">

    <?= number_format($totalSampah); ?>

</div>


<div class="stat-description">
    Jenis sampah tersedia
</div>

</div>

</div>


<div class="col-xl-3 col-md-6">

<div class="stat-card">

<div class="stat-top">

<div class="stat-icon orange">

    <i class="bi bi-receipt"></i>

</div>

<div class="stat-arrow">

    <i class="bi bi-arrow-up-right"></i>

</div>

</div>


<div class="stat-label">
    Total Transaksi
</div>


<div class="stat-number">

    <?= number_format($totalTransaksi); ?>

</div>


<div class="stat-description">
    Seluruh transaksi
</div>

</div>

</div>


<div class="col-xl-3 col-md-6">

<div class="stat-card">

<div class="stat-top">

<div class="stat-icon purple">

    <i class="bi bi-wallet-fill"></i>

</div>

<div class="stat-arrow">

    <i class="bi bi-arrow-up-right"></i>

</div>

</div>


<div class="stat-label">
    Total Saldo
</div>


<div class="stat-number">

    <?= rupiah($totalSaldo); ?>

</div>


<div class="stat-description">
    Saldo seluruh pengguna
</div>

</div>

</div>

</div>


<!-- =====================================================
     GRAFIK BARIS 1
===================================================== -->

<div class="row g-4 mb-4">


<!-- TRANSAKSI -->

<div class="col-lg-8">

<div class="chart-card">

<div class="chart-header">

<div>

<h5>

<i class="bi bi-graph-up-arrow text-success me-2"></i>

Aktivitas Transaksi

</h5>

<small>
Jumlah transaksi 7 hari terakhir
</small>

</div>


<span class="type-badge">

<i class="bi bi-calendar3 me-1"></i>

7 Hari

</span>

</div>


<div class="chart-area">

<canvas id="grafikTransaksi"></canvas>

</div>

</div>

</div>


<!-- PENDAPATAN -->

<div class="col-lg-4">

<div class="chart-card">

<div class="chart-header">

<div>

<h5>

<i class="bi bi-cash-stack text-success me-2"></i>

Pendapatan

</h5>

<small>
Pendapatan 7 hari
</small>

</div>

<span class="type-badge">
Rp
</span>

</div>


<div class="chart-area">

<canvas id="grafikPendapatan"></canvas>

</div>

</div>

</div>

</div>


<!-- =====================================================
     GRAFIK BARIS 2
===================================================== -->

<div class="row g-4 mb-4">


<!-- BERAT SAMPAH -->

<div class="col-lg-7">

<div class="chart-card">

<div class="chart-header">

<div>

<h5>

<i class="bi bi-recycle text-primary me-2"></i>

Sampah Masuk

</h5>

<small>
Berat sampah berdasarkan transaksi
</small>

</div>


<span class="type-badge">
Kg
</span>

</div>


<div class="chart-area">

<canvas id="grafikBerat"></canvas>

</div>

</div>

</div>


<!-- JENIS SAMPAH -->

<div class="col-lg-5">

<div class="chart-card">

<div class="chart-header">

<div>

<h5>

<i class="bi bi-pie-chart-fill text-success me-2"></i>

Jenis Sampah

</h5>

<small>
Distribusi transaksi
</small>

</div>


<span class="type-badge">
TOP 6
</span>

</div>


<div class="chart-area">

<canvas id="grafikJenis"></canvas>

</div>

</div>

</div>

</div>


<!-- =====================================================
     AKSES CEPAT
===================================================== -->

<div class="section-title">

Akses Cepat

</div>


<div class="row g-3 mb-4">


<div class="col-lg-3 col-md-6">

<a
    href="users.php"
    class="quick-card"
>

<div class="quick-icon">

<i class="bi bi-person-plus-fill"></i>

</div>

<div>

<strong>
Kelola Pengguna
</strong>

<small>
Tambah & kelola pengguna
</small>

</div>

</a>

</div>


<div class="col-lg-3 col-md-6">

<a
    href="sampah.php"
    class="quick-card"
>

<div class="quick-icon">

<i class="bi bi-trash3-fill"></i>

</div>

<div>

<strong>
Data Sampah
</strong>

<small>
Kelola jenis sampah
</small>

</div>

</a>

</div>


<div class="col-lg-3 col-md-6">

<a
    href="transaksi.php"
    class="quick-card"
>

<div class="quick-icon">

<i class="bi bi-plus-circle-fill"></i>

</div>

<div>

<strong>
Transaksi Baru
</strong>

<small>
Tambah transaksi
</small>

</div>

</a>

</div>


<div class="col-lg-3 col-md-6">

<a
    href="tarik.php"
    class="quick-card"
>

<div class="quick-icon">

<i class="bi bi-wallet2"></i>

</div>

<div>

<strong>
Tarik Saldo
</strong>

<small>
Kelola penarikan saldo
</small>

</div>

</a>

</div>

</div>


<!-- =====================================================
     TRANSAKSI TERBARU
===================================================== -->

<div class="transaction-card">


<div class="transaction-header">

<div>

<h5>
Transaksi Terbaru
</h5>

<small>
Aktivitas transaksi terakhir
</small>

</div>


<div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">


<div class="transaction-search">

<i class="bi bi-search"></i>

<input
    type="text"
    id="searchTransaksi"
    placeholder="Cari transaksi..."
    autocomplete="off"
>

</div>


<a
    href="transaksi.php"
    class="btn-see"
>

Lihat Semua

<i class="bi bi-arrow-right ms-1"></i>

</a>

</div>

</div>


<div class="table-responsive">

<table class="table">

<thead>

<tr>

<th>#</th>

<th>Pengguna</th>

<th>Jenis Sampah</th>

<th>Berat</th>

<th>Total</th>

<th>Tanggal</th>

</tr>

</thead>


<tbody>

<?php

if (
    $queryTransaksi &&
    mysqli_num_rows($queryTransaksi) > 0
):

$no = 1;

while (
    $row =
    mysqli_fetch_assoc($queryTransaksi)
):

?>

<tr>

<td>

<span class="text-muted">

<?= $no++; ?>

</span>

</td>


<td>

<div class="user-info">

<div class="user-avatar">

<i class="bi bi-person-fill"></i>

</div>


<div>

<div class="user-name">

<?= htmlspecialchars(
    $row['nama'] ?? '-'
); ?>

</div>


<div class="user-role">
Nasabah
</div>

</div>

</div>

</td>


<td>

<span class="type-badge">

<i class="bi bi-recycle me-1"></i>

<?= htmlspecialchars(
    $row['jenis_sampah'] ?? '-'
); ?>

</span>

</td>


<td>

<strong>

<?= htmlspecialchars(
    $row['berat_kg'] ?? '0'
); ?>

</strong>

<span class="text-muted">
Kg
</span>

</td>


<td>

<span class="amount">

<?= rupiah(
    $row['total_rp'] ?? 0
); ?>

</span>

</td>


<td>

<span class="date-text">

<i class="bi bi-calendar3 me-1"></i>

<?= htmlspecialchars(
    $row['tanggal'] ?? '-'
); ?>

</span>

</td>

</tr>

<?php

endwhile;

else:

?>

<tr>

<td
    colspan="6"
    class="p-0"
>

<div class="empty-state">

<div class="empty-icon">

<i class="bi bi-inbox"></i>

</div>


<div class="mt-3">

<strong>
Belum ada transaksi
</strong>

<p class="text-muted small mb-0">

Data transaksi akan
muncul di sini.

</p>

</div>

</div>

</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>


</main>


<footer class="footer">

<strong>
Bank Sampah rafaa
</strong>

</footer>


<!-- BOOTSTRAP JS -->

<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


<script>

/* =====================================================
   DATA PHP
===================================================== */

const labelGrafik =
    <?= json_encode($labelGrafik); ?>;

const dataGrafik =
    <?= json_encode($dataGrafik); ?>;

const labelPendapatan =
    <?= json_encode($labelPendapatan); ?>;

const dataPendapatan =
    <?= json_encode($dataPendapatan); ?>;

const labelBerat =
    <?= json_encode($labelBerat); ?>;

const dataBerat =
    <?= json_encode($dataBerat); ?>;

const labelJenis =
    <?= json_encode($labelJenis); ?>;

const dataJenis =
    <?= json_encode($dataJenis); ?>;


/* =====================================================
   GRAFIK TRANSAKSI
===================================================== */

const ctxTransaksi =
    document
        .getElementById("grafikTransaksi")
        .getContext("2d");


const gradientTransaksi =
    ctxTransaksi.createLinearGradient(
        0,
        0,
        0,
        350
    );


gradientTransaksi.addColorStop(
    0,
    "rgba(25,135,84,.35)"
);


gradientTransaksi.addColorStop(
    1,
    "rgba(25,135,84,0)"
);


new Chart(ctxTransaksi, {

    type: "line",

    data: {

        labels: labelGrafik,

        datasets: [{

            label: "Transaksi",

            data: dataGrafik,

            borderColor: "#198754",

            backgroundColor:
                gradientTransaksi,

            borderWidth: 3,

            fill: true,

            tension: .45,

            pointRadius: 5,

            pointHoverRadius: 8,

            pointBackgroundColor:
                "#198754",

            pointBorderColor:
                "#ffffff",

            pointBorderWidth: 2

        }]

    },


    options: {

        responsive: true,

        maintainAspectRatio: false,

        plugins: {

            legend: {
                display: false
            },

            tooltip: {

                backgroundColor:
                    "#17202a",

                padding: 12,

                callbacks: {

                    label: function(context) {

                        return " " +
                            context.parsed.y +
                            " transaksi";

                    }

                }

            }

        },


        scales: {

            y: {

                beginAtZero: true,

                ticks: {
                    precision: 0
                },

                grid: {
                    color: "#edf0f2"
                }

            },

            x: {

                grid: {
                    display: false
                }

            }

        }

    }

});


/* =====================================================
   GRAFIK PENDAPATAN
===================================================== */

const ctxPendapatan =
    document
        .getElementById("grafikPendapatan")
        .getContext("2d");


new Chart(ctxPendapatan, {

    type: "bar",

    data: {

        labels: labelPendapatan,

        datasets: [{

            label: "Pendapatan",

            data: dataPendapatan,

            backgroundColor:
                "#20c997",

            borderRadius: 8,

            borderSkipped: false

        }]

    },


    options: {

        responsive: true,

        maintainAspectRatio: false,

        plugins: {

            legend: {
                display: false
            },

            tooltip: {

                callbacks: {

                    label: function(context) {

                        return " Rp " +
                            new Intl.NumberFormat(
                                "id-ID"
                            ).format(
                                context.parsed.y
                            );

                    }

                }

            }

        },


        scales: {

            y: {

                beginAtZero: true,

                ticks: {

                    callback:
                        function(value) {

                            return "Rp " +
                                new Intl.NumberFormat(
                                    "id-ID",
                                    {
                                        notation:
                                            "compact"
                                    }
                                ).format(value);

                        }

                },

                grid: {
                    color: "#edf0f2"
                }

            },

            x: {

                grid: {
                    display: false
                }

            }

        }

    }

});


/* =====================================================
   GRAFIK BERAT SAMPAH
===================================================== */

const ctxBerat =
    document
        .getElementById("grafikBerat")
        .getContext("2d");


new Chart(ctxBerat, {

    type: "bar",

    data: {

        labels: labelBerat,

        datasets: [{

            label: "Berat Sampah",

            data: dataBerat,

            backgroundColor:
                "#0d6efd",

            borderRadius: 9,

            borderSkipped: false

        }]

    },


    options: {

        responsive: true,

        maintainAspectRatio: false,

        plugins: {

            legend: {
                display: false
            },

            tooltip: {

                callbacks: {

                    label: function(context) {

                        return " " +
                            context.parsed.y +
                            " Kg";

                    }

                }

            }

        },


        scales: {

            y: {

                beginAtZero: true,

                grid: {
                    color: "#edf0f2"
                }

            },

            x: {

                grid: {
                    display: false
                }

            }

        }

    }

});


/* =====================================================
   GRAFIK JENIS SAMPAH
===================================================== */

const ctxJenis =
    document
        .getElementById("grafikJenis")
        .getContext("2d");


new Chart(ctxJenis, {

    type: "doughnut",

    data: {

        labels: labelJenis,

        datasets: [{

            data: dataJenis,

            backgroundColor: [

                "#198754",

                "#20c997",

                "#0d6efd",

                "#ffc107",

                "#fd7e14",

                "#6f42c1"

            ],

            borderWidth: 3,

            borderColor: "#ffffff",

            hoverOffset: 12

        }]

    },


    options: {

        responsive: true,

        maintainAspectRatio: false,

        cutout: "65%",

        plugins: {

            legend: {

                position: "bottom",

                labels: {

                    padding: 15,

                    usePointStyle: true,

                    font: {
                        size: 11
                    }

                }

            },

            tooltip: {

                callbacks: {

                    label: function(context) {

                        return " " +
                            context.label +
                            ": " +
                            context.parsed +
                            " transaksi";

                    }

                }

            }

        }

    }

});


/* =====================================================
   SEARCH TRANSAKSI
===================================================== */

const search =
    document.getElementById(
        "searchTransaksi"
    );


const table =
    document.querySelector(
        ".transaction-card table"
    );


if (search && table) {

    const rows =
        table.querySelectorAll(
            "tbody tr"
        );


    search.addEventListener(
        "input",
        function () {

            const keyword =
                this.value
                .trim()
                .toLowerCase();


            rows.forEach(function(row) {

                const text =
                    row.textContent
                    .toLowerCase();


                if (
                    !keyword ||
                    text.includes(keyword)
                ) {

                    row.classList.remove(
                        "transaction-row-hidden"
                    );

                } else {

                    row.classList.add(
                        "transaction-row-hidden"
                    );

                }

            });

        }
    );

}

</script>

</body>

</html>