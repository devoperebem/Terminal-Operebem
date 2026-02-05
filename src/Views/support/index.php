<?php
ob_start();
$ok = $ok ?? 0;
$prefillName = htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8');
$prefillEmail = htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8');
$prefillCpf = htmlspecialchars($cpf ?? '', ENT_QUOTES, 'UTF-8');
$csrf = htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8');
?>

<?php if (empty($user)): ?>
<header class="modern-header">
  <nav class="navbar navbar-expand-lg">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="/">
        <div class="brand-icon me-2">
          <img src="/assets/images/favicon.png" alt="" width="28" height="28" style="border-radius:6px" />
        </div>
        <span class="brand-text">Terminal Operebem</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupport">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarSupport">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="/">Início</a></li>
          <li class="nav-item"><a class="nav-link" href="/#faq">FAQ</a></li>
        </ul>
      </div>
    </div>
  </nav>
</header>
<div class="header-spacer"></div>
<?php endif; ?>

<div class="container my-4">
  <div class="row">
    <div class="col-12 mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div>
        <h1 class="h3 mb-1">Central de Suporte</h1>
        <p class="text-muted mb-0">Fale conosco pelo WhatsApp ou abra um ticket pelo formulário abaixo.</p>
      </div>
      <?php if (!empty($user)): ?>
      <div>
        <a href="/app/support" class="btn btn-outline-primary"><i class="fas fa-inbox me-2"></i>Meus tickets</a>
      </div>
      <?php endif; ?>
    </div>
    <div class="col-12">
      <?php if ($ok): ?>
        <div class="alert alert-success alert-auto-dismiss"><i class="fas fa-check-circle me-2"></i>Ticket enviado com sucesso! Nossa equipe retornará em breve.</div>
      <?php endif; ?>
      <?php if (!empty($err) || !empty($errMsg)): ?>
        <div class="alert alert-danger alert-auto-dismiss"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($errMsg ?: 'Preencha todos os campos obrigatórios.', ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Suporte via WhatsApp</h5>
          <p class="text-muted">Atendimento rápido de segunda à sexta, 9h às 18h.</p>
          <?php
            $waNumber = $_ENV['WHATSAPP_SUPPORT_NUMBER'] ?? '5599999999999';
            $waText = rawurlencode('Olá! Preciso de ajuda no Terminal Operebem.' . ($prefillCpf ? "\nCPF: {$prefillCpf}" : ''));
          ?>
          <a href="https://wa.me/<?= htmlspecialchars($waNumber, ENT_QUOTES, 'UTF-8') ?>?text=<?= $waText ?>" target="_blank" class="btn btn-success w-100">
            <i class="fab fa-whatsapp me-2"></i>Chamar no WhatsApp
          </a>
        </div>
      </div>
      <!-- FAQ de suporte -->
      <div class="card mt-4">
        <div class="card-body">
          <h5 class="card-title">FAQ de Suporte</h5>
          <div class="accordion" id="supportFaq">
            <div class="accordion-item">
              <h2 class="accordion-header" id="sfaq1">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#sfaq1c" aria-expanded="true" aria-controls="sfaq1c">
                  Como exporto os dados do Dashboard para Excel?
                </button>
              </h2>
              <div id="sfaq1c" class="accordion-collapse collapse show" data-bs-parent="#supportFaq">
                <div class="accordion-body text-secondary">
                  No Dashboard, clique no seu avatar (canto superior direito) e selecione <strong>Exportar Excel do Dashboard</strong>. O arquivo é gerado em uma única aba, organizado por seções. No momento, o Excel não recebe atualizações em tempo real.
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header" id="sfaq2">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sfaq2c" aria-expanded="false" aria-controls="sfaq2c">
                  Não consigo fazer login
                </button>
              </h2>
              <div id="sfaq2c" class="accordion-collapse collapse" data-bs-parent="#supportFaq">
                <div class="accordion-body text-secondary">
                  Verifique e-mail/senha e o teclado (Caps Lock). Se persistir, abra um ticket com detalhes (print e horário) para analisarmos os logs.
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header" id="sfaq3">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sfaq3c" aria-expanded="false" aria-controls="sfaq3c">
                  Captcha não valida no celular
                </button>
              </h2>
              <div id="sfaq3c" class="accordion-collapse collapse" data-bs-parent="#supportFaq">
                <div class="accordion-body text-secondary">
                  Arraste a peça de forma contínua (evite toques muito rápidos). Se errar, clique em <em>Novo Puzzle</em>.
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header" id="sfaq4">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sfaq4c" aria-expanded="false" aria-controls="sfaq4c">
                  Em quanto tempo o suporte responde?
                </button>
              </h2>
              <div id="sfaq4c" class="accordion-collapse collapse" data-bs-parent="#supportFaq">
                <div class="accordion-body text-secondary">
                  Atendemos em dias úteis das 9h às 18h. O primeiro retorno costuma ocorrer no mesmo dia (ou até 24–48h em períodos de alta demanda).
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header" id="sfaq5">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sfaq5c" aria-expanded="false" aria-controls="#sfaq5c">
                  Onde acompanho meus tickets?
                </button>
              </h2>
              <div id="sfaq5c" class="accordion-collapse collapse" data-bs-parent="#supportFaq">
                <div class="accordion-body text-secondary">Acesse <strong>App → Suporte</strong> para visualizar e responder seus tickets.</div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header" id="sfaq6">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sfaq6c" aria-expanded="false" aria-controls="#sfaq6c">
                  Privacidade e dados
                </button>
              </h2>
              <div id="sfaq6c" class="accordion-collapse collapse" data-bs-parent="#supportFaq">
                <div class="accordion-body text-secondary">Consulte nossa <a href="/privacy" target="_blank" rel="noopener">Política de Privacidade</a>. Seguimos boas práticas de segurança alinhadas à ISO 27001.</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Abrir ticket</h5>
          <form method="POST" action="/support" class="row g-3 needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>" />
            <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;opacity:0;height:1px;width:1px" aria-hidden="true">
            <div class="col-md-6">
              <label class="form-label">Nome</label>
              <input type="text" name="name" class="form-control" value="<?= $prefillName ?>" <?= !empty($user) ? 'readonly' : '' ?> required>
              <div class="invalid-feedback">Informe seu nome.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" value="<?= $prefillEmail ?>" <?= !empty($user) ? 'readonly' : '' ?> required>
              <div class="invalid-feedback">Informe um email válido.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Categoria</label>
              <select name="category" class="form-select" required>
                <option value="">Selecione</option>
                <option value="conta">Conta e Acesso</option>
                <option value="planos">Planos e Pagamentos</option>
                <option value="dados">Dados e Cotações</option>
                <option value="bugs">Erros e Bugs</option>
                <option value="sugestoes">Sugestões</option>
                <option value="outros">Outros</option>
              </select>
              <div class="invalid-feedback">Selecione uma categoria.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Assunto</label>
              <input type="text" name="subject" class="form-control" placeholder="Resumo em até 120 caracteres" minlength="6" maxlength="120" required>
              <div class="invalid-feedback">Informe um assunto.</div>
            </div>
            <div class="col-12">
              <label class="form-label">Mensagem</label>
              <textarea name="message" class="form-control" rows="6" placeholder="Descreva o problema, passos para reproduzir e prints (se possível)." minlength="12" maxlength="3000" required></textarea>
              <div class="form-text"><span id="msg-count">0</span>/3000</div>
              <div class="invalid-feedback">Descreva sua solicitação.</div>
            </div>
            <div class="col-12 d-flex justify-content-end">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane me-2"></i>Enviar ticket
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
$scripts = <<<'SCRIPTS'
<script>
document.addEventListener('DOMContentLoaded', function(){
  var f=document.querySelector('form[action="/support"]');
  if(!f) return;
  var email=f.querySelector('input[name="email"]');
  var cat=f.querySelector('select[name="category"]');
  var subject=f.querySelector('input[name="subject"]');
  var msg=f.querySelector('textarea[name="message"]');
  var counter=document.getElementById('msg-count');
  // Restaurar rascunho
  try{
    var draft=JSON.parse(localStorage.getItem('support_form_draft_v1')||'{}');
    if (draft && typeof draft==='object'){
      if (draft.subject && subject && !subject.value) subject.value=draft.subject;
      if (draft.message && msg && !msg.value) msg.value=draft.message;
      if (draft.category && cat && !cat.value) cat.value=draft.category;
    }
  }catch(_){ }
  function persist(){
    try{
      var d={ subject: (subject&&subject.value||'').trim(), message:(msg&&msg.value||'').trim(), category:(cat&&cat.value||'') };
      localStorage.setItem('support_form_draft_v1', JSON.stringify(d));
    }catch(_){ }
  }
  function updateCount(){
    if(!msg||!counter) return; counter.textContent = (msg.value||'').length;
  }
  f.addEventListener('submit', function(ev){
    var ok=true;
    var vmail=(email&&email.value||'').trim();
    if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(vmail)){ if(email) email.classList.add('is-invalid'); ok=false; }
    if(cat && (cat.value||'')===''){ cat.classList.add('is-invalid'); ok=false; }
    if(subject && (subject.value||'').trim().length<6){ subject.classList.add('is-invalid'); ok=false; }
    if(msg && (msg.value||'').trim().length<12){ msg.classList.add('is-invalid'); ok=false; }
    if(!ok){ ev.preventDefault(); ev.stopPropagation(); }
    else { try{ localStorage.removeItem('support_form_draft_v1'); }catch(_){ } }
  });
  ['change','input'].forEach(function(evt){
    if(cat){ cat.addEventListener(evt, function(){ cat.classList.remove('is-invalid'); }); }
    if(email){ email.addEventListener(evt, function(){ email.classList.remove('is-invalid'); }); }
    if(subject){ subject.addEventListener(evt, function(){ subject.classList.remove('is-invalid'); persist(); }); }
    if(msg){ msg.addEventListener(evt, function(){ msg.classList.remove('is-invalid'); updateCount(); persist(); }); }
  });
  updateCount();
});
</script>
SCRIPTS;
include __DIR__ . '/../layouts/app.php';
?>
