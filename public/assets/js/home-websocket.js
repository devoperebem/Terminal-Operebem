(function() {
    // Config é resolvida dinamicamente no connect() para evitar corrida com ws-config.js (defer)
    window.__HOME_WS_OK = false;

    let ws = null;
    let reconnectTimer = null;
    let FALLBACK_TIMER = null;

    function startFallback(){
        if (FALLBACK_TIMER) return;
        // Poll snapshot endpoint to keep UI fresh when WS is blocked by anti-bot
        FALLBACK_TIMER = setInterval(fetchSnapshotAndUpdate, 5000);
        // Do one immediately
        fetchSnapshotAndUpdate();
    }

    function stopFallback(){ if (FALLBACK_TIMER) { clearInterval(FALLBACK_TIMER); FALLBACK_TIMER = null; } }

    function connect() {
        // Resolver configuração a cada tentativa
        var cfg = (window.WEBSOCKET_CONFIG || {});
        if (!cfg.url) {
            try {
                var loc = window.location;
                var scheme = (loc.protocol === 'https:') ? 'wss://' : 'ws://';
                cfg.url = scheme + loc.host + '/ws';
            } catch(e) { /* ignore */ }
        }
        var baseUrl = (cfg.url || '').replace(/\/$/, '');
        var PUBLIC_WS_URL = baseUrl + '/public';

        try {
            ws = new WebSocket(PUBLIC_WS_URL);

            ws.onopen = function() {
                window.__HOME_WS_OK = true;
                if (reconnectTimer) { clearTimeout(reconnectTimer); reconnectTimer = null; }
                stopFallback();
            };

            ws.onmessage = function(event) {
                const data = parseProxyMessage(event.data);
                if (data) updateAsset(data);
            };

            ws.onclose = function() {
                window.__HOME_WS_OK = false;
                if (!reconnectTimer) reconnectTimer = setTimeout(connect, 5000);
                startFallback();
            };

            ws.onerror = function(error) {
                try { ws.close(); } catch(e) {}
                startFallback();
            };

        } catch (e) {}
    }

    function parseProxyMessage(raw) {
        try {
            if (typeof raw === 'string' && raw.startsWith('a[')) {
                const arr = JSON.parse(raw.slice(1));
                const obj = JSON.parse(arr[0]);
                const msg = obj.message || '';
                const idx = msg.indexOf('::');
                if (idx === -1) return null;
                const payloadStr = msg.slice(idx + 2);
                const data = JSON.parse(payloadStr);
                // Derive region/pid/id_api from prefix like 'pid-eu-1234::'
                const m = msg.match(/pid(?:Ext)?-([a-z]+)-(\d+)/i);
                if (m) {
                    data.region = m[1];
                    data.pid = m[2];
                    data.id_api = `pid-${m[1]}-${m[2]}`;
                }
                return data;
            }
        } catch (e) {}
        return null;
    }

    function updateAsset(data) {
        const candidates = [];
        if (data.id_api) candidates.push(String(data.id_api));
        if (data.pid) candidates.push(String(data.pid));
        if (data.code) candidates.push(String(data.code));
        if (!candidates.length) return;

        for (const key of candidates) {
            const selKey = (window.CSS && CSS.escape) ? CSS.escape(key) : key.replace(/[!"#$%&'()*+,./:;<=>?@[\]^`{|}~]/g, "\\$&");

            // Valor
            const lastTd = document.querySelector(`.vlr_${selKey}`);
            if (lastTd) {
                const newText = (data.last ?? '').toString().trim();
                const span = lastTd.querySelector('.vlr-text');
                const oldText = (span ? span.textContent : lastTd.textContent) || '';
                if (span) span.textContent = newText; else lastTd.textContent = newText;
                lastTd.setAttribute('last', newText || '0');
                lastTd.setAttribute('data-tooltip', data.pc || '');
                const prev = toNumber(oldText);
                const next = toNumber(newText);
                if (prev !== null && next !== null && prev !== next) {
                    flashElement(span || lastTd, next > prev ? '#37ed00' : '#FF0000');
                }
            }

            // Percentual
            const percTd = document.querySelector(`.perc_${selKey}`);
            if (percTd) {
                let rawP = (data.pcp ?? '').toString().trim();
                let nval;
                if (rawP === '' || /^(-|—|UNCH)$/i.test(rawP)) { nval = 0; rawP = '0.00%'; }
                else { const tmp = rawP.includes('%') ? rawP : rawP + '%'; nval = toNumber(tmp); }
                const displayPerc = (nval !== null && Number.isFinite(nval)) ? `${nval.toFixed(2)}%` : rawP;
                percTd.textContent = displayPerc;
                percTd.classList.remove('text-danger','text-success','text-neutral','text-success-alt');
                const { cls, color } = classFromPercent(nval || 0);
                if (cls) percTd.classList.add(cls);
                if (color) percTd.style.setProperty('color', color, 'important');
            }

            // Hora
            const timeTd = document.querySelector(`.hr_${selKey}`);
            if (timeTd) {
                const info = formatTimeInfo(data.timestamp);
                timeTd.textContent = data.last ? info.time : '';
                timeTd.classList.add('tooltip-target-left');
                timeTd.setAttribute('data-tooltip', info.full || '');
            }

            // Notify status-service.js that this row was updated (same as boot.js)
            try {
                window.dispatchEvent(new CustomEvent('dashboardRowUpdated', {
                    detail: { id: key }
                }));
            } catch(_) {}
        }
    }

    // no resolveKey: we test multiple candidates

    function toNumber(text){
        const sraw = (text ?? '').toString().trim();
        if (!sraw) return null;
        const s = sraw.replace(/\s+/g, '');
        const m = s.match(/[+-]?[0-9.,]+/);
        if (!m) return null;
        let numStr = m[0];
        const lastDot = numStr.lastIndexOf('.');
        const lastComma = numStr.lastIndexOf(',');
        const lastSep = Math.max(lastDot, lastComma);
        if (lastSep > -1) {
            const intPart = numStr.slice(0, lastSep).replace(/[.,]/g, '');
            const decPart = numStr.slice(lastSep + 1).replace(/[^0-9]/g, '');
            numStr = `${intPart}.${decPart}`;
        } else {
            numStr = numStr.replace(/[^0-9+-]/g, '');
        }
        const n = parseFloat(numStr);
        return Number.isFinite(n) ? n : null;
    }

    function classFromPercent(num){
        if (num > 0) return { cls: 'text-success', color: '#37ed00' };
        if (num < 0) return { cls: 'text-danger', color: '#FF0000' };
        return { cls: 'text-neutral', color: '#000000' };
    }

    function formatTimeInfo(ts){
        if(!ts) return { time: '', full: '' };
        try{
            const d = new Date(parseInt(ts,10)*1000);
            const tz = (typeof window !== 'undefined' && window.USER_TIMEZONE) ? window.USER_TIMEZONE : 'America/Sao_Paulo';
            const time = d.toLocaleTimeString('pt-BR', { hour12:false, timeZone: tz });
            const full = d.toLocaleString('pt-BR', { hour12:false, timeZone: tz });
            return { time, full };
        }catch{ return { time:'', full:'' }; }
    }

    function formatNumber(num) {
        if (typeof num !== 'number') return '-';
        return num.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function flashElement(el, color) {
        const originalColor = el.style.color;
        el.style.color = color;
        setTimeout(() => {
            el.style.color = originalColor;
        }, 1000);
    }

    document.addEventListener('DOMContentLoaded', connect);

})();
