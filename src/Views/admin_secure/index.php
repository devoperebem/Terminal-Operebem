<?php
ob_start();
?>
<style>
.stat-card { transition: transform 0.2s, box-shadow 0.2s; border-left: 4px solid; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.stat-card.primary { border-left-color: #0d6efd; }
.stat-card.success { border-left-color: #198754; }
.stat-card.warning { border-left-color: #ffc107; }
.stat-card.danger { border-left-color: #dc3545; }
.stat-card.info { border-left-color: #0dcaf0; }
.stat-value { font-size: 2rem; font-weight: 700; line-height: 1; }
.stat-label { font-size: 0.875rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
.chart-container { position: relative; height: 300px; }
.shortcut-btn { transition: all 0.2s; }
.shortcut-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
.badge-xp { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.table-sm td, .table-sm th { padding: 0.5rem; font-size: 0.875rem; }
</style>

<div class="container-fluid py-4">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Dashboard Administrativo</h1>
    <div class="text-muted small">
      Última atualização: <?= date('d/m/Y H:i:s') ?>
    </div>
  </div>

  <!-- Quick Actions / Atalhos no Topo -->
  <div class="card mb-4">
    <div class="card-header bg-primary text-white">
      <strong>Atalhos Rápidos</strong>
    </div>
    <div class="card-body">
      <div class="row g-2">
        <div class="col-6 col-md-4 col-lg-2">
          <a href="/secure/adm/users" class="btn btn-outline-primary w-100 shortcut-btn">
            <div class="py-2">
              <div class="h1 mb-1">👥</div>
              <div class="small">Usuários</div>
            </div>
          </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
          <a href="/secure/adm/tickets" class="btn btn-outline-secondary w-100 shortcut-btn">
            <div class="py-2">
              <div class="h1 mb-1">🎫</div>
              <div class="small">Tickets</div>
            </div>
          </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
          <a href="/secure/adm/gamification" class="btn btn-outline-info w-100 shortcut-btn">
            <div class="py-2">
              <div class="h1 mb-1">🎮</div>
              <div class="small">Gamificação</div>
            </div>
          </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
          <a href="/secure/adm/crm" class="btn btn-outline-success w-100 shortcut-btn">
            <div class="py-2">
              <div class="h1 mb-1">📊</div>
              <div class="small">CRM</div>
            </div>
          </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
          <a href="/secure/adm/reviews" class="btn btn-outline-warning w-100 shortcut-btn">
            <div class="py-2">
              <div class="h1 mb-1">⭐</div>
              <div class="small">Reviews & Feedbacks</div>
            </div>
          </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
          <a href="/secure/adm/aluno/access" class="btn btn-outline-danger w-100 shortcut-btn">
            <div class="py-2">
              <div class="h1 mb-1">🔑</div>
              <div class="small">Acessos</div>
            </div>
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- System Status -->
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><strong>Status do Sistema</strong></span>
      <a href="/status" class="small">ver detalhes</a>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-6 col-md-3">
          <div class="d-flex justify-content-between align-items-center">
            <span>Banco de Dados</span>
            <span class="badge <?= !empty($status['db_ok']) ? 'bg-success' : 'bg-danger' ?>">
              <?= !empty($status['db_ok']) ? '✓ OK' : '✗ Falha' ?>
            </span>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="d-flex justify-content-between align-items-center">
            <span>SMTP</span>
            <span class="badge <?= !empty($status['smtp_config']) ? 'bg-success' : 'bg-warning' ?>">
              <?= !empty($status['smtp_config']) ? '✓ OK' : '⚠ Ausente' ?>
            </span>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="d-flex justify-content-between align-items-center">
            <span>reCAPTCHA</span>
            <span class="badge <?= !empty($status['recaptcha_site_key']) ? 'bg-success' : 'bg-warning' ?>">
              <?= !empty($status['recaptcha_site_key']) ? '✓ OK' : '⚠ Ausente' ?>
            </span>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="d-flex justify-content-between align-items-center">
            <span>JWT Secret</span>
            <span class="badge <?= !empty($status['jwt_secret']) ? 'bg-success' : 'bg-danger' ?>">
              <?= !empty($status['jwt_secret']) ? '✓ OK' : '✗ Faltando' ?>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Stats Cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="card stat-card primary h-100">
        <div class="card-body">
          <div class="stat-label mb-2">Total de Usuários</div>
          <div class="stat-value text-primary mb-1"><?= number_format($counts['users_total'] ?? 0, 0, ',', '.') ?></div>
          <small class="text-muted"><?= $userStats['verified'] ?? 0 ?> verificados</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card stat-card warning h-100">
        <div class="card-body">
          <div class="stat-label mb-2">Tickets Abertos</div>
          <div class="stat-value text-warning mb-1"><?= $counts['tickets_open'] ?? 0 ?></div>
          <small class="text-muted">suporte ativo</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card stat-card success h-100">
        <div class="card-body">
          <div class="stat-label mb-2">Logins (24h)</div>
          <div class="stat-value text-success mb-1"><?= $counts['logins_24h'] ?? 0 ?></div>
          <small class="text-muted">tentativas</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card stat-card info h-100">
        <div class="card-body">
          <div class="stat-label mb-2">Administradores</div>
          <div class="stat-value text-info mb-1"><?= $counts['admins_total'] ?? 0 ?></div>
          <small class="text-muted">ativos</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Charts Row -->
  <div class="row g-3 mb-4">
    <!-- User Activity Chart -->
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <strong>Atividade de Usuários</strong>
        </div>
        <div class="card-body">
          <canvas id="userActivityChart"></canvas>
        </div>
      </div>
    </div>

    <!-- XP Distribution Chart -->
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <strong>Distribuição de XP por Fonte (7 dias)</strong>
        </div>
        <div class="card-body">
          <canvas id="xpSourceChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- XP & Discord Stats -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-lg-3">
      <div class="card stat-card h-100" style="border-left-color: #667eea;">
        <div class="card-body text-center">
          <div class="stat-label mb-2">XP Total</div>
          <div class="stat-value mb-1" style="color: #667eea;"><?= number_format($xpStats['total_xp'] ?? 0, 0, ',', '.') ?></div>
          <small class="text-muted">Média: <?= $xpStats['avg_xp'] ?? 0 ?> XP</small>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
      <div class="card stat-card h-100" style="border-left-color: #f39c12;">
        <div class="card-body text-center">
          <div class="stat-label mb-2">Nível Médio</div>
          <div class="stat-value mb-1" style="color: #f39c12;"><?= $xpStats['avg_level'] ?? 0 ?></div>
          <small class="text-muted">Máximo: <?= $xpStats['max_level'] ?? 0 ?></small>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
      <div class="card stat-card h-100" style="border-left-color: #e74c3c;">
        <div class="card-body text-center">
          <div class="stat-label mb-2">Streak Médio</div>
          <div class="stat-value mb-1" style="color: #e74c3c;"><?= $xpStats['avg_streak'] ?? 0 ?></div>
          <small class="text-muted">Máximo: <?= $xpStats['max_streak'] ?? 0 ?></small>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
      <div class="card stat-card h-100" style="border-left-color: #5865F2;">
        <div class="card-body text-center">
          <div class="stat-label mb-2">Discord Verificados</div>
          <div class="stat-value mb-1" style="color: #5865F2;"><?= $discordStats['verified_users'] ?? 0 ?></div>
          <small class="text-muted"><?= $discordStats['pending_users'] ?? 0 ?> pendentes</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Top Users & Recent XP -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <strong>🏆 Top 10 Usuários por XP</strong>
        </div>
        <div class="card-body">
          <?php if (!empty($topUsers)): ?>
            <div class="table-responsive">
              <table class="table table-sm table-hover mb-0">
                <thead>
                  <tr>
                    <th width="40">#</th>
                    <th>Nome</th>
                    <th class="text-center">Level</th>
                    <th class="text-end">XP</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($topUsers as $idx => $user): ?>
                    <tr>
                      <td>
                        <?php if ($idx === 0): ?>
                          🥇
                        <?php elseif ($idx === 1): ?>
                          🥈
                        <?php elseif ($idx === 2): ?>
                          🥉
                        <?php else: ?>
                          <?= $idx + 1 ?>
                        <?php endif; ?>
                      </td>
                      <td>
                        <a href="/secure/adm/users/<?= $user['id'] ?>" class="text-decoration-none">
                          <?= htmlspecialchars($user['name'] ?? 'N/A') ?>
                        </a>
                      </td>
                      <td class="text-center"><span class="badge bg-info"><?= $user['level'] ?? 0 ?></span></td>
                      <td class="text-end"><strong><?= number_format($user['xp'] ?? 0, 0, ',', '.') ?></strong></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <p class="text-muted text-center mb-0">Nenhum usuário encontrado</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <strong>📈 Transações de XP Recentes</strong>
        </div>
        <div class="card-body">
          <?php if (!empty($recentXP)): ?>
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
              <table class="table table-sm table-hover mb-0">
                <thead class="sticky-top bg-white">
                  <tr>
                    <th>Usuário</th>
                    <th>Fonte</th>
                    <th class="text-end">XP</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach (array_slice($recentXP, 0, 10) as $xp): ?>
                    <tr>
                      <td>
                        <a href="/secure/adm/users/<?= $xp['user_id'] ?>" class="text-decoration-none small">
                          <?= htmlspecialchars(substr($xp['user_name'] ?? 'N/A', 0, 20)) ?>
                        </a>
                      </td>
                      <td><span class="badge badge-xp small"><?= htmlspecialchars($xp['source'] ?? 'unknown') ?></span></td>
                      <td class="text-end">
                        <span class="badge <?= ($xp['amount'] ?? 0) > 0 ? 'bg-success' : 'bg-danger' ?>">
                          <?= ($xp['amount'] ?? 0) > 0 ? '+' : '' ?><?= $xp['amount'] ?? 0 ?>
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <p class="text-muted text-center mb-0">Nenhuma transação recente</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// User Activity Chart
<?php
$userActivityData = [
  $userStats['new_24h'] ?? 0,
  $userStats['new_7d'] ?? 0,
  $userStats['active_24h'] ?? 0,
  $userStats['active_7d'] ?? 0
];
?>
const userActivityCtx = document.getElementById('userActivityChart');
if (userActivityCtx) {
  new Chart(userActivityCtx, {
    type: 'bar',
    data: {
      labels: ['Novos (24h)', 'Novos (7d)', 'Ativos (24h)', 'Ativos (7d)'],
      datasets: [{
        label: 'Usuários',
        data: <?= json_encode($userActivityData) ?>,
        backgroundColor: [
          'rgba(25, 135, 84, 0.7)',
          'rgba(25, 135, 84, 0.5)',
          'rgba(13, 110, 253, 0.7)',
          'rgba(13, 110, 253, 0.5)'
        ],
        borderColor: [
          'rgb(25, 135, 84)',
          'rgb(25, 135, 84)',
          'rgb(13, 110, 253)',
          'rgb(13, 110, 253)'
        ],
        borderWidth: 2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0 } }
      }
    }
  });
}

// XP Source Chart
<?php
$xpSourceLabels = [];
$xpSourceData = [];
foreach ($xpBySource as $src) {
  $xpSourceLabels[] = $src['source'] ?? 'unknown';
  $xpSourceData[] = (int)($src['total_xp'] ?? 0);
}
?>
const xpSourceCtx = document.getElementById('xpSourceChart');
if (xpSourceCtx) {
  new Chart(xpSourceCtx, {
    type: 'doughnut',
    data: {
      labels: <?= json_encode($xpSourceLabels) ?>,
      datasets: [{
        data: <?= json_encode($xpSourceData) ?>,
        backgroundColor: [
          'rgba(102, 126, 234, 0.8)',
          'rgba(118, 75, 162, 0.8)',
          'rgba(255, 193, 7, 0.8)',
          'rgba(13, 202, 240, 0.8)',
          'rgba(25, 135, 84, 0.8)',
          'rgba(220, 53, 69, 0.8)',
          'rgba(108, 117, 125, 0.8)'
        ],
        borderWidth: 2,
        borderColor: '#fff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom' }
      }
    }
  });
}
</script>

<?php
$content = ob_get_clean();
$scripts = '';
include __DIR__ . '/../layouts/app.php';
?>
