<?php
ob_start();
?>
<div class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><i class="fas fa-headset me-2"></i>Tickets de Suporte</h1>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-lg-6">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Criar novo ticket</h5>
          <form method="POST" action="/secure/adm/support/create" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div class="col-12">
              <label class="form-label">Usuário (ID) - opcional</label>
              <input type="number" name="user_id" class="form-control" placeholder="Ex.: 123">
              <div class="form-text">Se informar o ID de usuário, nome/email/CPF serão preenchidos automaticamente.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Nome</label>
              <input type="text" name="name" class="form-control" placeholder="Nome do contato">
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" placeholder="email@dominio.com">
            </div>
            <div class="col-md-6">
              <label class="form-label">CPF (opcional)</label>
              <input type="text" name="cpf" class="form-control" placeholder="000.000.000-00">
            </div>
            <div class="col-12">
              <label class="form-label">Assunto</label>
              <input type="text" name="subject" class="form-control" required>
            </div>
            <div class="col-12">
              <label class="form-label">Mensagem inicial</label>
              <textarea name="message" rows="3" class="form-control" required></textarea>
            </div>
            <div class="col-12 d-flex justify-content-end">
              <button class="btn btn-primary"><i class="fas fa-plus me-2"></i>Criar ticket</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Resumo</h5>
          <div class="d-flex gap-2 flex-wrap">
            <span class="badge bg-success">Abertos: <?= isset($openTickets) ? count($openTickets) : 0 ?></span>
            <span class="badge bg-secondary">Fechados: <?= isset($closedTickets) ? count($closedTickets) : 0 ?></span>
            <span class="badge bg-info">Total: <?= isset($tickets) ? count($tickets) : 0 ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if (empty($tickets)): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Nenhum ticket encontrado.</div>
  <?php endif; ?>

  <ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-open" type="button" role="tab">
        <i class="fas fa-folder-open me-2"></i>Abertos (<?= isset($openTickets) ? count($openTickets) : 0 ?>)
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-closed" type="button" role="tab">
        <i class="fas fa-archive me-2"></i>Fechados (<?= isset($closedTickets) ? count($closedTickets) : 0 ?>)
      </button>
    </li>
  </ul>

  <div class="tab-content">
    <div class="tab-pane fade show active" id="tab-open" role="tabpanel">
      <?php foreach (($openTickets ?? []) as $t): $tid = (int)$t['id']; ?>
      <div class="card mb-3" id="ticket-<?= $tid ?>">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center gap-3">
            <strong>#<?= $tid ?></strong>
            <span><?= htmlspecialchars($t['subject']) ?></span>
          </div>
          <div class="d-flex align-items-center gap-3">
            <span class="badge bg-success text-uppercase">aberto</span>
            <small class="text-muted"><?= htmlspecialchars($t['created_at']) ?></small>
          </div>
        </div>
        <div class="card-body">
          <div class="mb-2 small text-muted">
            <i class="fas fa-user me-1"></i><?= htmlspecialchars($t['name']) ?>
            • <a href="mailto:<?= htmlspecialchars($t['email']) ?>"><?= htmlspecialchars($t['email']) ?></a>
            <?php if(!empty($t['cpf'])): ?> • CPF: <?= htmlspecialchars($t['cpf']) ?><?php endif; ?>
            <?php if(!empty($t['user_id'])): ?> • User ID: <?= (int)$t['user_id'] ?><?php endif; ?>
          </div>

          <div class="border rounded p-2 mb-3" style="max-height: 280px; overflow:auto;">
            <div class="mb-2">
              <div class="small text-muted"><i class="fas fa-envelope me-1"></i>Ticket • <?= htmlspecialchars($t['created_at']) ?></div>
              <div><?= nl2br(htmlspecialchars($t['message'])) ?></div>
            </div>
            <?php if (!empty($messages[$tid])): foreach ($messages[$tid] as $m): ?>
              <div class="mb-2">
                <div class="small text-muted">
                  <?php if ($m['sender_type']==='user'): ?>
                    <i class="fas fa-user me-1"></i>Usuário
                  <?php elseif ($m['sender_type']==='admin'): ?>
                    <i class="fas fa-shield-alt me-1"></i>Admin
                  <?php else: ?>
                    <i class="fas fa-robot me-1"></i>Sistema
                  <?php endif; ?>
                  • <?= htmlspecialchars($m['created_at']) ?>
                </div>
                <div><?= nl2br(htmlspecialchars($m['message'])) ?></div>
              </div>
            <?php endforeach; endif; ?>
          </div>

          <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mt-2">
            <form method="POST" action="/secure/adm/support/close" class="m-0 p-0">
              <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
              <input type="hidden" name="ticket_id" value="<?= $tid ?>">
              <button class="btn btn-outline-danger"><i class="fas fa-lock me-2"></i>Fechar ticket</button>
            </form>
            <form method="POST" action="/secure/adm/support/reply" class="flex-grow-1">
              <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
              <input type="hidden" name="ticket_id" value="<?= $tid ?>">
              <div class="mb-2">
                <label class="form-label">Responder como admin</label>
                <textarea name="message" class="form-control" rows="3" required></textarea>
              </div>
              <div class="d-flex justify-content-end">
                <button class="btn btn-primary"><i class="fas fa-paper-plane me-2"></i>Enviar</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="tab-pane fade" id="tab-closed" role="tabpanel">
      <?php foreach (($closedTickets ?? []) as $t): $tid = (int)$t['id']; ?>
      <div class="card mb-3" id="ticket-<?= $tid ?>">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center gap-3">
            <strong>#<?= $tid ?></strong>
            <span><?= htmlspecialchars($t['subject']) ?></span>
          </div>
          <div class="d-flex align-items-center gap-3">
            <span class="badge bg-secondary text-uppercase">fechado</span>
            <small class="text-muted"><?= htmlspecialchars($t['created_at']) ?></small>
          </div>
        </div>
        <div class="card-body">
          <div class="mb-2 small text-muted">
            <i class="fas fa-user me-1"></i><?= htmlspecialchars($t['name']) ?>
            • <a href="mailto:<?= htmlspecialchars($t['email']) ?>"><?= htmlspecialchars($t['email']) ?></a>
            <?php if(!empty($t['cpf'])): ?> • CPF: <?= htmlspecialchars($t['cpf']) ?><?php endif; ?>
            <?php if(!empty($t['user_id'])): ?> • User ID: <?= (int)$t['user_id'] ?><?php endif; ?>
          </div>
          <div class="border rounded p-2 mb-3" style="max-height: 280px; overflow:auto;">
            <div class="mb-2">
              <div class="small text-muted"><i class="fas fa-envelope me-1"></i>Ticket • <?= htmlspecialchars($t['created_at']) ?></div>
              <div><?= nl2br(htmlspecialchars($t['message'])) ?></div>
            </div>
            <?php if (!empty($messages[$tid])): foreach ($messages[$tid] as $m): ?>
              <div class="mb-2">
                <div class="small text-muted">
                  <?php if ($m['sender_type']==='user'): ?>
                    <i class="fas fa-user me-1"></i>Usuário
                  <?php elseif ($m['sender_type']==='admin'): ?>
                    <i class="fas fa-shield-alt me-1"></i>Admin
                  <?php else: ?>
                    <i class="fas fa-robot me-1"></i>Sistema
                  <?php endif; ?>
                  • <?= htmlspecialchars($m['created_at']) ?>
                </div>
                <div><?= nl2br(htmlspecialchars($m['message'])) ?></div>
              </div>
            <?php endforeach; endif; ?>
          </div>
          <form method="POST" action="/secure/adm/support/reopen" class="mt-2">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="ticket_id" value="<?= $tid ?>">
            <button class="btn btn-outline-primary"><i class="fas fa-unlock me-2"></i>Reabrir ticket</button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
$scripts = '';
include __DIR__ . '/../layouts/app.php';
?>
