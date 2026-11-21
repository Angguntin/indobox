<?php
require __DIR__ . '/../includes/bootstrap.php';
require_member(); csrf_check();
$mid = (int)me()['id'];
$m = row("SELECT * FROM members WHERE id=?", [$mid]);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('act') === 'profil') {
        db_update('members', $mid, ['telp' => post('telp'), 'alamat' => post('alamat'), 'email' => post('email')]);
        flash('ok', 'Profil diperbarui.');
    } elseif (post('act') === 'pw') {
        $old = (string)$_POST['old']; $new = (string)$_POST['new']; $cf = (string)$_POST['cf'];
        if (!verify_pw($old, $m['password'])) flash('err', 'Password lama salah.');
        elseif (strlen($new) < 6) flash('err', 'Password baru minimal 6 karakter.');
        elseif ($new !== $cf) flash('err', 'Konfirmasi password tidak sama.');
        else { db_update('members', $mid, ['password' => hash_pw($new)]); log_act('Anggota mengganti password'); flash('ok', 'Password berhasil diganti.'); }
    }
    redirect('member/profile.php');
}
page_start('Profil', 'public'); member_nav('profile');
?>
<div class="grid g2">
<div class="card"><h3>Data diri</h3><form method="post"><?= csrf() ?><input type="hidden" name="act" value="profil">
  <div class="field"><label>Nama</label><input type="text" value="<?= e($m['nama']) ?>" disabled></div>
  <div class="field"><label>Email</label><input type="email" name="email" value="<?= e($m['email']) ?>"></div>
  <div class="field"><label>No. telepon</label><input type="tel" name="telp" value="<?= e($m['telp']) ?>"></div>
  <div class="field"><label>Alamat</label><textarea name="alamat"><?= e($m['alamat']) ?></textarea></div>
  <button class="btn" type="submit">Simpan</button></form></div>
<div class="card"><h3>Ganti password</h3><form method="post"><?= csrf() ?><input type="hidden" name="act" value="pw">
  <div class="field"><label>Password lama</label><input type="password" name="old" required></div>
  <div class="field"><label>Password baru</label><input type="password" name="new" required minlength="6"></div>
  <div class="field"><label>Ulangi password baru</label><input type="password" name="cf" required></div>
  <button class="btn" type="submit">Ganti password</button></form></div>
</div>
<?php page_end();
