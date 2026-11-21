<?php
require __DIR__ . '/../includes/bootstrap.php';
require_member(); csrf_check();
$mid = (int)me()['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'extend') {
    $lid = pid('loan_id');
    $r = $lid === null ? [false, 'Data tidak valid.'] : extend_loan($lid, $mid);
    flash($r[0] ? 'ok' : 'err', $r[1]); redirect('member/index.php');
}
$m = member_info($mid);
$loans = rows("SELECT l.*, b.judul, b.pengarang, b.id AS bid, b.file_pdf FROM loans l JOIN biblios b ON b.id=l.biblio_id WHERE l.member_id=? AND l.status='dipinjam' ORDER BY l.tgl_jatuh_tempo", [$mid]);
$ready = rows("SELECT r.*, b.judul FROM reservations r JOIN biblios b ON b.id=r.biblio_id WHERE r.member_id=? AND r.status='siap'", [$mid]);
$total = (int)val("SELECT COUNT(*) FROM loans WHERE member_id=?", [$mid]);
$unpaid = member_unpaid($mid);
page_start('Akun Saya', 'public');
member_nav('home');
?>
<h1>Halo, <?= e($m['nama']) ?> 👋</h1>
<div class="stat-strip">
  <div><b><?= count($loans) ?>/<?= (int)$m['maks_pinjam'] ?></b><span>Buku sedang dipinjam</span></div>
  <div><b><?= $total ?></b><span>Total peminjaman</span></div>
  <div><b><?= rupiah($unpaid) ?></b><span>Denda belum dibayar</span></div>
  <div><b><?= e(tgl($m['tgl_expire'])) ?></b><span>Masa berlaku · <?= e($m['tipe']) ?></span></div>
</div>
<?php foreach ($ready as $r): ?><div class="alert alert-ok">🔖 Reservasi <b><?= e($r['judul']) ?></b> sudah siap diambil sebelum <b><?= e(tgl($r['batas_ambil'])) ?></b>.</div><?php endforeach; ?>
<div class="card"><h3>Sedang dipinjam</h3>
<div class="tbl-wrap"><table><tr><th>Judul</th><th>Pinjam</th><th>Jatuh tempo</th><th>Status</th><th></th></tr>
<?php foreach ($loans as $l): $late = days_late($l['tgl_jatuh_tempo']); ?>
<tr><td><a href="<?= url('detail.php?id=' . $l['bid']) ?>"><b><?= e($l['judul']) ?></b></a><br><small class="muted"><?= e($l['pengarang']) ?></small></td>
<td><?= e(tgl($l['tgl_pinjam'])) ?></td><td><?= e(tgl($l['tgl_jatuh_tempo'])) ?></td>
<td><?php if ($late > 0): ?><span class="badge b-bad">Terlambat <?= $late ?> hari · <?= rupiah($late * $m['denda_per_hari']) ?></span><?php else: ?><span class="badge b-ok">Aman</span><?php endif; ?> <small class="muted">diperpanjang <?= (int)$l['perpanjang_count'] ?>×</small></td>
<td class="right"><?php if ($l['file_pdf']): ?><a class="btn btn-sm btn-mint" href="<?= url('detail.php?id=' . $l['bid'] . '#pdf') ?>">Baca PDF</a> <?php endif; ?>
<form method="post" class="inline-form"><?= csrf() ?><input type="hidden" name="act" value="extend"><input type="hidden" name="loan_id" value="<?= $l['id'] ?>"><button class="btn btn-sm btn-soft" type="submit">Perpanjang</button></form></td></tr>
<?php endforeach; if (!$loans): ?><tr><td colspan="5" class="empty">Tidak ada pinjaman aktif. <a href="<?= url('opac.php') ?>">Cari buku</a></td></tr><?php endif; ?></table></div></div>
<?php page_end();
