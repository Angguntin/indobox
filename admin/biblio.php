<?php
require __DIR__ . '/../includes/bootstrap.php';
require_staff(); csrf_check();

/* Hapus */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'del') {
    $id = pid('id'); $b = $id === null ? null : row("SELECT * FROM biblios WHERE id=?", [$id]);
    if ($b) {
        try {
            db_delete('biblios', $id);
            if ($b['file_pdf'] && is_file(UPLOAD_PDF . basename($b['file_pdf']))) @unlink(UPLOAD_PDF . basename($b['file_pdf']));
            if ($b['cover'] && is_file(UPLOAD_COVER . basename($b['cover']))) @unlink(UPLOAD_COVER . basename($b['cover']));
            log_act('Hapus koleksi: ' . $b['judul']); flash('ok', 'Koleksi dihapus.');
        } catch (PDOException $ex) { flash('err', 'Koleksi tidak bisa dihapus karena sudah memiliki riwayat peminjaman. Ubah stok menjadi 0 jika ingin menonaktifkannya.'); }
    }
    redirect('admin/biblio.php');
}
/* Import XML: MARCXML (iPusnas/Perpusnas/SLiMS/dll) atau format sederhana <koleksi><buku> */
function marc_sf($rec, $tags, $codes) {
    foreach ((array)$tags as $t) {
        foreach ($rec->xpath("datafield[@tag='$t']") as $df) {
            $out = array();
            foreach ($df->subfield as $sf) { $c = (string)$sf['code']; if ($c !== '' && strpos($codes, $c) !== false) $out[] = trim((string)$sf); }
            if ($out) return trim(implode(' ', $out), " /:;,.");
        }
    }
    return '';
}
function marc_all($rec, $tags, $code, $max = 3) {
    $out = array();
    foreach ((array)$tags as $t) foreach ($rec->xpath("datafield[@tag='$t']/subfield[@code='$code']") as $sf) { $v = trim((string)$sf, " /:;,."); if ($v !== '') $out[] = $v; }
    return implode('; ', array_slice(array_unique($out), 0, $max));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'import') {
    try {
        if (empty($_FILES['xml']) || $_FILES['xml']['error'] !== UPLOAD_ERR_OK) throw new Exception('Pilih berkas XML terlebih dahulu.');
        if (strtolower(pathinfo($_FILES['xml']['name'], PATHINFO_EXTENSION)) !== 'xml') throw new Exception('Berkas harus berekstensi .xml');
        $raw = file_get_contents($_FILES['xml']['tmp_name']);
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
        $raw = preg_replace('/\s(xmlns(:\w+)?|xsi:\w+)="[^"]*"/', '', $raw);
        $raw = preg_replace('/<(\/?)marc:/', '<$1', $raw);
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($raw);
        if ($xml === false) throw new Exception('Isi XML tidak valid / rusak.');
        $bhsMap = array('ind' => 'Indonesia', 'eng' => 'Inggris', 'jav' => 'Jawa', 'ara' => 'Arab', 'zho' => 'Mandarin', 'dut' => 'Belanda');
        $items = array();
        foreach ($xml->xpath('//record') as $rec) {
            $thn = ''; if (preg_match('/(1[5-9]\d\d|20\d\d)/', marc_sf($rec, array('264', '260'), 'c'), $mm)) $thn = $mm[1];
            $isbn = ''; if (preg_match('/[\dXx\-]{10,17}/', marc_sf($rec, '020', 'a'), $mm)) $isbn = $mm[0];
            $hal = ''; if (preg_match('/\d+/', marc_sf($rec, '300', 'a'), $mm)) $hal = $mm[0];
            $lang = strtolower(marc_sf($rec, '041', 'a'));
            $pg = marc_all($rec, array('100', '110', '700', '710'), 'a');
            $items[] = array('judul' => marc_sf($rec, '245', 'ab'), 'pengarang' => $pg !== '' ? $pg : marc_sf($rec, '245', 'c'),
                'penerbit' => marc_sf($rec, array('264', '260'), 'b'), 'tahun' => $thn, 'kategori' => marc_sf($rec, '650', 'a'), 'isbn' => $isbn,
                'stok' => count($rec->xpath("datafield[@tag='990']")), 'rak' => marc_sf($rec, array('090', '082', '084'), 'ab'),
                'bahasa' => isset($bhsMap[$lang]) ? $bhsMap[$lang] : ($lang !== '' ? $lang : 'Indonesia'), 'halaman' => $hal,
                'sinopsis' => marc_sf($rec, '520', 'a'), 'edisi' => marc_sf($rec, '250', 'a'));
        }
        if (!$items) foreach ($xml->xpath('//buku') as $it) {
            $d = array(); foreach ($it->children() as $k => $v) $d[strtolower($k)] = trim((string)$v);
            $items[] = $d;
        }
        if (!$items) throw new Exception('Tidak ada data buku di file XML ini (file kosong atau formatnya tidak dikenali). Coba unduh ulang file dari sumbernya.');
        $n = 0; $skip = 0;
        foreach ($items as $d) {
            $judul = trim(nz($d, 'judul', '')); if ($judul === '') continue;
            $peng = trim(nz($d, 'pengarang', '')) ?: '-'; $isbn = trim(nz($d, 'isbn', ''));
            if (($isbn !== '' && val("SELECT COUNT(*) FROM biblios WHERE isbn=?", array($isbn)) > 0) || val("SELECT COUNT(*) FROM biblios WHERE judul=? AND pengarang=?", array($judul, $peng)) > 0) { $skip++; continue; }
            $kn = trim(nz($d, 'kategori', '')) ?: 'Karya Umum';
            $cat = row("SELECT id FROM categories WHERE nama=?", array($kn));
            $cid = $cat ? (int)$cat['id'] : db_insert('categories', array('kode' => strtoupper(mb_substr(preg_replace('/[^A-Za-z]/', '', $kn), 0, 3)) ?: 'UMM', 'nama' => mb_substr($kn, 0, 80)));
            $stok = max(1, (int)nz($d, 'stok', 1));
            db_insert('biblios', array('kode_barcode' => next_barcode(), 'isbn' => $isbn ?: null, 'judul' => mb_substr($judul, 0, 255), 'pengarang' => mb_substr($peng, 0, 150),
                'penerbit' => mb_substr(trim(nz($d, 'penerbit', '')), 0, 150), 'tahun' => (int)nz($d, 'tahun', 0) ?: null, 'edisi' => trim(nz($d, 'edisi', '')) ?: null, 'kategori_id' => $cid,
                'bahasa' => trim(nz($d, 'bahasa', '')) ?: 'Indonesia', 'halaman' => (int)nz($d, 'halaman', 0) ?: null, 'lokasi_rak' => mb_substr(trim(nz($d, 'rak', '')), 0, 30),
                'sinopsis' => trim(nz($d, 'sinopsis', '')), 'stok_total' => $stok, 'stok_tersedia' => $stok, 'tgl_masuk' => date('Y-m-d')));
            $n++;
        }
        log_act("Import XML bibliografi: $n baris, $skip dilewati"); flash('ok', "$n koleksi berhasil diimpor" . ($skip ? ", $skip dilewati karena sudah ada (ISBN/judul sama)." : '.'));
    } catch (Exception $ex) { flash('err', $ex->getMessage()); }
    redirect('admin/biblio.php');
}

