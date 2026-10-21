<?php
/* =====================================================
   HALAMAN NASABAH (dipakai portal siswa & guru)
===================================================== */

require_once __DIR__ . '/portal.php';


/* =====================================================
   BERANDA
===================================================== */

function page_nasabah_dashboard(string $role): void
{
    $u  = portal_user($role);
    $id = (int)$u['id_user'];

    $sum = db_row(
        "SELECT COUNT(*) AS jml,
                COALESCE(SUM(berat_kg), 0) AS berat,
                COALESCE(SUM(total_rp), 0) AS total
         FROM transaksi
         WHERE id_user = ? AND tipe = 'setor'",
        'i',
        [$id]
    ) ?? [];

    $ditarik = (db_row(
        "SELECT COALESCE(SUM(nominal), 0) AS total
         FROM tarik_saldo
         WHERE id_user = ? AND status = 'disetujui'",
        'i',
        [$id]
    ) ?? [])['total'] ?? 0;

    $pending = db_row(
        "SELECT nominal FROM tarik_saldo
         WHERE id_user = ? AND status = 'pending'
         ORDER BY id_tarik DESC LIMIT 1",
        'i',
        [$id]
    );

    $riwayat = db_all(
        "SELECT t.berat_kg, t.total_rp, t.tanggal, s.jenis_sampah
         FROM transaksi t
         LEFT JOIN sampah s ON t.id_sampah = s.id_sampah
         WHERE t.id_user = ?
         ORDER BY t.id_transaksi DESC
         LIMIT 10",
        'i',
        [$id]
    );

    $harga = db_all(
        "SELECT jenis_sampah, harga_per_kg FROM sampah ORDER BY jenis_sampah ASC"
    );

    portal_start($u, $role, 'dashboard', 'Beranda');
    ?>

    <div class="page-head">
        <h1>Halo, <?= e($u['nama']) ?></h1>
        <p>Ringkasan tabungan sampah Anda.</p>
    </div>

    <div class="wallet mb-4">
        <div style="position:relative;z-index:1">
            <div class="label">Saldo Anda</div>
            <div class="amount"><?= e(rupiah($u['saldo'])) ?></div>
            <?php if ($pending): ?>
                <div class="note">
                    <i class="bi bi-clock me-1"></i>
                    Penarikan <?= e(rupiah($pending['nominal'])) ?> menunggu persetujuan admin
                </div>
            <?php else: ?>
                <div class="note">Saldo bertambah setiap kali Anda menyetor sampah.</div>
            <?php endif; ?>
        </div>
        <a href="tarik.php" class="btn-light-x">
            <i class="bi bi-wallet2 me-1"></i> Tarik saldo
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat"><div class="k">Kali menyetor</div><div class="v"><?= (int)($sum['jml'] ?? 0) ?></div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat"><div class="k">Total berat</div><div class="v"><?= e(kg($sum['berat'] ?? 0)) ?> kg</div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat"><div class="k">Total pendapatan</div><div class="v"><?= e(rupiah($sum['total'] ?? 0)) ?></div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat"><div class="k">Sudah ditarik</div><div class="v"><?= e(rupiah($ditarik)) ?></div></div>
        </div>
    </div>

    <div class="row g-3">

        <div class="col-lg-7">
            <div class="card-x">
                <div class="head">Setoran terakhir</div>
                <div class="table-wrap">
                    <table class="tbl">
                        <thead>
                            <tr><th>Tanggal</th><th>Jenis</th><th class="num">Berat</th><th class="num">Nilai</th></tr>
                        </thead>
                        <tbody>
                        <?php if ($riwayat): foreach ($riwayat as $r): ?>
                            <tr>
                                <td><?= e(tgl($r['tanggal'])) ?></td>
                                <td><?= e($r['jenis_sampah'] ?? '-') ?></td>
                                <td class="num"><?= e(kg($r['berat_kg'])) ?> kg</td>
                                <td class="num"><?= e(rupiah($r['total_rp'])) ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="4"><div class="empty"><i class="bi bi-inbox"></i>Belum ada setoran sampah.</div></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card-x">
                <div class="head">Harga sampah per kg</div>
                <div class="table-wrap">
                    <table class="tbl">
                        <tbody>
                        <?php if ($harga): foreach ($harga as $h): ?>
                            <tr>
                                <td><?= e($h['jenis_sampah']) ?></td>
                                <td class="num fw-semibold"><?= e(rupiah($h['harga_per_kg'])) ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td><div class="empty">Belum ada data harga.</div></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <?php
    portal_end();
}


