<?php
require '../inc.php';
guard('admin');

// ---------- 1. Angka ringkasan ----------
$bulanIni = q("SELECT COALESCE(SUM(total), 0) FROM transaksi
               WHERE MONTH(tgl_masuk) = MONTH(CURDATE())
                 AND YEAR(tgl_masuk)  = YEAR(CURDATE())")->fetchColumn();

$ratingRata = (float)q("SELECT AVG((r_kebersihan + r_waktu + r_pelayanan + r_harga) / 4)
                        FROM review")->fetchColumn();

// Setiap kartu: [judul, nilai, ikon, warna]
$kartu = [
    ['Pelanggan',
        q("SELECT COUNT(*) FROM pelanggan")->fetchColumn(),
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

    ['Review belum dibalas',
        q("SELECT COUNT(*) FROM review WHERE balasan IS NULL OR balasan = ''")->fetchColumn(),
        'bi-chat-heart', '#ef4444'],

    ['Total transaksi',
        q("SELECT COUNT(*) FROM transaksi")->fetchColumn(),
        'bi-bar-chart', '#6b7c93'],
];

// ---------- 2. Lima transaksi terakhir ----------
$terbaru = q("SELECT t.*, p.nama pelanggan, " . RINGKAS . " barang
              FROM transaksi t
              JOIN pelanggan p ON p.id = t.pelanggan_id
              ORDER BY t.id DESC
              LIMIT 5")->fetchAll();

// ---------- 3. Lima pelanggan dengan transaksi terbanyak ----------
$pelangganTop = q("SELECT p.nama, p.no_hp,
                          COUNT(t.id) jumlah,
                          COALESCE(SUM(t.total), 0) belanja
                   FROM pelanggan p
                   JOIN transaksi t ON t.pelanggan_id = p.id
                   GROUP BY p.id
                   ORDER BY jumlah DESC, belanja DESC
                   LIMIT 5")->fetchAll();

// ---------- 4. Grafik pendapatan 1 bulan terakhir (30 hari) ----------
$perHari = q("SELECT DATE(tgl_masuk) tanggal, SUM(total) jumlah
              FROM transaksi
              WHERE tgl_masuk >= CURDATE() - INTERVAL 29 DAY
              GROUP BY tanggal")->fetchAll(PDO::FETCH_KEY_PAIR);

$labelBulan = [];
$nilaiBulan = [];

for ($i = 29; $i >= 0; $i--) {
    $tanggal      = date('Y-m-d', strtotime("-$i day"));
    $labelBulan[] = date('d/m', strtotime($tanggal));
    $nilaiBulan[] = (int)($perHari[$tanggal] ?? 0);
}

// ---------- 5. Grafik komposisi status ----------
$perStatus   = q("SELECT status, COUNT(*) FROM transaksi GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$nilaiStatus = [];

foreach (STATUS as $s) {
    $nilaiStatus[] = (int)($perStatus[$s] ?? 0);
}

head('Dashboard', 'admin');
?>

<!-- Seluruh dashboard diperbarui otomatis (real-time) saat data berubah -->
<div id="live">

  <!-- ============ KARTU RINGKASAN ============ -->
  <div class="row g-3 mb-4">
    <?php foreach ($kartu as [$judul, $nilai, $ikon, $warna]): ?>
      <div class="col-6 col-xl-3">
        <div class="card p-3 kartu-stat">
          <div class="d-flex align-items-center gap-3">
            <div class="ico" style="background:<?= $warna ?>1f; color:<?= $warna ?>">
              <i class="bi <?= $ikon ?>"></i>
            </div>
            <div style="min-width:0">
              <div class="judul"><?= $judul ?></div>
              <div class="stat"><?= $nilai ?></div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach ?>
  </div>

  <!-- ============ GRAFIK (dua kartu berukuran sama) ============ -->
  <div class="row g-3 mb-4">

    <div class="col-lg-6">
      <div class="card p-3 h-100">
        <h6>Pendapatan 1 bulan terakhir</h6>

        <div style="position:relative; height:300px">
          <canvas id="grafikPendapatan"
                  data-label="<?= e(json_encode($labelBulan)) ?>"
                  data-nilai="<?= e(json_encode($nilaiBulan)) ?>"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card p-3 h-100">
        <h6>Komposisi status</h6>

        <div style="position:relative; height:300px">
          <canvas id="grafikStatus"
                  data-label="<?= e(json_encode(STATUS)) ?>"
                  data-nilai="<?= e(json_encode($nilaiStatus)) ?>"></canvas>
        </div>
      </div>
    </div>

  </div>

  <!-- ============ TABEL ============ -->
  <div class="row g-3">

    <!-- 5 transaksi terakhir -->
    <div class="col-xxl-7">
      <div class="card table-responsive h-100">
        <div class="p-3 d-flex justify-content-between align-items-center">
          <h5 class="m-0">5 transaksi terakhir</h5>
          <a href="transaksi.php" class="btn btn-sm btn-primary">Kelola</a>
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

    <!-- 5 pelanggan dengan transaksi terbanyak -->
    <div class="col-xxl-5">
      <div class="card table-responsive h-100">
        <div class="p-3 d-flex justify-content-between align-items-center">
          <h5 class="m-0">5 pelanggan paling banyak transaksi</h5>
          <a href="pelanggan.php" class="btn btn-sm btn-primary">Kelola</a>
        </div>

        <table class="table rtable mb-0">
          <thead>
            <tr>
              <th>No</th>
              <th>Nama</th>
              <th>Transaksi</th>
              <th>Total belanja</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pelangganTop as $no => $p): ?>
              <tr>
                <td><?= $no + 1 ?></td>
                <td>
                  <?= e($p['nama']) ?>
                  <div class="small text-muted"><?= e($p['no_hp']) ?></div>
                </td>
                <td><?= $p['jumlah'] ?>x</td>
                <td><?= rp($p['belanja']) ?></td>
              </tr>
            <?php endforeach ?>

            <?php if (!$pelangganTop): ?>
              <tr><td colspan="4" class="text-muted">Belum ada data transaksi.</td></tr>
            <?php endif ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
  // Menggambar kedua grafik dari data di atribut canvas.
  // Dipanggil saat halaman dibuka dan setiap kali bagian #live diperbarui otomatis.
  function gambarGrafik() {
    const batang = document.getElementById('grafikPendapatan');
    const donat  = document.getElementById('grafikStatus');

    // Grafik batang: pendapatan per hari selama 30 hari terakhir
    new Chart(batang, {
      type: 'bar',
      data: {
        labels: JSON.parse(batang.dataset.label),
        datasets: [{
          data: JSON.parse(batang.dataset.nilai),
          backgroundColor: '#1363df',
          borderRadius: 4
        }]
      },
      options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } },
          y: {
            beginAtZero: true,
            ticks: { callback: nilai => 'Rp ' + nilai.toLocaleString('id-ID') }
          }
        }
      }
    });

    // Grafik donat: jumlah transaksi per status
    new Chart(donat, {
      type: 'doughnut',
      data: {
        labels: JSON.parse(donat.dataset.label),
        datasets: [{
          data: JSON.parse(donat.dataset.nilai),
          backgroundColor: ['#94a3b8', '#1363df', '#22c55e', '#0b2545']
        }]
      },
      options: {
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
      }
    });
  }

  // Dipanggil sebelum bagian #live diganti, agar grafik lama dibersihkan
  function bersihkanGrafik() {
    ['grafikPendapatan', 'grafikStatus'].forEach(id => {
      const grafik = Chart.getChart(id);
      if (grafik) grafik.destroy();
    });
  }

  gambarGrafik();
</script>
<?php foot();