$qq = get('q'); $kat = get('kat'); $page = max(1, (int)get('page', 1)); $per = 15;
$where = ['1=1']; $p = [];
if ($qq !== '') { $where[] = '(b.judul LIKE ? OR b.pengarang LIKE ? OR b.kode_barcode LIKE ? OR b.isbn LIKE ? OR b.penerbit LIKE ?)'; for ($i = 0; $i < 5; $i++) $p[] = "%$qq%"; }
if ($kat !== '' && ctype_digit($kat)) { $where[] = 'b.kategori_id=?'; $p[] = (int)$kat; }
$W = implode(' AND ', $where);

if (get('export') === '1') {
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="bibliografi-' . date('Ymd') . '.csv"');
    $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
    fputcsv($o, ['id', 'barcode', 'judul', 'pengarang', 'penerbit', 'tahun', 'kategori', 'isbn', 'bahasa', 'halaman', 'rak', 'stok_total', 'stok_tersedia']);
    foreach (rows("SELECT b.*, c.nama kategori FROM biblios b JOIN categories c ON c.id=b.kategori_id WHERE $W ORDER BY b.id", $p) as $r)
        fputcsv($o, [$r['id'], $r['kode_barcode'], $r['judul'], $r['pengarang'], $r['penerbit'], $r['tahun'], $r['kategori'], $r['isbn'], $r['bahasa'], $r['halaman'], $r['lokasi_rak'], $r['stok_total'], $r['stok_tersedia']]);
    exit;
}
$total = (int)val("SELECT COUNT(*) FROM biblios b WHERE $W", $p); $off = ($page - 1) * $per;
$list = rows("SELECT b.*, c.nama kategori FROM biblios b JOIN categories c ON c.id=b.kategori_id WHERE $W ORDER BY b.id DESC LIMIT $per OFFSET $off", $p);
$cats = rows("SELECT * FROM categories ORDER BY nama");
page_start('Daftar Koleksi (Bibliografi)', 'admin', 'biblio');
?>
<div class="card">
<form method="get" class="toolbar">
  <div class="field" style="flex:1;min-width:200px"><label>Cari</label><input type="search" name="q" value="<?= e($qq) ?>" placeholder="Judul, pengarang, barcode, ISBN…"></div>
  <div class="field"><label>Kategori</label><select name="kat"><option value="">Semua</option><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $kat !== '' && (int)$kat === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['nama']) ?></option><?php endforeach; ?></select></div>
  <button class="btn" type="submit">Filter</button>
  <a class="btn btn-mint" href="<?= url('admin/biblio_form.php') ?>">➕ Tambah</a>
  <a class="btn btn-ghost" href="?<?= e(qs(['export' => 1])) ?>">⬇ Ekspor CSV</a>
