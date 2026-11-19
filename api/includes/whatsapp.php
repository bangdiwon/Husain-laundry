<?php
// ============================================================
// NOTIFIKASI WHATSAPP (lewat layanan Fonnte)
// ============================================================

// Ubah 0812xxx / 812xxx menjadi format internasional 62812xxx
function wa_no($nomor) {
    $nomor = preg_replace('/\D/', '', (string)$nomor);
    if ($nomor === '')      return '';
    if ($nomor[0] === '0')  return '62' . substr($nomor, 1);
    if ($nomor[0] === '8')  return '62' . $nomor;
    return $nomor;
}

// Kirim pesan ke WhatsApp pelanggan. Hasil: true jika berhasil.
function wa_kirim($userId, $pesan) {
    if (WA_TOKEN === '' || !function_exists('curl_init')) return false;

    $user  = q("SELECT nama, no_hp FROM users WHERE id=?", [$userId])->fetch();
    $nomor = wa_no($user['no_hp'] ?? '');
    if (!$nomor) return false;

    $curl = curl_init('https://api.fonnte.com/send');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => [
            'target'      => $nomor,
            'message'     => "Halo {$user['nama']},\n$pesan\n\n_Husain Laundry_",
            'countryCode' => '62',
        ],
        CURLOPT_HTTPHEADER     => ['Authorization: ' . WA_TOKEN],
    ]);
    $hasil = json_decode((string)curl_exec($curl), true);
    curl_close($curl);
    return !empty($hasil['status']);
}
