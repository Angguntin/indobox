<?php
require __DIR__ . '/../includes/bootstrap.php';
require_staff();
$ym = date('Y-m');
$k = [
 ['📚', 'Judul koleksi', (int)val("SELECT COUNT(*) FROM biblios"), ''],
 ['📦', 'Total eksemplar', (int)val("SELECT COALESCE(SUM(stok_total),0) FROM biblios"), 'm'],
 ['👥', 'Anggota aktif', (int)val("SELECT COUNT(*) FROM members WHERE status='aktif'"), ''],
 ['📖', 'Sedang dipinjam', (int)val("SELECT COUNT(*) FROM loans WHERE status='dipinjam'"), 'm'],
 ['⏰', 'Terlambat', (int)val("SELECT COUNT(*) FROM loans WHERE status='dipinjam' AND tgl_jatuh_tempo<CURDATE()"), ''],
 ['📤', 'Dipinjam hari ini', (int)val("SELECT COUNT(*) FROM loans WHERE tgl_pinjam=CURDATE()"), 'm'],
 ['📥', 'Kembali hari ini', (int)val("SELECT COUNT(*) FROM loans WHERE tgl_kembali=CURDATE()"), ''],
 ['🔖', 'Reservasi aktif', (int)val("SELECT COUNT(*) FROM reservations WHERE status IN ('menunggu','siap')"), 'm'],
 ['💰', 'Denda belum dibayar', rupiah((int)val("SELECT COALESCE(SUM(denda-denda_dibayar),0) FROM loans WHERE status='kembali'")), ''],
 ['🚪', 'Kunjungan hari ini', (int)val("SELECT COUNT(*) FROM visits WHERE DATE(waktu)=CURDATE()"), 'm'],
];
$labels = []; $vals = [];
for ($i = 5; $i >= 0; $i--) { $mm = month_back($i); $labels[] = nama_bulan($mm); $vals[] = (int)val("SELECT COUNT(*) FROM loans WHERE DATE_FORMAT(tgl_pinjam,'%Y-%m')=?", [$mm]); }
$labels = array_map(function ($l) { return mb_substr($l, 0, 3); }, $labels);
$topB = rows("SELECT b.judul, COUNT(*) n FROM loans l JOIN biblios b ON b.id=l.biblio_id WHERE DATE_FORMAT(l.tgl_pinjam,'%Y-%m')=? GROUP BY b.id ORDER BY n DESC, b.judul LIMIT 5", [$ym]);
$topM = rows("SELECT m.nama, COUNT(*) n FROM loans l JOIN members m ON m.id=l.member_id WHERE DATE_FORMAT(l.tgl_pinjam,'%Y-%m')=? GROUP BY m.id ORDER BY n DESC, m.nama LIMIT 5", [$ym]);
$late = rows("SELECT l.*, b.judul, m.nama, m.no_anggota FROM loans l JOIN biblios b ON b.id=l.biblio_id JOIN members m ON m.id=l.member_id WHERE l.status='dipinjam' AND l.tgl_jatuh_tempo<CURDATE() ORDER BY l.tgl_jatuh_tempo LIMIT 6");
$recent = rows("SELECT l.*, b.judul, m.nama FROM loans l JOIN biblios b ON b.id=l.biblio_id JOIN members m ON m.id=l.member_id ORDER BY l.id DESC LIMIT 7");
$resv = rows("SELECT r.*, b.judul, m.nama FROM reservations r JOIN biblios b ON b.id=r.biblio_id JOIN members m ON m.id=r.member_id WHERE r.status IN ('menunggu','siap') ORDER BY r.tgl_reservasi DESC LIMIT 5");
page_start('Dashboard', 'admin', 'dash');
?>
<div class="card" style="background:linear-gradient(120deg,var(--pink-100),var(--mint-100));border:0">
  <h2 style="margin:0">Selamat datang, <?= e(me()['nama']) ?> </h2>
  <p class="muted" style="margin:.3rem 0 0">Hari ini <?= e(tgl(date('Y-m-d'))) ?> — semua ringkasan perpustakaan <?= e(setting('nama_perpustakaan')) ?> ada di bawah.</p>
