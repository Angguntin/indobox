<?php
require __DIR__ . '/includes/bootstrap.php';
require_member(); csrf_check();
$bid = pid('biblio_id'); $mid = (int)me()['id'];
$b = $bid === null ? null : row("SELECT * FROM biblios WHERE id=?", [$bid]);
$m = row("SELECT * FROM members WHERE id=?", [$mid]);
$back = 'detail.php?id=' . (int)$bid;
if (!$b || !$m) { flash('err', 'Data tidak ditemukan.'); redirect('opac.php'); }
expire_reservations();
if ($m['status'] !== 'aktif' || strtotime($m['tgl_expire']) < strtotime(date('Y-m-d'))) { flash('err', 'Keanggotaan Anda tidak aktif / sudah kedaluwarsa. Hubungi petugas untuk perpanjangan.'); redirect($back); }
if (val("SELECT COUNT(*) FROM loans WHERE biblio_id=? AND member_id=? AND status='dipinjam'", [$bid, $mid]) > 0) { flash('err', 'Anda sedang meminjam buku ini.'); redirect($back); }
if (val("SELECT COUNT(*) FROM reservations WHERE biblio_id=? AND member_id=? AND status IN ('menunggu','siap')", [$bid, $mid]) > 0) { flash('err', 'Anda sudah memiliki reservasi untuk buku ini.'); redirect($back); }
$max = (int)setting('maks_reservasi', 3);
if (val("SELECT COUNT(*) FROM reservations WHERE member_id=? AND status IN ('menunggu','siap')", [$mid]) >= $max) { flash('err', "Batas reservasi aktif adalah $max buku."); redirect($back); }
if (effective_stock($bid) > 0) {
    $days = max(1, (int)setting('hari_siap_ambil', 2));
    db_insert('reservations', ['biblio_id' => $bid, 'member_id' => $mid, 'tgl_reservasi' => date('Y-m-d H:i:s'), 'batas_ambil' => date('Y-m-d', strtotime("+$days days")), 'status' => 'siap']);
    flash('ok', "Reservasi berhasil. Buku siap diambil di perpustakaan paling lambat " . tgl(date('Y-m-d', strtotime("+$days days"))) . '.');
} else {
    db_insert('reservations', ['biblio_id' => $bid, 'member_id' => $mid, 'tgl_reservasi' => date('Y-m-d H:i:s'), 'batas_ambil' => null, 'status' => 'menunggu']);
    flash('ok', 'Reservasi berhasil. Anda masuk antrean; kami akan menandai “siap” begitu buku dikembalikan.');
}
log_act('Reservasi online: ' . $b['judul'] . ' oleh ' . $m['nama']);
redirect('member/reservations.php');
