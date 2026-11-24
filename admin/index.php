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

// ---------- 3. Lima pelanggan terbaru ----------
$pelangganBaru = q("SELECT p.*,
                           (SELECT COUNT(*) FROM transaksi WHERE pelanggan_id = p.id) jumlah
                    FROM pelanggan p
                    ORDER BY p.id DESC
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

// ---------- 5. Grafik pemesanan per pelanggan (5 pelanggan paling sering memesan) ----------
$topPelanggan = q("SELECT p.nama, COUNT(t.id) jumlah
                   FROM pelanggan p
                   JOIN transaksi t ON t.pelanggan_id = p.id
                   GROUP BY p.id
                   ORDER BY jumlah DESC, p.nama
                   LIMIT 5")->fetchAll();

$labelPelanggan = array_column($topPelanggan, 'nama');
$nilaiPelanggan = array_map('intval', array_column($topPelanggan, 'jumlah'));

// ---------- 6. Grafik komposisi status ----------
$perStatus   = q("SELECT status, COUNT(*) FROM transaksi GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$nilaiStatus = [];

foreach (STATUS as $s) {
    $nilaiStatus[] = (int)($perStatus[$s] ?? 0);
}

head('Dashboard', 'admin');
?>

<!-- ============ BAGIAN YANG DIPERBARUI OTOMATIS (real-time) ============ -->
<div id="live">

  <!-- Kartu ringkasan -->
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

  <div class="row g-3 mb-4">

    <!-- 5 transaksi terakhir -->
    <div class="col-xl-7">
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

    <!-- 5 pelanggan terbaru -->
    <div class="col-xl-5">
      <div class="card table-responsive h-100">
        <div class="p-3 d-flex justify-content-between align-items-center">
          <h5 class="m-0">5 pelanggan terbaru</h5>
          <a href="pelanggan.php" class="btn btn-sm btn-primary">Kelola</a>
        </div>

        <table class="table rtable mb-0">
          <thead>
            <tr>
              <th>Nama</th>
              <th>WhatsApp</th>
              <th>Pesanan</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pelangganBaru as $p): ?>
              <tr>
                <td><?= e($p['nama']) ?></td>
                <td><?= e($p['no_hp']) ?></td>
                <td><?= $p['jumlah'] ?>x</td>
              </tr>
            <?php endforeach ?>

            <?php if (!$pelangganBaru): ?>
              <tr><td colspan="3" class="text-muted">Belum ada pelanggan.</td></tr>
            <?php endif ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- ============ GRAFIK ============ -->
<div class="row g-3 mb-4">

  <div class="col-lg-8">
    <div class="card p-3">
      <h6>Pendapatan 1 bulan terakhir</h6>
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

<div class="row g-3">
  <div class="col-12">
    <div class="card p-3">
      <h6>Pemesanan berdasarkan pelanggan (5 pelanggan paling sering memesan)</h6>

      <?php if ($topPelanggan): ?>
        <canvas id="grafikPelanggan" height="70"></canvas>
      <?php else: ?>
        <div class="text-muted">Belum ada data pemesanan.</div>
      <?php endif ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
  // Grafik batang: pendapatan per hari selama 30 hari terakhir
  new Chart(document.getElementById('grafikPendapatan'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($labelBulan) ?>,
      datasets: [{
        data: <?= json_encode($nilaiBulan) ?>,
        backgroundColor: '#1363df',
        borderRadius: 4
      }]
    },
    options: {
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

  // Grafik batang mendatar: berapa kali tiap pelanggan memesan
  const grafikPelanggan = document.getElementById('grafikPelanggan');

  if (grafikPelanggan) {
    new Chart(grafikPelanggan, {
      type: 'bar',
      data: {
        labels: <?= json_encode($labelPelanggan) ?>,
        datasets: [{
          label: 'Jumlah pesanan',
          data: <?= json_encode($nilaiPelanggan) ?>,
          backgroundColor: '#0f9d9a',
          borderRadius: 6
        }]
      },
      options: {
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: {
          x: { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: 'Kali memesan' } }
        }
      }
    });
  }
</script>
<?php foot();
