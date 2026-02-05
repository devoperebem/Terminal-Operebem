/**
 * Terminal Operebem - Main JavaScript
 */

// Configuração global
window.TerminalOperebem = {
    config: {
        apiUrl: '/api',
        updateInterval: 30000, // 30 segundos
        theme: document.documentElement.className || 'light'
    },
    
    // Utilitários
    utils: {
        // Fazer requisições AJAX
        ajax: function(url, options = {}) {
            const defaults = {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            };
            
            // Adicionar CSRF token se for POST
            if (options.method === 'POST' && options.body) {
                const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
                if (csrfToken) {
                    if (options.body instanceof FormData) {
                        options.body.append('csrf_token', csrfToken);
                    } else if (typeof options.body === 'string') {
                        try {
                            const data = JSON.parse(options.body);
                            data.csrf_token = csrfToken;
                            options.body = JSON.stringify(data);
                        } catch (e) {
                            // Se não for JSON, adicionar como form data
                            options.body += `&csrf_token=${encodeURIComponent(csrfToken)}`;
                        }
                    }
                }
            }
            
            const config = { ...defaults, ...options };
            // Ensure headers object exists
            config.headers = config.headers || {};
            
            // If sending FormData, let the browser set the Content-Type with boundary
            const isFormData = (config.body && typeof FormData !== 'undefined' && config.body instanceof FormData);
            if (isFormData && config.headers['Content-Type']) {
                delete config.headers['Content-Type'];
            }
            // If sending raw JSON string and no explicit content-type, set application/json
            if (!isFormData && config.body && typeof config.body === 'string' && !config.headers['Content-Type']) {
                config.headers['Content-Type'] = 'application/json';
            }

            return fetch(url, config)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .catch(error => {
                    console.error('Erro na requisição:', error);
                    throw error;
                });
        },
        
        // Mostrar notificações (glass, canto inferior direito, maior)
        showNotification: function(message, type = 'info', duration = 4000) {
            // Remover anteriores
            document.querySelectorAll('.toast-notification').forEach(t => t.remove());
            // Container
            const toast = document.createElement('div');
            toast.className = `toast-notification toast-${type}`;
            toast.style.cssText = [
              'position:fixed',
              'right:24px','bottom:24px','transform:none',
              'z-index:1090','width:min(92vw,560px)','border-radius:14px',
              'padding:14px 16px','display:flex','align-items:center',
              'gap:10px','backdrop-filter:blur(14px)','-webkit-backdrop-filter:blur(14px)',
              'box-shadow:0 20px 60px rgba(0,0,0,0.25)','border:1px solid rgba(255,255,255,0.15)'
            ].join(';');
            const theme = document.documentElement.className || 'light';
            const isLight = theme === 'light';
            toast.style.background = isLight ? 'rgba(255,255,255,0.85)' : 'rgba(17,24,39,0.6)';
            toast.style.color = 'var(--text-primary)';
            const icon = this.getNotificationIcon(type);
            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'btn btn-sm btn-outline-light';
            closeBtn.textContent = 'Fechar';
            closeBtn.addEventListener('click', () => toast.remove());
            const iconEl = document.createElement('i');
            iconEl.className = icon + ' me-1';
            const textEl = document.createElement('div');
            textEl.style.flex = '1';
            textEl.innerHTML = message;
            toast.appendChild(iconEl);
            toast.appendChild(textEl);
            toast.appendChild(closeBtn);
            document.body.appendChild(toast);
            window.__activeToastEl = toast;
            if (duration > 0) {
                setTimeout(() => { if (toast.parentNode) toast.remove(); }, duration);
            }
        },
        // Toast com ações (Salvar/Descartar)
        confirmToast: function(message, options) {
            const opts = Object.assign({
                type: 'info',
                primaryText: 'Salvar', primaryAction: null,
                secondaryText: 'Descartar', secondaryAction: null,
                duration: 0
            }, options || {});
            document.querySelectorAll('.toast-notification').forEach(t => t.remove());
            const toast = document.createElement('div');
            toast.className = `toast-notification toast-${opts.type}`;
            toast.style.cssText = [
              'position:fixed','right:24px','bottom:24px','transform:none',
              'z-index:1090','width:min(92vw,560px)','border-radius:16px',
              'padding:16px 18px','display:flex','align-items:center','gap:12px',
              'backdrop-filter:blur(16px)','-webkit-backdrop-filter:blur(16px)',
              'box-shadow:0 24px 70px rgba(0,0,0,0.3)','border:1px solid rgba(255,255,255,0.15)'
            ].join(';');
            const theme = document.documentElement.className || 'light';
            const isLight = theme === 'light';
            toast.style.background = isLight ? 'rgba(255,255,255,0.9)' : 'rgba(17,24,39,0.7)';
            toast.style.color = 'var(--text-primary)';
            const icon = this.getNotificationIcon(opts.type);
            const iconEl = document.createElement('i'); iconEl.className = icon + ' me-1';
            const textEl = document.createElement('div'); textEl.style.flex = '1'; textEl.innerHTML = message;
            const actions = document.createElement('div'); actions.style.display = 'flex'; actions.style.gap = '8px';
            const discard = document.createElement('button'); discard.type = 'button'; discard.className = 'btn btn-outline-light btn-sm'; discard.textContent = opts.secondaryText;
            const save = document.createElement('button'); save.type = 'button'; save.className = 'btn btn-primary btn-sm'; save.textContent = opts.primaryText;
            discard.addEventListener('click', () => { if (opts.secondaryAction) opts.secondaryAction(); toast.remove(); });
            save.addEventListener('click', () => { if (opts.primaryAction) opts.primaryAction(); toast.remove(); });
            actions.appendChild(discard); actions.appendChild(save);
            toast.appendChild(iconEl); toast.appendChild(textEl); toast.appendChild(actions);
            document.body.appendChild(toast);
            window.__activeToastEl = toast;
            return toast;
        },
        // Agitar o toast ativo
        shakeActiveToast: function(){
            const t = window.__activeToastEl; if (!t) return;
            // inject keyframes once
            if (!document.getElementById('toast-shake-keyframes')){
                const st = document.createElement('style'); st.id = 'toast-shake-keyframes';
                st.textContent = '@keyframes prof-shake {0%{transform:translateX(0)}20%{transform:translateX(-6px)}40%{transform:translateX(6px)}60%{transform:translateX(-4px)}80%{transform:translateX(4px)}100%{transform:translateX(0)}}';
                document.head.appendChild(st);
            }
            t.style.animation = 'none';
            // force reflow
            void t.offsetWidth;
            t.style.animation = 'prof-shake 450ms ease-in-out';
        },
        
        getNotificationIcon: function(type) {
            const icons = {
                'success': 'fas fa-check-circle',
                'danger': 'fas fa-exclamation-triangle',
                'warning': 'fas fa-exclamation-circle',
                'info': 'fas fa-info-circle'
            };
            return icons[type] || icons.info;
        },
        
        // Formatar números
        formatNumber: function(num, decimals = 2) {
            return new Intl.NumberFormat('pt-BR', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(num);
        },
        
        // Formatar moeda
        formatCurrency: function(value, currency = 'BRL') {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: currency
            }).format(value);
        },
        
        // Formatar porcentagem
        formatPercent: function(value, decimals = 2) {
            return new Intl.NumberFormat('pt-BR', {
                style: 'percent',
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(value / 100);
        },
        
        // Debounce function
        debounce: function(func, wait, immediate) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    timeout = null;
                    if (!immediate) func(...args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func(...args);
            };
        }
    },
    
    // Gerenciamento de temas
    theme: {
        current: document.documentElement.className || 'light',
        themes: ['light','dark-blue','all-black'],
        
        set: function(theme) {
            document.documentElement.className = theme;
            this.current = theme;
            localStorage.setItem('terminal_theme', theme);
            
            // Disparar evento personalizado
            window.dispatchEvent(new CustomEvent('themeChanged', { 
                detail: { theme: theme } 
            }));
        },
        
        get: function() {
            return this.current;
        },
        
        init: function() {
            // Carregar tema salvo; se não houver, detectar do SO na primeira visita
            const savedTheme = localStorage.getItem('terminal_theme');
            if (savedTheme) {
                const normalized = (savedTheme === 'dark') ? 'all-black' : savedTheme;
                this.set(normalized);
            } else {
                try {
                    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                    const detected = prefersDark ? 'all-black' : 'light';
                    this.set(detected);
                } catch(e) {
                    this.set('light');
                }
            }
        },
        cycle: function(){
            const list = Array.isArray(this.themes) && this.themes.length ? this.themes : ['light','dark-blue','all-black'];
            const cur = this.get();
            const idx = Math.max(0, list.indexOf(cur));
            const next = list[(idx + 1) % list.length];
            this.set(next);
        }
    }
};

