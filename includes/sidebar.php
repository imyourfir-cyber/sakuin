<?php
$ic = [
 'dashboard'=>'<path d="M3 11l9-8 9 8v9a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1z"/>',
 'kategori'=>'<path d="M20 12l-8 8-9-9V3h8z"/><circle cx="7.5" cy="7.5" r="1"/>',
 'transaksi'=>'<path d="M7 4v14M3 14l4 4 4-4M17 20V6M13 10l4-4 4 4"/>',
 'laporan'=>'<path d="M4 20V4M4 20h16M8 16v-5M12 16V8M16 16v-3"/>',
 'anggaran'=>'<path d="M4 16a8 8 0 1116 0"/><path d="M12 16l4-5"/>',
 'tabungan'=>'<ellipse cx="12" cy="6" rx="7" ry="3"/><path d="M5 6v6c0 1.7 3 3 7 3s7-1.3 7-3V6M5 12v6c0 1.7 3 3 7 3s7-1.3 7-3v-6"/>',
 'keluar'=>'<path d="M9 4H5a1 1 0 00-1 1v14a1 1 0 001 1h4M16 8l4 4-4 4M20 12H9"/>'];
$nav = ['dashboard'=>'Dashboard','kategori'=>'Kategori','transaksi'=>'Transaksi','anggaran'=>'Anggaran','tabungan'=>'Tabungan','laporan'=>'Laporan'];
?>
<aside class="glass">
  <div class="logo"><img src="<?= BASE ?>assets/img/logo.png" alt=""><b>Sakuin</b></div>
  <?php foreach ($nav as $k => $n): ?>
  <a class="nav <?= ($hal ?? '') === $k ? 'on' : '' ?>" href="<?= BASE ?>pages/<?= $k ?>.php"><svg viewBox="0 0 24 24"><?= $ic[$k] ?></svg><span><?= $n ?></span></a>
  <?php endforeach; ?>
  <div class="sp"></div>
  <a class="nav" href="<?= BASE ?>auth/logout.php"><svg viewBox="0 0 24 24"><?= $ic['keluar'] ?></svg><span>Keluar</span></a>
</aside>
