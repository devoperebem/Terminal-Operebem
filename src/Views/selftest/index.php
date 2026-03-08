<?php
$title = 'Self-Test - Terminal Operebem';
$csrf_token = $_SESSION['csrf_token'] ?? '';
ob_start();
?>
<section class="py-4">
  <div class="container">
    <h1 class="h4 mb-3">Self-Test</h1>
    <p class="text-secondary">Execute os testes automáticos no navegador e copie os resultados para enviar.</p>

    <div class="card mb-4">
      <div class="card-header">Servidor</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead>
              <tr><th>Teste</th><th>Status</th><th>Valor</th></tr>
            </thead>
            <tbody>
              <?php foreach (($serverResults ?? []) as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td>
                  <?php if (!empty($row['ok'])): ?>
                    <span class="badge bg-success">OK</span>
                  <?php else: ?>
                    <span class="badge bg-danger">Falha</span>
                  <?php endif; ?>
                </td>
                <td><code><?= htmlspecialchars(is_scalar($row['value']) ? (string)$row['value'] : json_encode($row['value'])) ?></code></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Testes no Navegador</span>
        <div class="d-flex gap-2">
          <button id="runTestsBtn" class="btn btn-sm btn-primary">Executar</button>
          <button id="copyBtn" class="btn btn-sm btn-outline-secondary">Copiar Resultado</button>
        </div>
      </div>
      <div class="card-body">
        <pre id="testOutput" class="small" style="white-space: pre-wrap; word-break: break-word;"></pre>
      </div>
    </div>
  </div>
</section>
<script>
(function(){
  const out = document.getElementById('testOutput');
  const btn = document.getElementById('runTestsBtn');
  const copyBtn = document.getElementById('copyBtn');

  function report(obj){ try { out.textContent = JSON.stringify(obj, null, 2); } catch(e) { out.textContent = String(obj); } }

  async function runTests(){
    const results = { when: new Date().toISOString(), user: <?= json_encode(isset($user) ? ['id' => $user['id'] ?? null, 'email' => $user['email'] ?? null] : null) ?> };
    try {
      results.recaptcha_site_key = !!window.__RECAPTCHA_V3_SITE_KEY;
      const badge = document.querySelector('.grecaptcha-badge');
      results.recaptcha_badge_hidden = badge ? (getComputedStyle(badge).visibility === 'hidden' || getComputedStyle(badge).opacity === '0') : true;
    } catch(e){ results.recaptcha_error = e && e.message || 'error'; }

    try {
      const r = await fetch('/csrf/token', { method: 'GET', credentials: 'same-origin' });
      const j = await r.json();
      results.csrf_endpoint_ok = !!(j && j.token);
    } catch(e){ results.csrf_endpoint_ok = false; results.csrf_error = e && e.message; }

    try {
      results.fetch_wrapper_present = (typeof window.fetch === 'function');
      results.csrf_token_in_window = !!window.__CSRF_TOKEN;
    } catch(e){ results.fetch_wrapper_present = false; }

    try {
      // Check avatar header if logged in
      const img = document.querySelector('img.nav-avatar');
      results.header_avatar_present = !!img;
      if (img) { results.header_avatar_src = img.getAttribute('src'); }
    } catch(e){ results.header_avatar_present = false; }

    try {
      // Quick layout checks (mobile header min-height)
      const hdr = document.querySelector('.card .card-header.title-card');
      if (hdr) { results.card_header_min_height = getComputedStyle(hdr).minHeight; }
    } catch(e){}

    report(results);
  }

  if (btn) btn.addEventListener('click', runTests);
  if (copyBtn) copyBtn.addEventListener('click', async function(){
    try { await navigator.clipboard.writeText(out.textContent || ''); copyBtn.textContent = 'Copiado!'; setTimeout(()=>{ copyBtn.textContent='Copiar Resultado'; }, 1200); } catch(e){}
  });

  // Auto-run on load
  document.addEventListener('DOMContentLoaded', runTests);
})();
</script>
<?php
$content = ob_get_clean();
$scripts = '';
include __DIR__ . '/../layouts/app.php';
?>
