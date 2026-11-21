<?php
require __DIR__ . '/../includes/bootstrap.php';
require_staff(); csrf_check();
$id = (int)me()['id'];
$u = row("SELECT * FROM users WHERE id=?", [$id]);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (post('act') === 'pw') {
            $new = (string)$_POST['new']; 
            if (!verify_pw((string)$_POST['old'], $u['password'])) throw new Exception('Password lama salah.');
            if (strlen($new) < 6) throw new Exception('Password baru minimal 6 karakter.');
            if ($new !== (string)$_POST['cf']) throw new Exception('Konfirmasi password tidak sama.');
            db_update('users', $id, ['password' => hash_pw($new)]); log_act('Ganti password'); flash('ok', 'Password berhasil diganti.');
        } else {
            $un = strtolower(post('username'));
            if (!preg_match('/^[a-z0-9_.]{3,30}$/', $un)) throw new Exception('Username 3-30 karakter: huruf kecil, angka, titik, underscore.');
            if (post('nama') === '') throw new Exception('Nama wajib diisi.');
            db_update('users', $id, ['username' => $un, 'nama' => post('nama')]);
            $_SESSION['u']['nama'] = post('nama'); $_SESSION['u']['username'] = $un; flash('ok', 'Profil diperbarui.');
        }
    } catch (PDOException $ex) { flash('err', 'Username sudah dipakai.'); }
    catch (Exception $ex) { flash('err', $ex->getMessage()); }
    redirect('admin/profile.php');
}
page_start('Akun & Ganti Password', 'admin', 'profile');
?>
<div class="grid g2">
<div class="card"><h3>Profil & username</h3><form method="post"><?= csrf() ?><input type="hidden" name="act" value="profil">
<div class="field"><label>Username</label><input type="text" name="username" required value="<?= e($u['username']) ?>"></div>
<div class="field"><label>Nama lengkap</label><input type="text" name="nama" required value="<?= e($u['nama']) ?>"></div>
<p class="muted">Peran: <b><?= e($u['role']) ?></b></p><button class="btn" type="submit">Simpan</button></form></div>
<div class="card"><h3>Ganti password</h3><form method="post"><?= csrf() ?><input type="hidden" name="act" value="pw">
<div class="field"><label>Password lama</label><input type="password" name="old" required></div>
<div class="field"><label>Password baru</label><input type="password" name="new" required minlength="6"></div>
<div class="field"><label>Ulangi password baru</label><input type="password" name="cf" required></div>
<button class="btn" type="submit">Ganti password</button></form></div>
</div>
<?php page_end('admin');
