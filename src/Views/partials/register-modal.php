<?php
// Shared Register Modal (5-step flow)
$countryDialCodes = [];
$countryCodesPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'country-codes.json';
if (is_file($countryCodesPath)) {
    $countryDialCodes = json_decode(file_get_contents($countryCodesPath), true) ?: [];
}
if (!$countryDialCodes) {
    $countryDialCodes = [
        ['country' => 'Brasil', 'dial_code' => '+55', 'iso' => 'BR'],
        ['country' => 'Estados Unidos', 'dial_code' => '+1', 'iso' => 'US'],
        ['country' => 'Portugal', 'dial_code' => '+351', 'iso' => 'PT'],
        ['country' => 'Espanha', 'dial_code' => '+34', 'iso' => 'ES'],
        ['country' => 'Reino Unido', 'dial_code' => '+44', 'iso' => 'GB'],
    ];
}
usort($countryDialCodes, function ($a, $b) {
    if (($a['dial_code'] ?? '') === '+55') {
        return ($b['dial_code'] ?? '') === '+55' ? 0 : -1;
    }
    if (($b['dial_code'] ?? '') === '+55') {
        return 1;
    }
    return strcmp($a['country'] ?? '', $b['country'] ?? '');
});
$defaultDialCode = '+55';

if (!function_exists('country_flag_url')) {
    function country_flag_url(?string $iso): string
    {
        $iso = strtolower(trim($iso ?? ''));
        if (strlen($iso) !== 2) {
            return 'https://flagcdn.com/24x18/un.png';
        }
        return "https://flagcdn.com/24x18/{$iso}.png";
    }
}

