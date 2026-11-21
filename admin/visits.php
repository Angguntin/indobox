<?php
require __DIR__ . '/../includes/bootstrap.php';
require_staff(); csrf_check();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'del' && me()['role'] === 'admin') { $id = pid('id'); if ($id !== null) { db_delete('visits', $id); flash('ok', 'Catatan dihapus.'); } redirect('admin/visits.php'); }
$d14 = []; $v14 = [];
for ($i = 13; $i >= 0; $i--) { $d = date('Y-m-d', strtotime("-$i day")); $d14[] = date('d/m', strtotime($d)); $v14[] = (int)val("SELECT COUNT(*) FROM visits WHERE DATE(waktu)=?", [$d]); }
$kep = rows("SELECT keperluan, COUNT(*) n FROM visits WHERE DATE_FORMAT(waktu,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m') GROUP BY keperluan ORDER BY n DESC");
$list = rows("SELECT * FROM visits ORDER BY waktu DESC, id DESC LIMIT 60");
$today = (int)val("SELECT COUNT(*) FROM visits WHERE DATE(waktu)=CURDATE()"); $bln = (int)val("SELECT COUNT(*) FROM visits WHERE DATE_FORMAT(waktu,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')");
page_start('Kunjungan Pengunjung', 'admin', 'visits');
?>
<div class="kpis"><div class="kpi"><div class="ic">🚪</div><div><b><?= $today ?></b><span>Hari ini</span></div></div><div class="kpi m"><div class="ic">📅</div><div><b><?= $bln ?></b><span>Bulan ini</span></div></div></div>
<div class="grid g2"><div class="card"><h3>14 hari terakhir</h3><?= bars_chart($d14, $v14) ?></div><div class="card"><h3>Keperluan (bulan ini)</h3><?= $kep ? hbars(array_column($kep, 'keperluan'), array_map('intval', array_column($kep, 'n'))) : '<p class="muted">Belum ada data.</p>' ?>
<p class="hint">Pengunjung mencatat diri di halaman <a href="<?= url('visit.php') ?>" target="_blank">Kunjungan</a> (cocok untuk kiosk di meja depan).</p></div></div>
<div class="card"><h3>Catatan terbaru</h3><div class="tbl-wrap"><table><tr><th>Waktu</th><th>Nama</th><th>Keperluan</th><th>Anggota</th><th></th></tr>
<?php foreach ($list as $r): ?><tr><td><?= e(tgl($r['waktu'], true)) ?></td><td><?= e($r['nama']) ?></td><td><?= e($r['keperluan']) ?></td><td><?= $r['member_id'] !== null ? '<span class="badge b-ok">Anggota</span>' : '<span class="badge b-gray">Umum</span>' ?></td>
<td class="right"><?php if (me()['role'] === 'admin'): ?><form method="post" class="inline-form" data-confirm="Hapus catatan ini?"><?= csrf() ?><input type="hidden" name="act" value="del"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-danger" type="submit">Hapus</button></form><?php endif; ?></td></tr><?php endforeach; ?></table></div></div>
<?php page_end('admin');
