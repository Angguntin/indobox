<?php
require __DIR__ . '/../includes/bootstrap.php';
require_staff(); csrf_check();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = post('act'); $id = pid('id');
    if ($act === 'save') {
        if (post('judul') === '' || post('isi') === '') flash('err', 'Judul dan isi wajib diisi.');
        else {
            $d = ['judul' => post('judul'), 'isi' => post('isi'), 'tgl' => post('tgl') ?: date('Y-m-d'), 'aktif' => post('aktif') === '1' ? 1 : 0];
            if ($id !== null) db_update('announcements', $id, $d); else db_insert('announcements', $d);
            flash('ok', 'Pengumuman disimpan.'); log_act('Simpan pengumuman: ' . $d['judul']);
        }
    } elseif ($act === 'del' && $id !== null) { db_delete('announcements', $id); flash('ok', 'Pengumuman dihapus.'); }
    redirect('admin/news.php');
}
$edit = gid('edit') !== null ? row("SELECT * FROM announcements WHERE id=?", [gid('edit')]) : null;
$list = rows("SELECT * FROM announcements ORDER BY tgl DESC, id DESC");
page_start('Pengumuman', 'admin', 'news');
?>
<div class="grid" style="grid-template-columns:1fr 1.4fr;align-items:start">
<div class="card"><h3><?= $edit ? 'Edit pengumuman' : 'Tulis pengumuman' ?></h3><form method="post"><?= csrf() ?><input type="hidden" name="act" value="save"><?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
<div class="field"><label>Judul</label><input type="text" name="judul" required value="<?= e(nz($edit, 'judul', '')) ?>"></div>
<div class="field"><label>Isi</label><textarea name="isi" required><?= e(nz($edit, 'isi', '')) ?></textarea></div>
<div class="field"><label>Tanggal</label><input type="date" name="tgl" value="<?= e(nz($edit, 'tgl', date('Y-m-d'))) ?>"></div>
<div class="field"><label><input type="checkbox" name="aktif" value="1" <?= !$edit || $edit['aktif'] ? 'checked' : '' ?>> Tampilkan di OPAC</label></div>
<div class="actions"><button class="btn" type="submit">Simpan</button><?php if ($edit): ?><a class="btn btn-ghost" href="<?= url('admin/news.php') ?>">Batal</a><?php endif; ?></div></form></div>
<div class="card"><div class="tbl-wrap"><table><tr><th>Tanggal</th><th>Judul</th><th>Tampil</th><th></th></tr>
<?php foreach ($list as $n): ?><tr><td><?= e(tgl($n['tgl'])) ?></td><td><?= e($n['judul']) ?></td><td><?= $n['aktif'] ? '<span class="badge b-ok">Ya</span>' : '<span class="badge b-gray">Tidak</span>' ?></td>
<td class="right"><a class="btn btn-sm btn-soft" href="?edit=<?= $n['id'] ?>">Edit</a> <form method="post" class="inline-form" data-confirm="Hapus pengumuman?"><?= csrf() ?><input type="hidden" name="act" value="del"><input type="hidden" name="id" value="<?= $n['id'] ?>"><button class="btn btn-sm btn-danger" type="submit">Hapus</button></form></td></tr><?php endforeach; ?></table></div></div>
</div>
<?php page_end('admin');
