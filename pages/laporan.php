<?php
require_once '../config/koneksi.php'; wajib();
$uid = $_SESSION['uid']; $JN = ['Pemasukan', 'Pengeluaran', 'Tabungan'];
$jenis = in_array($_GET['jenis'] ?? '', $JN) ? $_GET['jenis'] : 'Pemasukan';
$dari = tg($_GET['dari'] ?? ''); $sampai = tg($_GET['sampai'] ?? ''); $kid = (int)($_GET['kategori'] ?? 0);
$w = 't.id_user=?'; $p = [$uid];
if ($dari) { $w .= ' AND t.tanggal>=?'; $p[] = $dari; }
if ($sampai) { $w .= ' AND t.tanggal<=?'; $p[] = $sampai; }
$F = "FROM transaksi t JOIN kategori k ON k.id_kategori=t.id_kategori";
$s = $pdo->prepare("SELECT k.jenis, SUM(t.nominal) $F WHERE $w GROUP BY k.jenis"); $s->execute($p); $tot = $s->fetchAll(PDO::FETCH_KEY_PAIR);   // FR-11
$w .= ' AND k.jenis=?'; $p[] = $jenis;
if ($kid) { $w .= ' AND k.id_kategori=?'; $p[] = $kid; }
$s = $pdo->prepare("SELECT t.tanggal, t.keterangan, t.nominal, k.nama_kategori $F WHERE $w ORDER BY t.tanggal, t.id_transaksi"); $s->execute($p);
$rows = $s->fetchAll(); $sum = array_sum(array_column($rows, 'nominal'));                                                                       // FR-09, FR-10
$ks = $pdo->prepare('SELECT * FROM kategori WHERE id_user=? AND jenis=? ORDER BY nama_kategori'); $ks->execute([$uid, $jenis]); $kat = $ks->fetchAll();
$in = $tot['Pemasukan'] ?? 0; $out = $tot['Pengeluaran'] ?? 0;
// Rekap tabungan: riwayat setoran + progres tiap target
$tg = (int)($_GET['target'] ?? 0); $trows = []; $tsum = 0; $tts = [];
if ($jenis === 'Tabungan') {
    $w2 = 's.id_user=?'; $p2 = [$uid];
    if ($dari) { $w2 .= ' AND s.tanggal>=?'; $p2[] = $dari; }
    if ($sampai) { $w2 .= ' AND s.tanggal<=?'; $p2[] = $sampai; }
    if ($tg) { $w2 .= ' AND s.id_tabungan=?'; $p2[] = $tg; }
    $q2 = $pdo->prepare("SELECT s.tanggal, s.nominal, t.nama FROM setoran s JOIN tabungan t ON t.id_tabungan=s.id_tabungan WHERE $w2 ORDER BY s.tanggal, s.id_setoran"); $q2->execute($p2);
    $trows = $q2->fetchAll(); $tsum = array_sum(array_column($trows, 'nominal'));
    $q3 = $pdo->prepare('SELECT t.*, COALESCE((SELECT SUM(nominal) FROM setoran s WHERE s.id_tabungan=t.id_tabungan),0) terkumpul FROM tabungan t WHERE t.id_user=? ORDER BY t.nama'); $q3->execute([$uid]); $tts = $q3->fetchAll();
    [$sisa, $totalTab] = saldo($uid);
}
$hal = 'laporan'; $judul = 'Laporan Keuangan';
require '../includes/header.php'; ?>
<div class="glass pad">
  <form class="bar" method="get"><input type="hidden" name="jenis" value="<?= e($jenis) ?>">
    <div class="seg"><?php foreach ($JN as $j): ?><a class="<?= $j === $jenis ? 'on' : '' ?>" href="?<?= e(http_build_query(['jenis' => $j, 'dari' => $dari, 'sampai' => $sampai])) ?>"><?= $j ?></a><?php endforeach; ?></div>
    <input type="date" name="dari" value="<?= e($dari) ?>" title="Dari tanggal"><input type="date" name="sampai" value="<?= e($sampai) ?>" title="Sampai tanggal">
    <?php if ($jenis === 'Tabungan'): ?><select name="target"><option value="">Semua Target</option><?php foreach ($tts as $k): ?><option value="<?= $k['id_tabungan'] ?>" <?= $k['id_tabungan'] == $tg ? 'selected' : '' ?>><?= e($k['nama']) ?></option><?php endforeach; ?></select>
    <?php else: ?><select name="kategori"><option value="">Semua Kategori</option><?php foreach ($kat as $k): ?><option value="<?= $k['id_kategori'] ?>" <?= $k['id_kategori'] == $kid ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option><?php endforeach; ?></select><?php endif; ?>
    <button class="btn gold">Tampilkan</button>
  </form>
