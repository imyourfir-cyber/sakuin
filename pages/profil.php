<?php
require_once '../config/koneksi.php'; wajib();
$uid = $_SESSION['uid'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek();
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'profil') {
        $n = trim($_POST['nama'] ?? ''); $em = trim($_POST['email'] ?? ''); $nim = trim($_POST['nim'] ?? '');
        if ($n === '' || mb_strlen($n) > 100) flash('Nama lengkap wajib diisi (maks. 100 karakter).', true);
        elseif ($em !== '' && !filter_var($em, FILTER_VALIDATE_EMAIL)) flash('Format email belum sesuai.', true);
        elseif (mb_strlen($nim) > 20) flash('NIM maksimal 20 karakter.', true);
        else { $pdo->prepare('UPDATE users SET nama=?, email=?, nim=? WHERE id_user=?')->execute([$n, $em ?: null, $nim ?: null, $uid]); flash('Profil berhasil disimpan.'); }
    } elseif ($aksi === 'password') {
        $u = user(); $baru = $_POST['baru'] ?? '';
        if (!password_verify($_POST['lama'] ?? '', $u['password'])) flash('Password lama salah.', true);
        elseif (strlen($baru) < 6) flash('Password baru minimal 6 karakter.', true);
        elseif ($baru !== ($_POST['konfirmasi'] ?? '')) flash('Konfirmasi password tidak sama.', true);
        else { $pdo->prepare('UPDATE users SET password=? WHERE id_user=?')->execute([password_hash($baru, PASSWORD_DEFAULT), $uid]); flash('Password berhasil diubah.'); }
    } elseif ($aksi === 'foto' && isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['foto']['tmp_name']);
        $x = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
        if (!$x || $_FILES['foto']['size'] > 2097152) flash('Foto harus JPG, PNG, atau WEBP dengan ukuran maksimal 2 MB.', true);
        else {
            $dir = __DIR__ . '/../assets/uploads/'; is_dir($dir) || mkdir($dir, 0755, true);
            $f = "user_$uid.$x"; move_uploaded_file($_FILES['foto']['tmp_name'], $dir . $f);
            $pdo->prepare('UPDATE users SET foto=? WHERE id_user=?')->execute([$f, $uid]); flash('Foto profil berhasil diubah.');
        }
    }
    go('pages/profil.php');
}
$hal = 'profil'; $judul = 'Profil';
require '../includes/header.php';
$r = fn($l, $v) => '<div class="row"><span>' . $l . '</span><b>' . e($v ?: '-') . '</b></div>'; ?>
<div class="glass pad prof"><h3>Profil</h3><hr>
  <div class="pav"><?= av($me, 'big') ?><small>Foto / Avatar Mahasiswa</small>
    <form method="post" enctype="multipart/form-data"><?= field() ?><input type="hidden" name="aksi" value="foto">
      <label class="btn sm" style="display:inline-block;margin:6px 0 0;color:var(--tx);cursor:pointer">Ganti foto<input type="file" name="foto" accept="image/jpeg,image/png,image/webp" hidden onchange="this.form.submit()"></label></form></div>
  <div class="sub">Informasi Akun</div>
  <?= $r('Nama', $me['nama']) . $r('Username', $me['username']) . $r('Email', $me['email']) . $r('NIM (opsional)', $me['nim']) ?>
  <button class="btn gold" onclick="openM('m-prof',this)" data-d="<?= e(json_encode(['nama' => $me['nama'], 'email' => $me['email'], 'nim' => $me['nim']])) ?>">Ubah Profil</button>
</div>
<div class="ov" id="m-prof"><form method="post" class="glass modal"><?= field() ?><input type="hidden" name="aksi" value="profil"><h3>Ubah Profil</h3>
  <label>Nama lengkap *</label><input name="nama" required maxlength="100">
  <label>Username</label><input value="<?= e($me['username']) ?>" disabled>
  <label>Email</label><input name="email" type="email" placeholder="nama@email.com">
  <label>NIM (opsional)</label><input name="nim" maxlength="20" placeholder="Masukkan NIM">
  <div class="acts"><button type="button" class="btn" onclick="closeM()">Batal</button><button class="btn gold">Simpan</button></div></form></div>
<?php require '../includes/footer.php';
