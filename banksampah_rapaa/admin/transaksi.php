<?php
session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

include __DIR__ . "/../koneksi.php";
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

/* Fungsi Bantuan Rupiah */
if (!function_exists('rupiah')) {
    function rupiah($angka) {
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }
}

/* =========================
   DATA TRANSAKSI
========================= */

$query = mysqli_query($conn, "
    SELECT 
        transaksi.*,
        users.nama,
        users.role,
        sampah.jenis_sampah,
        sampah.harga_per_kg
    FROM transaksi
    LEFT JOIN users 
        ON transaksi.id_user = users.id_user
    LEFT JOIN sampah 
        ON transaksi.id_sampah = sampah.id_sampah
    ORDER BY transaksi.id_transaksi DESC
");

/* =========================
   STATISTIK
========================= */

/* Total transaksi */
$qTotal = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS jumlah FROM transaksi"
);

$totalTransaksi =
    mysqli_fetch_assoc($qTotal)['jumlah'] ?? 0;


/* Total berat */
$qBerat = mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(berat_kg), 0) AS total
     FROM transaksi"
);

$totalBerat =
    mysqli_fetch_assoc($qBerat)['total'] ?? 0;


/* Total uang */
$qUang = mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(total_rp), 0) AS total
     FROM transaksi"
);

$totalUang =
    mysqli_fetch_assoc($qUang)['total'] ?? 0;


/* Transaksi hari ini */
$qHari = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS jumlah
     FROM transaksi
     WHERE DATE(tanggal) = CURDATE()"
);

$transaksiHariIni =
    mysqli_fetch_assoc($qHari)['jumlah'] ?? 0;

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
    Data Transaksi - Bank Sampah rapa
</title>


<!-- Bootstrap -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<!-- Bootstrap Icons -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    rel="stylesheet"
>


<!-- Google Fonts -->

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet"
>


<style>

:root {

    --bg-body: #f8fafc;

    --navbar-bg: #ffffff;

    --text-dark: #0f172a;

    --text-muted: #64748b;

    --card-border: #f1f5f9;

    --table-header: #f8fafc;

}


* {
    box-sizing: border-box;
}


body {

    margin: 0;

    background-color: var(--bg-body);

    color: var(--text-dark);

    font-family: 'Inter', sans-serif;

    font-size: 13px;

}


/* =========================
   TOP NAVBAR
========================= */

.top-navbar {

    background: var(--navbar-bg);

    border-bottom:
        1px solid #e2e8f0;

    padding: 0 40px;

    height: 64px;

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

    font-weight: 700;

    font-size: 15px;

    color: var(--text-dark);

    text-decoration: none;

}


.brand-logo i {

    background: var(--text-dark);

    color: white;

    padding: 6px;

    border-radius: 8px;

    font-size: 14px;

}


.nav-links {

    display: flex;

    align-items: center;

    gap: 8px;

}


.nav-links a {

    color: var(--text-muted);

    text-decoration: none;

    padding: 8px 16px;

    border-radius: 8px;

    font-weight: 500;

    display: flex;

    align-items: center;

    gap: 8px;

    transition: .2s;

}


.nav-links a:hover {

    color: var(--text-dark);

    background: #f1f5f9;

}


.nav-links a.active {

    background: var(--text-dark);

    color: white;

}


.user-profile {

    display: flex;

    align-items: center;

    gap: 12px;

}


.user-info {

    text-align: right;

}


.user-name {

    font-weight: 600;

    font-size: 12px;

    line-height: 1.2;

}


.user-role {

    font-size: 10px;

    color: var(--text-muted);

}


.user-avatar {

    width: 32px;

    height: 32px;

    border-radius: 50%;

    background: #e2e8f0;

    display: flex;

    align-items: center;

    justify-content: center;

    color: var(--text-muted);

}


.btn-logout {

    color: #ef4444;

    padding: 6px;

    border-radius: 6px;

    text-decoration: none;

    transition: .2s;

}


.btn-logout:hover {

    background: #fee2e2;

}


/* =========================
   CONTAINER
========================= */

.main-container {

    max-width: 1140px;

    margin: 32px auto 0;

    padding: 0 20px;

}


/* =========================
   HEADER
========================= */

.page-header {

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    margin-bottom: 24px;

}


.page-title h2 {

    font-size: 22px;

    font-weight: 700;

    margin: 0;

    letter-spacing: -.3px;

}


