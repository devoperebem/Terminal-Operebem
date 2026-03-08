<?php
ob_start();
?>
<style>
/* Admin Dashboard Styles */
.adm-dash { max-width: 1280px; margin: 0 auto; padding: 1.5rem 1rem; }
@media (min-width: 768px) { .adm-dash { padding: 2rem 2rem; } }
@media (min-width: 1200px) { .adm-dash { padding: 2rem 2.5rem; } }

.adm-dash .dash-header { margin-bottom: 1.5rem; }
.adm-dash .dash-header h1 { font-size: 1.5rem; font-weight: 700; color: var(--text-primary); }
.adm-dash .dash-header .dash-meta { font-size: 0.8rem; color: var(--text-secondary); }

/* Quick Actions Grid */
.quick-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-bottom: 1.5rem; }
@media (min-width: 576px) { .quick-actions { grid-template-columns: repeat(3, 1fr); gap: 0.625rem; } }
@media (min-width: 768px) { .quick-actions { grid-template-columns: repeat(6, 1fr); gap: 0.75rem; } }

.qa-link {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 0.35rem; padding: 0.75rem 0.25rem; border-radius: 10px; text-decoration: none;
  background: var(--card-bg); border: 1px solid var(--border-color);
  color: var(--text-primary); font-size: 0.75rem; font-weight: 500;
  transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
  text-align: center; line-height: 1.2;
}
.qa-link:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-color: #667eea; color: var(--text-primary); text-decoration: none; }
.qa-link .qa-icon { font-size: 1.25rem; line-height: 1; }

/* Status pills row */
.status-row { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1.5rem; }
.status-pill {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.5rem 0.875rem; border-radius: 8px; font-size: 0.8rem; font-weight: 500;
  background: var(--card-bg); border: 1px solid var(--border-color);
  color: var(--text-primary); white-space: nowrap;
}
.status-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.status-dot.ok { background: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,0.2); }
.status-dot.warn { background: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.2); }
.status-dot.fail { background: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,0.2); }

/* Metric Cards */
.metrics-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; margin-bottom: 1.5rem; }
@media (min-width: 768px) { .metrics-grid { grid-template-columns: repeat(4, 1fr); } }

.metric-card {
  background: var(--card-bg); border: 1px solid var(--border-color);
  border-radius: 12px; padding: 1rem 1.125rem; position: relative; overflow: hidden;
  transition: transform 0.15s, box-shadow 0.15s;
}
.metric-card:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
.metric-card .mc-accent { position: absolute; left: 0; top: 0; bottom: 0; width: 4px; border-radius: 4px 0 0 4px; }
.metric-card .mc-label { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary); margin-bottom: 0.375rem; }
.metric-card .mc-value { font-size: 1.5rem; font-weight: 700; line-height: 1.1; margin-bottom: 0.25rem; }
.metric-card .mc-sub { font-size: 0.75rem; color: var(--text-secondary); }
@media (max-width: 575.98px) {
  .metric-card { padding: 0.75rem 0.875rem; }
  .metric-card .mc-value { font-size: 1.25rem; }
}

/* Section card */
.dash-card {
  background: var(--card-bg); border: 1px solid var(--border-color);
  border-radius: 12px; overflow: hidden;
}
.dash-card .dc-header {
  padding: 0.75rem 1rem; font-size: 0.85rem; font-weight: 600;
  color: var(--text-primary); border-bottom: 1px solid var(--border-color);
  display: flex; justify-content: space-between; align-items: center;
}
.dash-card .dc-header a { font-size: 0.75rem; color: var(--text-secondary); text-decoration: none; }
.dash-card .dc-header a:hover { color: var(--text-primary); }
.dash-card .dc-body { padding: 1rem; }
.dash-card .dc-body.p-0 { padding: 0; }

/* Chart containers */
.chart-box { position: relative; height: 260px; }
@media (max-width: 767.98px) { .chart-box { height: 220px; } }

