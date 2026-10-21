<?php
/* =====================================================
   LAYOUT & HELPER PORTAL (siswa, guru, pengepul)
===================================================== */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../koneksi.php';

const PORTALS = [
    'siswa' => [
        'label'  => 'Siswa',
        'icon'   => 'bi-backpack2',
        'accent' => '#0d9488',
        'dark'   => '#0f766e',
        'soft'   => '#f0fdfa',
    ],
    'guru' => [
        'label'  => 'Guru',
        'icon'   => 'bi-mortarboard',
        'accent' => '#4f46e5',
        'dark'   => '#3730a3',
        'soft'   => '#eef2ff',
    ],
    'pengepul' => [
        'label'  => 'Pengepul',
        'icon'   => 'bi-truck',
        'accent' => '#d97706',
        'dark'   => '#92400e',
        'soft'   => '#fffbeb',
    ],
];

/* ---------- DATABASE ---------- */

function db_all(string $sql, string $types = '', array $params = []): array
{
    global $conn;

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return [];
    }

    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $res  = mysqli_stmt_get_result($stmt);
    $rows = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt);

    return $rows;
}

function db_row(string $sql, string $types = '', array $params = []): ?array
{
    $rows = db_all($sql, $types, $params);
    return $rows[0] ?? null;
}

function table_exists(string $table): bool
{
    global $conn;
    $t   = mysqli_real_escape_string($conn, $table);
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$t'");
    return $res && mysqli_num_rows($res) > 0;
}

/* ---------- FORMAT ---------- */

function kg($angka): string
{
    $s = number_format((float)$angka, 2, ',', '.');
    return rtrim(rtrim($s, '0'), ',');
}

function tgl($tanggal): string
{
    $t = strtotime((string)$tanggal);
    return $t ? date('d/m/Y', $t) : '-';
}

function status_badge(string $status): string
{
    $map = [
        'pending'   => ['pending', 'bi-clock', 'Menunggu'],
        'disetujui' => ['ok', 'bi-check-circle', 'Disetujui'],
        'ditolak'   => ['no', 'bi-x-circle', 'Ditolak'],
    ];
    [$cls, $icon, $label] = $map[$status] ?? ['pending', 'bi-question-circle', ucfirst($status)];

    return '<span class="pill pill-' . $cls . '"><i class="bi ' . $icon . '"></i> ' . e($label) . '</span>';
}

