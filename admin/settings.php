<?php
require __DIR__ . '/../includes/bootstrap.php';
require_staff(['admin']); csrf_check();
$keys = ['nama_perpustakaan' => 'Nama perpustakaan', 'tagline' => 'Tagline', 'alamat' => 'Alamat', 'telepon' => 'Telepon', 'email' => 'Email', 'jam_buka' => 'Jam layanan (boleh beberapa baris)', 'hari_siap_ambil' => 'Lama reservasi ditahan (hari)', 'maks_reservasi' => 'Maks. reservasi aktif per anggota'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($keys as $k => $l) { $v = post($k); if (in_array($k, ['hari_siap_ambil', 'maks_reservasi'])) $v = (string)max(1, (int)$v); if ($k === 'nama_perpustakaan' && $v === '') $v = 'INDO BOX'; set_setting($k, $v); }
    log_act('Ubah pengaturan'); flash('ok', 'Pengaturan disimpan.'); redirect('admin/settings.php');
}
page_start('Pengaturan Perpustakaan', 'admin', 'settings');
?>
<form method="post" class="card"><?= csrf() ?><div class="grid g2">
<?php foreach ($keys as $k => $l): ?><div class="field <?= $k === 'jam_buka' ? 'span2' : '' ?>"><label><?= e($l) ?></label>
<?php if ($k === 'jam_buka'): ?><textarea name="<?= $k ?>" style="min-height:80px"><?= e(setting($k)) ?></textarea><?php else: ?><input type="<?= in_array($k, ['hari_siap_ambil', 'maks_reservasi']) ? 'number' : 'text' ?>" name="<?= $k ?>" value="<?= e(setting($k)) ?>"><?php endif; ?></div><?php endforeach; ?></div>
<p class="hint">Aturan denda, lama pinjam, dan batas pinjam diatur per tipe anggota di menu <a href="<?= url('admin/member_types.php') ?>">Tipe Anggota</a>.</p>
<button class="btn" type="submit">💾 Simpan pengaturan</button></form>
<?php page_end('admin');
