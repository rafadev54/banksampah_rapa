<?php

session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

include __DIR__ . "/../koneksi.php";
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');


/* TAMBAH SAMPAH */

if (isset($_POST['tambah'])) {

    $jenis = mysqli_real_escape_string(
        $conn,
        $_POST['jenis_sampah']
    );

    $harga = intval($_POST['harga_per_kg']);

    $stok = floatval($_POST['stok_kg']);

    $query = mysqli_query(
        $conn,
        "INSERT INTO sampah
        (jenis_sampah, harga_per_kg, stok_kg)
        VALUES
        ('$jenis', $harga, $stok)"
    );

    if ($query) {

        header("Location: sampah.php");
        exit;

    } else {

        die("Gagal menambahkan data: " . mysqli_error($conn));

    }
}


/* HAPUS SAMPAH */

if (isset($_GET['hapus'])) {

    $id = intval($_GET['hapus']);

    mysqli_query(
        $conn,
        "DELETE FROM sampah WHERE id_sampah = $id"
    );

    header("Location: sampah.php");
    exit;
}

?>