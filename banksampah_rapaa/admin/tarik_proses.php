<?php

session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

include __DIR__ . "/../koneksi.php";
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');


/* =========================================
   AMBIL DATA
========================================= */

$aksi = $_GET['aksi'] ?? '';
$idTarik = (int)($_GET['id'] ?? 0);


if ($idTarik <= 0) {
    header("Location: tarik.php");
    exit;
}


/* =========================================
   SETUJUI PENARIKAN
========================================= */

if ($aksi === 'setujui') {

    mysqli_begin_transaction($conn);

    try {

        /* Ambil data penarikan dan kunci baris */
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_tarik, id_user, nominal, status
             FROM tarik_saldo
             WHERE id_tarik = ?
             FOR UPDATE"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $idTarik
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $tarik = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);


        if (!$tarik) {
            throw new Exception(
                "Data penarikan tidak ditemukan."
            );
        }


        /* Pastikan masih pending */
        if ($tarik['status'] !== 'pending') {
            throw new Exception(
                "Penarikan ini sudah diproses."
            );
        }


        $idUser = (int)$tarik['id_user'];
        $nominal = (float)$tarik['nominal'];


        /* =====================================
           AMBIL SALDO USER
        ===================================== */

        $stmtUser = mysqli_prepare(
            $conn,
            "SELECT saldo
             FROM users
             WHERE id_user = ?
             FOR UPDATE"
        );

        mysqli_stmt_bind_param(
            $stmtUser,
            "i",
            $idUser
        );

        mysqli_stmt_execute($stmtUser);

        $resultUser =
            mysqli_stmt_get_result($stmtUser);

        $user =
            mysqli_fetch_assoc($resultUser);

        mysqli_stmt_close($stmtUser);


        if (!$user) {
            throw new Exception(
                "Data pengguna tidak ditemukan."
            );
        }


        $saldo = (float)$user['saldo'];


        /* =====================================
           CEK SALDO
        ===================================== */

        if ($nominal > $saldo) {
            throw new Exception(
                "Saldo nasabah tidak mencukupi."
            );
        }


        /* =====================================
           KURANGI SALDO
        ===================================== */

        $stmtUpdateUser = mysqli_prepare(
            $conn,
            "UPDATE users
             SET saldo = saldo - ?
             WHERE id_user = ?"
        );

        mysqli_stmt_bind_param(
            $stmtUpdateUser,
            "di",
            $nominal,
            $idUser
        );

        if (!mysqli_stmt_execute($stmtUpdateUser)) {

            mysqli_stmt_close($stmtUpdateUser);

            throw new Exception(
                "Gagal mengurangi saldo."
            );
        }

        mysqli_stmt_close($stmtUpdateUser);


        /* =====================================
           UPDATE STATUS PENARIKAN
        ===================================== */

        $stmtUpdateTarik = mysqli_prepare(
            $conn,
            "UPDATE tarik_saldo
             SET status = 'disetujui'
             WHERE id_tarik = ?
             AND status = 'pending'"
        );

        mysqli_stmt_bind_param(
            $stmtUpdateTarik,
            "i",
            $idTarik
        );

        if (!mysqli_stmt_execute($stmtUpdateTarik)) {

            mysqli_stmt_close($stmtUpdateTarik);

            throw new Exception(
                "Gagal memperbarui status penarikan."
            );
        }

        mysqli_stmt_close($stmtUpdateTarik);


        /* =====================================
           COMMIT
        ===================================== */

        mysqli_commit($conn);


        header(
            "Location: tarik.php?status=disetujui"
        );

        exit;


    } catch (Exception $e) {

        mysqli_rollback($conn);

        header(
            "Location: tarik.php?status=gagal&pesan=" .
            urlencode($e->getMessage())
        );

        exit;
    }
}


/* =========================================
   TOLAK PENARIKAN
========================================= */

elseif ($aksi === 'tolak') {

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE tarik_saldo
         SET status = 'ditolak'
         WHERE id_tarik = ?
         AND status = 'pending'"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $idTarik
    );

    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);


    header(
        "Location: tarik.php?status=ditolak"
    );

    exit;
}


/* =========================================
   AKSI TIDAK VALID
========================================= */

else {

    header("Location: tarik.php");

    exit;
}