/* Compact table */
.tbl-compact { width: 100%; font-size: 0.8rem; }
.tbl-compact th { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; color: var(--text-secondary); padding: 0.5rem 0.75rem; border-bottom: 2px solid var(--border-color); }
.tbl-compact td { padding: 0.5rem 0.75rem; border-bottom: 1px solid var(--border-color); vertical-align: middle; color: var(--text-primary); }
.tbl-compact tbody tr:last-child td { border-bottom: none; }
.tbl-compact tbody tr:hover { background: rgba(102,126,234,0.04); }
.tbl-compact a { color: var(--text-primary); text-decoration: none; }
.tbl-compact a:hover { color: #667eea; }

/* XP mini cards */
.xp-mini-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; }
@media (min-width: 768px) { .xp-mini-grid { grid-template-columns: repeat(4, 1fr); } }
.xp-mini {
  background: var(--card-bg); border: 1px solid var(--border-color);
  border-radius: 10px; padding: 0.875rem; text-align: center;
}
.xp-mini .xm-label { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; color: var(--text-secondary); margin-bottom: 0.25rem; }
.xp-mini .xm-value { font-size: 1.25rem; font-weight: 700; line-height: 1.2; }
.xp-mini .xm-sub { font-size: 0.7rem; color: var(--text-secondary); margin-top: 0.125rem; }
</style>

