<?php
ob_start();

// Garantir que todas as variáveis existam para evitar erros
$profile = $profile ?? ['id' => 0, 'name' => 'N/A', 'email' => '', 'cpf' => '', 'tier' => 'FREE', 'xp' => 0, 'streak' => 0, 'created_at' => date('Y-m-d H:i:s'), 'last_login_at' => null];
$stats = $stats ?? ['tickets_total' => 0, 'tickets_open' => 0, 'logins_total' => 0, 'logins_success_30d' => 0];
$courseGrants = $courseGrants ?? [];
$lessonGrants = $lessonGrants ?? [];
$progress = $progress ?? [];
$activeSubscription = $activeSubscription ?? null;

// Helper para formatar datas com segurança
$safeDate = function($dateString, $format = 'd/m/Y') {
    if (empty($dateString)) return '-';
    $timestamp = strtotime($dateString);
    if ($timestamp === false) return '-';
    return date($format, $timestamp);
};
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0">Usuário #<?= (int)$profile['id'] ?></h1>
    <div class="d-flex align-items-center gap-2">
      <a href="/secure/adm/users" class="btn btn-outline-secondary btn-sm">Voltar</a>
    </div>
  </div>

  <?php
    // Resolver avatar do usuário
    $avatarUrl = '';
    if (!empty($profile['id'])) {
      $root = dirname(__DIR__, 2); // src/Views -> novo_public_html
      $publicPath = $root . DIRECTORY_SEPARATOR . 'public';
      $uploadsDir = $publicPath . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars';
      $cands = [ $uploadsDir . DIRECTORY_SEPARATOR . $profile['id'] . '.png', $uploadsDir . DIRECTORY_SEPARATOR . $profile['id'] . '.jpg', $uploadsDir . DIRECTORY_SEPARATOR . $profile['id'] . '.jpeg', $uploadsDir . DIRECTORY_SEPARATOR . $profile['id'] . '.webp' ];
      foreach ($cands as $c) { if (is_file($c)) { $mtime = @filemtime($c) ?: time(); $avatarUrl = '/uploads/avatars/' . basename($c) . '?v=' . $mtime; break; } }
    }
    $avatarFallbackSvg = '/assets/images/user_image.png';
  ?>

  <div class="row g-3">
    <div class="col-12 col-lg-4">
      <div class="card h-100">
        <div class="card-body d-flex flex-column align-items-center text-center">
          <img src="<?= htmlspecialchars($avatarUrl ?: $avatarFallbackSvg, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" class="rounded-circle mb-3" style="width:120px;height:120px;object-fit:cover;border:1px solid var(--border-color);"/>
          <?php
            // Format CPF: 000.000.000-00
            $cpfRaw = preg_replace('/\D/', '', $profile['cpf'] ?? '');
            $cpfFormatted = $cpfRaw;
            if (strlen($cpfRaw) === 11) {
                $cpfFormatted = substr($cpfRaw, 0, 3) . '.' . substr($cpfRaw, 3, 3) . '.' . substr($cpfRaw, 6, 3) . '-' . substr($cpfRaw, 9, 2);
            }
            // Calculate age from birthday
            $birthday = $profile['birthday'] ?? $profile['birth_date'] ?? $profile['data_nascimento'] ?? null;
            $age = null;
            $birthdayFormatted = '-';
            if ($birthday && $birthday !== '0000-00-00') {
                $birthdayFormatted = date('d/m/Y', strtotime($birthday));
                $birthDate = new DateTime($birthday);
                $today = new DateTime();
                $age = $today->diff($birthDate)->y;
            }
          ?>
          <div class="w-100">
            <div class="input-group mb-2">
              <div class="form-floating flex-grow-1">
                <input type="text" class="form-control" id="uv_name" value="<?= htmlspecialchars($profile['name'] ?? '') ?>" readonly>
                <label for="uv_name">Nome</label>
              </div>
              <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('uv_name')" title="Copiar"><i class="fas fa-copy"></i></button>
            </div>
            <div class="input-group mb-2">
              <div class="form-floating flex-grow-1">
                <input type="email" class="form-control" id="uv_email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" readonly>
                <label for="uv_email">Email</label>
              </div>
              <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('uv_email')" title="Copiar"><i class="fas fa-copy"></i></button>
            </div>
            <div class="input-group mb-2">
              <div class="form-floating flex-grow-1">
                <input type="text" class="form-control" id="uv_cpf" value="<?= htmlspecialchars($cpfFormatted) ?>" readonly>
                <label for="uv_cpf">CPF</label>
              </div>
              <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('uv_cpf')" title="Copiar"><i class="fas fa-copy"></i></button>
            </div>
            <div class="form-floating mb-2">
              <input type="text" class="form-control" id="uv_birthday" value="<?= htmlspecialchars($birthdayFormatted) ?><?= $age !== null ? ' (' . $age . ' anos)' : '' ?>" readonly>
              <label for="uv_birthday">Data de Nascimento</label>
            </div>
            <div class="form-floating mb-2">
              <input type="text" class="form-control" id="uv_created" value="<?= $safeDate($profile['created_at'] ?? '', 'd/m/Y H:i') ?>" readonly>
              <label for="uv_created">Criado em</label>
            </div>
            <div class="form-floating">
              <input type="text" class="form-control" id="uv_lastlogin" value="<?= $safeDate($profile['last_login_at'] ?? '', 'd/m/Y H:i') ?>" readonly>
              <label for="uv_lastlogin">Último login</label>
            </div>
          </div>
          <div class="d-grid gap-2 w-100 mt-3">
            <a href="/secure/adm/users/edit?id=<?= (int)$profile['id'] ?>" class="btn btn-primary">Editar</a>
            <button type="button" class="btn btn-warning" 
                    data-bs-toggle="modal" 
                    data-bs-target="#resetPasswordModal">
              <i class="fas fa-key me-2"></i>Resetar Senha
            </button>
            <button type="button" class="btn btn-outline-danger" 
                    data-bs-toggle="modal" 
                    data-bs-target="#logoutAllDevicesModal">
              <i class="fas fa-sign-out-alt me-2"></i>Deslogar Todos Dispositivos
            </button>
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmDeleteUserModal" data-user-id="<?= (int)$profile['id'] ?>" data-user-name="<?= htmlspecialchars($profile['name'] ?? '') ?>">
              Excluir
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-8">
      <div class="card h-100">
        <div class="card-header">Estatísticas</div>
        <div class="card-body">
          <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
              <div class="card h-100">
                <div class="card-body py-3 text-center">
                  <div class="text-muted small">Tickets</div>
                  <div class="h5 mb-0"><?= (int)($stats['tickets_total'] ?? 0) ?></div>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="card h-100">
                <div class="card-body py-3 text-center">
                  <div class="text-muted small">Abertos</div>
                  <div class="h5 mb-0"><?= (int)($stats['tickets_open'] ?? 0) ?></div>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="card h-100">
                <div class="card-body py-3 text-center">
                  <div class="text-muted small">Logins total</div>
                  <div class="h5 mb-0"><?= (int)($stats['logins_total'] ?? 0) ?></div>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="card h-100">
                <div class="card-body py-3 text-center">
                  <div class="text-muted small">Logins (30d)</div>
                  <div class="h5 mb-0"><?= (int)($stats['logins_success_30d'] ?? 0) ?></div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Gamificação -->
          <div class="border-top pt-3">
            <h6 class="mb-3">Gamificação</h6>
            <div class="row g-3">
              <div class="col-6 col-md-3">
                <div class="card h-100 bg-primary bg-opacity-10">
                  <div class="card-body py-3 text-center">
                    <div class="text-muted small">XP Total</div>
                    <div class="h5 mb-0 text-primary"><?= number_format((int)($profile['xp'] ?? 0)) ?></div>
                  </div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="card h-100 bg-warning bg-opacity-10">
                  <div class="card-body py-3 text-center">
                    <div class="text-muted small">Nível</div>
                    <div class="h5 mb-0 text-warning"><?= (int)($profile['level'] ?? 1) ?></div>
                  </div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="card h-100 bg-danger bg-opacity-10">
                  <div class="card-body py-3 text-center">
                    <div class="text-muted small">Streak</div>
                    <div class="h5 mb-0 text-danger"><?= (int)($profile['streak'] ?? 0) ?> 🔥</div>
                  </div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="card h-100">
                  <div class="card-body py-3 text-center">
                    <a href="/secure/adm/gamification/user/<?= (int)$profile['id'] ?>" class="btn btn-sm btn-outline-primary w-100">
                      Ver Detalhes
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
        </div>
      </div>
    </div>
  </div>

  <!-- SECTION: Gerenciar Assinatura -->
  <div class="row g-3 mt-3">
    <div class="col-12">
      <div class="card">
        <div class="card-header bg-white">
          <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Gerenciar Assinatura</h5>
        </div>
