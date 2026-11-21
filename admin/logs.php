<?php
require __DIR__ . '/../includes/bootstrap.php';
require_staff(['admin']);
$qq = get('q'); $p = []; $w = '1=1';
if ($qq !== '') { $w = '(pengguna LIKE ? OR aksi LIKE ?)'; $p = ["%$qq%", "%$qq%"]; }
$list = rows("SELECT * FROM activity_log WHERE $w ORDER BY id DESC LIMIT 200", $p);
page_start('Log Aktivitas', 'admin', 'logs');
?>
<div class="card"><form method="get" class="toolbar"><div class="field" style="flex:1"><label>Cari</label><input type="search" name="q" value="<?= e($qq) ?>"></div><button class="btn" type="submit">Cari</button></form>
<div class="tbl-wrap"><table><tr><th>Waktu</th><th>Pengguna</th><th>Aktivitas</th></tr>
<?php foreach ($list as $r): ?><tr><td><?= e(tgl($r['waktu'], true)) ?></td><td><?= e($r['pengguna']) ?></td><td><?= e($r['aksi']) ?></td></tr><?php endforeach; ?></table></div><p class="hint">Menampilkan 200 aktivitas terakhir.</p></div>
<?php page_end('admin');
