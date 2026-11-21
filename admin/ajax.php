<?php
require __DIR__ . '/../includes/bootstrap.php';
require_staff();
header('Content-Type: application/json; charset=utf-8');
$t = get('type'); $term = get('term'); $out = [];
if (mb_strlen($term) >= 1) {
    if ($t === 'member') {
        foreach (rows("SELECT no_anggota, nama FROM members WHERE (no_anggota LIKE ? OR nama LIKE ?) ORDER BY nama LIMIT 10", ["%$term%", "%$term%"]) as $r)
            $out[] = ['value' => $r['no_anggota'], 'label' => $r['nama']];
    } elseif ($t === 'biblio') {
        foreach (rows("SELECT kode_barcode, judul, stok_tersedia FROM biblios WHERE (kode_barcode LIKE ? OR judul LIKE ? OR isbn LIKE ?) ORDER BY judul LIMIT 10", ["%$term%", "%$term%", "%$term%"]) as $r)
            $out[] = ['value' => $r['kode_barcode'], 'label' => $r['judul'] . ' (tersedia ' . $r['stok_tersedia'] . ')'];
    }
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);
