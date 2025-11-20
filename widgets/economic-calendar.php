<?php
$defaultCountries = '110,17,29,25,32,6,37,26,5,22,39,14,48,10,35,7,43,38,4,36,12,72';
$defaultCalType = 'week';
$defaultImportance = '2,3';
$lang = '12';
?>
<style>
.econcal-root{position:relative}
.econcal-viewport{width:100%;max-width:100%;height:600px;overflow:hidden;border-radius:12px;margin:0;background:var(--card-bg, #fff);border:1px solid var(--border-color, rgba(0,0,0,.12));box-shadow:0 4px 12px rgba(0,0,0,.08)}
.econcal-header{position:absolute;top:0;left:0;right:0;height:52px;display:none;align-items:center;justify-content:space-between;padding:0 12px;background:#fff;border-bottom:2px solid #0d6efd;border-radius:12px 12px 0 0;z-index:2}
.econcal-root:not(.econcal-hosted) .econcal-header{display:flex}
.econcal-title{font-size:15px;font-weight:600;color:var(--text-primary, #111)}
.econcal-filter{position:relative}
.econcal-filter-btn{background:var(--secondary-color, #0d6efd);color:#fff;border:0;border-radius:6px;padding:6px 10px;font-size:12px;display:flex;align-items:center;gap:6px;cursor:pointer}
.econcal-filter-btn:hover{filter:brightness(0.95)}
.econcal-color-btn{background:transparent;border:1px solid var(--border-color, rgba(0,0,0,.12));color:var(--text-primary,#111);border-radius:6px;padding:6px 8px;font-size:12px;display:inline-flex;align-items:center;justify-content:center;margin-right:6px;cursor:pointer}
.econcal-color-btn:hover{background:var(--bg-secondary, rgba(0,0,0,0.04))}
.econcal-dropdown{position:absolute;top:100%;right:0;background:#fff;border:1px solid rgba(0,0,0,.12);border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.15);padding:12px;min-width:280px;display:none;flex-direction:column;gap:12px;z-index:10000;box-sizing:border-box;margin-top:4px}
.econcal-dropdown.show{display:flex}
.econcal-root.econcal-hosted .econcal-dropdown{display:none !important; position:static !important}
.dark-blue .econcal-dropdown, .all-black .econcal-dropdown { background: var(--card-bg, #242526); border-color: var(--border-color, #3a3b3c); }
.dark-blue .econcal-title, .all-black .econcal-title { color: var(--text-primary, #e4e6eb); }
.econcal-section h4{margin:0 0 6px 0;font-size:14px;border-bottom:1px solid rgba(0,0,0,.12);padding-bottom:4px}
.econcal-section button{background:#0d6efd;color:#fff;border:0;border-radius:4px;padding:6px 10px;font-size:11px;margin:2px;display:inline-block;cursor:pointer}
.econcal-section button:hover{background:#0b5ed7}
/* cortes finos para esconder barra superior da Investing e eliminar folgas laterais/inferior */
:root{--econcal-cut-top:68px;--econcal-cut-side:2px;--econcal-cut-bottom:0px}
.econcal-iframe{width:100%;height:calc(600px + var(--econcal-cut-top) + var(--econcal-cut-bottom));transform:translateY(calc(-1*var(--econcal-cut-top)));clip-path:inset(var(--econcal-cut-top) var(--econcal-cut-side) var(--econcal-cut-bottom) var(--econcal-cut-side));border:0;display:block;overflow-x:hidden;overflow-y:auto;position:relative;z-index:1}
.econcal-root.econcal-dd-open .econcal-iframe{pointer-events:none}
/* When dropdown is portaled to body, ensure positioning doesn't inherit top/right */
.econcal-dd-portal{ right:auto !important; top:auto !important; max-width:420px; }
/* CSS customizado injetado no iframe para modo compacto */
.econcal-compact-mode{font-size:12px;line-height:1.3}
.dark-blue .econcal-viewport,.all-black .econcal-viewport{background:var(--card-bg, #242526);border-color:var(--border-color, #3a3b3c);box-shadow:0 4px 12px rgba(0,0,0,.4)}
.dark-blue .econcal-header,.all-black .econcal-header{background:var(--card-bg, #242526);border-bottom-color:#0d84ff}
.dark-blue .econcal-dropdown,.all-black .econcal-dropdown{background:var(--card-bg, #242526);border-color:var(--border-color, #3a3b3c)}
.econcal-root.econcal-hosted .econcal-header{display:none}
.econcal-root.econcal-hosted .econcal-viewport{background:transparent;border:none;box-shadow:none;border-radius:0}
.econcal-loading{position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,0.95);display:none;align-items:center;justify-content:center;z-index:10;border-radius:12px}
.econcal-loading.active{display:flex}
.dark-blue .econcal-loading,.all-black .econcal-loading{background:rgba(36,37,38,0.95)}
.econcal-spinner{width:48px;height:48px;border:4px solid rgba(13,110,253,0.2);border-top-color:#0d6efd;border-radius:50%;animation:econcal-spin 0.8s linear infinite}
@keyframes econcal-spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}

/* Filtro visual do iframe quando em tema escuro e usuário quer adaptar o calendário ao dark */
.dark-blue .econcal-root.force-dark .econcal-iframe,
.all-black .econcal-root.force-dark .econcal-iframe{
  filter: invert(1) hue-rotate(180deg) contrast(1.05) brightness(0.9);
}

/* Dropdown dentro do modal: sem aparência de card dentro do modal */
#econcal-modal-body .econcal-dropdown{ position:static; display:flex; background:transparent; border:0; box-shadow:none; border-radius:0; padding:0; }
#econcal-modal-body .econcal-section h4{ color:var(--text-primary,#111); border-bottom:1px solid var(--border-color, rgba(0,0,0,.12)); margin-top:8px; }
#econcal-modal-body .econcal-section button{ background:var(--secondary-color,#0d6efd); color:#fff; }
</style>
<div class="econcal-widget econcal-root" data-countries="<?= htmlspecialchars($defaultCountries, ENT_QUOTES, 'UTF-8') ?>" data-caltype="<?= htmlspecialchars($defaultCalType, ENT_QUOTES, 'UTF-8') ?>" data-importance="<?= htmlspecialchars($defaultImportance, ENT_QUOTES, 'UTF-8') ?>" data-lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
  <div class="econcal-viewport">
    <div class="econcal-loading">
      <div class="econcal-spinner"></div>
    </div>
    <div class="econcal-header">
      <div class="econcal-title">📅 Calendário Econômico Investing</div>
      <div class="econcal-filter">
        <button type="button" class="econcal-filter-btn" data-toggle="dropdown"><span class="econcal-icon">▼</span>Filtros</button>
        <div class="econcal-dropdown">
          <div class="econcal-section">
            <h4>🌎 Países</h4>
            <div>
              <button type="button" data-key="countries" data-val="<?= htmlspecialchars($defaultCountries, ENT_QUOTES, 'UTF-8') ?>">Atual (22)</button>
              <button type="button" data-key="countries" data-val="35,5,17,32,37">Principais</button>
              <button type="button" data-key="countries" data-val="5,35,17,32,37,4,22,26,29,6,25,14,48,70,56,53,110,34,60,54">G20</button>
              <button type="button" data-key="countries" data-val="">Mundo</button>
            </div>
          </div>
          <div class="econcal-section">
            <h4>🗓️ Período</h4>
            <div>
              <button type="button" data-key="calType" data-val="day">Dia</button>
              <button type="button" data-key="calType" data-val="week">Semana</button>
            </div>
          </div>
          <div class="econcal-section">
            <h4>⭐️ Importância</h4>
            <div>
              <button type="button" data-key="importance" data-val="3">Alta</button>
              <button type="button" data-key="importance" data-val="2,3">Média e Alta</button>
              <button type="button" data-key="importance" data-val="1,2,3">Todas</button>
            </div>
          </div>
        </div>
      </div>
    </div>
    <iframe class="econcal-iframe" src="" loading="lazy" allowtransparency="true" sandbox="allow-scripts allow-forms allow-same-origin allow-popups allow-pointer-lock allow-presentation" referrerpolicy="no-referrer-when-downgrade" title="Calendário Econômico"></iframe>
  </div>
</div>
 