</div>
<?php if ($jenis === 'Tabungan'): ?>
<div class="cards">
  <div class="glass stat"><small>Total Disetor (periode ini)</small><b class="cin"><?= rp($tsum) ?></b></div>
  <div class="glass stat"><small>Total Tabungan (keseluruhan)</small><b><?= rp($totalTab) ?></b></div>
  <div class="glass stat sal"><small>Saldo Tersedia</small><b><?= rp($sisa) ?></b></div>
</div>
<div class="glass pad"><h3 style="margin-bottom:10px">Progres per Target</h3>
  <div class="tw"><table><thead><tr><th>No</th><th>Target</th><th class="n">Terkumpul</th><th class="n">Target</th><th class="n">Progres</th></tr></thead><tbody>
  <?php foreach ($tts as $i => $r): ?><tr><td><?= $i + 1 ?></td><td><?= e($r['nama']) ?></td><td class="n"><?= rp($r['terkumpul']) ?></td><td class="n"><?= rp($r['target']) ?></td><td class="n"><?= min(100, round($r['terkumpul'] / $r['target'] * 100)) ?>%</td></tr>
  <?php endforeach; if (!$tts): ?><tr><td colspan="5" class="empty">Belum ada target tabungan.</td></tr><?php endif; ?></tbody></table></div></div>
<div class="glass pad"><h3 style="margin-bottom:10px">Rekap Tabungan</h3>
  <div class="tw"><table><thead><tr><th>No</th><th>Tanggal</th><th>Target</th><th class="n">Nominal</th></tr></thead><tbody>
  <?php foreach ($trows as $i => $r): ?><tr><td><?= $i + 1 ?></td><td><?= tgl($r['tanggal']) ?></td><td><?= e($r['nama']) ?></td><td class="n"><?= rp($r['nominal']) ?></td></tr>
  <?php endforeach; if (!$trows): ?><tr><td colspan="4" class="empty">Data tidak ditemukan.</td></tr><?php endif; ?></tbody>
  <tfoot><tr><td colspan="3" class="n">Total</td><td class="n"><?= rp($tsum) ?></td></tr></tfoot></table></div></div>
<?php else: ?>
<div class="cards">
  <div class="glass stat"><small>Total Pemasukan</small><b class="cin"><?= rp($in) ?></b></div>
  <div class="glass stat"><small>Total Pengeluaran</small><b class="cout"><?= rp($out) ?></b></div>
  <div class="glass stat sal"><small>Saldo</small><b><?= rp($in - $out) ?></b></div>
</div>
<div class="glass pad"><h3 style="margin-bottom:10px">Rekap <?= $jenis ?></h3>
  <div class="tw"><table>
    <thead><tr><th>No</th><th>Tanggal</th><th>Kategori</th><th>Keterangan</th><th class="n">Nominal</th></tr></thead><tbody>
    <?php foreach ($rows as $i => $r): ?>
      <tr><td><?= $i + 1 ?></td><td><?= tgl($r['tanggal']) ?></td><td><?= e($r['nama_kategori']) ?></td><td><?= e($r['keterangan'] ?: '-') ?></td><td class="n"><?= rp($r['nominal']) ?></td></tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="5" class="empty">Data tidak ditemukan.</td></tr><?php endif; ?>
    </tbody><tfoot><tr><td colspan="4" class="n">Total</td><td class="n"><?= rp($sum) ?></td></tr></tfoot></table></div>
</div>
<?php endif; ?>
<?php require '../includes/footer.php';
