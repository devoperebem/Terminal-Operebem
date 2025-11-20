<?php
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h4 mb-0 d-flex align-items-center gap-2">Status do Sistema (Admin)</h1>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="card h-100"><div class="card-body d-flex flex-column justify-content-center"><div class="text-muted small">Usuários</div><div class="h4 mb-0"><?= (int)($metrics['users'] ?? 0) ?></div></div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card h-100"><div class="card-body d-flex flex-column justify-content-center"><div class="text-muted small">Tickets abertos</div><div class="h4 mb-0"><?= (int)($metrics['tickets_open'] ?? 0) ?></div></div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card h-100"><div class="card-body d-flex flex-column justify-content-center"><div class="text-muted small">Admins</div><div class="h4 mb-0"><?= (int)($metrics['admins'] ?? 0) ?></div></div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card h-100"><div class="card-body d-flex flex-column justify-content-center"><div class="text-muted small">Logins (24h)</div><div class="h4 mb-0"><?= (int)($metrics['logins_24h'] ?? 0) ?></div></div></div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header">Componentes</div>
        <div class="card-body">
          <ul class="list-unstyled mb-0">
            <li class="d-flex justify-content-between align-items-center py-1 border-bottom"><span>Banco de Dados</span><span class="badge <?= !empty($status['db_ok']) ? 'bg-success' : 'bg-danger' ?>"><?= !empty($status['db_ok']) ? 'OK' : 'Falha' ?></span></li>
            <li class="d-flex justify-content-between align-items-center py-1 border-bottom"><span>SMTP</span><span class="badge <?= !empty($status['smtp_config']) ? 'bg-success' : 'bg-warning' ?>"><?= !empty($status['smtp_config']) ? 'OK' : 'Ausente' ?></span></li>
            <li class="d-flex justify-content-between align-items-center py-1 border-bottom"><span>reCAPTCHA site key</span><span class="badge <?= !empty($status['recaptcha_site_key']) ? 'bg-success' : 'bg-warning' ?>"><?= !empty($status['recaptcha_site_key']) ? 'OK' : 'Ausente' ?></span></li>
            <li class="d-flex justify-content-between align-items-center py-1 border-bottom"><span>reCAPTCHA secret</span><span class="badge <?= !empty($status['recaptcha_secret']) ? 'bg-success' : 'bg-warning' ?>"><?= !empty($status['recaptcha_secret']) ? 'OK' : 'Ausente' ?></span></li>
            <li class="d-flex justify-content-between align-items-center py-1"><span>JWT Secret</span><span class="badge <?= !empty($status['jwt_secret']) ? 'bg-success' : 'bg-danger' ?>"><?= !empty($status['jwt_secret']) ? 'OK' : 'Faltando' ?></span></li>
          </ul>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header">Uptime (últimas 24h)</div>
        <div class="card-body">
          <div class="progress" style="height: 10px;">
            <div class="progress-bar bg-success" style="width: 99%"></div>
          </div>
          <div class="small text-muted mt-2">Gráfico ilustrativo — expandiremos com pings e histórico.</div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
$scripts = '';
include __DIR__ . '/../layouts/app.php';
?>