.page-title p {

    color: var(--text-muted);

    margin: 4px 0 0;

    font-size: 13px;

}


.btn-add {

    background: var(--text-dark);

    color: white;

    border: none;

    padding: 10px 18px;

    border-radius: 8px;

    font-weight: 600;

    font-size: 13px;

    display: flex;

    align-items: center;

    gap: 8px;

}


.btn-add:hover {

    background: #1e293b;

    color: white;

}


/* =========================
   STATISTIK
========================= */

.stat-card {

    background: white;

    border:
        1px solid var(--card-border);

    border-radius: 12px;

    padding: 20px;

    box-shadow:
        0 1px 3px rgba(0,0,0,.02);

    height: 100%;

}


.stat-icon {

    width: 36px;

    height: 36px;

    border-radius: 8px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 16px;

    margin-bottom: 16px;

}


.icon-total {

    background: #f1f5f9;

    color: var(--text-dark);

}


.icon-berat {

    background: #eff6ff;

    color: #2563eb;

}


.icon-nilai {

    background: #fefce8;

    color: #ca8a04;

}


.icon-hari {

    background: #f0fdf4;

    color: #16a34a;

}


.stat-label {

    color: var(--text-muted);

    font-size: 11px;

    margin-bottom: 4px;

    font-weight: 500;

}


.stat-value {

    font-size: 20px;

    font-weight: 700;

    color: var(--text-dark);

    line-height: 1.2;

}


.stat-value small {

    font-size: 12px;

    font-weight: 500;

    color: var(--text-muted);

}


.stat-desc {

    font-size: 11px;

    color: #94a3b8;

    margin-top: 6px;

}


/* =========================
   TABLE CARD
========================= */

.table-card {

    background: white;

    border:
        1px solid var(--card-border);

    border-radius: 12px;

    box-shadow:
        0 1px 3px rgba(0,0,0,.02);

    margin-top: 24px;

    overflow: hidden;

}


.table-header {

    padding: 20px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    border-bottom:
        1px solid var(--card-border);

}


.table-title {

    font-size: 14px;

    font-weight: 700;

}


.table-subtitle {

    font-size: 12px;

    color: var(--text-muted);

    margin-top: 2px;

}


/* =========================
   SEARCH
========================= */

.search-box {

    position: relative;

    width: 220px;

}


.search-box input {

    padding-left: 32px;

    font-size: 12px;

    border-radius: 8px;

    border:
        1px solid #e2e8f0;

    height: 36px;

}


.search-box i {

    position: absolute;

    left: 10px;

    top: 50%;

    transform:
        translateY(-50%);

    color: #94a3b8;

    font-size: 13px;

}


/* =========================
   TABLE
========================= */

.table {

    margin: 0;

}


.table thead th {

    background: var(--table-header);

    color: var(--text-muted);

    font-size: 11px;

    font-weight: 600;

    text-transform: uppercase;

    letter-spacing: .5px;

    padding: 12px 20px;

    border-bottom:
        1px solid #e2e8f0;

}


.table tbody td {

    padding: 16px 20px;

    vertical-align: middle;

    border-bottom:
        1px solid #f1f5f9;

    font-size: 13px;

}


.table tbody tr:last-child td {

    border-bottom: none;

}


.table tbody tr:hover {

    background: #fafbfd;

}


/* =========================
   SAMPAH
========================= */

.badge-sampah {

    background: #f1f5f9;

    color: var(--text-dark);

    padding: 6px 10px;

    border-radius: 6px;

    font-weight: 500;

    display: inline-flex;

    align-items: center;

    gap: 6px;

    font-size: 12px;

}


.badge-sampah i {

    color: #16a34a;

}


/* =========================
   BERAT
========================= */

.weight-pill {

    background: #eff6ff;

    color: #1d4ed8;

    padding: 4px 8px;

    border-radius: 6px;

    font-weight: 600;

    font-size: 11px;

    display: inline-block;

}


/* =========================
   EMPTY
========================= */

.empty-state {

    padding: 40px;

    text-align: center;

    color: var(--text-muted);

}


/* =========================
   FOOTER
========================= */

.footer {

    max-width: 1140px;

    margin: 35px auto 20px;

    padding: 18px 20px;

    text-align: center;

    border-top:
        1px solid #e2e8f0;

    color: #94a3b8;

    font-size: 12px;

}


.footer strong {

    color: #0f172a;

    font-weight: 600;

}