</form>
<form method="post" action="<?= url('admin/labels.php') ?>" target="_blank"><?= csrf() ?><input type="hidden" name="go" value="1">
<div class="tbl-wrap"><table>
<tr><th><input type="checkbox" data-check-all aria-label="Pilih semua"></th><th>ID</th><th>Barcode</th><th>Judul</th><th>Kategori</th><th>Stok</th><th>PDF</th><th></th></tr>
<?php foreach ($list as $b): ?>
<tr><td><input type="checkbox" name="ids[]" value="<?= $b['id'] ?>"></td><td><?= $b['id'] ?></td><td><?= e($b['kode_barcode']) ?></td>
<td><b><?= e($b['judul']) ?></b><br><small class="muted"><?= e($b['pengarang']) ?> · <?= e($b['tahun']) ?></small></td><td><?= e($b['kategori']) ?></td>
<td><span class="badge <?= $b['stok_tersedia'] > 0 ? 'b-ok' : 'b-warn' ?>"><?= (int)$b['stok_tersedia'] ?>/<?= (int)$b['stok_total'] ?></span></td>
<td><?= $b['file_pdf'] ? '<span class="badge b-ok">Ada</span>' : '<span class="badge b-gray">-</span>' ?></td>
<td class="right"><a class="btn btn-sm btn-ghost" target="_blank" href="<?= url('detail.php?id=' . $b['id']) ?>">Lihat</a>
<a class="btn btn-sm btn-soft" href="<?= url('admin/biblio_form.php?id=' . $b['id']) ?>">Edit</a>
<button class="btn btn-sm btn-danger" type="submit" form="del-<?= $b['id'] ?>">Hapus</button></td></tr>
<?php endforeach; if (!$list): ?><tr><td colspan="8" class="empty">Belum ada koleksi.</td></tr><?php endif; ?></table></div>
<div class="actions" style="margin-top:.8rem"><span class="muted">Terpilih:</span>
  <button class="btn btn-sm btn-mint" type="submit" name="mode" value="label">🏷️ Cetak label barcode</button>
  <button class="btn btn-sm btn-soft" type="submit" name="mode" value="katalog">📖 Cetak katalog</button>
  <span class="hint">Tanpa centang = semua koleksi.</span></div>
</form>
<?php foreach ($list as $b): ?><form id="del-<?= $b['id'] ?>" method="post" data-confirm="Hapus koleksi ini beserta file PDF-nya?"><?= csrf() ?><input type="hidden" name="act" value="del"><input type="hidden" name="id" value="<?= $b['id'] ?>"></form><?php endforeach; ?>
<?= pager($total, $per, $page) ?>
</div>
<div class="card"><h3>Impor massal dari XML</h3>
<p class="muted">Mendukung <b>MARCXML</b> (hasil unduh katalog dari iPusnas/Perpusnas/OPAC perpustakaan lain) dan format sederhana <code>&lt;koleksi&gt;&lt;buku&gt;</code> dengan tag judul, pengarang, penerbit, tahun, kategori, isbn, stok, rak, bahasa, halaman, sinopsis. Barcode dibuat otomatis; buku yang ISBN/judulnya sudah ada dilewati.</p>
<form method="post" enctype="multipart/form-data" class="actions"><?= csrf() ?><input type="hidden" name="act" value="import"><input type="file" name="xml" accept=".xml,text/xml,application/xml" required><button class="btn" type="submit">Impor XML</button></form></div>
<?php page_end('admin');