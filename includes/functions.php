<?php
/* ---------- Kompatibilitas PHP 5.6 ---------- */
if (!function_exists('random_bytes')) {
    function random_bytes($n) {
        if (function_exists('openssl_random_pseudo_bytes')) return openssl_random_pseudo_bytes($n);
        $b = ''; for ($i = 0; $i < $n; $i++) $b .= chr(mt_rand(0, 255)); return $b;
    }
}
function nz($arr, $key, $default = '') { return (is_array($arr) && isset($arr[$key])) ? $arr[$key] : $default; }
/* ---------- Dasar ---------- */
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function url($p = '') { return BASE_URL . '/' . ltrim($p, '/'); }
function redirect($p) { header('Location: ' . (preg_match('#^https?:#', $p) ? $p : url($p))); exit; }
function flash($t, $m) { $_SESSION['flash'][] = [$t, $m]; }
function flash_html() {
    if (empty($_SESSION['flash'])) return '';
    $h = '';
    foreach ($_SESSION['flash'] as $f) $h .= '<div class="alert alert-' . e($f[0]) . '">' . e($f[1]) . '</div>';
    unset($_SESSION['flash']);
    return $h;
}
function csrf() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return '<input type="hidden" name="_t" value="' . $_SESSION['csrf'] . '">';
}
function csrf_check() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (empty($_POST['_t']) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['_t'])) {
            http_response_code(419);
            die('Sesi kedaluwarsa. Silakan kembali dan muat ulang halaman.');
        }
    }
}
/* ID aman: angka >= 0 (ID dimulai dari 0, jadi jangan pakai pengecekan "kosong") */
function gid($k = 'id') { return (isset($_GET[$k]) && ctype_digit((string)$_GET[$k])) ? (int)$_GET[$k] : null; }
function pid($k = 'id') { return (isset($_POST[$k]) && ctype_digit((string)$_POST[$k])) ? (int)$_POST[$k] : null; }
function post($k, $d = '') { return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $d; }
function get($k, $d = '') { return isset($_GET[$k]) ? trim((string)$_GET[$k]) : $d; }

/* ---------- Auth ---------- */
function me() { return isset($_SESSION['u']) ? $_SESSION['u'] : null; }
function is_staff() { return isset($_SESSION['u']) && $_SESSION['u']['kind'] === 'staff'; }
function is_member() { return isset($_SESSION['u']) && $_SESSION['u']['kind'] === 'member'; }
function require_staff($roles = null) {
    if (!is_staff()) { flash('err', 'Silakan login sebagai petugas terlebih dahulu.'); redirect('login.php'); }
    if ($roles && !in_array($_SESSION['u']['role'], $roles)) { flash('err', 'Anda tidak punya akses ke halaman tersebut.'); redirect('admin/index.php'); }
}
function require_member() {
    if (!is_member()) { flash('err', 'Silakan login sebagai anggota terlebih dahulu.'); redirect('login.php'); }
}
function verify_pw($plain, $stored) {
    if (strpos($stored, 'plain:') === 0) return hash_equals(substr($stored, 6), $plain);
    return password_verify($plain, $stored);
}
function hash_pw($p) { return password_hash($p, PASSWORD_DEFAULT); }

/* ---------- Pengaturan ---------- */
function setting($k, $d = '') {
    static $c = null;
    if ($c === null) { $c = []; foreach (rows("SELECT k, v FROM settings") as $r) $c[$r['k']] = $r['v']; }
    return array_key_exists($k, $c) ? $c[$k] : $d;
}
function set_setting($k, $v) {
    if (val("SELECT COUNT(*) FROM settings WHERE k=?", [$k]) > 0) q("UPDATE settings SET v=? WHERE k=?", [$v, $k]);
    else db_insert('settings', ['k' => $k, 'v' => $v]);
}

