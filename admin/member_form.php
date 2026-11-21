<?php
require __DIR__ . '/../includes/bootstrap.php';
require_staff(); csrf_check();
$id = gid();
$m = $id !== null ? row("SELECT * FROM members WHERE id=?", [$id]) : null;
if ($id !== null && !$m) { flash('err', 'Anggota tidak ditemukan.'); redirect('admin/members.php'); }
$types = rows("SELECT * FROM member_types ORDER BY id");
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nama = post('nama'); $tid = pid('type_id');
        if ($nama === '' || $tid === null) throw new Exception('Nama dan tipe anggota wajib diisi.');
        $t = row("SELECT * FROM member_types WHERE id=?", [$tid]); if (!$t) throw new Exception('Tipe anggota tidak valid.');
        $tglLahir = post('tgl_lahir') ?: null;
        if (post('email') !== '' && !filter_var(post('email'), FILTER_VALIDATE_EMAIL)) throw new Exception('Format email tidak valid.');
        $data = ['no_anggota' => strtoupper(post('no_anggota')) ?: next_member_no(), 'nama' => $nama, 'jk' => post('jk') === 'P' ? 'P' : 'L', 'tgl_lahir' => $tglLahir,
            'alamat' => post('alamat'), 'telp' => post('telp'), 'email' => post('email'), 'type_id' => $tid, 'status' => in_array(post('status'), ['aktif', 'nonaktif', 'blokir']) ? post('status') : 'aktif'];
        if ($m) {
            $data['tgl_expire'] = post('tgl_expire') ?: $m['tgl_expire'];
            if (post('password') !== '') { if (strlen(post('password')) < 6) throw new Exception('Password minimal 6 karakter.'); $data['password'] = hash_pw(post('password')); }
            db_update('members', $id, $data); log_act("Ubah anggota: $nama"); flash('ok', 'Data anggota diperbarui.');
        } else {
            $data['tgl_daftar'] = date('Y-m-d'); $data['tgl_expire'] = post('tgl_expire') ?: date('Y-m-d', strtotime('+' . (int)$t['masa_berlaku_bulan'] . ' months'));
            $data['password'] = hash_pw(post('password') !== '' ? post('password') : 'anggota123');
            db_insert('members', $data); log_act("Tambah anggota: $nama"); flash('ok', 'Anggota ditambahkan. Password awal: ' . (post('password') !== '' ? '(sesuai input)' : 'anggota123'));
        }
        redirect('admin/members.php');
    } catch (PDOException $ex) {
        flash('err', strpos($ex->getMessage(), 'Duplicate') !== false ? 'Nomor anggota sudah dipakai.' : 'Database error: ' . $ex->getMessage()); $m = array_merge($m ?: [], $_POST);
    } catch (Exception $ex) { flash('err', $ex->getMessage()); $m = array_merge($m ?: [], $_POST); }
}
$v = function ($k, $d = '') use (&$m) { return e(isset($m[$k]) ? $m[$k] : $d); };
page_start($id !== null ? 'Edit Anggota' : 'Tambah Anggota', 'admin', $id !== null ? 'members' : 'member_add');
?>
<form method="post" class="card"><?= csrf() ?>
<div class="grid g3">
  <div class="field"><label>No. anggota</label><input type="text" name="no_anggota" value="<?= $v('no_anggota') ?>" placeholder="Kosongkan = otomatis"></div>
  <div class="field span2"><label>Nama lengkap *</label><input type="text" name="nama" required value="<?= $v('nama') ?>"></div>
  <div class="field"><label>Jenis kelamin</label><select name="jk"><option value="L" <?= (nz($m, 'jk', '')) === 'L' ? 'selected' : '' ?>>Laki-laki</option><option value="P" <?= (nz($m, 'jk', '')) === 'P' ? 'selected' : '' ?>>Perempuan</option></select></div>
  <div class="field"><label>Tanggal lahir</label><input type="date" name="tgl_lahir" value="<?= $v('tgl_lahir') ?>"></div>
  <div class="field"><label>Tipe anggota *</label><select name="type_id" required><?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>" <?= isset($m['type_id']) && $m['type_id'] !== '' && (int)$m['type_id'] === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['nama']) ?> (maks <?= $t['maks_pinjam'] ?> buku, <?= $t['lama_pinjam'] ?> hari)</option><?php endforeach; ?></select></div>
  <div class="field"><label>Email</label><input type="email" name="email" value="<?= $v('email') ?>"></div>
  <div class="field"><label>No. telepon</label><input type="tel" name="telp" value="<?= $v('telp') ?>"></div>
  <div class="field"><label>Status</label><select name="status"><?php foreach (['aktif', 'nonaktif', 'blokir'] as $s): ?><option <?= (nz($m, 'status', 'aktif')) === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
  <div class="field"><label>Berlaku sampai</label><input type="date" name="tgl_expire" value="<?= $v('tgl_expire') ?>"><div class="hint">Kosongkan saat tambah = otomatis sesuai tipe.</div></div>
  <div class="field"><label>Password portal anggota</label><input type="text" name="password" minlength="6" placeholder="<?= $id !== null ? 'Kosongkan jika tidak diubah' : 'Default: anggota123' ?>"></div>
  <div class="field span2" style="grid-column:1/-1"><label>Alamat</label><textarea name="alamat" style="min-height:70px"><?= $v('alamat') ?></textarea></div>
</div>
<div class="actions"><button class="btn" type="submit">💾 Simpan</button><a class="btn btn-ghost" href="<?= url('admin/members.php') ?>">Batal</a>
<?php if ($id !== null): ?><button class="btn btn-soft" type="submit" form="reset-pw">Reset password</button><?php endif; ?></div>
</form>
<?php if ($id !== null): ?><form id="reset-pw" method="post" action="<?= url('admin/members.php') ?>" data-confirm="Reset password anggota ini ke anggota123?"><?= csrf() ?><input type="hidden" name="act" value="resetpw"><input type="hidden" name="id" value="<?= $id ?>"></form><?php endif;
page_end('admin');
