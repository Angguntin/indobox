<?php
require __DIR__ . '/../includes/bootstrap.php';
require_staff(); csrf_check();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = post('act'); $id = pid('id'); $kode = strtoupper(mb_substr(post('kode'), 0, 10)); $nama = mb_substr(post('nama'), 0, 80);
    try {
        if ($act === 'save') {
            if ($kode === '' || $nama === '') throw new Exception('Kode dan nama wajib diisi.');
            if ($id !== null) { db_update('categories', $id, ['kode' => $kode, 'nama' => $nama]); flash('ok', 'Kategori diperbarui.'); }
            else { db_insert('categories', ['kode' => $kode, 'nama' => $nama]); flash('ok', 'Kategori ditambahkan.'); }
            log_act("Simpan kategori $nama");
        } elseif ($act === 'del' && $id !== null) {
            if (val("SELECT COUNT(*) FROM biblios WHERE kategori_id=?", [$id]) > 0) throw new Exception('Kategori masih dipakai koleksi, tidak bisa dihapus.');
            db_delete('categories', $id); flash('ok', 'Kategori dihapus.'); log_act("Hapus kategori #$id");
        }
    } catch (Exception $ex) { flash('err', $ex->getMessage()); }
    redirect('admin/categories.php');
}
$edit = gid('edit') !== null ? row("SELECT * FROM categories WHERE id=?", [gid('edit')]) : null;
$list = rows("SELECT c.*, COUNT(b.id) n FROM categories c LEFT JOIN biblios b ON b.kategori_id=c.id GROUP BY c.id ORDER BY c.id");
page_start('Kategori Koleksi', 'admin', 'cat');
?>
<div class="grid g2" style="grid-template-columns:1fr 2fr;align-items:start">
<div class="card"><h3><?= $edit ? 'Edit kategori' : 'Tambah kategori' ?></h3><form method="post"><?= csrf() ?><input type="hidden" name="act" value="save"><?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
  <div class="field"><label>Kode</label><input type="text" name="kode" maxlength="10" required value="<?= e(nz($edit, 'kode', '')) ?>"></div>
  <div class="field"><label>Nama kategori</label><input type="text" name="nama" maxlength="80" required value="<?= e(nz($edit, 'nama', '')) ?>"></div>
  <div class="actions"><button class="btn" type="submit">Simpan</button><?php if ($edit): ?><a class="btn btn-ghost" href="<?= url('admin/categories.php') ?>">Batal</a><?php endif; ?></div></form></div>
<div class="card"><div class="tbl-wrap"><table><tr><th>ID</th><th>Kode</th><th>Nama</th><th>Koleksi</th><th></th></tr>
<?php foreach ($list as $c): ?><tr><td><?= $c['id'] ?></td><td><?= e($c['kode']) ?></td><td><?= e($c['nama']) ?></td><td><?= $c['n'] ?></td><td class="right">
<a class="btn btn-sm btn-soft" href="?edit=<?= $c['id'] ?>">Edit</a>
<form method="post" class="inline-form" data-confirm="Hapus kategori ini?"><?= csrf() ?><input type="hidden" name="act" value="del"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="btn btn-sm btn-danger" type="submit">Hapus</button></form></td></tr><?php endforeach; ?></table></div></div>
</div>
<?php page_end('admin');
