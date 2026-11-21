<?php
require __DIR__ . '/../includes/bootstrap.php';
require_staff(); csrf_check();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = post('act'); $id = pid('id');
    if ($act === 'del' && $id !== null) {
        $m = row("SELECT nama FROM members WHERE id=?", [$id]);
        try { db_delete('members', $id); log_act('Hapus anggota: ' . ($m ? $m['nama'] : "#$id")); flash('ok', 'Anggota dihapus.'); }
        catch (PDOException $ex) { flash('err', 'Anggota tidak bisa dihapus karena punya riwayat peminjaman. Ubah statusnya menjadi nonaktif.'); }
    } elseif ($act === 'status' && $id !== null && in_array(post('status'), ['aktif', 'nonaktif', 'blokir'])) {
        db_update('members', $id, ['status' => post('status')]); flash('ok', 'Status anggota diubah.');
    } elseif ($act === 'extend' && $id !== null) {
        $m = member_info($id);
        if ($m) { $base = strtotime($m['tgl_expire']) > time() ? $m['tgl_expire'] : date('Y-m-d'); $new = date('Y-m-d', strtotime($base . ' +' . (int)$m['masa_berlaku_bulan'] . ' months')); db_update('members', $id, ['tgl_expire' => $new]); flash('ok', 'Keanggotaan diperpanjang sampai ' . tgl($new) . '.'); }
    } elseif ($act === 'resetpw' && $id !== null) {
        db_update('members', $id, ['password' => hash_pw('anggota123')]); flash('ok', 'Password direset menjadi: anggota123');
    }
    redirect('admin/members.php');
}
$qq = get('q'); $st = get('status'); $tp = get('tipe'); $page = max(1, (int)get('page', 1)); $per = 15;
$where = ['1=1']; $p = [];
if ($qq !== '') { $where[] = '(m.nama LIKE ? OR m.no_anggota LIKE ? OR m.email LIKE ? OR m.telp LIKE ?)'; for ($i = 0; $i < 4; $i++) $p[] = "%$qq%"; }
if (in_array($st, ['aktif', 'nonaktif', 'blokir'])) { $where[] = 'm.status=?'; $p[] = $st; }
if ($tp !== '' && ctype_digit($tp)) { $where[] = 'm.type_id=?'; $p[] = (int)$tp; }
$W = implode(' AND ', $where);
if (get('export') === '1') {
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="anggota-' . date('Ymd') . '.csv"');
    $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF"); fputcsv($o, ['id', 'no_anggota', 'nama', 'jk', 'tipe', 'telp', 'email', 'alamat', 'tgl_daftar', 'tgl_expire', 'status']);
    foreach (rows("SELECT m.*, t.nama tipe FROM members m JOIN member_types t ON t.id=m.type_id WHERE $W ORDER BY m.id", $p) as $r) fputcsv($o, [$r['id'], $r['no_anggota'], $r['nama'], $r['jk'], $r['tipe'], $r['telp'], $r['email'], $r['alamat'], $r['tgl_daftar'], $r['tgl_expire'], $r['status']]);
    exit;
}
$total = (int)val("SELECT COUNT(*) FROM members m WHERE $W", $p); $off = ($page - 1) * $per;
$list = rows("SELECT m.*, t.nama tipe, (SELECT COUNT(*) FROM loans l WHERE l.member_id=m.id AND l.status='dipinjam') aktif FROM members m JOIN member_types t ON t.id=m.type_id WHERE $W ORDER BY m.id DESC LIMIT $per OFFSET $off", $p);
$types = rows("SELECT * FROM member_types ORDER BY id");
page_start('Daftar Anggota', 'admin', 'members');
?>
<div class="card">
<form method="get" class="toolbar">
  <div class="field" style="flex:1;min-width:200px"><label>Cari</label><input type="search" name="q" value="<?= e($qq) ?>" placeholder="Nama, no. anggota, email, telp…"></div>
  <div class="field"><label>Tipe</label><select name="tipe"><option value="">Semua</option><?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>" <?= $tp !== '' && (int)$tp === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['nama']) ?></option><?php endforeach; ?></select></div>
  <div class="field"><label>Status</label><select name="status"><option value="">Semua</option><?php foreach (['aktif', 'nonaktif', 'blokir'] as $s): ?><option <?= $st === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
  <button class="btn" type="submit">Filter</button><a class="btn btn-mint" href="<?= url('admin/member_form.php') ?>">➕ Tambah</a><a class="btn btn-ghost" href="?<?= e(qs(['export' => 1])) ?>">⬇ Ekspor CSV</a>
</form>
<form method="post" action="<?= url('admin/cards.php') ?>" target="_blank"><?= csrf() ?>
<div class="tbl-wrap"><table><tr><th><input type="checkbox" data-check-all aria-label="Pilih semua"></th><th>ID</th><th>No. anggota</th><th>Nama</th><th>Tipe</th><th>Berlaku s/d</th><th>Pinjam</th><th>Status</th><th></th></tr>
<?php foreach ($list as $m): $exp = strtotime($m['tgl_expire']) < strtotime(date('Y-m-d')); ?>
<tr><td><input type="checkbox" name="ids[]" value="<?= $m['id'] ?>"></td><td><?= $m['id'] ?></td><td><?= e($m['no_anggota']) ?></td>
<td><b><?= e($m['nama']) ?></b><br><small class="muted"><?= e($m['telp']) ?> · <?= e($m['email']) ?></small></td><td><?= e($m['tipe']) ?></td>
<td><?= e(tgl($m['tgl_expire'])) ?> <?= $exp ? '<span class="badge b-bad">kedaluwarsa</span>' : '' ?></td><td><?= (int)$m['aktif'] ?></td>
<td><span class="badge <?= $m['status'] === 'aktif' ? 'b-ok' : ($m['status'] === 'blokir' ? 'b-bad' : 'b-gray') ?>"><?= e($m['status']) ?></span></td>
<td class="right"><a class="btn btn-sm btn-ghost" href="<?= url('admin/history.php?member=' . urlencode($m['no_anggota'])) ?>">Riwayat</a>
<a class="btn btn-sm btn-soft" href="<?= url('admin/member_form.php?id=' . $m['id']) ?>">Edit</a>
<button class="btn btn-sm btn-mint" type="submit" form="ext-<?= $m['id'] ?>" title="Perpanjang masa berlaku">⟳</button>
<button class="btn btn-sm btn-danger" type="submit" form="del-<?= $m['id'] ?>">Hapus</button></td></tr>
<?php endforeach; if (!$list): ?><tr><td colspan="9" class="empty">Belum ada anggota.</td></tr><?php endif; ?></table></div>
<div class="actions" style="margin-top:.8rem"><button class="btn btn-sm btn-soft" type="submit">🪪 Cetak kartu terpilih</button><span class="hint">Tanpa centang = semua anggota pada halaman filter.</span></div>
</form>
<?php foreach ($list as $m): ?>
<form id="del-<?= $m['id'] ?>" method="post" data-confirm="Hapus anggota ini?"><?= csrf() ?><input type="hidden" name="act" value="del"><input type="hidden" name="id" value="<?= $m['id'] ?>"></form>
<form id="ext-<?= $m['id'] ?>" method="post"><?= csrf() ?><input type="hidden" name="act" value="extend"><input type="hidden" name="id" value="<?= $m['id'] ?>"></form>
<?php endforeach; ?>
<?= pager($total, $per, $page) ?></div>
<?php page_end('admin');
