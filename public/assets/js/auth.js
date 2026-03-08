/**
 * Terminal Operebem - Authentication JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Formulário de Login
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    // Formulário de Registro
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }
    
    // Formulário de Esqueci a Senha
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');
    if (forgotPasswordForm) {
        forgotPasswordForm.addEventListener('submit', handleForgotPassword);
    }
    
    // Validação em tempo real para confirmação de senha
    const passwordField = document.getElementById('registerPassword');
    const confirmPasswordField = document.getElementById('registerPasswordConfirm');
    
    if (passwordField && confirmPasswordField) {
        confirmPasswordField.addEventListener('input', function() {
            validatePasswordMatch();
        });
        
        passwordField.addEventListener('input', function() {
            validatePasswordMatch();
        });
    }
});

// Manipular login
async function handleLogin(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Desabilitar botão e mostrar loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Entrando...';
    
    try {
        const formData = new FormData(form);
        
        const response = await TO.utils.ajax('/login', {
            method: 'POST',
            body: formData
        });
        
        if (response.success) {
            TO.utils.showNotification(response.message, 'success');
            
            // Redirecionar após pequeno delay
            setTimeout(() => {
                window.location.href = response.redirect || '/app/dashboard';
            }, 1000);
        } else {
            TO.utils.showNotification(response.message, 'danger');
        }
        
    } catch (error) {
        TO.utils.showNotification('Erro ao fazer login. Tente novamente.', 'danger');
        console.error('Erro no login:', error);
    } finally {
        // Restaurar botão
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

// Manipular registro
async function handleRegister(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Validar senhas antes de enviar
    if (!validatePasswordMatch()) {
        TO.utils.showNotification('As senhas não coincidem', 'danger');
        return;
    }
    
    // Desabilitar botão e mostrar loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Criando conta...';
    
    try {
        const formData = new FormData(form);
        
        const response = await TO.utils.ajax('/register', {
            method: 'POST',
            body: formData
        });
        
        if (response.success) {
            TO.utils.showNotification(response.message, 'success');
            
            // Fechar modal de registro e abrir modal de login
            const registerModal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
            registerModal.hide();
            
            setTimeout(() => {
                const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
                loginModal.show();
            }, 500);
            
            // Limpar formulário
            form.reset();
        } else {
            TO.utils.showNotification(response.message, 'danger');
        }
        
    } catch (error) {
        TO.utils.showNotification('Erro ao criar conta. Tente novamente.', 'danger');
        console.error('Erro no registro:', error);
    } finally {
        // Restaurar botão
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

// Manipular esqueci a senha
async function handleForgotPassword(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Desabilitar botão e mostrar loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
    
    try {
        const formData = new FormData(form);
        
        const response = await TO.utils.ajax('/forgot-password', {
            method: 'POST',
            body: formData
        });
        
        if (response.success) {
            TO.utils.showNotification(response.message, 'success');
            
            // Fechar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('forgotPasswordModal'));
            modal.hide();
            
            // Limpar formulário
            form.reset();
        } else {
            TO.utils.showNotification(response.message, 'danger');
        }
        
    } catch (error) {
        TO.utils.showNotification('Erro ao enviar solicitação. Tente novamente.', 'danger');
        console.error('Erro no esqueci a senha:', error);
    } finally {
        // Restaurar botão
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

// Validar se as senhas coincidem
function validatePasswordMatch() {
    const password = document.getElementById('registerPassword');
    const confirmPassword = document.getElementById('registerPasswordConfirm');
    
    if (!password || !confirmPassword) return true;
    
    const isValid = password.value === confirmPassword.value;
    
    // Adicionar/remover classes de validação
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

// Validação de força da senha
function validatePasswordStrength(password) {
    const minLength = 8;
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);
    
    let strength = 0;
    let feedback = [];
    
    if (password.length >= minLength) strength++;
    else feedback.push('Pelo menos 8 caracteres');
    
    if (hasUpperCase) strength++;
    else feedback.push('Uma letra maiúscula');
    
    if (hasLowerCase) strength++;
    else feedback.push('Uma letra minúscula');
    
    if (hasNumbers) strength++;
    else feedback.push('Um número');
    
    if (hasSpecialChar) strength++;
    else feedback.push('Um caractere especial');
    
    return {
        strength: strength,
        feedback: feedback,
        isStrong: strength >= 4
    };
}

// Mostrar/ocultar senha
function togglePasswordVisibility(inputId, buttonElement) {
    const input = document.getElementById(inputId);
    const icon = buttonElement.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
