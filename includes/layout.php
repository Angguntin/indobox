<?php
function html_head($title) {
    $nm = setting('nama_perpustakaan', 'INDO BOX');
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>' . e($title) . ' · ' . e($nm) . '</title>'
        . '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
        . '<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">'
        . '<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27%3E%3Crect width=%27100%27 height=%27100%27 rx=%2726%27 fill=%27%23f08ab0%27/%3E%3Cpath d=%27M22 38l28-14 28 14v34L50 86 22 72z%27 fill=%27%23d9f3e4%27/%3E%3C/svg%3E">'
        . '<link rel="stylesheet" href="' . url('assets/css/style.css') . '"></head>';
}
function logo_html() {
    return '<span class="logo"><svg viewBox="0 0 48 48" width="34" height="34" aria-hidden="true"><rect width="48" height="48" rx="13" fill="#f08ab0"/><path d="M10 19l14-7 14 7v17l-14 7-14-7z" fill="#d9f3e4"/><path d="M10 19l14 7 14-7M24 26v17" stroke="#d6457f" stroke-width="2.2" fill="none" stroke-linejoin="round"/></svg><b>' . e(setting('nama_perpustakaan', 'INDO BOX')) . '</b></span>';
}

function page_start($title, $area = 'public', $active = '') {
    html_head($title);
    if ($area === 'admin') { admin_shell_start($title, $active); return; }
    $u = me();
    $nav = ['index.php' => ['Beranda', 'home'], 'opac.php' => ['Katalog OPAC', 'opac'], 'news.php' => ['Pengumuman', 'news'], 'visit.php' => ['Kunjungan', 'visit']];
    echo '<body class="pub"><header class="topnav"><div class="wrap nav-in"><a href="' . url('index.php') . '" class="brand">' . logo_html() . '</a>'
        . '<button class="burger" type="button" data-toggle="#pubmenu" aria-label="Menu">☰</button><nav id="pubmenu">';
    foreach ($nav as $f => $n) echo '<a href="' . url($f) . '" class="' . ($active === $n[1] ? 'on' : '') . '">' . $n[0] . '</a>';
    if (is_staff()) echo '<a class="btn btn-sm" href="' . url('admin/index.php') . '">Panel Petugas</a>';
    elseif (is_member()) echo '<a class="btn btn-sm" href="' . url('member/index.php') . '">Akun Saya</a><a href="' . url('logout.php') . '">Keluar</a>';
    else echo '<a class="btn btn-sm" href="' . url('login.php') . '">Masuk</a>';
    echo '</nav></div></header><main class="wrap pub-main">' . flash_html();
}
function member_nav($active) {
    $items = ['index.php' => ['Ringkasan', 'home'], 'history.php' => ['Riwayat Pinjam', 'history'], 'reservations.php' => ['Reservasi', 'res'], 'card.php' => ['Kartu Anggota', 'card'], 'profile.php' => ['Profil & Password', 'profile']];
    echo '<div class="subnav">';
    foreach ($items as $f => $n) echo '<a class="' . ($active === $n[1] ? 'on' : '') . '" href="' . url('member/' . $f) . '">' . $n[0] . '</a>';
    echo '</div>';
}

function page_end($area = 'public') {
    if ($area === 'admin') { echo '</div></main></div><script src="' . url('assets/js/app.js') . '"></script></body></html>'; return; }
    $nm = e(setting('nama_perpustakaan', 'INDO BOX'));
    echo '</main><footer class="foot"><div class="wrap foot-in"><div>' . logo_html() . '<p>' . e(setting('tagline', '')) . '</p></div>'
        . '<div><h5>Kontak</h5><p>' . e(setting('alamat')) . '<br>' . e(setting('telepon')) . ' · ' . e(setting('email')) . '</p></div>'
        . '<div><h5>Jam Layanan</h5><p>' . nl2br(e(setting('jam_buka'))) . '</p></div></div>'
        . '<div class="wrap copy">© ' . date('Y') . ' ' . $nm . ' — Sistem Otomasi Perpustakaan</div></footer><script src="' . url('assets/js/app.js') . '"></script></body></html>';
}

function admin_shell_start($title, $active) {
    expire_reservations();
    $u = me();
    $role = $u['role'];
    $pending = (int)val("SELECT COUNT(*) FROM reservations WHERE status IN ('menunggu','siap')");
    $overdue = (int)val("SELECT COUNT(*) FROM loans WHERE status='dipinjam' AND tgl_jatuh_tempo < CURDATE()");
    $groups = [
        ['Utama', [['index.php', 'dash', '', 'Dashboard']]],
        ['Bibliografi', [['biblio.php', 'biblio', '', 'Daftar Koleksi'], ['biblio_form.php', 'biblio_add', '', 'Tambah Koleksi'], ['categories.php', 'cat', '️', 'Kategori'], ['labels.php', 'labels', '', 'Cetak Label & Katalog']]],
        ['Keanggotaan', [['members.php', 'members', '', 'Daftar Anggota'], ['member_form.php', 'member_add', '', 'Tambah Anggota'], ['member_types.php', 'mtype', '', 'Tipe Anggota']]],
        ['Sirkulasi', [['circulation.php', 'pinjam', '', 'Peminjaman'], ['return.php', 'return', '', 'Pengembalian'], ['loans.php', 'loans', '', 'Sedang Dipinjam'], ['reservations.php', 'res', '', 'Reservasi'], ['fines.php', 'fines', '', 'Denda'], ['history.php', 'history', '', 'Riwayat Pinjam-Kembali']]],
        ['Analitik', [['stats.php', 'stats', '', 'Statistik'], ['visits.php', 'visits', '', 'Kunjungan']]],
        ['Konten', [['news.php', 'news', '', 'Pengumuman']]],
    ];
    if ($role === 'admin') $groups[] = ['Sistem', [['users.php', 'users', '', 'Pengguna / Petugas'], ['settings.php', 'settings', '️', 'Pengaturan'], ['backup.php', 'backup', '', 'Backup Database'], ['logs.php', 'logs', '', 'Log Aktivitas']]];
    $groups[] = ['Akun', [['profile.php', 'profile', '', 'Ganti Password']]];
    echo '<body class="adm"><div class="shell"><aside class="side" id="side"><a href="' . url('admin/index.php') . '" class="side-brand">' . logo_html() . '</a><div class="side-scroll">';
    foreach ($groups as $g) {
        echo '<div class="sg"><h6>' . $g[0] . '</h6>';
        foreach ($g[1] as $i) {
            $badge = '';
            if ($i[1] === 'res' && $pending) $badge = '<em>' . $pending . '</em>';
            if ($i[1] === 'loans' && $overdue) $badge = '<em class="warn">' . $overdue . '</em>';
            echo '<a href="' . url('admin/' . $i[0]) . '" class="' . ($active === $i[1] ? 'on' : '') . '"><span>' . $i[2] . '</span>' . $i[3] . $badge . '</a>';
        }
        echo '</div>';
    }
    echo '<div class="sg"><a href="' . url('index.php') . '" target="_blank"><span></span>Lihat OPAC</a><a href="' . url('logout.php') . '"><span></span>Keluar</a></div></div></aside>'
        . '<main class="main"><header class="bar"><button class="burger" type="button" data-toggle="#side" aria-label="Menu">☰</button><h1>' . e($title) . '</h1>'
        . '<div class="who"><span class="avatar">' . e(mb_strtoupper(mb_substr($u['nama'], 0, 1))) . '</span><div><b>' . e($u['nama']) . '</b><small>' . e(ucfirst($role)) . '</small></div></div></header><div class="content">' . flash_html();
}