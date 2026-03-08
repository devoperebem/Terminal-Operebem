/**
 * Auth Utilities - Common JavaScript functions for authentication flows
 * Terminal Operebem
 */

/**
 * Toggle password visibility in input field
 * @param {HTMLElement} button - Toggle button element
 * @param {HTMLElement} input - Password input element
 */
export function setupPasswordToggle(button, input) {
    if (!button || !input) return;
    
    button.addEventListener('click', function() {
        const icon = this.querySelector('i');
        if (!icon) return;
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
            button.setAttribute('aria-label', 'Ocultar senha');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
            button.setAttribute('aria-label', 'Mostrar senha');
        }
    });
}

/**
 * Calculate password strength score
 * @param {string} password - Password to evaluate
 * @returns {number} Score from 0-100
 */
export function calculatePasswordStrength(password) {
    if (!password) return 0;
    
    let score = 0;
    
    // Length bonuses
    if (password.length >= 8) score += 25;
    if (password.length >= 12) score += 10;
    if (password.length >= 16) score += 5;
    
    // Character variety
    if (/[a-z]/.test(password)) score += 20;
    if (/[A-Z]/.test(password)) score += 20;
    if (/\d/.test(password)) score += 15;
    if (/[^a-zA-Z0-9]/.test(password)) score += 10;
    
    // Penalty for common patterns
    if (/(.)\1{2,}/.test(password)) score -= 10; // Repeated characters
    if (/^(123|abc|qwe)/i.test(password)) score -= 15; // Common sequences
    
    return Math.max(0, Math.min(100, score));
}

/**
 * Get password strength level
 * @param {number} score - Strength score (0-100)
 * @returns {string} Level: 'weak', 'fair', 'good', or 'strong'
 */
export function getPasswordStrengthLevel(score) {
    if (score < 40) return 'weak';
    if (score < 60) return 'fair';
    if (score < 80) return 'good';
    return 'strong';
}

/**
 * Setup password strength meter
 * @param {HTMLElement} input - Password input element
 * @param {HTMLElement} meter - Strength meter bar element
 * @param {Function} callback - Optional callback with (score, level)
 */
export function setupPasswordStrengthMeter(input, meter, callback) {
    if (!input || !meter) return;
    
    function updateMeter() {
        const score = calculatePasswordStrength(input.value);
        const level = getPasswordStrengthLevel(score);
        
        meter.classList.remove('weak', 'fair', 'good', 'strong');
        meter.classList.add(level);
        
        if (callback) callback(score, level);
    }
    
    input.addEventListener('input', updateMeter);
    updateMeter();
}

/**
 * Show alert message in container
 * @param {HTMLElement} container - Alert container element
 * @param {string} type - Alert type: 'success', 'danger', 'warning', 'info'
 * @param {string} message - Message to display
 * @param {boolean} dismissible - Whether alert is dismissible (default: true)
 */
export function showAlert(container, type, message, dismissible = true) {
    if (!container) return;
    
    const iconMap = {
        success: 'fa-check-circle',
        danger: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    const icon = iconMap[type] || 'fa-info-circle';
    const dismissBtn = dismissible ? '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>' : '';
    
    container.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="fas ${icon} me-2"></i>
            ${message}
            ${dismissBtn}
        </div>
    `;
}

/**
 * Validate email format
 * @param {string} email - Email to validate
 * @returns {boolean} True if valid
 */
export function isValidEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Validate CPF format (Brazilian tax ID)
 * @param {string} cpf - CPF to validate
 * @returns {boolean} True if valid
 */
export function isValidCPF(cpf) {
    cpf = cpf.replace(/[^\d]/g, '');
    
    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
    
    let sum = 0;
    for (let i = 0; i < 9; i++) {
        sum += parseInt(cpf.charAt(i)) * (10 - i);
    }
    let digit = 11 - (sum % 11);
    if (digit >= 10) digit = 0;
    if (digit !== parseInt(cpf.charAt(9))) return false;
    
    sum = 0;
    for (let i = 0; i < 10; i++) {
        sum += parseInt(cpf.charAt(i)) * (11 - i);
    }
    digit = 11 - (sum % 11);
    if (digit >= 10) digit = 0;
    if (digit !== parseInt(cpf.charAt(10))) return false;
    
    return true;
}

/**
 * Format CPF with mask
 * @param {string} cpf - CPF to format
 * @returns {string} Formatted CPF (XXX.XXX.XXX-XX)
 */
export function formatCPF(cpf) {
    cpf = cpf.replace(/[^\d]/g, '');
    return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
}

/**
 * Format phone number with mask
 * @param {string} phone - Phone to format
 * @returns {string} Formatted phone
 */
export function formatPhone(phone) {
    phone = phone.replace(/[^\d]/g, '');
    if (phone.length === 11) {
        return phone.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    } else if (phone.length === 10) {
        return phone.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
    }
    return phone;
}

/**
 * Debounce function calls
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} Debounced function
 */
export function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Set button loading state
 * @param {HTMLElement} button - Button element
 * @param {boolean} loading - Whether button is loading
 * @param {string} loadingText - Text to show when loading (default: "Carregando...")
 */
export function setButtonLoading(button, loading, loadingText = 'Carregando...') {
    if (!button) return;
    
    if (loading) {
        button.dataset.originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i>${loadingText}`;
    } else {
        button.disabled = false;
        button.innerHTML = button.dataset.originalText || button.innerHTML;
    }
}

/**
 * Auto-open modal based on URL query parameter
 * @param {string} paramName - Query parameter name (default: 'modal')
 */
export function autoOpenModal(paramName = 'modal') {
    const params = new URLSearchParams(window.location.search);
    const modalParam = params.get(paramName);
    
    if (!modalParam) return;
    
    const modalMap = {
        'login': 'loginModal',
        'register': 'registerModal',
        'forgot': 'forgotPasswordModal'
    };
    
    const modalId = modalMap[modalParam];
    if (!modalId) return;
    
    const modalEl = document.getElementById(modalId);
    if (!modalEl || typeof bootstrap === 'undefined') return;
    
    setTimeout(() => {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
        
        // Clean URL without reload
        const cleanUrl = window.location.pathname;
        window.history.replaceState({}, document.title, cleanUrl);
    }, 300);
}

/**
 * Initialize all auth utilities on page load
 */
export function initAuthUtils() {
    // Auto-open modals
    autoOpenModal();
    
    // Setup password toggles
    document.querySelectorAll('[data-password-toggle]').forEach(button => {
        const targetId = button.dataset.passwordToggle;
        const input = document.getElementById(targetId);
        if (input) setupPasswordToggle(button, input);
    });
    
    // Setup password strength meters
    document.querySelectorAll('[data-password-strength]').forEach(input => {
        const meterId = input.dataset.passwordStrength;
        const meter = document.getElementById(meterId);
        if (meter) setupPasswordStrengthMeter(input, meter);
    });
}

// Auto-initialize if not using as module
if (typeof module === 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAuthUtils);
    } else {
        initAuthUtils();
    }
}
