(() => {
  const CACHE_KEY = 'transport-pwa-bootstrap-v1';
  const OUTBOX_KEY = 'transport-pwa-outbox-v1';
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || localStorage.getItem('transport-csrf');
  if (csrf) localStorage.setItem('transport-csrf', csrf);
  const bootstrap = window.TRANSPORT_BOOTSTRAP || JSON.parse(localStorage.getItem(CACHE_KEY) || '{"jobs":[],"companies":[],"customers":[],"sites":[],"permissions":{}}');
  const syncUrl = window.TRANSPORT_SYNC || '/pwa/sync';
  localStorage.setItem(CACHE_KEY, JSON.stringify(bootstrap));
  let outbox = JSON.parse(localStorage.getItem(OUTBOX_KEY) || '[]');
  const $ = selector => document.querySelector(selector);
  const esc = value => String(value ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
  const uuid = () => crypto.randomUUID ? crypto.randomUUID() : `${Date.now()}-${Math.random()}`;
  const statusLabel = status => status.replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
  const nextStatus = { assigned:'en_route', en_route:'on_site', on_site:'collected', collected:'completed' };

  function setConnectivity() {
    const online = navigator.onLine;
    $('#connection-state').textContent = online ? 'Online' : 'Offline';
    $('#connection-state').className = `rounded-full px-3 py-1 text-xs font-semibold ${online ? 'bg-emerald-500' : 'bg-amber-500'}`;
  }
  function saveOutbox() { localStorage.setItem(OUTBOX_KEY, JSON.stringify(outbox)); renderOutbox(); }
  function renderJobs() {
    $('#jobs').innerHTML = bootstrap.jobs.length ? bootstrap.jobs.map(job => {
      const action = job.actions?.[0]; const site = action?.site;
      const next = nextStatus[job.status];
      return `<article class="rounded-xl bg-white p-4 shadow"><div class="flex items-start justify-between gap-3"><div><p class="font-mono text-sm text-slate-500">${esc(job.reference)}</p><h2 class="text-lg font-semibold">${esc(job.customer?.name)}</h2></div><span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-semibold">${esc(statusLabel(job.status))}</span></div><p class="mt-2 text-sm">${esc(site?.name || '')} ${esc(site?.postcode || '')}</p><p class="text-sm text-slate-600">${esc(action?.schedule_start ? new Date(action.schedule_start).toLocaleString() : 'Schedule not set')}</p><div class="mt-3 space-y-1">${(job.items || []).map(item => `<p class="rounded bg-slate-50 p-2 text-sm">${esc(item.stock_number || '')} ${esc(item.description)}</p>`).join('')}</div>${next ? `<button data-job="${job.id}" data-next="${next}" class="mt-4 min-h-12 w-full rounded-lg bg-blue-700 p-3 font-semibold text-white">Mark ${esc(statusLabel(next))}</button>` : ''}</article>`;
    }).join('') : '<p class="rounded-xl bg-white p-6 text-center text-slate-600 shadow">No assigned jobs.</p>';
    document.querySelectorAll('[data-job]').forEach(button => button.addEventListener('click', () => openJob(Number(button.dataset.job), button.dataset.next)));
  }
  function renderOutbox() {
    $('#outbox-count').textContent = outbox.length ? `(${outbox.length})` : '';
    $('#outbox').innerHTML = outbox.length ? outbox.map(op => `<article class="rounded-xl bg-white p-4 shadow"><p class="font-semibold">${esc(statusLabel(op.type))}</p><p class="text-xs text-slate-500">${esc(op.id)}</p><p class="mt-1 text-sm">${op.error ? esc(op.error) : 'Waiting to synchronize'}</p></article>`).join('') : '<p class="rounded-xl bg-white p-6 text-center text-slate-600 shadow">Everything is synchronized.</p>';
  }
  function fillSelect(id, rows, label) { const element = $(id); if (!element) return; rows.forEach(row => element.insertAdjacentHTML('beforeend', `<option value="${row.id}">${esc(label(row))}</option>`)); }
  fillSelect('#yard-company', bootstrap.companies || [], row => row.name); fillSelect('#yard-customer', bootstrap.customers || [], row => `${row.name} (${row.account_number})`); fillSelect('#yard-site', bootstrap.sites || [], row => `${row.name} — ${row.postcode}`);

  document.querySelectorAll('.pwa-tab').forEach(button => button.addEventListener('click', () => {
    ['jobs','yard','outbox'].forEach(tab => $(`#tab-${tab}`).classList.toggle('hidden', tab !== button.dataset.tab));
    document.querySelectorAll('.pwa-tab').forEach(tab => { tab.classList.toggle('bg-slate-900', tab === button); tab.classList.toggle('text-white', tab === button); tab.classList.toggle('bg-white', tab !== button); });
  }));

  async function fileData(files) { return Promise.all([...files].slice(0, 12).map(file => new Promise((resolve, reject) => { const reader = new FileReader(); reader.onload = () => resolve(reader.result); reader.onerror = reject; reader.readAsDataURL(file); }))); }
  async function queue(type, payload) { outbox.push({id: uuid(), type, payload, queued_at: new Date().toISOString()}); saveOutbox(); await synchronize(); }

  $('#yard-form')?.addEventListener('submit', async event => {
    event.preventDefault(); const form = event.currentTarget; const data = Object.fromEntries(new FormData(form));
    data.company_id = Number(data.company_id); data.site_id = Number(data.site_id); data.customer_id = data.customer_id ? Number(data.customer_id) : null; data.photos = await fileData(form.elements.photos.files);
    await queue('yard_receipt', data); form.reset(); alert('Receipt saved. It will synchronize automatically.');
  });

  const dialog = $('#job-dialog'); let drawing = false; const canvas = $('#signature'); const ctx = canvas?.getContext('2d');
  function point(event) { const rect = canvas.getBoundingClientRect(); const touch = event.touches?.[0] || event; return [(touch.clientX - rect.left) * canvas.width / rect.width, (touch.clientY - rect.top) * canvas.height / rect.height]; }
  canvas?.addEventListener('pointerdown', event => { drawing = true; ctx.beginPath(); ctx.moveTo(...point(event)); });
  canvas?.addEventListener('pointermove', event => { if (!drawing) return; ctx.lineWidth = 3; ctx.lineCap = 'round'; ctx.lineTo(...point(event)); ctx.stroke(); });
  window.addEventListener('pointerup', () => drawing = false); $('#clear-signature')?.addEventListener('click', () => ctx.clearRect(0,0,canvas.width,canvas.height));
  document.querySelector('[data-close]')?.addEventListener('click', () => dialog.close());
  function openJob(id, next) { const job = bootstrap.jobs.find(row => row.id === id); const form = $('#job-form'); form.elements.movement_id.value = id; form.elements.expected_lock_version.value = job.lock_version; form.elements.to_status.value = next; $('#job-title').textContent = `${job.reference}: ${statusLabel(next)}`; ctx.clearRect(0,0,canvas.width,canvas.height); dialog.showModal(); }
  $('#job-form')?.addEventListener('submit', async event => { event.preventDefault(); const form = event.currentTarget; const data = Object.fromEntries(new FormData(form)); data.movement_id = Number(data.movement_id); data.expected_lock_version = Number(data.expected_lock_version); data.photos = await fileData(form.elements.photos.files); data.signature = canvas.toDataURL('image/png'); await queue('transition', data); dialog.close(); });

  async function synchronize() {
    if (!navigator.onLine || !outbox.length || !csrf) return;
    const pending = outbox.filter(op => !op.error); if (!pending.length) return;
    try {
      const response = await fetch(syncUrl, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf}, body:JSON.stringify({operations:pending})});
      if (response.status === 401 || response.status === 419) { $('#sync-alert').classList.remove('hidden'); $('#sync-alert').textContent = 'Sign in again before queued work can synchronize.'; return; }
      const body = await response.json(); body.results.forEach(result => { const op = outbox.find(row => row.id === result.id); if (result.ok) outbox = outbox.filter(row => row.id !== result.id); else if (op) op.error = result.message; }); saveOutbox();
      if (body.results.some(result => result.ok)) location.reload();
    } catch (_) { setConnectivity(); }
  }

  setConnectivity(); renderJobs(); renderOutbox(); window.addEventListener('online', () => { setConnectivity(); synchronize(); }); window.addEventListener('offline', setConnectivity);
  if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js');
  synchronize();
})();
