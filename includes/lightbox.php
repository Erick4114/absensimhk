<dialog id="lightbox" class="m-auto max-w-[92vw] rounded-2xl bg-transparent p-0">
  <div class="relative">
    <img id="lightbox-img" src="" alt="Bukti foto absensi" class="max-h-[85vh] rounded-2xl">
    <button type="button" onclick="document.getElementById('lightbox').close()" class="absolute top-2 right-2 rounded-full bg-black/60 p-2 text-white" aria-label="Tutup"><?= icon('x') ?></button>
  </div>
</dialog>
<script>
  document.addEventListener('click', (e) => {
    const t = e.target.closest('[data-full]');
    if (!t) return;
    document.getElementById('lightbox-img').src = t.dataset.full;
    document.getElementById('lightbox').showModal();
  });
  document.getElementById('lightbox').addEventListener('click', (e) => { if (e.target.id === 'lightbox') e.target.close(); });
</script>
