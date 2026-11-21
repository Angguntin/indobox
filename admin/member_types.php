<?php
require __DIR__ . '/../includes/bootstrap.php';
require_staff(); csrf_check();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = post('act'); $id = pid('id');
    try {
        if ($act === 'save') {
            $d = ['nama' => post('nama'), 'maks_pinjam' => max(1, (int)post('maks_pinjam', 3)), 'lama_pinjam' => max(1, (int)post('lama_pinjam', 7)), 'denda_per_hari' => max(0, (int)post('denda_per_hari', 500)), 'maks_perpanjang' => max(0, (int)post('maks_perpanjang', 1)), 'masa_berlaku_bulan' => max(1, (int)post('masa_berlaku_bulan', 12))];
            if ($d['nama'] === '') throw new Exception('Nama tipe wajib diisi.');
            if ($id !== null) { db_update('member_types', $id, $d); flash('ok', 'Tipe anggota diperbarui.'); } else { db_insert('member_types', $d); flash('ok', 'Tipe anggota ditambahkan.'); }
            log_act('Simpan tipe anggota ' . $d['nama']);
        } elseif ($act === 'del' && $id !== null) {
            if (val("SELECT COUNT(*) FROM members WHERE type_id=?", [$id]) > 0) throw new Exception('Tipe masih dipakai anggota.');
            db_delete('member_types', $id); flash('ok', 'Tipe dihapus.');
        }
    } catch (Exception $ex) { flash('err', $ex->getMessage()); }
    redirect('admin/member_types.php');
}
$edit = gid('edit') !== null ? row("SELECT * FROM member_types WHERE id=?", [gid('edit')]) : null;
$list = rows("SELECT t.*, COUNT(m.id) n FROM member_types t LEFT JOIN members m ON m.type_id=t.id GROUP BY t.id ORDER BY t.id");
page_start('Tipe Anggota & Aturan Pinjam', 'admin', 'mtype');
?>
<div class="grid" style="grid-template-columns:1fr 2fr;align-items:start">
<div class="card"><h3><?= $edit ? 'Edit tipe' : 'Tambah tipe' ?></h3><form method="post"><?= csrf() ?><input type="hidden" name="act" value="save"><?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
<div class="field"><label>Nama</label><input type="text" name="nama" required value="<?= e(nz($edit, 'nama', '')) ?>"></div>
<div class="field"><label>Maks. buku dipinjam</label><input type="number" name="maks_pinjam" min="1" value="<?= e(nz($edit, 'maks_pinjam', 3)) ?>"></div>
<div class="field"><label>Lama pinjam (hari)</label><input type="number" name="lama_pinjam" min="1" value="<?= e(nz($edit, 'lama_pinjam', 7)) ?>"></div>
<div class="field"><label>Denda per hari (Rp)</label><input type="number" name="denda_per_hari" min="0" step="100" value="<?= e(nz($edit, 'denda_per_hari', 500)) ?>"></div>
<div class="field"><label>Maks. perpanjangan</label><input type="number" name="maks_perpanjang" min="0" value="<?= e(nz($edit, 'maks_perpanjang', 1)) ?>"></div>
<div class="field"><label>Masa berlaku (bulan)</label><input type="number" name="masa_berlaku_bulan" min="1" value="<?= e(nz($edit, 'masa_berlaku_bulan', 12)) ?>"></div>
<div class="actions"><button class="btn" type="submit">Simpan</button><?php if ($edit): ?><a class="btn btn-ghost" href="<?= url('admin/member_types.php') ?>">Batal</a><?php endif; ?></div></form></div>
<div class="card"><div class="tbl-wrap"><table><tr><th>ID</th><th>Tipe</th><th>Maks</th><th>Hari</th><th>Denda/hari</th><th>Perpanjang</th><th>Berlaku</th><th>Anggota</th><th></th></tr>
<?php foreach ($list as $t): ?><tr><td><?= $t['id'] ?></td><td><b><?= e($t['nama']) ?></b></td><td><?= $t['maks_pinjam'] ?></td><td><?= $t['lama_pinjam'] ?></td><td><?= rupiah($t['denda_per_hari']) ?></td><td><?= $t['maks_perpanjang'] ?>×</td><td><?= $t['masa_berlaku_bulan'] ?> bln</td><td><?= $t['n'] ?></td>
<td class="right"><a class="btn btn-sm btn-soft" href="?edit=<?= $t['id'] ?>">Edit</a>
<form method="post" class="inline-form" data-confirm="Hapus tipe ini?"><?= csrf() ?><input type="hidden" name="act" value="del"><input type="hidden" name="id" value="<?= $t['id'] ?>"><button class="btn btn-sm btn-danger" type="submit">Hapus</button></form></td></tr><?php endforeach; ?></table></div></div>
</div>
<?php page_end('admin');
