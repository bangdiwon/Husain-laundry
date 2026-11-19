// ============================================================
// Skrip tampilan E-CRM Husain Laundry
// 1. Notifikasi pop-up   2. Tabel responsif   3. Update real-time
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

// Keamanan: setiap form POST otomatis membawa token CSRF
document.addEventListener('submit', e => {
  const form = e.target, meta = document.querySelector('meta[name=csrf]');
  if (meta && form.method === 'post' && !form.querySelector('[name=csrf]')) {
    const input = document.createElement('input');
    input.type = 'hidden'; input.name = 'csrf'; input.value = meta.content;
    form.appendChild(input);
  }
}, true);

// 2. Isi label tiap sel dari judul kolom, dipakai saat tabel jadi kartu di HP
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
  if (side && !e.target.closest('#side, .topbar button')) side.classList.remove('open');
});

// 3. Real-time: tanya server tiap 8 detik (lewat api.php)
if (window.APP) {
  let tandaTerakhir = null;                                   // penanda perubahan data
  let notifTerlihat = localStorage.getItem('seen' + APP.uid); // id notifikasi terakhir yang sudah dilihat
  notifTerlihat = notifTerlihat === null ? null : +notifTerlihat;
  let feedbackBaru = null;

  function updatePelanggan(d) {
    document.querySelectorAll('.nbadge').forEach(l => { l.textContent = d.unread; l.hidden = !d.unread; });
    (d.items || []).slice().reverse().forEach(n => {
      if (notifTerlihat !== null && n.id > notifTerlihat) toast(n.pesan);
    });
    if (notifTerlihat === null || d.maxid > notifTerlihat) {
      notifTerlihat = d.maxid;
      localStorage.setItem('seen' + APP.uid, notifTerlihat);
    }
  }

  function updateAdmin(d) {
    const lencana = document.getElementById('fbadge');
    if (lencana) { lencana.textContent = d.baru; lencana.hidden = !d.baru; }
    if (feedbackBaru !== null && d.baru > feedbackBaru) toast('Ada masukan baru dari pelanggan.');
    feedbackBaru = d.baru;
  }

  async function segarkanBagianLive() {
    const html = await (await fetch(location.href)).text();
    const baru = new DOMParser().parseFromString(html, 'text/html').getElementById('live');
    if (baru) {
      document.getElementById('live').innerHTML = baru.innerHTML;
      labelTabel();
    }
  }

  async function poll() {
    try {
      const r = await fetch('../api.php', { cache: 'no-store' });
      if (!r.ok) return;
      const d = await r.json();
      if (d.login === false) { location.href = '../login.php'; return; }   // sesi habis
      APP.role === 'customer' ? updatePelanggan(d) : updateAdmin(d);
      const adaPerubahan = tandaTerakhir !== null && d.sig !== tandaTerakhir;
      if (document.getElementById('live') && adaPerubahan) {
        await segarkanBagianLive();
      }
      tandaTerakhir = d.sig;
      const jam = document.getElementById('lu');
      if (jam) jam.textContent = new Date().toLocaleTimeString('id-ID');
    } catch (e) { /* jaringan putus: coba lagi pada putaran berikutnya */ }
  }
  poll();
  setInterval(poll, 8000);
}
