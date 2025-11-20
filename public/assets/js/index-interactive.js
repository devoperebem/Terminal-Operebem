/**
 * Terminal Operebem - Index Page Interactive Features
 * Modern JavaScript for enhanced user experience
 */

document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // ========================================
    // HEADER SCROLL EFFECT
    // ========================================
    
    const header = document.querySelector('.modern-header');
    let lastScrollTop = 0;
    
    function handleHeaderScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > 100) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        
        lastScrollTop = scrollTop;
    }
    
    window.addEventListener('scroll', throttle(handleHeaderScroll, 10));

    // ========================================
    // SMOOTH SCROLLING FOR NAVIGATION LINKS
    // ========================================
    
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            if (href && href !== '#') {
                const target = document.querySelector(href);
                if (target) {
                    const headerHeight = header.offsetHeight;
                    const targetPosition = target.offsetTop - headerHeight - 20;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });

    // ========================================
    // ANIMATED COUNTERS
    // ========================================
    
    const counters = document.querySelectorAll('.statistics-section .stat-number');
    let countersAnimated = false;
    
    // Special counter: 0→1024 GB then 1.0→target TB (0.1 steps)
    function animateGbTb(counter) {
        const targetTb = parseFloat(counter.getAttribute('data-target-tb') || '2');
        const stage1Dur = 1200; // ms for GB phase
        const stage2Dur = 1200; // ms for TB phase
        const start = performance.now();

        function frame(now){
            const elapsed = now - start;
            if (elapsed < stage1Dur) {
                const ratio = Math.max(0, Math.min(1, elapsed / stage1Dur));
                const gb = Math.min(1024, Math.floor(1024 * ratio));
                try { counter.textContent = gb.toLocaleString('pt-BR') + ' GB'; } catch { counter.textContent = gb + ' GB'; }
                requestAnimationFrame(frame);
                return;
            }
            const t2 = Math.min(stage2Dur, Math.max(0, elapsed - stage1Dur));
            const steps = Math.max(0, Math.round((targetTb - 1) * 10)); // e.g., 1.0..2.0 => 10 steps
            const idx = Math.min(steps, Math.floor((t2 / stage2Dur) * steps));
            const tb = 1 + (idx / 10);
            const txt = tb.toFixed(1).replace('.', ',') + ' TB';
            counter.textContent = txt;
            if (idx < steps) requestAnimationFrame(frame);
        }
        requestAnimationFrame(frame);
    }
    
    function animateCounters() {
        if (countersAnimated) return;
        
        const statisticsSection = document.querySelector('.statistics-section');
        if (!statisticsSection) return;
        
        const sectionTop = statisticsSection.offsetTop;
        const sectionHeight = statisticsSection.offsetHeight;
        const scrollTop = window.pageYOffset;
        const windowHeight = window.innerHeight;
        
        if (scrollTop + windowHeight > sectionTop + sectionHeight / 2) {
            countersAnimated = true;
            
            counters.forEach(counter => {
                // Special mode (GB→TB)
                if (counter.getAttribute('data-counter-mode') === 'gb-tb') {
                    animateGbTb(counter);
                    return;
                }

                // Base target (supports decimals)
                let target = parseFloat(counter.getAttribute('data-target'));
                const suffix = counter.getAttribute('data-suffix') || '';
                const randMinAttr = counter.getAttribute('data-random-min');
                const randMaxAttr = counter.getAttribute('data-random-max');
                const decimalsAttr = counter.getAttribute('data-decimals');
                const decimals = decimalsAttr !== null ? parseInt(decimalsAttr, 10) : 0;

                // Randomização (ex.: uptime 99.7–99.9 com 1 casa decimal)
                if (randMinAttr !== null && randMaxAttr !== null) {
                    const min = parseFloat(randMinAttr);
                    const max = parseFloat(randMaxAttr);
                    if (!isNaN(min) && !isNaN(max) && max >= min) {
                        const rand = Math.random() * (max - min) + min;
                        target = parseFloat(rand.toFixed(decimals));
                    }
                }

                if (isNaN(target)) return; // Skip non-numeric values

                const duration = 2000; // 2 seconds
                const steps = Math.max(1, Math.floor(duration / 16));
                const increment = target / steps;
                let current = 0;

                const nf = new Intl.NumberFormat('pt-BR', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                });

                const updateCounter = () => {
                    current += increment;
                    const value = current < target ? current : target;
                    if (value < target) {
                        counter.textContent = nf.format(value) + suffix;
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.textContent = nf.format(target) + suffix;
                    }
                };

                updateCounter();
            });
        }
    }
    
    window.addEventListener('scroll', throttle(animateCounters, 100));
    // Executa uma vez no load para o caso da seção já estar visível
    animateCounters();

    // ========================================
    // INTERSECTION OBSERVER FOR ANIMATIONS
    // ========================================
    
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);
    
    // Observe elements for animation
    document.querySelectorAll('.feature-card, .stat-card').forEach(el => {
        observer.observe(el);
    });

    // ========================================
    // PARALLAX EFFECT FOR BACKGROUND ELEMENTS
    // ========================================
    
    const floatingCircles = document.querySelectorAll('.floating-circle');
    
    function updateParallax() {
        const scrollTop = window.pageYOffset;
        const rate = scrollTop * -0.5;
        
        floatingCircles.forEach((circle, index) => {
            const speed = (index + 1) * 0.1;
            circle.style.transform = `translateY(${rate * speed}px)`;
        });
    }
    
    window.addEventListener('scroll', throttle(updateParallax, 16));

    // Cursor follow effect removido a pedido do usuário

    // ========================================
    // BUTTON HOVER EFFECTS
    // ========================================
    
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // ========================================
    // CARD HOVER EFFECTS
    // ========================================
    
    document.querySelectorAll('.feature-card, .stat-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });

    // ========================================
    // LOADING ANIMATION
    // ========================================
    
    window.addEventListener('load', function() {
        document.body.classList.add('loaded');
        
        // Trigger initial animations
        setTimeout(() => {
            document.querySelectorAll('.animate-fade-in-up').forEach((el, index) => {
                setTimeout(() => {
                    el.classList.add('animate-in');
                }, index * 200);
            });
        }, 300);
    });

    // ========================================
    // PERFORMANCE OPTIMIZATION
    // ========================================
    
    // Preload critical images
    const criticalImages = [
        '/assets/images/favicon.png'
    ];
    
    criticalImages.forEach(src => {
        const img = new Image();
        img.src = src;
    });

    // ========================================
    // UTILITY FUNCTIONS
    // ========================================
    
    function throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
    
    function debounce(func, wait, immediate) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            const later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }

    // ========================================
    // ERROR HANDLING
    // ========================================
    
    window.addEventListener('error', function(e) {
        console.warn('JavaScript error handled:', e.error);
    });

    // Acessibilidade: skip-link removido a pedido do usuário (evitar bleed visual no topo)

    // Console message moved to global app.js security notice
});

// CSS do cursor customizado removido
