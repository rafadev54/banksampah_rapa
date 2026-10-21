<?php
/* =====================================================
   AUTH & HELPER UMUM (dipakai semua portal)
===================================================== */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const ROLE_HOME = [
    'admin'    => 'admin/dashboard.php',
    'siswa'    => 'siswa/dashboard.php',
    'guru'     => 'guru/dashboard.php',
    'pengepul' => 'pengepul/dashboard.php',
];

function role_home(string $role): string
{
    return ROLE_HOME[$role] ?? 'login.php';
}

/* Dipanggil di paling atas setiap halaman di dalam folder role */
function require_role(string $role): void
{
    if (empty($_SESSION['id_user'])) {
        header('Location: ../login.php');
        exit;
    }

    $actual = $_SESSION['role'] ?? '';

    if ($actual !== $role) {
        if (isset(ROLE_HOME[$actual])) {
            header('Location: ../' . ROLE_HOME[$actual]);
        } else {
            header('Location: ../logout.php');
        }
        exit;
    }
}

function e($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    return isset($_POST['csrf'])
        && is_string($_POST['csrf'])
        && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']);
}

/* ---------- FLASH MESSAGE ---------- */

function flash_set(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function flash_get(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- PASSWORD (hash, tetap kompatibel dgn password lama) ---------- */

/* Kolom password harus minimal 60 karakter agar hash muat.
   Jalankan database_update.sql terlebih dahulu. */
function password_column_ok(mysqli $conn): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }

    $ok = false;
    $res = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'password'");
    $row = $res ? mysqli_fetch_assoc($res) : null;

    if ($row) {
        if (preg_match('/\((\d+)\)/', $row['Type'], $m)) {
            $ok = ((int)$m[1] >= 60);
        } elseif (stripos($row['Type'], 'text') !== false) {
            $ok = true;
        }
    }

    return $ok;
}

function password_is_hashed(string $stored): bool
{
    return (bool)preg_match('/^\$(2[aby]|argon2)/', $stored);
}

function password_check(string $input, string $stored): bool
{
    if (password_is_hashed($stored)) {
        return password_verify($input, $stored);
    }
    return hash_equals($stored, $input);
}

function password_make(mysqli $conn, string $plain): string
{
    return password_column_ok($conn)
        ? password_hash($plain, PASSWORD_DEFAULT)
        : $plain;
}
