<?php
ob_start();
$ok = $ok ?? '';
$err = $err ?? '';
$meta = $meta ?? ['created'=>0,'updated'=>0,'course_id'=>0];
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0 d-flex align-items-center gap-2"><i class="fas fa-video"></i> Aluno · Bunny Tools</h1>
    <a href="/secure/adm/aluno/courses" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
  </div>
  <?php if ($ok): ?>
    <div class="alert alert-success">
      
      <?php if ($ok === 'import'): ?>Importação concluída. Criadas: <?= (int)$meta['created'] ?> · Atualizadas: <?= (int)$meta['updated'] ?> · Curso #<?= (int)$meta['course_id'] ?>
      <?php elseif ($ok === 'import_default'): ?>Coleções padrão importadas (verifique cursos para detalhes).
      <?php elseif ($ok === 'refresh'): ?>Timestamps de thumbnails atualizados; o cache será renovado.
      <?php else: ?>Operação concluída com sucesso.<?php endif; ?>
    </div>
  <?php endif; ?>
  <?php if ($err): ?>
    <div class="alert alert-danger">
      <?php if ($err === 'csrf'): ?>CSRF inválido. Recarregue a página.
      <?php elseif ($err === 'bunny_env'): ?>Credenciais da Bunny ausentes no .env.
      <?php elseif ($err === 'missing'): ?>Informe nome da coleção ou ID.
      <?php elseif ($err === 'empty'): ?>Coleção não encontrada ou sem vídeos.
      <?php elseif ($err === 'create_course'): ?>Falha ao criar curso.
      <?php elseif ($err === 'tx'): ?>Falha de transação.
      <?php elseif ($err === 'refresh'): ?>Falha ao atualizar timestamps.
      <?php else: ?>Erro na operação.<?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Importar por coleção</span>
        </div>
        <div class="card-body">
          <form method="post" action="/secure/adm/aluno/bunny/import-collection" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>"/>
            <div class="col-12">
              <label class="form-label">Nome da coleção (ou ID)</label>
              <input type="text" class="form-control" name="name" placeholder="Ex.: Trading System Vencedor"/>
            </div>
            <div class="col-12">
              <label class="form-label">Collection ID (opcional)</label>
              <input type="text" class="form-control" name="collection_id" placeholder="GUID da coleção"/>
            </div>
            <div class="col-12">
              <label class="form-label">Curso alvo (opcional)</label>
              <select class="form-select" name="course_id">
                <option value="0">(Criar novo curso automaticamente)</option>
                <?php foreach (($courses ?? []) as $c): ?>
                  <option value="<?= (int)$c['id'] ?>">#<?= (int)$c['id'] ?> · <?= htmlspecialchars((string)$c['title']) ?> <?= !empty($c['bunny_collection_id']) ? '· ['.htmlspecialchars((string)$c['bunny_collection_id']).']' : '' ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12 d-flex gap-2">
              <button type="submit" class="btn btn-primary">Importar</button>
              <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('importDefaultForm').submit();">Importar Padrão</button>
            </div>
          </form>
          <form id="importDefaultForm" method="post" action="/secure/adm/aluno/bunny/import-default" class="d-none">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>"/>
          </form>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Thumbnails e Cache</span>
        </div>
        <div class="card-body">
          <form method="post" action="/secure/adm/aluno/bunny/refresh-thumbnails" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>"/>
            <div class="col-12">
              <label class="form-label">Curso (opcional)</label>
              <select class="form-select" name="course_id">
                <option value="0">Todos os cursos</option>
                <?php foreach (($courses ?? []) as $c): ?>
                  <option value="<?= (int)$c['id'] ?>">#<?= (int)$c['id'] ?> · <?= htmlspecialchars((string)$c['title']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12 d-flex gap-2">
              <button type="submit" class="btn btn-outline-primary">Atualizar timestamps</button>
            </div>
          </form>
          <div class="small text-muted mt-3">Isto força cache-busting nos thumbnails do Bunny via parâmetro de ciclo.
          </div>
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
