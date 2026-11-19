<?php
// ============================================================
// KERANGKA TAMPILAN: head() membuka halaman, foot() menutupnya
//
// Mode halaman:
//   'app'  = sudah login (menu admin / pelanggan)
//   'page' = halaman publik dengan kontainer
//   'full' = halaman publik tanpa kontainer (landing page)
//   'bare' = tanpa menu (login / daftar)
// ============================================================

const MENU = [
    'admin' => [
        'index'     => ['Dashboard',       'bi-grid-1x2'],
        'pelanggan' => ['Pelanggan',       'bi-people'],
        'transaksi' => ['Transaksi',       'bi-receipt'],
        'layanan'   => ['Layanan & Harga', 'bi-tags'],
        'promo'     => ['Promosi',         'bi-megaphone'],
        'feedback'  => ['Umpan Balik',     'bi-chat-heart'],
        'laporan'   => ['Laporan',         'bi-bar-chart-line'],
    ],
    'customer' => [
        'index'      => ['Beranda',    'bi-house'],
        'layanan'    => ['Layanan',    'bi-tags'],
        'riwayat'    => ['Riwayat',    'bi-clock-history'],
        'notifikasi' => ['Notifikasi', 'bi-bell'],
        'promo'      => ['Promo',      'bi-gift'],
        'feedback'   => ['Feedback',   'bi-chat-heart'],
        'profil'     => ['Profil',     'bi-person-circle'],
    ],
];

// ------------------------------------------------------------
// Garis waktu status cucian: Antri > Proses > Selesai > Ambil
// ------------------------------------------------------------
function tracker($status) {
    $ikon   = ['bi-box-seam', 'bi-droplet-half', 'bi-check2-circle', 'bi-bag-check'];
    $posisi = array_search($status, STATUS);
    $terakhir = count(STATUS) - 1;

    $html = "<div class='steps'>";

    foreach (STATUS as $i => $nama) {
        if ($posisi == $terakhir || $i < $posisi) {
            $kelas = 'done';        // tahap sudah lewat
        } elseif ($i == $posisi) {
            $kelas = 'now';         // tahap saat ini
        } else {
            $kelas = '';            // tahap berikutnya
        }

        $html .= "<div class='step $kelas'>";
        $html .=   "<i class='bi {$ikon[$i]}'></i>$nama";
        $html .= "</div>";
    }

    return $html . "</div>";
}

// ------------------------------------------------------------
// Pembuka halaman (<head> + menu sesuai peran)
// ------------------------------------------------------------
function head($judul, $peran = null, $mode = false) {
    global $MODE, $ROLE;

    $ROLE = $peran;
    $MODE = $mode ?: ($peran ? 'app' : 'page');
    $base = $peran ? '../' : '';     // lokasi folder assets dari halaman ini
    ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($judul) ?> - Husain Laundry</title>

  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;800&display=swap">
  <link rel="stylesheet" href="<?= $base ?>assets/style.css">

  <?php if ($peran && !empty($_SESSION['csrf'])): ?>
    <meta name="csrf" content="<?= e($_SESSION['csrf']) ?>">
  <?php endif ?>
</head>
<body class="r-<?= $peran ?: 'pub' ?>">
<?php
    if ($mode === 'bare') {
        return;
    }

    if ($peran === 'admin') {
        menu_admin($judul);
    } elseif ($peran === 'customer') {
        menu_pelanggan();
    } else {
        menu_publik($mode);
    }
}

// ------------------------------------------------------------
// Menu admin: sidebar kiri + bilah atas
// ------------------------------------------------------------
function menu_admin($judul) {
    $aktif   = basename($_SERVER['PHP_SELF'], '.php');
    $baru    = q("SELECT COUNT(*) FROM feedback WHERE status='Baru'")->fetchColumn();
    $inisial = mb_strtoupper(mb_substr($_SESSION['nama'], 0, 1));
    ?>
<aside class="side" id="side">

  <a class="brand" href="index.php">
    <i class="bi bi-droplet-half"></i> Husain Laundry
  </a>
  <div class="sub">Panel admin E-CRM</div>

  <?php foreach (MENU['admin'] as $file => [$label, $ikon]): ?>
    <a class="m <?= $aktif == $file ? 'on' : '' ?>" href="<?= $file ?>.php">
      <i class="bi <?= $ikon ?>"></i><?= $label ?>

      <?php if ($file == 'feedback'): ?>
        <span class="badge bg-warning text-dark ms-auto"
              id="fbadge" <?= $baru ? '' : 'hidden' ?>><?= $baru ?></span>
      <?php endif ?>
    </a>
  <?php endforeach ?>

  <div class="side-foot">
    <a class="m" href="../logout.php">
      <i class="bi bi-box-arrow-left"></i>Keluar
    </a>
  </div>

</aside>

<div class="content">

  <header class="topbar no-print">
    <button class="btn btn-light d-xl-none"
            onclick="document.getElementById('side').classList.toggle('open')">
      <i class="bi bi-list"></i>
    </button>

    <h5 class="m-0"><?= e($judul) ?></h5>

    <span class="ms-auto live">
      <i class="dot"></i>
      <span class="d-none d-sm-inline">Live</span>
      <small id="lu"></small>
    </span>

    <span class="avatar"><?= $inisial ?></span>
    <span class="d-none d-md-inline fw-semibold"><?= e($_SESSION['nama']) ?></span>
  </header>

  <main class="p-3 p-xl-4">
    <?= flash() ?>
<?php
}

