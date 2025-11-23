<?php
/**
 * Market Clock Widget - Relógio 24h de mercados globais
 * Versão compacta para dashboard (sem controles e legendas)
 */
?>
<style>
/* Market Clock Widget - Compacto */
.market-clock-widget {
    width: 100%;
    max-width: 400px;
    margin: 0 auto;
    position: relative;
}

.market-clock-widget .clock__svg {
    width: 100%;
    height: auto;
    display: block;
    pointer-events: auto;
}

/* Cores adaptadas aos temas - Light */
html.light {
    --clock-open: #0d6efd;
    --clock-closed: #9ca3af;
    --clock-hand-hour: #1e40af;
    --clock-hand-minute: #3b82f6;
    --clock-hand-second: #ef4444;
    --clock-ticks: #d1d5db;
    --clock-ring: #e5e7eb;
    --clock-text: #1f2937;
    --clock-bg: #ffffff;
}

/* Cores adaptadas aos temas - Dark Blue */
html.dark-blue {
    --clock-open: #3b82f6;
    --clock-closed: #4b5563;
    --clock-hand-hour: #60a5fa;
    --clock-hand-minute: #93c5fd;
    --clock-hand-second: #f87171;
    --clock-ticks: #374151;
    --clock-ring: #1f2937;
    --clock-text: #ffffff;
    --clock-bg: #001233;
}

/* Cores adaptadas aos temas - All Black */
html.all-black {
    --clock-open: #3b82f6;
    --clock-closed: #374151;
    --clock-hand-hour: #60a5fa;
    --clock-hand-minute: #93c5fd;
    --clock-hand-second: #f87171;
    --clock-ticks: #1f2937;
    --clock-ring: #0a0a0a;
    --clock-text: #ffffff;
    --clock-bg: #000000;
}

.market-clock-widget .outer-ring {
    fill: none;
    stroke: var(--clock-ring);
    stroke-width: 34;
}

.market-clock-widget .tick {
    stroke: var(--clock-ticks);
    stroke-width: 2;
    stroke-linecap: round;
}

.market-clock-widget .hour-text {
    fill: var(--clock-text);
    font-size: 20px;
    font-weight: 700;
    text-anchor: middle;
    dominant-baseline: middle;
    font-family: 'Inter', sans-serif;
}

.market-clock-widget .market-arc {
    fill: none;
    stroke: var(--clock-closed);
    stroke-width: 20;
    stroke-linecap: round;
    transition: stroke 0.3s ease;
    opacity: 0.9;
    pointer-events: auto;
    cursor: pointer;
}

.market-clock-widget .market-arc.open {
    stroke: var(--clock-open);
    opacity: 1;
}

.market-clock-widget .market-label {
    fill: var(--clock-text);
    font-size: 13px;
    font-weight: 800;
    text-anchor: middle;
    font-family: 'Inter', sans-serif;
    letter-spacing: 0.5px;
    paint-order: stroke fill;
    stroke: var(--clock-ring);
    stroke-width: 3px;
    stroke-linecap: round;
    stroke-linejoin: round;
    pointer-events: none;
}

.market-clock-widget .hand-hour {
    stroke: var(--clock-hand-hour);
    stroke-width: 6;
    stroke-linecap: round;
}

.market-clock-widget .hand-minute {
    stroke: var(--clock-hand-minute);
    stroke-width: 4;
    stroke-linecap: round;
}

.market-clock-widget .hand-second {
    stroke: var(--clock-hand-second);
    stroke-width: 2;
    stroke-linecap: round;
}

.market-clock-widget .center-dot {
    fill: var(--clock-hand-hour);
}

/* Tooltip - Tema Light (padrão) */
.market-tooltip {
    position: fixed !important;
    background: rgba(255, 255, 255, 0.98);
    color: #1f2937;
    padding: 14px 18px;
    border-radius: 10px;
    font-size: 13px;
    pointer-events: none;
    z-index: 999999 !important;
    display: none;
    min-width: 240px;
    max-width: 320px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15), 0 0 0 1px rgba(0,0,0,0.05);
    border: 1px solid rgba(0,0,0,0.08);
}

.market-tooltip.show {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
}

.market-tooltip-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}

.market-tooltip-title {
    font-weight: 700;
    font-size: 14px;
    flex: 1;
    color: #111827;
}

.market-status-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

.market-status-indicator.open {
    background: #10b981;
    box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
    animation: pulse-green 2s ease-in-out infinite;
}

.market-status-indicator.closed {
    background: #6b7280;
}

@keyframes pulse-green {
    0%, 100% {
        opacity: 1;
        box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
    }
    50% {
        opacity: 0.6;
        box-shadow: 0 0 16px rgba(16, 185, 129, 0.8);
    }
}