/* =========================
   MODAL
========================= */

.modal-content {

    border: none;

    border-radius: 12px;

    overflow: hidden;

}


.modal-header {

    background: #ffffff;

    padding: 20px;

}


.modal-body {

    padding: 20px;

}


.modal-footer {

    padding: 15px 20px;

}


/* =========================
   RESPONSIVE
========================= */

@media(max-width: 1000px) {

    .nav-links {

        gap: 2px;

    }

    .nav-links a {

        padding: 8px 8px;

        font-size: 11px;

    }

    .top-navbar {

        padding: 0 20px;

    }

}


@media(max-width: 768px) {

    .top-navbar {

        height: auto;

        min-height: 64px;

        padding: 12px 15px;

        flex-wrap: wrap;

        gap: 10px;

    }

    .nav-links {

        order: 3;

        width: 100%;

        overflow-x: auto;

        padding-bottom: 3px;

    }

    .nav-links a {

        white-space: nowrap;

    }

    .user-info {

        display: none;

    }

    .main-container {

        padding: 0 15px;

        margin-top: 20px;

    }

    .page-header {

        flex-direction: column;

        gap: 15px;

    }

    .btn-add {

        width: 100%;

        justify-content: center;

    }

    .table-header {

        flex-direction: column;

        align-items: stretch;

        gap: 15px;

    }

    .search-box {

        width: 100%;

    }

}


/* =========================
   MOBILE TABLE
========================= */

