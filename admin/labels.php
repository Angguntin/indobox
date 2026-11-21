<?php
require __DIR__ . '/../includes/bootstrap.php';
require_staff(); csrf_check();
$go = isset($_REQUEST['go']); $mode = isset($_REQUEST['mode']) && $_REQUEST['mode'] === 'katalog' ? 'katalog' : 'label';
$kat = isset($_REQUEST['kat']) && ctype_digit((string)$_REQUEST['kat']) ? (int)$_REQUEST['kat'] : null;
$ids = [];
if (!empty($_POST['ids']) && is_array($_POST['ids'])) foreach ($_POST['ids'] as $i) if (ctype_digit((string)$i)) $ids[] = (int)$i;
if ($go) {
    $where = '1=1'; $p = [];
    if ($ids) { $where = 'b.id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')'; $p = $ids; }
    elseif ($kat !== null) { $where = 'b.kategori_id=?'; $p = [$kat]; }
    $list = rows("SELECT b.*, c.nama kategori FROM biblios b JOIN categories c ON c.id=b.kategori_id WHERE $where ORDER BY c.nama, b.judul", $p);
}
page_start($mode === 'katalog' ? 'Cetak Katalog' : 'Cetak Label & Katalog', 'admin', 'labels');
if (!$go): $cats = rows("SELECT * FROM categories ORDER BY nama"); ?>
<div class="card"><h3>Cetak label barcode atau katalog</h3>
<p class="muted">Gunakan tombol “Cetak” pada peramban lalu pilih <b>Simpan sebagai PDF</b> untuk mendapatkan berkas PDF katalog/label.</p>
<form method="get" class="toolbar"><input type="hidden" name="go" value="1">
  <div class="field"><label>Jenis</label><select name="mode"><option value="label">Label barcode</option><option value="katalog">Katalog (daftar koleksi)</option></select></div>
  <div class="field"><label>Kategori</label><select name="kat"><option value="">Semua kategori</option><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['nama']) ?></option><?php endforeach; ?></select></div>
  <button class="btn" type="submit">Tampilkan</button></form>
<p class="hint">Atau centang beberapa koleksi langsung dari menu <a href="<?= url('admin/biblio.php') ?>">Daftar Koleksi</a>.</p></div>
<?php else: ?>
<div class="no-print actions" style="margin-bottom:1rem"><button class="btn" data-print>🖨️ Cetak / Simpan PDF</button><a class="btn btn-ghost" href="<?= url('admin/labels.php') ?>">Kembali</a></div>
<div class="card">
<?php if ($mode === 'label'): ?>
  <div class="labels"><?php foreach ($list as $b): ?><div class="label-card"><b><?= e(mb_strimwidth($b['judul'], 0, 36, '…')) ?></b><?= barcode_svg($b['kode_barcode'], 40) ?><small><?= e($b['kode_barcode']) ?> · <?= e($b['lokasi_rak']) ?></small></div><?php endforeach; ?></div>
<?php else: ?>
  <h2 style="text-align:center;margin-bottom:0">Katalog Koleksi <?= e(setting('nama_perpustakaan')) ?></h2><p class="center muted">Dicetak <?= e(tgl(date('Y-m-d'))) ?> · <?= count($list) ?> judul</p>
  <div class="tbl-wrap"><table><tr><th>No</th><th>Barcode</th><th>Judul</th><th>Pengarang</th><th>Penerbit</th><th>Tahun</th><th>Kategori</th><th>Rak</th></tr>
  <?php foreach ($list as $i => $b): ?><tr><td><?= $i + 1 ?></td><td><?= e($b['kode_barcode']) ?></td><td><?= e($b['judul']) ?></td><td><?= e($b['pengarang']) ?></td><td><?= e($b['penerbit']) ?></td><td><?= e($b['tahun']) ?></td><td><?= e($b['kategori']) ?></td><td><?= e($b['lokasi_rak']) ?></td></tr><?php endforeach; ?></table></div>
<?php endif; ?></div>
<?php endif;
page_end('admin');
