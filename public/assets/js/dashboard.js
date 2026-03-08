/**
 * Terminal Operebem - Dashboard JavaScript
 */

// Variáveis globais do dashboard
let dashboardData = {
    lastUpdate: new Date(),
    updateInterval: null,
    widgets: {}
};

document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();

    console.log('%c🚀 Terminal OpereBem', 'color: #3b82f6; font-size: 20px; font-weight: bold;');
    console.log('%cBem-vindo ao seu sistema de análise financeira!', 'color: #64748b; font-size: 14px;');
    console.log('%c\n⚠ Aviso Importante:', 'color: #f59e0b; font-size: 13px; font-weight: bold;');
    console.log('O uso deste sistema é exclusivo para fins educacionais e de análise.');
    console.log('É estritamente proibido qualquer tipo de webscraping, automação indevida ou utilização fora dos Termos de Uso da OpereBem.');
    console.log('\nAo utilizar este terminal, você concorda em respeitar as políticas de segurança e confidencialidade.');
});

// Inicializar dashboard
function initializeDashboard() {
    // Configurar atualização automática
    setupAutoUpdate();
    
    // Inicializar widgets
    initializeWidgets();
    
    // Configurar eventos
    setupEventListeners();
    
    // Primeira atualização
    updateDashboardData();
}

// Configurar atualização automática
function setupAutoUpdate() {
    // Limpar intervalo existente se houver
    if (dashboardData.updateInterval) {
        clearInterval(dashboardData.updateInterval);
    }
    
    // Configurar novo intervalo
    dashboardData.updateInterval = setInterval(() => {
        updateDashboardData();
    }, TO.config.updateInterval);
    
    // Pausar atualizações quando a aba não estiver ativa
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            if (dashboardData.updateInterval) {
                clearInterval(dashboardData.updateInterval);
                dashboardData.updateInterval = null;
            }
        } else {
            setupAutoUpdate();
            updateDashboardData();
        }
    });
}

// Inicializar widgets
function initializeWidgets() {
    // Widget de notícias
    const newsWidget = document.getElementById('newsWidget');
    if (newsWidget) {
        loadNewsWidget();
    }
    
    // Widget de agenda econômica
    const calendarWidget = document.getElementById('economicCalendar');
    if (calendarWidget) {
        loadEconomicCalendar();
    }
    
    // Configurar timeframes dos gráficos
    const timeframeButtons = document.querySelectorAll('input[name="timeframe"]');
    timeframeButtons.forEach(button => {
        button.addEventListener('change', function() {
            if (this.checked) {
                updateChartTimeframe(this.value);
            }
        });
    });
}

// Configurar event listeners
function setupEventListeners() {
    // Botão de atualização manual
    const refreshBtn = document.getElementById('refreshData');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            updateDashboardData().finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-sync-alt"></i>';
            });
        });
    }
}

// Atualizar dados do dashboard
async function updateDashboardData() {
    try {
        // Simular dados (substituir por chamadas reais à API)
        const data = await simulateMarketData();
        
        // Atualizar cards principais
        updateMainCards(data);
        
        // Atualizar lista de ações
        updateStocksList(data.stocks);
        
        // Atualizar timestamp
        updateLastUpdateTime();
        
    } catch (error) {
        console.error('Erro ao atualizar dados:', error);
        TO.utils.showNotification('Erro ao atualizar dados do mercado', 'warning');
    }
}

// Simular dados do mercado (substituir por API real)
async function simulateMarketData() {
    // Simular delay de rede
    await new Promise(resolve => setTimeout(resolve, 500));
    
    return {
        ibovespa: {
            value: 126547 + (Math.random() - 0.5) * 1000,
            change: (Math.random() - 0.5) * 3
        },
        usdbrl: {
            value: 5.1234 + (Math.random() - 0.5) * 0.1,
            change: (Math.random() - 0.5) * 2
        },
        bitcoin: {
            value: 43256 + (Math.random() - 0.5) * 2000,
            change: (Math.random() - 0.5) * 5
        },
        feargreed: {
            value: Math.floor(Math.random() * 100),
            label: getFearGreedLabel(Math.floor(Math.random() * 100))
        },
        stocks: generateRandomStocks()
    };
}

// Gerar dados aleatórios de ações
function generateRandomStocks() {
    const stocks = [
        { symbol: 'PETR4', name: 'Petrobras', price: 32.45 },
        { symbol: 'VALE3', name: 'Vale', price: 68.90 },
        { symbol: 'ITUB4', name: 'Itaú Unibanco', price: 25.67 },
        { symbol: 'BBDC4', name: 'Bradesco', price: 14.23 },
        { symbol: 'ABEV3', name: 'Ambev', price: 11.89 }
    ];
    
    return stocks.map(stock => ({
        ...stock,
        price: stock.price + (Math.random() - 0.5) * 2,
        change: (Math.random() - 0.5) * 4
    }));
}

// Atualizar cards principais
function updateMainCards(data) {
    // IBOVESPA
    const ibovespaElement = document.getElementById('ibovespa');
    if (ibovespaElement) {
        ibovespaElement.textContent = TO.utils.formatNumber(data.ibovespa.value, 0);
        updateChangeIndicator(ibovespaElement.parentElement, data.ibovespa.change);
    }
    
    // USD/BRL
    const usdBrlElement = document.getElementById('usdbrl');
    if (usdBrlElement) {
        usdBrlElement.textContent = TO.utils.formatNumber(data.usdbrl.value, 4);
        updateChangeIndicator(usdBrlElement.parentElement, data.usdbrl.change);
    }
    
    // Bitcoin
    const bitcoinElement = document.getElementById('bitcoin');
    if (bitcoinElement) {
        bitcoinElement.textContent = '$' + TO.utils.formatNumber(data.bitcoin.value, 0);
        updateChangeIndicator(bitcoinElement.parentElement, data.bitcoin.change);
    }
    
    // Fear & Greed
    const fearGreedElement = document.getElementById('feargreed');
    if (fearGreedElement) {
        fearGreedElement.textContent = data.feargreed.value;
        const labelElement = fearGreedElement.parentElement.querySelector('small');
        if (labelElement) {
            labelElement.textContent = data.feargreed.label;
        }
    }
}

