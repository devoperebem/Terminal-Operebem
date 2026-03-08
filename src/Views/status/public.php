<?php
$title = 'Status do Sistema';
$csrf_token = $_SESSION['csrf_token'] ?? '';
ob_start();
?>
<section class="py-4">
  <div class="container">
    <h1 class="h4 mb-3 d-flex align-items-center gap-2"><i class="fas fa-wave-square"></i>Status do Sistema</h1>
    <p class="text-secondary">Painel público de status. Sem exposição de dados sensíveis.</p>

    <div class="row g-3">
      <?php foreach (($components ?? []) as $c): $ok = in_array($c['status'], ['operational','configured','online'], true); ?>
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100">
          <div class="card-body d-flex flex-column justify-content-center text-center">
            <div class="mb-1 small text-muted"><?= htmlspecialchars($c['name']) ?></div>
            <div class="h5 mb-0">
              <span class="badge <?= $ok ? 'bg-success' : 'bg-warning' ?>"><?= htmlspecialchars($c['status']) ?></span>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="mt-4">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span><i class="fas fa-chart-line me-2"></i>Uptime (últimas 24h)</span>
          <small class="text-muted">Atualizado em <?= htmlspecialchars($updated_at ?? date('c')) ?></small>
        </div>
        <div class="card-body">
          <div class="progress" style="height: 10px;">
            <div class="progress-bar bg-success" style="width: 99%"></div>
          </div>
          <div class="small text-muted mt-2">Uptime ilustrativo — painel detalhado disponível apenas para administradores.</div>
        </div>
      </div>
    </div>

    <div class="mt-4">
      <div class="card">
        <div class="card-header"><i class="fas fa-shield-alt me-2"></i>Segurança</div>
        <div class="card-body">
          <div class="row g-3">
            <?php foreach (($security ?? []) as $s): $ok = in_array($s['status'], ['enabled','nosniff','deny_or_sameorigin','strict-origin-when-cross-origin','restricted'], true); ?>
            <div class="col-12 col-md-6 col-lg-4">
              <div class="d-flex align-items-center justify-content-between border rounded p-2">
                <div class="d-flex align-items-center gap-2">
                  <i class="fas fa-lock text-muted"></i>
                  <span class="small"><?= htmlspecialchars($s['name']) ?></span>
                </div>
                <span class="badge <?= $ok ? 'bg-success' : 'bg-warning' ?>"><?= htmlspecialchars($s['status']) ?></span>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php $__host = $_SERVER['HTTP_HOST'] ?? 'terminal.operebem.com.br'; $hostQ = rawurlencode($__host); ?>
          <div class="d-flex flex-wrap gap-2 mt-3" aria-label="Validações de segurança externas">
            <a class="badge bg-secondary d-inline-flex align-items-center gap-1" href="https://www.ssllabs.com/ssltest/analyze.html?d=<?= $hostQ ?>" target="_blank" rel="noopener"><i class="fas fa-lock"></i> SSL Labs</a>
            <a class="badge bg-secondary d-inline-flex align-items-center gap-1" href="https://transparencyreport.google.com/safe-browsing/search?url=<?= $hostQ ?>" target="_blank" rel="noopener"><i class="fab fa-google"></i> Safe Browsing</a>
            <a class="badge bg-success d-inline-flex align-items-center gap-1" href="https://securityheaders.com/?q=<?= $hostQ ?>&followRedirects=on" target="_blank" rel="noopener"><i class="fas fa-shield-alt"></i> Security Headers</a>
            <a class="badge bg-success d-inline-flex align-items-center gap-1" href="https://observatory.mozilla.org/analyze/<?= $hostQ ?>" target="_blank" rel="noopener"><i class="fab fa-firefox-browser"></i> Mozilla Observatory</a>
            <a class="badge bg-primary d-inline-flex align-items-center gap-1" href="https://csp-evaluator.withgoogle.com/" target="_blank" rel="noopener"><i class="fas fa-user-shield"></i> CSP Evaluator</a>
            <a class="badge bg-dark d-inline-flex align-items-center gap-1" href="https://hstspreload.org/?domain=<?= $hostQ ?>" target="_blank" rel="noopener"><i class="fas fa-link"></i> HSTS Preload</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php
$content = ob_get_clean();
$scripts = '';
include __DIR__ . '/../layouts/app.php';
?>
