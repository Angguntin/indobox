<?php
require __DIR__ . '/../includes/bootstrap.php';
require_staff(); csrf_check();
$id = gid();
$b = $id !== null ? row("SELECT * FROM biblios WHERE id=?", [$id]) : null;
if ($id !== null && !$b) { flash('err', 'Koleksi tidak ditemukan.'); redirect('admin/biblio.php'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $judul = post('judul'); $peng = post('pengarang'); $kat = pid('kategori_id'); $stok = (int)post('stok_total', 1);
        if ($judul === '' || $peng === '' || $kat === null) throw new Exception('Judul, pengarang, dan kategori wajib diisi.');
        if ($stok < 0) throw new Exception('Stok tidak boleh minus.');
        $barcode = strtoupper(post('kode_barcode')) ?: next_barcode();
        $data = ['kode_barcode' => $barcode, 'isbn' => post('isbn') ?: null, 'judul' => $judul, 'pengarang' => $peng, 'penerbit' => post('penerbit'),
            'tahun' => (int)post('tahun') ?: null, 'edisi' => post('edisi') ?: null, 'kategori_id' => $kat, 'bahasa' => post('bahasa', 'Indonesia'),
            'halaman' => (int)post('halaman') ?: null, 'lokasi_rak' => post('lokasi_rak'), 'sinopsis' => post('sinopsis'), 'stok_total' => $stok];
        $pdf = save_upload('file_pdf', UPLOAD_PDF, ['pdf'], 60);
        $cov = save_upload('cover', UPLOAD_COVER, ['jpg', 'jpeg', 'png', 'webp'], 5);
        if ($b) {
            $aktif = (int)val("SELECT COUNT(*) FROM loans WHERE biblio_id=? AND status='dipinjam'", [$id]);
            if ($stok < $aktif) throw new Exception("Stok tidak boleh kurang dari jumlah yang sedang dipinjam ($aktif).");
            $data['stok_tersedia'] = $stok - $aktif;
            if ($pdf) { if ($b['file_pdf'] && is_file(UPLOAD_PDF . basename($b['file_pdf']))) @unlink(UPLOAD_PDF . basename($b['file_pdf'])); $data['file_pdf'] = $pdf; }
            elseif (post('hapus_pdf') === '1') { if ($b['file_pdf'] && is_file(UPLOAD_PDF . basename($b['file_pdf']))) @unlink(UPLOAD_PDF . basename($b['file_pdf'])); $data['file_pdf'] = null; }
            if ($cov) { if ($b['cover'] && is_file(UPLOAD_COVER . basename($b['cover']))) @unlink(UPLOAD_COVER . basename($b['cover'])); $data['cover'] = $cov; }
            db_update('biblios', $id, $data);
            promote_queue($id);
            log_act("Ubah koleksi: $judul"); flash('ok', 'Koleksi diperbarui.');
        } else {
            $data['stok_tersedia'] = $stok; $data['tgl_masuk'] = date('Y-m-d'); $data['file_pdf'] = $pdf; $data['cover'] = $cov;
            db_insert('biblios', $data);
            log_act("Tambah koleksi: $judul"); flash('ok', 'Koleksi ditambahkan.');
        }
        redirect('admin/biblio.php');
    } catch (PDOException $ex) {
        flash('err', strpos($ex->getMessage(), 'Duplicate') !== false ? 'Kode barcode sudah dipakai koleksi lain.' : 'Database error: ' . $ex->getMessage());
        $b = array_merge($b ?: [], $_POST);
    } catch (Exception $ex) { flash('err', $ex->getMessage()); $b = array_merge($b ?: [], $_POST); }
}
$cats = rows("SELECT * FROM categories ORDER BY nama");
$v = function ($k, $d = '') use (&$b) { return e(isset($b[$k]) ? $b[$k] : $d); };
page_start($id !== null ? 'Edit Koleksi' : 'Tambah Koleksi', 'admin', $id !== null ? 'biblio' : 'biblio_add');
?>
<form method="post" enctype="multipart/form-data" class="card"><?= csrf() ?>
<div class="grid g3">
  <div class="field"><label>Kode barcode</label><input type="text" name="kode_barcode" value="<?= $v('kode_barcode') ?>" placeholder="Kosongkan = otomatis"></div>
  <div class="field"><label>ISBN</label><input type="text" name="isbn" value="<?= $v('isbn') ?>"></div>
  <div class="field"><label>Kategori *</label><select name="kategori_id" required><option value="">— pilih —</option>
    <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= isset($b['kategori_id']) && $b['kategori_id'] !== '' && (int)$b['kategori_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['nama']) ?></option><?php endforeach; ?></select></div>
  <div class="field span2"><label>Judul *</label><input type="text" name="judul" required value="<?= $v('judul') ?>"></div>
  <div class="field"><label>Pengarang *</label><input type="text" name="pengarang" required value="<?= $v('pengarang') ?>"></div>
  <div class="field"><label>Penerbit</label><input type="text" name="penerbit" value="<?= $v('penerbit') ?>"></div>
  <div class="field"><label>Tahun terbit</label><input type="number" name="tahun" min="1000" max="2100" value="<?= $v('tahun') ?>"></div>
  <div class="field"><label>Edisi</label><input type="text" name="edisi" value="<?= $v('edisi') ?>"></div>
  <div class="field"><label>Bahasa</label><input type="text" name="bahasa" value="<?= $v('bahasa', 'Indonesia') ?>"></div>
  <div class="field"><label>Jumlah halaman</label><input type="number" name="halaman" min="0" value="<?= $v('halaman') ?>"></div>
  <div class="field"><label>Lokasi rak</label><input type="text" name="lokasi_rak" value="<?= $v('lokasi_rak') ?>" placeholder="RAK A-1"></div>
  <div class="field"><label>Jumlah eksemplar</label><input type="number" name="stok_total" min="0" value="<?= $v('stok_total', 1) ?>"></div>
  <div class="field span2" style="grid-column:1/-1"><label>Sinopsis</label><textarea name="sinopsis"><?= $v('sinopsis') ?></textarea></div>
  <div class="field span2"><label>Berkas PDF (e-book / sampel)</label><input type="file" name="file_pdf" accept="application/pdf,.pdf">
    <?php if ($b && !empty($b['file_pdf'])): ?><div class="hint">Saat ini: <?= e($b['file_pdf']) ?> · <a target="_blank" href="<?= url('file.php?id=' . (int)$b['id']) ?>">lihat</a> · <label style="display:inline"><input type="checkbox" name="hapus_pdf" value="1"> hapus PDF</label></div><?php else: ?><div class="hint">Maks 60 MB (atur upload_max_filesize di php.ini jika perlu).</div><?php endif; ?></div>
  <div class="field"><label>Gambar sampul (opsional)</label><input type="file" name="cover" accept="image/*"><div class="hint">Tanpa gambar, sampul dibuat otomatis.</div></div>
</div>
<div class="actions"><button class="btn" type="submit">💾 Simpan</button><a class="btn btn-ghost" href="<?= url('admin/biblio.php') ?>">Batal</a></div>
</form>
<?php page_end('admin');
