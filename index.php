<?php
require __DIR__ . '/includes/bootstrap.php';
$stat = [
    'judul'   => (int)val("SELECT COUNT(*) FROM biblios"),
    'eks'     => (int)val("SELECT COALESCE(SUM(stok_total),0) FROM biblios"),
    'anggota' => (int)val("SELECT COUNT(*) FROM members WHERE status='aktif'"),
    'bulan'   => (int)val("SELECT COUNT(*) FROM loans WHERE DATE_FORMAT(tgl_pinjam,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')"),
];
$baru = rows("SELECT b.*, c.nama AS kategori FROM biblios b JOIN categories c ON c.id=b.kategori_id ORDER BY b.tgl_masuk DESC, b.id DESC LIMIT 8");
$populer = rows("SELECT b.*, c.nama AS kategori, COUNT(l.id) AS n FROM loans l JOIN biblios b ON b.id=l.biblio_id JOIN categories c ON c.id=b.kategori_id
                 WHERE DATE_FORMAT(l.tgl_pinjam,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m') GROUP BY b.id ORDER BY n DESC, b.judul LIMIT 6");
if (!$populer) $populer = rows("SELECT b.*, c.nama AS kategori, COUNT(l.id) AS n FROM biblios b JOIN categories c ON c.id=b.kategori_id LEFT JOIN loans l ON l.biblio_id=b.id GROUP BY b.id ORDER BY n DESC LIMIT 6");
$kats = rows("SELECT c.id, c.nama, COUNT(b.id) AS n FROM categories c LEFT JOIN biblios b ON b.kategori_id=c.id GROUP BY c.id ORDER BY c.nama");
$news = rows("SELECT * FROM announcements WHERE aktif=1 ORDER BY tgl DESC, id DESC LIMIT 3");

page_start('Beranda', 'public', 'home');
?>
<section class="hero">
  <div>
    <h1>Temukan bukumu, baca langsung.</h1>
    <p><?= e(setting('tagline')) ?> Cari katalog, buka PDF koleksinya, dan reservasi buku favorit dari mana saja.</p>
    <form class="searchbox" action="<?= url('opac.php') ?>" method="get">
      <select name="f" aria-label="Cari berdasarkan">
        <option value="all">Semua</option><option value="judul">Judul</option><option value="pengarang">Pengarang</option><option value="penerbit">Penerbit</option><option value="isbn">ISBN</option>
      </select>
      <input type="search" name="q" placeholder="Ketik judul, pengarang, atau kata kunci…" aria-label="Kata kunci">
      <button class="btn" type="submit">Cari</button>
    </form>
  </div>
  <div class="spines" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i></div>
</section>

<div class="stat-strip">
  <div><b><?= $stat['judul'] ?></b><span>Judul koleksi</span></div>
  <div><b><?= $stat['eks'] ?></b><span>Eksemplar tersedia di rak</span></div>
  <div><b><?= $stat['anggota'] ?></b><span>Anggota aktif</span></div>
  <div><b><?= $stat['bulan'] ?></b><span>Peminjaman bulan ini</span></div>
</div>

<div class="chips">
  <?php foreach ($kats as $k): ?><a class="chip" href="<?= url('opac.php?kat=' . $k['id']) ?>"><?= e($k['nama']) ?> (<?= $k['n'] ?>)</a><?php endforeach; ?>
</div>

<div class="sec-title"><h2>Paling banyak dipinjam bulan ini</h2><a href="<?= url('opac.php?sort=populer') ?>">Lihat semua</a></div>
<div class="shelf">
<?php foreach ($populer as $b): ?>
  <a class="book" href="<?= url('detail.php?id=' . $b['id']) ?>"><?= cover_html($b) ?><h4><?= e($b['judul']) ?></h4><small><?= e($b['pengarang']) ?></small>
    <div class="meta"><span class="badge b-ok"><?= (int)$b['n'] ?>× dipinjam</span></div></a>
<?php endforeach; ?>
</div>

<div class="sec-title"><h2>Koleksi terbaru</h2><a href="<?= url('opac.php?sort=terbaru') ?>">Lihat semua</a></div>
<div class="shelf">
<?php foreach ($baru as $b): ?>
  <a class="book" href="<?= url('detail.php?id=' . $b['id']) ?>"><?= cover_html($b) ?><h4><?= e($b['judul']) ?></h4><small><?= e($b['pengarang']) ?></small>
    <div class="meta"><span class="badge"><?= e($b['kategori']) ?></span><?php if ($b['file_pdf']): ?><span class="badge b-ok">PDF</span><?php endif; ?></div></a>
<?php endforeach; ?>
</div>

<?php if ($news): ?>
<div class="sec-title"><h2>Pengumuman</h2><a href="<?= url('news.php') ?>">Semua pengumuman</a></div>
<div class="grid g3">
<?php foreach ($news as $n): ?>
  <div class="card news-item"><small class="muted"><?= e(tgl($n['tgl'])) ?></small><h3><?= e($n['judul']) ?></h3><p><?= e(mb_strimwidth($n['isi'], 0, 140, '…')) ?></p></div>
<?php endforeach; ?>
</div>
<?php endif;
page_end();
