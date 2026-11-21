<?php
/* ============================================================
   INDO BOX - Sistem Otomasi Perpustakaan
   Konfigurasi utama. Sesuaikan jika MySQL XAMPP Anda memakai password.
   ============================================================ */
if (session_status() === PHP_SESSION_NONE) session_start();
date_default_timezone_set('Asia/Jakarta');

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3309');      // port MySQL XAMPP Anda (default 3306)
define('DB_NAME', 'indobox');
define('DB_USER', 'root');
define('DB_PASS', '');          // default XAMPP: kosong

define('APP_ROOT', realpath(__DIR__ . '/..'));
$__doc  = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
$__base = ($__doc && strpos(APP_ROOT, $__doc) === 0)
        ? str_replace('\\', '/', substr(APP_ROOT, strlen($__doc)))
        : '/indobox';
define('BASE_URL', rtrim($__base, '/'));
define('UPLOAD_PDF',   APP_ROOT . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pdf'   . DIRECTORY_SEPARATOR);
define('UPLOAD_COVER', APP_ROOT . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'cover' . DIRECTORY_SEPARATOR);