/* ---------- Format ---------- */
function rupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function tgl($d, $time = false) {
    if (!$d) return '-';
    $t = strtotime($d);
    $b = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return date('j', $t) . ' ' . $b[(int)date('n', $t)] . ' ' . date('Y', $t) . ($time ? ' ' . date('H:i', $t) : '');
}
function nama_bulan($ym) {
    $b = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $p = explode('-', $ym);
    return $b[(int)$p[1]] . ' ' . $p[0];
}
function days_late($due, $ret = null) {
    $r = $ret ? strtotime($ret) : strtotime(date('Y-m-d'));
    $d = (int)floor(($r - strtotime($due)) / 86400);
    return max(0, $d);
}
function log_act($aksi) {
    $u = me();
    db_insert('activity_log', ['pengguna' => $u ? $u['nama'] . ' (' . ($u['kind'] === 'staff' ? $u['role'] : 'anggota') . ')' : 'sistem', 'aksi' => $aksi]);
}
function qs($over = []) {
    $p = array_merge($_GET, $over);
    foreach ($p as $k => $v) if ($v === '' || $v === null) unset($p[$k]);
    return http_build_query($p);
}
function pager($total, $per, $page) {
    $pages = (int)ceil($total / $per);
    if ($pages <= 1) return '';
    $h = '<nav class="pager">';
    $link = function ($p, $t, $cls = '') { return '<a class="' . $cls . '" href="?' . e(qs(['page' => $p])) . '">' . $t . '</a>'; };
    if ($page > 1) $h .= $link($page - 1, '‹ Sebelumnya');
    $from = max(1, $page - 2); $to = min($pages, $page + 2);
    if ($from > 1) $h .= $link(1, '1') . ($from > 2 ? '<span>…</span>' : '');
    for ($i = $from; $i <= $to; $i++) $h .= $link($i, $i, $i == $page ? 'on' : '');
    if ($to < $pages) $h .= ($to < $pages - 1 ? '<span>…</span>' : '') . $link($pages, $pages);
    if ($page < $pages) $h .= $link($page + 1, 'Berikutnya ›');
    return $h . '</nav>';
}

/* ---------- Upload ---------- */
function save_upload($field, $dir, $exts, $maxMB = 40) {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new Exception('Upload gagal (kode ' . $f['error'] . '). Naikkan upload_max_filesize & post_max_size di php.ini XAMPP jika file besar.');
    if ($f['size'] > $maxMB * 1048576) throw new Exception('File terlalu besar (maks ' . $maxMB . ' MB).');
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $exts)) throw new Exception('Tipe file tidak diizinkan. Gunakan: ' . implode(', ', $exts));
    if ($ext === 'pdf' && file_get_contents($f['tmp_name'], false, null, 0, 4) !== '%PDF') throw new Exception('Berkas bukan PDF yang valid.');
    $name = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    if (!move_uploaded_file($f['tmp_name'], $dir . $name)) throw new Exception('Gagal menyimpan file ke folder uploads.');
    return $name;
}

/* ---------- Tampilan koleksi ---------- */
function cover_html($b, $cls = '') {
    if (!empty($b['cover']) && is_file(UPLOAD_COVER . $b['cover']))
        return '<div class="cover ' . $cls . '"><img src="' . url('uploads/cover/' . rawurlencode($b['cover'])) . '" alt="Sampul ' . e($b['judul']) . '" loading="lazy"></div>';
    $pal = [['#fcc8d9', '#b8e6cc'], ['#b8e6cc', '#fcc8d9'], ['#f9a8c4', '#d9f3e4'], ['#c9eed8', '#f6b6cd'], ['#ffd6e4', '#9fdcbb'], ['#a8e0c2', '#ffc1d6']];
    $p = $pal[((int)$b['id']) % 6];
    return '<div class="cover ' . $cls . '" style="background:linear-gradient(145deg,' . $p[0] . ',' . $p[1] . ')"><span class="cover-title">' . e(mb_strimwidth($b['judul'], 0, 64, '…')) . '</span><span class="cover-author">' . e($b['pengarang']) . '</span></div>';
}
function stars($n, $max = 5) {
    $n = (int)round($n); $h = '<span class="stars" aria-label="' . $n . ' dari ' . $max . '">';
    for ($i = 1; $i <= $max; $i++) $h .= '<i class="' . ($i <= $n ? 'on' : '') . '">★</i>';
    return $h . '</span>';
}