.market-tooltip-location {
    color: #6b7280;
    font-size: 11px;
    margin-bottom: 10px;
}

.market-tooltip-hours {
    font-size: 11px;
    margin-bottom: 10px;
    color: #4b5563;
}

.market-tooltip-message {
    font-size: 12px;
    font-weight: 600;
    margin: 8px 0;
    padding: 6px 10px;
    border-radius: 6px;
}

.market-tooltip-message.open {
    background: rgba(16, 185, 129, 0.1);
    border-left: 3px solid #10b981;
    color: #059669;
}

.market-tooltip-message.closed {
    background: rgba(107, 114, 128, 0.1);
    border-left: 3px solid #6b7280;
    color: #4b5563;
}

.market-progress-container {
    margin-top: 10px;
    margin-bottom: 8px;
}

.market-progress-label {
    display: flex;
    justify-content: space-between;
    font-size: 10px;
    color: #6b7280;
    margin-bottom: 4px;
}

.market-progress-bar-bg {
    width: 100%;
    height: 6px;
    background: rgba(0,0,0,0.08);
    border-radius: 3px;
    overflow: hidden;
    position: relative;
}

.market-progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6 0%, #60a5fa 100%);
    border-radius: 3px;
    transition: width 0.3s ease;
    box-shadow: 0 0 8px rgba(59, 130, 246, 0.5);
}

.market-progress-bar-fill.closed {
    background: #9ca3af;
    box-shadow: none;
}

.market-tooltip-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    margin-top: 6px;
}

.market-tooltip-status.open {
    background: rgba(16, 185, 129, 0.15);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.market-tooltip-status.closed {
    background: rgba(107, 114, 128, 0.15);
    color: #4b5563;
    border: 1px solid rgba(107, 114, 128, 0.3);
}

/* Tooltip - Temas Escuros */
html.dark-blue .market-tooltip,
html.all-black .market-tooltip {
    background: rgba(0, 0, 0, 0.95);
    color: #f3f4f6;
    box-shadow: 0 8px 24px rgba(0,0,0,0.6), 0 0 0 1px rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.1);
}

html.dark-blue .market-tooltip-title,
html.all-black .market-tooltip-title {
    color: #f9fafb;
}

html.dark-blue .market-tooltip-location,
html.all-black .market-tooltip-location {
    color: #9ca3af;
}

html.dark-blue .market-tooltip-hours,
html.all-black .market-tooltip-hours {
    color: #d1d5db;
}

html.dark-blue .market-progress-label,
html.all-black .market-progress-label {
    color: #9ca3af;
}

html.dark-blue .market-progress-bar-bg,
html.all-black .market-progress-bar-bg {
    background: rgba(255,255,255,0.1);
}

