<?php
require __DIR__ . '/../includes/bootstrap.php';
require_member();
$m = member_info((int)me()['id']);
page_start('Kartu Anggota', 'public'); member_nav('card');
?>
<div class="card"><div class="card-head"><h3>Kartu anggota digital</h3><button class="btn btn-sm no-print" data-print>🖨️ Cetak kartu</button></div>
<div class="cards-print"><?= member_card_html($m) ?></div><p class="hint">Tunjukkan barcode ini kepada petugas saat meminjam buku.</p></div>
<?php page_end();
