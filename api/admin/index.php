<?php
require '../inc.php';
guard('admin');

// ---------- 1. Angka ringkasan ----------
$bulanIni = q("SELECT COALESCE(SUM(total), 0) FROM transaksi
               WHERE MONTH(tgl_masuk) = MONTH(CURDATE())
                 AND YEAR(tgl_masuk)  = YEAR(CURDATE())")->fetchColumn();

$ratingRata = (float)q("SELECT AVG(rating) FROM feedback")->fetchColumn();

// Setiap kartu: [judul, nilai, ikon, warna]
$kartu = [
    ['Pelanggan',
        q("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn(),
        'bi-people', '#1363df'],

    ['Transaksi hari ini',
        q("SELECT COUNT(*) FROM transaksi WHERE DATE(tgl_masuk) = CURDATE()")->fetchColumn(),
        'bi-receipt', '#18b5d4'],

    ['Cucian diproses',
        q("SELECT COUNT(*) FROM transaksi WHERE status IN ('Antri','Proses')")->fetchColumn(),
        'bi-hourglass-split', '#f59e0b'],

    ['Siap diambil',
        q("SELECT COUNT(*) FROM transaksi WHERE status='Selesai'")->fetchColumn(),
        'bi-check2-circle', '#22c55e'],

    ['Pendapatan bulan ini',
        rp($bulanIni),
        'bi-cash-coin', '#1363df'],

    ['Rating rata-rata',
        number_format($ratingRata, 1) . ' / 5',
        'bi-star-fill', '#f59e0b'],

    ['Feedback baru',
        q("SELECT COUNT(*) FROM feedback WHERE status='Baru'")->fetchColumn(),
        'bi-chat-heart', '#ef4444'],

    ['Total transaksi',
        q("SELECT COUNT(*) FROM transaksi")->fetchColumn(),
        'bi-bar-chart', '#6b7c93'],
];

// ---------- 2. Transaksi terbaru ----------
$terbaru = q("SELECT t.*, u.nama pelanggan, " . RINGKAS . " barang
              FROM transaksi t
              JOIN users u ON u.id = t.user_id
              ORDER BY t.id DESC
              LIMIT 8")->fetchAll();

// ---------- 3. Data grafik pendapatan 7 hari ----------
$perHari = q("SELECT DATE(tgl_masuk) tanggal, SUM(total) jumlah
              FROM transaksi
              WHERE tgl_masuk >= CURDATE() - INTERVAL 6 DAY
              GROUP BY tanggal")->fetchAll(PDO::FETCH_KEY_PAIR);

$labelGrafik = [];
$nilaiGrafik = [];

for ($i = 6; $i >= 0; $i--) {
    $tanggal       = date('Y-m-d', strtotime("-$i day"));
    $labelGrafik[] = date('d M', strtotime($tanggal));
    $nilaiGrafik[] = (int)($perHari[$tanggal] ?? 0);
}

// ---------- 4. Data grafik komposisi status ----------
$perStatus   = q("SELECT status, COUNT(*) FROM transaksi GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$nilaiStatus = [];

foreach (STATUS as $s) {
    $nilaiStatus[] = (int)($perStatus[$s] ?? 0);
}

head('Dashboard', 'admin');
?>

<!-- Bagian yang diperbarui otomatis (real-time) -->
<div id="live">

  <div class="row g-3 mb-4">
    <?php foreach ($kartu as [$judul, $nilai, $ikon, $warna]): ?>
      <div class="col-6 col-xl-3">
        <div class="card p-3">
          <div class="d-flex align-items-center gap-3">
            <div class="ico" style="background:<?= $warna ?>1f; color:<?= $warna ?>">
              <i class="bi <?= $ikon ?>"></i>
            </div>
            <div>
              <div class="text-muted small"><?= $judul ?></div>
              <div class="stat fs-4"><?= $nilai ?></div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach ?>
  </div>

  <div class="card table-responsive mb-4">
    <div class="p-3 d-flex justify-content-between align-items-center">
      <h5 class="m-0">Transaksi terbaru</h5>
      <a href="transaksi.php" class="btn btn-sm btn-primary">Kelola transaksi</a>
    </div>

    <table class="table rtable mb-0">
      <thead>
        <tr>
          <th>Kode</th>
          <th>Pelanggan</th>
          <th>Barang</th>
          <th>Total</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($terbaru as $t): ?>
          <tr>
            <td><?= e($t['kode']) ?></td>
            <td><?= e($t['pelanggan']) ?></td>
            <td><?= e($t['barang']) ?></td>
            <td><?= rp($t['total']) ?></td>
            <td><?= badge($t['status']) ?></td>
          </tr>
        <?php endforeach ?>

        <?php if (!$terbaru): ?>
          <tr><td colspan="5" class="text-muted">Belum ada transaksi.</td></tr>
        <?php endif ?>
      </tbody>
    </table>
  </div>

</div>

<!-- Grafik -->
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card p-3">
      <h6>Pendapatan 7 hari terakhir</h6>
      <canvas id="grafikPendapatan" height="110"></canvas>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card p-3">
      <h6>Komposisi status</h6>
      <canvas id="grafikStatus"></canvas>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
  // Grafik batang: pendapatan per hari
  new Chart(document.getElementById('grafikPendapatan'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($labelGrafik) ?>,
      datasets: [{
        data: <?= json_encode($nilaiGrafik) ?>,
        backgroundColor: '#1363df',
        borderRadius: 6
      }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { callback: nilai => 'Rp ' + nilai.toLocaleString('id-ID') }
        }
      }
    }
  });

  // Grafik donat: jumlah transaksi per status
  new Chart(document.getElementById('grafikStatus'), {
    type: 'doughnut',
    data: {
      labels: <?= json_encode(STATUS) ?>,
      datasets: [{
        data: <?= json_encode($nilaiStatus) ?>,
        backgroundColor: ['#94a3b8', '#1363df', '#22c55e', '#0b2545']
      }]
    },
    options: {
      plugins: { legend: { position: 'bottom' } }
    }
  });
</script>
<?php foot();
