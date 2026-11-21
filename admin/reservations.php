<?php
require __DIR__ . '/../includes/bootstrap.php';
require_staff(); csrf_check();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rid = pid('rid'); $act = post('act');
    $r = $rid === null ? null : row("SELECT * FROM reservations WHERE id=?", [$rid]);
    if ($r) {
        if ($act === 'batal') { q("UPDATE reservations SET status='batal' WHERE id=?", [$rid]); promote_queue($r['biblio_id']); flash('ok', 'Reservasi dibatalkan.'); log_act("Batalkan reservasi #$rid"); }
        elseif ($act === 'diambil') { q("UPDATE reservations SET status='diambil' WHERE id=?", [$rid]); flash('ok', 'Ditandai sudah diambil.'); }
        elseif ($act === 'perpanjang') { $d = max(1, (int)setting('hari_siap_ambil', 2)); q("UPDATE reservations SET batas_ambil=DATE_ADD(GREATEST(batas_ambil,CURDATE()), INTERVAL $d DAY), status='siap' WHERE id=? AND status='siap'", [$rid]); flash('ok', 'Batas ambil diperpanjang.'); }
    }
    redirect('admin/reservations.php?' . qs());
}
expire_reservations();
$f = get('f', 'aktif');
$where = $f === 'aktif' ? "r.status IN ('menunggu','siap')" : (in_array($f, ['menunggu', 'siap', 'diambil', 'batal', 'kadaluarsa']) ? 'r.status=?' : '1=1');
$list = rows("SELECT r.*, b.judul, b.kode_barcode, m.nama, m.no_anggota, m.telp FROM reservations r JOIN biblios b ON b.id=r.biblio_id JOIN members m ON m.id=r.member_id WHERE $where ORDER BY r.tgl_reservasi DESC, r.id DESC LIMIT 200", strpos($where, '?') !== false ? [$f] : []);
$badge = ['menunggu' => 'b-warn', 'siap' => 'b-ok', 'diambil' => 'b-gray', 'batal' => 'b-gray', 'kadaluarsa' => 'b-bad'];
page_start('Reservasi Online', 'admin', 'res');
?>
<div class="chips" style="margin-bottom:1rem"><?php foreach (['aktif' => 'Aktif', 'menunggu' => 'Menunggu', 'siap' => 'Siap diambil', 'diambil' => 'Diambil', 'batal' => 'Batal', 'kadaluarsa' => 'Kadaluarsa', 'semua' => 'Semua'] as $k => $v): ?><a class="chip <?= $f === $k ? 'on' : '' ?>" href="?f=<?= $k ?>"><?= $v ?></a><?php endforeach; ?></div>
<div class="card"><div class="tbl-wrap"><table><tr><th>Tgl reservasi</th><th>Anggota</th><th>Koleksi</th><th>Status</th><th>Keterangan</th><th></th></tr>
<?php foreach ($list as $r): ?><tr><td><?= e(tgl($r['tgl_reservasi'], true)) ?></td><td><b><?= e($r['nama']) ?></b><br><small class="muted"><?= e($r['no_anggota']) ?> · <?= e($r['telp']) ?></small></td><td><?= e($r['judul']) ?><br><small class="muted"><?= e($r['kode_barcode']) ?></small></td>
<td><span class="badge <?= $badge[$r['status']] ?>"><?= e($r['status']) ?></span></td>
<td><?= $r['status'] === 'siap' ? 'Ambil sebelum ' . e(tgl($r['batas_ambil'])) : ($r['status'] === 'menunggu' ? 'Antrean ke-' . queue_position($r['id']) : '') ?></td>
<td class="right"><form method="post" class="inline-form"><?= csrf() ?><input type="hidden" name="rid" value="<?= $r['id'] ?>">
<?php if ($r['status'] === 'siap'): ?><a class="btn btn-sm" href="<?= url('admin/circulation.php?m=' . urlencode($r['no_anggota']) . '&b=' . urlencode($r['kode_barcode'])) ?>">Pinjamkan</a> <button class="btn btn-sm btn-soft" name="act" value="perpanjang" type="submit">+Hari</button> <?php endif; ?>
<?php if (in_array($r['status'], ['menunggu', 'siap'])): ?><button class="btn btn-sm btn-danger" name="act" value="batal" type="submit">Batalkan</button><?php endif; ?></form></td></tr>
<?php endforeach; if (!$list): ?><tr><td colspan="6" class="empty">Tidak ada reservasi.</td></tr><?php endif; ?></table></div></div>
<?php page_end('admin');
