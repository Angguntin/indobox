<?php
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    $pdo->exec("SET time_zone = '+07:00'");
} catch (PDOException $ex) {
    http_response_code(500);
    die('<h2 style="font-family:sans-serif">Database belum siap</h2><p style="font-family:sans-serif">Pastikan MySQL di XAMPP menyala dan database <b>indobox</b> sudah di-import (lihat README.txt).<br><small>' . htmlspecialchars($ex->getMessage()) . '</small></p>');
}

function q($sql, $p = []) { global $pdo; $s = $pdo->prepare($sql); $s->execute($p); return $s; }
function rows($sql, $p = []) { return q($sql, $p)->fetchAll(); }
function row($sql, $p = []) { $r = q($sql, $p)->fetch(); return $r === false ? null : $r; }
function val($sql, $p = []) { $v = q($sql, $p)->fetchColumn(); return $v === false ? null : $v; }

/* Insert: jika tabel kosong, ID pertama = 0, selanjutnya 1,2,3,... (tidak pernah minus) */
function db_insert($table, $data) {
    global $pdo;
    $empty = ((int)val("SELECT COUNT(*) FROM `$table`")) === 0;
    if ($empty) {
        $pdo->exec("SET SESSION sql_mode = CONCAT_WS(',', @@sql_mode, 'NO_AUTO_VALUE_ON_ZERO')");
        $data = ['id' => 0] + $data;
    }
    $cols = array_keys($data);
    $sql  = "INSERT INTO `$table` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")";
    q($sql, array_values($data));
    return $empty ? 0 : (int)$pdo->lastInsertId();
}
function db_update($table, $id, $data) {
    $set = implode(',', array_map(function ($c) { return "`$c`=?"; }, array_keys($data)));
    q("UPDATE `$table` SET $set WHERE id=?", array_merge(array_values($data), [$id]));
}
/* Delete + rapikan AUTO_INCREMENT supaya ID berikutnya tetap berurutan */
function db_delete($table, $id) {
    q("DELETE FROM `$table` WHERE id=?", [$id]);
    resequence($table);
}
function resequence($table) {
    $max = val("SELECT MAX(id) FROM `$table`");
    $next = ($max === null) ? 1 : ((int)$max + 1);
    q("ALTER TABLE `$table` AUTO_INCREMENT = $next");
}