// Inicialização quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tema
    TerminalOperebem.theme.init();
    // Sincronizar ícones do botão de tema com o tema atual
    function updateThemeToggleIcons() {
        const theme = TerminalOperebem.theme.get();
        document.querySelectorAll('.theme-toggle-btn i').forEach(icon => {
            icon.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        });
    }
    updateThemeToggleIcons();
    window.addEventListener('themeChanged', updateThemeToggleIcons);
    // Click handlers globais para botões de tema (ciclo)
    document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            TerminalOperebem.theme.cycle();
        });
    });
    
    // Converter tooltips custom (pseudo-element) para Bootstrap tooltips
    try {
        const pseudoTips = document.querySelectorAll('.tooltip-target, .tooltip-target-left');
        pseudoTips.forEach(function(el){
            const text = (el.getAttribute('data-tooltip') || '').trim();
            if (text !== '') {
                if (!el.hasAttribute('title')) el.setAttribute('title', text);
                el.setAttribute('data-bs-toggle', 'tooltip');
                if (!el.hasAttribute('data-bs-placement')) {
                    // Sempre usar placement 'top' para consistência visual
                    el.setAttribute('data-bs-placement', 'top');
                }
            }
        });
    } catch(_) {}
    
    // Configurar tooltips do Bootstrap (container no body para evitar clipping/z-index)
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, { container: 'body', boundary: 'viewport' });
    });
    
    // Configurar popovers do Bootstrap
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl, { container: 'body', boundary: 'viewport' });
    });

    // Evitar tooltips sobre modais e garantir limpeza de backdrop (sem loops pesados)
    try {
        document.addEventListener('show.bs.modal', function(){
            try {
                // Remover tooltips visíveis que não sejam do tipo "snapshot-tip"
                document.querySelectorAll('.tooltip.show:not(.snapshot-tip)').forEach(function(tt){ tt.remove(); });
                document.body.classList.add('modal-tooltips-hidden');
            } catch(_){}
        });
        document.addEventListener('hidden.bs.modal', function(){
            try {
                document.body.classList.remove('modal-tooltips-hidden');
                // Se não houver nenhum modal aberto, forçar limpeza de estados/backdrops que possam ter ficado
                setTimeout(function(){
                    if (!document.querySelector('.modal.show')) {
                        document.querySelectorAll('.modal-backdrop').forEach(function(bd){ bd.remove(); });
                        document.body.classList.remove('modal-open');
                        document.body.style.removeProperty('overflow');
                        document.body.style.removeProperty('padding-right');
                    }
                }, 30);
            } catch(_){}
        });
    } catch(_) {}
    
    // Auto-hide apenas alerts de feedback (com classe .alert-auto-dismiss) após 10 segundos
    const autoDismissAlerts = document.querySelectorAll('.alert-auto-dismiss');
    autoDismissAlerts.forEach(alert => {
        setTimeout(() => {
            if (alert.parentNode) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 10000);
    });
    
    // Smooth scroll para links âncora
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            if (href && href !== '#') {
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

    // Console Security Notice (global)
    try {
        console.log(
          "%c🚀 TERMINAL OPEREBEM — AVISO DE SEGURANÇA",
          "color: #d4af37; font-weight: 700; font-size: 16px; text-transform: uppercase;"
        );
        console.log(
          "\n⚠️ %cEste sistema é destinado exclusivamente a fins educacionais e de estudo de mercado.",
          "color: #e0e0e0; font-weight: 600;"
        );
        console.log(
          "%cQualquer tentativa de automação, cópia, webscrapping, engenharia reversa ou manipulação indevida constitui violação dos Termos de Uso e da Lei de Direitos Autorais (Lei nº 9.610/98), podendo resultar em medidas legais.",
          "color: #a9a9a9; font-size: 13px;"
        );
        console.log(
          "\n%cO Grupo OpereBem valoriza a transparência, a ética e a segurança de sua comunidade.",
          "color: #d4af37; font-weight: 600; font-size: 13px;"
        );
        console.log(
          "%cSe você chegou até aqui por curiosidade, sem problema 😊 — mas lembre-se: conhecimento técnico é bem-vindo, desde que usado com responsabilidade.",
          "color: #bfbfbf; font-size: 13px;"
        );
        console.log(
          "\n%c© 2025 Grupo OpereBem. Todos os direitos reservados.",
          "color: #808080; font-size: 12px; font-style: italic;"
        );
        console.log(
          "%c═══════════════════════════════════════════════════════════",
          "color: #555;"
        );
    } catch(e) { /* noop */ }
});

