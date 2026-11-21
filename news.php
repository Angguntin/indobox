<?php
require __DIR__ . '/includes/bootstrap.php';
$list = rows("SELECT * FROM announcements WHERE aktif=1 ORDER BY tgl DESC, id DESC");
page_start('Pengumuman', 'public', 'news');
echo '<h1>Pengumuman</h1>';
foreach ($list as $n) echo '<div class="card news-item"><small class="muted">' . e(tgl($n['tgl'])) . '</small><h3>' . e($n['judul']) . '</h3><p>' . nl2br(e($n['isi'])) . '</p></div>';
if (!$list) echo '<div class="card empty">Belum ada pengumuman.</div>';
page_end();