/* ---------- Barcode Code 39 (SVG, tanpa library) ---------- */
function barcode_svg($text, $height = 40, $unit = 1.6) {
    $map = ['0' => '000110100', '1' => '100100001', '2' => '001100001', '3' => '101100000', '4' => '000110001', '5' => '100110000', '6' => '001110000', '7' => '000100101', '8' => '100100100', '9' => '001100100',
        'A' => '100001001', 'B' => '001001001', 'C' => '101001000', 'D' => '000011001', 'E' => '100011000', 'F' => '001011000', 'G' => '000001101', 'H' => '100001100', 'I' => '001001100', 'J' => '000011100',
        'K' => '100000011', 'L' => '001000011', 'M' => '101000010', 'N' => '000010011', 'O' => '100010010', 'P' => '001010010', 'Q' => '000000111', 'R' => '100000110', 'S' => '001000110', 'T' => '000010110',
        'U' => '110000001', 'V' => '011000001', 'W' => '111000000', 'X' => '010010001', 'Y' => '110010000', 'Z' => '011010000', '-' => '010000101', '.' => '110000100', ' ' => '011000100', '*' => '010010100',
        '$' => '010101000', '/' => '010100010', '+' => '010001010', '%' => '000101010'];
    $t = '*' . strtoupper(preg_replace('/[^A-Za-z0-9\-\. \$\/\+%]/', '', $text)) . '*';
    $x = 0; $bars = '';
    foreach (str_split($t) as $ch) {
        $pat = $map[$ch];
        for ($i = 0; $i < 9; $i++) {
            $w = ($pat[$i] === '1' ? 3 : 1) * $unit;
            if ($i % 2 === 0) $bars .= '<rect x="' . round($x, 2) . '" y="0" width="' . round($w, 2) . '" height="' . $height . '"/>';
            $x += $w;
        }
        $x += $unit;
    }
    return '<svg class="barcode" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . round($x, 2) . ' ' . $height . '" preserveAspectRatio="none" fill="#2b2026">' . $bars . '</svg>';
}

/* ---------- Grafik ringan (HTML/CSS) ---------- */
function bars_chart($labels, $values, $unit = '') {
    $max = max(1, $values ? max($values) : 1);
    $h = '<div class="bars">';
    foreach ($labels as $i => $l) {
        $pct = round(($values[$i] / $max) * 100);
        $h .= '<div class="bcol"><span class="bval">' . $values[$i] . $unit . '</span><div class="bbar" style="height:' . max(3, $pct) . '%"></div><span class="blab">' . e($l) . '</span></div>';
    }
    return $h . '</div>';
}
function hbars($labels, $values) {
    $max = max(1, $values ? max($values) : 1);
    $h = '<div class="hbars">';
    foreach ($labels as $i => $l)
        $h .= '<div class="hrow"><span class="hlab" title="' . e($l) . '">' . e($l) . '</span><div class="htrack"><div class="hfill" style="width:' . round(($values[$i] / $max) * 100) . '%"></div></div><b>' . $values[$i] . '</b></div>';
    return $h . '</div>';
}
function donut($labels, $values) {
    $cols = ['#f08ab0', '#7fd0a6', '#fcc8d9', '#b8e6cc', '#d6457f', '#4fae83', '#ffd6e4', '#9fdcbb', '#e8739f', '#c9eed8'];
    $sum = array_sum($values); if ($sum <= 0) return '<p class="muted">Belum ada data.</p>';
    $acc = 0; $stops = []; $leg = '';
    foreach ($labels as $i => $l) {
        $a = $acc / $sum * 100; $acc += $values[$i]; $b = $acc / $sum * 100; $c = $cols[$i % count($cols)];
        $stops[] = "$c {$a}% {$b}%";
        $leg .= '<li><i style="background:' . $c . '"></i>' . e($l) . ' <b>' . $values[$i] . '</b></li>';
    }
    return '<div class="donut-wrap"><div class="donut" style="background:conic-gradient(' . implode(',', $stops) . ')"><span>' . $sum . '</span></div><ul class="legend">' . $leg . '</ul></div>';
}

/* ---------- Reservasi ---------- */
function held_count($bid) { return (int)val("SELECT COUNT(*) FROM reservations WHERE biblio_id=? AND status='siap'", [$bid]); }
function effective_stock($bid) { return (int)val("SELECT stok_tersedia FROM biblios WHERE id=?", [$bid]) - held_count($bid); }
function promote_queue($bid) {
    $days = max(1, (int)setting('hari_siap_ambil', 2));
    while (effective_stock($bid) > 0) {
        $r = row("SELECT id FROM reservations WHERE biblio_id=? AND status='menunggu' ORDER BY tgl_reservasi, id LIMIT 1", [$bid]);
        if (!$r) break;
        q("UPDATE reservations SET status='siap', batas_ambil=DATE_ADD(CURDATE(), INTERVAL $days DAY) WHERE id=?", [$r['id']]);
    }
}
function expire_reservations() {
    foreach (rows("SELECT id, biblio_id FROM reservations WHERE status='siap' AND batas_ambil < CURDATE()") as $r) {
        q("UPDATE reservations SET status='kadaluarsa' WHERE id=?", [$r['id']]);
        promote_queue($r['biblio_id']);
    }
}
function queue_position($rid) {
    $r = row("SELECT biblio_id, tgl_reservasi, id FROM reservations WHERE id=?", [$rid]);
    if (!$r) return 0;
    return 1 + (int)val("SELECT COUNT(*) FROM reservations WHERE biblio_id=? AND status='menunggu' AND (tgl_reservasi < ? OR (tgl_reservasi = ? AND id < ?))", [$r['biblio_id'], $r['tgl_reservasi'], $r['tgl_reservasi'], $r['id']]);
}

