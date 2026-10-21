<?php
session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

include __DIR__ . "/../koneksi.php";
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$query = mysqli_query(
    $conn,
    "SELECT * FROM users ORDER BY id_user DESC"
);


/* ================================
   HITUNG DATA
================================ */

$totalPengguna = mysqli_num_rows($query);

$querySiswa = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'siswa'"
);

$dataSiswa = mysqli_fetch_assoc($querySiswa);
$totalSiswa = $dataSiswa['total'];


$queryGuru = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'guru'"
);

$dataGuru = mysqli_fetch_assoc($queryGuru);
$totalGuru = $dataGuru['total'];


$queryPengepul = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'pengepul'"
);

$dataPengepul = mysqli_fetch_assoc($queryPengepul);
$totalPengepul = $dataPengepul['total'];

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
        Pengguna - Bank Sampah rapa
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


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            background: #f7f8fc;

            font-family:
                "Segoe UI",
                Arial,
                sans-serif;

            color: #202733;

        }


        /* ==========================================
           NAVBAR
        ========================================== */

        .topbar {

            height: 70px;

            background: #ffffff;

            border-bottom:
                1px solid #e8ebef;

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


        .menu-link:hover {

            color: #17202a;

            background: #f0f2f5;

        }


        .menu-link.active {

            background: #17202a;

            color: white;

        }


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


        /* ==========================================
           CONTENT
        ========================================== */

        .container-main {

            max-width: 1400px;

            margin: auto;

            padding: 32px;

        }


        .page-heading {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 25px;

        }


        .page-heading h1 {

            font-size: 28px;

            font-weight: 700;

            margin: 0 0 5px;

        }


        .page-heading p {

            margin: 0;

            color: #89919c;

            font-size: 14px;

        }


        .btn-add {

            background: #17202a;

            border: none;

            color: white;

            padding: 11px 18px;

            border-radius: 9px;

            font-size: 13px;

            font-weight: 600;

        }


        .btn-add:hover {

            background: #303b48;

            color: white;

        }


        /* ==========================================
           STATISTIC
        ========================================== */

        .summary-card {

            background: white;

            border: 1px solid #e7e9ed;

            border-radius: 14px;

            padding: 19px;

            height: 100%;

        }


        .summary-icon {

            width: 42px;

            height: 42px;

            border-radius: 10px;

            display: flex;

            align-items: center;

            justify-content: center;

            margin-bottom: 15px;

            font-size: 18px;

        }


        .summary-total {

            background: #edf0f4;

            color: #17202a;

        }


        .summary-siswa {

            background: #e9f2ff;

            color: #0d6efd;

        }


        .summary-guru {

            background: #fff3dc;

            color: #d58b00;

        }


        .summary-pengepul {

            background: #e5f6ed;

            color: #198754;

        }


        .summary-title {

            font-size: 12px;

            color: #89919c;

            margin-bottom: 3px;

        }


        .summary-number {

            font-size: 24px;

            font-weight: 700;

        }


        /* ==========================================
           TABLE CONTAINER
        ========================================== */

        .users-card {

            margin-top: 25px;

            background: white;

            border: 1px solid #e5e8ec;

            border-radius: 16px;

            overflow: hidden;

        }


        .users-header {

            padding: 21px 23px;

            border-bottom:
                1px solid #e9ebee;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

        }


        .users-header h5 {

            font-size: 17px;

            font-weight: 700;

            margin: 0;

        }


        .users-header small {

            color: #929aa4;

        }


        .tools {

            display: flex;

            gap: 8px;

        }


        .search-box {

            width: 230px;

            position: relative;

        }


        .search-box i {

            position: absolute;

            left: 12px;

            top: 10px;

            color: #9aa2ab;

        }


        .search-box input {

            width: 100%;

            height: 38px;

            border: 1px solid #dfe3e8;

            border-radius: 8px;

            padding-left: 35px;

            font-size: 12px;

            outline: none;

        }


        .search-box input:focus {

            border-color: #17202a;

        }


        .filter-select {

            height: 38px;

            border: 1px solid #dfe3e8;

            border-radius: 8px;

            padding: 0 12px;

            font-size: 12px;

            color: #555e68;

            outline: none;

        }


        /* ==========================================
           TABLE
        ========================================== */

        .table {

            margin: 0;

        }


        .table thead th {

            background: #fafbfc;

            border-bottom:
                1px solid #e7e9ec;

            color: #8a929d;

            font-size: 10px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: .4px;

            padding: 14px 20px;

        }


        .table tbody td {

            padding: 15px 20px;

            border-color: #eef0f2;

            font-size: 13px;

            vertical-align: middle;

        }


        .table tbody tr:hover {

            background: #fafbfd;

        }


        .number {

            color: #9ba3ad;

            font-size: 12px;

        }


        .person {

            display: flex;

            align-items: center;

            gap: 11px;

        }


        .person-avatar {

            width: 38px;

            height: 38px;

            border-radius: 10px;

            background: #edf0f4;

            color: #17202a;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 16px;

        }


        .person-name {

            font-weight: 600;

            color: #252c35;

        }


        .person-id {

            font-size: 11px;

            color: #9aa1a9;

            margin-top: 2px;

        }


        .username {

            color: #626b76;

            font-family: monospace;

            font-size: 12px;

        }


        /* ==========================================
           ROLE BADGE
        ========================================== */

        .role {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 5px 9px;

            border-radius: 6px;

            font-size: 10px;

            font-weight: 700;

        }


        .role-admin {

            background: #fde9ec;

            color: #dc3545;

        }


        .role-siswa {

            background: #e7f0ff;

            color: #0d6efd;

        }


        .role-guru {

            background: #fff2d7;

            color: #b77900;

        }


        .role-pengepul {

            background: #e4f5eb;

            color: #198754;

        }


        .role-default {

            background: #edf0f2;

            color: #6c757d;

        }


        /* ==========================================
           SALDO
        ========================================== */

        .saldo {

            font-weight: 700;

            color: #198754;

        }


        /* ==========================================
           ACTION
        ========================================== */

        .action-btn {

            width: 34px;

            height: 34px;

            border-radius: 8px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border: none;

            text-decoration: none;

            font-size: 14px;

        }


        .delete-btn {

            background: #fff0f1;

            color: #dc3545;

        }


        .delete-btn:hover {

            background: #dc3545;

            color: white;

        }


        /* ==========================================
           MODAL
        ========================================== */

        .modal-content {

            border: none;

            border-radius: 15px;

            overflow: hidden;

        }


        .modal-header {

            background: #17202a;

            color: white;

            padding: 18px 22px;

            border: none;

        }


        .modal-title {

            font-size: 17px;

            font-weight: 600;

        }


        .modal-header .btn-close {

            filter: invert(1);

        }


        .modal-body {

            padding: 23px;

        }


        .form-label {

            font-size: 13px;

            font-weight: 600;

        }


        .form-control,
        .form-select {

            border-radius: 8px;

            font-size: 13px;

            padding: 10px 12px;

        }


        .form-control:focus,
        .form-select:focus {

            border-color: #17202a;

            box-shadow:
                0 0 0 .2rem
                rgba(23,32,42,.08);

        }


        /* ==========================================
           FOOTER
        ========================================== */

        .footer {

            max-width: 1400px;

            margin: 0 auto;

            padding: 25px 32px;

            text-align: center;

            color: #89919c;

            font-size: 12px;

        }


        .footer strong {

            color: #17202a;

            font-size: 13px;

        }


        /* ==========================================
           MOBILE
        ========================================== */

        @media(max-width: 900px) {

            .menu-link {

                display: none;

            }

            .container-main {

                padding: 20px;

            }

            .users-header {

                align-items: stretch;

                flex-direction: column;

            }

            .tools {

                width: 100%;

            }

            .search-box {

                width: 100%;

            }

        }


        @media(max-width: 600px) {

            .container-main {

                padding: 15px;

            }

            .page-heading {

                align-items: flex-start;

                flex-direction: column;

                gap: 15px;

            }

            .page-heading .btn-add {

                width: 100%;

            }

            .user-area .user-name {

                display: none;

            }

            .table {

                min-width: 750px;

            }

            .footer {

                padding: 20px 15px;

            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="topbar">

    <div class="container-fluid px-4 h-100">

        <div class="d-flex align-items-center justify-content-between h-100">


            <!-- LOGO -->

            <a
                href="dashboard.php"
                class="brand"
            >

                <div class="brand-icon">

                    <i class="bi bi-recycle"></i>

                </div>

                <span>
                    Bank Sampah rapa
                </span>

            </a>


            <!-- MENU -->

            <div
                class="d-flex align-items-center"
            >

                <a
                    href="dashboard.php"
                    class="menu-link"
                >

                    <i class="bi bi-grid me-1"></i>

                    Dashboard

                </a>


                <a
                    href="users.php"
                    class="menu-link active"
                >

                    <i class="bi bi-people me-1"></i>

                    Pengguna

                </a>


                <a
                    href="sampah.php"
                    class="menu-link"
                >

                    <i class="bi bi-recycle me-1"></i>

                    Sampah

                </a>


                <a
                    href="transaksi.php"
                    class="menu-link"
                >

                    <i class="bi bi-arrow-left-right me-1"></i>

                    Transaksi

                </a>


                <a
                    href="tarik.php"
                    class="menu-link"
                >

                    <i class="bi bi-wallet2 me-1"></i>

                    Tarik Saldo

                </a>
                <a
                    href="pembelian.php"
                    class="menu-link"
                >

                    <i class="bi bi-bag-check me-1"></i>

                    Pembelian

                </a>

            </div>


            <!-- USER -->

            <div class="user-area">

                <div class="text-end user-name">

                    <div class="fw-semibold small">

                        <?= htmlspecialchars(
                            $_SESSION['nama']
                        ); ?>

                    </div>

                    <div
                        class="text-muted"
                        style="font-size:10px"
                    >

                        <?= htmlspecialchars(
                            $_SESSION['role']
                        ); ?>

                    </div>

                </div>


                <div class="avatar">

                    <i class="bi bi-person-fill"></i>

                </div>


                <a
                    href="../logout.php"
                    class="text-danger ms-1"
                    title="Logout"
                >

                    <i class="bi bi-box-arrow-right"></i>

                </a>

            </div>

        </div>

    </div>

</nav>



<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<main class="container-main">


    <!-- HEADER -->

    <div class="page-heading">

        <div>

            <h1>
                Data Pengguna
            </h1>

            <p>
                Kelola akun dan informasi pengguna Bank Sampah rapa.
            </p>

        </div>


        <button
            class="btn btn-add"
            data-bs-toggle="modal"
            data-bs-target="#modalTambah"
        >

            <i class="bi bi-person-plus me-1"></i>

            Tambah Pengguna

        </button>

    </div>



    <!-- =================================================
         SUMMARY
    ================================================== -->

    <div class="row g-3">


        <!-- TOTAL -->

        <div class="col-xl-3 col-md-6">

            <div class="summary-card">

                <div class="summary-icon summary-total">

                    <i class="bi bi-people-fill"></i>

                </div>

                <div class="summary-title">
                    Semua Pengguna
                </div>

                <div class="summary-number">

                    <?= $totalPengguna; ?>

                </div>

            </div>

        </div>



        <!-- SISWA -->

        <div class="col-xl-3 col-md-6">

            <div class="summary-card">

                <div class="summary-icon summary-siswa">

                    <i class="bi bi-mortarboard-fill"></i>

                </div>

                <div class="summary-title">
                    Siswa
                </div>

                <div class="summary-number">

                    <?= $totalSiswa; ?>

                </div>

            </div>

        </div>



        <!-- GURU -->

        <div class="col-xl-3 col-md-6">

            <div class="summary-card">

                <div class="summary-icon summary-guru">

                    <i class="bi bi-person-workspace"></i>

                </div>

                <div class="summary-title">
                    Guru
                </div>

                <div class="summary-number">

                    <?= $totalGuru; ?>

                </div>

            </div>

        </div>



        <!-- PENGEPUL -->

        <div class="col-xl-3 col-md-6">

            <div class="summary-card">

                <div class="summary-icon summary-pengepul">

                    <i class="bi bi-truck"></i>

                </div>

                <div class="summary-title">
                    Pengepul
                </div>

                <div class="summary-number">

                    <?= $totalPengepul; ?>

                </div>

            </div>

        </div>

    </div>



    <!-- =================================================
         TABLE
    ================================================== -->

    <div class="users-card">


        <!-- HEADER TABLE -->

        <div class="users-header">

            <div>

                <h5>
                    Daftar Pengguna
                </h5>

                <small>
                    Data seluruh akun yang terdaftar
                </small>

            </div>


            <div class="tools">


                <!-- SEARCH -->

                <div class="search-box">

                    <i class="bi bi-search"></i>

                    <input
                        type="text"
                        id="searchInput"
                        placeholder="Cari pengguna..."
                    >

                </div>


                <!-- FILTER -->

                <select
                    id="roleFilter"
                    class="filter-select"
                >

                    <option value="all">
                        Semua Role
                    </option>

                    <option value="admin">
                        Admin
                    </option>

                    <option value="siswa">
                        Siswa
                    </option>

                    <option value="guru">
                        Guru
                    </option>

                    <option value="pengepul">
                        Pengepul
                    </option>

                </select>

            </div>

        </div>



        <!-- TABLE -->

        <div class="table-responsive">

            <table
                class="table align-middle"
                id="userTable"
            >

                <thead>

                    <tr>

                        <th width="60">
                            No
                        </th>

                        <th>
                            Pengguna
                        </th>

                        <th>
                            Username
                        </th>

                        <th>
                            Role
                        </th>

                        <th>
                            Saldo
                        </th>

                        <th width="80">
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php

                $no = 1;

                while (
                    $data =
                    mysqli_fetch_assoc($query)
                ):

                    $role =
                        strtolower(
                            $data['role']
                        );


                    $roleClass =
                        "role-default";


                    if ($role == "admin") {

                        $roleClass =
                            "role-admin";

                    } elseif ($role == "siswa") {

                        $roleClass =
                            "role-siswa";

                    } elseif ($role == "guru") {

                        $roleClass =
                            "role-guru";

                    } elseif ($role == "pengepul") {

                        $roleClass =
                            "role-pengepul";

                    }

                ?>


                    <tr
                        data-role="<?= $role; ?>"
                    >


                        <!-- NO -->

                        <td>

                            <span class="number">

                                <?= $no++; ?>

                            </span>

                        </td>



                        <!-- NAMA -->

                        <td>

                            <div class="person">

                                <div class="person-avatar">

                                    <i class="bi bi-person-fill"></i>

                                </div>


                                <div>

                                    <div class="person-name">

                                        <?= htmlspecialchars(
                                            $data['nama']
                                        ); ?>

                                    </div>

                                    <div class="person-id">

                                        ID #<?= $data['id_user']; ?>

                                    </div>

                                </div>

                            </div>

                        </td>



                        <!-- USERNAME -->

                        <td>

                            <span class="username">

                                @<?= htmlspecialchars(
                                    $data['usernama']
                                ); ?>

                            </span>

                        </td>



                        <!-- ROLE -->

                        <td>

                            <span
                                class="role <?= $roleClass; ?>"
                            >

                                <?php if ($role == "admin"): ?>

                                    <i class="bi bi-shield-check"></i>

                                <?php elseif ($role == "siswa"): ?>

                                    <i class="bi bi-mortarboard"></i>

                                <?php elseif ($role == "guru"): ?>

                                    <i class="bi bi-person-workspace"></i>

                                <?php elseif ($role == "pengepul"): ?>

                                    <i class="bi bi-truck"></i>

                                <?php else: ?>

                                    <i class="bi bi-person"></i>

                                <?php endif; ?>


                                <?= ucfirst($role); ?>

                            </span>

                        </td>



                        <!-- SALDO -->

                        <td>

                            <span class="saldo">

                                <?= rupiah(
                                    $data['saldo']
                                ); ?>

                            </span>

                        </td>



                        <!-- AKSI -->

                        <td>

                            <?php
                            if (
                                $data['id_user']
                                !=
                                $_SESSION['id_user']
                            ):
                            ?>

                                <a
                                    href="users_proses.php?hapus=<?= $data['id_user']; ?>"
                                    class="action-btn delete-btn"
                                    title="Hapus"
                                    onclick="
                                        return confirm(
                                            'Yakin ingin menghapus pengguna ini?'
                                        );
                                    "
                                >

                                    <i class="bi bi-trash3"></i>

                                </a>

                            <?php else: ?>

                                <span
                                    class="text-muted"
                                    title="Akun yang sedang digunakan"
                                >

                                    <i class="bi bi-lock"></i>

                                </span>

                            <?php endif; ?>

                        </td>


                    </tr>


                <?php endwhile; ?>


                </tbody>

            </table>

        </div>

    </div>

</main>


<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="footer">

    <strong>
        Bank Sampah rapa
    </strong>
    
</footer>



<!-- =====================================================
     MODAL TAMBAH PENGGUNA
===================================================== -->

<div
    class="modal fade"
    id="modalTambah"
    tabindex="-1"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <form
                action="users_proses.php"
                method="POST"
            >


                <!-- HEADER -->

                <div class="modal-header">

                    <h5 class="modal-title">

                        <i class="bi bi-person-plus me-2"></i>

                        Tambah Pengguna

                    </h5>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>



                <!-- BODY -->

                <div class="modal-body">


                    <!-- NAMA -->

                    <div class="mb-3">

                        <label class="form-label">

                            Nama Lengkap

                        </label>

                        <input
                            type="text"
                            name="nama"
                            class="form-control"
                            placeholder="Masukkan nama lengkap"
                            required
                        >

                    </div>



                    <!-- USERNAME -->

                    <div class="mb-3">

                        <label class="form-label">

                            Username

                        </label>

                        <input
                            type="text"
                            name="usernama"
                            class="form-control"
                            placeholder="Contoh: siswa01"
                            required
                        >

                    </div>



                    <!-- PASSWORD -->

                    <div class="mb-3">

                        <label class="form-label">

                            Password

                        </label>

                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            placeholder="Masukkan password"
                            required
                        >

                    </div>



                    <!-- ROLE -->

                    <div class="mb-2">

                        <label class="form-label">

                            Role Pengguna

                        </label>

                        <select
                            name="role"
                            class="form-select"
                            required
                        >

                            <option value="">
                                -- Pilih Role --
                            </option>

                            <option value="siswa">
                                Siswa
                            </option>

                            <option value="guru">
                                Guru
                            </option>

                            <option value="pengepul">
                                Pengepul
                            </option>

                            <option value="admin">
                                Admin
                            </option>

                        </select>

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
                        name="tambah"
                        class="btn btn-dark"
                    >

                        <i class="bi bi-check-lg me-1"></i>

                        Simpan Pengguna

                    </button>

                </div>


            </form>

        </div>

    </div>

</div>



<!-- =====================================================
     JAVASCRIPT SEARCH + FILTER
===================================================== -->

<script>

const searchInput =
    document.getElementById("searchInput");

const roleFilter =
    document.getElementById("roleFilter");

const rows =
    document.querySelectorAll(
        "#userTable tbody tr"
    );


function filterUsers() {

    const keyword =
        searchInput.value
        .toLowerCase()
        .trim();

    const role =
        roleFilter.value;


    rows.forEach(function(row) {

        const text =
            row.innerText.toLowerCase();

        const rowRole =
            row.getAttribute("data-role");


        const cocokSearch =
            text.includes(keyword);


        const cocokRole =
            role === "all"
            ||
            rowRole === role;


        if (
            cocokSearch &&
            cocokRole
        ) {

            row.style.display = "";

        } else {

            row.style.display = "none";

        }

    });

}


searchInput.addEventListener(
    "keyup",
    filterUsers
);


roleFilter.addEventListener(
    "change",
    filterUsers
);

</script>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>