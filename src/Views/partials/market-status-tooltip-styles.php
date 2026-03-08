<?php
/**
 * Estilos para Tooltips de Status de Mercado
 * 
 * Este arquivo contém todos os estilos CSS para as tooltips de status de mercado
 * que aparecem ao passar o mouse sobre as status bubbles.
 * 
 * Usado em:
 * - Dashboard (logado): src/Views/app/dashboard.php
 * - Homepage (deslogado): src/Views/home/index.php
 */
?>
<style>
/* ========================================================================
   TOOLTIPS DE STATUS DE MERCADO - TEMA CLARO (LIGHT)
   ======================================================================== */

html.light .tooltip.snapshot-tip .tooltip-inner,
html.light .tooltip.market-status-tip .tooltip-inner {
    max-width: 350px;
    padding: 0;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    text-align: left;
    border: 1px solid #dee2e6;
}

html.light .snap-head {
    font-size: 0.7rem;
    color: #6c757d;
    margin-bottom: 10px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

html.light .snap-name {
    font-size: 1rem;
    color: #212529;
    font-weight: 700;
    margin-bottom: 14px;
}

html.light .snap-grid .lbl {
    font-size: 0.65rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    font-weight: 600;
}

html.light .snap-grid .val {
    font-size: 0.95rem;
    color: #212529;
    font-weight: 700;
}

html.light .snap-grid .val.pos {
    color: #198754;
}

html.light .snap-grid .val.neg {
    color: #dc3545;
}

html.light .snap-grid .val.neu {
    color: #6c757d;
}

/* ========================================================================
   TOOLTIPS DE STATUS DE MERCADO - TEMA DARK-BLUE
   ======================================================================== */

html.dark-blue .tooltip.snapshot-tip .tooltip-inner,
html.dark-blue .tooltip.market-status-tip .tooltip-inner {
    max-width: 350px;
    padding: 0;
    background: linear-gradient(135deg, #1a2332 0%, #0d1520 100%);
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.6);
    text-align: left;
    border: 1px solid rgba(96, 165, 250, 0.2);
}

html.dark-blue .snap-head {
    font-size: 0.7rem;
    color: #94a3b8;
    margin-bottom: 10px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

html.dark-blue .snap-name {
    font-size: 1rem;
    color: #f1f5f9;
    font-weight: 700;
    margin-bottom: 14px;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

html.dark-blue .snap-grid .lbl {
    font-size: 0.65rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    font-weight: 600;
}

html.dark-blue .snap-grid .val {
    font-size: 0.95rem;
    color: #e2e8f0;
    font-weight: 700;
}

html.dark-blue .snap-grid .val.pos {
    color: #34d399;
}

html.dark-blue .snap-grid .val.neg {
    color: #f87171;
}

html.dark-blue .snap-grid .val.neu {
    color: #94a3b8;
}

/* ========================================================================
   TOOLTIPS DE STATUS DE MERCADO - TEMA ALL-BLACK
   ======================================================================== */

html.all-black .tooltip.snapshot-tip .tooltip-inner,
html.all-black .tooltip.market-status-tip .tooltip-inner {
    max-width: 350px;
    padding: 0;
    background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.8);
    text-align: left;
    border: 1px solid #333333;
}

html.all-black .snap-head {
    font-size: 0.7rem;
    color: #9ca3af;
    margin-bottom: 10px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

html.all-black .snap-name {
    font-size: 1rem;
    color: #f9fafb;
    font-weight: 700;
    margin-bottom: 14px;
    text-shadow: 0 1px 2px rgba(0,0,0,0.5);
}

html.all-black .snap-grid .lbl {
    font-size: 0.65rem;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    font-weight: 600;
}

html.all-black .snap-grid .val {
    font-size: 0.95rem;
    color: #e5e7eb;
    font-weight: 700;
}

html.all-black .snap-grid .val.pos {
    color: #10b981;
}

html.all-black .snap-grid .val.neg {
    color: #ef4444;
}

html.all-black .snap-grid .val.neu {
    color: #9ca3af;
}

/* ========================================================================
   ESTRUTURA COMUM (TODOS OS TEMAS)
   ======================================================================== */

.tooltip.snapshot-tip,
.tooltip.market-status-tip {
    opacity: 1 !important;
}

.snap-tip {
    padding: 14px 18px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.snap-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.snap-grid > div {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

/* Espaçamento entre snapshot e preço */
.snapcam {
    margin-right: 8px;
}

.snap-simple {
    margin-right: 8px;
}
</style>
