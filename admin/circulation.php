<?php
require __DIR__ . '/../includes/bootstrap.php';
require_staff(); csrf_check();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no = strtoupper(post('no_anggota')); $bc = strtoupper(post('barcode'));
    $m = row("SELECT id FROM members WHERE no_anggota=?", [$no]);
    $b = row("SELECT id FROM biblios WHERE kode_barcode=?", [$bc]);
    if (!$m) flash('err', 'Nomor anggota tidak ditemukan.');
    elseif (!$b) flash('err', 'Kode barcode koleksi tidak ditemukan.');
    else { $r = checkout((int)$m['id'], (int)$b['id'], (int)me()['id']); flash($r[0] ? 'ok' : 'err', $r[1]); if ($r[0]) log_act("Pinjam: $bc oleh $no"); }
    redirect('admin/circulation.php?m=' . urlencode($no));
}
$no = strtoupper(get('m')); $pre = strtoupper(get('b'));
$m = $no !== '' ? row("SELECT id FROM members WHERE no_anggota=?", [$no]) : null;
$info = $m ? member_info((int)$m['id']) : null;
if ($no !== '' && !$m) flash('err', 'Nomor anggota tidak ditemukan.');
$loans = $info ? rows("SELECT l.*, b.judul, b.kode_barcode FROM loans l JOIN biblios b ON b.id=l.biblio_id WHERE l.member_id=? AND l.status='dipinjam' ORDER BY l.tgl_jatuh_tempo", [$info['id']]) : [];
$res = $info ? rows("SELECT r.*, b.judul, b.kode_barcode FROM reservations r JOIN biblios b ON b.id=r.biblio_id WHERE r.member_id=? AND r.status='siap'", [$info['id']]) : [];
page_start('Peminjaman', 'admin', 'pinjam');
?>
<div class="grid g2" style="align-items:start">
<div class="card"><h3>1. Pilih anggota</h3>
  <form method="get" class="actions"><div class="lookup" style="flex:1"><input class="big-input" type="text" name="m" list="dl-m" value="<?= e($no) ?>" placeholder="Scan / ketik no. anggota atau nama" data-suggest="member" data-url="<?= url('admin/ajax.php') ?>" autocomplete="off" <?= $info ? '' : 'data-autofocus' ?>><datalist id="dl-m"></datalist></div><button class="btn" type="submit">Cari</button></form>
  <?php if ($info): ?>
  <div class="pick"><div class="avatar"><?= e(mb_strtoupper(mb_substr($info['nama'], 0, 1))) ?></div><div><b><?= e($info['nama']) ?></b> <span class="badge <?= $info['status'] === 'aktif' ? 'b-ok' : 'b-bad' ?>"><?= e($info['status']) ?></span><br>
    <small class="muted"><?= e($info['no_anggota']) ?> · <?= e($info['tipe']) ?> · berlaku s/d <?= e(tgl($info['tgl_expire'])) ?></small><br>
    <small>Pinjam <b><?= count($loans) ?>/<?= (int)$info['maks_pinjam'] ?></b> · lama <?= (int)$info['lama_pinjam'] ?> hari · denda <?= rupiah($info['denda_per_hari']) ?>/hari<?= member_unpaid($info['id']) > 0 ? ' · <b style="color:#b3263e">denda belum bayar ' . rupiah(member_unpaid($info['id'])) . '</b>' : '' ?></small></div></div>
  <?php foreach ($res as $r): ?><div class="alert alert-ok">🔖 Reservasi siap: <b><?= e($r['judul']) ?></b> (<code><?= e($r['kode_barcode']) ?></code>) — ambil sebelum <?= e(tgl($r['batas_ambil'])) ?></div><?php endforeach; ?>
  <?php endif; ?>
</div>
<div class="card"><h3>2. Scan koleksi</h3>
  <?php if ($info): ?>
  <form method="post"><?= csrf() ?><input type="hidden" name="no_anggota" value="<?= e($info['no_anggota']) ?>">
    <div class="field lookup"><label>Kode barcode koleksi</label><input class="big-input" type="text" name="barcode" list="dl-b" value="<?= e($pre) ?>" placeholder="IB-0001" data-suggest="biblio" data-url="<?= url('admin/ajax.php') ?>" autocomplete="off" required <?= $info ? 'data-autofocus' : '' ?>><datalist id="dl-b"></datalist></div>
    <button class="btn" type="submit">📤 Pinjamkan</button> <span class="hint">Stok berkurang otomatis. Jatuh tempo mengikuti tipe anggota.</span>
  </form>
  <?php else: ?><p class="muted">Pilih anggota terlebih dahulu.</p><?php endif; ?>
</div></div>
<?php if ($info): ?><div class="card"><h3>Pinjaman aktif <?= e($info['nama']) ?></h3>
<div class="tbl-wrap"><table><tr><th>Barcode</th><th>Judul</th><th>Pinjam</th><th>Jatuh tempo</th><th>Status</th></tr>
<?php foreach ($loans as $l): $late = days_late($l['tgl_jatuh_tempo']); ?><tr><td><?= e($l['kode_barcode']) ?></td><td><?= e($l['judul']) ?></td><td><?= e(tgl($l['tgl_pinjam'])) ?></td><td><?= e(tgl($l['tgl_jatuh_tempo'])) ?></td>
<td><?= $late ? '<span class="badge b-bad">Terlambat ' . $late . ' hari</span>' : '<span class="badge b-ok">Aman</span>' ?></td></tr><?php endforeach; if (!$loans): ?><tr><td colspan="5" class="empty">Tidak ada pinjaman aktif.</td></tr><?php endif; ?></table></div></div><?php endif;
page_end('admin');
