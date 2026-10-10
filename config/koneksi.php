<?php
// Konfigurasi koneksi & fungsi bantu. Sesuaikan BASE dengan nama folder di htdocs.
session_start();
const BASE = '/sakuin/';
$pdo = new PDO('mysql:host=localhost;dbname=sakuin;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rp($n){ return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function tgl($d){ return date('d/m/Y', strtotime($d)); }
function tg($v){ return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$v) ? $v : ''; }
function flash($m, $bad = false){ $_SESSION['flash'] = [$m, $bad]; }
function go($p){ header('Location: ' . BASE . $p); exit; }
function csrf(){ return $_SESSION['t'] ?? ($_SESSION['t'] = bin2hex(random_bytes(16))); }
function field(){ return '<input type="hidden" name="t" value="' . csrf() . '">'; }
function cek(){ if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals(csrf(), $_POST['t'] ?? '')) { http_response_code(400); exit('Token tidak valid.'); } }
function wajib(){ if (empty($_SESSION['uid'])) go('auth/login.php'); }   // NFR-07
function user(){ global $pdo; static $u; return $u ??= $pdo->query('SELECT * FROM users WHERE id_user=' . (int)$_SESSION['uid'])->fetch(); }
function av($u, $c = ''){
    $s = $u['foto'] ? " style=\"background-image:url('" . BASE . "assets/uploads/" . e($u['foto']) . "?v=" . time() . "')\"" : '';
    return "<div class=\"av $c\"$s>" . ($u['foto'] ? '' : e(mb_strtoupper(mb_substr($u['nama'], 0, 1)))) . '</div>';
}
function pager($total, $per = 10){
    $p = max(1, (int)($_GET['p'] ?? 1)); $n = max(1, (int)ceil($total / $per)); $p = min($p, $n); $h = '';
    if ($n > 1) { $h = '<div class="pg">'; for ($i = 1; $i <= $n; $i++) $h .= '<a class="' . ($i == $p ? 'on' : '') . '" href="?' . e(http_build_query(array_merge($_GET, ['p' => $i]))) . '">' . $i . '</a>'; $h .= '</div>'; }
    return [($p - 1) * $per, $h];
}

// ===== Anggaran (batas pengeluaran) & Tabungan =====
// Daftar batas bulan dari $tgl beserta pengeluaran yang sudah terpakai (id_kategori NULL = batas total bulanan)
function anggaran($uid, $tgl, $kid = null){
    global $pdo;
    $a = date('Y-m-01', strtotime($tgl)); $b = date('Y-m-t', strtotime($tgl));
    $w = 'a.id_user=?'; $p = [$a, $b, $uid];
    if ($kid) { $w .= ' AND (a.id_kategori IS NULL OR a.id_kategori=?)'; $p[] = $kid; }
    $s = $pdo->prepare("SELECT a.id_anggaran, a.id_kategori, a.batas, k.nama_kategori,
      (SELECT COALESCE(SUM(t.nominal),0) FROM transaksi t JOIN kategori kk ON kk.id_kategori=t.id_kategori
       WHERE t.id_user=a.id_user AND kk.jenis='Pengeluaran' AND t.tanggal BETWEEN ? AND ?
       AND (a.id_kategori IS NULL OR t.id_kategori=a.id_kategori)) AS pakai
      FROM anggaran a LEFT JOIN kategori k ON k.id_kategori=a.id_kategori WHERE $w
      ORDER BY a.id_kategori IS NOT NULL, k.nama_kategori");
    $s->execute($p);
    return $s->fetchAll();
}
// Pesan peringatan setelah transaksi pengeluaran disimpan ('' bila aman). Ubah kalimatnya di sini.
function cekAnggaran($uid, $kid, $tgl){
    global $pdo;
    $c = $pdo->prepare("SELECT COUNT(*) FROM kategori WHERE id_kategori=? AND jenis='Pengeluaran'"); $c->execute([$kid]);
    if (!$c->fetchColumn()) return '';
    $dekat = '';
    foreach (anggaran($uid, $tgl, $kid) as $r) {
        $u = $r['nama_kategori'] ? 'Kategori ' . $r['nama_kategori'] : 'Pengeluaran bulan ini';
        if ($r['pakai'] > $r['batas'])
            return "Hey, udah dong! $u sudah lewat batas " . rp($r['batas']) . ' (kelebihan ' . rp($r['pakai'] - $r['batas']) . '). Tahan dulu, dompetmu juga butuh napas.';
        if (!$dekat && $r['pakai'] >= 0.8 * $r['batas'])
            $dekat = "Hati-hati, $u sudah " . round($r['pakai'] / $r['batas'] * 100) . '% dari batas ' . rp($r['batas']) . '.';
    }
    return $dekat;
}
// [saldo setelah tabungan, total tabungan]
function saldo($uid){
    global $pdo;
    $s = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN k.jenis='Pemasukan' THEN t.nominal ELSE -t.nominal END),0) FROM transaksi t JOIN kategori k ON k.id_kategori=t.id_kategori WHERE t.id_user=?");
    $s->execute([$uid]); $a = $s->fetchColumn();
    $s = $pdo->prepare('SELECT COALESCE(SUM(nominal),0) FROM setoran WHERE id_user=?'); $s->execute([$uid]); $b = $s->fetchColumn();
    return [$a - $b, $b];
}
