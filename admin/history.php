<?php
require __DIR__ . '/../includes/bootstrap.php';
require_staff();
$from = get('from'); $to = get('to'); $mem = get('member'); $bk = get('buku'); $st = get('status'); $page = max(1, (int)get('page', 1)); $per = 25;
$where = ['1=1']; $p = [];
if ($from !== '') { $where[] = 'l.tgl_pinjam>=?'; $p[] = $from; }
if ($to !== '') { $where[] = 'l.tgl_pinjam<=?'; $p[] = $to; }
if ($mem !== '') { $where[] = '(m.no_anggota=? OR m.nama LIKE ?)'; $p[] = $mem; $p[] = "%$mem%"; }
if ($bk !== '') { $where[] = '(b.judul LIKE ? OR b.kode_barcode=?)'; $p[] = "%$bk%"; $p[] = strtoupper($bk); }
if (in_array($st, ['dipinjam', 'kembali'])) { $where[] = 'l.status=?'; $p[] = $st; }
elseif ($st === 'terlambat') $where[] = "((l.status='dipinjam' AND l.tgl_jatuh_tempo<CURDATE()) OR (l.status='kembali' AND l.tgl_kembali>l.tgl_jatuh_tempo))";
$W = implode(' AND ', $where);
$base = "FROM loans l JOIN biblios b ON b.id=l.biblio_id JOIN members m ON m.id=l.member_id WHERE $W";
if (get('export') === '1') {
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="riwayat-peminjaman-' . date('Ymd') . '.csv"');
    $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF"); fputcsv($o, ['id', 'no_anggota', 'nama', 'barcode', 'judul', 'tgl_pinjam', 'jatuh_tempo', 'tgl_kembali', 'denda', 'dibayar', 'status']);
    foreach (rows("SELECT l.*, b.judul, b.kode_barcode, m.nama, m.no_anggota $base ORDER BY l.id DESC", $p) as $r) fputcsv($o, [$r['id'], $r['no_anggota'], $r['nama'], $r['kode_barcode'], $r['judul'], $r['tgl_pinjam'], $r['tgl_jatuh_tempo'], $r['tgl_kembali'], $r['denda'], $r['denda_dibayar'], $r['status']]);
    exit;
}
$total = (int)val("SELECT COUNT(*) $base", $p); $off = ($page - 1) * $per;
$sum = row("SELECT COALESCE(SUM(l.denda),0) d, COALESCE(SUM(l.denda_dibayar),0) b $base", $p);
$list = rows("SELECT l.*, b.judul, b.kode_barcode, m.nama, m.no_anggota $base ORDER BY l.tgl_pinjam DESC, l.id DESC LIMIT $per OFFSET $off", $p);
page_start('Riwayat Peminjaman & Pengembalian', 'admin', 'history');
?>
<div class="card">
<form method="get" class="toolbar no-print">
  <div class="field"><label>Dari</label><input type="date" name="from" value="<?= e($from) ?>"></div><div class="field"><label>Sampai</label><input type="date" name="to" value="<?= e($to) ?>"></div>
  <div class="field"><label>Anggota</label><input type="text" name="member" value="<?= e($mem) ?>" placeholder="No./nama"></div><div class="field"><label>Koleksi</label><input type="text" name="buku" value="<?= e($bk) ?>" placeholder="Judul/barcode"></div>
  <div class="field"><label>Status</label><select name="status"><option value="">Semua</option><option value="dipinjam" <?= $st === 'dipinjam' ? 'selected' : '' ?>>Dipinjam</option><option value="kembali" <?= $st === 'kembali' ? 'selected' : '' ?>>Dikembalikan</option><option value="terlambat" <?= $st === 'terlambat' ? 'selected' : '' ?>>Terlambat</option></select></div>
  <button class="btn" type="submit">Filter</button><a class="btn btn-ghost" href="?<?= e(qs(['export' => 1])) ?>">⬇ CSV</a><button type="button" class="btn btn-ghost" data-print>🖨️ Cetak</button>
</form>
<p class="muted"><b><?= $total ?></b> transaksi · total denda <b><?= rupiah($sum['d']) ?></b> · dibayar <b><?= rupiah($sum['b']) ?></b></p>
<div class="tbl-wrap"><table><tr><th>ID</th><th>Anggota</th><th>Koleksi</th><th>Pinjam</th><th>Jatuh tempo</th><th>Kembali</th><th>Denda</th><th>Status</th></tr>
<?php foreach ($list as $l): ?><tr><td><?= $l['id'] ?></td><td><b><?= e($l['nama']) ?></b><br><small class="muted"><?= e($l['no_anggota']) ?></small></td><td><?= e($l['judul']) ?><br><small class="muted"><?= e($l['kode_barcode']) ?></small></td>
<td><?= e(tgl($l['tgl_pinjam'])) ?></td><td><?= e(tgl($l['tgl_jatuh_tempo'])) ?></td><td><?= e(tgl($l['tgl_kembali'])) ?></td><td><?= $l['denda'] ? rupiah($l['denda']) : '-' ?></td>
<td><span class="badge <?= $l['status'] === 'kembali' ? 'b-ok' : 'b-warn' ?>"><?= $l['status'] === 'kembali' ? 'Kembali' : 'Dipinjam' ?></span></td></tr>
<?php endforeach; if (!$list): ?><tr><td colspan="8" class="empty">Tidak ada data.</td></tr><?php endif; ?></table></div>
<?= pager($total, $per, $page) ?></div>
<?php page_end('admin');
