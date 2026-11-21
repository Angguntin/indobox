<?php
require __DIR__ . '/../includes/bootstrap.php';
require_member();
$mid = (int)me()['id'];
$list = rows("SELECT l.*, b.judul, b.id AS bid FROM loans l JOIN biblios b ON b.id=l.biblio_id WHERE l.member_id=? ORDER BY l.tgl_pinjam DESC, l.id DESC", [$mid]);
page_start('Riwayat Peminjaman', 'public'); member_nav('history');
?>
<div class="card"><div class="card-head"><h3>Riwayat peminjaman & pengembalian</h3><button class="btn btn-ghost btn-sm no-print" data-print>🖨️ Cetak</button></div>
<div class="tbl-wrap"><table><tr><th>Judul</th><th>Dipinjam</th><th>Jatuh tempo</th><th>Dikembalikan</th><th>Denda</th><th>Status</th></tr>
<?php foreach ($list as $l): ?><tr><td><a href="<?= url('detail.php?id=' . $l['bid']) ?>"><?= e($l['judul']) ?></a></td><td><?= e(tgl($l['tgl_pinjam'])) ?></td><td><?= e(tgl($l['tgl_jatuh_tempo'])) ?></td>
<td><?= e(tgl($l['tgl_kembali'])) ?></td><td><?= $l['denda'] ? rupiah($l['denda']) . ($l['denda_dibayar'] >= $l['denda'] ? ' <span class="badge b-ok">lunas</span>' : ' <span class="badge b-bad">belum</span>') : '-' ?></td>
<td><span class="badge <?= $l['status'] === 'kembali' ? 'b-ok' : 'b-warn' ?>"><?= $l['status'] === 'kembali' ? 'Dikembalikan' : 'Dipinjam' ?></span></td></tr>
<?php endforeach; if (!$list): ?><tr><td colspan="6" class="empty">Belum ada riwayat.</td></tr><?php endif; ?></table></div></div>
<?php page_end();