/* =====================================================
   TARIK SALDO
===================================================== */

function page_nasabah_tarik(string $role): void
{
    global $conn;

    $u  = portal_user($role);
    $id = (int)$u['id_user'];

    /* ---------- PROSES PENGAJUAN ---------- */

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajukan'])) {

        $raw = trim((string)($_POST['nominal'] ?? ''));

        if (!csrf_valid()) {

            flash_set('danger', 'Sesi formulir tidak valid. Silakan coba lagi.');

        } elseif ($raw === '' || !ctype_digit($raw)) {

            flash_set('danger', 'Nominal penarikan tidak valid.');

        } else {

            $nominal = (float)$raw;

            if ($nominal < 1000) {

                flash_set('danger', 'Minimal penarikan adalah Rp 1.000.');

            } elseif (fmod($nominal, 1000) != 0) {

                flash_set('danger', 'Nominal penarikan harus kelipatan Rp 1.000.');

            } else {

                mysqli_begin_transaction($conn);

                try {

                    /* kunci baris user agar tidak terjadi pengajuan ganda */
                    $stmt = mysqli_prepare($conn, "SELECT saldo FROM users WHERE id_user = ? FOR UPDATE");
                    mysqli_stmt_bind_param($stmt, 'i', $id);
                    mysqli_stmt_execute($stmt);
                    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                    mysqli_stmt_close($stmt);

                    $saldo = (float)($row['saldo'] ?? 0);

                    $stmt = mysqli_prepare(
                        $conn,
                        "SELECT COUNT(*) AS n FROM tarik_saldo WHERE id_user = ? AND status = 'pending'"
                    );
                    mysqli_stmt_bind_param($stmt, 'i', $id);
                    mysqli_stmt_execute($stmt);
                    $pend = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['n'] ?? 0);
                    mysqli_stmt_close($stmt);

                    if ($nominal > $saldo) {
                        throw new Exception('Saldo tidak mencukupi. Saldo tersedia: ' . rupiah($saldo) . '.');
                    }

                    if ($pend > 0) {
                        throw new Exception('Masih ada pengajuan penarikan yang menunggu persetujuan.');
                    }

                    $stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO tarik_saldo (id_user, nominal, tanggal, status)
                         VALUES (?, ?, NOW(), 'pending')"
                    );
                    mysqli_stmt_bind_param($stmt, 'id', $id, $nominal);

                    if (!mysqli_stmt_execute($stmt)) {
                        mysqli_stmt_close($stmt);
                        throw new Exception('Pengajuan penarikan gagal disimpan.');
                    }
                    mysqli_stmt_close($stmt);

                    mysqli_commit($conn);

                    flash_set('success', 'Pengajuan penarikan berhasil dibuat dan menunggu persetujuan admin.');

                } catch (Throwable $ex) {

                    mysqli_rollback($conn);
                    flash_set('danger', $ex->getMessage());
                }
            }
        }

        header('Location: tarik.php');
        exit;
    }

    /* ---------- DATA ---------- */

    $riwayat = db_all(
        "SELECT id_tarik, nominal, tanggal, status
         FROM tarik_saldo
         WHERE id_user = ?
         ORDER BY id_tarik DESC
         LIMIT 50",
        'i',
        [$id]
    );

    $adaPending = false;
    foreach ($riwayat as $r) {
        if ($r['status'] === 'pending') {
            $adaPending = true;
            break;
        }
    }

    portal_start($u, $role, 'tarik', 'Tarik Saldo');
    ?>

    <div class="page-head">
        <h1>Tarik saldo</h1>
        <p>Ajukan penarikan, admin akan memprosesnya.</p>
    </div>

    <?php portal_flash(); ?>

    <div class="row g-3">

        <div class="col-lg-5">
            <div class="card-x">
                <div class="head">Ajukan penarikan</div>
                <div class="body">

                    <div class="mb-3">
                        <div class="text-muted" style="font-size:12px">Saldo tersedia</div>
                        <div class="fw-bold" style="font-size:24px"><?= e(rupiah($u['saldo'])) ?></div>
                    </div>

                    <?php if ($adaPending): ?>

                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-clock me-1"></i>
                            Anda masih punya pengajuan yang menunggu persetujuan.
                            Pengajuan baru bisa dibuat setelah diproses.
                        </div>

                    <?php else: ?>

                        <form method="POST" autocomplete="off">
                            <?= csrf_field() ?>

                            <label class="form-label" for="nominal">Nominal penarikan</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="nominal" id="nominal" class="form-control"
                                       min="1000" step="1000" max="<?= (int)$u['saldo'] ?>"
                                       placeholder="Contoh: 50000" required>
                            </div>
                            <div class="hint">Minimal Rp 1.000 dan kelipatan Rp 1.000.</div>

                            <button type="submit" name="ajukan" class="btn-accent w-100 mt-3">
                                <i class="bi bi-send me-1"></i> Ajukan penarikan
                            </button>
                        </form>

                    <?php endif; ?>

                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card-x">
                <div class="head">Riwayat penarikan</div>
                <div class="table-wrap">
                    <table class="tbl">
                        <thead>
                            <tr><th>Tanggal</th><th class="num">Nominal</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                        <?php if ($riwayat): foreach ($riwayat as $r): ?>
                            <tr>
                                <td><?= e(tgl($r['tanggal'])) ?></td>
                                <td class="num"><?= e(rupiah($r['nominal'])) ?></td>
                                <td><?= status_badge((string)$r['status']) ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="3"><div class="empty"><i class="bi bi-inbox"></i>Belum ada riwayat penarikan.</div></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <?php
    portal_end();
}