// ------------------------------------------------------------
// Menu pelanggan: bilah atas (laptop/PC) + tab bawah (HP)
// ------------------------------------------------------------
function menu_pelanggan() {
    $aktif   = basename($_SERVER['PHP_SELF'], '.php');
    $inisial = mb_strtoupper(mb_substr($_SESSION['nama'], 0, 1));
    $depan   = explode(' ', $_SESSION['nama'])[0];

    $belum = q("SELECT COUNT(*) FROM notifikasi WHERE user_id=? AND dibaca=0", [$_SESSION['uid']])
             ->fetchColumn();

    $badge = "<span class='nbadge' " . ($belum ? '' : 'hidden') . ">$belum</span>";

    $atas  = ['index', 'layanan', 'riwayat', 'promo', 'feedback'];    // tautan di bilah atas
    $bawah = ['index', 'riwayat', 'notifikasi', 'promo', 'profil'];   // tab di HP
    ?>
<header class="cnav no-print">
  <div class="container cnav-in">

    <a class="brand" href="index.php">
      <span class="logo"><i class="bi bi-droplet-half"></i></span>
      <span>Husain Laundry</span>
    </a>

    <nav class="cnav-links d-none d-lg-flex">
      <?php foreach ($atas as $f): [$label, $ikon] = MENU['customer'][$f]; ?>
        <a class="cl <?= $aktif == $f ? 'active' : '' ?>" href="<?= $f ?>.php">
          <i class="bi <?= $ikon ?>"></i><?= $label ?>
        </a>
      <?php endforeach ?>
    </nav>

    <div class="cnav-right">
      <span class="live d-none d-md-flex"><i class="dot"></i>Live</span>

      <a href="notifikasi.php" class="bell" title="Notifikasi">
        <i class="bi bi-bell"></i><?= $badge ?>
      </a>

      <div class="dropdown">
        <button class="avatar-btn" data-bs-toggle="dropdown">
          <span class="avatar"><?= $inisial ?></span>
          <span class="d-none d-md-inline"><?= e($depan) ?></span>
          <i class="bi bi-chevron-down small d-none d-md-inline"></i>
        </button>

        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
          <li>
            <a class="dropdown-item" href="profil.php">
              <i class="bi bi-person-circle me-2"></i>Profil saya
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="layanan.php">
              <i class="bi bi-tags me-2"></i>Layanan & harga
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="feedback.php">
              <i class="bi bi-chat-heart me-2"></i>Feedback
            </a>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <a class="dropdown-item text-danger" href="../logout.php">
              <i class="bi bi-box-arrow-right me-2"></i>Keluar
            </a>
          </li>
        </ul>
      </div>
    </div>

  </div>
</header>

<nav class="tabbar no-print">
  <?php foreach ($bawah as $f): [$label, $ikon] = MENU['customer'][$f]; ?>
    <a class="<?= $aktif == $f ? 'active' : '' ?>" href="<?= $f ?>.php">
      <i class="bi <?= $ikon ?>"></i><?= $label ?>
      <?= $f == 'notifikasi' ? $badge : '' ?>
    </a>
  <?php endforeach ?>
</nav>

<main class="container py-4 cmain">
  <?= flash() ?>
<?php
}

// ------------------------------------------------------------
// Menu halaman publik (sebelum login)
// ------------------------------------------------------------
function menu_publik($mode) {
    $gelap = $mode === 'full';     // 'full' = di atas hero berwarna gelap

    $kelasNav   = $gelap ? 'navbar-dark position-absolute w-100' : 'bg-white border-bottom';
    $kelasMasuk = $gelap ? 'btn-outline-light' : 'btn-outline-primary';
    ?>
<nav class="navbar navbar-expand-lg <?= $kelasNav ?>" style="z-index:5">
  <div class="container">

    <a class="navbar-brand fw-bold" href="index.php">
      <i class="bi bi-droplet-half text-warning"></i> Husain Laundry
    </a>

    <div class="ms-auto">
      <a class="btn btn-sm <?= $kelasMasuk ?> me-2" href="login.php">Masuk</a>
      <a class="btn btn-sm btn-sun" href="register.php">Daftar gratis</a>
    </div>

  </div>
</nav>

<?php if (!$gelap): ?>
  <main class="container py-4">
    <?= flash() ?>
<?php endif;
}

// ------------------------------------------------------------
// Penutup halaman
// ------------------------------------------------------------
function foot() {
    global $MODE, $ROLE;

    if ($MODE === 'app' && $ROLE === 'admin') {
        echo '</main></div>';
    } elseif ($MODE === 'app' || $MODE === 'page') {
        echo '</main>';
        echo '<footer class="text-center text-muted small py-4 no-print">';
        echo '&copy; ' . date('Y') . ' Husain Laundry. Sistem E-CRM';
        echo '</footer>';
    }
    ?>
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="tc"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($MODE === 'app'): ?>
  <script>
    window.APP = { role: '<?= $ROLE ?>', uid: <?= (int)$_SESSION['uid'] ?> };
  </script>
<?php endif ?>

<script src="<?= $ROLE ? '../' : '' ?>assets/app.js"></script>
</body>
</html>
<?php
}
