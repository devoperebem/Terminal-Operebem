<?php
ob_start();
?>
<div class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><i class="fas fa-inbox me-2"></i>Meus Tickets</h1>
    <a href="/support" class="btn btn-outline-primary"><i class="fas fa-headset me-2"></i>Abrir novo ticket</a>
  </div>

  <?php if (!empty($ok)): ?>
    <div class="alert alert-success alert-auto-dismiss"><i class="fas fa-check-circle me-2"></i>Mensagem enviada com sucesso.</div>
  <?php endif; ?>

  <?php if (empty($tickets)): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Você ainda não possui tickets. Abra um novo para falar com nossa equipe.</div>
  <?php endif; ?>

  <?php foreach (($tickets ?? []) as $t): $tid = (int)$t['id']; ?>
    <div class="card mb-3" id="ticket-<?= $tid ?>">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <strong>#<?= $tid ?></strong>
          <span class="ms-2"><?= htmlspecialchars($t['subject']) ?></span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-<?= $t['status']==='open'?'success':'secondary' ?> text-uppercase"><?= htmlspecialchars($t['status']) ?></span>
          <small class="text-muted"><?= htmlspecialchars($t['created_at']) ?></small>
        </div>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <div class="fw-semibold mb-2">Mensagens</div>
          <div class="border rounded p-2" style="max-height: 280px; overflow:auto;">
            <?php if (!empty($messages[$tid])): foreach ($messages[$tid] as $m): ?>
              <div class="mb-2">
                <div class="small text-muted">
                  <?php if ($m['sender_type']==='user'): ?>
                    <i class="fas fa-user me-1"></i>Você
                  <?php elseif ($m['sender_type']==='admin'): ?>
                    <i class="fas fa-shield-alt me-1"></i>Suporte
                  <?php else: ?>
                    <i class="fas fa-robot me-1"></i>Sistema
                  <?php endif; ?>
                  • <?= htmlspecialchars($m['created_at']) ?>
                </div>
                <div><?= nl2br(htmlspecialchars($m['message'])) ?></div>
              </div>
            <?php endforeach; else: ?>
              <div class="text-muted">Sem mensagens ainda.</div>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($t['status']==='open'): ?>
        <form method="POST" action="/app/support/reply" class="mt-3">
          <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
          <input type="hidden" name="ticket_id" value="<?= $tid ?>">
          <div class="mb-2">
            <label class="form-label">Sua mensagem</label>
            <textarea name="message" class="form-control" rows="3" required></textarea>
          </div>
          <div class="d-flex justify-content-end">
            <button class="btn btn-primary"><i class="fas fa-paper-plane me-2"></i>Enviar</button>
          </div>
        </form>
        <?php else: ?>
          <div class="alert alert-secondary mb-0"><i class="fas fa-lock me-2"></i>Este ticket está encerrado.</div>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php
$content = ob_get_clean();
$scripts = '';
include __DIR__ . '/../layouts/app.php';
?>