/* =====================================================
   PROFIL (dipakai semua portal non-admin)
===================================================== */

function page_profil(string $role): void
{
    global $conn;

    $u  = portal_user($role);
    $id = (int)$u['id_user'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_password'])) {

        $lama    = (string)($_POST['password_lama'] ?? '');
        $baru    = (string)($_POST['password_baru'] ?? '');
        $konfirm = (string)($_POST['password_konfirmasi'] ?? '');

        $row = db_row("SELECT password FROM users WHERE id_user = ? LIMIT 1", 'i', [$id]);

        if (!csrf_valid()) {

            flash_set('danger', 'Sesi formulir tidak valid. Silakan coba lagi.');

        } elseif (!$row || !password_check($lama, (string)$row['password'])) {

            flash_set('danger', 'Password lama salah.');

        } elseif (strlen($baru) < 6) {

            flash_set('danger', 'Password baru minimal 6 karakter.');

        } elseif ($baru !== $konfirm) {

            flash_set('danger', 'Konfirmasi password baru tidak sama.');

        } else {

            $hash = password_make($conn, $baru);

            $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id_user = ?");
            mysqli_stmt_bind_param($stmt, 'si', $hash, $id);
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($ok) {
                flash_set('success', 'Password berhasil diganti.');
            } else {
                flash_set('danger', 'Password gagal diganti.');
            }
        }

        header('Location: profil.php');
        exit;
    }

    portal_start($u, $role, 'profil', 'Profil');
    ?>

    <div class="page-head">
        <h1>Profil</h1>
        <p>Data akun dan keamanan.</p>
    </div>

    <?php portal_flash(); ?>

    <div class="row g-3">

        <div class="col-lg-5">
            <div class="card-x">
                <div class="head">Data akun</div>
                <table class="tbl">
                    <tbody>
                        <tr><td class="text-muted">Nama</td><td class="num fw-semibold"><?= e($u['nama']) ?></td></tr>
                        <tr><td class="text-muted">Username</td><td class="num fw-semibold">@<?= e($u['usernama']) ?></td></tr>
                        <tr><td class="text-muted">Peran</td><td class="num fw-semibold"><?= e(PORTALS[$role]['label']) ?></td></tr>
                        <?php if ($role !== 'pengepul'): ?>
                        <tr><td class="text-muted">Saldo</td><td class="num fw-semibold"><?= e(rupiah($u['saldo'])) ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card-x">
                <div class="head">Ganti password</div>
                <div class="body">
                    <form method="POST" autocomplete="off">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label">Password lama</label>
                            <input type="password" name="password_lama" class="form-control" autocomplete="current-password" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password baru</label>
                            <input type="password" name="password_baru" class="form-control" minlength="6" autocomplete="new-password" required>
                            <div class="hint">Minimal 6 karakter.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ulangi password baru</label>
                            <input type="password" name="password_konfirmasi" class="form-control" minlength="6" autocomplete="new-password" required>
                        </div>

                        <button type="submit" name="ganti_password" class="btn-accent">
                            <i class="bi bi-shield-lock me-1"></i> Simpan password
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <?php
    portal_end();
}
