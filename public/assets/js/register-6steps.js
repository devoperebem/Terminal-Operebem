/**
 * JavaScript para o novo fluxo de registro de 6 passos
 * Terminal Operebem - Sistema de Registro Multi-Step
 */

document.addEventListener("DOMContentLoaded", function() {
    // Elementos do DOM
    const cpfForm = document.getElementById("cpfForm");
    const confirmDataForm = document.getElementById("confirmDataForm");
    const passwordForm = document.getElementById("passwordForm");
    const phoneForm = document.getElementById("phoneForm");
    const emailForm = document.getElementById("emailForm");
    const verificationForm = document.getElementById("verificationForm");
    const notMeBtn = document.getElementById("notMeBtn");
    const overrideForm = document.getElementById("overrideForm");
    const countryCodeSelect = document.getElementById("country_code");
    const countrySelectToggle = document.getElementById("countrySelectToggle");
    const countrySelectDropdown = document.getElementById("countrySelectDropdown");
    const countrySelectSearch = document.getElementById("countrySelectSearch");
    const countrySelectOptions = document.querySelectorAll(".country-select-option");
    const countrySelectLabel = document.getElementById("countrySelectLabel");
    const countrySelectFlag = document.getElementById("countrySelectFlag");
    const registerBackBtn = document.getElementById("registerBackBtn");
    const registerModalBody = document.getElementById("registerModalBody");
    const phoneConfirmOverlay = document.getElementById("phoneConfirmOverlay");
    const phoneConfirmNumber = document.getElementById("phoneConfirmNumber");
    const phoneConfirmEditBtn = document.getElementById("phoneConfirmEditBtn");
    const phoneConfirmProceedBtn = document.getElementById("phoneConfirmProceedBtn");
    let phoneLibPromise = null;
    let phoneExampleData = null;
    let pendingPhoneData = null;
    
    const registerAlertContainer = document.getElementById("registerAlertContainer");
    
    // Utility functions

    if (registerBackBtn) {
        registerBackBtn.addEventListener('click', function() {
            const currentStep = document.querySelector('.register-step:not(.d-none)');
            if (!currentStep) {
                return;
            }
            const currentId = currentStep.id || '';
            switch (currentId) {
                case 'step2':
                    pendingPhoneData = null;
                    showStep(1);
                    break;
                case 'step3':
                    showStep(2);
                    break;
                case 'step4':
                    showStep(3);
                    break;
                case 'step5':
                    showStep(4);
                    break;
                default:
                    break;
            }
        });
    }

    function closeCountryDropdown() {
        if (countrySelectDropdown) {
            countrySelectDropdown.classList.add('d-none');
        }
        if (countrySelectToggle) {
            countrySelectToggle.setAttribute('aria-expanded', 'false');
        }
    }

    function openCountryDropdown() {
        if (countrySelectDropdown) {
            countrySelectDropdown.classList.remove('d-none');
        }
        if (countrySelectToggle) {
            countrySelectToggle.setAttribute('aria-expanded', 'true');
        }
    }

    function updateCountrySelectVisual(dialCode, iso, label, flagUrl, displayText = '', dispatchChange = true) {
        if (countryCodeSelect && dispatchChange) {
            countryCodeSelect.value = dialCode;
            const event = new Event('change', { bubbles: true });
            countryCodeSelect.dispatchEvent(event);
        }
        if (countrySelectLabel) {
            countrySelectLabel.textContent = displayText || dialCode || label || '';
        }
        if (countrySelectFlag && flagUrl) {
            countrySelectFlag.src = flagUrl;
            countrySelectFlag.alt = iso || '';
        }
        if (countrySelectToggle) {
            countrySelectToggle.setAttribute('data-selected-iso', iso || '');
        }
    }

    if (countrySelectToggle && countrySelectDropdown) {
        countrySelectToggle.addEventListener('click', function() {
            if (countrySelectDropdown.classList.contains('d-none')) {
                openCountryDropdown();
                if (countrySelectSearch) {
                    countrySelectSearch.value = '';
                    countrySelectSearch.focus();
                    if (countrySelectOptions) {
                        countrySelectOptions.forEach(opt => opt.classList.remove('d-none'));
                    }
                }
            } else {
                closeCountryDropdown();
            }
        });
        document.addEventListener('click', function(event) {
            if (!countrySelectToggle.contains(event.target) && !countrySelectDropdown.contains(event.target)) {
                closeCountryDropdown();
            }
        });
    }

    if (countrySelectOptions && countrySelectOptions.length) {
        countrySelectOptions.forEach(option => {
            option.addEventListener('click', function() {
                updateCountrySelectVisual(
                    option.dataset.code || '',
                    option.dataset.iso || '',
                    option.dataset.label || '',
                    option.dataset.flagUrl || '',
                    option.dataset.display || option.dataset.code || ''
                );
                closeCountryDropdown();
            });
        });
    }

    if (countryCodeSelect) {
        countryCodeSelect.addEventListener('change', function() {
            const selectedOption = countryCodeSelect.selectedOptions && countryCodeSelect.selectedOptions[0];
            if (!selectedOption) {
                return;
            }
            const label = selectedOption.textContent || selectedOption.innerText || '';
            updateCountrySelectVisual(
                selectedOption.value,
                selectedOption.dataset.iso || '',
                label.trim(),
                selectedOption.dataset.flagUrl || (countrySelectFlag ? countrySelectFlag.src : ''),
                selectedOption.dataset.display || selectedOption.value,
                false
            );
        });
    }

    if (countrySelectSearch) {
        countrySelectSearch.addEventListener('input', function() {
            const term = (countrySelectSearch.value || '').toLowerCase();
            countrySelectOptions.forEach(option => {
                const label = (option.dataset.label || '').toLowerCase();
                option.classList.toggle('d-none', term && !label.includes(term));
            });
        });
    }

    function showAlert(containerId, type, message) {
        const container = document.getElementById(containerId);
        container.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show animate-scale-in" role="alert">
                <i class="fas fa-${type === "success" ? "check-circle" : type === "danger" ? "exclamation-circle" : "info-circle"} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }
    
    function setButtonLoading(button, loading = true) {
        if (!button) return;
        if (loading) {
            if (!button.getAttribute('data-original-text')) {
                button.setAttribute('data-original-text', button.innerHTML);
            }
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processando...';
        } else {
            button.disabled = false;
            const original = button.getAttribute('data-original-text');
            if (original) {
                button.innerHTML = original;
            }
        }
    }
    
    function showStep(stepNumber) {
        // Esconder todos os passos
        for (let i = 1; i <= 5; i++) {
            const step = document.getElementById(`step${i}`);
            if (step) {
                step.classList.add('d-none');
            }
        }

        // Mostrar o passo atual
        const currentStep = document.getElementById(`step${stepNumber}`);
        if (currentStep) {
            currentStep.classList.remove('d-none');
        }

        // Mostrar/esconder botão de voltar (não mostrar no step 1)
        if (registerBackBtn) {
            if (stepNumber === 1) {
                registerBackBtn.style.display = 'none';
            } else {
                registerBackBtn.style.display = '';
            }
        }

        // Atualizar indicadores de progresso
        updateStepIndicators(stepNumber);
    }
    
    function updateStepIndicators(currentStep) {
        for (let i = 1; i <= 5; i++) {
            const badge = document.querySelector(`#step${currentStep} .step-indicator .badge:nth-child(${i * 2 - 1})`);
            if (badge) {
                if (i < currentStep) {
                    badge.className = 'badge bg-success';
                    badge.textContent = '✓';
                } else if (i === currentStep) {
                    badge.className = 'badge bg-primary';
                    badge.textContent = i;
                } else {
                    badge.className = 'badge bg-secondary';
                    badge.textContent = i;
                }
            }
        }
    }

    function loadPhoneLibScript() {
        if (window.libphonenumber) {
            return Promise.resolve();
        }
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = '/assets/js/vendor/libphonenumber-js.min.js';
            script.async = true;
            script.onload = () => resolve();
            script.onerror = () => reject(new Error('Não foi possível carregar o validador de telefone.'));
            document.head.appendChild(script);
        });
    }

    function ensurePhoneLib() {
        if (!phoneLibPromise) {
            phoneLibPromise = Promise.all([
                loadPhoneLibScript(),
                fetch('/assets/js/vendor/libphonenumber-js.examples.mobile.json')
                    .then(resp => resp.ok ? resp.json() : null)
                    .catch(() => null)
            ]).then(([, examples]) => {
                phoneExampleData = examples;
            }).catch(error => {
                console.error('Falha ao preparar utilitário de telefone:', error);
            });
        }
        return phoneLibPromise;
    }

    function getSelectedCountryIso() {
        if (!countryCodeSelect) {
            return null;
        }
        const selected = countryCodeSelect.selectedOptions[0];
        return (selected && selected.dataset.iso) ? selected.dataset.iso.toUpperCase() : null;
    }

    function getMaxPhoneLengthForCountry(iso) {
        if (!window.libphonenumber || !phoneExampleData) {
            return 25;
        }
        try {
            const example = window.libphonenumber.getExampleNumber(iso, phoneExampleData);
            if (example) {
                const formatted = example.formatNational();
                return formatted.length + 3;
            }
        } catch (error) {
            console.warn('Erro ao obter tamanho máximo:', error);
        }
        return 25;
    }

    function formatPhoneFieldValue(value) {
        const iso = getSelectedCountryIso();
        if (!window.libphonenumber || !iso) {
            return value.replace(/[^\d+]/g, '');
        }
        try {
            const maxLength = getMaxPhoneLengthForCountry(iso);
            const truncated = value.substring(0, maxLength);
            const formatter = new window.libphonenumber.AsYouType(iso);
            return formatter.input(truncated);
        } catch (error) {
            console.warn('Erro ao formatar telefone:', error);
            return value.replace(/[^\d+]/g, '');
        }
    }

    function updatePhonePlaceholder() {
        if (!phoneExampleData || !window.libphonenumber) {
            return;
        }
        const iso = getSelectedCountryIso();
        const phoneInput = document.getElementById('telefone');
        if (!iso || !phoneInput) {
            return;
        }
        try {
            const example = window.libphonenumber.getExampleNumber(iso, phoneExampleData);
            if (example) {
                phoneInput.placeholder = example.formatNational();
            }
        } catch (error) {
            console.warn('Erro ao gerar exemplo de telefone:', error);
        }
    }

    async function parsePhoneNumberFromInput(value) {
        await ensurePhoneLib();
        const iso = getSelectedCountryIso();
        if (!window.libphonenumber || !iso) {
            const digitsOnly = (value || '').replace(/\D/g, '');
            const countryValue = countryCodeSelect ? countryCodeSelect.value.replace(/\D/g, '') : '';
            const combined = `${countryValue}${digitsOnly}`.replace(/\D/g, '');
            if (countryValue && digitsOnly.length >= 4 && combined.length >= 6 && combined.length <= 15) {
                const internationalNumber = `+${combined}`;
                return {
                    number: internationalNumber,
                    countryCallingCode: countryValue,
                    country: iso || null,
                    formatInternational: () => internationalNumber
                };
            }
            return null;
        }
        try {
            const parsed = window.libphonenumber.parsePhoneNumberFromString(value, iso);
            if (parsed && parsed.isValid()) {
                return parsed;
            }
        } catch (error) {
            console.warn('Erro ao validar telefone:', error);
        }
        return null;
    }

    function showPhoneConfirmOverlay(formattedNumber) {
        if (!phoneConfirmOverlay) {
            return;
        }
        if (phoneConfirmNumber && formattedNumber) {
            phoneConfirmNumber.textContent = formattedNumber;
        }
        phoneConfirmOverlay.classList.remove('d-none');
        if (registerModalBody) {
            registerModalBody.classList.add('phone-confirm-visible');
        }
    }

    function hidePhoneConfirmOverlay() {
        if (!phoneConfirmOverlay) {
            return;
        }
        phoneConfirmOverlay.classList.add('d-none');
        if (registerModalBody) {
            registerModalBody.classList.remove('phone-confirm-visible');
        }
    }

    function buildPhoneSubmissionData(parsedPhone) {
        const fallbackCode = countryCodeSelect ? countryCodeSelect.value.replace(/\D/g, '') : '';
        const fallbackIso = getSelectedCountryIso();
        const formatted = parsedPhone && parsedPhone.formatInternational ? parsedPhone.formatInternational() : (parsedPhone?.number || '');
        return {
            formatted: formatted || parsedPhone.number,
            e164: parsedPhone.number,
            countryCallingCode: (parsedPhone.countryCallingCode || fallbackCode || '').replace(/\D/g, ''),
            countryIso: parsedPhone.country || fallbackIso || ''
        };
    }

    async function validatePhoneInRealTime(inputElement) {
        if (!inputElement) {
            return;
        }

        // Se o campo está vazio, limpar validação
        if (!inputElement.value || inputElement.value.trim() === '') {
            inputElement.setCustomValidity('');
            inputElement.classList.remove('is-invalid');
            inputElement.classList.remove('is-valid');
            return;
        }

        const iso = getSelectedCountryIso();
        if (!window.libphonenumber || !iso) {
            return;
        }

        try {
            const parsed = window.libphonenumber.parsePhoneNumberFromString(inputElement.value, iso);
            if (parsed && parsed.isValid()) {
                inputElement.setCustomValidity('');
                inputElement.classList.remove('is-invalid');
                inputElement.classList.add('is-valid');
            } else {
                const digitsOnly = inputElement.value.replace(/\D/g, '');
                // Validar se tem dígitos suficientes para ser considerado um número completo
                if (digitsOnly.length >= 4) {
                    inputElement.setCustomValidity('Número de telefone inválido para o país selecionado');
                    inputElement.classList.remove('is-valid');
                    inputElement.classList.add('is-invalid');
                } else {
                    inputElement.setCustomValidity('');
                    inputElement.classList.remove('is-invalid');
                    inputElement.classList.remove('is-valid');
                }
            }
        } catch (error) {
            console.warn('Erro na validação em tempo real:', error);
            inputElement.setCustomValidity('Erro ao validar o número de telefone');
            inputElement.classList.remove('is-valid');
            inputElement.classList.add('is-invalid');
        }
    }

    // Máscaras de input
    function initMasks() {
        // Máscara para CPF
        const cpfInput = document.getElementById('cpf');
        if (cpfInput) {
            cpfInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                e.target.value = value;
            });
        }
        
        // Limitar telefone para apenas dígitos (suporte internacional)
        const phoneInput = document.getElementById('telefone');
        if (phoneInput) {
            ensurePhoneLib().then(() => updatePhonePlaceholder());
            phoneInput.addEventListener('focus', () => {
                ensurePhoneLib().then(() => updatePhonePlaceholder());
            });
            phoneInput.addEventListener('input', function(e) {
                e.target.value = formatPhoneFieldValue(e.target.value);
                validatePhoneInRealTime(e.target);
            });
            phoneInput.addEventListener('blur', function(e) {
                validatePhoneInRealTime(e.target);
            });
        }
        if (countryCodeSelect) {
            countryCodeSelect.addEventListener('change', () => {
                const input = document.getElementById('telefone');
                if (input) {
                    input.value = formatPhoneFieldValue(input.value);
                    validatePhoneInRealTime(input);
                }
                ensurePhoneLib().then(() => updatePhonePlaceholder());
            });
        }
        
        // Máscara para código de verificação
        const codeInput = document.getElementById('codigo');
        if (codeInput) {
            codeInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '').substring(0, 6);
            });
        }
    }
    
    // Toggle de senha
    const togglePassword = document.getElementById('togglePassword');
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
    }

    // Passo 2: "Não sou eu" -> abrir modal com CPF e opções
    if (notMeBtn) {
        notMeBtn.addEventListener('click', function() {
            const cpfInput = document.getElementById('cpf');
            const cpfVal = (cpfInput ? cpfInput.value.trim() : '').replace(/\s+/g, ' ');
            const cpfSpan = document.getElementById('notMeCpf');
            if (cpfSpan) cpfSpan.textContent = cpfVal || '(não informado)';
            if (window.bootstrap) {
                const modalEl = document.getElementById('notMeModal');
                if (modalEl) new bootstrap.Modal(modalEl).show();
            }
        });
    }

    const wrongCpfBtn = document.getElementById('wrongCpfBtn');
    if (wrongCpfBtn) {
        wrongCpfBtn.addEventListener('click', function() {
            if (window.bootstrap) {
                const modalEl = document.getElementById('notMeModal');
                if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }
            // Reiniciar a etapa 3: limpar CPF, esconder dados e confirmar, ocultar 'Não sou eu', focar CPF
            showStep(3);
            const cpfInput = document.getElementById('cpf');
            if (cpfInput) { cpfInput.value = ''; cpfInput.focus(); }
            const userDataDiv = document.getElementById('userData');
            if (userDataDiv) userDataDiv.innerHTML = '';
            const confirmDataFormEl = document.getElementById('confirmDataForm');
            if (confirmDataFormEl) confirmDataFormEl.classList.add('d-none');
            const notMe = document.getElementById('notMeBtn');
            if (notMe) notMe.classList.add('d-none');
            // Reexibir botão Consultar CPF caso tenha sido ocultado
            const cpfSubmitBtn = document.querySelector('#cpfForm button[type="submit"]');
            if (cpfSubmitBtn && cpfSubmitBtn.closest('.d-grid')) {
                cpfSubmitBtn.closest('.d-grid').classList.remove('d-none');
            }
        });
    }

    const correctCpfNotMeBtn = document.getElementById('correctCpfNotMeBtn');
    if (correctCpfNotMeBtn) {
        correctCpfNotMeBtn.addEventListener('click', function() {
            const cpfInput = document.getElementById('cpf');
            const raw = cpfInput ? cpfInput.value.trim() : '';
            const enc = encodeURIComponent(raw);
            window.location.href = '/support?cpf=' + enc;
        });
    }

    // Enviar dados manuais (override-dados)
    if (overrideForm) {
        overrideForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (!overrideForm.checkValidity()) {
                e.stopPropagation();
                overrideForm.classList.add('was-validated');
                return;
            }
            const btn = overrideForm.querySelector('button[type="submit"]');
            if (btn) {
                btn.setAttribute('data-original-text', btn.innerHTML);
                setButtonLoading(btn, true);
            }
            try {
                const formData = new FormData(overrideForm);
                const response = await fetch('/register/override-dados', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    showStep(3);
                    showAlert('registerAlertContainer', 'success', 'Dados informados com sucesso!');
                } else {
                    showAlert('registerAlertContainer', 'danger', result.message || 'Erro ao informar dados');
                }
            } catch (err) {
                console.error('Erro override-dados:', err);
                showAlert('registerAlertContainer', 'danger', 'Erro de conexão. Tente novamente.');
            } finally {
                if (btn) setButtonLoading(btn, false);
            }
        });
    }
    
    // Validação de confirmação de senha
    const passwordConfirmation = document.getElementById('password_confirmation');
    if (passwordConfirmation) {
        passwordConfirmation.addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmation = this.value;
            
            if (confirmation && password !== confirmation) {
                this.setCustomValidity('As senhas não coincidem');
            } else {
                this.setCustomValidity('');
            }
        });
    }
    
    // Passo 3: Consultar CPF (após telefone e email)
    if (cpfForm) {
        cpfForm.addEventListener("submit", async function(e) {
            e.preventDefault();
            
            if (!cpfForm.checkValidity()) {
                e.stopPropagation();
                cpfForm.classList.add("was-validated");
                return;
            }
            
            const button = cpfForm.querySelector('button[type="submit"]');
            setButtonLoading(button, true);
            
            try {
                const formData = new FormData(cpfForm);
                const response = await fetch('/register/consultar-cpf', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Preencher dados do usuário
                    const userDataDiv = document.getElementById('userData');
                    if (userDataDiv) {
                        userDataDiv.innerHTML = `
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Dados encontrados:</h6>
                                    <p class="mb-0"><strong>Nome:</strong> ${result.data.nome}</p>
                                </div>
                            </div>
                        `;
                    }
                    // Exibir 'Não sou eu!'
                    const notMe = document.getElementById('notMeBtn');
                    if (notMe) notMe.classList.remove('d-none');
                    // Exibir formulário de confirmação e ocultar botão 'Consultar CPF'
                    const confirmDataFormEl = document.getElementById('confirmDataForm');
                    if (confirmDataFormEl) confirmDataFormEl.classList.remove('d-none');
                    const cpfSubmitBtn = document.querySelector('#cpfForm button[type="submit"]');
                    if (cpfSubmitBtn && cpfSubmitBtn.closest('.d-grid')) {
                        cpfSubmitBtn.closest('.d-grid').classList.add('d-none');
                    }
                    showStep(3);
                    showAlert('registerAlertContainer', 'success', 'CPF consultado com sucesso!');
                } else {
                    if (result.redirect) {
                        showAlert('registerAlertContainer', 'danger', `${result.message || 'Erro na consulta do CPF'} <a href="${result.redirect}" class="alert-link">Ir para suporte</a>.`);
                    } else {
                        showAlert('registerAlertContainer', 'danger', result.message || 'Erro na consulta do CPF');
                    }
                }
            } catch (error) {
                console.error('Erro na consulta CPF:', error);
                showAlert('registerAlertContainer', 'danger', 'Erro de conexão. Tente novamente.');
            } finally {
                setButtonLoading(button, false);
            }
        });
    }
    
    // Passo 3: Confirmar Dados
    if (confirmDataForm) {
        confirmDataForm.addEventListener("submit", async function(e) {
            e.preventDefault();

            // Validação do checkbox de consentimento
            if (!confirmDataForm.checkValidity()) {
                e.stopPropagation();
                confirmDataForm.classList.add('was-validated');
                return;
            }
            
            const button = confirmDataForm.querySelector('button[type="submit"]');
            setButtonLoading(button, true);
            
            try {
                const formData = new FormData(confirmDataForm);
                const response = await fetch('/register/confirmar-dados', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showStep(4);
                    showAlert('registerAlertContainer', 'success', 'Dados confirmados!');
                } else {
                    showAlert('registerAlertContainer', 'danger', result.message || 'Erro na confirmação');
                }
            } catch (error) {
                console.error('Erro na confirmação:', error);
                showAlert('registerAlertContainer', 'danger', 'Erro de conexão. Tente novamente.');
            } finally {
                setButtonLoading(button, false);
            }
        });
    }
    
    // Passo 4: Criar Senha
    if (passwordForm) {
        passwordForm.addEventListener("submit", async function(e) {
            e.preventDefault();
            
            if (!passwordForm.checkValidity()) {
                e.stopPropagation();
                passwordForm.classList.add("was-validated");
                return;
            }
            // Validação extra de força de senha (coerente com backend)
            const pwd = passwordForm.querySelector('#password').value || '';
            const pwdRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
            if (!pwdRegex.test(pwd)) {
                showAlert('registerAlertContainer', 'danger', 'A senha deve ter pelo menos 8 caracteres e conter 1 minúscula, 1 maiúscula e 1 número.');
                return;
            }
            
            const button = passwordForm.querySelector('button[type="submit"]');
            setButtonLoading(button, true);
            
            try {
                const formData = new FormData(passwordForm);
                const response = await fetch('/register/criar-senha', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showStep(5);
                    showAlert('registerAlertContainer', 'success', 'Senha criada com sucesso!');
                    // Ao entrar na etapa 5, gerar/enviar código automaticamente
                    try {
                        const fd = new FormData();
                        const csrf = document.querySelector('input[name="csrf_token"]');
                        if (csrf) fd.append('csrf_token', csrf.value);
                        const resp = await fetch('/register/reenviar-codigo', { method: 'POST', body: fd });
                        const rr = await resp.json();
                        if (rr && rr.success) {
                            if (rr.dev_preview_code) {
                                showAlert('registerAlertContainer', 'info', 'Código gerado (teste): ' + rr.dev_preview_code);
                            } else {
                                showAlert('registerAlertContainer', 'success', 'Código de verificação enviado para o email!');
                            }
                        } else {
                            showAlert('registerAlertContainer', 'danger', (rr && rr.message) || 'Erro ao enviar código');
                        }
                    } catch (e2) {
                        console.error('Erro ao gerar/enviar código:', e2);
                        showAlert('registerAlertContainer', 'danger', 'Erro de conexão ao enviar código. Tente novamente.');
                    }
                } else {
                    showAlert('registerAlertContainer', 'danger', result.message || 'Erro ao criar senha');
                }
            } catch (error) {
                console.error('Erro ao criar senha:', error);
                showAlert('registerAlertContainer', 'danger', 'Erro de conexão. Tente novamente.');
            } finally {
                setButtonLoading(button, false);
            }
        });
    }
    
        async function submitPhoneData(phoneData) {
        if (!phoneForm || !phoneData) {
            return false;
        }
        try {
            const formData = new FormData(phoneForm);
            formData.set('telefone', phoneData.e164);
            const callingCode = (phoneData.countryCallingCode || '').replace(/\D/g, '');
            if (callingCode) {
                formData.set('country_code', `+${callingCode}`);
            } else if (countryCodeSelect && countryCodeSelect.value) {
                formData.set('country_code', countryCodeSelect.value);
            }
            if (phoneData.countryIso) {
                formData.set('country_iso', phoneData.countryIso);
            }
            const response = await fetch('/register/adicionar-telefone', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showStep(2);
                showAlert('registerAlertContainer', 'success', 'Telefone adicionado com sucesso!');
                pendingPhoneData = null;
                hidePhoneConfirmOverlay();
                return true;
            }
            hidePhoneConfirmOverlay();
            pendingPhoneData = null;
            if (result.redirect) {
                showAlert('registerAlertContainer', 'danger', `${result.message || 'Erro ao adicionar telefone'} <a href="${result.redirect}" class="alert-link">Ir para suporte</a>.`);
            } else {
                showAlert('registerAlertContainer', 'danger', result.message || 'Erro ao adicionar telefone');
            }
            return false;
        } catch (error) {
            console.error('Erro ao adicionar telefone:', error);
            hidePhoneConfirmOverlay();
            pendingPhoneData = null;
            showAlert('registerAlertContainer', 'danger', 'Erro de conexao. Tente novamente.');
            return false;
        }
    }

    if (phoneConfirmEditBtn) {
        phoneConfirmEditBtn.addEventListener('click', function() {
            pendingPhoneData = null;
            hidePhoneConfirmOverlay();
        });
    }

    if (phoneConfirmProceedBtn) {
        phoneConfirmProceedBtn.addEventListener('click', async function() {
            if (!pendingPhoneData) {
                hidePhoneConfirmOverlay();
                return;
            }
            setButtonLoading(phoneConfirmProceedBtn, true);
            if (phoneConfirmEditBtn) {
                phoneConfirmEditBtn.disabled = true;
            }
            try {
                await submitPhoneData(pendingPhoneData);
            } finally {
                setButtonLoading(phoneConfirmProceedBtn, false);
                if (phoneConfirmEditBtn) {
                    phoneConfirmEditBtn.disabled = false;
                }
            }
        });
    }

    // Passo 1: Telefone
    if (phoneForm) {
        const phoneSubmitButton = phoneForm.querySelector('button[type="submit"]');
        phoneForm.addEventListener("submit", async function(e) {
            e.preventDefault();

            const phoneInput = document.getElementById('telefone');

            // Validar o número antes de verificar o checkValidity
            if (phoneInput) {
                await validatePhoneInRealTime(phoneInput);
            }

            if (!phoneForm.checkValidity()) {
                e.stopPropagation();
                phoneForm.classList.add("was-validated");
                return;
            }

            try {
                const parsedPhone = phoneInput ? await parsePhoneNumberFromInput(phoneInput.value) : null;
                if (!parsedPhone) {
                    if (phoneInput) {
                        phoneInput.setCustomValidity('Informe um telefone válido para o país selecionado.');
                        phoneInput.classList.remove('is-valid');
                        phoneInput.classList.add('is-invalid');
                    }
                    phoneForm.classList.add("was-validated");
                    showAlert('registerAlertContainer', 'danger', 'Por favor, informe um número de telefone válido.');
                    return;
                }
                const phoneData = buildPhoneSubmissionData(parsedPhone);
                if (phoneInput) {
                    phoneInput.setCustomValidity('');
                    phoneInput.classList.remove('is-invalid');
                    phoneInput.classList.add('is-valid');
                    phoneInput.value = phoneData.formatted;
                }

                if (phoneConfirmOverlay && phoneConfirmNumber && phoneConfirmProceedBtn && phoneConfirmEditBtn) {
                    pendingPhoneData = phoneData;
                    showPhoneConfirmOverlay(phoneData.formatted);
                } else {
                    setButtonLoading(phoneSubmitButton, true);
                    try {
                        await submitPhoneData(phoneData);
                    } finally {
                        setButtonLoading(phoneSubmitButton, false);
                    }
                }
            } catch (error) {
                console.error('Erro ao preparar telefone:', error);
                showAlert('registerAlertContainer', 'danger', 'Erro ao validar o telefone informado.');
                pendingPhoneData = null;
                hidePhoneConfirmOverlay();
            }
        });
    }

    // Passo 2: Email
    if (emailForm) {
        emailForm.addEventListener("submit", async function(e) {
            e.preventDefault();
            
            if (!emailForm.checkValidity()) {
                e.stopPropagation();
                emailForm.classList.add("was-validated");
                return;
            }
            
            const button = emailForm.querySelector('button[type="submit"]');
            setButtonLoading(button, true);
            
            try {
                const formData = new FormData(emailForm);
                const response = await fetch('/register/adicionar-email', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showStep(3);
                    showAlert('registerAlertContainer', 'success', 'Email adicionado com sucesso!');
                } else {
                    showAlert('registerAlertContainer', 'danger', result.message || 'Erro ao enviar código');
                }
            } catch (error) {
                console.error('Erro ao enviar código:', error);
                showAlert('registerAlertContainer', 'danger', 'Erro de conexão. Tente novamente.');
            } finally {
                setButtonLoading(button, false);
            }
        });
    }
    
    // Passo 6: Verificação de Código
    if (verificationForm) {
        verificationForm.addEventListener("submit", async function(e) {
            e.preventDefault();
            
            if (!verificationForm.checkValidity()) {
                e.stopPropagation();
                verificationForm.classList.add("was-validated");
                return;
            }
            
            const button = verificationForm.querySelector('button[type="submit"]');
            setButtonLoading(button, true);
            
            try {
                const formData = new FormData(verificationForm);
                const response = await fetch('/register/verificar-codigo', {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json, text/plain, */*' }
                });
                
                // Tratar respostas não-JSON (ex.: redireções/HTML/204) como sucesso
                const ct = (response.headers && response.headers.get('Content-Type')) || '';
                const isJson = ct && ct.toLowerCase().includes('application/json');
                let result = null;
                if (isJson) {
                    try { result = await response.json(); } catch(_) { result = null; }
                }
                
                if ((result && result.success === true) || (response.ok && !isJson)) {
                    showAlert('registerAlertContainer', 'success', 'Conta criada com sucesso!');
                    try { if (typeof gtag === 'function') { gtag('event', 'sign_up', { method: 'email' }); } } catch(e){}
                    // Redirecionar imediatamente se já autenticado (cookie setado pelo backend)
                    setTimeout(() => {
                        const redirectUrl = (result && result.redirect) ? result.redirect : '/app/dashboard';
                        window.location.href = redirectUrl;
                    }, 600);
                } else if (result && result.success === false) {
                    showAlert('registerAlertContainer', 'danger', result.message || 'Código inválido. Tente novamente.');
                    const codigoInput = document.getElementById('codigo');
                    if (codigoInput) { codigoInput.value = ''; codigoInput.focus(); }
                } else {
                    // Status não-OK e sem JSON válido
                    showAlert('registerAlertContainer', 'danger', 'Erro de verificação. Tente novamente.');
                }
            } catch (error) {
                console.error('Erro na verificação:', error);
                // Se houver sessão já criada (ex.: backend completou e erro foi de parsing), tentar redirecionar
                try { window.location.href = '/app/dashboard'; return; } catch(_) {}
                showAlert('registerAlertContainer', 'danger', 'Erro de conexão. Tente novamente.');
                const codigoInput = document.getElementById('codigo');
                if (codigoInput) { codigoInput.value = ''; codigoInput.focus(); }
            } finally {
                setButtonLoading(button, false);
            }
        });
    }
    
    // Reenviar código
    const resendCode = document.getElementById('resendCode');
    if (resendCode) {
        resendCode.addEventListener('click', async function() {
            const button = this;
            setButtonLoading(button, true);
            
            try {
                const formData = new FormData();
                formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
                
                const response = await fetch('/register/reenviar-codigo', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('registerAlertContainer', 'success', 'Código reenviado com sucesso!');
                } else {
                    showAlert('registerAlertContainer', 'danger', result.message || 'Erro ao reenviar código');
                }
            } catch (error) {
                console.error('Erro ao reenviar código:', error);
                showAlert('registerAlertContainer', 'danger', 'Erro de conexão. Tente novamente.');
            } finally {
                setButtonLoading(button, false);
            }
        });
    }
    
    // Inicializar máscaras
    initMasks();
    
    // Mostrar passo 1 por padrão
    showStep(1);
});