<div class="card-body">
          <?php if ($activeSubscription): 
              $status = $activeSubscription['status'];
              $statusColors = [
                  'active' => 'bg-success',
                  'trialing' => 'bg-info',
                  'past_due' => 'bg-danger',
                  'canceled' => 'bg-secondary',
                  'incomplete' => 'bg-warning',
                  'incomplete_expired' => 'bg-secondary',
                  'unpaid' => 'bg-danger'
              ];
              $statusLabels = [
                  'active' => 'Ativa',
                  'trialing' => 'Em Período de Testes',
                  'past_due' => 'Vencida / Pendente',
                  'canceled' => 'Cancelada',
                  'incomplete' => 'Incompleta',
                  'incomplete_expired' => 'Expirada',
                  'unpaid' => 'Não Paga'
              ];
              $cardColor = $statusColors[$status] ?? 'bg-primary';
              $statusLabel = $statusLabels[$status] ?? strtoupper($status);
              
              $isTrial = $status === 'trialing';
              $isCanceled = $status === 'canceled';
              // Verifica se há cancelamento agendado (ex: status active mas com ends_at definido)
              $cancelAtPeriodEnd = false;
              $endsAtTimestamp = strtotime($activeSubscription['ends_at'] ?? '');
              if (!$isCanceled && $endsAtTimestamp !== false && $endsAtTimestamp > time()) {
                  $cancelAtPeriodEnd = true;
              }
          ?>
            <div class="card mb-0">
              <div class="card-header <?= $cardColor ?> text-white d-flex justify-content-between align-items-center">
                <span class="fw-bold">
                    <i class="fas fa-file-signature me-2"></i>
                    <?= $statusLabel ?> #<?= $activeSubscription['id'] ?>
                </span>
                <div class="d-flex gap-2">
                    <button type="button" onclick="syncSubscription()" class="btn btn-sm btn-outline-light" title="Sincronizar com Stripe">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <a href="/secure/adm/subscriptions/view?id=<?= $activeSubscription['id'] ?>" class="btn btn-sm btn-light text-dark shadow-sm" target="_blank">
                      Ver Detalhes <i class="fas fa-external-link-alt ms-1"></i>
                    </a>
                </div>
              </div>
              <div class="card-body">
                
                <?php if ($cancelAtPeriodEnd): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-clock me-2"></i>
                        <strong>Cancelamento Agendado!</strong> Acesso até <?= $safeDate($activeSubscription['ends_at'] ?? null) ?>.
                    </div>
                <?php endif; ?>

                <?php if ($isCanceled): ?>
                    <div class="alert alert-secondary">
                        <i class="fas fa-ban me-2"></i>
                        <strong>Assinatura Cancelada.</strong> O usuário não tem mais acesso aos benefícios.
                    </div>
                <?php endif; ?>

                <div class="row g-3 mb-3">
                  <div class="col-md-3">
                    <small class="text-muted d-block text-uppercase" style="font-size:0.75rem">Plano</small>
                    <div class="fw-bold fs-5"><?= htmlspecialchars($activeSubscription['plan_name'] ?? $activeSubscription['plan_slug']) ?></div>
                  </div>
                  <div class="col-md-3">
                    <small class="text-muted d-block text-uppercase" style="font-size:0.75rem">Data de Início</small>
                    <div class="fs-6"><?= $safeDate($activeSubscription['created_at'] ?? null) ?></div>
                  </div>

                  <?php if ($isTrial): 
                      $trialEnd = strtotime($activeSubscription['trial_end'] ?? '');
                      $daysLeft = $trialEnd ? ceil(($trialEnd - time()) / 86400) : 0;
                      $daysLeft = $daysLeft < 0 ? 0 : $daysLeft;
                  ?>
                  <div class="col-md-3">
                    <small class="text-muted d-block text-uppercase" style="font-size:0.75rem">Fim do Trial</small>
                    <div class="fw-bold fs-6 text-info">
                        <?= $trialEnd ? date('d/m/Y H:i', $trialEnd) : 'N/A' ?>
                        <?php if ($trialEnd): ?>
                             <span class="badge bg-info text-white ms-1"><?= $daysLeft ?> dias rest.</span>
                        <?php endif; ?>
                    </div>
                  </div>
                  <?php else: ?>
                  <div class="col-md-3">
                    <small class="text-muted d-block text-uppercase" style="font-size:0.75rem">Próxima Cobrança/Fim</small>
                    <div class="fs-6">
                      <?php if (!empty($activeSubscription['current_period_end'])): ?>
                        <?= $safeDate($activeSubscription['current_period_end'] ?? null) ?>
                      <?php elseif (!empty($activeSubscription['ends_at'])): ?>
                         <?= $safeDate($activeSubscription['ends_at'] ?? null) ?>
                      <?php else: ?>
                        —
                      <?php endif; ?>
                    </div>
                  </div>
                  <?php endif; ?>

                  <div class="col-md-3">
                    <small class="text-muted d-block text-uppercase" style="font-size:0.75rem">Stripe ID</small>
                    <div class="font-monospace small text-truncate" title="<?= htmlspecialchars($activeSubscription['stripe_subscription_id'] ?? 'Manual') ?>"><?= htmlspecialchars($activeSubscription['stripe_subscription_id'] ?? 'Manual') ?></div>
                  </div>
                </div>
                
                <div class="border-top pt-3">
                  <div class="d-flex flex-wrap gap-2">
                    <?php if (!$isCanceled && !$cancelAtPeriodEnd && in_array($activeSubscription['status'], ['active', 'trialing', 'past_due'])): ?>
                      <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelSubscriptionModal" data-subscription-id="<?= $activeSubscription['id'] ?>" data-user-name="<?= htmlspecialchars($profile['name']) ?>">
                        <i class="fas fa-times-circle me-1"></i>Cancelar Assinatura
                      </button>
                    <?php endif; ?>
                    
                    <?php if ($isTrial): ?>
                      <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#extendTrialModal" data-subscription-id="<?= $activeSubscription['id'] ?>" data-trial-end="<?= $activeSubscription['trial_end'] ?>" data-trial-extended-days="<?= (int)($activeSubscription['trial_extended_days'] ?? 0) ?>">
                        <i class="fas fa-calendar-plus me-1"></i>Estender Trial
                      </button>
                    <?php endif; ?>
                    
                    <a href="/secure/adm/subscriptions/payments?user_id=<?= $profile['id'] ?>" class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-cash-register me-1"></i>Ver Pagamentos
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php else: ?>
            <div class="alert alert-secondary mb-0 d-flex align-items-center justify-content-between">
              <div>
                  <i class="fas fa-info-circle me-2"></i>
                  Este usuário nunca teve uma assinatura registrada.
              </div>
              <a href="/secure/adm/subscriptions/grant?user_id=<?= $profile['id'] ?>" class="btn btn-sm btn-success ms-3">
                <i class="fas fa-crown me-1"></i>Conceder Tier Manual
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- SECTION: Histórico de Logs -->
  <div class="row g-3 mt-3">
    <div class="col-12">
      <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Histórico de Ações / Logs</h5>
        </div>
        <div class="card-body">
            <?php
            // Buscar logs do usuário
            try {
                $logs = \App\Services\AuditLogService::getUserLogs((int)$profile['id'], 20);
                $totalLogs = \App\Services\AuditLogService::countUserLogs((int)$profile['id']);
            } catch (\Throwable $e) {
                $logs = [];
                $totalLogs = 0;
            }
            ?>
            
            <?php if (empty($logs)): ?>
              <div class="alert alert-secondary mb-0">
                <i class="fas fa-info-circle me-2"></i>
                Nenhuma ação registrada para este usuário.
              </div>
            <?php else: ?>
                  <div class="timeline">
                    <?php foreach ($logs as $log):
                      try {
                        $formatted = \App\Services\AuditLogService::formatLogEntry($log);
                      } catch (\Throwable $e) {
                        continue; // Pula logs com erro de formatação
                      }
                    ?>
                      <div class="timeline-item mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-start">
                          <div class="flex-grow-1">
                            <div class="d-flex align-items-center mb-2">
                              <span class="badge <?= $formatted['badge_class'] ?> me-2">
                                <i class="fas <?= $formatted['icon_class'] ?> me-1"></i>
                                <?= htmlspecialchars($formatted['action_type']) ?>
                              </span>
                              <small class="text-muted">
                                <?= $formatted['created_at_relative'] ?>
                                (<?= $formatted['created_at_formatted'] ?>)
                              </small>
                            </div>
                            
                            <div class="mb-1">
                              <strong><?= $formatted['actor_name'] ?></strong>
                            </div>
                            
                            <?php if (!empty($formatted['description'])): ?>
                              <div class="text-muted small">
                                <?= htmlspecialchars($formatted['description']) ?>
                              </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($formatted['changes_decoded'])): ?>
                              <button type="button" class="btn btn-xs btn-outline-secondary mt-1"
                                      data-bs-toggle="modal"
                                      data-bs-target="#logDetailsModal"
                                      data-log-id="<?= $log['id'] ?>"
                                      data-log-changes='<?= htmlspecialchars($log['changes']) ?>'>
                                <i class="fas fa-eye me-1"></i>Ver Detalhes
                              </button>
                            <?php endif; ?>
                          </div>
                          
                          <div class="text-end">
                            <small class="text-muted d-block">
                              IP: <?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?>
                            </small>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  
                  <?php if ($totalLogs > 20): ?>
                    <div class="text-center mt-3">
                      <p class="text-muted mb-2">
                        Mostrando 20 de <?= number_format($totalLogs) ?> logs
                      </p>
                    </div>
                  <?php endif; ?>
            <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mt-1">
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center"><span>Acessos por Curso</span><small class="text-muted">Usuário #<?= (int)$profile['id'] ?></small></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead><tr><th>Curso</th><th>Expira</th><th>Status</th><th class="text-end">Ações</th></tr></thead>
              <tbody>
              <?php if (!empty($courseGrants)): foreach ($courseGrants as $g): ?>
                <tr>
                  <td><?= htmlspecialchars((string)$g['title'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string)($g['expires_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                  <td>
                    <?php
                    $exp = $g['expires_at'] ?? null;
                    $expTimestamp = strtotime((string)$exp);
                    $active = empty($exp) || ($expTimestamp !== false && $expTimestamp > time());
                    ?>
                    <?php if ($active): ?><span class="badge text-bg-success">Ativo</span><?php else: ?><span class="badge text-bg-secondary">Expirado</span><?php endif; ?>
                  </td>
                  <td class="text-end">
                    <form method="post" action="/secure/adm/aluno/access/revoke-course" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$profile['id'] ?>">
                      <input type="hidden" name="course_id" value="<?= (int)$g['course_id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger">Revogar</button>
                    </form>
                    <form method="post" action="/secure/adm/aluno/access/extend-course" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$profile['id'] ?>">
                      <input type="hidden" name="course_id" value="<?= (int)$g['course_id'] ?>">
                      <input type="hidden" name="mode" value="7d">
                      <button type="submit" class="btn btn-sm btn-outline-secondary">+7 dias</button>
                    </form>
                    <form method="post" action="/secure/adm/aluno/access/extend-course" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$profile['id'] ?>">
                      <input type="hidden" name="course_id" value="<?= (int)$g['course_id'] ?>">
                      <input type="hidden" name="mode" value="30d">
                      <button type="submit" class="btn btn-sm btn-outline-secondary">+30 dias</button>
                    </form>
                    <form method="post" action="/secure/adm/aluno/access/extend-course" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$profile['id'] ?>">
                      <input type="hidden" name="course_id" value="<?= (int)$g['course_id'] ?>">
                      <input type="hidden" name="mode" value="lifetime">
                      <button type="submit" class="btn btn-sm btn-outline-success">Vitalício</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="3" class="text-center text-muted">Sem concessões</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center"><span>Acessos por Aula</span><small class="text-muted">Usuário #<?= (int)$profile['id'] ?></small></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead><tr><th>Curso · Aula</th><th>Expira</th><th>Status</th><th class="text-end">Ações</th></tr></thead>
              <tbody>
              <?php if (!empty($lessonGrants)): foreach ($lessonGrants as $g): ?>
                <tr>
                  <td><?= htmlspecialchars((string)$g['course_title'], ENT_QUOTES, 'UTF-8') ?> · #<?= (int)$g['position'] ?> <?= htmlspecialchars((string)$g['title'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string)($g['expires_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                  <td>
                    <?php
                    $exp = $g['expires_at'] ?? null;
                    $expTimestamp = strtotime((string)$exp);
                    $active = empty($exp) || ($expTimestamp !== false && $expTimestamp > time());
                    ?>
                    <?php if ($active): ?><span class="badge text-bg-success">Ativo</span><?php else: ?><span class="badge text-bg-secondary">Expirado</span><?php endif; ?>
                  </td>
                  <td class="text-end">
                    <form method="post" action="/secure/adm/aluno/access/revoke-lesson" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$profile['id'] ?>">
                      <input type="hidden" name="lesson_id" value="<?= (int)$g['lesson_id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger">Revogar</button>
                    </form>
                    <form method="post" action="/secure/adm/aluno/access/extend-lesson" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$profile['id'] ?>">
                      <input type="hidden" name="lesson_id" value="<?= (int)$g['lesson_id'] ?>">
                      <input type="hidden" name="mode" value="7d">
                      <button type="submit" class="btn btn-sm btn-outline-secondary">+7 dias</button>
                    </form>
                    <form method="post" action="/secure/adm/aluno/access/extend-lesson" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$profile['id'] ?>">
                      <input type="hidden" name="lesson_id" value="<?= (int)$g['lesson_id'] ?>">
                      <input type="hidden" name="mode" value="30d">
                      <button type="submit" class="btn btn-sm btn-outline-secondary">+30 dias</button>
                    </form>
                    <form method="post" action="/secure/adm/aluno/access/extend-lesson" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$profile['id'] ?>">
                      <input type="hidden" name="lesson_id" value="<?= (int)$g['lesson_id'] ?>">
                      <input type="hidden" name="mode" value="lifetime">
                      <button type="submit" class="btn btn-sm btn-outline-success">Vitalício</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="3" class="text-center text-muted">Sem concessões</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mt-1">
    <div class="col-12">
      <div class="card">
        <div class="card-header">Progresso recente</div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead><tr><th>Curso</th><th>Aula</th><th>Posição</th><th>Duração</th><th>Último segundo</th><th>Concluído</th><th>Atualizado</th></tr></thead>
              <tbody>
              <?php if (!empty($progress)): foreach ($progress as $p): ?>
                <tr>
                  <td><?= htmlspecialchars((string)$p['course_title'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string)$p['title'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td>#<?= (int)$p['position'] ?></td>
                  <td><?= (int)floor(((int)($p['duration_seconds'] ?? 0))/60) ?> min</td>
                  <td><?= (int)($p['last_second'] ?? 0) ?> s</td>
                  <td><?= !empty($p['completed']) ? 'Sim' : 'Não' ?></td>
                  <td><?= htmlspecialchars((string)$p['updated_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="7" class="text-center text-muted">Sem progresso</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Cancelar Assinatura -->
<div class="modal fade" id="cancelSubscriptionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Cancelar Assinatura</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="/secure/adm/subscriptions/cancel">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <input type="hidden" name="subscription_id" id="cancel-subscription-id">
          <input type="hidden" name="user_id" value="<?= $profile['id'] ?>">
          
          <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Atenção!</strong> Esta ação irá cancelar a assinatura.
          </div>
          
          <p>Usuário: <strong id="cancel-user-name"></strong></p>
          
          <div class="mb-3">
            <label class="form-label fw-bold">Tipo de Cancelamento:</label>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="cancel_type" 
                     id="cancel-at-period-end" value="at_period_end" checked>
              <label class="form-check-label" for="cancel-at-period-end">
                <strong>Ao fim do período</strong>
                <small class="text-muted d-block">
                  Usuário mantém acesso até o fim do período pago
                </small>
              </label>
            </div>
            <div class="form-check mt-2">
              <input class="form-check-input" type="radio" name="cancel_type" 
                     id="cancel-immediately" value="immediately">
              <label class="form-check-label" for="cancel-immediately">
                <strong>Imediatamente</strong>
                <small class="text-muted d-block">
                  Usuário perde acesso agora (sem reembolso)
                </small>
              </label>
            </div>
          </div>
          
          <div class="mb-3">
            <label for="cancel-reason" class="form-label">Motivo do Cancelamento:</label>
            <textarea class="form-control" id="cancel-reason" name="reason" 
                      rows="3" placeholder="Ex: Solicitação do usuário, problema técnico..." required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
          <button type="submit" class="btn btn-danger">
            <i class="fas fa-times-circle me-2"></i>Confirmar Cancelamento
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Estender Trial -->
<div class="modal fade" id="extendTrialModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title">Estender Trial</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="/secure/adm/subscriptions/extend-trial">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <input type="hidden" name="subscription_id" id="extend-subscription-id">
          <input type="hidden" name="user_id" value="<?= $profile['id'] ?>">
          
          <div class="alert alert-info" id="extend-trial-info">
            <strong>Limite de Extensão:</strong>
            <ul class="mb-0">
              <li>Dias já estendidos: <strong><span id="extend-days-used">0</span> dias</strong></li>
              <li>Limite máximo: <strong>60 dias</strong></li>
              <li>Disponível para estender: <strong id="extend-days-available" class="text-success">60 dias</strong></li>
            </ul>
          </div>
          
          <div id="extend-trial-form">
            <p>Trial atual termina em: <strong id="extend-trial-current"></strong></p>
            
            <div class="mb-3">
              <label for="extend-days" class="form-label">Dias Adicionais:</label>
              <select class="form-select" id="extend-days" name="additional_days" required>
                <option value="7">7 dias</option>
                <option value="14">14 dias</option>
                <option value="30">30 dias</option>
                <option value="custom">Personalizado...</option>
              </select>
            </div>
            
            <div class="mb-3" id="custom-days-container" style="display: none;">
              <label for="custom-days" class="form-label">Quantidade de dias:</label>
              <input type="number" class="form-control" id="custom-days" 
                     name="custom_days" min="1" max="60">
            </div>
            
            <div class="mb-3">
              <label for="extend-reason" class="form-label">Motivo:</label>
              <textarea class="form-control" id="extend-reason" name="reason" 
                        rows="2" placeholder="Ex: Problema técnico, solicitação especial..." required></textarea>
            </div>
          </div>
          
          <div class="alert alert-danger" id="extend-trial-limit-reached" style="display: none;">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Limite atingido!</strong> Não é possível estender mais o trial.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-info text-white" id="btn-extend-trial">
            <i class="fas fa-calendar-plus me-2"></i>Estender Trial
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Resetar Senha -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title">Resetar Senha do Usuário</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="/secure/adm/users/reset-password">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <input type="hidden" name="user_id" value="<?= $profile['id'] ?>">
          
          <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Atenção!</strong> Esta ação irá:
            <ul class="mb-0 mt-2">
              <li>Gerar uma senha aleatória e segura</li>
              <li>Enviar a nova senha por email para: <strong><?= htmlspecialchars($profile['email']) ?></strong></li>
              <li>O usuário poderá alterar a senha depois pelo sistema</li>
            </ul>
          </div>
          
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="reset-logout-all" name="logout_all" checked>
            <label class="form-check-label" for="reset-logout-all">
              Deslogar o usuário de todos os dispositivos
            </label>
          </div>
          
          <div class="mb-3">
            <label for="reset-reason" class="form-label">Motivo:</label>
            <textarea class="form-control" id="reset-reason" name="reason" 
                      rows="2" placeholder="Ex: Usuário esqueceu a senha..." required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-warning text-dark">
            <i class="fas fa-key me-2"></i>Resetar Senha
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Deslogar Todos Dispositivos -->
<div class="modal fade" id="logoutAllDevicesModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Deslogar Todos os Dispositivos</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="/secure/adm/users/logout-all-devices">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <input type="hidden" name="user_id" value="<?= $profile['id'] ?>">
          
          <p>Esta ação irá <strong>deslogar o usuário de todos os dispositivos</strong> onde ele está logado.</p>
          
          <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Útil para casos de:
            <ul class="mb-0">
              <li>Suspeita de acesso não autorizado</li>
              <li>Usuário reportou problema de segurança</li>
              <li>Dispositivo perdido/roubado</li>
            </ul>
          </div>
          
          <div class="mb-3">
            <label for="logout-reason" class="form-label">Motivo:</label>
            <textarea class="form-control" id="logout-reason" name="reason" 
                      rows="2" placeholder="Ex: Suspeita de acesso indevido..." required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-danger">
            <i class="fas fa-sign-out-alt me-2"></i>Deslogar Todos
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Detalhes do Log -->
<div class="modal fade" id="logDetailsModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalhes das Mudanças</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <pre id="log-changes-content" class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;"></pre>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
$csrf = htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8');
ob_start();
?>
<div class="modal fade" id="confirmDeleteUserModal" tabindex="-1" aria-labelledby="confirmDeleteUserLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="/secure/adm/users/delete">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmDeleteUserLabel">Confirmar exclusão</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        Tem certeza que deseja excluir o usuário <span id="deleteUserName" class="fw-semibold">#</span>? Esta ação não pode ser desfeita.
      </div>
      <div class="modal-footer">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="id" id="deleteUserId" value="0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-danger">Excluir</button>
      </div>
    </form>
  </div>
</div>
<script>
(function(){
  var modal = document.getElementById('confirmDeleteUserModal');
  if (!modal) return;
  modal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var uid = button.getAttribute('data-user-id');
    var uname = button.getAttribute('data-user-name') || ('#'+uid);
    var idInput = modal.querySelector('#deleteUserId');
    var nameSpan = modal.querySelector('#deleteUserName');
    if (idInput) idInput.value = uid || '0';
    if (nameSpan) nameSpan.textContent = uname;
  });
  
  // Modal: Cancelar Assinatura
  var cancelModal = document.getElementById('cancelSubscriptionModal');
  if (cancelModal) {
    cancelModal.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget;
      var subscriptionId = button.getAttribute('data-subscription-id');
      var userName = button.getAttribute('data-user-name');
      
      document.getElementById('cancel-subscription-id').value = subscriptionId;
      document.getElementById('cancel-user-name').textContent = userName;
    });
  }
  
  // Modal: Estender Trial
  var extendModal = document.getElementById('extendTrialModal');
  if (extendModal) {
    extendModal.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget;
      var subscriptionId = button.getAttribute('data-subscription-id');
      var trialEnd = button.getAttribute('data-trial-end');
      var extendedDays = parseInt(button.getAttribute('data-trial-extended-days') || '0');
      var maxDays = 60;
      var remaining = maxDays - extendedDays;
      
      document.getElementById('extend-subscription-id').value = subscriptionId;
      document.getElementById('extend-trial-current').textContent = trialEnd ? new Date(trialEnd).toLocaleDateString('pt-BR') : 'N/A';
      document.getElementById('extend-days-used').textContent = extendedDays;
      document.getElementById('extend-days-available').textContent = remaining;
      
      var availableSpan = document.getElementById('extend-days-available');
      if (remaining <= 0) {
        availableSpan.className = 'text-danger';
        document.getElementById('extend-trial-form').style.display = 'none';
        document.getElementById('extend-trial-limit-reached').style.display = 'block';
        document.getElementById('btn-extend-trial').disabled = true;
      } else {
        availableSpan.className = 'text-success';
        document.getElementById('extend-trial-form').style.display = 'block';
        document.getElementById('extend-trial-limit-reached').style.display = 'none';
        document.getElementById('btn-extend-trial').disabled = false;
        
        // Atualizar opções do select
        var select = document.getElementById('extend-days');
        select.innerHTML = '';
        if (remaining >= 7) select.innerHTML += '<option value="7">7 dias</option>';
        if (remaining >= 14) select.innerHTML += '<option value="14">14 dias</option>';
        if (remaining >= 30) select.innerHTML += '<option value="30">30 dias</option>';
        select.innerHTML += '<option value="custom">Personalizado (max: ' + remaining + ' dias)</option>';
        
        // Atualizar max do input custom
        document.getElementById('custom-days').max = remaining;
      }
    });
    
    // Toggle custom days input
    document.getElementById('extend-days').addEventListener('change', function() {
      var container = document.getElementById('custom-days-container');
      container.style.display = this.value === 'custom' ? 'block' : 'none';
    });
  }
  
  // Modal: Detalhes do Log
  var logModal = document.getElementById('logDetailsModal');
  if (logModal) {
    logModal.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget;
      var changes = button.getAttribute('data-log-changes');
      
      try {
        var parsed = JSON.parse(changes);
        document.getElementById('log-changes-content').textContent = JSON.stringify(parsed, null, 2);
      } catch (e) {
        document.getElementById('log-changes-content').textContent = changes;
      }
    });
  }

  // Copy to clipboard function
  window.copyToClipboard = function(inputId) {
    var input = document.getElementById(inputId);
    if (!input) return;
    navigator.clipboard.writeText(input.value).then(function() {
      var btn = input.closest('.input-group').querySelector('button');
      if (btn) {
        var originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check text-success"></i>';
        setTimeout(function() { btn.innerHTML = originalHtml; }, 1500);
      }
    }).catch(function() {
      input.select();
      document.execCommand('copy');
    });
  };

  // Sincronizar Subscription (Global)
  window.syncSubscription = function() {
      if(!confirm('Deseja forçar a atualização dos dados da assinatura com o Stripe?')) return;
      
      const formData = new FormData();
      formData.append('user_id', '<?= $profile['id'] ?>');
      formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
      
      fetch('/secure/adm/subscriptions/sync', {
          method: 'POST',
          body: formData
      })
      .then(r => r.json())
      .then(data => {
          if(data.success) {
              alert('Sincronização realizada com sucesso!');
              location.reload();
          } else {
              alert('Erro ao sincronizar: ' + (data.error || 'Erro desconhecido'));
          }
      })
      .catch(err => alert('Erro de conexão'));
  };
})();
</script>
<?php
$scripts = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>