// Função global para abrir ferramentas
window.openTool = function(tool) {
    TerminalOperebem.utils.showNotification(
        `Ferramenta "${tool}" será implementada em breve!`,
        'info'
    );
};

// Exportar para uso global
window.TO = TerminalOperebem;

// Silenciar erros de extensões (ex.: inpage.js/metamask) no console do app
try {
    window.addEventListener('error', function(ev){
        try {
            const msg = (ev && ev.message) ? String(ev.message) : '';
            const file = (ev && ev.filename) ? String(ev.filename) : '';
            if (/inpage\.js/i.test(file) || /isTrust/i.test(msg)) {
                ev.preventDefault();
            }
        } catch(_){ }
    }, true);
    window.addEventListener('unhandledrejection', function(ev){
        try {
            const rsn = ev && ev.reason ? String(ev.reason) : '';
            if (/inpage\.js/i.test(rsn) || /isTrust/i.test(rsn)) {
                ev.preventDefault();
            }
        } catch(_){ }
    });
} catch(_){ }

// Evitar warning de aria-hidden com foco em botões de modal em transições rápidas
try {
    document.addEventListener('hide.bs.modal', function(){
        try { if (document.activeElement && typeof document.activeElement.blur === 'function') { document.activeElement.blur(); } } catch(_){ }
    });
} catch(_){ }
