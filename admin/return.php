<?php
require __DIR__ . '/../includes/bootstrap.php';
require_staff(); csrf_check();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lid = pid('loan_id');
    if ($lid === null) { flash('err', 'Data tidak valid.'); redirect('admin/return.php'); }
    $r = return_loan($lid);
    if ($r[0]) {
        if ($r[3] > 0 && post('bayar') === '1') { q("UPDATE loans SET denda_dibayar=denda WHERE id=?", [$lid]); $r[1] .= ' Denda dibayar tunai.'; }
        log_act("Kembali: loan #$lid");
    }
    flash($r[0] ? 'ok' : 'err', $r[1]);
    redirect('admin/return.php?q=' . urlencode(post('q')));
}
$qq = strtoupper(get('q')); $loans = [];
if ($qq !== '') $loans = rows("SELECT l.*, b.judul, b.kode_barcode, m.nama, m.no_anggota, t.denda_per_hari FROM loans l JOIN biblios b ON b.id=l.biblio_id JOIN members m ON m.id=l.member_id JOIN member_types t ON t.id=m.type_id
    WHERE l.status='dipinjam' AND (m.no_anggota=? OR b.kode_barcode=? OR m.nama LIKE ?) ORDER BY l.tgl_jatuh_tempo", [$qq, $qq, '%' . get('q') . '%']);
page_start('Pengembalian', 'admin', 'return');
?>
<div class="card"><h3>Scan barcode koleksi atau no. anggota</h3>
<form method="get" class="actions"><div class="lookup" style="flex:1;min-width:240px"><input class="big-input" type="text" name="q" value="<?= e(get('q')) ?>" placeholder="IB-0001 atau AGT-0001 atau nama anggota" data-autofocus autocomplete="off"></div><button class="btn" type="submit">Cari pinjaman</button></form></div>
<?php if ($qq !== ''): ?><div class="card"><h3>Pinjaman aktif ditemukan: <?= count($loans) ?></h3>
<div class="tbl-wrap"><table><tr><th>Anggota</th><th>Koleksi</th><th>Jatuh tempo</th><th>Keterlambatan / denda</th><th></th></tr>
<?php foreach ($loans as $l): $late = days_late($l['tgl_jatuh_tempo']); ?><tr><td><b><?= e($l['nama']) ?></b><br><small class="muted"><?= e($l['no_anggota']) ?></small></td><td><?= e($l['judul']) ?><br><small class="muted"><?= e($l['kode_barcode']) ?></small></td><td><?= e(tgl($l['tgl_jatuh_tempo'])) ?></td>
<td><?= $late ? '<span class="badge b-bad">' . $late . ' hari · ' . rupiah($late * $l['denda_per_hari']) . '</span>' : '<span class="badge b-ok">Tepat waktu</span>' ?></td>
<td class="right"><form method="post" class="inline-form"><?= csrf() ?><input type="hidden" name="loan_id" value="<?= $l['id'] ?>"><input type="hidden" name="q" value="<?= e(get('q')) ?>">
<?php if ($late): ?><label style="display:inline;font-weight:500"><input type="checkbox" name="bayar" value="1"> bayar denda</label> <?php endif; ?><button class="btn btn-sm" type="submit">📥 Kembalikan</button></form></td></tr>
<?php endforeach; if (!$loans): ?><tr><td colspan="5" class="empty">Tidak ada pinjaman aktif yang cocok.</td></tr><?php endif; ?></table></div></div><?php endif;
page_end('admin');
