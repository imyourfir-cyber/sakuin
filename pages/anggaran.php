<?php
require_once '../config/koneksi.php'; wajib();
$uid = $_SESSION['uid'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek();
    if (($_POST['aksi'] ?? '') === 'hapus') {
        $pdo->prepare('DELETE FROM anggaran WHERE id_anggaran=? AND id_user=?')->execute([(int)($_POST['id'] ?? 0), $uid]);
        flash('Batas pengeluaran dihapus.');
    } else {
        $kid = (int)($_POST['kategori'] ?? 0); $b = $_POST['batas'] ?? '';
        $ck = $pdo->prepare("SELECT COUNT(*) FROM kategori WHERE id_kategori=? AND id_user=? AND jenis='Pengeluaran'"); $ck->execute([$kid, $uid]);
        if ($kid && !$ck->fetchColumn()) flash('Kategori tidak valid.', true);
        elseif (!is_numeric($b) || $b <= 0 || $b > 9999999999) flash('Batas harus berupa angka lebih besar dari 0.', true);   // NFR-01
        else {
            $s = $pdo->prepare('SELECT id_anggaran FROM anggaran WHERE id_user=? AND id_kategori <=> ?'); $s->execute([$uid, $kid ?: null]); $ada = $s->fetchColumn();
            if ($ada) $pdo->prepare('UPDATE anggaran SET batas=? WHERE id_anggaran=?')->execute([$b, $ada]);
            else $pdo->prepare('INSERT INTO anggaran(id_user,id_kategori,batas) VALUES(?,?,?)')->execute([$uid, $kid ?: null, $b]);
            flash('Batas pengeluaran disimpan.');
        }
    }
    go('pages/anggaran.php');
}
$rows = anggaran($uid, date('Y-m-d'));
$ks = $pdo->prepare("SELECT * FROM kategori WHERE id_user=? AND jenis='Pengeluaran' ORDER BY nama_kategori"); $ks->execute([$uid]); $kat = $ks->fetchAll();
$bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][(int)date('n')] . ' ' . date('Y');
$hal = 'anggaran'; $judul = 'Anggaran';
require '../includes/header.php'; ?>
<div class="glass pad bar" style="margin:0"><div><h3 style="font-size:20px">Batas Pengeluaran – <?= $bulan ?></h3><small style="color:var(--mut)">Atur batas total bulanan atau per kategori. Kalau terlampaui, Sakuin akan mengingatkanmu.</small></div>
  <button class="btn gold" onclick="openM('m-ang')">+ Atur Batas</button></div>
<div class="grid2">
<?php foreach ($rows as $r): $pct = $r['pakai'] / $r['batas'] * 100; $cls = $pct > 100 ? 'hot' : ($pct >= 80 ? 'mid' : ''); ?>
  <div class="glass item <?= $pct > 100 ? 'warn' : '' ?>">
    <div class="hd"><b><?= $r['nama_kategori'] ? e($r['nama_kategori']) : 'Total pengeluaran bulanan' ?></b><small><?= round($pct) ?>%</small></div>
    <div class="prog"><i class="<?= $cls ?>" style="width:<?= min(100, $pct) ?>%"></i></div>
    <div class="ft"><span><?= rp($r['pakai']) ?> <small>dari <?= rp($r['batas']) ?></small></span>
      <?= $pct > 100 ? '<b class="cout">Lewat ' . rp($r['pakai'] - $r['batas']) . '</b>' : '<small>Sisa ' . rp($r['batas'] - $r['pakai']) . '</small>' ?></div>
    <div class="ft"><span></span><span>
      <button type="button" class="btn sm" onclick="openM('m-ang',this)" data-d="<?= e(json_encode(['kategori' => $r['id_kategori'] ?? '', 'batas' => (int)$r['batas']])) ?>">Edit</button>
      <form method="post" style="display:inline"><?= field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $r['id_anggaran'] ?>"><button type="button" class="btn sm dng" onclick="hapus(this)">Hapus</button></form></span></div>
  </div>
<?php endforeach; if (!$rows): ?><div class="glass pad empty" style="grid-column:1/-1">Belum ada batas. Klik "+ Atur Batas" untuk membuat batas total bulanan atau per kategori.</div><?php endif; ?>
</div>
<div class="ov" id="m-ang"><form method="post" class="glass modal"><?= field() ?><h3>Atur Batas Pengeluaran</h3>
  <label>Berlaku untuk *</label><select name="kategori"><option value="">Semua pengeluaran (total bulanan)</option><?php foreach ($kat as $k): ?><option value="<?= $k['id_kategori'] ?>"><?= e($k['nama_kategori']) ?></option><?php endforeach; ?></select>
  <label>Batas maksimal per bulan *</label><input type="number" name="batas" min="1" step="1" required placeholder="Rp 0">
  <div class="acts"><button type="button" class="btn" onclick="closeM()">Batal</button><button class="btn gold">Simpan</button></div></form></div>
<?php require '../includes/footer.php';
