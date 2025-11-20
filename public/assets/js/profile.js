/**
 * Terminal Operebem - Profile JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeProfile();
});

// Inicializar página de perfil
function initializeProfile() {
    // Configurar formulário de preferências
    setupPreferencesForm();
    
    // Configurar modal de alteração de senha
    setupChangePasswordModal();
    
    // Configurar preview de temas
    setupThemePreview();

    // Configurar UX de alterações não salvas
    setupAvatarUpload();
    setupUnsavedChangesUX();
}

// Configurar formulário de preferências
function setupPreferencesForm() {
    const preferencesForm = document.getElementById('preferencesForm');
    if (!preferencesForm) return;
    
    preferencesForm.addEventListener('submit', handlePreferencesUpdate);
    
    // Configurar mudança de tema em tempo real
    const themeInputs = preferencesForm.querySelectorAll('input[name="theme"]');
    themeInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.checked) {
                // Aplicar tema imediatamente para preview
                TO.theme.set(this.value);
            }
        });
    });
    
    // Configurar toggle do media card
    const mediaCardToggle = document.getElementById('mediaCard');
    if (mediaCardToggle) {
        mediaCardToggle.addEventListener('change', function() {
            // Mostrar feedback visual
            const label = this.parentElement.querySelector('.form-check-label');
            if (label) {
                label.style.opacity = '0.7';
                setTimeout(() => {
                    label.style.opacity = '1';
                }, 200);
            }
        });
    }
}

// Manipular atualização de preferências
async function handlePreferencesUpdate(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn ? submitBtn.innerHTML : '';
    
    // Desabilitar botão e mostrar loading
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Salvando...';
    }
    
    try {
        const formData = new FormData(form);
        // Garantir que os checkboxes sejam enviados corretamente mesmo quando desmarcados/alternados rapidamente
        const mediaInput = document.getElementById('mediaCard');
        if (mediaInput && mediaInput.checked && !formData.has('media_card')) {
            formData.append('media_card', 'on');
        }
        const advancedSnapshotInput = document.getElementById('advancedSnapshot');
        if (advancedSnapshotInput && advancedSnapshotInput.checked && !formData.has('advanced_snapshot')) {
            formData.append('advanced_snapshot', 'on');
        }
        // Commit de avatar (remover/subir) antes de preferências
        if (window.__pendingAvatarRemove) {
            const delFd = new FormData();
            delFd.append('confirm', '1');
            const delRes = await TO.utils.ajax('/app/profile/avatar/delete', { method: 'POST', body: delFd });
            if (!delRes || !delRes.success) {
                throw new Error(delRes && delRes.message ? delRes.message : 'Erro ao remover foto de perfil');
            }
            window.__pendingAvatarRemove = false;
        }
        if (window.__pendingAvatarFile) {
            const upFd = new FormData();
            upFd.append('avatar', window.__pendingAvatarFile);
            const upRes = await TO.utils.ajax('/app/profile/avatar', { method: 'POST', body: upFd });
            if (!upRes || !upRes.success) {
                throw new Error(upRes && upRes.message ? upRes.message : 'Erro ao enviar foto de perfil');
            }
            window.__pendingAvatarFile = null;
        }
        
        const response = await TO.utils.ajax('/app/profile/preferences', {
            method: 'POST',
            body: formData
        });
        
        if (response.success) {
            TO.utils.showNotification(response.message, 'success');
            // Marcar como salvo e esconder barra
            if (typeof window.__profile_markClean === 'function') window.__profile_markClean();
            
            // Atualizar tema no localStorage
            const selectedTheme = formData.get('theme');
            if (selectedTheme) {
                localStorage.setItem('terminal_theme', selectedTheme);
            }
            // Atualizar timezone imediatamente para refletir em scripts que usam window.USER_TIMEZONE
            const selectedTz = formData.get('timezone');
            if (selectedTz) {
                try { window.USER_TIMEZONE = String(selectedTz); } catch(_) {}
                try { localStorage.setItem('terminal_timezone', String(selectedTz)); } catch(_) {}
            }
            if (window.__profile_afterSaveNavigateTo) {
                const href = window.__profile_afterSaveNavigateTo;
                window.__profile_afterSaveNavigateTo = null;
                window.location.href = href;
                return;
            }
        } else {
            TO.utils.showNotification(response.message, 'danger');
        }
        
    } catch (error) {
        TO.utils.showNotification('Erro ao salvar preferências. Tente novamente.', 'danger');
        console.error('Erro ao salvar preferências:', error);
    } finally {
        // Restaurar botão
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }
}

// Configurar modal de alteração de senha
function setupChangePasswordModal() {
    const changePasswordForm = document.getElementById('changePasswordForm');
    if (!changePasswordForm) return;
    
    changePasswordForm.addEventListener('submit', handlePasswordChange);
    
    // Validação em tempo real
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmNewPassword');
    
    if (newPasswordInput && confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            validatePasswordConfirmation();
        });
        
        newPasswordInput.addEventListener('input', function() {
            validatePasswordStrength();
            validatePasswordConfirmation();
        });
    }
}

// Mostrar modal de alteração de senha
function showChangePasswordModal() {
    const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
    modal.show();
}

// Manipular alteração de senha
async function handlePasswordChange(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Validar confirmação de senha
    if (!validatePasswordConfirmation()) {
        TO.utils.showNotification('As senhas não coincidem', 'danger');
        return;
    }
    
    // Desabilitar botão e mostrar loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Alterando...';
    
    try {
        const formData = new FormData(form);
        
        const response = await TO.utils.ajax('/app/profile/change-password', {
            method: 'POST',
            body: formData
        });
        
        if (response.success) {
            TO.utils.showNotification(response.message, 'success');
            
            // Fechar modal e limpar formulário
            const modal = bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'));
            modal.hide();
            form.reset();
            
            // Remover classes de validação
            form.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
                el.classList.remove('is-valid', 'is-invalid');
            });
        } else {
            TO.utils.showNotification(response.message, 'danger');
        }
        
    } catch (error) {
        TO.utils.showNotification('Erro ao alterar senha. Tente novamente.', 'danger');
        console.error('Erro ao alterar senha:', error);
    } finally {
        // Restaurar botão
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

// Validar confirmação de senha
function validatePasswordConfirmation() {
    const newPassword = document.getElementById('newPassword');
    const confirmPassword = document.getElementById('confirmNewPassword');
    
    if (!newPassword || !confirmPassword) return true;
    
    const isValid = newPassword.value === confirmPassword.value;
    
    if (confirmPassword.value.length > 0) {
        if (isValid) {
            confirmPassword.classList.remove('is-invalid');
            confirmPassword.classList.add('is-valid');
        } else {
            confirmPassword.classList.remove('is-valid');
            confirmPassword.classList.add('is-invalid');
        }
    } else {
        confirmPassword.classList.remove('is-valid', 'is-invalid');
    }
    
    return isValid;
}

// Validar força da senha
function validatePasswordStrength() {
    const passwordInput = document.getElementById('newPassword');
    if (!passwordInput) return;
    
    const password = passwordInput.value;
    const strength = calculatePasswordStrength(password);
    
    // Remover indicador existente
    let strengthIndicator = passwordInput.parentElement.querySelector('.password-strength');
    if (strengthIndicator) {
        strengthIndicator.remove();
    }
    
    // Adicionar novo indicador se houver senha
    if (password.length > 0) {
        strengthIndicator = document.createElement('div');
        strengthIndicator.className = 'password-strength mt-1';
        
        const strengthBar = document.createElement('div');
        strengthBar.className = 'progress';
        strengthBar.style.height = '4px';
        
        const strengthProgress = document.createElement('div');
        strengthProgress.className = `progress-bar bg-${getStrengthColor(strength.score)}`;
        strengthProgress.style.width = `${(strength.score / 4) * 100}%`;
        
        strengthBar.appendChild(strengthProgress);
        strengthIndicator.appendChild(strengthBar);
        
        const strengthText = document.createElement('small');
        strengthText.className = `text-${getStrengthColor(strength.score)} mt-1 d-block`;
        strengthText.textContent = getStrengthLabel(strength.score);
        
        strengthIndicator.appendChild(strengthText);
        passwordInput.parentElement.appendChild(strengthIndicator);
    }
}

// Calcular força da senha
function calculatePasswordStrength(password) {
    let score = 0;
    const feedback = [];
    
    // Comprimento
    if (password.length >= 8) score++;
    else feedback.push('Pelo menos 8 caracteres');
    
    // Letra minúscula
    if (/[a-z]/.test(password)) score++;
    else feedback.push('Uma letra minúscula');
    
    // Letra maiúscula
    if (/[A-Z]/.test(password)) score++;
    else feedback.push('Uma letra maiúscula');
    
    // Número
    if (/\d/.test(password)) score++;
    else feedback.push('Um número');
    
    // Caractere especial
    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) score++;
    else feedback.push('Um caractere especial');
    
    return { score: Math.min(score, 4), feedback };
}

// Obter cor da força da senha
function getStrengthColor(score) {
    const colors = ['danger', 'danger', 'warning', 'info', 'success'];
    return colors[score] || 'danger';
}

// Obter label da força da senha
function getStrengthLabel(score) {
    const labels = ['Muito fraca', 'Fraca', 'Regular', 'Boa', 'Forte'];
    return labels[score] || 'Muito fraca';
}

// Configurar preview de temas
function setupThemePreview() {
    const themeOptions = document.querySelectorAll('.theme-option');
    
    themeOptions.forEach(option => {
        const input = option.querySelector('input[type="radio"]');
        const preview = option.querySelector('.theme-preview');
        
        // Hover effect
        option.addEventListener('mouseenter', function() {
            if (!input.checked) {
                preview.style.transform = 'scale(1.05)';
                preview.style.transition = 'transform 0.2s ease';
            }
        });
        
        option.addEventListener('mouseleave', function() {
            if (!input.checked) {
                preview.style.transform = 'scale(1)';
            }
        });
        
        // Click effect
        option.addEventListener('click', function() {
            input.checked = true;
            input.dispatchEvent(new Event('change'));
        });
    });
}

// Estado de alterações e barra/modal
let profileDirty = false;
let profileInitial = { theme: null, media_card: null, advanced_snapshot: null, timezone: null };
window.__profile_afterSaveNavigateTo = null;

function setupUnsavedChangesUX() {
    const form = document.getElementById('preferencesForm');
    if (!form) return;

    const themeInput = form.querySelector('input[name="theme"]:checked');
    const mediaInput = document.getElementById('mediaCard');
    const advancedSnapshotInput = document.getElementById('advancedSnapshot');
    const timezoneSelect = form.querySelector('select[name="timezone"], #timezone');
    profileInitial.theme = themeInput ? themeInput.value : null;
    profileInitial.media_card = !!(mediaInput && mediaInput.checked);
    profileInitial.advanced_snapshot = !!(advancedSnapshotInput && advancedSnapshotInput.checked);
    profileInitial.timezone = timezoneSelect ? timezoneSelect.value : null;

    let showedConfirm = false;
    function ensureConfirmToast(){
        if (window.__activeToastEl) { TO.utils.shakeActiveToast(); return; }
        TO.utils.confirmToast('Você tem alterações não salvas.', {
            type: 'warning',
            primaryText: 'Salvar',
            secondaryText: 'Descartar',
            primaryAction: () => { if (typeof form.requestSubmit === 'function') form.requestSubmit(); else form.dispatchEvent(new Event('submit', { cancelable: true })); },
            secondaryAction: () => { restoreSnapshot(); markProfileClean(); }
        });
    }
    function markProfileDirty(){
        profileDirty = true;
        if (!showedConfirm) { showedConfirm = true; ensureConfirmToast(); }
    }
    function markProfileClean(){
        profileDirty = false;
        showedConfirm = false;
        const th = form.querySelector('input[name="theme"]:checked');
        profileInitial.theme = th ? th.value : profileInitial.theme;
        profileInitial.media_card = !!(mediaInput && mediaInput.checked);
        profileInitial.advanced_snapshot = !!(advancedSnapshotInput && advancedSnapshotInput.checked);
        profileInitial.timezone = timezoneSelect ? timezoneSelect.value : profileInitial.timezone;
    }
    function restoreSnapshot(){
        if (profileInitial.theme) {
            const toCheck = form.querySelector(`input[name="theme"][value="${profileInitial.theme}"]`);
            if (toCheck) { toCheck.checked = true; TO.theme.set(profileInitial.theme); }
        }
        if (mediaInput) mediaInput.checked = !!profileInitial.media_card;
        if (advancedSnapshotInput) advancedSnapshotInput.checked = !!profileInitial.advanced_snapshot;
        if (timezoneSelect && typeof profileInitial.timezone === 'string') {
            timezoneSelect.value = profileInitial.timezone;
        }
        // Reverter avatar preview se houver staging
        if (window.__avatarOriginalUrl) {
            const img = document.getElementById('profileAvatarImg');
            const placeholder = document.getElementById('profileAvatarPlaceholder');
            if (window.__avatarOriginalUrl && img) img.src = window.__avatarOriginalUrl;
            if (!window.__avatarOriginalUrl && placeholder) placeholder.classList.remove('d-none');
            window.__pendingAvatarFile = null;
            window.__pendingAvatarRemove = false;
        }
    }
    window.__profile_markClean = markProfileClean;
    window.__profile_markDirty = markProfileDirty;

    form.querySelectorAll('input[name="theme"]').forEach(r => r.addEventListener('change', markProfileDirty));
    if (mediaInput) mediaInput.addEventListener('change', markProfileDirty);
    if (advancedSnapshotInput) advancedSnapshotInput.addEventListener('change', markProfileDirty);
    if (timezoneSelect) timezoneSelect.addEventListener('change', markProfileDirty);

    // Não há barra fixa; ações via toast

    window.addEventListener('beforeunload', function(e){ if (!profileDirty) return; e.preventDefault(); e.returnValue = ''; });
    document.addEventListener('click', function(e){
        if (!profileDirty) return;
        const a = e.target.closest('a[href]');
        if (a && a.getAttribute('href') && !a.hasAttribute('data-allow-unsaved')) {
            e.preventDefault();
            window.__profile_afterSaveNavigateTo = a.getAttribute('href');
            if (window.__activeToastEl) {
                TO.utils.shakeActiveToast();
            } else {
                TO.utils.confirmToast('Você tem alterações não salvas.', {
                    type: 'warning',
                    primaryText: 'Salvar e sair',
                    secondaryText: 'Descartar',
                    primaryAction: () => { if (typeof form.requestSubmit === 'function') form.requestSubmit(); else form.dispatchEvent(new Event('submit', { cancelable: true })); },
                    secondaryAction: () => { restoreSnapshot(); markProfileClean(); window.location.href = window.__profile_afterSaveNavigateTo; }
                });
            }
        }
    }, true);
}

function setupAvatarUpload(){
    const btn = document.getElementById('avatarChangeBtn');
    const input = document.getElementById('avatarInput');
    const wrapper = document.querySelector('.profile-avatar-wrapper');
    const img = document.getElementById('profileAvatarImg');
    const placeholder = document.getElementById('profileAvatarPlaceholder');
    if (!btn || !input || !wrapper) return;

    // Guardar URL original para restauração
    window.__avatarOriginalUrl = img ? img.src : '';
    window.__pendingAvatarFile = null;
    window.__pendingAvatarRemove = false;

    function openUpload(){ input.click(); }
    function stageRemove(){
        window.__pendingAvatarRemove = true;
        window.__pendingAvatarFile = null;
        if (img) { img.remove(); }
        if (placeholder) { placeholder.classList.remove('d-none'); }
        // marcar como sujo
        const mediaInput = document.getElementById('mediaCard');
        if (mediaInput) mediaInput.dispatchEvent(new Event('change'));
        if (window.__profile_markDirty) window.__profile_markDirty();
    }

    btn.addEventListener('click', () => {
        if (img) {
            TO.utils.confirmToast('Foto de perfil', {
                type: 'info',
                primaryText: 'Alterar foto',
                secondaryText: 'Remover foto',
                primaryAction: () => openUpload(),
                secondaryAction: () => stageRemove()
            });
        } else {
            openUpload();
        }
    });

    wrapper.addEventListener('click', (e) => {
        if (e.target.closest('#avatarChangeBtn')) return;
        if (img) {
            TO.utils.confirmToast('Foto de perfil', {
                type: 'info',
                primaryText: 'Alterar foto',
                secondaryText: 'Remover foto',
                primaryAction: () => openUpload(),
                secondaryAction: () => stageRemove()
            });
        } else {
            openUpload();
        }
    });

    input.addEventListener('change', async () => {
        const file = input.files && input.files[0];
        if (!file) return;
        if (file.size > 3 * 1024 * 1024) { TO.utils.showNotification('Imagem muito grande (máx. 3MB)', 'danger'); input.value = ''; return; }
        // Stage file (preview local)
        window.__pendingAvatarFile = file;
        window.__pendingAvatarRemove = false;
        const url = URL.createObjectURL(file);
        if (img) { img.src = url; }
        if (!img && placeholder) {
            const newImg = document.createElement('img');
            newImg.id = 'profileAvatarImg';
            newImg.src = url;
            newImg.alt = 'Avatar';
            newImg.className = 'rounded-circle';
            newImg.style.width = '96px'; newImg.style.height = '96px'; newImg.style.objectFit = 'cover'; newImg.style.border = '2px solid var(--border-color)';
            placeholder.replaceWith(newImg);
        }
        // marcar como sujo
        const mediaInput = document.getElementById('mediaCard');
        if (mediaInput) mediaInput.dispatchEvent(new Event('change'));
        if (window.__profile_markDirty) window.__profile_markDirty();
        input.value = '';
    });
}

// Exportar funções globais
window.showChangePasswordModal = showChangePasswordModal;