// Atualizar indicador de mudança
function updateChangeIndicator(container, change) {
    const indicator = container.querySelector('small');
    if (!indicator) return;
    
    const isPositive = change >= 0;
    const icon = isPositive ? 'fa-arrow-up' : 'fa-arrow-down';
    const colorClass = isPositive ? 'text-success' : 'text-danger';
    const sign = isPositive ? '+' : '';
    
    indicator.className = `${colorClass}`;
    indicator.innerHTML = `<i class="fas ${icon}"></i> ${sign}${TO.utils.formatNumber(Math.abs(change), 2)}%`;
}

// Atualizar lista de ações
function updateStocksList(stocks) {
    const stocksList = document.getElementById('stocksList');
    if (!stocksList) return;
    
    stocksList.innerHTML = stocks.map(stock => `
        <tr>
            <td><strong>${stock.symbol}</strong></td>
            <td class="text-end">R$ ${TO.utils.formatNumber(stock.price, 2)}</td>
            <td class="text-end ${stock.change >= 0 ? 'text-success' : 'text-danger'}">
                ${stock.change >= 0 ? '+' : ''}${TO.utils.formatNumber(stock.change, 2)}%
            </td>
        </tr>
    `).join('');
}

// Atualizar timestamp da última atualização
function updateLastUpdateTime() {
    const lastUpdateElement = document.getElementById('lastUpdate');
    if (lastUpdateElement) {
        const now = new Date();
        const tz = (typeof window !== 'undefined' && window.USER_TIMEZONE) ? window.USER_TIMEZONE : 'America/Sao_Paulo';
        lastUpdateElement.textContent = now.toLocaleTimeString('pt-BR', { hour12: false, timeZone: tz });
        dashboardData.lastUpdate = now;
    }
}

// Obter label do Fear & Greed Index
function getFearGreedLabel(value) {
    if (value <= 25) return 'Medo Extremo';
    if (value <= 45) return 'Medo';
    if (value <= 55) return 'Neutro';
    if (value <= 75) return 'Ganância';
    return 'Ganância Extrema';
}

// Atualizar timeframe do gráfico
function updateChartTimeframe(timeframe) {
    // Implementar lógica para atualizar o gráfico TradingView
    console.log('Atualizando timeframe para:', timeframe);
    
    // Exemplo de como atualizar o widget TradingView
    if (window.tvWidget) {
        const intervals = {
            '1d': '1D',
            '1w': '1W',
            '1m': '1M'
        };
        
        window.tvWidget.chart().setResolution(intervals[timeframe] || '1D');
    }
}

// Carregar widget de notícias
async function loadNewsWidget() {
    const newsWidget = document.getElementById('newsWidget');
    if (!newsWidget) return;
    
    try {
        // Simular carregamento de notícias
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        const news = [
            {
                title: 'Mercado em alta com expectativas positivas',
                time: '2 horas atrás',
                source: 'Reuters'
            },
            {
                title: 'Banco Central mantém taxa de juros',
                time: '4 horas atrás',
                source: 'Bloomberg'
            },
            {
                title: 'Petróleo sobe com tensões geopolíticas',
                time: '6 horas atrás',
                source: 'Financial Times'
            }
        ];
        
        newsWidget.innerHTML = news.map(item => `
            <div class="border-bottom pb-2 mb-2">
                <h6 class="mb-1">${item.title}</h6>
                <small class="text-muted">${item.source} • ${item.time}</small>
            </div>
        `).join('');
        
    } catch (error) {
        newsWidget.innerHTML = '<p class="text-muted text-center">Erro ao carregar notícias</p>';
    }
}

// Carregar agenda econômica
async function loadEconomicCalendar() {
    const calendarWidget = document.getElementById('economicCalendar');
    if (!calendarWidget) return;
    
    try {
        // Simular carregamento da agenda
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        const events = [
            {
                time: '10:00',
                event: 'IPC-A (Brasil)',
                impact: 'high',
                forecast: '0.5%'
            },
            {
                time: '14:30',
                event: 'NFP (EUA)',
                impact: 'high',
                forecast: '200K'
            },
            {
                time: '16:00',
                event: 'PMI Serviços (EUA)',
                impact: 'medium',
                forecast: '52.1'
            }
        ];
        
        calendarWidget.innerHTML = events.map(event => `
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                <div>
                    <div class="fw-semibold">${event.event}</div>
                    <small class="text-muted">${event.time} • Prev: ${event.forecast}</small>
                </div>
                <span class="badge bg-${event.impact === 'high' ? 'danger' : event.impact === 'medium' ? 'warning' : 'secondary'}">
                    ${event.impact === 'high' ? 'Alto' : event.impact === 'medium' ? 'Médio' : 'Baixo'}
                </span>
            </div>
        `).join('');
        
    } catch (error) {
        calendarWidget.innerHTML = '<p class="text-muted text-center">Erro ao carregar agenda</p>';
    }
}

// Limpar intervalos ao sair da página
window.addEventListener('beforeunload', function() {
    if (dashboardData.updateInterval) {
        clearInterval(dashboardData.updateInterval);
    }
});