@media(max-width: 600px) {

    .table {

        min-width: 800px;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     TOP NAVBAR
===================================================== -->

<div class="top-navbar">


    <!-- LOGO -->

    <a
        href="dashboard.php"
        class="brand-logo"
    >

        <i class="bi bi-recycle"></i>

        <span>
            Bank Sampah rapa
        </span>

    </a>


    <!-- MENU -->

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


        <a
            href="transaksi.php"
            class="active"
        >

            <i class="bi bi-arrow-left-right"></i>

            Transaksi

        </a>


        <a href="tarik.php">

            <i class="bi bi-wallet2"></i>

            Tarik Saldo

        </a>
        <a href="pembelian.php">

            <i class="bi bi-bag-check"></i>

            Pembelian

        </a>


    </div>


    <!-- USER -->

    <div class="user-profile">


        <div class="user-info">

            <div class="user-name">

                <?= htmlspecialchars(
                    $_SESSION['nama'] ?? 'Admin'
                ); ?>

            </div>


            <div class="user-role">

                <?= ucfirst(
                    htmlspecialchars(
                        $_SESSION['role'] ?? 'admin'
                    )
                ); ?>

            </div>

        </div>


        <div class="user-avatar">

            <i class="bi bi-person"></i>

        </div>


        <a
            href="../logout.php"
            class="btn-logout"
            title="Logout"
        >

            <i class="bi bi-box-arrow-right"></i>

        </a>

    </div>

</div>



<!-- =====================================================
     MAIN
===================================================== -->

<div class="main-container">


    <!-- HEADER -->

    <div class="page-header">


        <div class="page-title">

            <h2>
                Data Transaksi
            </h2>

            <p>
                Kelola riwayat transaksi penyetoran sampah nasabah.
            </p>

        </div>


        <button
            type="button"
            class="btn btn-add"
            data-bs-toggle="modal"
            data-bs-target="#modalTransaksi"
        >

            <i class="bi bi-plus-lg"></i>

            Tambah Transaksi

        </button>

    </div>



    <!-- =================================================
         STATISTIK
    ================================================= -->

    <div class="row g-3">


        <!-- TOTAL TRANSAKSI -->

        <div class="col-md-3">

            <div class="stat-card">

                <div class="stat-icon icon-total">

                    <i class="bi bi-arrow-left-right"></i>

                </div>


                <div class="stat-label">

                    Total Transaksi

                </div>


                <div class="stat-value">

                    <?= number_format(
                        $totalTransaksi,
                        0,
                        ',',
                        '.'
                    ); ?>

                </div>


                <div class="stat-desc">

                    Seluruh transaksi terdata

                </div>

            </div>

        </div>



        <!-- TOTAL BERAT -->

        <div class="col-md-3">

            <div class="stat-card">

                <div class="stat-icon icon-berat">

                    <i class="bi bi-box-seam"></i>

                </div>


                <div class="stat-label">

                    Total Berat

                </div>


                <div class="stat-value">

                    <?= number_format(
                        $totalBerat,
                        2,
                        ',',
                        '.'
                    ); ?>

                    <small>
                        Kg
                    </small>

                </div>


                <div class="stat-desc">

                    Seluruh akumulasi berat

                </div>

            </div>

        </div>



        <!-- TOTAL NILAI -->

        <div class="col-md-3">

            <div class="stat-card">

                <div class="stat-icon icon-nilai">

                    <i class="bi bi-wallet2"></i>

                </div>


                <div class="stat-label">

                    Total Nilai

                </div>


                <div class="stat-value">

                    <?= rupiah(
                        $totalUang
                    ); ?>

                </div>


                <div class="stat-desc">

                    Estimasi total pembayaran

                </div>

            </div>

        </div>



        <!-- HARI INI -->

        <div class="col-md-3">

            <div class="stat-card">

                <div class="stat-icon icon-hari">

                    <i class="bi bi-calendar-check"></i>

                </div>


                <div class="stat-label">

                    Hari Ini

                </div>


                <div class="stat-value">

                    <?= number_format(
                        $transaksiHariIni,
                        0,
                        ',',
                        '.'
                    ); ?>

                </div>


                <div class="stat-desc">

                    Penyetoran hari ini

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

                    Daftar Transaksi

                </div>


                <div class="table-subtitle">

                    Data riwayat transaksi penyetoran sampah

                </div>

            </div>


            <!-- SEARCH -->

            <div class="search-box">

                <i class="bi bi-search"></i>

                <input
                    type="text"
                    id="searchInput"
                    class="form-control"
                    placeholder="Cari transaksi..."
                >

            </div>


        </div>



        <!-- TABLE -->

        <div class="table-responsive">


            <table
                class="table"
                id="transaksiTable"
            >


                <thead>

                    <tr>

                        <th width="60">
                            NO
                        </th>

                        <th>
                            NASABAH
                        </th>

                        <th>
                            JENIS SAMPAH
                        </th>

                        <th>
                            BERAT
                        </th>

                        <th>
                            TOTAL (RP)
                        </th>

                        <th>
                            TANGGAL
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php

                $no = 1;

                if (
                    mysqli_num_rows($query)
                    > 0
                ):

                    while (
                        $row =
                        mysqli_fetch_assoc($query)
                    ):

                ?>


                <tr>


                    <!-- NO -->

                    <td class="text-muted">

                        <?= $no++; ?>

                    </td>



                    <!-- NASABAH -->

                    <td>

                        <div
                            class="fw-semibold text-dark"
                        >

                            <?= htmlspecialchars(
                                $row['nama']
                                ?? '-'
                            ); ?>

                        </div>


                        <div
                            class="text-muted"
                            style="font-size:11px;"
                        >

                            <?= ucfirst(
                                htmlspecialchars(
                                    $row['role']
                                    ?? ''
                                )
                            ); ?>

                        </div>

                    </td>



                    <!-- SAMPAH -->

                    <td>

                        <span
                            class="badge-sampah"
                        >

                            <i class="bi bi-recycle"></i>

                            <?= htmlspecialchars(
                                $row['jenis_sampah']
                                ?? 'Dihapus'
                            ); ?>

                        </span>

                    </td>



                    <!-- BERAT -->

                    <td>

                        <span
                            class="weight-pill"
                        >

                            <?= number_format(
                                $row['berat_kg'],
                                2,
                                ',',
                                '.'
                            ); ?>

                            Kg

                        </span>

                    </td>



                    <!-- TOTAL -->

                    <td
                        class="fw-bold text-dark"
                    >

                        <?= rupiah(
                            $row['total_rp']
                        ); ?>

                    </td>



                    <!-- TANGGAL -->

                    <td class="text-muted">

                        <?= date(
                            'd-m-Y',
                            strtotime(
                                $row['tanggal']
                            )
                        ); ?>

                    </td>


                </tr>


                <?php

                    endwhile;

                else:

                ?>


                <tr>

                    <td colspan="6">

                        <div class="empty-state">

                            <i
                                class="bi bi-inbox fs-2 mb-2 d-block"
                            ></i>

                            Belum ada data transaksi.

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
     FOOTER
===================================================== -->

<footer class="footer">

    <strong>
        Bank Sampah rapa
    </strong>

</footer>



<!-- =====================================================
     MODAL TAMBAH TRANSAKSI
===================================================== -->

<div
    class="modal fade"
    id="modalTransaksi"
    tabindex="-1"
    aria-hidden="true"
>


    <div
        class="modal-dialog modal-dialog-centered"
    >

        <div class="modal-content shadow-sm">


            <form
                action="transaksi_proses.php"
                method="POST"
            >


                <!-- HEADER -->

                <div
                    class="modal-header"
                >

                    <h5
                        class="modal-title fw-bold"
                        style="font-size:16px;"
                    >

                        <i class="bi bi-plus-circle me-2"></i>

                        Tambah Transaksi

                    </h5>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>

                </div>



                <!-- BODY -->

                <div
                    class="modal-body"
                >


                    <!-- NASABAH -->

                    <div class="mb-3">

                        <label
                            class="form-label fw-semibold"
                        >

                            Nasabah

                        </label>


                        <select
                            name="id_user"
                            class="form-select"
                            required
                        >

                            <option value="">

                                -- Pilih Nasabah --

                            </option>


                            <?php

                            $users = mysqli_query(
                                $conn,
                                "SELECT *
                                 FROM users
                                 WHERE role IN ('siswa', 'guru')
                                 ORDER BY nama ASC"
                            );


                            while (
                                $user =
                                mysqli_fetch_assoc($users)
                            ):

                            ?>


                            <option
                                value="<?= $user['id_user']; ?>"
                            >

                                <?= htmlspecialchars(
                                    $user['nama']
                                ); ?>

                                -

                                <?= ucfirst(
                                    $user['role']
                                ); ?>

                            </option>


                            <?php endwhile; ?>


                        </select>

                    </div>



                    <!-- JENIS SAMPAH -->

                    <div class="mb-3">

                        <label
                            class="form-label fw-semibold"
                        >

                            Jenis Sampah

                        </label>


                        <select
                            name="id_sampah"
                            class="form-select"
                            required
                        >

                            <option value="">

                                -- Pilih Jenis Sampah --

                            </option>


                            <?php

                            $sampah = mysqli_query(
                                $conn,
                                "SELECT *
                                 FROM sampah
                                 ORDER BY jenis_sampah ASC"
                            );


                            while (
                                $s =
                                mysqli_fetch_assoc($sampah)
                            ):

                            ?>


                            <option
                                value="<?= $s['id_sampah']; ?>"
                            >

                                <?= htmlspecialchars(
                                    $s['jenis_sampah']
                                ); ?>

                                -

                                <?= rupiah(
                                    $s['harga_per_kg']
                                ); ?>/Kg

                            </option>


                            <?php endwhile; ?>


                        </select>

                    </div>



                    <!-- BERAT -->

                    <div class="mb-3">

                        <label
                            class="form-label fw-semibold"
                        >

                            Berat Sampah (Kg)

                        </label>


                        <div class="input-group">

                            <input
                                type="number"
                                name="berat_kg"
                                class="form-control"
                                placeholder="0.00"
                                min="0.01"
                                step="0.01"
                                required
                            >


                            <span class="input-group-text">

                                Kg

                            </span>

                        </div>

                    </div>



                    <!-- TANGGAL -->

                    <div class="mb-3">

                        <label
                            class="form-label fw-semibold"
                        >

                            Tanggal

                        </label>


                        <input
                            type="date"
                            name="tanggal"
                            class="form-control"
                            value="<?= date('Y-m-d'); ?>"
                            required
                        >

                    </div>


                </div>



                <!-- FOOTER MODAL -->

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >

                        Batal

                    </button>


                    <button
                        type="submit"
                        name="simpan"
                        class="btn btn-dark"
                    >

                        <i class="bi bi-save me-1"></i>

                        Simpan Transaksi

                    </button>

                </div>


            </form>

        </div>

    </div>

</div>



<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>



<!-- SEARCH -->

<script>

const searchInput =
    document.getElementById(
        'searchInput'
    );


searchInput.addEventListener(
    'keyup',
    function() {

        let filter =
            this.value
            .toLowerCase()
            .trim();


        let rows =
            document.querySelectorAll(
                '#transaksiTable tbody tr'
            );


        rows.forEach(
            function(row) {

                let text =
                    row.textContent
                    .toLowerCase();


                if (
                    text.includes(filter)
                ) {

                    row.style.display =
                        '';

                } else {

                    row.style.display =
                        'none';

                }

            }
        );

    }
);

</script>


</body>

</html>