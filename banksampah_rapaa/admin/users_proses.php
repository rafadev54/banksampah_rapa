<?php

session_start();

include __DIR__ . "/../koneksi.php";
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');


/* Tampilkan pesan lalu kembali ke daftar pengguna */
function kembali_users(string $pesan = ''): void
{
    echo "<script>";
    if ($pesan !== '') {
        echo "alert(" . json_encode($pesan, JSON_UNESCAPED_UNICODE) . ");";
    }
    echo "window.location='users.php';</script>";
    exit;
}

$roleValid = ['admin', 'siswa', 'guru', 'pengepul'];


/* =========================
   TAMBAH USER
========================= */

if (isset($_POST['tambah'])) {

    $nama     = trim($_POST['nama'] ?? '');
    $usernama = trim($_POST['usernama'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $role     = $_POST['role'] ?? '';

    if ($nama === '' || $usernama === '' || $password === '') {
        kembali_users('Nama, username, dan password wajib diisi.');
    }

    if (!in_array($role, $roleValid, true)) {
        kembali_users('Peran pengguna tidak valid.');
    }

    try {

        /* username harus unik */
        $cek = mysqli_prepare($conn, "SELECT id_user FROM users WHERE usernama = ? LIMIT 1");
        mysqli_stmt_bind_param($cek, "s", $usernama);
        mysqli_stmt_execute($cek);
        $ada = mysqli_stmt_get_result($cek);

        if ($ada && mysqli_num_rows($ada) > 0) {
            mysqli_stmt_close($cek);
            kembali_users('Username "' . $usernama . '" sudah dipakai.');
        }
        mysqli_stmt_close($cek);

        $hash = password_make($conn, $password);

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO users (nama, usernama, password, saldo, role)
             VALUES (?, ?, ?, 0, ?)"
        );
        mysqli_stmt_bind_param($stmt, "ssss", $nama, $usernama, $hash, $role);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

    } catch (Throwable $e) {
        kembali_users('Gagal menambahkan pengguna: ' . $e->getMessage());
    }

    kembali_users();
}


/* =========================
   HAPUS USER
========================= */

if (isset($_GET['hapus'])) {

    $id = (int)$_GET['hapus'];

    if ($id === (int)$_SESSION['id_user']) {
        kembali_users('Admin yang sedang login tidak boleh dihapus.');
    }

    try {

        /* jangan hapus pengguna yang sudah punya riwayat */
        $tabel = ['transaksi', 'tarik_saldo'];
        if (table_exists_admin($conn, 'pembelian')) {
            $tabel[] = 'pembelian';
        }

        foreach ($tabel as $t) {
            $q = mysqli_prepare($conn, "SELECT COUNT(*) AS n FROM $t WHERE id_user = ?");
            mysqli_stmt_bind_param($q, "i", $id);
            mysqli_stmt_execute($q);
            $n = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($q))['n'] ?? 0);
            mysqli_stmt_close($q);

            if ($n > 0) {
                kembali_users('Pengguna tidak bisa dihapus karena sudah memiliki riwayat ' . $t . '.');
            }
        }

        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id_user = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

    } catch (Throwable $e) {
        kembali_users('Gagal menghapus pengguna: ' . $e->getMessage());
    }

    kembali_users();
}


function table_exists_admin(mysqli $conn, string $table): bool
{
    $t = mysqli_real_escape_string($conn, $table);
    $r = mysqli_query($conn, "SHOW TABLES LIKE '$t'");
    return $r && mysqli_num_rows($r) > 0;
}

kembali_users();
