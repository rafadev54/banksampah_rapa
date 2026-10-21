<?php
/* =====================================================
   HALAMAN PENGEPUL
===================================================== */

require_once __DIR__ . '/pages_nasabah.php';   // page_profil() dipakai bersama


/* Tabel pembelian dibuat lewat database_update.sql */
function pengepul_require_table(array $u): void
{
    if (table_exists('pembelian')) {
        return;
    }

    portal_start($u, 'pengepul', 'dashboard', 'Database belum diperbarui');
    ?>
    <div class="alert alert-warning">
        <strong>Tabel <code>pembelian</code> belum ada.</strong><br>
        Minta admin menjalankan file <code>database_update.sql</code> pada database
        <code>banksampah_rapaa</code>, lalu muat ulang halaman ini.
    </div>
    <?php
    portal_end();
    exit;
}


/* =====================================================
   BERANDA
===================================================== */

function page_pengepul_dashboard(): void
{
    $u  = portal_user('pengepul');
    $id = (int)$u['id_user'];

    pengepul_require_table($u);

    $sum = db_row(
        "SELECT COALESCE(SUM(berat_kg), 0) AS berat,
                COALESCE(SUM(total_rp), 0) AS total
         FROM pembelian
         WHERE id_user = ? AND status = 'disetujui'",
        'i',
        [$id]
    ) ?? [];

    $pending = (int)((db_row(
        "SELECT COUNT(*) AS n FROM pembelian WHERE id_user = ? AND status = 'pending'",
        'i',
        [$id]
    ) ?? [])['n'] ?? 0);

    $stok = db_all(
        "SELECT jenis_sampah, harga_per_kg, stok_kg
         FROM sampah
         ORDER BY stok_kg DESC, jenis_sampah ASC"
    );

    $totalStok = 0;
    foreach ($stok as $s) {
        $totalStok += (float)$s['stok_kg'];
    }

    portal_start($u, 'pengepul', 'dashboard', 'Beranda');
    ?>

    <div class="page-head">
        <h1>Halo, <?= e($u['nama']) ?></h1>
        <p>Stok sampah yang tersedia di bank sampah.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat"><div class="k">Total stok tersedia</div><div class="v"><?= e(kg($totalStok)) ?> kg</div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat"><div class="k">Sudah dibeli</div><div class="v"><?= e(kg($sum['berat'] ?? 0)) ?> kg</div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat"><div class="k">Nilai pembelian</div><div class="v"><?= e(rupiah($sum['total'] ?? 0)) ?></div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat"><div class="k">Menunggu persetujuan</div><div class="v"><?= $pending ?></div></div>
        </div>
    </div>

    <div class="card-x">
        <div class="head">
            Stok per jenis
            <a href="beli.php" class="btn-accent" style="padding:7px 14px;font-size:13px;text-decoration:none">
                <i class="bi bi-bag-plus me-1"></i> Ajukan pembelian
            </a>
        </div>
        <div class="table-wrap">
            <table class="tbl">
                <thead>
                    <tr><th>Jenis sampah</th><th class="num">Harga / kg</th><th class="num">Stok</th></tr>
                </thead>
                <tbody>
                <?php if ($stok): foreach ($stok as $s): ?>
                    <tr>
                        <td><?= e($s['jenis_sampah']) ?></td>
                        <td class="num"><?= e(rupiah($s['harga_per_kg'])) ?></td>
                        <td class="num fw-semibold"><?= e(kg($s['stok_kg'])) ?> kg</td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="3"><div class="empty"><i class="bi bi-inbox"></i>Belum ada data sampah.</div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php
    portal_end();
}


/* =====================================================
   PEMBELIAN
===================================================== */

