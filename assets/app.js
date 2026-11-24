// ============================================================
// Skrip tampilan E-CRM Husain Laundry
// 1. Notifikasi pop-up        2. Token keamanan form (admin)
// 3. Tabel responsif          4. Update real-time admin
// 5. Update real-time halaman lacak pesanan (pelanggan)
// ============================================================

// 1. Pop-up notifikasi di pojok kanan bawah
function toast(pesan) {
  const kotak = document.createElement('div');
  kotak.className = 'toast show border-0 shadow';
  kotak.innerHTML = '<div class="toast-body d-flex gap-2">'
                  + '<i class="bi bi-bell-fill text-warning"></i><span></span>'
                  + '</div>';
  kotak.querySelector('span').textContent = pesan;

  document.getElementById('tc').appendChild(kotak);
  setTimeout(() => kotak.remove(), 9000);
}

// 2. Keamanan: setiap form POST admin otomatis membawa token CSRF
document.addEventListener('submit', e => {
  const form = e.target;
  const meta = document.querySelector('meta[name=csrf]');

  if (meta && form.method === 'post' && !form.querySelector('[name=csrf]')) {
    const input = document.createElement('input');
    input.type  = 'hidden';
    input.name  = 'csrf';
    input.value = meta.content;
    form.appendChild(input);
  }
}, true);

// 3. Isi label tiap sel dari judul kolom, dipakai saat tabel jadi kartu di HP
function labelTabel() {
  document.querySelectorAll('table.rtable').forEach(tabel => {
    const judul = [...tabel.querySelectorAll('thead th')].map(th => th.textContent.trim());

    tabel.querySelectorAll('tbody tr').forEach(baris => {
      [...baris.children].forEach((sel, i) => { sel.dataset.label = judul[i] || ''; });
    });
  });
}
labelTabel();

// Tutup sidebar admin saat layar disentuh di luar menu (HP)
document.addEventListener('click', e => {
  const side = document.getElementById('side');

  if (side && !e.target.closest('#side, .topbar button')) {
    side.classList.remove('open');
  }
});

// Ambil ulang sebuah halaman lalu kembalikan elemen dengan id tertentu
async function ambilBagian(id) {
  const html = await (await fetch(location.href)).text();
  return new DOMParser().parseFromString(html, 'text/html').getElementById(id);
}

// 4. Real-time ADMIN: tanya server tiap 8 detik (lewat api.php)
if (window.APP) {
  let tandaTerakhir = null;     // penanda perubahan data
  let reviewBaru    = null;     // jumlah review baru pada pengecekan sebelumnya

  async function poll() {
    try {
      const r = await fetch('../api.php', { cache: 'no-store' });
      if (!r.ok) return;

      const d = await r.json();

      // Sesi habis: kembali ke halaman login
      if (d.login === false) {
        location.href = '../login.php';
        return;
      }

      // Lencana jumlah review baru + pop-up bila bertambah
      const lencana = document.getElementById('fbadge');
      if (lencana) {
        lencana.textContent = d.baru;
        lencana.hidden = !d.baru;
      }
      if (reviewBaru !== null && d.baru > reviewBaru) {
        toast('Ada review baru dari pelanggan.');
      }
      reviewBaru = d.baru;

      // Segarkan bagian #live bila data berubah
      const adaPerubahan = tandaTerakhir !== null && d.sig !== tandaTerakhir;

      if (document.getElementById('live') && adaPerubahan) {
        const baru = await ambilBagian('live');

        if (baru) {
          document.getElementById('live').innerHTML = baru.innerHTML;
          labelTabel();
        }
      }
      tandaTerakhir = d.sig;

      const jam = document.getElementById('lu');
      if (jam) jam.textContent = new Date().toLocaleTimeString('id-ID');

    } catch (e) { /* jaringan putus: coba lagi pada putaran berikutnya */ }
  }

  poll();
  setInterval(poll, 8000);
}

// 5. Real-time PELANGGAN: halaman lacak memeriksa status pesanan tiap 8 detik
const hasilLacak = document.getElementById('hasil-lacak');

if (hasilLacak) {
  setInterval(async () => {
    try {
      const url = 'lacak_api.php?kode=' + encodeURIComponent(hasilLacak.dataset.kode);
      const d   = await (await fetch(url, { cache: 'no-store' })).json();

      // Jangan menimpa tampilan saat pelanggan sedang mengisi form review
      const sedangIsiReview = document.querySelector('.modal.show');
      const berubah         = d.sig && d.sig !== hasilLacak.dataset.sig;

      if (berubah && !sedangIsiReview) {
        const baru = await ambilBagian('hasil-lacak');

        if (baru) {
          hasilLacak.innerHTML = baru.innerHTML;
          hasilLacak.dataset.sig = d.sig;
          toast('Status pesanan Anda diperbarui: ' + d.status);
        }
      }
    } catch (e) { /* abaikan, coba lagi nanti */ }
  }, 8000);
}

// 6. Tombol "Isi review": buka pop-up review (tetap berfungsi setelah halaman diperbarui otomatis)
document.addEventListener('click', e => {
  if (!e.target.closest('[data-buka-review]')) return;

  const kotak = document.getElementById('modalReview');

  if (kotak && window.bootstrap) {
    bootstrap.Modal.getOrCreateInstance(kotak).show();
  }
});