function portal_flash(): void
{
    $f = flash_get();
    if (!$f) {
        return;
    }
    $icon = $f['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill';
    echo '<div class="alert alert-' . e($f['type']) . ' d-flex align-items-start gap-2">'
       . '<i class="bi ' . $icon . ' mt-1"></i><div>' . e($f['msg']) . '</div></div>';
}

/* ---------- AKSES ---------- */

/* Cek role + ambil data user terbaru dari database */
function portal_user(string $role): array
{
    global $conn;

    require_role($role);

    $u = db_row(
        "SELECT id_user, nama, usernama, saldo, role FROM users WHERE id_user = ? LIMIT 1",
        'i',
        [(int)$_SESSION['id_user']]
    );

    if (!$u || $u['role'] !== $role) {
        header('Location: ../logout.php');
        exit;
    }

    return $u;
}

function portal_nav(string $role): array
{
    if ($role === 'pengepul') {
        return [
            'dashboard' => ['dashboard.php', 'bi-grid', 'Beranda'],
            'beli'      => ['beli.php', 'bi-bag', 'Pembelian'],
            'profil'    => ['profil.php', 'bi-person', 'Profil'],
        ];
    }

    return [
        'dashboard' => ['dashboard.php', 'bi-grid', 'Beranda'],
        'tarik'     => ['tarik.php', 'bi-wallet2', 'Tarik Saldo'],
        'profil'    => ['profil.php', 'bi-person', 'Profil'],
    ];
}

/* ---------- LAYOUT ---------- */

function portal_start(array $u, string $role, string $active, string $title): void
{
    $p   = PORTALS[$role];
    $nav = portal_nav($role);
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> - Bank Sampah rapa</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --accent: <?= $p['accent'] ?>;
            --dark:   <?= $p['dark'] ?>;
            --soft:   <?= $p['soft'] ?>;
            --ink:    #1c1917;
            --muted:  #78716c;
            --line:   #e7e5e4;
            --bg:     #fafaf9;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font-family: 'Inter', system-ui, sans-serif;
            font-size: 14px;
        }

        a { color: var(--dark); }

        /* ----- top bar ----- */
        .topbar {
            position: sticky; top: 0; z-index: 30;
            background: #fff;
            border-bottom: 1px solid var(--line);
        }
        .topbar-in {
            max-width: 1040px; margin: 0 auto; padding: 0 20px;
            height: 60px; display: flex; align-items: center; gap: 20px;
        }
        .brand {
            display: flex; align-items: center; gap: 10px;
            font-weight: 700; color: var(--ink); text-decoration: none;
        }
        .brand-mark {
            width: 32px; height: 32px; border-radius: 9px;
            background: var(--accent); color: #fff;
            display: grid; place-items: center; font-size: 17px;
        }
        .role-tag {
            font-size: 11px; font-weight: 600; color: var(--dark);
            background: var(--soft); padding: 3px 10px; border-radius: 99px;
        }
        .nav-links { display: flex; gap: 4px; margin-left: 10px; }
        .nav-links a {
            padding: 8px 13px; border-radius: 9px;
            color: var(--muted); text-decoration: none; font-weight: 500;
        }
        .nav-links a:hover { background: #f5f5f4; color: var(--ink); }
        .nav-links a.active { background: var(--soft); color: var(--dark); }
        .who { margin-left: auto; display: flex; align-items: center; gap: 12px; }
        .who .name { font-weight: 600; }
        .logout {
            color: var(--muted); font-size: 18px; text-decoration: none;
            padding: 4px 8px; border-radius: 8px;
        }
        .logout:hover { color: #b91c1c; background: #fef2f2; }

        /* ----- mobile tab bar ----- */
        .tabbar {
            display: none; position: fixed; left: 0; right: 0; bottom: 0; z-index: 30;
            background: #fff; border-top: 1px solid var(--line);
            padding: 6px 8px calc(6px + env(safe-area-inset-bottom));
        }
        .tabbar a {
            flex: 1; text-align: center; text-decoration: none;
            color: var(--muted); font-size: 11px; font-weight: 500; padding: 6px 0;
        }
        .tabbar a i { display: block; font-size: 20px; }
        .tabbar a.active { color: var(--dark); }

        /* ----- layout ----- */
        .page { max-width: 1040px; margin: 0 auto; padding: 28px 20px 60px; }
        .page-head { margin-bottom: 22px; }
        .page-head h1 { font-size: 22px; font-weight: 700; margin: 0 0 4px; }
        .page-head p { color: var(--muted); margin: 0; }

        .card-x {
            background: #fff; border: 1px solid var(--line);
            border-radius: 14px; overflow: hidden;
        }
        .card-x .head {
            padding: 15px 20px; border-bottom: 1px solid var(--line);
            font-weight: 600; display: flex; align-items: center; justify-content: space-between;
        }
        .card-x .body { padding: 20px; }

        /* ----- saldo ----- */
        .wallet {
            background: var(--accent); color: #fff; border-radius: 16px;
            padding: 26px; display: flex; align-items: center;
            justify-content: space-between; gap: 20px; flex-wrap: wrap;
            position: relative; overflow: hidden;
        }
        .wallet::after {
            content: ""; position: absolute; right: -50px; top: -60px;
            width: 200px; height: 200px; border-radius: 50%;
            background: rgba(255,255,255,.1);
        }
        .wallet .label { opacity: .85; font-size: 13px; }
        .wallet .amount { font-size: 34px; font-weight: 700; letter-spacing: -.5px; line-height: 1.2; }
        .wallet .note { opacity: .85; font-size: 12px; margin-top: 4px; }
        .btn-light-x {
            background: #fff; color: var(--dark); font-weight: 600;
            border: none; border-radius: 10px; padding: 10px 18px;
            text-decoration: none; position: relative; z-index: 1;
        }
        .btn-light-x:hover { background: var(--soft); color: var(--dark); }

        .stat { background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 16px 18px; height: 100%; }
        .stat .k { color: var(--muted); font-size: 12px; }
        .stat .v { font-size: 20px; font-weight: 700; margin-top: 2px; }

        /* ----- table ----- */
        .tbl { width: 100%; border-collapse: collapse; }
        .tbl th {
            text-align: left; font-weight: 500; color: var(--muted);
            font-size: 12px; padding: 10px 20px; border-bottom: 1px solid var(--line);
            background: #fafaf9;
        }
        .tbl td { padding: 13px 20px; border-bottom: 1px solid #f5f5f4; vertical-align: middle; }
        .tbl tr:last-child td { border-bottom: none; }
        .tbl .num { text-align: right; font-variant-numeric: tabular-nums; }
        .empty { text-align: center; color: var(--muted); padding: 34px 20px; }
        .empty i { font-size: 28px; display: block; margin-bottom: 6px; opacity: .6; }
        .table-wrap { overflow-x: auto; }

        /* ----- pill ----- */
        .pill {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 10px; border-radius: 99px; font-size: 12px; font-weight: 600;
        }
        .pill-pending { background: #fefce8; color: #a16207; }
        .pill-ok      { background: #f0fdf4; color: #15803d; }
        .pill-no      { background: #fef2f2; color: #b91c1c; }

        /* ----- form ----- */
        .form-label { font-weight: 500; font-size: 13px; }
        .form-control, .form-select { border-radius: 10px; padding: 10px 13px; }
        .form-control:focus, .form-select:focus {
            border-color: var(--accent); box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--accent) 18%, transparent);
        }
        .btn-accent {
            background: var(--accent); color: #fff; border: none; font-weight: 600;
            border-radius: 10px; padding: 10px 20px;
        }
        .btn-accent:hover { background: var(--dark); color: #fff; }

        .hint { color: var(--muted); font-size: 12px; margin-top: 6px; }

        @media (max-width: 720px) {
            .nav-links, .who .name { display: none; }
            .tabbar { display: flex; }
            body { padding-bottom: 72px; }
            .page { padding-top: 20px; }
            .wallet .amount { font-size: 28px; }
            .tbl th, .tbl td { padding-left: 14px; padding-right: 14px; }
        }
    </style>
</head>
<body>

<header class="topbar">
    <div class="topbar-in">
        <a href="dashboard.php" class="brand">
            <span class="brand-mark"><i class="bi <?= e($p['icon']) ?>"></i></span>
            <span>Bank Sampah rapaa</span>
        </a>
        <span class="role-tag"><?= e($p['label']) ?></span>

        <nav class="nav-links">
            <?php foreach ($nav as $key => [$href, $icon, $label]): ?>
                <a href="<?= e($href) ?>" class="<?= $key === $active ? 'active' : '' ?>">
                    <i class="bi <?= e($icon) ?> me-1"></i><?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="who">
            <span class="name"><?= e($u['nama']) ?></span>
            <a href="../logout.php" class="logout" title="Keluar"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </div>
</header>

<nav class="tabbar">
    <?php foreach ($nav as $key => [$href, $icon, $label]): ?>
        <a href="<?= e($href) ?>" class="<?= $key === $active ? 'active' : '' ?>">
            <i class="bi <?= e($icon) ?>"></i><?= e($label) ?>
        </a>
    <?php endforeach; ?>
</nav>

<main class="page">
    <?php
}

function portal_end(): void
{
    ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
    <?php
}
