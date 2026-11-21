<?php
require __DIR__ . '/includes/bootstrap.php';
$id = gid();
$b = $id === null ? null : row("SELECT b.*, c.nama AS kategori FROM biblios b JOIN categories c ON c.id=b.kategori_id WHERE b.id=?", [$id]);
if (!$b) { flash('err', 'Koleksi tidak ditemukan.'); redirect('opac.php'); }
if (empty($_SESSION['seen'][$id])) { q("UPDATE biblios SET dilihat=dilihat+1 WHERE id=?", [$id]); $_SESSION['seen'][$id] = 1; }
$pinjam = (int)val("SELECT COUNT(*) FROM loans WHERE biblio_id=?", [$id]);
$avg = (float)val("SELECT COALESCE(AVG(rating),0) FROM reviews WHERE biblio_id=?", [$id]);
$reviews = rows("SELECT r.*, m.nama FROM reviews r JOIN members m ON m.id=r.member_id WHERE r.biblio_id=? ORDER BY r.created_at DESC", [$id]);
$related = rows("SELECT b.*, c.nama AS kategori FROM biblios b JOIN categories c ON c.id=b.kategori_id WHERE b.kategori_id=? AND b.id<>? ORDER BY b.tgl_masuk DESC LIMIT 6", [$b['kategori_id'], $id]);
$antre = (int)val("SELECT COUNT(*) FROM reservations WHERE biblio_id=? AND status IN ('menunggu','siap')", [$id]);
$mine = null; $myres = null; $myloan = null;
if (is_member()) {
    $mid = (int)me()['id'];
    $mine = row("SELECT * FROM reviews WHERE biblio_id=? AND member_id=?", [$id, $mid]);
    $myres = row("SELECT * FROM reservations WHERE biblio_id=? AND member_id=? AND status IN ('menunggu','siap')", [$id, $mid]);
    $myloan = row("SELECT * FROM loans WHERE biblio_id=? AND member_id=? AND status='dipinjam'", [$id, $mid]);
}
$hasPdf = $b['file_pdf'] && is_file(UPLOAD_PDF . $b['file_pdf']);
page_start($b['judul'], 'public', 'opac');
?>
<p><a href="<?= url('opac.php') ?>">‹ Kembali ke katalog</a></p>
<div class="card detail">
  <div><?= cover_html($b) ?><div style="margin-top:1rem"><?= barcode_svg($b['kode_barcode'], 40) ?><div class="center muted" style="font-size:.78rem;letter-spacing:.1em"><?= e($b['kode_barcode']) ?></div></div></div>
  <div>
    <h1 style="margin-bottom:.2rem"><?= e($b['judul']) ?></h1>
    <p class="muted" style="margin-top:0"><?= e($b['pengarang']) ?> <?php if ($avg > 0): ?>· <?= stars($avg) ?> <small><?= number_format($avg, 1) ?> (<?= count($reviews) ?> ulasan)</small><?php endif; ?></p>
    <p><?= nl2br(e($b['sinopsis'])) ?></p>
    <dl>
      <dt>Penerbit</dt><dd><?= e($b['penerbit']) ?></dd>
      <dt>Tahun / Edisi</dt><dd><?= e($b['tahun']) ?> <?= $b['edisi'] ? '· ' . e($b['edisi']) : '' ?></dd>
      <dt>Kategori</dt><dd><?= e($b['kategori']) ?></dd>
      <dt>ISBN</dt><dd><?= e($b['isbn'] ?: '-') ?></dd>
      <dt>Bahasa / Halaman</dt><dd><?= e($b['bahasa']) ?> · <?= e($b['halaman']) ?> hlm</dd>
      <dt>Lokasi rak</dt><dd><?= e($b['lokasi_rak']) ?></dd>
      <dt>Ketersediaan</dt><dd><?php if ($b['stok_tersedia'] > 0): ?><span class="badge b-ok">Tersedia <?= (int)$b['stok_tersedia'] ?> dari <?= (int)$b['stok_total'] ?> eksemplar</span><?php else: ?><span class="badge b-warn">Semua eksemplar sedang dipinjam</span><?php endif; ?> <small class="muted"><?= $antre ?> antrean reservasi</small></dd>
      <dt>Statistik</dt><dd><?= $pinjam ?>× dipinjam · <?= (int)$b['dilihat'] ?>× dilihat</dd>
    </dl>
    <div class="actions" style="margin-top:1.2rem">
      <?php if ($hasPdf): ?><a class="btn" href="#pdf">📄 Baca PDF</a><a class="btn btn-mint" href="<?= url('file.php?id=' . $b['id'] . '&dl=1') ?>">Unduh PDF</a><?php endif; ?>
      <?php if (is_member()): ?>
        <?php if ($myloan): ?><span class="badge b-warn">Sedang Anda pinjam (jatuh tempo <?= e(tgl($myloan['tgl_jatuh_tempo'])) ?>)</span>
        <?php elseif ($myres): ?><span class="badge b-ok">Anda sudah mereservasi (<?= e($myres['status']) ?>)</span><a class="btn btn-ghost btn-sm" href="<?= url('member/reservations.php') ?>">Lihat reservasi</a>
        <?php else: ?><form method="post" action="<?= url('reserve.php') ?>" class="inline-form"><?= csrf() ?><input type="hidden" name="biblio_id" value="<?= $b['id'] ?>"><button class="btn btn-soft" type="submit">🔖 Reservasi buku ini</button></form><?php endif; ?>
      <?php elseif (!is_staff()): ?><a class="btn btn-soft" href="<?= url('login.php') ?>">Masuk untuk reservasi</a><?php endif; ?>
    </div>
  </div>