function page_pengepul_beli(): void
{
    global $conn;

    $u  = portal_user('pengepul');
    $id = (int)$u['id_user'];

    pengepul_require_table($u);

    /* ---------- PROSES PENGAJUAN ---------- */

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajukan'])) {

        $idSampah = (int)($_POST['id_sampah'] ?? 0);
        $berat    = round((float)($_POST['berat_kg'] ?? 0), 2);

        $s = db_row(
            "SELECT id_sampah, jenis_sampah, harga_per_kg, stok_kg FROM sampah WHERE id_sampah = ? LIMIT 1",
            'i',
            [$idSampah]
        );

        if (!csrf_valid()) {

            flash_set('danger', 'Sesi formulir tidak valid. Silakan coba lagi.');

        } elseif (!$s) {

            flash_set('danger', 'Jenis sampah tidak ditemukan.');

        } elseif ($berat <= 0) {

            flash_set('danger', 'Berat pembelian harus lebih dari 0.');

        } elseif ($berat > (float)$s['stok_kg']) {

            flash_set('danger', 'Stok ' . $s['jenis_sampah'] . ' hanya tersedia ' . kg($s['stok_kg']) . ' kg.');

        } else {

            $harga = (int)$s['harga_per_kg'];
            $total = (int)round($berat * $harga);

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO pembelian (id_user, id_sampah, berat_kg, harga_per_kg, total_rp, status, tanggal)
                 VALUES (?, ?, ?, ?, ?, 'pending', NOW())"
            );
            mysqli_stmt_bind_param($stmt, 'iidii', $id, $idSampah, $berat, $harga, $total);
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($ok) {
                flash_set('success', 'Pengajuan pembelian berhasil dikirim dan menunggu persetujuan admin.');
            } else {
                flash_set('danger', 'Pengajuan pembelian gagal disimpan.');
            }
        }

        header('Location: beli.php');
        exit;
    }

    /* ---------- DATA ---------- */

    $jenis = db_all(
        "SELECT id_sampah, jenis_sampah, harga_per_kg, stok_kg
         FROM sampah
         WHERE stok_kg > 0
         ORDER BY jenis_sampah ASC"
    );

    $riwayat = db_all(
        "SELECT p.berat_kg, p.harga_per_kg, p.total_rp, p.status, p.tanggal, s.jenis_sampah
         FROM pembelian p
         LEFT JOIN sampah s ON p.id_sampah = s.id_sampah
         WHERE p.id_user = ?
         ORDER BY p.id_beli DESC
         LIMIT 50",
        'i',
        [$id]
    );

    portal_start($u, 'pengepul', 'beli', 'Pembelian');
    ?>

    <div class="page-head">
        <h1>Pembelian sampah</h1>
        <p>Ajukan pembelian dari stok yang tersedia. Stok berkurang setelah admin menyetujui.</p>
    </div>

    <?php portal_flash(); ?>

    <div class="row g-3">

        <div class="col-lg-5">
            <div class="card-x">
                <div class="head">Ajukan pembelian</div>
                <div class="body">

                    <?php if (!$jenis): ?>

                        <div class="empty"><i class="bi bi-inbox"></i>Belum ada stok yang tersedia.</div>

                    <?php else: ?>

                    <form method="POST" autocomplete="off">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label" for="id_sampah">Jenis sampah</label>
                            <select name="id_sampah" id="id_sampah" class="form-select" required>
                                <option value="">-- Pilih jenis --</option>
                                <?php foreach ($jenis as $j): ?>
                                    <option value="<?= (int)$j['id_sampah'] ?>"
                                            data-harga="<?= (int)$j['harga_per_kg'] ?>"
                                            data-stok="<?= e((float)$j['stok_kg']) ?>">
                                        <?= e($j['jenis_sampah']) ?> (stok <?= e(kg($j['stok_kg'])) ?> kg)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="berat_kg">Berat (kg)</label>
                            <input type="number" name="berat_kg" id="berat_kg" class="form-control"
                                   min="0.01" step="0.01" placeholder="Contoh: 25" required>
                            <div class="hint" id="infoStok">Pilih jenis sampah terlebih dahulu.</div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center p-3 mb-3"
                             style="background:var(--soft);border-radius:10px">
                            <span class="text-muted">Perkiraan total</span>
                            <strong id="totalPerkiraan">Rp 0</strong>
                        </div>

                        <button type="submit" name="ajukan" class="btn-accent w-100">
                            <i class="bi bi-send me-1"></i> Kirim pengajuan
                        </button>
                    </form>

                    <?php endif; ?>

                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card-x">
                <div class="head">Riwayat pembelian</div>
                <div class="table-wrap">
                    <table class="tbl">
                        <thead>
                            <tr><th>Tanggal</th><th>Jenis</th><th class="num">Berat</th><th class="num">Total</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                        <?php if ($riwayat): foreach ($riwayat as $r): ?>
                            <tr>
                                <td><?= e(tgl($r['tanggal'])) ?></td>
                                <td><?= e($r['jenis_sampah'] ?? '-') ?></td>
                                <td class="num"><?= e(kg($r['berat_kg'])) ?> kg</td>
                                <td class="num"><?= e(rupiah($r['total_rp'])) ?></td>
                                <td><?= status_badge((string)$r['status']) ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="5"><div class="empty"><i class="bi bi-inbox"></i>Belum ada pembelian.</div></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script>
    (function () {
        var sel   = document.getElementById('id_sampah');
        var berat = document.getElementById('berat_kg');
        var info  = document.getElementById('infoStok');
        var total = document.getElementById('totalPerkiraan');
        if (!sel) return;

        function rp(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); }

        function update() {
            var opt = sel.options[sel.selectedIndex];
            var harga = parseFloat(opt.getAttribute('data-harga') || 0);
            var stok  = parseFloat(opt.getAttribute('data-stok') || 0);
            var b     = parseFloat(berat.value || 0);

            if (sel.value) {
                berat.max = stok;
                info.textContent = 'Harga ' + rp(harga) + ' per kg, stok tersedia ' + stok + ' kg.';
            } else {
                berat.removeAttribute('max');
                info.textContent = 'Pilih jenis sampah terlebih dahulu.';
            }

            total.textContent = rp(harga * (isNaN(b) ? 0 : b));
        }

        sel.addEventListener('change', update);
        berat.addEventListener('input', update);
    })();
    </script>

    <?php
    portal_end();
}
