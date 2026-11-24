<?php
// Unduh laporan sebagai Excel
// export.php?jenis=transaksi|pelanggan|feedback&dari=...&sampai=...
require '../inc.php';
guard('admin');

[$jenis, $dari, $sampai] = param_laporan();
[$judul, $kolom, $baris] = data_laporan($jenis, $dari, $sampai);

// Baris pertama = judul kolom
$data = [array_column($kolom, 0)];

// Kolom bertipe rp / angka ditulis sebagai angka agar bisa dijumlahkan di Excel
foreach ($baris as $b) {
    $sel = [];

    foreach ($b as $i => $nilai) {
        $tipe = $kolom[$i][1];

        if ($tipe == 'desimal') {
            $sel[] = (float)$nilai;
        } elseif (in_array($tipe, ['rp', 'angka'])) {
            $sel[] = (int)$nilai;
        } else {
            $sel[] = (string)$nilai;
        }
    }

    $data[] = $sel;
}

excel_kirim("laporan-$jenis-$dari-sd-$sampai", $data);
