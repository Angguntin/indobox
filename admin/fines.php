<?php
require __DIR__ . '/../includes/bootstrap.php';
require_staff(); csrf_check();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lid = pid('loan_id'); $l = $lid === null ? null : row("SELECT * FROM loans WHERE id=?", [$lid]);
    if ($l) {
        $sisa = (int)$l['denda'] - (int)$l['denda_dibayar'];
        if (post('act') === 'bayar') { $amt = min($sisa, max(0, (int)post('jumlah'))); if ($amt > 0) { q("UPDATE loans SET denda_dibayar=denda_dibayar+? WHERE id=?", [$amt, $lid]); flash('ok', 'Pembayaran ' . rupiah($amt) . ' dicatat.'); log_act('Denda dibayar ' . rupiah($amt) . " (loan #$lid)"); } }
        elseif (post('act') === 'bebas' && me()['role'] === 'admin') { q("UPDATE loans SET denda=denda_dibayar WHERE id=?", [$lid]); flash('ok', 'Sisa denda dibebaskan.'); log_act("Bebaskan denda loan #$lid"); }
    }
    redirect('admin/fines.php');
}
$list = rows("SELECT l.*, b.judul, m.nama, m.no_anggota FROM loans l JOIN biblios b ON b.id=l.biblio_id JOIN members m ON m.id=l.member_id WHERE l.status='kembali' AND l.denda>l.denda_dibayar ORDER BY l.tgl_kembali DESC");
$jalan = rows("SELECT l.*, b.judul, m.nama, m.no_anggota, t.denda_per_hari FROM loans l JOIN biblios b ON b.id=l.biblio_id JOIN members m ON m.id=l.member_id JOIN member_types t ON t.id=m.type_id WHERE l.status='dipinjam' AND l.tgl_jatuh_tempo<CURDATE() ORDER BY l.tgl_jatuh_tempo");
$tot = (int)val("SELECT COALESCE(SUM(denda-denda_dibayar),0) FROM loans WHERE status='kembali'");
$paid = (int)val("SELECT COALESCE(SUM(denda_dibayar),0) FROM loans WHERE DATE_FORMAT(tgl_kembali,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')");
page_start('Denda Keterlambatan', 'admin', 'fines');
?>
<div class="kpis"><div class="kpi"><div class="ic">💰</div><div><b><?= rupiah($tot) ?></b><span>Denda belum dibayar</span></div></div><div class="kpi m"><div class="ic">✅</div><div><b><?= rupiah($paid) ?></b><span>Denda terbayar (pengembalian bulan ini)</span></div></div></div>
<div class="card"><h3>Denda menunggu pembayaran</h3><div class="tbl-wrap"><table><tr><th>Anggota</th><th>Koleksi</th><th>Kembali</th><th>Denda</th><th>Dibayar</th><th>Sisa</th><th></th></tr>
<?php foreach ($list as $l): $sisa = $l['denda'] - $l['denda_dibayar']; ?><tr><td><b><?= e($l['nama']) ?></b><br><small class="muted"><?= e($l['no_anggota']) ?></small></td><td><?= e($l['judul']) ?></td><td><?= e(tgl($l['tgl_kembali'])) ?></td><td><?= rupiah($l['denda']) ?></td><td><?= rupiah($l['denda_dibayar']) ?></td><td><b><?= rupiah($sisa) ?></b></td>
<td class="right"><form method="post" class="inline-form"><?= csrf() ?><input type="hidden" name="loan_id" value="<?= $l['id'] ?>"><input type="number" name="jumlah" value="<?= $sisa ?>" min="0" max="<?= $sisa ?>" style="width:110px;display:inline-block;padding:.35rem .5rem"> <button class="btn btn-sm" name="act" value="bayar" type="submit">Bayar</button>
<?php if (me()['role'] === 'admin'): ?><button class="btn btn-sm btn-ghost" name="act" value="bebas" type="submit" onclick="return confirm('Bebaskan sisa denda?')">Bebaskan</button><?php endif; ?></form></td></tr>
<?php endforeach; if (!$list): ?><tr><td colspan="7" class="empty">Tidak ada denda tertunggak 🎉</td></tr><?php endif; ?></table></div></div>
<div class="card"><h3>Denda berjalan (belum dikembalikan)</h3><div class="tbl-wrap"><table><tr><th>Anggota</th><th>Koleksi</th><th>Jatuh tempo</th><th>Telat</th><th>Estimasi denda</th></tr>
<?php foreach ($jalan as $l): $d = days_late($l['tgl_jatuh_tempo']); ?><tr><td><?= e($l['nama']) ?></td><td><?= e($l['judul']) ?></td><td><?= e(tgl($l['tgl_jatuh_tempo'])) ?></td><td><?= $d ?> hari</td><td><?= rupiah($d * $l['denda_per_hari']) ?></td></tr>
<?php endforeach; if (!$jalan): ?><tr><td colspan="5" class="empty">Tidak ada.</td></tr><?php endif; ?></table></div></div>
<?php page_end('admin');
