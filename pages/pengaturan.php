<?php
require_once '../config/koneksi.php'; wajib();
$hal = 'pengaturan'; $judul = 'Pengaturan';
require '../includes/header.php'; ?>
<div class="glass pad prof"><h3>Pengaturan</h3><hr>
  <div class="row"><span>Notifikasi</span><span style="display:flex;align-items:center;gap:10px"><b id="nt">ON</b><button class="tg" id="tg" onclick="tglNotif()" aria-label="Notifikasi"><i></i></button></span></div>
  <div class="row"><span>Tampilan</span><div class="seg"><a id="t-dark" href="#" onclick="return setTema('dark')">Dark</a><a id="t-light" href="#" onclick="return setTema('light')">Light</a></div></div>
  <div class="row"><span>Keamanan</span><button class="btn sm" onclick="openM('m-pass')">Ubah Password</button></div>
  <div class="row"><span>Bahasa</span><select><option>Indonesia</option></select></div>
  <a class="btn dng" style="display:block;margin-top:18px" href="<?= BASE ?>auth/logout.php">Logout</a>
</div>
<?php require '../includes/footer.php';
