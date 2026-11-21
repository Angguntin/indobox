<?php
require __DIR__ . '/includes/bootstrap.php';
require_member(); csrf_check();
$bid = pid('biblio_id'); $mid = (int)me()['id']; $rt = (int)post('rating', 5); $kom = mb_substr(post('komentar'), 0, 400);
if ($bid === null || !val("SELECT COUNT(*) FROM biblios WHERE id=?", [$bid])) redirect('opac.php');
$rt = max(1, min(5, $rt));
$ex = row("SELECT id FROM reviews WHERE biblio_id=? AND member_id=?", [$bid, $mid]);
if ($ex) db_update('reviews', $ex['id'], ['rating' => $rt, 'komentar' => $kom, 'created_at' => date('Y-m-d H:i:s')]);
else db_insert('reviews', ['biblio_id' => $bid, 'member_id' => $mid, 'rating' => $rt, 'komentar' => $kom]);
flash('ok', 'Terima kasih, ulasan Anda tersimpan.');
redirect('detail.php?id=' . $bid);
