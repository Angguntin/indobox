<?php
require __DIR__ . '/../includes/bootstrap.php';
require_member(); csrf_check();
$mid = (int)me()['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'cancel') {
    $rid = pid('rid'); $r = $rid === null ? null : row("SELECT * FROM reservations WHERE id=? AND member_id=? AND status IN ('menunggu','siap')", [$rid, $mid]);
    if ($r) { q("UPDATE reservations SET status='batal' WHERE id=?", [$rid]); promote_queue($r['biblio_id']); flash('ok', 'Reservasi dibatalkan.'); }
    redirect('member/reservations.php');
}
expire_reservations();
$list = rows("SELECT r.*, b.judul, b.pengarang, b.id AS bid FROM reservations r JOIN biblios b ON b.id=r.biblio_id WHERE r.member_id=? ORDER BY r.tgl_reservasi DESC, r.id DESC", [$mid]);
$badge = ['menunggu' => 'b-warn', 'siap' => 'b-ok', 'diambil' => 'b-gray', 'batal' => 'b-gray', 'kadaluarsa' => 'b-bad'];
page_start('Reservasi Saya', 'public'); member_nav('res');
?>
<div class="card"><div class="card-head"><h3>Reservasi buku</h3><a class="btn btn-sm" href="<?= url('opac.php') ?>">Cari buku untuk direservasi</a></div>
<div class="tbl-wrap"><table><tr><th>Judul</th><th>Tanggal</th><th>Status</th><th>Keterangan</th><th></th></tr>
<?php foreach ($list as $r): ?><tr><td><a href="<?= url('detail.php?id=' . $r['bid']) ?>"><b><?= e($r['judul']) ?></b></a><br><small class="muted"><?= e($r['pengarang']) ?></small></td><td><?= e(tgl($r['tgl_reservasi'], true)) ?></td>
<td><span class="badge <?= $badge[$r['status']] ?>"><?= e(ucfirst($r['status'])) ?></span></td>
<td><?php if ($r['status'] === 'siap') echo 'Ambil sebelum ' . e(tgl($r['batas_ambil'])); elseif ($r['status'] === 'menunggu') echo 'Antrean ke-' . queue_position($r['id']); ?></td>
<td class="right"><?php if (in_array($r['status'], ['menunggu', 'siap'])): ?><form method="post" class="inline-form" data-confirm="Batalkan reservasi ini?"><?= csrf() ?><input type="hidden" name="act" value="cancel"><input type="hidden" name="rid" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-danger" type="submit">Batalkan</button></form><?php endif; ?></td></tr>
<?php endforeach; if (!$list): ?><tr><td colspan="5" class="empty">Belum ada reservasi.</td></tr><?php endif; ?></table></div></div>
<?php page_end();
