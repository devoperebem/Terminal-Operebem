<?php
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0 d-flex align-items-center gap-2">Portal do Aluno (Admin)</h1>
    <a href="/secure/adm/aluno/status" class="btn btn-outline-secondary btn-sm">Status</a>
  </div>

  <div class="row g-3">
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card h-100">
        <div class="card-body d-grid gap-2">
          <h2 class="h6">Cursos</h2>
          <a href="/secure/adm/aluno/courses" class="btn btn-outline-primary">Listar cursos</a>
          <a href="/secure/adm/aluno/courses/create" class="btn btn-outline-secondary">Novo curso</a>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card h-100">
        <div class="card-body d-grid gap-2">
          <h2 class="h6">Aulas</h2>
          <a href="/secure/adm/aluno/lessons" class="btn btn-outline-primary">Gerenciar aulas</a>
          <a href="/secure/adm/aluno/bunny" class="btn btn-outline-secondary">Ferramentas Bunny</a>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card h-100">
        <div class="card-body d-grid gap-2">
          <h2 class="h6">Acessos</h2>
          <a href="/secure/adm/aluno/access" class="btn btn-outline-primary">Gerenciar acessos</a>
          <a href="/secure/adm/aluno/enrollments" class="btn btn-outline-secondary">Matrículas</a>
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
