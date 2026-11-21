<?php
require __DIR__ . '/includes/bootstrap.php';
if (is_staff()) redirect('admin/index.php');
if (is_member()) redirect('member/index.php');
csrf_check();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = post('identitas'); $pw = (string)(nz($_POST, 'password', ''));
    $u = row("SELECT * FROM users WHERE username=?", [$id]);
    if ($u && $u['aktif'] && verify_pw($pw, $u['password'])) {
        if (strpos($u['password'], 'plain:') === 0) db_update('users', $u['id'], ['password' => hash_pw($pw)]);
        session_regenerate_id(true);
        $_SESSION['u'] = ['kind' => 'staff', 'id' => (int)$u['id'], 'nama' => $u['nama'], 'role' => $u['role'], 'username' => $u['username']];
        log_act('Login petugas');
        redirect('admin/index.php');
    }
    $m = row("SELECT * FROM members WHERE no_anggota=?", [$id]);
    if ($m && verify_pw($pw, $m['password'])) {
        if ($m['status'] !== 'aktif') { flash('err', 'Akun anggota Anda berstatus ' . $m['status'] . '. Hubungi petugas perpustakaan.'); redirect('login.php'); }
        if (strpos($m['password'], 'plain:') === 0) db_update('members', $m['id'], ['password' => hash_pw($pw)]);
        session_regenerate_id(true);
        $_SESSION['u'] = ['kind' => 'member', 'id' => (int)$m['id'], 'nama' => $m['nama'], 'role' => 'anggota', 'username' => $m['no_anggota']];
        log_act('Login anggota');
        redirect('member/index.php');
    }
    flash('err', 'Username/No. anggota atau password salah.');
    redirect('login.php');
}
page_start('Masuk', 'public');
?>
<div class="login-box"><div class="card">
  <h1>Masuk</h1>
  <p class="muted">Petugas memakai <b>username</b>, anggota memakai <b>nomor anggota</b> (contoh: AGT-0001).</p>
  <form method="post"><?= csrf() ?>
    <div class="field"><label>Username / No. anggota</label><input type="text" name="identitas" required data-autofocus autocomplete="username"></div>
    <div class="field"><label>Password</label><input type="password" name="password" required autocomplete="current-password"></div>
    <button class="btn" type="submit" style="width:100%;justify-content:center">Masuk</button>
  </form>
  <p class="hint" style="margin-top:1rem">Demo: admin / admin123 · petugas / petugas123 · AGT-0001 / anggota123</p>
</div></div>
<?php page_end();