</div>
<div class="quick">
  <a href="<?= url('admin/circulation.php') ?>"><span>📤</span>Pinjamkan</a><a href="<?= url('admin/return.php') ?>"><span>📥</span>Kembalikan</a>
  <a href="<?= url('admin/biblio_form.php') ?>"><span>➕</span>Tambah Koleksi</a><a href="<?= url('admin/member_form.php') ?>"><span>🪪</span>Tambah Anggota</a>
  <a href="<?= url('admin/reservations.php') ?>"><span>🔖</span>Reservasi</a><a href="<?= url('admin/stats.php') ?>"><span>📊</span>Statistik</a>
</div>
<div class="kpis"><?php foreach ($k as $x): ?><div class="kpi <?= $x[3] ?>"><div class="ic"><?= $x[0] ?></div><div><b><?= $x[2] ?></b><span><?= $x[1] ?></span></div></div><?php endforeach; ?></div>
<div class="grid g2">
  <div class="card"><h3>Peminjaman 6 bulan terakhir</h3><?= bars_chart($labels, $vals) ?></div>
  <div class="card"><h3>Buku terpopuler — <?= e(nama_bulan($ym)) ?></h3>
    <div class="podium"><?php foreach ($topB as $i => $t): ?><div class="pod"><span class="no"><?= $i + 1 ?></span><div><b><?= e($t['judul']) ?></b></div><span class="n"><?= $t['n'] ?>×</span></div><?php endforeach; if (!$topB) echo '<p class="muted">Belum ada peminjaman bulan ini.</p>'; ?></div></div>
</div>
<div class="grid g2">
  <div class="card"><h3>Anggota paling aktif — <?= e(nama_bulan($ym)) ?></h3>
    <div class="podium"><?php foreach ($topM as $i => $t): ?><div class="pod"><span class="no"><?= $i + 1 ?></span><div><b><?= e($t['nama']) ?></b></div><span class="n"><?= $t['n'] ?>×</span></div><?php endforeach; if (!$topM) echo '<p class="muted">Belum ada data.</p>'; ?></div></div>
  <div class="card"><div class="card-head"><h3>Reservasi terbaru</h3><a href="<?= url('admin/reservations.php') ?>">Kelola</a></div>
    <?php foreach ($resv as $r): ?><div class="review"><b><?= e($r['judul']) ?></b><br><small class="muted"><?= e($r['nama']) ?></small> <span class="badge <?= $r['status'] === 'siap' ? 'b-ok' : 'b-warn' ?>"><?= e($r['status']) ?></span></div><?php endforeach; if (!$resv) echo '<p class="muted">Tidak ada reservasi aktif.</p>'; ?></div>
</div>
<div class="grid g2">
  <div class="card"><div class="card-head"><h3>⏰ Keterlambatan</h3><a href="<?= url('admin/loans.php?f=terlambat') ?>">Lihat semua</a></div>
    <div class="tbl-wrap"><table><tr><th>Anggota</th><th>Judul</th><th>Telat</th></tr>
    <?php foreach ($late as $l): ?><tr><td><?= e($l['nama']) ?></td><td><?= e(mb_strimwidth($l['judul'], 0, 34, '…')) ?></td><td><span class="badge b-bad"><?= days_late($l['tgl_jatuh_tempo']) ?> hari</span></td></tr>
    <?php endforeach; if (!$late): ?><tr><td colspan="3" class="empty">Tidak ada keterlambatan 🎉</td></tr><?php endif; ?></table></div></div>
  <div class="card"><div class="card-head"><h3>Transaksi terakhir</h3><a href="<?= url('admin/history.php') ?>">Riwayat</a></div>
    <div class="tbl-wrap"><table><tr><th>Anggota</th><th>Judul</th><th>Status</th></tr>
    <?php foreach ($recent as $l): ?><tr><td><?= e($l['nama']) ?></td><td><?= e(mb_strimwidth($l['judul'], 0, 30, '…')) ?></td><td><span class="badge <?= $l['status'] === 'kembali' ? 'b-ok' : 'b-warn' ?>"><?= $l['status'] === 'kembali' ? 'Kembali' : 'Dipinjam' ?></span></td></tr><?php endforeach; ?></table></div></div>
</div>
<?php page_end('admin');
