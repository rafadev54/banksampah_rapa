<?php
session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

include __DIR__ . "/../koneksi.php";
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$query = mysqli_query($conn, "
    SELECT 
        tarik_saldo.*,
        users.nama,
        users.role,
        users.usernama,
        users.saldo
    FROM tarik_saldo
    LEFT JOIN users 
        ON tarik_saldo.id_user = users.id_user
    WHERE tarik_saldo.id_tarik = $id
");

$data = mysqli_fetch_assoc($query);

if (!$data) {
    echo "<script>
        alert('Data penarikan tidak ditemukan!');
        window.location='tarik.php';
    </script>";
    exit;
}

function rupiahDetail($angka)
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Detail Penarikan - Bank Sampah Akbar</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <style>

        body {
            margin: 0;
            background: #f8fafc;
            color: #0f172a;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
        }

        .top-navbar {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 0 40px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            color: #0f172a;
            text-decoration: none;
        }

        .brand-logo i {
            background: #0f172a;
            color: white;
            padding: 6px;
            border-radius: 8px;
        }

        .main-container {
            max-width: 850px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-title {
            margin-bottom: 25px;
        }

        .page-title h2 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .page-title p {
            color: #64748b;
            margin: 0;
        }

        .detail-card {
            background: white;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            overflow: hidden;
        }

        .detail-header {
            padding: 20px;
            border-bottom: 1px solid #f1f5f9;
            font-weight: 700;
        }

        .detail-body {
            padding: 25px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #64748b;
        }

        .detail-value {
            font-weight: 600;
            text-align: right;
        }

        .badge-status {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }

        .pending {
            background: #fefce8;
            color: #a16207;
        }

        .disetujui {
            background: #f0fdf4;
            color: #15803d;
        }

        .ditolak {
            background: #fef2f2;
            color: #b91c1c;
        }

        .btn-back {
            background: #0f172a;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-top: 20px;
        }

        .btn-back:hover {
            color: white;
            background: #1e293b;
        }

    </style>
</head>

<body>

<div class="top-navbar">

    <a href="dashboard.php" class="brand-logo">
        <i class="bi bi-recycle"></i>
        Bank Sampah Akbar
    </a>

</div>


<div class="main-container">

    <div class="page-title">

        <h2>Detail Penarikan Saldo</h2>

        <p>
            Informasi lengkap pengajuan penarikan saldo.
        </p>

    </div>


    <div class="detail-card">

        <div class="detail-header">
            <i class="bi bi-wallet2 me-2"></i>
            Informasi Penarikan
        </div>


        <div class="detail-body">

            <div class="detail-row">

                <span class="detail-label">
                    ID Penarikan
                </span>

                <span class="detail-value">
                    #<?= $data['id_tarik']; ?>
                </span>

            </div>


            <div class="detail-row">

                <span class="detail-label">
                    Nama Pengguna
                </span>

                <span class="detail-value">
                    <?= htmlspecialchars($data['nama'] ?? '-'); ?>
                </span>

            </div>


            <div class="detail-row">

                <span class="detail-label">
                    Username
                </span>

                <span class="detail-value">
                    @<?= htmlspecialchars($data['usernama'] ?? '-'); ?>
                </span>

            </div>


            <div class="detail-row">

                <span class="detail-label">
                    Role
                </span>

                <span class="detail-value">
                    <?= ucfirst(htmlspecialchars($data['role'] ?? '-')); ?>
                </span>

            </div>


            <div class="detail-row">

                <span class="detail-label">
                    Saldo Saat Ini
                </span>

                <span class="detail-value">
                    <?= rupiahDetail($data['saldo'] ?? 0); ?>
                </span>

            </div>


            <div class="detail-row">

                <span class="detail-label">
                    Nominal Penarikan
                </span>

                <span class="detail-value">
                    <?= rupiahDetail($data['nominal']); ?>
                </span>

            </div>


            <div class="detail-row">

                <span class="detail-label">
                    Tanggal
                </span>

                <span class="detail-value">
                    <?= date('d-m-Y', strtotime($data['tanggal'])); ?>
                </span>

            </div>


            <div class="detail-row">

                <span class="detail-label">
                    Status
                </span>

                <span class="detail-value">

                    <?php if ($data['status'] == 'pending'): ?>

                        <span class="badge-status pending">
                            <i class="bi bi-clock"></i>
                            Pending
                        </span>

                    <?php elseif ($data['status'] == 'disetujui'): ?>

                        <span class="badge-status disetujui">
                            <i class="bi bi-check-circle"></i>
                            Disetujui
                        </span>

                    <?php else: ?>

                        <span class="badge-status ditolak">
                            <i class="bi bi-x-circle"></i>
                            Ditolak
                        </span>

                    <?php endif; ?>

                </span>

            </div>


            <?php if ($data['status'] == 'pending'): ?>

                <div class="d-flex gap-2 mt-4">

                    <a
                        href="tarik_proses.php?aksi=setujui&id=<?= $data['id_tarik']; ?>"
                        class="btn btn-success"
                        onclick="return confirm('Setujui penarikan saldo ini?')"
                    >
                        <i class="bi bi-check-lg me-1"></i>
                        Setujui
                    </a>

                    <a
                        href="tarik_proses.php?aksi=tolak&id=<?= $data['id_tarik']; ?>"
                        class="btn btn-danger"
                        onclick="return confirm('Tolak penarikan saldo ini?')"
                    >
                        <i class="bi bi-x-lg me-1"></i>
                        Tolak
                    </a>

                </div>

            <?php endif; ?>


            <a href="tarik.php" class="btn-back">
                <i class="bi bi-arrow-left me-1"></i>
                Kembali
            </a>

        </div>

    </div>

</div>

</body>
</html>