$defaultCountry = null;
foreach ($countryDialCodes as $dialEntry) {
    if (($dialEntry['dial_code'] ?? '') === $defaultDialCode) {
        $defaultCountry = $dialEntry;
        break;
    }
}
if (!$defaultCountry && isset($countryDialCodes[0])) {
    $defaultCountry = $countryDialCodes[0];
}
$defaultIso = $defaultCountry['iso'] ?? '';
$defaultLabel = trim(($defaultCountry['country'] ?? 'País') . ' (' . ($defaultCountry['dial_code'] ?? '') . ')');
$defaultDisplay = trim($defaultCountry['dial_code'] ?? '') ?: $defaultLabel;
$defaultFlagUrl = country_flag_url($defaultIso);
?>
<style>
  #cpf::placeholder { font-style: normal; }
  #cpf::-webkit-input-placeholder { font-style: normal; }
  #cpf::-moz-placeholder { font-style: normal; }
  #cpf:-ms-input-placeholder { font-style: normal; }
  #cpf:-moz-placeholder { font-style: normal; }
  .country-code-select {
    flex: 0 0 200px;
    max-width: 200px;
  }
  .visually-hidden {
    position: absolute !important;
    width: 1px; height: 1px;
    padding: 0; margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
  }
  #registerModalBody {
    position: relative;
    overflow: visible;
  }
  #registerModalBody.phone-confirm-visible > *:not(.phone-confirm-overlay) {
    filter: blur(3px);
    pointer-events: none;
    user-select: none;
  }
  .phone-confirm-overlay {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    z-index: 20;
  }
  .phone-confirm-card {
    width: 100%;
    max-width: 420px;
    background: #ffffff;
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 20px 45px rgba(15, 23, 42, 0.25);
  }
  .register-close-btn,
  .register-back-btn {
    filter: invert(0);
  }
  html.dark .register-close-btn,
  html.dark-blue .register-close-btn,
  html.all-black .register-close-btn,
  html.dark .register-back-btn,
  html.dark-blue .register-back-btn,
  html.all-black .register-back-btn {
    filter: invert(1);
  }
  .register-back-btn {
    width: 1em;
    height: 1em;
    padding: 0.75rem;
    border-radius: 0.375rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: transparent;
  }
  .register-back-btn::before {
    content: '\f060';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    font-size: 0.9rem;
  }

  .country-select-container {
    position: relative;
    flex: 0 0 200px;
    max-width: 210px;
    margin-right: 0.5rem;
  }
  .country-select-toggle {
    display: flex;
    align-items: center;
    width: 100%;
    border: 1px solid #ced4da;
    border-radius: 0.5rem;
    padding: 0.4rem 0.75rem;
    background: #fff;
    gap: 0.5rem;
    height: 100%;
  }
  html.dark .country-select-toggle,
  html.dark-blue .country-select-toggle,
  html.all-black .country-select-toggle {
    background: #1f2937;
    border-color: #374151;
    color: #f9fafb;
  }
  .country-select-toggle:focus {
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25);
  }
  .country-select-flag {
    width: 24px;
    height: 18px;
    border-radius: 2px;
    object-fit: cover;
    box-shadow: 0 0 0 1px rgba(15,23,42,0.15);
  }
  .country-select-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    width: 320px;
    max-width: 90vw;
    background: #ffffff;
    border-radius: 0.75rem;
    box-shadow: 0 20px 35px rgba(15,23,42,0.2);
    z-index: 30;
    padding: 1rem;
  }
  html.dark .country-select-dropdown,
  html.dark-blue .country-select-dropdown,
  html.all-black .country-select-dropdown {
    background: #111827;
    color: #f3f4f6;
    box-shadow: 0 20px 35px rgba(0,0,0,0.6);
  }
  .country-select-search input {
    width: 100%;
  }
  html.dark .country-select-search input,
  html.dark-blue .country-select-search input,
  html.all-black .country-select-search input {
    background: #1f2937;
    border-color: #374151;
    color: #f9fafb;
  }
  html.dark .country-select-search input::placeholder,
  html.dark-blue .country-select-search input::placeholder,
  html.all-black .country-select-search input::placeholder {
    color: #9ca3af;
  }
  .country-select-options {
    max-height: 260px;
    overflow-y: auto;
    margin-top: 0.75rem;
  }
  .country-select-option {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: none;
    background: transparent;
    padding: 0.4rem 0.25rem;
    border-radius: 0.5rem;
    text-align: left;
    color: inherit;
  }
  .country-select-option.d-none {
    display: none;
  }
  .country-select-option:hover,
  .country-select-option:focus {
    background: rgba(59,130,246,0.12);
    outline: none;
  }
  .country-select-option img {
    width: 24px;
    height: 18px;
    border-radius: 2px;
    object-fit: cover;
  }
  .country-select-option span {
    flex: 1;
  }
  @media (max-width: 576px) {
    .country-select-container {
      flex: 0 0 150px;
      max-width: 150px;
    }
    .country-select-dropdown {
      width: 260px;
    }
  }
  html.dark .phone-confirm-card,
  html.dark-blue .phone-confirm-card,
  html.all-black .phone-confirm-card {
    background: #1f2937;
    color: #f9fafb;
  }
  html.dark #registerModal .modal-content,
  html.dark-blue #registerModal .modal-content,
  html.all-black #registerModal .modal-content {
    background: #1f2937;
    color: #f9fafb;
  }
  html.dark #registerModal .modal-header,
  html.dark-blue #registerModal .modal-header,
  html.all-black #registerModal .modal-header {
    border-bottom-color: #374151;
  }
  html.dark #registerModal .input-group-text,
  html.dark-blue #registerModal .input-group-text,
  html.all-black #registerModal .input-group-text {
    background: #374151;
    border-color: #374151;
    color: #f9fafb;
  }
  html.dark #registerModal .form-control,
  html.dark-blue #registerModal .form-control,
  html.all-black #registerModal .form-control {
    background: #1f2937;
    border-color: #374151;
    color: #f9fafb;
  }
  html.dark #registerModal .form-control::placeholder,
  html.dark-blue #registerModal .form-control::placeholder,
  html.all-black #registerModal .form-control::placeholder {
    color: #9ca3af;
  }
  html.dark #registerModal .form-control:focus,
  html.dark-blue #registerModal .form-control:focus,
  html.all-black #registerModal .form-control:focus {
    background: #1f2937;
    border-color: #3b82f6;
    color: #f9fafb;
  }
  html.dark #registerModal .text-muted,
  html.dark-blue #registerModal .text-muted,
  html.all-black #registerModal .text-muted {
    color: #9ca3af !important;
  }
  html.dark #registerModal .form-text,
  html.dark-blue #registerModal .form-text,
  html.all-black #registerModal .form-text {
    color: #9ca3af;
  }
