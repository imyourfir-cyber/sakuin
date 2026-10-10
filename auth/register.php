<?php
require_once '../config/koneksi.php';
if (!empty($_SESSION['uid'])) go('pages/dashboard.php');
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {            // FR-01, NFR-01
    cek();
    $nama = trim($_POST['nama'] ?? ''); $un = trim($_POST['username'] ?? ''); $pw = $_POST['password'] ?? '';
    $c = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username=?'); $c->execute([$un]);
    if ($nama === '' || $un === '' || $pw === '') $err = 'Semua kolom wajib diisi.';
    elseif (mb_strlen($nama) > 100) $err = 'Nama maksimal 100 karakter.';
    elseif (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $un)) $err = 'Username 3-50 karakter: huruf, angka, titik, atau garis bawah.';
    elseif (strlen($pw) < 6) $err = 'Password minimal 6 karakter.';
    elseif ($pw !== ($_POST['konfirmasi'] ?? '')) $err = 'Konfirmasi password tidak sama.';
    elseif ($c->fetchColumn() > 0) $err = 'Username sudah dipakai.';
    else {
        $pdo->prepare('INSERT INTO users(nama,username,password) VALUES(?,?,?)')->execute([$nama, $un, password_hash($pw, PASSWORD_DEFAULT)]);
        $id = $pdo->lastInsertId();
        $i = $pdo->prepare('INSERT INTO kategori(id_user,nama_kategori,jenis) VALUES(?,?,?)');   // kategori awal
        foreach ([['Uang Saku','Pemasukan'],['Beasiswa','Pemasukan'],['Makan','Pengeluaran'],['Transportasi','Pengeluaran']] as [$n, $j]) $i->execute([$id, $n, $j]);
        flash('Akun berhasil dibuat. Silakan masuk.');
        go('auth/login.php');
    }
}
$auth = true; $judul = 'Daftar';
require '../includes/header.php'; ?>
<h2>Buat akun baru</h2>
<form method="post"><?= field() ?>
  <label>Nama lengkap</label><input name="nama" required placeholder="Masukkan nama lengkap" value="<?= e($_POST['nama'] ?? '') ?>">
  <label>Username</label><input name="username" required placeholder="Masukkan username" value="<?= e($_POST['username'] ?? '') ?>">
  <label>Password</label><input name="password" type="password" required placeholder="Masukkan password">
  <label>Konfirmasi password</label><input name="konfirmasi" type="password" required placeholder="Ulangi password">
  <div class="err"><?= e($err) ?></div>
  <button class="btn gold">Daftar</button>
</form>
<p class="sw">Sudah punya akun? <a href="login.php">Masuk</a></p>
<?php require '../includes/footer.php';