</div>

<div class="card" id="pdf">
  <div class="card-head"><h3>📄 Pembaca PDF</h3><?php if ($hasPdf): ?><a class="btn btn-ghost btn-sm" target="_blank" href="<?= url('file.php?id=' . $b['id']) ?>">Buka di tab baru</a><?php endif; ?></div>
  <?php if ($hasPdf): ?>
    <iframe class="pdf-frame" src="<?= url('file.php?id=' . $b['id']) ?>#view=FitH" title="PDF <?= e($b['judul']) ?>"></iframe>
  <?php else: ?><div class="empty">Koleksi ini belum memiliki berkas PDF. Silakan datang ke perpustakaan atau reservasi buku fisiknya.</div><?php endif; ?>
</div>

<div class="card">
  <h3>Ulasan & rating</h3>
  <?php if (is_member()): ?>
  <form method="post" action="<?= url('review.php') ?>" class="grid g3" style="align-items:end"><?= csrf() ?><input type="hidden" name="biblio_id" value="<?= $b['id'] ?>">
    <div class="field"><label>Rating</label><select name="rating"><?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>" <?= $mine && (int)$mine['rating'] === $i ? 'selected' : '' ?>><?= str_repeat('★', $i) ?></option><?php endfor; ?></select></div>
    <div class="field span2"><label>Komentar</label><input type="text" name="komentar" maxlength="400" value="<?= e($mine ? $mine['komentar'] : '') ?>" placeholder="Bagikan kesanmu…"></div>
    <div><button class="btn" type="submit"><?= $mine ? 'Perbarui ulasan' : 'Kirim ulasan' ?></button></div>
  </form>
  <?php else: ?><p class="muted">Masuk sebagai anggota untuk memberi ulasan.</p><?php endif; ?>
  <?php foreach ($reviews as $r): ?><div class="review"><b><?= e($r['nama']) ?></b> <?= stars($r['rating']) ?> <small class="muted"><?= e(tgl($r['created_at'])) ?></small><br><?= e($r['komentar']) ?></div><?php endforeach; ?>
  <?php if (!$reviews): ?><p class="muted">Belum ada ulasan.</p><?php endif; ?>
</div>

<?php if ($related): ?><div class="sec-title"><h2>Koleksi serupa</h2></div><div class="shelf">
<?php foreach ($related as $r): ?><a class="book" href="<?= url('detail.php?id=' . $r['id']) ?>"><?= cover_html($r) ?><h4><?= e($r['judul']) ?></h4><small><?= e($r['pengarang']) ?></small></a><?php endforeach; ?></div><?php endif;
page_end();