</style>
<div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title" id="registerModalLabel">
          <i class="fas fa-user-plus me-2"></i>Criar Nova Conta
        </h5>
        <div class="d-flex align-items-center gap-2">
          <button type="button" class="btn-close register-back-btn" id="registerBackBtn" aria-label="Voltar"></button>
          <button type="button" class="btn-close register-close-btn" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      </div>
      <div class="modal-body" id="registerModalBody">
        <div id="registerAlertContainer"></div>

        <!-- Step 1: Telefone -->
        <div id="step1" class="register-step">
          <div class="text-center mb-5">
            <div class="step-indicator">
              <span class="badge bg-primary">1</span><span class="mx-2">→</span>
              <span class="badge bg-secondary">2</span><span class="mx-2">→</span>
              <span class="badge bg-secondary">3</span><span class="mx-2">→</span>
              <span class="badge bg-secondary">4</span><span class="mx-2">→</span>
              <span class="badge bg-secondary">5</span>
            </div>
            <h4 class="mt-4 mb-2">Informe seu telefone</h4>
            <p class="text-muted">Para contato e verificação de segurança</p>
          </div>
          <form id="phoneForm" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" name="website" value="" autocomplete="off" tabindex="-1" style="position:absolute;left:-5000px;height:1px;width:1px;overflow:hidden;" aria-hidden="true">
            <div class="mb-4">
              <label for="telefone" class="form-label">Telefone</label>
              <div class="input-group input-group-lg align-items-stretch">
                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                <div class="country-select-container">
                  <button type="button" class="country-select-toggle" id="countrySelectToggle" aria-haspopup="listbox" aria-expanded="false">
                    <img src="<?= htmlspecialchars($defaultFlagUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($defaultIso, ENT_QUOTES, 'UTF-8') ?>" class="country-select-flag" id="countrySelectFlag">
                    <span id="countrySelectLabel"><?= htmlspecialchars($defaultDisplay, ENT_QUOTES, 'UTF-8') ?></span>
                    <i class="fas fa-chevron-down ms-auto"></i>
                  </button>
                  <div class="country-select-dropdown d-none" id="countrySelectDropdown" role="listbox">
                    <div class="country-select-search">
                      <input type="text" class="form-control form-control-sm" id="countrySelectSearch" placeholder="Buscar país">
                    </div>
                    <div class="country-select-options" id="countrySelectOptions">
                      <?php foreach ($countryDialCodes as $dial): ?>
                        <?php
                          $iso = strtoupper($dial['iso'] ?? '');
                          $flagUrl = country_flag_url($iso);
                          $labelText = trim(($dial['country'] ?? 'País') . ' (' . ($dial['dial_code'] ?? '') . ')');
                        ?>
                        <button type="button"
                                class="country-select-option"
                                data-code="<?= htmlspecialchars($dial['dial_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-iso="<?= htmlspecialchars($iso, ENT_QUOTES, 'UTF-8') ?>"
                                data-label="<?= htmlspecialchars($labelText, ENT_QUOTES, 'UTF-8') ?>"
                                data-display="<?= htmlspecialchars($dial['dial_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-flag-url="<?= htmlspecialchars($flagUrl, ENT_QUOTES, 'UTF-8') ?>">
                          <img src="<?= htmlspecialchars($flagUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($iso, ENT_QUOTES, 'UTF-8') ?>">
                          <span><?= htmlspecialchars($labelText, ENT_QUOTES, 'UTF-8') ?></span>
                        </button>
                      <?php endforeach; ?>
                    </div>
                  </div>
                  <select class="country-code-select visually-hidden" id="country_code" name="country_code" aria-label="Código do país" required>
                    <?php foreach ($countryDialCodes as $dial): ?>
                      <?php
                        $iso = strtoupper($dial['iso'] ?? '');
                        $optionFlag = country_flag_url($iso);
                        $optionLabel = trim(($dial['country'] ?? 'País') . ' (' . ($dial['dial_code'] ?? '') . ')');
                      ?>
                      <option value="<?= htmlspecialchars($dial['dial_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-iso="<?= htmlspecialchars($iso, ENT_QUOTES, 'UTF-8') ?>"
                        data-display="<?= htmlspecialchars($dial['dial_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-flag-url="<?= htmlspecialchars($optionFlag, ENT_QUOTES, 'UTF-8') ?>"
                        <?= (($dial['dial_code'] ?? '') === $defaultDialCode) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($optionLabel, ENT_QUOTES, 'UTF-8') ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <input type="tel" class="form-control" id="telefone" name="telefone" placeholder="Ex: (11) 96123-4567" required pattern="^[0-9\s\+\-\(\)]{4,25}$" minlength="4" maxlength="25" inputmode="tel">
              </div>
              <div class="invalid-feedback">Por favor, selecione o país e informe um telefone válido para o formato escolhido.</div>
              <div class="form-text"><i class="fas fa-info-circle me-1"></i>Selecione o país e digite o telefone completo (DDD + número). O formato será aplicado automaticamente.</div>
            </div>
            <div class="d-grid">
              <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-arrow-right me-2"></i>Continuar</button>
            </div>
          </form>
        </div>

        <!-- Step 2: Email -->
        <div id="step2" class="register-step d-none">
          <div class="text-center mb-5">
            <div class="step-indicator">
              <span class="badge bg-success">✓</span><span class="mx-2">→</span>
              <span class="badge bg-primary">2</span><span class="mx-2">→</span>
              <span class="badge bg-secondary">3</span><span class="mx-2">→</span>
              <span class="badge bg-secondary">4</span><span class="mx-2">→</span>
              <span class="badge bg-secondary">5</span>
            </div>
            <h4 class="mt-4 mb-2">Informe seu email</h4>
            <p class="text-muted">Usaremos este email para enviar o código na etapa final</p>
          </div>
          <form id="emailForm" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" name="website" value="" autocomplete="off" tabindex="-1" style="position:absolute;left:-5000px;height:1px;width:1px;overflow:hidden;" aria-hidden="true">
            <div class="mb-4">
              <label for="email_register" class="form-label">Email</label>
              <div class="input-group input-group-lg">
                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                <input type="email" class="form-control" id="email_register" name="email" placeholder="seu@email.com" required>
              </div>
              <div class="invalid-feedback">Por favor, informe um email válido.</div>
              <div class="form-text"><i class="fas fa-info-circle me-1"></i>Usaremos este email para enviar o código de verificação</div>
            </div>
            <div class="d-grid">
              <button type="submit" class="btn btn-primary btn-lg" id="emailBtn"><i class="fas fa-arrow-right me-2"></i>Continuar</button>
            </div>
          </form>
        </div>

        <!-- Step 3: CPF + Confirmar dados -->
        <div id="step3" class="register-step d-none">
          <div class="text-center mb-5">
            <div class="step-indicator">
              <span class="badge bg-success">✓</span><span class="mx-2">→</span>
              <span class="badge bg-success">✓</span><span class="mx-2">→</span>
              <span class="badge bg-primary">3</span><span class="mx-2">→</span>
              <span class="badge bg-secondary">4</span><span class="mx-2">→</span>
              <span class="badge bg-secondary">5</span>
            </div>
            <h4 class="mt-4 mb-2">Informe seu CPF e confirme seus dados</h4>
            <p class="text-muted">Vamos consultar automaticamente seu nome para confirmar sua identidade</p>
          </div>

          <!-- CPF form -->
          <form id="cpfForm" class="needs-validation mb-4" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" name="website" value="" autocomplete="off" tabindex="-1" style="position:absolute;left:-5000px;height:1px;width:1px;overflow:hidden;" aria-hidden="true">
            <div class="mb-3">
              <label for="cpf" class="form-label">CPF</label>
              <div class="input-group input-group-lg">
                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                <input type="text" class="form-control form-control-lg text-center" id="cpf" name="cpf" placeholder="000.000.000-00" maxlength="14" required>
              </div>
            </div>
            <div class="d-grid">
              <button type="submit" class="btn btn-outline-primary btn-lg"><i class="fas fa-search me-2"></i>Consultar CPF</button>
            </div>
          </form>

          <div id="userData" class="mb-4"><!-- preenchido via JS --></div>

          <div class="d-flex justify-content-start align-items-center gap-2 mb-3">
            <button type="button" class="btn btn-outline-secondary d-none" id="notMeBtn"><i class="fas fa-user-edit me-2"></i>Não sou eu!</button>
          </div>

          <!-- Modal: Não sou eu -->
          <div class="modal fade" id="notMeModal" tabindex="-1" aria-labelledby="notMeModalLabel" aria-hidden="true">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="notMeModalLabel"><i class="fas fa-user-times me-2"></i>Não sou eu</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                  <p>Você informou o CPF: <strong id="notMeCpf">(não informado)</strong></p>
                  <p>Escolha uma opção abaixo:</p>
                  <ul class="mb-0">
                    <li><strong>Digitei o CPF errado</strong>: voltar para a etapa para informar novamente.</li>
                    <li><strong>O CPF está correto mas não sou eu</strong>: abrir um ticket na Central de Suporte.</li>
                  </ul>
                </div>
                <div class="modal-footer d-flex flex-row gap-2 justify-content-between">
                  <button type="button" class="btn btn-outline-secondary" id="wrongCpfBtn"><i class="fas fa-arrow-left me-2"></i>Digitei o CPF errado</button>
                  <button type="button" class="btn btn-primary" id="correctCpfNotMeBtn"><i class="fas fa-headset me-2"></i>Ir para Suporte</button>
                </div>
              </div>
            </div>
          </div>

          <div id="overrideContainer" class="card d-none mb-3">
            <div class="card-body">
              <form id="overrideForm" class="row g-3 needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <input type="text" name="website" value="" autocomplete="off" tabindex="-1" style="position:absolute;left:-5000px;height:1px;width:1px;overflow:hidden;" aria-hidden="true">
                <div class="col-12 col-md-6">
                  <label class="form-label">Nome completo</label>
                  <input type="text" class="form-control" name="nome" required>
                  <div class="invalid-feedback">Informe seu nome</div>
                </div>
                <div class="col-12 col-md-3">
                  <label class="form-label">Gênero</label>
                  <select class="form-select" name="genero" required>
                    <option value="">Selecione</option>
                    <option>Masculino</option>
                    <option>Feminino</option>
                    <option>Outro</option>
                  </select>
                  <div class="invalid-feedback">Selecione um gênero</div>
                </div>
                <div class="col-12 col-md-3">
                  <label class="form-label">Data de Nascimento</label>
                  <input type="text" class="form-control" name="data_nascimento" placeholder="DD/MM/AAAA" required>
                  <div class="invalid-feedback">Informe uma data válida</div>
                </div>
                <div class="col-12">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="aceiteTermos" name="aceite_termos" required>
                    <label class="form-check-label" for="aceiteTermos">
                      Eu declaro que li e concordo com os Termos de Uso, Política de Privacidade e Aviso de Risco.
                    </label>
                    <div class="invalid-feedback">É necessário aceitar os termos</div>
                  </div>
                </div>
                <div class="col-12 d-grid">
                  <button type="submit" class="btn btn-outline-primary"><i class="fas fa-save me-2"></i>Preencher manualmente</button>
                </div>
              </form>
            </div>
          </div>

          <!-- Confirmar dados (aceite políticas) -->
          <form id="confirmDataForm" class="needs-validation d-none" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" name="website" value="" autocomplete="off" tabindex="-1" style="position:absolute;left:-5000px;height:1px;width:1px;overflow:hidden;" aria-hidden="true">
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" id="consentSecurity" name="aceite_politicas" required>
              <label class="form-check-label" for="consentSecurity">
                Eu declaro que li e concordo com os 
                <a href="https://group.operebem.com.br/terminal/terms-of-use" target="_blank" rel="noopener">Termos de Uso</a>,
                <a href="https://group.operebem.com.br/terminal/privacy-policy" target="_blank" rel="noopener">Política de Privacidade</a>
                e <a href="https://group.operebem.com.br/terminal/risk-advice" target="_blank" rel="noopener">Aviso de Risco</a>.
              </label>
              <div class="invalid-feedback">Você deve concordar para continuar.</div>
            </div>
            <div class="d-grid">
              <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-check me-2"></i>Confirmar Dados</button>
            </div>
          </form>
        </div>

        <!-- Step 4: Criar Senha -->
        <div id="step4" class="register-step d-none">
          <div class="text-center mb-5">
            <div class="step-indicator">
              <span class="badge bg-success">✓</span><span class="mx-2">→</span>
              <span class="badge bg-success">✓</span><span class="mx-2">→</span>
              <span class="badge bg-success">✓</span><span class="mx-2">→</span>
              <span class="badge bg-primary">4</span><span class="mx-2">→</span>
              <span class="badge bg-secondary">5</span>
            </div>
            <h4 class="mt-4 mb-2">Crie sua senha</h4>
            <p class="text-muted">Escolha uma senha segura para sua conta</p>
          </div>
          <form id="passwordForm" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" name="website" value="" autocomplete="off" tabindex="-1" style="position:absolute;left:-5000px;height:1px;width:1px;overflow:hidden;" aria-hidden="true">
            <div class="mb-4">
              <label for="password" class="form-label">Senha</label>
              <div class="input-group input-group-lg">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password" placeholder="Digite sua senha" required minlength="8">
                <button class="btn btn-outline-secondary" type="button" id="togglePassword"><i class="fas fa-eye"></i></button>
              </div>
              <div class="invalid-feedback">A senha deve ter pelo menos 8 caracteres.</div>
            </div>
            <div class="mb-4">
              <label for="password_confirmation" class="form-label">Confirmar Senha</label>
              <div class="input-group input-group-lg">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Confirme sua senha" required>
              </div>
              <div class="invalid-feedback">As senhas não coincidem.</div>
            </div>
            <div class="d-grid">
              <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-arrow-right me-2"></i>Continuar</button>
            </div>
          </form>
        </div>

        <!-- Step 5: Verificação de Código -->
        <div id="step5" class="register-step d-none">
          <div class="text-center mb-5">
            <div class="step-indicator">
              <span class="badge bg-success">✓</span><span class="mx-2">→</span>
              <span class="badge bg-success">✓</span><span class="mx-2">→</span>
              <span class="badge bg-success">✓</span><span class="mx-2">→</span>
              <span class="badge bg-success">✓</span><span class="mx-2">→</span>
              <span class="badge bg-primary">5</span>
            </div>
            <h4 class="mt-4 mb-2">Código de Verificação</h4>
            <p class="text-muted">Digite o código de 6 dígitos enviado para seu email</p>
          </div>
          <form id="verificationForm" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" name="website" value="" autocomplete="off" tabindex="-1" style="position:absolute;left:-5000px;height:1px;width:1px;overflow:hidden;" aria-hidden="true">
            <div class="mb-4">
              <label for="codigo" class="form-label">Código de Verificação</label>
              <div class="input-group input-group-lg justify-content-center">
                <input type="text" class="form-control form-control-lg text-center" id="codigo" name="codigo" placeholder="000000" maxlength="6" required style="max-width: 200px;">
              </div>
              <div class="invalid-feedback">Por favor, informe o código de 6 dígitos.</div>
              <div class="form-text text-center"><i class="fas fa-clock me-1"></i>O código expira em 30 minutos</div>
            </div>
            <div class="d-grid mb-4">
              <button type="submit" class="btn btn-success btn-lg" id="verificationBtn"><i class="fas fa-check me-2"></i>Finalizar Cadastro</button>
            </div>
            <div class="text-center">
              <button type="button" class="btn btn-link" id="resendCode"><i class="fas fa-redo me-1"></i>Reenviar código</button>
            </div>
          </form>
        </div>

        <div id="phoneConfirmOverlay" class="phone-confirm-overlay d-none">
          <div class="phone-confirm-card">
            <div class="text-center mb-3">
              <div class="text-muted small mb-1">Este é o seu número</div>
              <h4 class="fw-bold mb-0" id="phoneConfirmNumber">+00 0000-0000</h4>
            </div>
            <p class="text-muted mb-4 text-center">Confira se está correto. Iremos realizar uma verificação através do WhatsApp.</p>
            <div class="d-flex flex-column flex-sm-row gap-2">
              <button type="button" class="btn btn-outline-secondary flex-fill" id="phoneConfirmEditBtn">
                <i class="fas fa-edit me-2"></i>Editar número
              </button>
              <button type="button" class="btn btn-primary flex-fill" id="phoneConfirmProceedBtn">
                <i class="fas fa-check me-2"></i>Confirmar e continuar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