/* ---------- Kartu anggota ---------- */
function member_card_html($m) {
    $foto = strtoupper(mb_substr($m['nama'], 0, 1));
    return '<div class="idcard"><div class="idc-top"><b>' . e(setting('nama_perpustakaan', 'INDO BOX')) . '</b><small>Kartu Anggota Perpustakaan</small></div>'
        . '<div class="idc-body"><div class="idc-photo">' . e($foto) . '</div><div class="idc-info"><h4>' . e($m['nama']) . '</h4><p>' . e($m['no_anggota']) . '</p><p>' . e($m['tipe']) . '</p><p>Berlaku s/d ' . e(tgl($m['tgl_expire'])) . '</p></div></div>'
        . '<div class="idc-bar">' . barcode_svg($m['no_anggota'], 34, 1.2) . '<span>' . e($m['no_anggota']) . '</span></div></div>';
}

/* ---------- Sirkulasi ---------- */
function member_info($mid) {
    return row("SELECT m.*, t.nama AS tipe, t.maks_pinjam, t.lama_pinjam, t.denda_per_hari, t.maks_perpanjang, t.masa_berlaku_bulan
                FROM members m JOIN member_types t ON t.id=m.type_id WHERE m.id=?", [$mid]);
}
function member_unpaid($mid) {
    return (int)val("SELECT COALESCE(SUM(denda - denda_dibayar),0) FROM loans WHERE member_id=? AND status='kembali'", [$mid]);
}
function checkout($memberId, $biblioId, $uid) {
    global $pdo;
    $m = member_info($memberId);
    $b = row("SELECT * FROM biblios WHERE id=?", [$biblioId]);
    if (!$m) return [false, 'Anggota tidak ditemukan.'];
    if (!$b) return [false, 'Koleksi tidak ditemukan.'];
    if ($m['status'] !== 'aktif') return [false, 'Anggota berstatus ' . $m['status'] . '.'];
    if (strtotime($m['tgl_expire']) < strtotime(date('Y-m-d'))) return [false, 'Keanggotaan sudah kedaluwarsa (' . tgl($m['tgl_expire']) . ').'];
    $aktif = (int)val("SELECT COUNT(*) FROM loans WHERE member_id=? AND status='dipinjam'", [$memberId]);
    if ($aktif >= $m['maks_pinjam']) return [false, 'Batas pinjam tercapai (' . $m['maks_pinjam'] . ' buku untuk tipe ' . $m['tipe'] . ').'];
    if (val("SELECT COUNT(*) FROM loans WHERE member_id=? AND status='dipinjam' AND tgl_jatuh_tempo < CURDATE()", [$memberId]) > 0) return [false, 'Anggota masih memiliki buku terlambat. Selesaikan pengembalian dahulu.'];
    if (member_unpaid($memberId) > 0) return [false, 'Anggota masih punya denda belum dibayar: ' . rupiah(member_unpaid($memberId)) . '.'];
    if (val("SELECT COUNT(*) FROM loans WHERE member_id=? AND biblio_id=? AND status='dipinjam'", [$memberId, $biblioId]) > 0) return [false, 'Anggota sedang meminjam judul yang sama.'];
    $own = row("SELECT id FROM reservations WHERE biblio_id=? AND member_id=? AND status='siap'", [$biblioId, $memberId]);
    $avail = (int)$b['stok_tersedia'] - held_count($biblioId) + ($own ? 1 : 0);
    if ($avail <= 0) return [false, 'Stok tidak tersedia (habis dipinjam atau ditahan untuk reservasi anggota lain).'];
    $due = date('Y-m-d', strtotime('+' . (int)$m['lama_pinjam'] . ' days'));
    $pdo->beginTransaction();
    try {
        db_insert('loans', ['biblio_id' => $biblioId, 'member_id' => $memberId, 'user_id' => $uid, 'tgl_pinjam' => date('Y-m-d'), 'tgl_jatuh_tempo' => $due, 'status' => 'dipinjam']);
        q("UPDATE biblios SET stok_tersedia = stok_tersedia - 1 WHERE id=? AND stok_tersedia > 0", [$biblioId]);
        q("UPDATE reservations SET status='diambil' WHERE biblio_id=? AND member_id=? AND status IN ('siap','menunggu')", [$biblioId, $memberId]);
        $pdo->commit();
    } catch (Exception $ex) { $pdo->rollBack(); return [false, 'Gagal menyimpan: ' . $ex->getMessage()]; }
    return [true, '“' . $b['judul'] . '” dipinjam oleh ' . $m['nama'] . '. Jatuh tempo ' . tgl($due) . '.'];
}
function return_loan($lid) {
    global $pdo;
    $l = row("SELECT l.*, b.judul FROM loans l JOIN biblios b ON b.id=l.biblio_id WHERE l.id=?", [$lid]);
    if (!$l || $l['status'] !== 'dipinjam') return [false, 'Peminjaman tidak ditemukan / sudah dikembalikan.', 0, 0];
    $m = member_info($l['member_id']);
    $late = days_late($l['tgl_jatuh_tempo']);
    $denda = $late * (int)$m['denda_per_hari'];
    $pdo->beginTransaction();
    try {
        q("UPDATE loans SET tgl_kembali=CURDATE(), status='kembali', denda=? WHERE id=?", [$denda, $lid]);
        q("UPDATE biblios SET stok_tersedia = LEAST(stok_total, stok_tersedia + 1) WHERE id=?", [$l['biblio_id']]);
        promote_queue($l['biblio_id']);
        $pdo->commit();
    } catch (Exception $ex) { $pdo->rollBack(); return [false, 'Gagal: ' . $ex->getMessage(), 0, 0]; }
    $msg = '“' . $l['judul'] . '” dikembalikan oleh ' . $m['nama'] . '.';
    if ($late > 0) $msg .= " Terlambat $late hari, denda " . rupiah($denda) . '.';
    return [true, $msg, $late, $denda];
}
function extend_loan($lid, $onlyMember = null) {
    $l = row("SELECT l.*, b.judul FROM loans l JOIN biblios b ON b.id=l.biblio_id WHERE l.id=?", [$lid]);
    if (!$l || $l['status'] !== 'dipinjam') return [false, 'Peminjaman tidak ditemukan atau sudah dikembalikan.'];
    if ($onlyMember !== null && (int)$l['member_id'] !== (int)$onlyMember) return [false, 'Itu bukan peminjaman Anda.'];
    $m = member_info($l['member_id']);
    if ($l['perpanjang_count'] >= $m['maks_perpanjang']) return [false, 'Batas perpanjangan tercapai (' . $m['maks_perpanjang'] . '×).'];
    if (days_late($l['tgl_jatuh_tempo']) > 0) return [false, 'Buku sudah terlambat; kembalikan dulu dan selesaikan denda.'];
    if (val("SELECT COUNT(*) FROM reservations WHERE biblio_id=? AND member_id<>? AND status IN ('menunggu','siap')", [$l['biblio_id'], $l['member_id']]) > 0) return [false, 'Buku sedang diantre anggota lain sehingga tidak bisa diperpanjang.'];
    $new = date('Y-m-d', strtotime($l['tgl_jatuh_tempo'] . ' +' . (int)$m['lama_pinjam'] . ' days'));
    q("UPDATE loans SET tgl_jatuh_tempo=?, perpanjang_count=perpanjang_count+1 WHERE id=?", [$new, $lid]);
    return [true, '“' . $l['judul'] . '” diperpanjang sampai ' . tgl($new) . '.'];
}

/* ---------- Penomoran otomatis ---------- */
function next_barcode() {
    $n = (int)val("SELECT COUNT(*) FROM biblios") + 1;
    while (val("SELECT COUNT(*) FROM biblios WHERE kode_barcode=?", [sprintf('IB-%04d', $n)]) > 0) $n++;
    return sprintf('IB-%04d', $n);
}
function next_member_no() {
    $n = (int)val("SELECT COUNT(*) FROM members") + 1;
    while (val("SELECT COUNT(*) FROM members WHERE no_anggota=?", [sprintf('AGT-%04d', $n)]) > 0) $n++;
    return sprintf('AGT-%04d', $n);
}
function month_back($i) { return date('Y-m', strtotime(date('Y-m-01') . " -$i month")); }
