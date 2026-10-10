<?php if (!empty($auth)): ?>
  </div></div></section>
<?php else: ?>
</main></div>
<div class="ov" id="m-del"><div class="glass modal"><h3>Konfirmasi</h3>
  <p style="margin-top:8px;color:var(--mut)">Hapus data ini? Tindakan ini tidak dapat dibatalkan.</p>
  <div class="acts"><button type="button" class="btn" onclick="closeM()">Batal</button><button type="button" class="btn gold" onclick="_f.submit()">Ya, hapus</button></div></div></div>
<div class="ov" id="m-pass"><form method="post" action="<?= BASE ?>pages/profil.php" class="glass modal"><?= field() ?>
  <input type="hidden" name="aksi" value="password"><h3>Ubah Password</h3>
  <label>Password lama *</label><input name="lama" type="password" required>
  <label>Password baru *</label><input name="baru" type="password" required minlength="6" placeholder="Minimal 6 karakter">
  <label>Konfirmasi password baru *</label><input name="konfirmasi" type="password" required>
  <div class="acts"><button type="button" class="btn" onclick="closeM()">Batal</button><button class="btn gold">Simpan</button></div></form></div>
<?php endif; ?>
<div class="glass" id="toast"></div>
<script src="<?= BASE ?>assets/js/app.js"></script>
<?php if (!empty($_SESSION['flash'])) { [$fm, $fb] = $_SESSION['flash']; unset($_SESSION['flash']);
  echo '<script>toast(' . json_encode($fm) . ',' . ($fb ? 'true' : 'false') . ')</script>'; } ?>
</body>
</html>