html.dark-blue .market-tooltip-status.open,
html.all-black .market-tooltip-status.open {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

html.dark-blue .market-tooltip-status.closed,
html.all-black .market-tooltip-status.closed {
    background: rgba(107, 114, 128, 0.2);
    color: #9ca3af;
    border: 1px solid rgba(107, 114, 128, 0.3);
}

html.dark-blue .market-tooltip-message.open,
html.all-black .market-tooltip-message.open {
    background: rgba(16, 185, 129, 0.15);
    border-left: 3px solid #10b981;
    color: #10b981;
}

html.dark-blue .market-tooltip-message.closed,
html.all-black .market-tooltip-message.closed {
    background: rgba(107, 114, 128, 0.15);
    border-left: 3px solid #6b7280;
    color: #9ca3af;
}
</style>

<div class="market-clock-widget">
    <svg id="market-clock-svg" viewBox="0 0 600 600" class="clock__svg" aria-label="Relógio 24h de mercados" role="img">
        <defs>
            <filter id="clock-shadow" x="-50%" y="-50%" width="200%" height="200%">
                <feDropShadow dx="0" dy="2" stdDeviation="4" flood-color="#000" flood-opacity="0.1" />
            </filter>
        </defs>
        <g id="clock-dial"></g>
        <g id="clock-markets"></g>
        <g id="clock-hands"></g>
        <g id="clock-center"></g>
    </svg>
</div>
<!-- Tooltip fora do widget para evitar problemas de overflow/z-index -->
<div class="market-tooltip" id="market-tooltip"></div>

<script>
(function(){
    // BASE_TZ é usado como referência de cálculo (BRT = UTC-3)
    const BASE_TZ = -3;
    const CX = 300, CY = 300;
    const R_OUT = 280;
    const R_TICK_OUT = 270;
    const R_TICK_IN_MAJOR = 250;
    const R_TICK_IN_MINOR = 260;
    const R_HOUR_TEXT = 225;
    const R_MARKET_BASE = 195;
    const R_MARKET_STEP = 24;
    const R_LABEL_OFFSET_TOP = -4;
    const R_LABEL_OFFSET_BOTTOM = 4;
    const MIN_GAP_MIN = 15;
    
    // Será preenchido via API (tabela clock). Mantemos fallback estático.
    let MARKETS = [
        { name: 'B3', fullName: 'B3 - Brasil Bolsa Balcão', location: 'São Paulo, Brasil', brt: [['10:00','17:55']] },
        { name: 'NYSE', fullName: 'New York Stock Exchange', location: 'Nova York, EUA', brt: [['10:30','17:00']] },
        { name: 'NASDAQ', fullName: 'NASDAQ', location: 'Nova York, EUA', brt: [['10:30','17:00']] },
        { name: 'TSX', fullName: 'Toronto Stock Exchange', location: 'Toronto, Canadá', brt: [['10:30','17:00']] },
        { name: 'LSE', fullName: 'London Stock Exchange', location: 'Londres, Reino Unido', brt: [['04:00','12:30']] },
        { name: 'FWB', fullName: 'Frankfurt Stock Exchange', location: 'Frankfurt, Alemanha', brt: [['04:00','12:30']] },
        { name: 'SIX', fullName: 'SIX Swiss Exchange', location: 'Zurique, Suíça', brt: [['04:00','12:30']] },
        { name: 'JPX', fullName: 'Japan Exchange Group', location: 'Tóquio, Japão', brt: [['21:00','03:00']] },
        { name: 'ASX', fullName: 'Australian Securities Exchange', location: 'Sydney, Austrália', brt: [['20:00','02:00']] },
        { name: 'HKEX', fullName: 'Hong Kong Stock Exchange', location: 'Hong Kong', brt: [['22:30','01:00'], ['02:00','05:00']] },
        { name: 'SSE', fullName: 'Shanghai Stock Exchange', location: 'Xangai, China', brt: [['22:30','04:00']] },
        { name: 'SGX', fullName: 'Singapore Exchange', location: 'Singapura', brt: [['22:00','06:00']] },
        { name: 'NZX', fullName: 'New Zealand Exchange', location: 'Wellington, Nova Zelândia', brt: [['19:00','01:00']] }
    ];
    
    const dial = document.getElementById('clock-dial');
    const markets = document.getElementById('clock-markets');
    const hands = document.getElementById('clock-hands');
    const center = document.getElementById('clock-center');
    const tooltip = document.getElementById('market-tooltip');

    console.log('[MarketClock] Widget initialized. Tooltip element:', tooltip ? 'FOUND' : 'NOT FOUND');

    // Mover tooltip para o body para evitar problemas de overflow/z-index
    if (tooltip && tooltip.parentElement !== document.body) {
        console.log('[MarketClock] Moving tooltip to body');
        document.body.appendChild(tooltip);
    }
    
    // Função auxiliar para calcular próximo dia útil
    function getNextTradingDay(tradingDays, currentDay) {
        if (!tradingDays || tradingDays.length === 0) return 1; // Se não tem dados, assume próximo dia

        const days = tradingDays.split('').map(Number); // Ex: "23456" => [2,3,4,5,6]
        let daysToAdd = 1;
        let nextDay = currentDay + 1;
        if (nextDay > 7) nextDay = 1;

        // Buscar próximo dia útil (máximo 7 dias à frente)
        for (let i = 0; i < 7; i++) {
            if (days.includes(nextDay)) {
                return daysToAdd;
            }
            daysToAdd++;
            nextDay++;
            if (nextDay > 7) nextDay = 1;
        }

        return daysToAdd; // Fallback
    }

    function showTooltip(market, isOpen, event) {
        console.log('[MarketClock] showTooltip called:', market.name, isOpen);

        if (!tooltip) {
            console.error('[MarketClock] Tooltip element not found!');
            return;
        }

        const hours = market.brt.map(([s, e]) => `${s} - ${e}`).join(' | ');
        const statusClass = isOpen ? 'open' : 'closed';

        const now = new Date();
        const nowMin = now.getHours() * 60 + now.getMinutes();
        const pad = n => String(n).padStart(2, '0');
        const currentTimeLabel = `${pad(now.getHours())}:${pad(now.getMinutes())}`;
        // JS getDay: 0=dom, 1=seg, ..., 6=sáb → DB trading_days: 1=dom, 2=seg, ..., 7=sáb
        const currentDay = now.getDay() + 1; // Converter JS 0-6 para DB 1-7

        // Buscar dados do banco
        const code = NAME_TO_CODE[market.name] || null;
        const dbData = code ? (DB_DATA_BY_CODE[code] || null) : null;
        const tradingDays = dbData ? dbData.trading_days : null;
        const lastOpenTime = dbData ? dbData.last_open_time : null;
        const lastCloseTime = dbData ? dbData.last_close_time : null;

        // Calcular informações de tempo
        let statusMessage = '';
        let lastStateInfo = '';
        let progressPercent = 0;
        let progressStart = '--:--';
        let progressEnd = '--:--';

        if (isOpen && market.brt.length > 0) {
            // Mercado ABERTO - encontrar segmento ativo e calcular tempo até fechar
            for (const [start, end] of market.brt) {
                const startMin = timeToMin(start);
                const endMin = timeToMin(end);

                if (nowMin >= startMin && nowMin < endMin) {
                    const totalMin = endMin - startMin;
                    const elapsedMin = nowMin - startMin;
                    const remainingMin = endMin - nowMin;
                    progressPercent = (elapsedMin / totalMin) * 100;

                    progressStart = start;
                    progressEnd = end;

                    // Calcular horas e minutos restantes
                    const hoursLeft = Math.floor(remainingMin / 60);
                    const minutesLeft = remainingMin % 60;

                    if (hoursLeft > 0) {
                        statusMessage = `Fecha em ${hoursLeft} hora${hoursLeft > 1 ? 's' : ''} e ${minutesLeft} minuto${minutesLeft !== 1 ? 's' : ''}`;
                    } else {
                        statusMessage = `Fecha em ${minutesLeft} minuto${minutesLeft !== 1 ? 's' : ''}`;
                    }

                    // Informação de quando abriu - sempre mostrar
                    const openTimeDisplay = (lastOpenTime && hhmmFrom(lastOpenTime)) || start;
                    lastStateInfo = `Abriu às ${openTimeDisplay} • Fecha às ${end}`;
                    break;
                }
            }
        } else if (!isOpen && market.brt.length > 0) {
            // Mercado FECHADO - encontrar próximo horário de abertura
            let nextOpenMin = null;
            let nextOpenStr = null;
            let nextCloseStr = null;
            let daysToAdd = 0;

            // Procurar próximo segmento hoje
            for (const [start, end] of market.brt) {
                const startMin = timeToMin(start);
                const endMin = timeToMin(end);

                if (nowMin < startMin) {
                    // Verificar se hoje é dia útil
                    if (tradingDays) {
                        const days = tradingDays.split('').map(Number);
                        if (days.includes(currentDay)) {
                            nextOpenMin = startMin;
                            nextOpenStr = start;
                            nextCloseStr = end;
                            break;
                        }
                    } else {
                        // Sem dados de trading_days, assume que hoje é válido
                        nextOpenMin = startMin;
                        nextOpenStr = start;
                        nextCloseStr = end;
                        break;
                    }
                }
            }

            // Se não encontrou hoje, buscar próximo dia útil
            if (nextOpenMin === null && market.brt.length > 0) {
                if (tradingDays) {
                    daysToAdd = getNextTradingDay(tradingDays, currentDay);
                } else {
                    daysToAdd = 1; // Assume próximo dia se não tem dados
                }

                const [start, end] = market.brt[0];
                nextOpenMin = timeToMin(start) + (daysToAdd * 1440);
                nextOpenStr = start;
                nextCloseStr = end;
            }

            if (nextOpenMin !== null) {
                let minutesUntilOpen = nextOpenMin - nowMin;
                if (minutesUntilOpen < 0) minutesUntilOpen += 1440;

                const totalHours = Math.floor(minutesUntilOpen / 60);
                const minutesUntil = minutesUntilOpen % 60;
                const daysUntil = Math.floor(totalHours / 24);
                const hoursUntil = totalHours % 24;

                // Mensagem mais inteligente
                if (daysUntil > 0) {
                    if (daysUntil === 1) {
                        statusMessage = `Abre amanhã às ${nextOpenStr}`;
                        if (hoursUntil > 0 || minutesUntil > 0) {
                            statusMessage += ` (${hoursUntil}h${minutesUntil > 0 ? minutesUntil + 'min' : ''})`;
                        }
                    } else {
                        statusMessage = `Abre em ${daysUntil} dia${daysUntil > 1 ? 's' : ''}`;
                        if (hoursUntil > 0) {
                            statusMessage += ` e ${hoursUntil}h`;
                        }
                    }
                } else if (hoursUntil > 0) {
                    statusMessage = `Abre em ${hoursUntil} hora${hoursUntil > 1 ? 's' : ''}`;
                    if (minutesUntil > 0) {
                        statusMessage += ` e ${minutesUntil} minuto${minutesUntil !== 1 ? 's' : ''}`;
                    }
                } else {
                    statusMessage = `Abre em ${minutesUntil} minuto${minutesUntil !== 1 ? 's' : ''}`;
                }

                progressStart = nextOpenStr;
                progressEnd = nextCloseStr;

                // Informação de quando fechou e quando abrirá - sempre mostrar
                const closeTimeDisplay = (lastCloseTime && hhmmFrom(lastCloseTime)) || '--:--';
                lastStateInfo = `Fechou às ${closeTimeDisplay} • Abre às ${nextOpenStr}`;
            } else {
                statusMessage = 'Horário de abertura não disponível';
            }
        }

        // HTML do progresso (sempre mostrar, mesmo fechado)
        const progressHtml = `
            <div class="market-progress-container">
                <div class="market-progress-label">
                    <span>${progressStart}</span>
                    <span style="font-weight: 600; color: ${isOpen ? '#60a5fa' : '#9ca3af'};">${currentTimeLabel}</span>
                    <span>${progressEnd}</span>
                </div>
                <div class="market-progress-bar-bg">
                    <div class="market-progress-bar-fill ${isOpen ? '' : 'closed'}" style="width: ${progressPercent.toFixed(1)}%"></div>
                </div>
            </div>
        `;

        // Informação de último estado
        const lastStateHtml = lastStateInfo ? `<div class="market-tooltip-last-state" style="font-size: 11px; color: #6b7280; margin-bottom: 6px; font-weight: 500;">${lastStateInfo}</div>` : '';

        // Status principal
        const statusBadge = isOpen
            ? `<div class="market-tooltip-status ${statusClass}">✓ Mercado Aberto</div>`
            : `<div class="market-tooltip-status ${statusClass}">— Mercado Fechado</div>`;

        tooltip.innerHTML = `
            <div class="market-tooltip-header">
                <div class="market-tooltip-title">${market.fullName}</div>
                <div class="market-status-indicator ${statusClass}"></div>
            </div>
            <div class="market-tooltip-location">📍 ${market.location}</div>
            <div class="market-tooltip-message ${statusClass}">
                ${statusMessage}
            </div>
            ${lastStateHtml}
            ${progressHtml}
            <div class="market-tooltip-hours">⏰ Horário de negociação: ${hours} (BRT)</div>
            ${statusBadge}
        `;

        // Calcular posição em relação à viewport (fixed)
        const x = event.clientX + 15;
        const y = event.clientY - 10;

        // Ajustar se sair da tela
        tooltip.style.left = x + 'px';
        tooltip.style.top = y + 'px';
        tooltip.style.display = 'block'; // Mostrar temporariamente para calcular tamanho

        const tooltipRect = tooltip.getBoundingClientRect();
        const windowWidth = window.innerWidth;
        const windowHeight = window.innerHeight;

        let finalX = x;
        let finalY = y;

        // Ajustar horizontal
        if (tooltipRect.right > windowWidth - 10) {
            finalX = event.clientX - tooltipRect.width - 15;
        }
        if (finalX < 10) finalX = 10;

        // Ajustar vertical
        if (tooltipRect.bottom > windowHeight - 10) {
            finalY = event.clientY - tooltipRect.height - 10;
        }
        if (finalY < 10) finalY = 10;

        tooltip.style.left = finalX + 'px';
        tooltip.style.top = finalY + 'px';
        tooltip.classList.add('show');

        // Verificar estado computado
        const computedStyle = window.getComputedStyle(tooltip);
        console.log('[MarketClock] Tooltip positioned at:', finalX, finalY);
        console.log('[MarketClock] Tooltip inline style display:', tooltip.style.display);
        console.log('[MarketClock] Tooltip computed display:', computedStyle.display);
        console.log('[MarketClock] Tooltip computed visibility:', computedStyle.visibility);
        console.log('[MarketClock] Tooltip computed z-index:', computedStyle.zIndex);
        console.log('[MarketClock] Tooltip classList:', tooltip.classList.toString());
    }

    function hideTooltip() {
        tooltip.classList.remove('show');
        tooltip.style.display = ''; // Remover inline style para permitir CSS controlar
    }
    
    function polar(r, deg) {
        const rad = (deg - 90) * Math.PI / 180;
        return [CX + r * Math.cos(rad), CY + r * Math.sin(rad)];
    }
    
    function arcPath(r, startDeg, endDeg) {
        const [x1, y1] = polar(r, startDeg);
        const [x2, y2] = polar(r, endDeg);
        const large = (endDeg - startDeg > 180) ? 1 : 0;
        return `M ${x1} ${y1} A ${r} ${r} 0 ${large} 1 ${x2} ${y2}`;
    }
    
    function arcPathSmall(r, startDeg, endDeg) {
        const [x1, y1] = polar(r, endDeg);
        const [x2, y2] = polar(r, startDeg);
        const large = (endDeg - startDeg > 180) ? 1 : 0;
        return `M ${x1} ${y1} A ${r} ${r} 0 ${large} 0 ${x2} ${y2}`;
    }
    
    function minutesToAngle(min) {
        return (min / 1440) * 360;
    }
    
    function timeToMin(hhmm) {
        const [h, m] = hhmm.split(':').map(Number);
        return h * 60 + m;
    }
    
    function segmentsFor(mk, targetOffset) {
        const diff = targetOffset - BASE_TZ;
        const shift = diff * 60;
        const segs = [];
        mk.brt.forEach(([s, e]) => {
            let start = timeToMin(s) + shift;
            let end = timeToMin(e) + shift;
            while (start < 0) { start += 1440; end += 1440; }
            while (start >= 1440) { start -= 1440; end -= 1440; }
            if (end <= start) end += 1440;
            if (end > 1440) {
                segs.push([start, 1440]);
                segs.push([0, end - 1440]);
            } else {
                segs.push([start, end]);
            }
        });
        return segs;
    }
    
    function drawDial() {
        dial.innerHTML = '';
        const ring = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        ring.setAttribute('class', 'outer-ring');
        ring.setAttribute('cx', CX);
        ring.setAttribute('cy', CY);
        ring.setAttribute('r', R_OUT);
        dial.appendChild(ring);
        
        for (let h = 0; h < 24; h++) {
            const deg = (h / 24) * 360;
            const isMajor = (h % 6 === 0);
            const rIn = isMajor ? R_TICK_IN_MAJOR : R_TICK_IN_MINOR;
            const [x1, y1] = polar(R_TICK_OUT, deg);
            const [x2, y2] = polar(rIn, deg);
            const tick = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            tick.setAttribute('class', 'tick');
            tick.setAttribute('x1', x1);
            tick.setAttribute('y1', y1);
            tick.setAttribute('x2', x2);
            tick.setAttribute('y2', y2);
            dial.appendChild(tick);
            
            const [tx, ty] = polar(R_HOUR_TEXT, deg);
            const txt = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            txt.setAttribute('class', 'hour-text');
            txt.setAttribute('x', tx);
            txt.setAttribute('y', ty);
            txt.textContent = String(h).padStart(2, '0');
            dial.appendChild(txt);
        }
    }
    
    function renderMarkets(date, offset) {
        markets.innerHTML = '';
        const nowMin = date.getHours() * 60 + date.getMinutes();
        const allSegs = [];
        MARKETS.forEach(mk => {
            const segs = segmentsFor(mk, offset);
            allSegs.push({ market: mk, segments: segs });
        });
        
        const lanes = [];
        allSegs.forEach(({ market, segments }) => {
            let assigned = -1;
            for (let l = 0; l < lanes.length; l++) {
                let fits = true;
                for (const [s, e] of segments) {
                    for (const [os, oe] of lanes[l]) {
                        if (!(e + MIN_GAP_MIN <= os || s >= oe + MIN_GAP_MIN)) {
                            fits = false;
                            break;
                        }
                    }
                    if (!fits) break;
                }
                if (fits) {
                    assigned = l;
                    break;
                }
            }
            if (assigned === -1) {
                assigned = lanes.length;
                lanes.push([]);
            }
            lanes[assigned].push(...segments);
            
            const r = R_MARKET_BASE - assigned * R_MARKET_STEP;
            // Aberto por horário (fallback original)
            const openByTime = segments.some(([s, e]) => (nowMin >= s && nowMin < e));
            // Override por status do banco
            const code = NAME_TO_CODE[market.name] || null;
            const statusDb = code ? (DB_STATUS_BY_CODE[code] || null) : null;
            const isOpenFinal = statusDb ? (String(statusDb).toLowerCase() === 'open') : openByTime;
            segments.forEach(([s, e]) => {
                const sDeg = minutesToAngle(s);
                const eDeg = minutesToAngle(e);
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('class', 'market-arc' + (isOpenFinal ? ' open' : ''));
                path.setAttribute('d', arcPath(r, sDeg, eDeg));
                path.style.cursor = 'pointer';
                
                // Eventos de mouse para tooltip
                path.addEventListener('mouseenter', (e) => {
                    console.log('[MarketClock] mouseenter on path:', market.name);
                    showTooltip(market, isOpenFinal, e);
                });
                path.addEventListener('mousemove', (e) => {
                    if (!tooltip.classList.contains('show')) return;

                    const x = e.clientX + 15;
                    const y = e.clientY - 10;

                    const tooltipRect = tooltip.getBoundingClientRect();
                    const windowWidth = window.innerWidth;
                    const windowHeight = window.innerHeight;

                    let finalX = x;
                    let finalY = y;

                    // Ajustar horizontal
                    if (x + tooltipRect.width > windowWidth - 10) {
                        finalX = e.clientX - tooltipRect.width - 15;
                    }
                    if (finalX < 10) finalX = 10;

                    // Ajustar vertical
                    if (y + tooltipRect.height > windowHeight - 10) {
                        finalY = e.clientY - tooltipRect.height - 10;
                    }
                    if (finalY < 10) finalY = 10;

                    tooltip.style.left = finalX + 'px';
                    tooltip.style.top = finalY + 'px';
                });
                path.addEventListener('mouseleave', hideTooltip);
                
                markets.appendChild(path);
            });
            
            const largest = segments.reduce((a, b) => ((b[1] - b[0]) > (a[1] - a[0]) ? b : a));
            const [ls, le] = largest;
            const midMin = (ls + le) / 2;
            const midAng = minutesToAngle(midMin);
            const isBottom = (midAng > 90 && midAng < 270);
            const labelR = r + (isBottom ? R_LABEL_OFFSET_BOTTOM : R_LABEL_OFFSET_TOP);
            
            const pathId = `label-path-${market.name.replace(/\s+/g, '-')}`;
            const pathEl = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            pathEl.setAttribute('id', pathId);
            pathEl.setAttribute('d', isBottom ? arcPathSmall(labelR, ls / 1440 * 360, le / 1440 * 360) : arcPath(labelR, ls / 1440 * 360, le / 1440 * 360));
            pathEl.style.display = 'none';
            markets.appendChild(pathEl);
            
            const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            text.setAttribute('class', 'market-label');
            text.setAttribute('text-anchor', 'middle');
            const textPath = document.createElementNS('http://www.w3.org/2000/svg', 'textPath');
            textPath.setAttributeNS('http://www.w3.org/1999/xlink', 'href', '#' + pathId);
            textPath.setAttribute('startOffset', '50%');
            textPath.setAttribute('text-anchor', 'middle');
            textPath.textContent = market.name;
            text.appendChild(textPath);
            markets.appendChild(text);
        });
    }
    
    function renderHands(date) {
        hands.innerHTML = '';

        // Obter hora no timezone do usuário
        const userTz = window.USER_TIMEZONE || 'America/Sao_Paulo';
        const formatter = new Intl.DateTimeFormat('en-US', {
            timeZone: userTz,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        });

        const parts = formatter.formatToParts(date);
        const h = parseInt(parts.find(p => p.type === 'hour').value);
        const m = parseInt(parts.find(p => p.type === 'minute').value);
        const s = parseInt(parts.find(p => p.type === 'second').value);

        const hAng = ((h % 24) / 24) * 360 + (m / 60) * 15;
        const mAng = (m / 60) * 360 + (s / 60) * 6;
        const sAng = (s / 60) * 360;

        const drawHand = (ang, len, cls) => {
            const [x, y] = polar(len, ang);
            const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            line.setAttribute('class', cls);
            line.setAttribute('x1', CX);
            line.setAttribute('y1', CY);
            line.setAttribute('x2', x);
            line.setAttribute('y2', y);
            hands.appendChild(line);
        };

        drawHand(hAng, 120, 'hand-hour');
        drawHand(mAng, 160, 'hand-minute');
        drawHand(sAng, 180, 'hand-second');
    }
    
    // Calcular offset UTC do timezone do usuário
    function getUserTimezoneOffset() {
        try {
            // Ler timezone dinamicamente (permite atualização em tempo real)
            const userTz = window.USER_TIMEZONE || 'America/Sao_Paulo';
            const now = new Date();

            // Obter hora em UTC (formato ISO sem timezone)
            const utcHours = now.getUTCHours();
            const utcMinutes = now.getUTCMinutes();

            // Obter hora no timezone do usuário
            const formatter = new Intl.DateTimeFormat('en-US', {
                timeZone: userTz,
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            });

            const parts = formatter.formatToParts(now);
            const tzHours = parseInt(parts.find(p => p.type === 'hour').value);
            const tzMinutes = parseInt(parts.find(p => p.type === 'minute').value);

            // Calcular diferença em horas (com fração para minutos)
            const utcTotalMinutes = utcHours * 60 + utcMinutes;
            const tzTotalMinutes = tzHours * 60 + tzMinutes;
            let diffMinutes = tzTotalMinutes - utcTotalMinutes;

            // Ajustar para mudança de dia (crossing midnight)
            if (diffMinutes > 720) diffMinutes -= 1440;  // Se diferença > 12h, subtrair 24h
            if (diffMinutes < -720) diffMinutes += 1440; // Se diferença < -12h, adicionar 24h

            const offsetHours = diffMinutes / 60;

            return offsetHours;
        } catch (e) {
            console.warn('[MarketClock] Erro ao calcular timezone do usuário, usando timezone do navegador:', e);
            return -(new Date().getTimezoneOffset() / 60);
        }
    }

    function renderAll() {
        const now = new Date();
        const offset = getUserTimezoneOffset();
        drawDial();
        renderMarkets(now, offset);
        renderHands(now);

        const dot = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        dot.setAttribute('class', 'center-dot');
        dot.setAttribute('cx', CX);
        dot.setAttribute('cy', CY);
        dot.setAttribute('r', 8);
        center.innerHTML = '';
        center.appendChild(dot);
    }
    
    // --- Integração com API (tabela clock): apenas confirmar status (sem alterar lista) ---
    const API_URL = '/api/market-clock/all';
    const NAME_TO_CODE = {
        'B3': 'XBSP',
        'NYSE': 'XNYS',
        'NASDAQ': 'XNAS',
        'TSX': 'XTSE',
        'LSE': 'XLON',
        'FWB': 'XETR',
        'SIX': 'XSWX',
        'JPX': 'XTKS',
        'ASX': 'XASX',
        'HKEX': 'XHKG',
        'SSE': 'XSHG',
        'SGX': 'XSES',
        'NZX': 'XNZX'
    };
    let DB_STATUS_BY_CODE = {};
    let DB_DATA_BY_CODE = {}; // Armazenar dados completos da API
    
    function pad2(n){ return String(n).padStart(2,'0'); }
    function hhmmFrom(hms){ if(!hms) return null; const [h,m] = hms.split(':'); return `${pad2(+h)}:${pad2(+m)}`; }
    function parseHhmmToMin(hhmm){ const [h,m] = hhmm.split(':').map(Number); return h*60+m; }
    function minToHhmm(min){ let m=((min%1440)+1440)%1440; const h=Math.floor(m/60), mm=m%60; return `${pad2(h)}:${pad2(mm)}`; }
    
    // Converte horário local da bolsa para BRT, usando offset UTC da bolsa e BASE_TZ (-3)
    function toBrtHhmm(localHhmm, exchangeUtcOffset){
        if(!localHhmm || exchangeUtcOffset===undefined || exchangeUtcOffset===null) return null;
        const mins = parseHhmmToMin(localHhmm);
        const diffHours = BASE_TZ - Number(exchangeUtcOffset); // ex: NY (-4 no verão) -> -3 - (-4) = +1h
        const brtMins = mins + (diffHours*60);
        return minToHhmm(brtMins);
    }
    
    async function fetchStatuses(){
        try {
            const res = await fetch(API_URL, { credentials: 'same-origin' });
            if(!res.ok) throw new Error('HTTP '+res.status);
            const j = await res.json();
            if(j && j.success && Array.isArray(j.data)){
                const statusMap = {};
                const dataMap = {};
                j.data.forEach(ex => {
                    const code = String(ex.exchange_code || '').trim();
                    const st = (ex.calculated_status || ex.current_status || '').toString().toLowerCase();
                    if (code) {
                        statusMap[code] = st;
                        dataMap[code] = ex; // Armazenar objeto completo
                    }
                });
                DB_STATUS_BY_CODE = statusMap;
                DB_DATA_BY_CODE = dataMap;
                renderAll();
            }
        } catch(e){
            try{ console.warn('[MarketClock] status fetch failed, using time-based fallback.', e); }catch(_){ }
        }
    }
    
    function scheduleFiveMinuteSync(){
        const now = new Date();
        const msToNext = ((5 - (now.getMinutes() % 5)) % 5) * 60000 - now.getSeconds()*1000 - now.getMilliseconds();
        const delay = msToNext <= 0 ? 0 : msToNext;
        setTimeout(() => {
            fetchStatuses();
            setInterval(fetchStatuses, 5*60*1000);
        }, delay);
    }
    
    // Boot
    renderAll();
    fetchStatuses();
    scheduleFiveMinuteSync();
    setInterval(renderAll, 1000);
    new MutationObserver(() => renderAll()).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
})();
</script>