<div class="adm-dash">
  <!-- Quick Actions (only items NOT in sidebar) -->
  <div class="quick-actions">
    <a href="/secure/adm/tickets" class="qa-link">
      <span class="qa-icon"><i class="fas fa-headset" style="color:#6b7280;"></i></span>
      <span>Tickets</span>
    </a>
    <a href="/secure/adm/gamification" class="qa-link">
      <span class="qa-icon"><i class="fas fa-gamepad" style="color:#06b6d4;"></i></span>
      <span>XP</span>
    </a>
    <a href="/secure/adm/crm" class="qa-link">
      <span class="qa-icon"><i class="fas fa-address-card" style="color:#22c55e;"></i></span>
      <span>CRM</span>
    </a>
    <a href="/secure/adm/reviews" class="qa-link">
      <span class="qa-icon"><i class="fas fa-star" style="color:#f59e0b;"></i></span>
      <span>Reviews</span>
    </a>
    <a href="/secure/adm/coupons" class="qa-link">
      <span class="qa-icon"><i class="fas fa-ticket" style="color:#64748b;"></i></span>
      <span>Cupons</span>
    </a>
    <a href="/secure/adm/aluno" class="qa-link">
      <span class="qa-icon"><i class="fas fa-graduation-cap" style="color:#ec4899;"></i></span>
      <span>Portal</span>
    </a>
    <a href="/secure/adm/cdn" class="qa-link">
      <span class="qa-icon"><i class="fas fa-cloud" style="color:#3b82f6;"></i></span>
      <span>CDN</span>
    </a>
  </div>

  <!-- System Status -->
  <div class="status-row">
    <div class="status-pill">
      <span class="status-dot <?= !empty($status['db_ok']) ? 'ok' : 'fail' ?>"></span>
      Banco de Dados
    </div>
    <div class="status-pill">
      <span class="status-dot <?= !empty($status['smtp_config']) ? 'ok' : 'warn' ?>"></span>
      SMTP
    </div>
    <div class="status-pill">
      <span class="status-dot <?= !empty($status['recaptcha_site_key']) ? 'ok' : 'warn' ?>"></span>
      reCAPTCHA
    </div>
    <div class="status-pill">
      <span class="status-dot <?= !empty($status['jwt_secret']) ? 'ok' : 'fail' ?>"></span>
      JWT Secret
    </div>
    <a href="/secure/adm/status" class="status-pill" style="text-decoration:none; color: var(--text-secondary); font-size:0.75rem;">
      <i class="fas fa-external-link-alt"></i> Detalhes
    </a>
  </div>

  <!-- Main Metrics -->
  <div class="metrics-grid">
    <div class="metric-card">
      <div class="mc-accent" style="background:#3b82f6;"></div>
      <div class="mc-label">Usuarios</div>
      <div class="mc-value" style="color:#3b82f6;"><?= number_format($counts['users_total'] ?? 0, 0, ',', '.') ?></div>
      <div class="mc-sub"><?= $userStats['verified'] ?? 0 ?> verificados</div>
    </div>
    <div class="metric-card">
      <div class="mc-accent" style="background:#f59e0b;"></div>
      <div class="mc-label">Tickets Abertos</div>
      <div class="mc-value" style="color:#f59e0b;"><?= $counts['tickets_open'] ?? 0 ?></div>
      <div class="mc-sub">suporte ativo</div>
    </div>
    <div class="metric-card">
      <div class="mc-accent" style="background:#22c55e;"></div>
      <div class="mc-label">Logins (24h)</div>
      <div class="mc-value" style="color:#22c55e;"><?= $counts['logins_24h'] ?? 0 ?></div>
      <div class="mc-sub">tentativas</div>
    </div>
    <div class="metric-card">
      <div class="mc-accent" style="background:#06b6d4;"></div>
      <div class="mc-label">Administradores</div>
      <div class="mc-value" style="color:#06b6d4;"><?= $counts['admins_total'] ?? 0 ?></div>
      <div class="mc-sub">ativos</div>
    </div>
  </div>

  <!-- Charts -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
      <div class="dash-card">
        <div class="dc-header">
          <span><i class="fas fa-chart-bar me-2"></i>Atividade de Usuarios</span>
        </div>
        <div class="dc-body">
          <div class="chart-box">
            <canvas id="userActivityChart"></canvas>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="dash-card">
        <div class="dc-header">
          <span><i class="fas fa-chart-pie me-2"></i>Plano por Usuario</span>
          <a href="/secure/adm/plans">Gerenciar</a>
        </div>
        <div class="dc-body">
          <div class="chart-box">
            <canvas id="planDistChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- XP, Discord & Gamification -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
      <div class="xp-mini-grid mb-0">
        <div class="xp-mini">
          <div class="xm-label">XP Total</div>
          <div class="xm-value" style="color:#667eea;"><?= number_format($xpStats['total_xp'] ?? 0, 0, ',', '.') ?></div>
          <div class="xm-sub">Media: <?= $xpStats['avg_xp'] ?? 0 ?> XP</div>
        </div>
        <div class="xp-mini">
          <div class="xm-label">Nivel Medio</div>
          <div class="xm-value" style="color:#f59e0b;"><?= $xpStats['avg_level'] ?? 0 ?></div>
          <div class="xm-sub">Max: <?= $xpStats['max_level'] ?? 0 ?></div>
        </div>
        <div class="xp-mini">
          <div class="xm-label">Streak Medio</div>
          <div class="xm-value" style="color:#ef4444;"><?= $xpStats['avg_streak'] ?? 0 ?></div>
          <div class="xm-sub">Max: <?= $xpStats['max_streak'] ?? 0 ?></div>
        </div>
        <div class="xp-mini">
          <div class="xm-label">Discord</div>
          <div class="xm-value" style="color:#5865F2;"><?= $discordStats['verified_users'] ?? 0 ?></div>
          <div class="xm-sub"><?= $discordStats['pending_users'] ?? 0 ?> pendentes</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="dash-card" style="height:100%;">
        <div class="dc-header">
          <span><i class="fas fa-chart-pie me-2" style="color:#667eea;"></i>XP por Fonte (7d)</span>
          <a href="/secure/adm/gamification">Ver XP</a>
        </div>
        <div class="dc-body">
          <?php if (!empty($xpBySource)): ?>
          <div style="position:relative; height:180px;">
            <canvas id="xpSourceChart"></canvas>
          </div>
          <?php else: ?>
          <p class="text-muted text-center py-4 mb-0" style="font-size:0.85rem;"><i class="fas fa-info-circle me-1"></i>Sem dados de XP nos ultimos 7 dias</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Top Users & Recent XP -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
      <div class="dash-card">
        <div class="dc-header">
          <span><i class="fas fa-trophy me-2" style="color:#f59e0b;"></i>Top 10 por XP</span>
          <a href="/secure/adm/gamification">Ver todos</a>
        </div>
        <div class="dc-body p-0">
          <?php if (!empty($topUsers)): ?>
          <div class="table-responsive">
            <table class="tbl-compact">
              <thead>
                <tr>
                  <th style="width:40px;">#</th>
                  <th>Nome</th>
                  <th class="text-center">Lv</th>
                  <th class="text-end">XP</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($topUsers as $idx => $u): ?>
                <tr>
                  <td>
                    <?php if ($idx === 0): ?><span style="font-size:1rem;">&#129351;</span>
                    <?php elseif ($idx === 1): ?><span style="font-size:1rem;">&#129352;</span>
                    <?php elseif ($idx === 2): ?><span style="font-size:1rem;">&#129353;</span>
                    <?php else: ?><?= $idx + 1 ?>
                    <?php endif; ?>
                  </td>
                  <td><a href="/secure/adm/users/view?id=<?= $u['id'] ?>"><?= htmlspecialchars($u['name'] ?? 'N/A') ?></a></td>
                  <td class="text-center"><span class="badge badge-level"><?= $u['level'] ?? 0 ?></span></td>
                  <td class="text-end fw-bold"><?= number_format($u['xp'] ?? 0, 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <p class="text-muted text-center py-3 mb-0" style="font-size:0.85rem;">Nenhum usuario encontrado</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="dash-card">
        <div class="dc-header">
          <span><i class="fas fa-bolt me-2" style="color:#8b5cf6;"></i>XP Recentes</span>
          <a href="/secure/adm/gamification">Ver todos</a>
        </div>
        <div class="dc-body p-0">
          <?php if (!empty($recentXP)): ?>
          <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
            <table class="tbl-compact">
              <thead>
                <tr>
                  <th>Usuario</th>
                  <th>Fonte</th>
                  <th class="text-end">XP</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (array_slice($recentXP, 0, 10) as $xp): ?>
                <tr>
                  <td><a href="/secure/adm/users/view?id=<?= $xp['user_id'] ?>"><?= htmlspecialchars(mb_substr($xp['user_name'] ?? 'N/A', 0, 22)) ?></a></td>
                  <td><span class="badge badge-xp" style="font-size:0.7rem;"><?= htmlspecialchars($xp['source'] ?? 'unknown') ?></span></td>
                  <td class="text-end">
                    <span class="badge <?= ($xp['amount'] ?? 0) > 0 ? 'bg-success' : 'bg-danger' ?>" style="font-size:0.7rem;">
                      <?= ($xp['amount'] ?? 0) > 0 ? '+' : '' ?><?= $xp['amount'] ?? 0 ?>
                    </span>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <p class="text-muted text-center py-3 mb-0" style="font-size:0.85rem;">Nenhuma transacao recente</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <!-- Date/Time Footer -->
  <div class="text-center text-muted mt-4 mb-2" style="font-size:0.8rem;">
    <i class="fas fa-clock me-1"></i><?= date('d/m/Y H:i') ?>
  </div>
</div>

<script>
<?php
$userActivityData = [
  $userStats['new_24h'] ?? 0,
  $userStats['new_7d'] ?? 0,
  $userStats['active_24h'] ?? 0,
  $userStats['active_7d'] ?? 0,
  $userStats['inactive_30d'] ?? 0,
  $userStats['never_logged'] ?? 0
];
?>
const userActivityCtx = document.getElementById('userActivityChart');
if (userActivityCtx) {
  new Chart(userActivityCtx, {
    type: 'bar',
    data: {
      labels: ['Novos (24h)', 'Novos (7d)', 'Ativos (24h)', 'Ativos (7d)', 'Inativos (30d)', 'Nunca logaram'],
      datasets: [{
        label: 'Usuarios',
        data: <?= json_encode($userActivityData) ?>,
        backgroundColor: ['rgba(34,197,94,0.7)','rgba(34,197,94,0.45)','rgba(59,130,246,0.7)','rgba(59,130,246,0.45)','rgba(239,68,68,0.5)','rgba(148,163,184,0.5)'],
        borderColor: ['#22c55e','#22c55e','#3b82f6','#3b82f6','#ef4444','#94a3b8'],
        borderWidth: 2,
        borderRadius: 6,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } }, grid: { color: 'rgba(128,128,128,0.1)' } },
        x: { ticks: { font: { size: 10 } }, grid: { display: false } }
      }
    }
  });
}

