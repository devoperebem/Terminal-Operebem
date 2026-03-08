<?php
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0 d-flex align-items-center gap-2"><i class="fas fa-key"></i> Acessos (Aluno)</h1>
    <a href="/secure/adm/index" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
  </div>

  <div class="row g-3">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="row g-3 align-items-end">
            <div class="col-12 col-md-6">
              <label class="form-label">Selecionar usuário</label>
              <input type="text" class="form-control" id="userSearch" placeholder="Digite nome ou email..." autocomplete="off">
              <div id="userResults" class="list-group position-absolute mt-1 w-auto" style="z-index:20; min-width: 280px; display:none;"></div>
              <div class="form-text">Pesquise e selecione o usuário. O ID selecionado será aplicado aos formulários abaixo.</div>
            </div>
            <div class="col-12 col-md-6 d-flex align-items-end gap-2">
              <div class="small text-muted" id="selectedUserHint"><?php if (!empty($selectedUserId)): ?>Usuário selecionado: #<?= (int)$selectedUserId ?><?php endif; ?></div>
              <?php if (!empty($selectedUserId)): ?>
              <a class="btn btn-outline-secondary btn-sm" href="?user_id=<?= (int)$selectedUserId ?>">Recarregar acessos</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header">Conceder acesso por Curso</div>
        <div class="card-body">
          <form method="post" action="/secure/adm/aluno/access/grant-course" class="vstack gap-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="user_id" id="grantCourseUserId" value="<?= isset($selectedUserId)? (int)$selectedUserId : 0 ?>" />
            <input type="hidden" name="tz_offset" id="tz_offset_course" value="0" />
            <input type="hidden" name="ttl" id="ttl_course" value="" />
            <div>
              <label class="form-label">Curso</label>
              <select class="form-select" name="course_id" required>
                <option value="">Selecione...</option>
                <?php foreach (($courses ?? []) as $c): ?>
                  <option value="<?= (int)$c['id'] ?>" <?= (!empty($selectedCourseId) && (int)$selectedCourseId === (int)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars((string)$c['title'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="form-label">Duração rápida</label>
              <div class="btn-group" role="group" aria-label="Duração rápida">
                <button class="btn btn-outline-secondary btn-sm ttl-btn" data-target="ttl_course" data-ttl="3m" type="button">3 min</button>
                <button class="btn btn-outline-secondary btn-sm ttl-btn" data-target="ttl_course" data-ttl="7d" type="button">+7 dias</button>
                <button class="btn btn-outline-secondary btn-sm ttl-btn" data-target="ttl_course" data-ttl="30d" type="button">+30 dias</button>
                <button class="btn btn-outline-success btn-sm ttl-btn" data-target="ttl_course" data-ttl="lifetime" type="button">Vitalício</button>
              </div>
              <div class="form-text">Ou defina uma data/hora exata abaixo (horário local será convertido corretamente).</div>
            </div>
            <div class="row g-3 align-items-end">
              <div class="col-sm-7">
                <label class="form-label">Expira em (opcional)</label>
                <input type="datetime-local" class="form-control" name="expires_at" />
              </div>
              <div class="col-sm-5">
                <div class="form-check mt-4">
                  <input class="form-check-input" type="checkbox" name="lifetime" id="lifetime_course" />
                  <label class="form-check-label" for="lifetime_course">Vitalício</label>
                </div>
              </div>
            </div>
            <div>
              <button class="btn btn-primary" type="submit">Conceder acesso</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header">Conceder acesso por Aula</div>
        <div class="card-body">
          <form method="post" action="/secure/adm/aluno/access/grant-lesson" class="vstack gap-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="user_id" id="grantLessonUserId" value="<?= isset($selectedUserId)? (int)$selectedUserId : 0 ?>" />
            <input type="hidden" name="tz_offset" id="tz_offset_lesson" value="0" />
            <input type="hidden" name="ttl" id="ttl_lesson" value="" />
            <div>
              <label class="form-label">Curso</label>
              <select class="form-select" name="course_id" required onchange="location.href='?course_id='+this.value;">
                <option value="">Selecione...</option>
                <?php foreach (($courses ?? []) as $c): ?>
                  <option value="<?= (int)$c['id'] ?>" <?= (!empty($selectedCourseId) && (int)$selectedCourseId === (int)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars((string)$c['title'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="form-label">Duração rápida</label>
              <div class="btn-group" role="group" aria-label="Duração rápida">
                <button class="btn btn-outline-secondary btn-sm ttl-btn" data-target="ttl_lesson" data-ttl="3m" type="button">3 min</button>
                <button class="btn btn-outline-secondary btn-sm ttl-btn" data-target="ttl_lesson" data-ttl="7d" type="button">+7 dias</button>
                <button class="btn btn-outline-secondary btn-sm ttl-btn" data-target="ttl_lesson" data-ttl="30d" type="button">+30 dias</button>
                <button class="btn btn-outline-success btn-sm ttl-btn" data-target="ttl_lesson" data-ttl="lifetime" type="button">Vitalício</button>
              </div>
              <div class="form-text">Ou defina uma data/hora exata abaixo (horário local será convertido corretamente).</div>
            </div>
            <div>
              <label class="form-label">Aula</label>
              <select class="form-select" name="lesson_id" required>
                <option value="">Selecione...</option>
                <?php foreach (($lessons ?? []) as $l): ?>
                  <option value="<?= (int)$l['id'] ?>"><?php echo (int)$l['position'] . '. ' . htmlspecialchars((string)$l['title'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="row g-3 align-items-end">
              <div class="col-sm-7">
                <label class="form-label">Expira em (opcional)</label>
                <input type="datetime-local" class="form-control" name="expires_at" />
              </div>
              <div class="col-sm-5">
                <div class="form-check mt-4">
                  <input class="form-check-input" type="checkbox" name="lifetime" id="lifetime_lesson" />
                  <label class="form-check-label" for="lifetime_lesson">Vitalício</label>
                </div>
              </div>
            </div>
            <div>
              <button class="btn btn-primary" type="submit">Conceder acesso</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <?php if (!empty($selectedUserId)): ?>
  <div class="row g-3 mt-1">
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center"><span>Cursos com acesso</span><small class="text-muted">Usuário #<?= (int)$selectedUserId ?></small></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead><tr><th>Curso</th><th>Expira</th><th class="text-end">Ações</th></tr></thead>
              <tbody>
                <?php if (!empty($courseGrants)): foreach ($courseGrants as $g): ?>
                <tr>
                  <td><?= htmlspecialchars((string)$g['title'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string)($g['expires_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="text-end">
                    <form method="post" action="/secure/adm/aluno/access/revoke-course" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$selectedUserId ?>">
                      <input type="hidden" name="course_id" value="<?= (int)$g['course_id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger">Revogar</button>
                    </form>
                    <form method="post" action="/secure/adm/aluno/access/extend-course" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$selectedUserId ?>">
                      <input type="hidden" name="course_id" value="<?= (int)$g['course_id'] ?>">
                      <input type="hidden" name="mode" value="7d">
                      <button type="submit" class="btn btn-sm btn-outline-secondary">+7 dias</button>
                    </form>
                    <form method="post" action="/secure/adm/aluno/access/extend-course" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$selectedUserId ?>">
                      <input type="hidden" name="course_id" value="<?= (int)$g['course_id'] ?>">
                      <input type="hidden" name="mode" value="30d">
                      <button type="submit" class="btn btn-sm btn-outline-secondary">+30 dias</button>
                    </form>
                    <form method="post" action="/secure/adm/aluno/access/extend-course" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$selectedUserId ?>">
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
        <div class="card-header d-flex justify-content-between align-items-center"><span>Aulas com acesso</span><small class="text-muted">Usuário #<?= (int)$selectedUserId ?></small></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead><tr><th>Curso · Aula</th><th>Expira</th><th class="text-end">Ações</th></tr></thead>
              <tbody>
                <?php if (!empty($lessonGrants)): foreach ($lessonGrants as $g): ?>
                <tr>
                  <td><?= htmlspecialchars((string)$g['course_title'], ENT_QUOTES, 'UTF-8') ?> · #<?= (int)$g['position'] ?> <?= htmlspecialchars((string)$g['title'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string)($g['expires_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="text-end">
                    <form method="post" action="/secure/adm/aluno/access/revoke-lesson" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$selectedUserId ?>">
                      <input type="hidden" name="lesson_id" value="<?= (int)$g['lesson_id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger">Revogar</button>
                    </form>
                    <form method="post" action="/secure/adm/aluno/access/extend-lesson" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$selectedUserId ?>">
                      <input type="hidden" name="lesson_id" value="<?= (int)$g['lesson_id'] ?>">
                      <input type="hidden" name="mode" value="7d">
                      <button type="submit" class="btn btn-sm btn-outline-secondary">+7 dias</button>
                    </form>
                    <form method="post" action="/secure/adm/aluno/access/extend-lesson" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$selectedUserId ?>">
                      <input type="hidden" name="lesson_id" value="<?= (int)$g['lesson_id'] ?>">
                      <input type="hidden" name="mode" value="30d">
                      <button type="submit" class="btn btn-sm btn-outline-secondary">+30 dias</button>
                    </form>
                    <form method="post" action="/secure/adm/aluno/access/extend-lesson" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$selectedUserId ?>">
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
  <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
$scripts = <<<'SCRIPTS'
<script>
(function(){
  const inp = document.getElementById('userSearch');
  const res = document.getElementById('userResults');
  const hint = document.getElementById('selectedUserHint');
  const f1 = document.getElementById('grantCourseUserId');
  const f2 = document.getElementById('grantLessonUserId');
  // Set timezone offset (minutes from UTC)
  try {
    var tz = new Date().getTimezoneOffset();
    var tzc = document.getElementById('tz_offset_course'); if (tzc) tzc.value = String(tz);
    var tzl = document.getElementById('tz_offset_lesson'); if (tzl) tzl.value = String(tz);
  } catch(_){ }
  // TTL quick buttons
  document.querySelectorAll('.ttl-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
      var target = this.getAttribute('data-target'); var ttl = this.getAttribute('data-ttl');
      if (!target) return; var input = document.getElementById(target); if (!input) return;
      input.value = ttl || '';
      // Small visual feedback
      this.closest('.btn-group').querySelectorAll('.ttl-btn').forEach(b=>b.classList.remove('active'));
      this.classList.add('active');
    });
  });
  if (!inp || !res) return;
  let tmr = null;
  function hide(){ res.style.display='none'; res.innerHTML=''; }
  function select(u){ try{f1.value=u.id;f2.value=u.id;}catch(e){}; if(hint){ hint.textContent = 'Usuário selecionado: #'+u.id+' · '+u.name+' ('+u.email+')'; } hide(); }
  function fetchUsers(q){
    if (!q || q.length < 2) { hide(); return; }
    fetch('/api/admin/users/search?q='+encodeURIComponent(q))
      .then(r=>r.json()).then(j=>{
        const arr = (j && j.success && Array.isArray(j.data)) ? j.data : [];
        if (!arr.length) { hide(); return; }
        res.innerHTML = '';
        arr.forEach(u=>{
          const a = document.createElement('a');
          a.href = 'javascript:void(0)';
          a.className = 'list-group-item list-group-item-action';
          a.textContent = '#'+u.id+' · '+u.name+' ('+u.email+')';
          a.addEventListener('click', function(){ select(u); });
          res.appendChild(a);
        });
        const rect = inp.getBoundingClientRect();
        res.style.minWidth = rect.width+'px';
        res.style.display = 'block';
      }).catch(()=> hide());
  }
  inp.addEventListener('input', function(){ clearTimeout(tmr); const q = this.value.trim(); tmr = setTimeout(()=> fetchUsers(q), 180); });
  document.addEventListener('click', function(ev){ if (!res.contains(ev.target) && ev.target !== inp) hide(); });
})();
</script>
SCRIPTS;
include __DIR__ . '/../layouts/app.php';
?>
