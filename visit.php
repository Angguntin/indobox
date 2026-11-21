<?php
require __DIR__ . '/includes/bootstrap.php';
csrf_check();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no = post('no_anggota'); $nama = post('nama'); $kep = post('keperluan', 'Membaca'); $mid = null;
    if ($no !== '') {
        $m = row("SELECT id, nama FROM members WHERE no_anggota=?", [$no]);
        if (!$m) { flash('err', 'Nomor anggota tidak ditemukan. Kosongkan jika Anda pengunjung umum.'); redirect('visit.php'); }
        $mid = (int)$m['id']; $nama = $m['nama'];
    }
    if ($nama === '') { flash('err', 'Isi nama atau nomor anggota.'); redirect('visit.php'); }
    db_insert('visits', ['member_id' => $mid, 'nama' => mb_substr($nama, 0, 100), 'keperluan' => mb_substr($kep, 0, 50)]);
    flash('ok', 'Terima kasih, kunjungan Anda tercatat. Selamat membaca!');
    redirect('visit.php');
}
$today = (int)val("SELECT COUNT(*) FROM visits WHERE DATE(waktu)=CURDATE()");
$recent = rows("SELECT nama, keperluan, waktu FROM visits ORDER BY waktu DESC, id DESC LIMIT 8");
page_start('Kunjungan', 'public', 'visit');
?>
<h1>Buku Tamu & Kunjungan</h1>
<div class="grid g2">
  <div class="card"><h3>Catat kunjungan</h3>
    <form method="post"><?= csrf() ?>
      <div class="field"><label>Nomor anggota (opsional)</label><input type="text" name="no_anggota" placeholder="AGT-0001"></div>
      <div class="field"><label>Atau nama pengunjung</label><input type="text" name="nama" maxlength="100"></div>
      <div class="field"><label>Keperluan</label><select name="keperluan"><option>Membaca</option><option>Meminjam</option><option>Mengembalikan</option><option>Belajar</option><option>Lainnya</option></select></div>
      <button class="btn" type="submit">Catat kunjungan</button>
    </form></div>
  <div class="card"><h3>Hari ini</h3><p style="font:800 2.6rem var(--f-display);color:var(--pink-600);margin:0"><?= $today ?></p><p class="muted">pengunjung tercatat</p>
    <?php foreach ($recent as $r): ?><div class="review"><b><?= e($r['nama']) ?></b> <span class="badge b-ok"><?= e($r['keperluan']) ?></span> <small class="muted"><?= e(tgl($r['waktu'], true)) ?></small></div><?php endforeach; ?></div>
</div>
<?php page_end();
