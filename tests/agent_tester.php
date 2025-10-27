<?php
// /tests/agent_tester.php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Chatbot Tester — Category Only (No LLM)</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  :root { --bg:#f6f7fb; --muted:#6b7280; --border:#e5e7eb; }
  body{background:var(--bg)}
  .card{border-radius:16px}
  .code{font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono"; font-size:13px; white-space:pre-wrap}
  .pill{border:1px solid var(--border); border-radius:999px; padding:.2rem .6rem; font-size:.8rem; background:#fff}
  .grid{display:grid; gap:12px; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr))}
  .thumb{width:100%; height:140px; object-fit:cover; border-radius:12px; border:1px solid #eee}
  .muted{color:var(--muted)}
  .smallcaps{font-variant-caps:all-small-caps; letter-spacing:.02em}
  .chip{cursor:pointer}
</style>
</head>
<body>
<div class="container py-4">

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h3 mb-0">Chatbot Tester <span class="text-muted small">no-LLM</span></h1>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="/api/debug/catalog.php" target="_blank">Open /api/debug/catalog</a>
      <a class="btn btn-outline-secondary btn-sm" href=".././api/chatbot.php" target="_blank">Open API</a>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-3 align-items-end">
        <div class="col-12">
          <label class="form-label">Message</label>
          <textarea id="msg" class="form-control" rows="2" placeholder="Alphabet songs / Animal videos / قصص قبل النوم"></textarea>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label">Max Items</label>
          <input id="max" type="number" class="form-control" value="8" min="1" max="24">
        </div>
        <div class="col-6 col-md-3">
          <div class="form-check mt-4">
            <input class="form-check-input" type="checkbox" id="debug" checked>
            <label class="form-check-label" for="debug">Debug mode (?debug=1)</label>
          </div>
        </div>
        <div class="col-12 col-md-7 d-flex gap-2 mt-2 mt-md-0 justify-content-md-end">
          <button id="send" class="btn btn-primary">Send</button>
          <button id="getCounts" class="btn btn-outline-secondary">Get Counts</button>
          <button id="clear" class="btn btn-outline-danger">Clear</button>
        </div>
      </div>

      <div class="mt-3">
        <div class="muted small mb-1">Quick chips</div>
        <div id="chips" class="d-flex flex-wrap gap-2">
          <span class="pill chip">Alphabet songs</span>
          <span class="pill chip">Animal videos</span>
          <span class="pill chip">Fun math</span>
          <span class="pill chip">Bedtime stories</span>
          <span class="pill chip">Dance for kids</span>
          <span class="pill chip">Science experiments</span>
          <span class="pill chip">Games for kids</span>
          <span class="pill chip">قصص قبل النوم</span>
          <span class="pill chip">تعلم الحروف</span>
          <span class="pill chip">أغاني الأطفال</span>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card mb-3">
        <div class="card-header smallcaps">Request</div>
        <div class="card-body">
          <dl class="row">
            <dt class="col-4">Endpoint</dt><dd class="col-8"><code id="endpoint"></code></dd>
            <dt class="col-4">Method</dt><dd class="col-8"><code>POST</code></dd>
            <dt class="col-4">Elapsed</dt><dd class="col-8"><code id="elapsed">–</code></dd>
            <dt class="col-4">HTTP Status</dt><dd class="col-8"><code id="status">–</code></dd>
          </dl>
          <div class="mb-2 fw-semibold">Request Body</div>
          <pre id="reqbody" class="code bg-light p-2 rounded"></pre>
          <div class="mb-2 fw-semibold mt-3">Response Headers</div>
          <pre id="headers" class="code bg-light p-2 rounded"></pre>
        </div>
      </div>
      <div class="card">
        <div class="card-header smallcaps">Raw Response JSON</div>
        <div class="card-body">
          <pre id="raw" class="code bg-light p-2 rounded"></pre>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card mb-3">
        <div class="card-header smallcaps d-flex justify-content-between">
          <span>Parsed Items</span>
          <span class="muted small" id="chosenCatHint"></span>
        </div>
        <div class="card-body">
          <div id="summary" class="mb-2 muted"></div>
          <div id="grid" class="grid"></div>
        </div>
      </div>

      <div class="card">
        <div class="card-header smallcaps">Catalog Counts (/api/debug/catalog)</div>
        <div class="card-body">
          <pre id="counts" class="code bg-light p-2 rounded">Click “Get Counts”.</pre>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
const ep = './../api/llm_category_bot.php';
document.getElementById('endpoint').textContent = ep;

function pretty(x){ try { return JSON.stringify(x, null, 2); } catch(e){ return String(x); } }
function escapeHtml(s){ return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function renderHeaders(h){ const out=[]; for (const [k,v] of h.entries()) out.push(k+': '+v); return out.join('\\n'); }

function card(it){
  const minutes = it.duration_sec ? Math.round(it.duration_sec/60)+' min' : '';
  return `
  <div class="p-2 border rounded" style="background:#fff; border-color:#e5e7eb">
    ${it.thumbnail ? `<img class="thumb" src="${it.thumbnail}" alt="">` : ''}
    <div class="mt-2 fw-semibold">${escapeHtml(it.title || '(no title)')}</div>
    <div class="muted small mt-1">${(it.type||'').toUpperCase()} ${minutes? '• '+minutes:''}</div>
    <div class="mt-2 d-flex flex-wrap gap-1">
      ${it.source ? `<span class="pill">src: ${escapeHtml(it.source)}</span>`:''}
      ${it.category ? `<span class="pill">cat: ${escapeHtml(it.category)}</span>`:''}
    </div>
    ${it.detail_url ? `<div class="mt-2"><a class="btn btn-sm btn-outline-primary" target="_blank" href="${it.detail_url}">Open</a></div>`:''}
  </div>`;
}

async function send(){
  const message = document.getElementById('msg').value.trim() || 'Alphabet songs';
  const max     = parseInt(document.getElementById('max').value || '8', 10);
  const debug   = document.getElementById('debug').checked;

  const payload = { message, max_items: max, debug };
  document.getElementById('reqbody').textContent = pretty(payload);

  const t0 = performance.now();
  const res = await fetch(ep + (debug ? '?debug=1' : ''), {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify(payload)
  }).catch(e => ({ ok:false, statusText: String(e) }));

  const t1 = performance.now();
  document.getElementById('elapsed').textContent = (t1 - t0).toFixed(1) + ' ms';
  document.getElementById('status').textContent  = (res.status ?? '—') + ' ' + (res.statusText||'');

  if (!res.ok) {
    document.getElementById('raw').textContent = 'HTTP error';
    document.getElementById('headers').textContent = '(no headers)';
    document.getElementById('grid').innerHTML = '';
    document.getElementById('summary').textContent = '';
    document.getElementById('chosenCatHint').textContent = '';
    return;
  }

  document.getElementById('headers').textContent = renderHeaders(res.headers);

  let json;
  try { json = await res.json(); } catch(e){ json = { parse_error: String(e) }; }
  document.getElementById('raw').textContent = pretty(json);

  const dbgCat = json.debug && json.debug.chosen_category ? json.debug.chosen_category : '';
  document.getElementById('chosenCatHint').textContent = dbgCat ? ('chosen: ' + dbgCat) : '';

  document.getElementById('summary').textContent = json.summary || '';
  const grid = document.getElementById('grid');
  grid.innerHTML = Array.isArray(json.items) ? json.items.map(card).join('') : '';
}

async function getCounts(){
  const res = await fetch('.././api/debug/catalog.php').catch(()=>null);
  if (!res || !res.ok){
    document.getElementById('counts').textContent = 'Failed to load /api/debug/catalog (HTTP ' + (res && res.status) + ')';
    return;
  }
  const json = await res.json();
  document.getElementById('counts').textContent = pretty(json);
}

document.getElementById('send').addEventListener('click', send);
document.getElementById('getCounts').addEventListener('click', getCounts);
document.getElementById('clear').addEventListener('click', () => {
  document.getElementById('msg').value = '';
  document.getElementById('raw').textContent = '';
  document.getElementById('reqbody').textContent = '';
  document.getElementById('headers').textContent = '';
  document.getElementById('summary').textContent = '';
  document.getElementById('grid').innerHTML = '';
  document.getElementById('status').textContent = '–';
  document.getElementById('elapsed').textContent = '–';
  document.getElementById('chosenCatHint').textContent = '';
});
document.getElementById('chips').addEventListener('click', (e) => {
  if (e.target.classList.contains('chip')) {
    document.getElementById('msg').value = e.target.textContent.trim();
    document.getElementById('msg').focus();
  }
});
document.getElementById('msg').addEventListener('keydown', (e) => {
  if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) send();
});
</script>
</body>
</html>
