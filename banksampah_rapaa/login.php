<?php

session_start();
include "koneksi.php";
require_once __DIR__ . '/includes/auth.php';

/* Sudah login -> arahkan ke portal sesuai role */
if (!empty($_SESSION['id_user']) && isset(ROLE_HOME[$_SESSION['role'] ?? ''])) {
    header("Location: " . role_home($_SESSION['role']));
    exit;
}

$error = "";

if (isset($_POST['login'])) {

    $usernama = trim($_POST['usernama'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usernama == "" || $password == "") {

        $error = "Username dan password wajib diisi.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT * FROM users WHERE usernama = ? LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "s", $usernama);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $user   = $result ? mysqli_fetch_assoc($result) : null;

        mysqli_stmt_close($stmt);

        if ($user && password_check($password, (string)$user['password'])) {

            if (!isset(ROLE_HOME[$user['role']])) {

                $error = "Peran akun tidak dikenali. Hubungi administrator.";

            } else {

                /* Password lama (plaintext) otomatis di-hash saat login berhasil */
                if (!password_is_hashed((string)$user['password']) && password_column_ok($conn)) {

                    $hash = password_hash($password, PASSWORD_DEFAULT);

                    $up = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id_user = ?");
                    $uid = (int)$user['id_user'];
                    mysqli_stmt_bind_param($up, "si", $hash, $uid);
                    mysqli_stmt_execute($up);
                    mysqli_stmt_close($up);
                }

                session_regenerate_id(true);

                $_SESSION['id_user']  = $user['id_user'];
                $_SESSION['nama']     = $user['nama'];
                $_SESSION['usernama'] = $user['usernama'];
                $_SESSION['role']     = $user['role'];

                header("Location: " . role_home($user['role']));
                exit;
            }

        } else {

            /* pesan sama untuk username salah / password salah */
            $error = "Username atau password salah.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Login - Bank Sampah rapaa</title>

    <!-- Bootstrap 5 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet">

    <style>

        body {
            min-height: 100vh;
            background: #f1f8f5;
            font-family: Arial, sans-serif;
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px;
        }

        .login-container {
            width: 100%;
            max-width: 1050px;
            min-height: 600px;
            background: white;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .12);
        }

        /* BAGIAN KIRI */

        .left-section {
            background:
                linear-gradient(
                    145deg,
                    #198754,
                    #20a878
                );

            color: white;
            min-height: 600px;

            display: flex;
            flex-direction: column;
            justify-content: center;

            padding: 55px;
            position: relative;
            overflow: hidden;
        }

        .left-section::before {
            content: "";
            position: absolute;

            width: 300px;
            height: 300px;

            background: rgba(255,255,255,.08);

            border-radius: 50%;

            top: -100px;
            right: -100px;
        }

        .left-section::after {
            content: "";
            position: absolute;

            width: 220px;
            height: 220px;

            background: rgba(255,255,255,.08);

            border-radius: 50%;

            bottom: -90px;
            left: -80px;
        }

        .brand-icon {
            width: 85px;
            height: 85px;

            background: rgba(255,255,255,.15);

            border: 1px solid rgba(255,255,255,.25);

            border-radius: 22px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 45px;

            margin-bottom: 30px;
        }

        .left-section h1 {
            font-weight: 700;
            font-size: 38px;
            margin-bottom: 15px;
        }

        .left-section p {
            font-size: 16px;
            line-height: 1.7;
            opacity: .9;
            max-width: 450px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 13px;
            margin-top: 25px;
        }

        .feature-icon {
            width: 42px;
            height: 42px;

            background: rgba(255,255,255,.15);

            border-radius: 12px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 20px;
        }

        .feature span {
            font-size: 14px;
        }

        /* BAGIAN KANAN */

        .right-section {
            padding: 55px;
            display: flex;
            align-items: center;
        }

        .login-content {
            width: 100%;
            max-width: 420px;
            margin: auto;
        }

        .login-title {
            font-weight: 700;
            color: #212529;
            margin-bottom: 7px;
        }

        .login-subtitle {
            color: #777;
            margin-bottom: 35px;
        }

        .form-label {
            font-weight: 600;
            font-size: 14px;
        }

        .input-group-text {
            background: white;
            border-right: none;
            color: #198754;
        }

        .form-control {
            border-left: none;
            padding: 13px;
        }

        .form-control:focus {
            border-color: #198754;
            box-shadow: 0 0 0 .2rem rgba(25,135,84,.12);
        }

        .input-group:focus-within .input-group-text {
            border-color: #198754;
        }

        .btn-login {
            width: 100%;
            padding: 13px;

            background: #198754;
            border: none;

            border-radius: 10px;

            font-weight: 600;
            color: white;

            transition: .2s;
        }

        .btn-login:hover {
            background: #146c43;
            transform: translateY(-1px);
        }

        .admin-badge {
            display: inline-block;

            padding: 6px 12px;

            background: #e8f5ee;
            color: #198754;

            border-radius: 20px;

            font-size: 12px;
            font-weight: 600;

            margin-bottom: 15px;
        }

        .footer {
            text-align: center;
            color: #999;
            font-size: 12px;
            margin-top: 30px;
        }

        @media (max-width: 768px) {

            .login-container {
                max-width: 500px;
            }

            .left-section {
                min-height: auto;
                padding: 40px;
            }

            .left-section h1 {
                font-size: 30px;
            }

            .right-section {
                padding: 40px 25px;
            }

            .feature {
                display: none;
            }
        }

    </style>

</head>

<body>

<div class="login-wrapper">

    <div class="login-container">

        <div class="row g-0 h-100">

            <!-- =========================
                 BAGIAN KIRI
            ========================== -->

            <div class="col-lg-6">

                <div class="left-section">

                    <div class="brand-icon">
                        <i class="bi bi-recycle"></i>
                    </div>

                    <h1>
                        Bank Sampah<br>
                        Akbar
                    </h1>

                    <p>
                        Satu pintu masuk untuk admin, siswa, guru, dan pengepul. Setelah masuk, Anda diarahkan ke halaman sesuai peran.
                    </p>

                    <div class="feature">

                        <div class="feature-icon">
                            <i class="bi bi-bar-chart-fill"></i>
                        </div>

                        <span>
                            Kelola transaksi dengan mudah
                        </span>

                    </div>

                    <div class="feature">

                        <div class="feature-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>

                        <span>
                            Kelola data pengguna
                        </span>

                    </div>

                    <div class="feature">

                        <div class="feature-icon">
                            <i class="bi bi-wallet2"></i>
                        </div>

                        <span>
                            Pantau saldo pengguna
                        </span>

                    </div>

                </div>

            </div>


            <!-- =========================
                 BAGIAN LOGIN
            ========================== -->

            <div class="col-lg-6">

                <div class="right-section">

                    <div class="login-content">

                        <div class="admin-badge">

                            <i class="bi bi-shield-check me-1"></i>

                            PORTAL BANK SAMPAH

                        </div>

                        <h2 class="login-title">
                            Selamat Datang!
                        </h2>

                        <p class="login-subtitle">
                            Masuk sebagai admin, siswa, guru, atau pengepul.
                        </p>


                        <?php if ($error != ""): ?>

                            <div class="alert alert-danger d-flex align-items-center"
                                 role="alert">

                                <i class="bi bi-exclamation-circle-fill me-2"></i>

                                <div>
                                    <?= htmlspecialchars($error); ?>
                                </div>

                            </div>

                        <?php endif; ?>


                        <form method="POST">


                            <!-- USERNAME -->

                            <div class="mb-4">

                                <label class="form-label">
                                    Username
                                </label>

                                <div class="input-group">

                                    <span class="input-group-text">

                                        <i class="bi bi-person"></i>

                                    </span>

                                    <input
                                        type="text"
                                        name="usernama"
                                        class="form-control"
                                        placeholder="Masukkan username"
                                        autocomplete="username"
                                        required
                                    >

                                </div>

                            </div>


                            <!-- PASSWORD -->

                            <div class="mb-4">

                                <label class="form-label">
                                    Password
                                </label>

                                <div class="input-group">

                                    <span class="input-group-text">

                                        <i class="bi bi-lock"></i>

                                    </span>

                                    <input
                                        type="password"
                                        name="password"
                                        id="password"
                                        class="form-control"
                                        placeholder="Masukkan password"
                                        autocomplete="current-password"
                                        required
                                    >

                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary"
                                        onclick="togglePassword()"
                                    >

                                        <i
                                            class="bi bi-eye"
                                            id="eyeIcon">
                                        </i>

                                    </button>

                                </div>

                            </div>


                            <!-- LOGIN -->

                            <button
                                type="submit"
                                name="login"
                                class="btn-login"
                            >

                                <i class="bi bi-box-arrow-in-right me-2"></i>

                                Masuk

                            </button>


                        </form>


                        <div class="footer">

                            <i class="bi bi-recycle me-1"></i>

                            © 2026 Bank Sampah Akbar

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script>

function togglePassword() {

    const password =
        document.getElementById("password");

    const icon =
        document.getElementById("eyeIcon");

    if (password.type === "password") {

        password.type = "text";

        icon.classList.remove("bi-eye");

        icon.classList.add("bi-eye-slash");

    } else {

        password.type = "password";

        icon.classList.remove("bi-eye-slash");

        icon.classList.add("bi-eye");

    }

}

</script>


</body>
</html>