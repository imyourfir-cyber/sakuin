<?php
require_once '../config/koneksi.php'; wajib();
$uid = $_SESSION['uid'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek();
    $aksi = $_POST['aksi'] ?? ''; $id = (int)($_POST['id'] ?? 0);
    $ck = $pdo->prepare('SELECT * FROM tabungan WHERE id_tabungan=? AND id_user=?'); $ck->execute([$id, $uid]); $tb = $ck->fetch();
    if ($aksi === 'hapus' && $tb) {
        $pdo->prepare('DELETE FROM tabungan WHERE id_tabungan=?')->execute([$id]); flash('Target tabungan dihapus. Uangnya kembali ke saldo.');
    } elseif ($aksi === 'target') {
        $n = trim($_POST['nama'] ?? ''); $tg = $_POST['target'] ?? '';
        if ($n === '' || mb_strlen($n) > 80) flash('Nama target wajib diisi (maks. 80 karakter).', true);
        elseif (!is_numeric($tg) || $tg <= 0 || $tg > 9999999999) flash('Target harus berupa angka lebih besar dari 0.', true);
        elseif ($id && $tb) { $pdo->prepare('UPDATE tabungan SET nama=?, target=? WHERE id_tabungan=?')->execute([$n, $tg, $id]); flash('Target tabungan diubah.'); }
        elseif (!$id) { $pdo->prepare('INSERT INTO tabungan(id_user,nama,target) VALUES(?,?,?)')->execute([$uid, $n, $tg]); flash('Target tabungan dibuat. Semangat nabung!'); }
    } elseif ($aksi === 'setor' && $tb) {
        $t = $_POST['tanggal'] ?? ''; $nom = $_POST['nominal'] ?? ''; $d = DateTime::createFromFormat('!Y-m-d', $t); [$sisa] = saldo($uid);
        if (!$d || $d->format('Y-m-d') !== $t) flash('Tanggal tidak valid.', true);
        elseif (!is_numeric($nom) || $nom <= 0 || $nom > 9999999999) flash('Nominal harus berupa angka lebih besar dari 0.', true);   // NFR-01
        elseif ($nom > $sisa) flash('Saldo tidak cukup. Saldo tersedia ' . rp($sisa) . '.', true);
        else {
            $sm = $pdo->prepare('SELECT COALESCE(SUM(nominal),0) FROM setoran WHERE id_tabungan=?'); $sm->execute([$id]); $sebelum = $sm->fetchColumn();
            $pdo->prepare('INSERT INTO setoran(id_tabungan,id_user,tanggal,nominal) VALUES(?,?,?,?)')->execute([$id, $uid, $t, $nom]);
            if ($sebelum < $tb['target'] && $sebelum + $nom >= $tb['target']) flash('Selamat! Target "' . $tb['nama'] . '" tercapai. Kamu keren, terus nabung ya!');
            else flash('Setoran berhasil. Makin dekat ke targetmu!');
        }
    }
    go('pages/tabungan.php');
}
$s = $pdo->prepare('SELECT t.*, COALESCE((SELECT SUM(nominal) FROM setoran s WHERE s.id_tabungan=t.id_tabungan),0) terkumpul FROM tabungan t WHERE t.id_user=? ORDER BY t.id_tabungan DESC');
$s->execute([$uid]); $rows = $s->fetchAll(); [$sisa, $total] = saldo($uid);
$hal = 'tabungan'; $judul = 'Tabungan';
require '../includes/header.php'; ?>
<div class="cards" style="grid-template-columns:1fr 1fr 1fr">
  <div class="glass stat"><small>Total Tabungan</small><b class="cin"><?= rp($total) ?></b></div>
  <div class="glass stat sal"><small>Saldo Tersedia</small><b><?= rp($sisa) ?></b></div>
  <div class="glass stat" style="display:grid;place-items:center;gap:8px"><button class="btn gold" onclick="openM('m-tgt')">+ Target Baru</button><a class="btn sm" href="laporan.php?jenis=Tabungan">Lihat Rekap</a></div>
</div>
<div class="grid2">
<?php foreach ($rows as $r): $pct = min(100, $r['terkumpul'] / $r['target'] * 100); ?>
  <div class="glass item">
    <div class="hd"><b><?= e($r['nama']) ?></b><small><?= round($pct) ?>%<?= $pct >= 100 ? ' · Tercapai' : '' ?></small></div>
    <div class="prog"><i class="<?= $pct >= 100 ? 'mid' : '' ?>" style="width:<?= $pct ?>%"></i></div>
    <div class="ft"><span><?= rp($r['terkumpul']) ?> <small>dari <?= rp($r['target']) ?></small></span><small>Kurang <?= rp(max(0, $r['target'] - $r['terkumpul'])) ?></small></div>
    <div class="ft"><button type="button" class="btn gold sm" onclick="openM('m-setor',this)" data-d="<?= e(json_encode(['id' => $r['id_tabungan'], 'nama' => $r['nama'], 'tanggal' => date('Y-m-d')])) ?>">Nabung</button><span>
      <button type="button" class="btn sm" onclick="openM('m-tgt',this)" data-d="<?= e(json_encode(['id' => $r['id_tabungan'], 'nama' => $r['nama'], 'target' => (int)$r['target']])) ?>">Edit</button>
      <form method="post" style="display:inline"><?= field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $r['id_tabungan'] ?>"><button type="button" class="btn sm dng" onclick="hapus(this)">Hapus</button></form></span></div>
  </div>
<?php endforeach; if (!$rows): ?><div class="glass pad empty" style="grid-column:1/-1">Belum ada target tabungan. Buat satu, misalnya "Laptop baru" atau "Dana darurat".</div><?php endif; ?>
</div>
<div class="ov" id="m-tgt"><form method="post" class="glass modal"><?= field() ?><input type="hidden" name="aksi" value="target"><input type="hidden" name="id">
  <h3 data-add="Target Tabungan Baru" data-edit="Ubah Target Tabungan">Target Tabungan Baru</h3>
  <label>Nama target *</label><input name="nama" required maxlength="80" placeholder="Contoh: Laptop baru">
  <label>Jumlah target *</label><input type="number" name="target" min="1" step="1" required placeholder="Rp 0">
  <div class="acts"><button type="button" class="btn" onclick="closeM()">Batal</button><button class="btn gold">Simpan</button></div></form></div>
<div class="ov" id="m-setor"><form method="post" class="glass modal"><?= field() ?><input type="hidden" name="aksi" value="setor"><input type="hidden" name="id">
  <h3>Nabung</h3>
  <label>Target</label><input name="nama" disabled>
  <label>Tanggal *</label><input type="date" name="tanggal" required>
  <label>Nominal *</label><input type="number" name="nominal" min="1" step="1" required placeholder="Rp 0">
  <div class="acts"><button type="button" class="btn" onclick="closeM()">Batal</button><button class="btn gold">Simpan</button></div></form></div>
<?php require '../includes/footer.php';
