<?php

session_start();

include __DIR__ . "/../koneksi.php";
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');


function kembali_transaksi(string $pesan = ''): void
{
    echo "<script>";
    if ($pesan !== '') {
        echo "alert(" . json_encode($pesan, JSON_UNESCAPED_UNICODE) . ");";
    }
    echo "window.location='transaksi.php';</script>";
    exit;
}


if (isset($_POST['simpan'])) {

    $idUser   = (int)($_POST['id_user'] ?? 0);
    $idSampah = (int)($_POST['id_sampah'] ?? 0);
    $berat    = round((float)($_POST['berat_kg'] ?? 0), 2);
    $tanggal  = trim((string)($_POST['tanggal'] ?? ''));


    if ($berat <= 0) {
        kembali_transaksi('Berat sampah harus lebih dari 0.');
    }

    /* validasi tanggal (Y-m-d, boleh disertai jam) */
    $tanggal = str_replace('T', ' ', $tanggal);

    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})(?: \d{2}:\d{2}(?::\d{2})?)?$/', $tanggal, $t)
        || !checkdate((int)$t[2], (int)$t[3], (int)$t[1])) {
        kembali_transaksi('Format tanggal tidak valid.');
    }


    mysqli_begin_transaction($conn);

    try {

        /* nasabah harus siswa / guru */
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_user FROM users
             WHERE id_user = ? AND role IN ('siswa', 'guru')
             LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "i", $idUser);
        mysqli_stmt_execute($stmt);
        $nasabah = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$nasabah) {
            throw new Exception("Nasabah tidak ditemukan atau bukan siswa/guru.");
        }


        /* data sampah (harga diambil dari database, bukan dari form) */
        $stmt = mysqli_prepare(
            $conn,
            "SELECT harga_per_kg FROM sampah WHERE id_sampah = ? LIMIT 1 FOR UPDATE"
        );
        mysqli_stmt_bind_param($stmt, "i", $idSampah);
        mysqli_stmt_execute($stmt);
        $sampah = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$sampah) {
            throw new Exception("Data sampah tidak ditemukan.");
        }

        $harga = (int)$sampah['harga_per_kg'];
        $total = (int)round($berat * $harga);


        /* simpan transaksi */
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO transaksi (id_user, id_sampah, tipe, berat_kg, total_rp, tanggal)
             VALUES (?, ?, 'setor', ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "iidis", $idUser, $idSampah, $berat, $total, $tanggal);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Gagal menyimpan transaksi.");
        }
        mysqli_stmt_close($stmt);


        /* tambah saldo nasabah */
        $stmt = mysqli_prepare($conn, "UPDATE users SET saldo = saldo + ? WHERE id_user = ?");
        mysqli_stmt_bind_param($stmt, "ii", $total, $idUser);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Gagal menambahkan saldo.");
        }
        mysqli_stmt_close($stmt);


        /* tambah stok sampah */
        $stmt = mysqli_prepare($conn, "UPDATE sampah SET stok_kg = stok_kg + ? WHERE id_sampah = ?");
        mysqli_stmt_bind_param($stmt, "di", $berat, $idSampah);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Gagal memperbarui stok.");
        }
        mysqli_stmt_close($stmt);


        mysqli_commit($conn);

    } catch (Throwable $e) {

        mysqli_rollback($conn);
        kembali_transaksi($e->getMessage());
    }

    kembali_transaksi();
}

kembali_transaksi();
