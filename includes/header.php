<?php
require_once __DIR__ . '/../config/koneksi.php';
$auth = $auth ?? false;
if (!$auth) { wajib(); $me = user(); }
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul ?? 'Sakuin') ?> – Sakuin</title>
<link rel="icon" href="<?= BASE ?>assets/img/logo.png">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE ?>assets/css/style.css">
</head>
<body>
<script>if(localStorage.tema==='light')document.body.classList.add('light')</script>
<div class="orbs"><i></i><i></i><i></i></div>
<?php if ($auth): ?>
<section id="auth"><div class="glass authbox">
  <div class="brand">
    <img src="<?= BASE ?>assets/img/logo.png" alt="Logo Sakuin">
    <h1>Sakuin</h1><p>Catat. Atur. Hemat.</p>
    <p style="font-size:13px;margin-top:8px">Aplikasi Pencatatan Keuangan Harian Mahasiswa</p>
  </div>
  <div class="authform">
<?php else: ?>
<div id="app" class="on">
<?php include __DIR__ . '/sidebar.php'; ?>
<main>
  <header class="glass top">
    <h2><?= e($judul) ?></h2>
    <div class="user">
      <button class="uname" onclick="toggleDD(event)" aria-label="Menu pengguna"><?= e(explode(' ', $me['nama'])[0]) ?> ▾</button>
      <a href="<?= BASE ?>pages/profil.php" title="Lihat profil"><?= av($me) ?></a>
    </div>
    <div class="glass dd" id="dd">
      <div class="ddh"><?= av($me) ?><div><b><?= e($me['nama']) ?></b><small>@<?= e($me['username']) ?></small></div></div><hr>
      <a href="<?= BASE ?>pages/profil.php">Profil</a>
      <button type="button" onclick="openM('m-pass')">Ubah Password</button>
      <a href="<?= BASE ?>pages/pengaturan.php">Pengaturan</a>
      <a class="dng" href="<?= BASE ?>auth/logout.php">Logout</a>
    </div>
  </header>
<?php endif; ?>
