<?php

session_start();

include __DIR__ . "/../koneksi.php";
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');


if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    flash_set('danger', 'Permintaan tidak valid. Silakan coba lagi.');
    header('Location: pembelian.php');
    exit;
}

$aksi     = $_POST['aksi'] ?? '';
$idBeli   = (int)($_POST['id'] ?? 0);

if ($idBeli <= 0 || !in_array($aksi, ['setujui', 'tolak'], true)) {
    header('Location: pembelian.php');
    exit;
}


mysqli_begin_transaction($conn);

try {

    /* kunci baris pembelian */
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id_beli, id_sampah, berat_kg, status
         FROM pembelian WHERE id_beli = ? FOR UPDATE"
    );
    mysqli_stmt_bind_param($stmt, "i", $idBeli);
    mysqli_stmt_execute($stmt);
    $beli = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$beli) {
        throw new Exception('Data pembelian tidak ditemukan.');
    }

    if ($beli['status'] !== 'pending') {
        throw new Exception('Pembelian ini sudah diproses.');
    }


    if ($aksi === 'setujui') {

        $berat = (float)$beli['berat_kg'];

        /* kunci baris sampah, pastikan stok cukup */
        $stmt = mysqli_prepare(
            $conn,
            "SELECT stok_kg, jenis_sampah FROM sampah WHERE id_sampah = ? FOR UPDATE"
        );
        $idSampah = (int)$beli['id_sampah'];
        mysqli_stmt_bind_param($stmt, "i", $idSampah);
        mysqli_stmt_execute($stmt);
        $sampah = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$sampah) {
            throw new Exception('Jenis sampah sudah tidak ada.');
        }

        if ($berat > (float)$sampah['stok_kg']) {
            throw new Exception(
                'Stok ' . $sampah['jenis_sampah'] . ' tidak mencukupi (tersedia ' .
                rtrim(rtrim(number_format((float)$sampah['stok_kg'], 2, ',', '.'), '0'), ',') . ' kg).'
            );
        }

        $stmt = mysqli_prepare($conn, "UPDATE sampah SET stok_kg = stok_kg - ? WHERE id_sampah = ?");
        mysqli_stmt_bind_param($stmt, "di", $berat, $idSampah);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $status = 'disetujui';

    } else {

        $status = 'ditolak';
    }


    $stmt = mysqli_prepare(
        $conn,
        "UPDATE pembelian
         SET status = ?, tanggal_proses = NOW()
         WHERE id_beli = ? AND status = 'pending'"
    );
    mysqli_stmt_bind_param($stmt, "si", $status, $idBeli);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_commit($conn);

    flash_set('success', $status === 'disetujui'
        ? 'Pembelian disetujui dan stok sampah dikurangi.'
        : 'Pembelian ditolak.');

} catch (Throwable $e) {

    mysqli_rollback($conn);
    flash_set('danger', $e->getMessage());
}

header('Location: pembelian.php');
exit;
