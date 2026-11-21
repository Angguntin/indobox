<?php
require __DIR__ . '/includes/bootstrap.php';
$id = gid();
$b = $id === null ? null : row("SELECT judul, file_pdf FROM biblios WHERE id=?", [$id]);
if (!$b || !$b['file_pdf'] || !is_file(UPLOAD_PDF . basename($b['file_pdf']))) { http_response_code(404); die('Berkas PDF tidak tersedia.'); }
$path = UPLOAD_PDF . basename($b['file_pdf']);
$name = preg_replace('/[^A-Za-z0-9_\- ]/', '', $b['judul']) . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: ' . (get('dl') === '1' ? 'attachment' : 'inline') . '; filename="' . $name . '"');
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
readfile($path);
