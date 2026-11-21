<?php
require __DIR__ . '/../includes/bootstrap.php';
require_staff(['admin']); csrf_check();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $pdo;
    header('Content-Type: application/sql; charset=utf-8'); header('Content-Disposition: attachment; filename="indobox-backup-' . date('Ymd-His') . '.sql"');
    echo "-- Backup INDO BOX " . date('Y-m-d H:i:s') . "\nSET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nCREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4;\nUSE `" . DB_NAME . "`;\n\n";
    foreach ($pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN) as $t) {
        $c = $pdo->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM);
        echo "DROP TABLE IF EXISTS `$t`;\n" . $c[1] . ";\n\n";
        foreach ($pdo->query("SELECT * FROM `$t`")->fetchAll() as $r) {
            $vals = array_map(function ($v) use ($pdo) { return $v === null ? 'NULL' : $pdo->quote($v); }, array_values($r));
            echo "INSERT INTO `$t` (`" . implode('`,`', array_keys($r)) . "`) VALUES (" . implode(',', $vals) . ");\n";
        }
        echo "\n";
    }
    echo "SET FOREIGN_KEY_CHECKS=1;\n";
    log_act('Backup database'); exit;
}
page_start('Backup Database', 'admin', 'backup');
?>
<div class="card"><h3>Unduh cadangan database</h3><p>Berkas <code>.sql</code> berisi seluruh tabel dan data (ID tetap dari 0). Pulihkan lewat phpMyAdmin → Import.</p>
<form method="post"><?= csrf() ?><button class="btn" type="submit">💾 Unduh backup sekarang</button></form>
<p class="hint">File PDF/sampul yang diunggah tersimpan di folder <code>uploads/</code> — salin folder itu juga untuk backup lengkap.</p></div>
<?php page_end('admin');
