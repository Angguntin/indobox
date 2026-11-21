<?php
require __DIR__ . '/../includes/bootstrap.php';
require_staff();
$ym = preg_match('/^\d{4}-\d{2}$/', get('bulan')) ? get('bulan') : date('Y-m');
$days = (int)date('t', strtotime($ym . '-01'));
$M = "DATE_FORMAT(l.tgl_pinjam,'%Y-%m')=?";
$pinjam = (int)val("SELECT COUNT(*) FROM loans l WHERE $M", [$ym]);
$kembali = (int)val("SELECT COUNT(*) FROM loans WHERE DATE_FORMAT(tgl_kembali,'%Y-%m')=?", [$ym]);
$peminjam = (int)val("SELECT COUNT(DISTINCT member_id) FROM loans l WHERE $M", [$ym]);
$telat = (int)val("SELECT COUNT(*) FROM loans WHERE DATE_FORMAT(tgl_kembali,'%Y-%m')=? AND tgl_kembali>tgl_jatuh_tempo", [$ym]);
$dendaBln = (int)val("SELECT COALESCE(SUM(denda),0) FROM loans WHERE DATE_FORMAT(tgl_kembali,'%Y-%m')=?", [$ym]);
$topM = rows("SELECT m.nama, m.no_anggota, t.nama tipe, COUNT(*) n FROM loans l JOIN members m ON m.id=l.member_id JOIN member_types t ON t.id=m.type_id WHERE $M GROUP BY m.id ORDER BY n DESC, m.nama LIMIT 10", [$ym]);
$topB = rows("SELECT b.judul, b.pengarang, b.id, COUNT(*) n FROM loans l JOIN biblios b ON b.id=l.biblio_id WHERE $M GROUP BY b.id ORDER BY n DESC, b.judul LIMIT 10", [$ym]);
$daily = array_fill(1, $days, 0);
foreach (rows("SELECT DAY(l.tgl_pinjam) d, COUNT(*) n FROM loans l WHERE $M GROUP BY d", [$ym]) as $r) $daily[(int)$r['d']] = (int)$r['n'];
$l12 = []; $v12 = [];
for ($i = 11; $i >= 0; $i--) { $mm = month_back($i); $l12[] = substr(nama_bulan($mm), 0, 3); $v12[] = (int)val("SELECT COUNT(*) FROM loans l WHERE $M", [$mm]); }
$cat = rows("SELECT c.nama, COUNT(*) n FROM loans l JOIN biblios b ON b.id=l.biblio_id JOIN categories c ON c.id=b.kategori_id WHERE $M GROUP BY c.id ORDER BY n DESC", [$ym]);
$tipe = rows("SELECT t.nama, COUNT(*) n FROM loans l JOIN members m ON m.id=l.member_id JOIN member_types t ON t.id=m.type_id WHERE $M GROUP BY t.id ORDER BY n DESC", [$ym]);
$hari = array_fill(1, 7, 0);
foreach (rows("SELECT DAYOFWEEK(l.tgl_pinjam) d, COUNT(*) n FROM loans l WHERE $M GROUP BY d", [$ym]) as $r) $hari[(int)$r['d']] = (int)$r['n'];
$namaHari = [1 => 'Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
$nunggu = rows("SELECT b.judul, b.pengarang FROM biblios b WHERE NOT EXISTS (SELECT 1 FROM loans l WHERE l.biblio_id=b.id) ORDER BY b.judul LIMIT 8");
$dilihat = rows("SELECT judul, dilihat FROM biblios ORDER BY dilihat DESC, judul LIMIT 6");
$rating = rows("SELECT b.judul, AVG(r.rating) a, COUNT(*) n FROM reviews r JOIN biblios b ON b.id=r.biblio_id GROUP BY b.id ORDER BY a DESC, n DESC LIMIT 6");
page_start('Statistik Perpustakaan', 'admin', 'stats');
?>
<form method="get" class="toolbar no-print"><div class="field"><label>Pilih bulan</label><input type="month" name="bulan" value="<?= e($ym) ?>"></div><button class="btn" type="submit">Tampilkan</button><button type="button" class="btn btn-ghost" data-print>🖨️ Cetak</button></form>
<h2>Ringkasan <?= e(nama_bulan($ym)) ?></h2>
<div class="kpis">
  <div class="kpi"><div class="ic">📤</div><div><b><?= $pinjam ?></b><span>Peminjaman</span></div></div>
  <div class="kpi m"><div class="ic">📥</div><div><b><?= $kembali ?></b><span>Pengembalian</span></div></div>
  <div class="kpi"><div class="ic">🧑‍🎓</div><div><b><?= $peminjam ?></b><span>Anggota meminjam</span></div></div>
  <div class="kpi m"><div class="ic">⏰</div><div><b><?= $telat ?></b><span>Kembali terlambat</span></div></div>
  <div class="kpi"><div class="ic">💰</div><div><b><?= rupiah($dendaBln) ?></b><span>Denda tercatat</span></div></div>
</div>
<div class="grid g2">
  <div class="card"><h3>🏆 Anggota paling banyak meminjam — <?= e(nama_bulan($ym)) ?></h3><div class="podium">
    <?php foreach ($topM as $i => $t): ?><div class="pod"><span class="no"><?= $i + 1 ?></span><div><b><?= e($t['nama']) ?></b><small><?= e($t['no_anggota']) ?> · <?= e($t['tipe']) ?></small></div><span class="n"><?= $t['n'] ?>×</span></div><?php endforeach; if (!$topM) echo '<p class="muted">Belum ada peminjaman pada bulan ini.</p>'; ?></div></div>
  <div class="card"><h3>📚 Koleksi paling banyak dipinjam — <?= e(nama_bulan($ym)) ?></h3><div class="podium">
    <?php foreach ($topB as $i => $t): ?><div class="pod"><span class="no"><?= $i + 1 ?></span><div><b><a href="<?= url('detail.php?id=' . $t['id']) ?>"><?= e($t['judul']) ?></a></b><small><?= e($t['pengarang']) ?></small></div><span class="n"><?= $t['n'] ?>×</span></div><?php endforeach; if (!$topB) echo '<p class="muted">Belum ada peminjaman pada bulan ini.</p>'; ?></div></div>
</div>
<div class="card"><h3>Peminjaman harian — <?= e(nama_bulan($ym)) ?></h3><?= bars_chart(array_keys($daily), array_values($daily)) ?></div>
<div class="grid g2">
  <div class="card"><h3>Tren 12 bulan terakhir</h3><?= bars_chart($l12, $v12) ?></div>
  <div class="card"><h3>Kategori favorit bulan ini</h3><?= $cat ? donut(array_column($cat, 'nama'), array_map('intval', array_column($cat, 'n'))) : '<p class="muted">Belum ada data.</p>' ?></div>
</div>
<div class="grid g3">
  <div class="card"><h3>Hari tersibuk</h3><?= bars_chart(array_values($namaHari), array_values($hari)) ?></div>
  <div class="card"><h3>Peminjaman per tipe anggota</h3><?= $tipe ? hbars(array_column($tipe, 'nama'), array_map('intval', array_column($tipe, 'n'))) : '<p class="muted">Belum ada data.</p>' ?></div>
  <div class="card"><h3>Paling sering dilihat (OPAC)</h3><?= hbars(array_column($dilihat, 'judul'), array_map('intval', array_column($dilihat, 'dilihat'))) ?></div>
</div>
<div class="grid g2">
  <div class="card"><h3>⭐ Rating tertinggi</h3><?php foreach ($rating as $r): ?><div class="review"><b><?= e($r['judul']) ?></b> <?= stars($r['a']) ?> <small class="muted"><?= number_format($r['a'], 1) ?> (<?= $r['n'] ?> ulasan)</small></div><?php endforeach; if (!$rating) echo '<p class="muted">Belum ada ulasan.</p>'; ?></div>
  <div class="card"><h3>Koleksi yang belum pernah dipinjam</h3><?php foreach ($nunggu as $r): ?><div class="review"><b><?= e($r['judul']) ?></b> <small class="muted"><?= e($r['pengarang']) ?></small></div><?php endforeach; if (!$nunggu) echo '<p class="muted">Semua koleksi pernah dipinjam 🎉</p>'; ?></div>
</div>
<?php page_end('admin');
