<?php
// ============================================================
// DATA LAPORAN: dipakai halaman laporan (tampil / cetak / PDF) dan export Excel
// Hasil: [judul, kolom, baris]
// Kolom = [label, tipe]  -> tipe: teks | angka | desimal | rp
// ============================================================
function data_laporan($jenis, $dari, $sampai) {
    $param = [$dari, $sampai];

    // ---------- Laporan pelanggan ----------
    if ($jenis == 'pelanggan') {
        $kolom = [
            ['Nama',             'teks'],
            ['No. WhatsApp',     'teks'],
            ['Alamat',           'teks'],
            ['Jumlah Transaksi', 'angka'],
            ['Total Belanja',    'rp'],
        ];

        $baris = q("SELECT p.nama, COALESCE(p.no_hp, '-'), COALESCE(NULLIF(p.alamat, ''), '-'),
                           COUNT(t.id),
                           COALESCE(SUM(t.total), 0)
                    FROM pelanggan p
                    LEFT JOIN transaksi t
                           ON t.pelanggan_id = p.id
                          AND DATE(t.tgl_masuk) BETWEEN ? AND ?
                    GROUP BY p.id
                    ORDER BY 5 DESC", $param)->fetchAll(PDO::FETCH_NUM);

        return ['Laporan Pelanggan', $kolom, $baris];
    }

    // ---------- Laporan umpan balik (review) ----------
    if ($jenis == 'feedback') {
        $kolom = [
            ['Tanggal',        'teks'],
            ['Pelanggan',      'teks'],
            ['Kode Pesanan',   'teks'],
            ['Kebersihan',     'angka'],
            ['Tepat Waktu',    'angka'],
            ['Pelayanan',      'angka'],
            ['Harga',          'angka'],
            ['Rata-rata',      'desimal'],
            ['Rekomendasi',    'teks'],
            ['Saran / Keluhan', 'teks'],
            ['Kode Promo',     'teks'],
        ];

        $baris = q("SELECT DATE_FORMAT(r.created_at, '%d/%m/%Y %H:%i'),
                           p.nama, t.kode,
                           r.r_kebersihan, r.r_waktu, r.r_pelayanan, r.r_harga,
                           ROUND((r.r_kebersihan + r.r_waktu + r.r_pelayanan + r.r_harga) / 4, 1),
                           r.rekomendasi,
                           COALESCE(NULLIF(r.komentar, ''), '-'),
                           COALESCE(pr.kode, '-')
                    FROM review r
                    JOIN pelanggan p  ON p.id = r.pelanggan_id
                    JOIN transaksi t  ON t.id = r.transaksi_id
                    LEFT JOIN promo pr ON pr.id = r.promo_id
                    WHERE DATE(r.created_at) BETWEEN ? AND ?
                    ORDER BY r.id DESC", $param)->fetchAll(PDO::FETCH_NUM);

        return ['Laporan Review Pelanggan', $kolom, $baris];
    }

    // ---------- Laporan transaksi (bawaan) ----------
    $kolom = [
        ['Tanggal',          'teks'],
        ['Kode Pesanan',     'teks'],
        ['Pelanggan',        'teks'],
        ['Barang / Layanan', 'teks'],
        ['Total',            'rp'],
        ['Status',           'teks'],
    ];

    $baris = q("SELECT DATE_FORMAT(t.tgl_masuk, '%d/%m/%Y %H:%i'),
                       t.kode, p.nama, " . RINGKAS . ", t.total, t.status
                FROM transaksi t
                JOIN pelanggan p ON p.id = t.pelanggan_id
                WHERE DATE(t.tgl_masuk) BETWEEN ? AND ?
                ORDER BY t.tgl_masuk", $param)->fetchAll(PDO::FETCH_NUM);

    return ['Laporan Penjualan', $kolom, $baris];
}

// Validasi parameter laporan dari URL: [jenis, tanggal awal, tanggal akhir]
function param_laporan() {
    $jenisBoleh = ['transaksi', 'pelanggan', 'feedback'];
    $jenis      = $_GET['jenis'] ?? '';

    if (!in_array($jenis, $jenisBoleh)) {
        $jenis = 'transaksi';
    }

    $dari   = $_GET['dari']   ?? '';
    $sampai = $_GET['sampai'] ?? '';

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)) {
        $dari = date('Y-m-01');
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai)) {
        $sampai = date('Y-m-d');
    }

    return [$jenis, $dari, $sampai];
}

// ============================================================
// EXPORT EXCEL (.xlsx dibuat langsung, tanpa library tambahan)
// $data = array baris; baris pertama = judul kolom (dicetak tebal)
// ============================================================

