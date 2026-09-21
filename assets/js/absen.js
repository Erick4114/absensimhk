(() => {
  const C = window.ABSEN;
  const $ = (id) => document.getElementById(id);
  const R_PX = 80;   // jari-jari cincin area kantor pada radar (px)
  const MAX_PX = 100; // batas terluar titik pengguna

  let pos = null, stream = null, foto = null, snap = null, sending = false;

  /* ---------- Jam (mengikuti waktu server, zona WITA) ---------- */
  const offset = C.serverNow - Date.now();
  const fmt = new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Makassar', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
  const fmtFull = new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Makassar', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
  function tick() {
    const parts = Object.fromEntries(fmt.formatToParts(new Date(Date.now() + offset)).map((p) => [p.type, p.value]));
    $('clock').innerHTML = `${parts.hour}:${parts.minute}<span class="ml-1 text-2xl font-bold text-river-200">${parts.second}</span>`;
  }
  tick(); setInterval(tick, 1000);

  /* ---------- Lokasi ---------- */
  const rad = (d) => (d * Math.PI) / 180;
  function haversine(a, b, c, d) {
    const R = 6371000, dLat = rad(c - a), dLng = rad(d - b);
    const h = Math.sin(dLat / 2) ** 2 + Math.cos(rad(a)) * Math.cos(rad(c)) * Math.sin(dLng / 2) ** 2;
    return 2 * R * Math.asin(Math.min(1, Math.sqrt(h)));
  }

  const chip = $('loc-chip'), chipText = $('loc-text'), sub = $('loc-sub'), btn = $('btn-absen'), chk = $('chk-loc');
  const tones = {
    ok:   'bg-river-100 text-river-800',
    bad:  'bg-clay-100 text-clay-700',
    wait: 'bg-river-50 text-river-800',
  };
  function tone(kind) {
    chip.className = 'mx-auto mt-2 flex w-fit max-w-full items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold ' + tones[kind];
  }
  function setBtn(on) {
    if (!btn) return;
    btn.disabled = !on;
    btn.classList.toggle('btn-pulse', on);
  }
  function setCheck(on) {
    if (!chk) return;
    const dot = chk.querySelector('span');
    dot.className = 'flex h-5 w-5 items-center justify-center rounded-full ' + (on ? 'bg-river-600 text-white' : 'border-2 border-river-200');
    dot.innerHTML = on ? '<svg viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>' : '';
    chk.classList.toggle('text-river-900', on);
    chk.classList.toggle('font-semibold', on);
  }

  function evaluate() {
    if (!pos) return;
    const d = haversine(pos.lat, pos.lng, C.lat, C.lng);
    const inside = d <= C.radius;
    const okAcc = pos.acc <= C.maxAkurasi;

    // Titik pada radar
    const s = R_PX / C.radius;
    let x = (pos.lng - C.lng) * 111320 * Math.cos(rad(C.lat)) * s;
    let y = -(pos.lat - C.lat) * 110540 * s;
    const len = Math.hypot(x, y);
    if (len > MAX_PX) { x = (x / len) * MAX_PX; y = (y / len) * MAX_PX; }
    const dot = $('dot-user');
    dot.style.opacity = 1;
    dot.style.transform = `translate(${110 + x}px, ${110 + y}px)`;
    const color = inside && okAcc ? '#14776A' : '#C2410C';
    $('dot-core').setAttribute('fill', color);
    dot.firstElementChild.setAttribute('fill', color);
    const ring = $('ring');
    ring.setAttribute('stroke', inside ? '#14776A' : '#C2410C');
    ring.setAttribute('fill', inside ? '#14776A' : '#C2410C');

    const dm = Math.round(d);
    if (!okAcc) {
      tone('bad'); chipText.textContent = 'Sinyal GPS belum akurat';
      sub.textContent = `Akurasi ±${Math.round(pos.acc)} m (maks ${C.maxAkurasi} m). Pindah ke area terbuka.`;
    } else if (inside) {
      tone('ok'); chipText.textContent = `Di dalam area kantor · ${dm} m`;
      sub.textContent = `Akurasi lokasi ±${Math.round(pos.acc)} m`;
    } else {
      tone('bad'); chipText.textContent = `Di luar area · ${dm} m dari kantor`;
      sub.textContent = `Batas absen ${C.radius} m. Dekati kantor untuk absen.`;
    }
    const ok = inside && okAcc;
    setCheck(ok);
    setBtn(ok && C.mode !== 'selesai');
  }

  function onPos(p) {
    pos = { lat: p.coords.latitude, lng: p.coords.longitude, acc: p.coords.accuracy, t: Date.now() };
    evaluate();
  }
  function onErr(err) {
    pos = null; setBtn(false); setCheck(false); tone('bad');
    if (err.code === 1) {
      chipText.textContent = 'Izin lokasi ditolak';
      sub.textContent = 'Buka pengaturan situs di browser, izinkan Lokasi, lalu muat ulang halaman.';
    } else {
      chipText.textContent = 'Lokasi belum ditemukan';
      sub.textContent = 'Aktifkan GPS/Lokasi perangkat, lalu tunggu beberapa detik.';
    }
  }

  if (!('geolocation' in navigator)) {
    tone('bad'); chipText.textContent = 'Browser tidak mendukung lokasi';
    sub.textContent = 'Gunakan Chrome, Edge, atau Safari versi terbaru.';
  } else {
    navigator.geolocation.watchPosition(onPos, onErr, { enableHighAccuracy: true, maximumAge: 5000, timeout: 20000 });
  }

  if (!btn) return; // absensi hari ini sudah selesai

  /* ---------- Kamera ---------- */
  const modal = $('cam-modal'), video = $('cam-video'), preview = $('cam-preview'), errBox = $('cam-error');
  const ctlShoot = $('ctl-shoot'), ctlReview = $('ctl-review'), guide = $('cam-guide');

  function camError(msg) { errBox.textContent = msg; errBox.classList.toggle('hidden', !msg); }
  function showReview(on) {
    preview.classList.toggle('hidden', !on);
    video.classList.toggle('hidden', on);
    guide.classList.toggle('hidden', on);
    ctlShoot.classList.toggle('hidden', on);
    ctlReview.classList.toggle('hidden', !on);
    ctlReview.classList.toggle('grid', on);
  }
  function stopStream() {
    if (stream) { stream.getTracks().forEach((t) => t.stop()); stream = null; }
    video.srcObject = null;
  }

  async function openCam() {
    modal.classList.remove('hidden'); modal.classList.add('flex');
    camError(''); showReview(false); foto = null;
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      camError('Kamera hanya bisa dipakai lewat HTTPS atau http://localhost. Hubungi admin untuk mengaktifkan HTTPS.');
      return;
    }
    try {
      stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 960 } }, audio: false });
      video.srcObject = stream;
      await video.play();
    } catch (e) {
      const m = {
        NotAllowedError: 'Izin kamera ditolak. Izinkan Kamera pada pengaturan situs browser, lalu coba lagi.',
        NotFoundError: 'Kamera tidak ditemukan pada perangkat ini.',
        NotReadableError: 'Kamera sedang dipakai aplikasi lain. Tutup aplikasi tersebut lalu coba lagi.',
      };
      camError(m[e.name] || 'Kamera tidak dapat dibuka: ' + e.message);
    }
  }
  function closeCam() {
    stopStream();
    modal.classList.add('hidden'); modal.classList.remove('flex');
  }

  function capture() {
    if (!video.videoWidth || !pos) { camError(!pos ? 'Lokasi hilang. Tunggu sampai lokasi terdeteksi.' : 'Kamera belum siap.'); return; }
    camError('');
    const scale = Math.min(1, 720 / video.videoWidth);
    const w = Math.round(video.videoWidth * scale), h = Math.round(video.videoHeight * scale);
    const cv = $('cam-canvas'); cv.width = w; cv.height = h;
    const ctx = cv.getContext('2d');
    ctx.drawImage(video, 0, 0, w, h);

    // Watermark: nama, waktu (WITA), koordinat
    snap = { ...pos };
    const fs = Math.max(14, Math.round(w * 0.04));
    const lines = [
      C.nama,
      fmtFull.format(new Date(Date.now() + offset)).replace(/\./g, ':') + ' WITA',
      `${snap.lat.toFixed(6)}, ${snap.lng.toFixed(6)} (±${Math.round(snap.acc)} m)`,
    ];
    const pad = Math.round(fs * 0.7), bh = lines.length * fs * 1.35 + pad * 1.4;
    ctx.fillStyle = 'rgba(10,35,31,.72)'; ctx.fillRect(0, h - bh, w, bh);
    ctx.fillStyle = '#fff'; ctx.textBaseline = 'top';
    lines.forEach((t, i) => { ctx.font = `${i === 0 ? '700' : '500'} ${fs}px system-ui, sans-serif`; ctx.fillText(t, pad, h - bh + pad * 0.7 + i * fs * 1.35); });

    foto = cv.toDataURL('image/jpeg', 0.82);
    preview.src = foto;
    showReview(true);
  }

  async function send() {
    if (sending || !foto || !snap) return;
    sending = true;
    const sendBtn = $('btn-send');
    sendBtn.disabled = true; sendBtn.textContent = 'Mengirim…'; camError('');
    try {
      const res = await fetch(C.api, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': C.csrf },
        body: JSON.stringify({ type: C.mode, lat: snap.lat, lng: snap.lng, akurasi: snap.acc, foto }),
      });
      const j = await res.json().catch(() => ({ ok: false, message: 'Respons server tidak valid.' }));
      if (!j.ok) throw new Error(j.message);
      stopStream();
      $('done-title').textContent = j.message;
      $('done-sub').textContent = 'Pukul ' + j.jam + ' WITA';
      $('cam-done').classList.remove('hidden'); $('cam-done').classList.add('flex');
      ctlReview.classList.add('hidden'); ctlReview.classList.remove('grid');
      setTimeout(() => location.reload(), 1800);
    } catch (e) {
      camError(e.message || 'Gagal mengirim. Periksa koneksi internet.');
      sendBtn.disabled = false; sendBtn.textContent = 'Kirim absen';
    } finally { sending = false; }
  }

  btn.addEventListener('click', openCam);
  $('cam-close').addEventListener('click', closeCam);
  $('btn-shoot').addEventListener('click', capture);
  $('btn-retake').addEventListener('click', () => { foto = null; camError(''); showReview(false); });
  $('btn-send').addEventListener('click', send);
})();
