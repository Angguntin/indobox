<?php
require __DIR__ . '/includes/bootstrap.php';
$q = get('q'); $f = get('f', 'all'); $kat = get('kat'); $th1 = get('th1'); $th2 = get('th2'); $bhs = get('bhs');
$avail = get('avail'); $pdf = get('pdf'); $sort = get('sort', 'terbaru'); $page = max(1, (int)get('page', 1)); $per = 12;

$where = ['1=1']; $p = [];
$fields = ['judul' => 'b.judul', 'pengarang' => 'b.pengarang', 'penerbit' => 'b.penerbit', 'isbn' => 'b.isbn', 'tahun' => 'b.tahun', 'barcode' => 'b.kode_barcode', 'sinopsis' => 'b.sinopsis'];
if ($q !== '') {
    if ($f === 'all') {
        $parts = [];
        foreach (['judul', 'pengarang', 'penerbit', 'isbn', 'sinopsis', 'barcode'] as $k) { $parts[] = $fields[$k] . ' LIKE ?'; $p[] = "%$q%"; }
        $where[] = '(' . implode(' OR ', $parts) . ')';
    } elseif (isset($fields[$f])) { $where[] = $fields[$f] . ' LIKE ?'; $p[] = "%$q%"; }
}
if ($kat !== '' && ctype_digit($kat)) { $where[] = 'b.kategori_id=?'; $p[] = (int)$kat; }
if ($th1 !== '' && ctype_digit($th1)) { $where[] = 'b.tahun>=?'; $p[] = (int)$th1; }
if ($th2 !== '' && ctype_digit($th2)) { $where[] = 'b.tahun<=?'; $p[] = (int)$th2; }
if ($bhs !== '') { $where[] = 'b.bahasa=?'; $p[] = $bhs; }
if ($avail === '1') $where[] = 'b.stok_tersedia>0';
if ($pdf === '1') $where[] = "(b.file_pdf IS NOT NULL AND b.file_pdf<>'')";
$W = implode(' AND ', $where);
$orders = ['terbaru' => 'b.tgl_masuk DESC, b.id DESC', 'judul' => 'b.judul ASC', 'tahun' => 'b.tahun DESC', 'populer' => 'pop DESC, b.judul ASC', 'rating' => 'rating DESC, b.judul ASC'];
$order = isset($orders[$sort]) ? $orders[$sort] : $orders['terbaru'];
$total = (int)val("SELECT COUNT(*) FROM biblios b WHERE $W", $p);
$off = ($page - 1) * $per;
$list = rows("SELECT b.*, c.nama AS kategori, (SELECT COUNT(*) FROM loans l WHERE l.biblio_id=b.id) AS pop, COALESCE((SELECT AVG(rating) FROM reviews r WHERE r.biblio_id=b.id),0) AS rating
              FROM biblios b JOIN categories c ON c.id=b.kategori_id WHERE $W ORDER BY $order LIMIT $per OFFSET $off", $p);
$cats = rows("SELECT * FROM categories ORDER BY nama");
$langs = rows("SELECT DISTINCT bahasa FROM biblios WHERE bahasa IS NOT NULL AND bahasa<>'' ORDER BY bahasa");

page_start('Katalog OPAC', 'public', 'opac');
?>
<h1>Katalog OPAC</h1>
<div class="opac-layout">
  <aside class="card filters no-print">
    <form method="get">
      <div class="field"><label>Kata kunci</label><input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari…"></div>
      <div class="field"><label>Cari berdasarkan</label>
        <select name="f"><?php foreach (['all' => 'Semua kolom', 'judul' => 'Judul', 'pengarang' => 'Pengarang', 'penerbit' => 'Penerbit', 'isbn' => 'ISBN', 'tahun' => 'Tahun terbit', 'barcode' => 'Kode barcode', 'sinopsis' => 'Sinopsis'] as $k => $v): ?>
          <option value="<?= $k ?>" <?= $f === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Kategori</label><select name="kat"><option value="">Semua kategori</option>
        <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $kat !== '' && (int)$kat === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['nama']) ?></option><?php endforeach; ?></select></div>
      <div class="grid g2"><div class="field"><label>Tahun dari</label><input type="number" name="th1" value="<?= e($th1) ?>" min="1900" max="2100"></div>
        <div class="field"><label>Sampai</label><input type="number" name="th2" value="<?= e($th2) ?>" min="1900" max="2100"></div></div>
      <div class="field"><label>Bahasa</label><select name="bhs"><option value="">Semua</option><?php foreach ($langs as $l): ?><option <?= $bhs === $l['bahasa'] ? 'selected' : '' ?>><?= e($l['bahasa']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Urutkan</label><select name="sort"><?php foreach (['terbaru' => 'Terbaru', 'judul' => 'Judul A–Z', 'tahun' => 'Tahun terbit', 'populer' => 'Paling sering dipinjam', 'rating' => 'Rating tertinggi'] as $k => $v): ?>
        <option value="<?= $k ?>" <?= $sort === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
      <div class="field"><label><input type="checkbox" name="avail" value="1" <?= $avail === '1' ? 'checked' : '' ?>> Hanya yang tersedia</label>
        <label><input type="checkbox" name="pdf" value="1" <?= $pdf === '1' ? 'checked' : '' ?>> Hanya yang punya PDF</label></div>
      <div class="actions"><button class="btn" type="submit">Terapkan</button><a class="btn btn-ghost" href="<?= url('opac.php') ?>">Reset</a></div>
    </form>
  </aside>
  <section>
    <p class="muted"><b><?= $total ?></b> koleksi ditemukan<?= $q !== '' ? ' untuk “' . e($q) . '”' : '' ?>.</p>
    <div class="card res-list">
      <?php if (!$list): ?><div class="empty">Tidak ada koleksi yang cocok. Coba kata kunci atau filter lain.</div><?php endif; ?>
      <?php foreach ($list as $b): ?>
        <div class="item">
          <a href="<?= url('detail.php?id=' . $b['id']) ?>"><?= cover_html($b) ?></a>
          <div>
            <h3><a href="<?= url('detail.php?id=' . $b['id']) ?>"><?= e($b['judul']) ?></a></h3>
            <div class="muted"><?= e($b['pengarang']) ?> · <?= e($b['penerbit']) ?> · <?= e($b['tahun']) ?></div>
            <div style="margin:.4rem 0"><span class="badge"><?= e($b['kategori']) ?></span>
              <?php if ($b['stok_tersedia'] > 0): ?><span class="badge b-ok">Tersedia <?= (int)$b['stok_tersedia'] ?>/<?= (int)$b['stok_total'] ?></span><?php else: ?><span class="badge b-warn">Sedang dipinjam</span><?php endif; ?>
              <?php if ($b['file_pdf']): ?><span class="badge b-ok">📄 PDF</span><?php endif; ?>
              <?php if ($b['rating'] > 0): ?><?= stars($b['rating']) ?><?php endif; ?></div>
            <p class="muted" style="margin:.2rem 0"><?= e(mb_strimwidth((string)$b['sinopsis'], 0, 150, '…')) ?></p>
          </div>
          <div class="act"><a class="btn btn-sm" href="<?= url('detail.php?id=' . $b['id']) ?>#pdf"><?= $b['file_pdf'] ? 'Buka PDF' : 'Lihat detail' ?></a></div>
        </div>
      <?php endforeach; ?>
    </div>
    <?= pager($total, $per, $page) ?>
  </section>
</div>
<?php page_end();
