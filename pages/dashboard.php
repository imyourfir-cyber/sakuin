<?php
require_once '../config/koneksi.php'; wajib();
$uid = $_SESSION['uid'];
$a = date('Y-m-01'); $b = date('Y-m-t');
$q = "FROM transaksi t JOIN kategori k ON k.id_kategori=t.id_kategori WHERE t.id_user=?";
// FR-11, NFR-06: semua angka dihitung dari database
$s = $pdo->prepare("SELECT k.jenis, SUM(t.nominal) $q AND t.tanggal BETWEEN ? AND ? GROUP BY k.jenis"); $s->execute([$uid, $a, $b]); $bln = $s->fetchAll(PDO::FETCH_KEY_PAIR);
$s = $pdo->prepare("SELECT k.jenis, SUM(t.nominal) $q GROUP BY k.jenis"); $s->execute([$uid]); $all = $s->fetchAll(PDO::FETCH_KEY_PAIR);
$in = $bln['Pemasukan'] ?? 0; $out = $bln['Pengeluaran'] ?? 0; [$saldo, $tab] = saldo($uid);
$s = $pdo->prepare("SELECT t.tanggal, k.jenis, SUM(t.nominal) n $q AND t.tanggal BETWEEN ? AND ? GROUP BY t.tanggal, k.jenis ORDER BY t.tanggal"); $s->execute([$uid, $a, $b]);
$g = []; foreach ($s as $r) $g[$r['tanggal']][$r['jenis']] = (float)$r['n'];
$mx = 1; foreach ($g as $x) $mx = max($mx, ...array_values($x));
function garis($g, $j, $mx){ $o = []; $i = 0; $n = count($g); foreach ($g as $x) { $o[] = round(18 + $i++ * 564 / max($n - 1, 1)) . ',' . round(172 - ($x[$j] ?? 0) / $mx * 154); } return implode(' ', $o); }
$s = $pdo->prepare("SELECT t.*, k.nama_kategori, k.jenis $q ORDER BY t.tanggal DESC, t.id_transaksi DESC LIMIT 5"); $s->execute([$uid]); $baru = $s->fetchAll();
$lewat = array_filter(anggaran($uid, date('Y-m-d')), fn($r) => $r['pakai'] > $r['batas']);
$hal = 'dashboard'; $judul = 'Dashboard';
require '../includes/header.php'; ?>
<div class="glass pad hello"><h1>Selamat datang, <?= e(explode(' ', $me['nama'])[0]) ?></h1><p>Ringkasan keuangan bulan ini</p></div>
<?php foreach ($lewat as $r): ?>
<div class="glass pad warn"><b>Hey, udah dong!</b> <?= $r['nama_kategori'] ? 'Kategori ' . e($r['nama_kategori']) : 'Pengeluaran bulan ini' ?> sudah lewat batas <?= rp($r['batas']) ?> (kelebihan <?= rp($r['pakai'] - $r['batas']) ?>). <a href="anggaran.php" style="color:var(--gold)">Lihat anggaran</a></div>
<?php endforeach; ?>
<div class="cards">
  <div class="glass stat"><small>Total Pemasukan</small><b class="cin"><?= rp($in) ?></b></div>
  <div class="glass stat"><small>Total Pengeluaran</small><b class="cout"><?= rp($out) ?></b></div>
  <div class="glass stat sal"><small>Saldo (keseluruhan)</small><b><?= rp($saldo) ?></b><small>Sudah dikurangi tabungan <?= rp($tab) ?></small></div>
</div>
<div class="two">
  <div class="glass pad"><h3>Grafik Keuangan</h3>
  <?php if ($g): ?>
    <svg viewBox="0 0 600 190" width="100%"><defs><linearGradient id="gg" x1="0" x2="1"><stop offset="0" stop-color="#f8e6ae"/><stop offset="1" stop-color="#e6bd62"/></linearGradient></defs>
      <line x1="18" y1="172" x2="582" y2="172" stroke="#fff3"/>
      <polyline points="<?= garis($g, 'Pemasukan', $mx) ?>" fill="none" stroke="url(#gg)" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
      <polyline points="<?= garis($g, 'Pengeluaran', $mx) ?>" fill="none" style="stroke:var(--in)" stroke-width="2.5" stroke-dasharray="6 6" stroke-linecap="round"/></svg>
    <div class="leg"><span><i style="background:var(--gold)"></i>Pemasukan (garis tebal)</span><span><i style="background:var(--in)"></i>Pengeluaran (garis putus)</span></div>
  <?php else: ?><div class="empty">Belum ada transaksi bulan ini.</div><?php endif; ?></div>
  <div class="glass pad"><h3>Transaksi Terbaru</h3>
  <?php foreach ($baru as $t): $m = $t['jenis'] === 'Pemasukan'; ?>
    <div class="li"><div><?= e($t['nama_kategori']) ?><small><?= e($t['keterangan'] ?: '-') ?></small></div><b class="<?= $m ? 'cin' : 'cout' ?>"><?= $m ? '+' : '-' ?> <?= rp($t['nominal']) ?></b></div>
  <?php endforeach; if (!$baru): ?><div class="empty">Belum ada transaksi.</div><?php endif; ?></div>
</div>
<?php require '../includes/footer.php';
