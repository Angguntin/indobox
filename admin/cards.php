<?php
require __DIR__ . '/../includes/bootstrap.php';
require_staff(); csrf_check();
$ids = [];
if (!empty($_POST['ids']) && is_array($_POST['ids'])) foreach ($_POST['ids'] as $i) if (ctype_digit((string)$i)) $ids[] = (int)$i;
if (gid('id') !== null) $ids = [gid('id')];
$sql = "SELECT m.*, t.nama tipe FROM members m JOIN member_types t ON t.id=m.type_id";
$list = $ids ? rows("$sql WHERE m.id IN (" . implode(',', array_fill(0, count($ids), '?')) . ") ORDER BY m.id", $ids) : rows("$sql ORDER BY m.id LIMIT 60");
page_start('Cetak Kartu Anggota', 'admin', 'members');
?>
<div class="no-print actions" style="margin-bottom:1rem"><button class="btn" data-print>🖨️ Cetak / Simpan PDF</button><a class="btn btn-ghost" href="<?= url('admin/members.php') ?>">Kembali</a></div>
<div class="cards-print"><?php foreach ($list as $m) echo member_card_html($m); ?></div>
<?php page_end('admin');
