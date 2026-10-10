const $ = s => document.querySelector(s);

function toast(m, bad) {                       // NFR-03
  if (localStorage.notif === 'off' && !bad) return;
  const t = $('#toast');
  t.textContent = (bad ? '✕  ' : '✓  ') + m;
  t.style.color = bad ? '#ffd0c6' : '#fff';
  t.classList.add('on');
  clearTimeout(t.h);
  t.h = setTimeout(() => t.classList.remove('on'), 2800 + m.length * 35);
}
function toggleDD(e) { e.stopPropagation(); $('#dd').classList.toggle('on'); }
document.addEventListener('click', e => {
  const d = $('#dd');
  if (d && !e.target.closest('#dd')) d.classList.remove('on');
  if (e.target.classList.contains('ov')) closeM();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeM(); });

// Modal: openM('id') untuk form kosong, openM('id', tombol) untuk mengisi dari data-d (JSON)
function openM(id, el) {
  const o = $('#' + id), f = o.querySelector('form');
  $('#dd')?.classList.remove('on');
  if (f) {
    f.reset();
    const d = el && el.dataset && el.dataset.d ? JSON.parse(el.dataset.d) : {};
    for (const k in d) if (f.elements[k]) f.elements[k].value = d[k] ?? '';
    const h = f.querySelector('h3');
    if (h && h.dataset.add) h.textContent = d.id ? h.dataset.edit : h.dataset.add;
    if (f.elements.kategori && f.elements.jenis) filterKat(f);
  }
  o.classList.add('on');
}
function closeM() { document.querySelectorAll('.ov.on').forEach(o => o.classList.remove('on')); }

// Kategori pada form transaksi mengikuti jenis yang dipilih
function filterKat(f) {
  const j = f.elements.jenis.value, s = f.elements.kategori;
  [...s.options].forEach(o => { o.disabled = o.hidden = o.dataset.j !== j; });
  if (s.selectedOptions[0]?.disabled) s.value = ([...s.options].find(o => !o.disabled) || {}).value || '';
}
function hapus(btn) { window._f = btn.form; openM('m-del'); }    // NFR-02

// Pengaturan (disimpan di browser)
function syncSet() {
  const tg = $('#tg'); if (!tg) return;
  const on = localStorage.notif !== 'off', l = document.body.classList.contains('light');
  tg.classList.toggle('on', on); $('#nt').textContent = on ? 'ON' : 'OFF';
  $('#t-dark').classList.toggle('on', !l); $('#t-light').classList.toggle('on', l);
}
function tglNotif() {
  const on = localStorage.notif !== 'off';
  if (on) toast('Notifikasi dimatikan.');
  localStorage.notif = on ? 'off' : 'on'; syncSet();
  if (!on) toast('Notifikasi dinyalakan.');
}
function setTema(t) { localStorage.tema = t; document.body.classList.toggle('light', t === 'light'); syncSet(); return false; }
syncSet();