<?php
// Plan Distribution chart data
$planLabels = [];
$planData = [];
$planColors = ['rgba(34,197,94,0.8)', 'rgba(59,130,246,0.8)', 'rgba(139,92,246,0.8)', 'rgba(245,158,11,0.8)', 'rgba(6,182,212,0.8)', 'rgba(239,68,68,0.8)'];
foreach ($planDistribution as $pd) {
  $planLabels[] = $pd['tier'] ?? 'FREE';
  $planData[] = (int)($pd['total'] ?? 0);
}
?>
const planDistCtx = document.getElementById('planDistChart');
if (planDistCtx) {
  new Chart(planDistCtx, {
    type: 'doughnut',
    data: {
      labels: <?= json_encode($planLabels) ?>,
      datasets: [{
        data: <?= json_encode($planData) ?>,
        backgroundColor: <?= json_encode(array_slice($planColors, 0, max(count($planLabels), 1))) ?>,
        borderWidth: 2,
        borderColor: 'var(--card-bg, #fff)'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12, usePointStyle: true } } }
    }
  });
}

<?php
// XP by Source chart (moved here)
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
        backgroundColor: ['rgba(102,126,234,0.8)','rgba(139,92,246,0.8)','rgba(245,158,11,0.8)','rgba(6,182,212,0.8)','rgba(34,197,94,0.8)','rgba(239,68,68,0.8)','rgba(100,116,139,0.8)'],
        borderWidth: 2,
        borderColor: 'var(--card-bg, #fff)'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom', labels: { font: { size: 10 }, padding: 8, usePointStyle: true } } }
    }
  });
}
</script>

<?php
$content = ob_get_clean();
$scripts = '';
include __DIR__ . '/../layouts/app.php';
?>
