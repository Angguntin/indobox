<?php
require __DIR__ . '/../includes/bootstrap.php';
require_staff(['admin']); csrf_check();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = post('act'); $id = pid('id');
    try {
        if ($act === 'add') {
            $un = strtolower(post('username')); $pw = (string)(nz($_POST, 'password', ''));
            if (!preg_match('/^[a-z0-9_.]{3,30}$/', $un)) throw new Exception('Username 3-30 karakter: huruf kecil, angka, titik, underscore.');
            if (strlen($pw) < 6) throw new Exception('Password minimal 6 karakter.');
            if (post('nama') === '') throw new Exception('Nama wajib diisi.');
            db_insert('users', ['username' => $un, 'password' => hash_pw($pw), 'nama' => post('nama'), 'role' => post('role') === 'admin' ? 'admin' : 'petugas', 'aktif' => 1]);
            flash('ok', "Pengguna $un ditambahkan."); log_act("Tambah pengguna $un");
        } elseif ($id !== null) {
            if ($act === 'toggle') { if ($id === (int)me()['id']) throw new Exception('Tidak bisa menonaktifkan akun sendiri.'); q("UPDATE users SET aktif=1-aktif WHERE id=?", [$id]); flash('ok', 'Status diubah.'); }
            elseif ($act === 'reset') { $pw = (string)(nz($_POST, 'newpw', '')); if (strlen($pw) < 6) throw new Exception('Password baru minimal 6 karakter.'); db_update('users', $id, ['password' => hash_pw($pw)]); flash('ok', 'Password diganti.'); log_act("Reset password pengguna #$id"); }
            elseif ($act === 'del') { if ($id === (int)me()['id']) throw new Exception('Tidak bisa menghapus akun sendiri.'); if (val("SELECT COUNT(*) FROM users WHERE role='admin' AND aktif=1 AND id<>?", [$id]) < 1) throw new Exception('Harus ada minimal satu admin aktif.'); db_delete('users', $id); flash('ok', 'Pengguna dihapus.'); }
        }
    } catch (PDOException $ex) { flash('err', strpos($ex->getMessage(), 'Duplicate') !== false ? 'Username sudah dipakai.' : 'Database error.'); }
    catch (Exception $ex) { flash('err', $ex->getMessage()); }
    redirect('admin/users.php');
}
$list = rows("SELECT * FROM users ORDER BY id");
page_start('Pengguna / Petugas', 'admin', 'users');
?>
<div class="grid" style="grid-template-columns:1fr 1.7fr;align-items:start">
<div class="card"><h3>Tambah pengguna (username baru)</h3><form method="post"><?= csrf() ?><input type="hidden" name="act" value="add">
<div class="field"><label>Username</label><input type="text" name="username" required pattern="[a-z0-9_.]{3,30}"></div>
<div class="field"><label>Nama lengkap</label><input type="text" name="nama" required></div>
<div class="field"><label>Peran</label><select name="role"><option value="petugas">Petugas</option><option value="admin">Admin</option></select></div>
<div class="field"><label>Password</label><input type="password" name="password" required minlength="6"></div>
<button class="btn" type="submit">Tambah</button></form></div>
<div class="card"><div class="tbl-wrap"><table><tr><th>ID</th><th>Username</th><th>Nama</th><th>Peran</th><th>Status</th><th>Aksi</th></tr>
<?php foreach ($list as $u): ?><tr><td><?= $u['id'] ?></td><td><?= e($u['username']) ?></td><td><?= e($u['nama']) ?></td><td><span class="badge"><?= e($u['role']) ?></span></td><td><?= $u['aktif'] ? '<span class="badge b-ok">aktif</span>' : '<span class="badge b-gray">nonaktif</span>' ?></td>
<td><form method="post" class="inline-form"><?= csrf() ?><input type="hidden" name="id" value="<?= $u['id'] ?>"><input type="password" name="newpw" placeholder="password baru" style="width:130px;display:inline-block;padding:.3rem .5rem"> <button class="btn btn-sm btn-soft" name="act" value="reset" type="submit">Reset</button>
<button class="btn btn-sm btn-ghost" name="act" value="toggle" type="submit"><?= $u['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
<button class="btn btn-sm btn-danger" name="act" value="del" type="submit" onclick="return confirm('Hapus pengguna ini?')">Hapus</button></form></td></tr><?php endforeach; ?></table></div></div>
</div>
<?php page_end('admin');
