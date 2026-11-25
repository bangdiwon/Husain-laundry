<?php
// ============================================================
// KERANGKA TAMPILAN: head() membuka halaman, foot() menutupnya
//
// Mode halaman:
//   'app'  = halaman admin (sudah login)
//   'page' = halaman publik dengan kontainer
//   'full' = halaman publik tanpa kontainer (landing page)
//   'bare' = tanpa menu (halaman login)
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
  <link rel="stylesheet"
        href="<?= $base ?>assets/style.css?v=<?= filemtime(__DIR__ . '/../assets/style.css') ?>">

  <?php if ($peran && !empty($_SESSION['csrf'])): ?>
    <meta name="csrf" content="<?= e($_SESSION['csrf']) ?>">
  <?php endif ?>
</head>
<body class="r-<?= $peran ?: 'customer' ?>">
<?php
    if ($mode === 'bare') {
        return;
    }

    if ($peran === 'admin') {
        menu_admin($judul);
    } else {
        menu_publik();
    }

    // Halaman publik biasa dibungkus kontainer (landing page mengatur sendiri)
    if (!$peran && $mode === 'page') {
        echo '<main class="container py-4">' . flash();
    }
}

// ------------------------------------------------------------
// Menu admin: sidebar kiri + bilah atas
// ------------------------------------------------------------
function menu_admin($judul) {
    $aktif   = basename($_SERVER['PHP_SELF'], '.php');
    $baru    = q("SELECT COUNT(*) FROM review WHERE balasan IS NULL OR balasan = ''")->fetchColumn();
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
    <a class="m text-danger" href="../logout.php"
       onclick="return confirm('Apakah Anda yakin ingin keluar dari panel admin?')">
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
// Menu website publik (pelanggan tidak perlu login)
// ------------------------------------------------------------
function menu_publik() {
    $tautan = [
        '#beranda' => 'Beranda',
        '#tentang' => 'Tentang',
        '#layanan' => 'Layanan',
        '#lacak'   => 'Lacak Pesanan',
        '#kontak'  => 'Kontak',
    ];
    ?>
<header class="cnav pubnav no-print">
  <div class="container cnav-in">

    <a class="brand" href="index.php">
      <span class="logo"><i class="bi bi-droplet-half"></i></span>
      <span>Husain Laundry</span>
    </a>

    <nav class="cnav-links d-none d-lg-flex">
      <?php foreach ($tautan as $href => $label): ?>
        <a class="cl" href="<?= $href ?>"><?= $label ?></a>
      <?php endforeach ?>
    </nav>

  <div class="cnav-right">
      <a class="btn btn-primary" href="#lacak">
        <i class="bi bi-search me-1"></i>Lacak Pesanan
      </a>

      <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <div class="dropdown">
          <button class="bell border-0 bg-primary text-white" type="button"
                  data-bs-toggle="dropdown" aria-expanded="false"
                  title="Akun Admin (<?= e($_SESSION['nama'] ?? 'Admin') ?>)">
            <i class="bi bi-person-check-fill"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li>
              <span class="dropdown-item-text small text-muted">
                Masuk sebagai <strong><?= e($_SESSION['nama'] ?? 'Admin') ?></strong>
              </span>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item" href="admin/index.php">
                <i class="bi bi-grid-1x2 me-2"></i>Dashboard Admin
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="login.php">
                <i class="bi bi-arrow-repeat me-2"></i>Ganti Akun / Login Manual
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item text-danger" href="logout.php"
                 onclick="return confirm('Keluar dari sesi admin sekarang?')">
                <i class="bi bi-box-arrow-left me-2"></i>Keluar (Logout)
              </a>
            </li>
          </ul>
        </div>
      <?php else: ?>
        <a class="bell" href="login.php" title="Login Admin">
          <i class="bi bi-person-lock"></i>
        </a>
      <?php endif ?>
    </div>

  </div>
</header>
<?php
}

// ------------------------------------------------------------
// Penutup halaman
// ------------------------------------------------------------
function foot() {
    global $MODE, $ROLE;

    if ($MODE === 'app' && $ROLE === 'admin') {
        echo '</main></div>';
    } elseif ($MODE === 'page') {
        echo '</main>';
    }
    ?>
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="tc"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($MODE === 'app'): ?>
  <script>
    window.APP = { role: 'admin' };
  </script>
<?php endif ?>

<script src="<?= $ROLE ? '../' : '' ?>assets/app.js?v=<?= filemtime(__DIR__ . '/../assets/app.js') ?>"></script>
</body>
</html>
<?php
}
