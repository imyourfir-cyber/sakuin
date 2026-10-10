<?php
require_once '../config/koneksi.php';
if (!empty($_SESSION['uid'])) go('pages/dashboard.php');
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {            // FR-02
    cek();
    $s = $pdo->prepare('SELECT * FROM users WHERE username=?');
    $s->execute([trim($_POST['username'] ?? '')]);
    $u = $s->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = $u['id_user'];
        go('pages/dashboard.php');
    }
    $err = 'Username atau password salah.';
}
$auth = true; $judul = 'Masuk';
require '../includes/header.php'; ?>
<h2>Masuk ke akun</h2>
<form method="post"><?= field() ?>
  <label>Username</label><input name="username" required placeholder="Masukkan username" value="<?= e($_POST['username'] ?? '') ?>">
  <label>Password</label><input name="password" type="password" required placeholder="Masukkan password">
  <div class="err"><?= e($err) ?></div>
  <button class="btn gold">Masuk</button>
</form>
<p class="sw">Belum punya akun? <a href="register.php">Daftar</a></p>
<?php require '../includes/footer.php';
