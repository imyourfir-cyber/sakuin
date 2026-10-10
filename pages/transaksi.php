<?php
require_once '../config/koneksi.php'; wajib();
$uid = $_SESSION['uid']; $JN = ['Pemasukan', 'Pengeluaran'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {               // FR-05, FR-06
    cek();
    $id = (int)($_POST['id'] ?? 0);
    if (($_POST['aksi'] ?? '') === 'hapus') {
        $pdo->prepare('DELETE FROM transaksi WHERE id_transaksi=? AND id_user=?')->execute([$id, $uid]); flash('Transaksi berhasil dihapus.');
    } else {
        $t = $_POST['tanggal'] ?? ''; $kid = (int)($_POST['kategori'] ?? 0); $nom = $_POST['nominal'] ?? ''; $ket = trim($_POST['keterangan'] ?? '');
        $d = DateTime::createFromFormat('!Y-m-d', $t);
        $ck = $pdo->prepare('SELECT COUNT(*) FROM kategori WHERE id_kategori=? AND id_user=?'); $ck->execute([$kid, $uid]);
        if (!$d || $d->format('Y-m-d') !== $t || !$ck->fetchColumn()) flash('Tanggal dan kategori wajib diisi dengan benar.', true);
        elseif (!is_numeric($nom) || $nom <= 0 || $nom > 9999999999) flash('Nominal harus berupa angka lebih besar dari 0.', true);   // NFR-01
        elseif (mb_strlen($ket) > 200) flash('Keterangan maksimal 200 karakter.', true);
        elseif ($id) { $pdo->prepare('UPDATE transaksi SET id_kategori=?, tanggal=?, nominal=?, keterangan=? WHERE id_transaksi=? AND id_user=?')->execute([$kid, $t, $nom, $ket, $id, $uid]); $w2 = cekAnggaran($uid, $kid, $t); flash('Transaksi berhasil diubah.' . ($w2 ? ' ' . $w2 : ''), (bool)$w2); }
        else { $pdo->prepare('INSERT INTO transaksi(id_user,id_kategori,tanggal,nominal,keterangan) VALUES(?,?,?,?,?)')->execute([$uid, $kid, $t, $nom, $ket]); $w2 = cekAnggaran($uid, $kid, $t); flash('Transaksi berhasil ditambahkan.' . ($w2 ? ' ' . $w2 : ''), (bool)$w2); }
    }
    go('pages/transaksi.php');
}
// FR-07, FR-08: pencarian & filter
$q = trim($_GET['q'] ?? ''); $jn = in_array($_GET['jenis'] ?? '', $JN) ? $_GET['jenis'] : ''; $kf = (int)($_GET['kategori'] ?? 0);
$dari = tg($_GET['dari'] ?? ''); $sampai = tg($_GET['sampai'] ?? '');
$w = 't.id_user=?'; $p = [$uid];
if ($q !== '') { $w .= ' AND (t.keterangan LIKE ? OR k.nama_kategori LIKE ?)'; array_push($p, "%$q%", "%$q%"); }
if ($jn) { $w .= ' AND k.jenis=?'; $p[] = $jn; }
if ($kf) { $w .= ' AND k.id_kategori=?'; $p[] = $kf; }
if ($dari) { $w .= ' AND t.tanggal>=?'; $p[] = $dari; }
if ($sampai) { $w .= ' AND t.tanggal<=?'; $p[] = $sampai; }
$F = "FROM transaksi t JOIN kategori k ON k.id_kategori=t.id_kategori WHERE $w";
$c = $pdo->prepare("SELECT COUNT(*) $F"); $c->execute($p); [$off, $pg] = pager($c->fetchColumn());
$s = $pdo->prepare("SELECT t.*, k.nama_kategori, k.jenis $F ORDER BY t.tanggal DESC, t.id_transaksi DESC LIMIT 10 OFFSET $off"); $s->execute($p); $rows = $s->fetchAll();
$ks = $pdo->prepare('SELECT * FROM kategori WHERE id_user=? ORDER BY jenis, nama_kategori'); $ks->execute([$uid]); $kat = $ks->fetchAll();
$hal = 'transaksi'; $judul = 'Transaksi';
require '../includes/header.php'; ?>
<div class="glass pad">
  <form class="bar" method="get">
    <input name="q" placeholder="Cari kata kunci" value="<?= e($q) ?>">
    <select name="jenis"><option value="">Semua Jenis</option><?php foreach ($JN as $j): ?><option <?= $j === $jn ? 'selected' : '' ?>><?= $j ?></option><?php endforeach; ?></select>
    <select name="kategori"><option value="">Semua Kategori</option><?php foreach ($kat as $k): ?><option value="<?= $k['id_kategori'] ?>" <?= $k['id_kategori'] == $kf ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option><?php endforeach; ?></select>
    <input type="date" name="dari" value="<?= e($dari) ?>" title="Dari tanggal"><input type="date" name="sampai" value="<?= e($sampai) ?>" title="Sampai tanggal">
    <button class="btn">Filter</button>
    <button type="button" class="btn gold" onclick="openM('m-trx',this)" data-d="<?= e(json_encode(['tanggal' => date('Y-m-d')])) ?>">+ Tambah Transaksi</button>
  </form>
  <div class="tw"><table>
    <thead><tr><th>No</th><th>Tanggal</th><th>Jenis</th><th>Kategori</th><th class="n">Nominal</th><th>Keterangan</th><th class="n">Aksi</th></tr></thead><tbody>
    <?php foreach ($rows as $i => $r): $m = $r['jenis'] === 'Pemasukan'; ?>
      <tr><td><?= $off + $i + 1 ?></td><td><?= tgl($r['tanggal']) ?></td><td><span class="tag <?= $m ? 'i' : 'o' ?>"><?= $r['jenis'] ?></span></td><td><?= e($r['nama_kategori']) ?></td><td class="n"><?= rp($r['nominal']) ?></td><td><?= e($r['keterangan'] ?: '-') ?></td>
        <td class="n"><button type="button" class="btn sm" onclick="openM('m-trx',this)" data-d="<?= e(json_encode(['id' => $r['id_transaksi'], 'tanggal' => $r['tanggal'], 'jenis' => $r['jenis'], 'kategori' => $r['id_kategori'], 'nominal' => (int)$r['nominal'], 'keterangan' => $r['keterangan']])) ?>">Edit</button>
          <form method="post" style="display:inline"><?= field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $r['id_transaksi'] ?>"><button type="button" class="btn sm dng" onclick="hapus(this)">Hapus</button></form></td></tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="7" class="empty">Data tidak ditemukan.</td></tr><?php endif; ?>
    </tbody></table></div>
  <?= $pg ?>
</div>
<div class="ov" id="m-trx"><form method="post" class="glass modal"><?= field() ?><input type="hidden" name="id">
  <h3 data-add="Tambah Transaksi" data-edit="Ubah Transaksi">Tambah Transaksi</h3>
  <label>Tanggal *</label><input type="date" name="tanggal" required>
  <label>Jenis *</label><select name="jenis" onchange="filterKat(this.form)"><option>Pemasukan</option><option>Pengeluaran</option></select>
  <label>Kategori *</label><select name="kategori" required><?php foreach ($kat as $k): ?><option value="<?= $k['id_kategori'] ?>" data-j="<?= $k['jenis'] ?>"><?= e($k['nama_kategori']) ?></option><?php endforeach; ?></select>
  <label>Nominal *</label><input type="number" name="nominal" min="1" step="1" required placeholder="Rp 0">
  <label>Keterangan</label><textarea name="keterangan" rows="2" maxlength="200" placeholder="Tulis keterangan"></textarea>
  <div class="acts"><button type="button" class="btn" onclick="closeM()">Batal</button><button class="btn gold">Simpan</button></div></form></div>
<?php require '../includes/footer.php';
