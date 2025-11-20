<?php
?>
<div class="container py-4">
  <h1 class="mb-4">Integração Aluno</h1>
  <?php if (!empty($error)): ?>
    <div class="alert alert-danger">Falha na conexão com o banco do Aluno: <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
  <?php elseif (!empty($ok)): ?>
    <div class="alert alert-success">Conexão com o banco do Aluno estabelecida com sucesso.</div>
  <?php else: ?>
    <div class="alert alert-warning">Conexão não verificada.</div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header">Estatísticas básicas</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <div class="p-3 border rounded">
            <div class="text-muted small">Cursos</div>
            <div class="fs-4 fw-bold"><?= (int)($stats['courses'] ?? 0) ?></div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="p-3 border rounded">
            <div class="text-muted small">Matrículas</div>
            <div class="fs-4 fw-bold"><?= (int)($stats['enrollments'] ?? 0) ?></div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="p-3 border rounded">
            <div class="text-muted small">Usuários</div>
            <div class="fs-4 fw-bold"><?= (int)($stats['users_total'] ?? 0) ?></div>
          </div>
        </div>
      </div>
      <?php if (empty($ok)): ?>
        <hr/>
        <p class="mb-2">Para ativar a integração, configure as variáveis de ambiente no Terminal:</p>
        <pre class="bg-light p-3 rounded"><code>ALUNO_DB_HOST=147.93.35.184
ALUNO_DB_PORT=5432
ALUNO_DB_DATABASE=aluno_db
ALUNO_DB_USERNAME=aluno_app
ALUNO_DB_PASSWORD=********</code></pre>
        <p class="mb-0 text-muted">Após definir, recarregue esta página. Em seguida podemos liberar telas de gestão (Cursos, Aulas, Matrículas) dentro do Secure Admin.</p>
      <?php else: ?>
        <hr/>
        <a href="/secure/adm/aluno/courses" class="btn btn-primary">Ir para Cursos (Aluno)</a>
      <?php endif; ?>
    </div>
  </div>
</div>
