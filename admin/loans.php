<?php
require __DIR__ . '/../includes/bootstrap.php';
require_staff(); csrf_check();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lid = pid('loan_id'); $act = post('act');
    if ($lid !== null) {
        if ($act === 'extend') { $r = extend_loan($lid); flash($r[0] ? 'ok' : 'err', $r[1]); if ($r[0]) log_act("Perpanjang loan #$lid"); }
        elseif ($act === 'return') { $r = return_loan($lid); flash($r[0] ? 'ok' : 'err', $r[1]); if ($r[0]) log_act("Kembali: loan #$lid"); }
    }
    redirect('admin/loans.php?' . qs());
}
$f = get('f'); $where = "l.status='dipinjam'";
if ($f === 'terlambat') $where .= ' AND l.tgl_jatuh_tempo < CURDATE()';
elseif ($f === 'segera') $where .= ' AND l.tgl_jatuh_tempo BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 DAY)';
$list = rows("SELECT l.*, b.judul, b.kode_barcode, m.nama, m.no_anggota, m.telp, t.denda_per_hari FROM loans l JOIN biblios b ON b.id=l.biblio_id JOIN members m ON m.id=l.member_id JOIN member_types t ON t.id=m.type_id WHERE $where ORDER BY l.tgl_jatuh_tempo");
page_start('Sedang Dipinjam', 'admin', 'loans');
?>
<div class="chips" style="margin-bottom:1rem"><?php foreach ([''=>'Semua', 'terlambat' => 'Terlambat', 'segera' => 'Jatuh tempo ≤ 2 hari'] as $k => $v): ?><a class="chip <?= $f === $k ? 'on' : '' ?>" href="?f=<?= $k ?>"><?= $v ?></a><?php endforeach; ?></div>
<div class="card"><div class="tbl-wrap"><table><tr><th>Anggota</th><th>Koleksi</th><th>Pinjam</th><th>Jatuh tempo</th><th>Status</th><th></th></tr>
<?php foreach ($list as $l): $late = days_late($l['tgl_jatuh_tempo']); ?><tr><td><b><?= e($l['nama']) ?></b><br><small class="muted"><?= e($l['no_anggota']) ?> · <?= e($l['telp']) ?></small></td><td><?= e($l['judul']) ?><br><small class="muted"><?= e($l['kode_barcode']) ?></small></td>
<td><?= e(tgl($l['tgl_pinjam'])) ?></td><td><?= e(tgl($l['tgl_jatuh_tempo'])) ?></td><td><?= $late ? '<span class="badge b-bad">Telat ' . $late . ' hari · ' . rupiah($late * $l['denda_per_hari']) . '</span>' : '<span class="badge b-ok">Aman</span>' ?> <small class="muted"><?= (int)$l['perpanjang_count'] ?>× perpanjang</small></td>

<td class="right">
    <form method="post" class="loan-actions">
        <?= csrf() ?>
        <input type="hidden" name="loan_id" value="<?= $l['id'] ?>">

        <button class="btn btn-sm btn-soft" name="act" value="extend" type="submit">
            Perpanjang
        </button>

        <button class="btn btn-sm" name="act" value="return" type="submit">
            Kembalikan
        </button>
    </form>
</td>
<?php endforeach; if (!$list): ?><tr><td colspan="6" class="empty">Tidak ada data.</td></tr><?php endif; ?></table></div></div>
<?php page_end('admin');