// Nomor kolom (0,1,2...) menjadi huruf Excel (A,B,C... AA)
function huruf_kolom($nomor) {
    $huruf = '';

    for ($n = $nomor + 1; $n > 0; $n = intdiv($n - 1, 26)) {
        $huruf = chr(65 + ($n - 1) % 26) . $huruf;
    }

    return $huruf;
}

// Ubah array data menjadi isi <sheetData> (baris dan sel XML)
function xlsx_baris($data) {
    $xml = '';

    foreach ($data as $r => $baris) {
        $xml .= '<row r="' . ($r + 1) . '">';

        foreach ($baris as $c => $nilai) {
            $ref   = huruf_kolom($c) . ($r + 1);
            $gaya  = $r == 0 ? ' s="1"' : '';        // baris pertama = tebal

            if (is_int($nilai) || is_float($nilai)) {
                $xml .= "<c r=\"$ref\"$gaya><v>$nilai</v></c>";
                continue;
            }

            $teks = htmlspecialchars((string)$nilai, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $xml .= "<c r=\"$ref\"$gaya t=\"inlineStr\">"
                  . "<is><t xml:space=\"preserve\">$teks</t></is></c>";
        }

        $xml .= '</row>';
    }

    return $xml;
}

// Kumpulan file XML yang membentuk satu berkas .xlsx
function xlsx_berkas($jumlahKolom, $isiSheet) {
    $kepala = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $ns     = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    $rel    = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    $paket  = 'http://schemas.openxmlformats.org/package/2006/relationships';
    $ofis   = 'application/vnd.openxmlformats-officedocument.spreadsheetml';

    return [

        '[Content_Types].xml' => $kepala
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="' . $ofis . '.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="' . $ofis . '.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="' . $ofis . '.styles+xml"/>'
            . '</Types>',

        '_rels/.rels' => $kepala
            . '<Relationships xmlns="' . $paket . '">'
            . '<Relationship Id="rId1" Type="' . $rel . '/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>',

        'xl/workbook.xml' => $kepala
            . '<workbook xmlns="' . $ns . '" xmlns:r="' . $rel . '">'
            . '<sheets><sheet name="Laporan" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>',

        'xl/_rels/workbook.xml.rels' => $kepala
            . '<Relationships xmlns="' . $paket . '">'
            . '<Relationship Id="rId1" Type="' . $rel . '/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="' . $rel . '/styles" Target="styles.xml"/>'
            . '</Relationships>',

        // Gaya: 0 = biasa, 1 = tebal (untuk judul kolom)
        'xl/styles.xml' => $kepala
            . '<styleSheet xmlns="' . $ns . '">'
            . '<fonts count="2">'
            .   '<font><sz val="11"/><name val="Calibri"/></font>'
            .   '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="2">'
            .   '<fill><patternFill patternType="none"/></fill>'
            .   '<fill><patternFill patternType="gray125"/></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            .   '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .   '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '</cellXfs>'
            . '</styleSheet>',

        'xl/worksheets/sheet1.xml' => $kepala
            . '<worksheet xmlns="' . $ns . '">'
            . '<cols><col min="1" max="' . $jumlahKolom . '" width="24" customWidth="1"/></cols>'
            . '<sheetData>' . $isiSheet . '</sheetData>'
            . '</worksheet>',
    ];
}

// Kirim file Excel ke browser
function excel_kirim($namaFile, $data) {
    if (!class_exists('ZipArchive')) {
        return excel_kirim_html($namaFile, $data);
    }

    $berkas = xlsx_berkas(count($data[0]), xlsx_baris($data));

    $tmp = tempnam(sys_get_temp_dir(), 'xl');
    unlink($tmp);

    $zip = new ZipArchive();
    $zip->open($tmp, ZipArchive::CREATE);

    foreach ($berkas as $nama => $isi) {
        $zip->addFromString($nama, $isi);
    }

    $zip->close();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $namaFile . '.xlsx"');
    header('Content-Length: ' . filesize($tmp));

    readfile($tmp);
    unlink($tmp);
    exit;
}

// Cadangan bila ekstensi zip PHP belum aktif: tabel HTML yang tetap bisa dibuka Excel
function excel_kirim_html($namaFile, $data) {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $namaFile . '.xls"');

    echo '<meta charset="utf-8"><table border="1">';

    foreach ($data as $i => $baris) {
        echo '<tr>';

        foreach ($baris as $nilai) {
            echo $i ? '<td>' . e($nilai) . '</td>' : '<th>' . e($nilai) . '</th>';
        }

        echo '</tr>';
    }

    echo '</table>';
    exit;
}
