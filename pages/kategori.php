<?php
require_once '../config/koneksi.php'; wajib();
$uid = $_SESSION['uid']; $JN = ['Pemasukan', 'Pengeluaran'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {               // FR-03, FR-04
    cek();
    $aksi = $_POST['aksi'] ?? ''; $id = (int)($_POST['id'] ?? 0); $n = trim($_POST['nama'] ?? ''); $j = $_POST['jenis'] ?? '';
    if (!in_array($j, $JN)) $j = 'Pemasukan';
    if ($aksi === 'hapus') {
        $c = $pdo->prepare('SELECT COUNT(*) FROM transaksi WHERE id_kategori=? AND id_user=?'); $c->execute([$id, $uid]);
        if ($c->fetchColumn() > 0) flash('Kategori masih dipakai transaksi, tidak dapat dihapus.', true);   // NFR-04
        else { $pdo->prepare('DELETE FROM kategori WHERE id_kategori=? AND id_user=?')->execute([$id, $uid]); flash('Kategori berhasil dihapus.'); }
    } elseif ($n === '' || mb_strlen($n) > 50) flash('Nama kategori wajib diisi (maks. 50 karakter).', true);   // NFR-01
    elseif ($id) { $pdo->prepare('UPDATE kategori SET nama_kategori=?, jenis=? WHERE id_kategori=? AND id_user=?')->execute([$n, $j, $id, $uid]); flash('Kategori berhasil diubah.'); }
    else { $pdo->prepare('INSERT INTO kategori(id_user,nama_kategori,jenis) VALUES(?,?,?)')->execute([$uid, $n, $j]); flash('Kategori berhasil ditambahkan.'); }
    go('pages/kategori.php?jenis=' . $j);
}
$jenis = in_array($_GET['jenis'] ?? '', $JN) ? $_GET['jenis'] : 'Pemasukan'; $q = trim($_GET['q'] ?? '');
$c = $pdo->prepare('SELECT COUNT(*) FROM kategori WHERE id_user=? AND jenis=? AND nama_kategori LIKE ?'); $c->execute([$uid, $jenis, "%$q%"]);
[$off, $pg] = pager($c->fetchColumn());
$s = $pdo->prepare("SELECT k.*, COALESCE(SUM(t.nominal),0) total FROM kategori k LEFT JOIN transaksi t ON t.id_kategori=k.id_kategori
  WHERE k.id_user=? AND k.jenis=? AND k.nama_kategori LIKE ? GROUP BY k.id_kategori ORDER BY k.nama_kategori LIMIT 10 OFFSET $off");
$s->execute([$uid, $jenis, "%$q%"]); $rows = $s->fetchAll();
$hal = 'kategori'; $judul = 'Kategori';
require '../includes/header.php'; ?>
<div class="glass pad">
  <form class="bar" method="get"><input type="hidden" name="jenis" value="<?= e($jenis) ?>">
    <div class="seg"><?php foreach ($JN as $j): ?><a class="<?= $j === $jenis ? 'on' : '' ?>" href="?jenis=<?= $j ?>"><?= $j ?></a><?php endforeach; ?></div>
    <input name="q" placeholder="Cari kategori" value="<?= e($q) ?>"><button class="btn">Cari</button>
    <button type="button" class="btn gold" onclick="openM('m-kat',this)" data-d="<?= e(json_encode(['jenis' => $jenis])) ?>">+ Tambah Kategori</button>
  </form>
  <div class="tw"><table>
    <thead><tr><th>No</th><th>Nama Kategori</th><th class="n">Total Transaksi</th><th class="n">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $i => $r): ?>
      <tr><td><?= $off + $i + 1 ?></td><td><?= e($r['nama_kategori']) ?></td><td class="n"><?= rp($r['total']) ?></td>
        <td class="n"><button type="button" class="btn sm" onclick="openM('m-kat',this)" data-d="<?= e(json_encode(['id' => $r['id_kategori'], 'nama' => $r['nama_kategori'], 'jenis' => $r['jenis']])) ?>">Edit</button>
          <form method="post" style="display:inline"><?= field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $r['id_kategori'] ?>"><input type="hidden" name="jenis" value="<?= e($jenis) ?>">
            <button type="button" class="btn sm dng" onclick="hapus(this)">Hapus</button></form></td></tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="4" class="empty">Data tidak ditemukan.</td></tr><?php endif; ?>
    </tbody></table></div>
  <?= $pg ?>
</div>
<div class="ov" id="m-kat"><form method="post" class="glass modal"><?= field() ?><input type="hidden" name="id">
  <h3 data-add="Tambah Kategori" data-edit="Ubah Kategori">Tambah Kategori</h3>
  <label>Nama kategori *</label><input name="nama" required maxlength="50" placeholder="Masukkan nama kategori">
  <label>Jenis *</label><select name="jenis"><option>Pemasukan</option><option>Pengeluaran</option></select>
  <div class="acts"><button type="button" class="btn" onclick="closeM()">Batal</button><button class="btn gold">Simpan</button></div></form></div>
<?php require '../includes/footer.php';
