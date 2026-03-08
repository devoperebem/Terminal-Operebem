# 📊 Mapeamento de Endpoints com Polling

**Data:** 2026-01-01
**Objetivo:** Documentar todos os endpoints que usam polling/tempo real para implementar restrições por tier

---

## 🔄 Endpoints de Dados (APIs Internas)

### 1. Cotações Gold Dashboard
| Campo | Valor |
|-------|-------|
| **Endpoint** | `POST /api/quotes/gold-boot` |
| **Controller** | `QuotesController::goldBoot` |
| **Middleware** | `AuthMiddleware`, `SameOriginAjaxMiddleware` |
| **JS que usa** | `gold-dashboard.js` (linha 198) |
| **Intervalo atual** | 60 segundos |
| **Feature** | Dashboard Ouro |
| **Tier** | FREE: ❌ Bloqueado, PLUS: WS, PRO: WS |
| **Ação necessária** | Bloquear endpoint para FREE |

---

### 2. Cotações Dashboard Principal
| Campo | Valor |
|-------|-------|
| **Endpoint** | `POST /actions/boot.php` |
| **Controller** | `QuotesController::boot` |
| **Middleware** | `AuthMiddleware` |
| **JS que usa** | `boot.js` |
| **Intervalo atual** | 5 minutos / 5 segundos (fallback) |
| **Feature** | Dashboard Principal |
| **Tier** | Todos WS |
| **Ação necessária** | Nenhuma (todos têm acesso) |

---

### 3. Cotações Públicas (Home)
| Campo | Valor |
|-------|-------|
| **Endpoint** | `POST /actions/quotes-public` |
| **Controller** | `QuotesController::listarPublic` |
| **Middleware** | `SameOriginAjaxMiddleware` |
| **JS que usa** | `home-preview.js`, `home-websocket.js` |
| **Intervalo atual** | 5 segundos |
| **Feature** | Home Page (preview) |
| **Tier** | Público (não logado) |
| **Ação necessária** | Nenhuma |

---

### 4. Market Clock
| Campo | Valor |
|-------|-------|
| **Endpoint** | `GET /api/market-clock/all` |
| **Controller** | `MarketClockController` |
| **Middleware** | Sem auth (público) |
| **JS que usa** | `status-service.js` (linha 384) |
| **Intervalo atual** | 5 minutos (alinhado) |
| **Feature** | Relógio de Mercados |
| **Tier** | Todos Real Time |
| **Ação necessária** | Nenhuma |

---

### 5. Fear & Greed Index
| Campo | Valor |
|-------|-------|
| **Endpoint** | `GET /api/fg/current` |
| **Controller** | `FearGreedController::current` |
| **Middleware** | `AuthMiddleware`, `SameOriginAjaxMiddleware` |
| **JS que usa** | `boot.js`, `dashboard.js` |
| **Feature** | Indicadores Sentimento |
| **Tier** | FREE: 5min, PLUS: 1min, PRO: WS |
| **Ação necessária** | Implementar intervalo variável |

---

### 6. Fear & Greed Summary/Indicators
| Campo | Valor |
|-------|-------|
| **Endpoint** | `GET /api/fg/summary`, `/api/fg/indicators` |
| **Controller** | `FearGreedController` |
| **Middleware** | `AuthMiddleware`, `SameOriginAjaxMiddleware` |
| **Feature** | Indicadores Sentimento |
| **Tier** | FREE: 5min, PLUS: 1min, PRO: WS |
| **Ação necessária** | Implementar intervalo variável |

---

### 7. US Market Barometer
| Campo | Valor |
|-------|-------|
| **Endpoint** | `GET /api/usmb/data` |
| **Controller** | `UsMarketBarometerController::data` |
| **Middleware** | `AuthMiddleware`, `SameOriginAjaxMiddleware` |
| **JS que usa** | `barometer.js` (linha 2) |
| **Feature** | Indicadores Operebem |
| **Tier** | FREE: 5min, PLUS: 1min, PRO: WS |
| **Ação necessária** | Implementar intervalo variável |

---

### 8. Federal Reserve Probabilities
| Campo | Valor |
|-------|-------|
| **Endpoint** | `GET /api/fed/probabilities` |
| **Controller** | `FedController::probabilities` |
| **Middleware** | `AuthMiddleware`, `SameOriginAjaxMiddleware` |
| **JS que usa** | `fed.js` (linha 46) |
| **Feature** | Federal Reserve |
| **Tier** | Todos Real Time |
| **Ação necessária** | Nenhuma |

---

### 9. ORM (8 Assets)
| Campo | Valor |
|-------|-------|
| **Endpoint** | `GET /api/orm` |
| **Controller** | `OrmController::data` |
| **Middleware** | `AuthMiddleware`, `SameOriginAjaxMiddleware` |
| **Feature** | Dashboard Principal |
| **Tier** | Todos WS |
| **Ação necessária** | Nenhuma |

---

### 10. OB Indices
| Campo | Valor |
|-------|-------|
| **Endpoint** | `GET /api/indices`, `/api/indices/{name}` |
| **Controller** | `OBIndicesController` |
| **Middleware** | `AuthMiddleware` |
| **Feature** | Indicadores Operebem |
| **Tier** | FREE: 5min, PLUS: 1min, PRO: WS |
| **Ação necessária** | Implementar intervalo variável |

---

### 11. News API
| Campo | Valor |
|-------|-------|
| **Endpoint** | `GET /api/news`, `/api/news/noticias` |
| **Controller** | `NewsController::noticias` |
| **Middleware** | `AuthMiddleware` |
| **Feature** | Notícias |
| **Tier** | Todos Real Time |
| **Ação necessária** | Nenhuma |

---

### 12. Gamification Stats
| Campo | Valor |
|-------|-------|
| **Endpoint** | `GET /api/profile/gamification` |
| **Controller** | `ProfileController::getGamificationStats` |
| **Middleware** | `AuthMiddleware` |
| **Feature** | Perfil/XP |
| **Tier** | Todos |
| **Ação necessária** | Nenhuma |

---

## 📋 Resumo de Ações por Endpoint

### Bloquear para FREE:
| Endpoint | Feature |
|----------|---------|
| `/api/quotes/gold-boot` | Dashboard Ouro |
| (futuro) `/api/quotes/nasdaq` | Dashboard NASDAQ |

### Implementar Intervalo Variável:
| Endpoint | FREE | PLUS | PRO |
|----------|------|------|-----|
| `/api/fg/*` | 5min | 1min | 5s |
| `/api/usmb/data` | 5min | 1min | 5s |
| `/api/indices/*` | 5min | 1min | 5s |

### Sem Alteração:
| Endpoint | Motivo |
|----------|--------|
| `/actions/boot.php` | Todos WS |
| `/actions/quotes-public` | Público |
| `/api/market-clock/all` | Público |
| `/api/fed/probabilities` | Todos Real Time |
| `/api/orm` | Todos WS |
| `/api/news/*` | Todos Real Time |

---

## 🔧 Implementação Sugerida

### 1. Middleware de Tier (Backend)

```php
// src/Middleware/TierMiddleware.php

class TierMiddleware
{
    private string $requiredTier;
    
    public function __construct(string $requiredTier = 'FREE')
    {
        $this->requiredTier = $requiredTier;
    }
    
    public function handle(): bool
    {
        $user = (new AuthService())->getCurrentUser();
        $userTier = TierService::getEffectiveTier($user);
        
        $tierOrder = ['FREE' => 1, 'PLUS' => 2, 'PRO' => 3];
        
        if ($tierOrder[$userTier] < $tierOrder[$this->requiredTier]) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'tier_required',
                'required_tier' => $this->requiredTier,
                'user_tier' => $userTier
            ]);
            return false;
        }
        
        return true;
    }
}
```

### 2. Resposta com Intervalo (Backend)

```php
// Em cada controller de API

public function data(): void
{
    $user = $this->authService->getCurrentUser();
    $tier = TierService::getEffectiveTier($user);
    $config = TierService::getFeatureConfig($tier, 'indicadores_sentimento');
    
    // Dados normais...
    $data = $this->fetchData();
    
    // Adicionar config de atualização na resposta
    echo json_encode([
        'success' => true,
        'data' => $data,
        '_tier' => [
            'current' => $tier,
            'update_type' => $config['update_type'],
            'interval_ms' => $config['interval_ms'] ?? null,
            'next_update' => time() + (($config['interval_ms'] ?? 60000) / 1000)
        ]
    ]);
}
```

### 3. Cliente JS Inteligente

```javascript
// public/assets/js/tier-polling.js

class TierPolling {
    constructor(endpoint, feature, onData) {
        this.endpoint = endpoint;
        this.feature = feature;
        this.onData = onData;
        this.timer = null;
        this.interval = 60000; // default
    }
    
    async fetch() {
        try {
            const resp = await fetch(this.endpoint);
            const json = await resp.json();
            
            if (!json.success) {
                if (json.error === 'tier_required') {
                    TierUtils.showUpgradeModal(this.feature, json.required_tier);
                    return;
                }
            }
            
            // Atualizar intervalo baseado na resposta
            if (json._tier?.interval_ms) {
                this.updateInterval(json._tier.interval_ms);
            }
            
            this.onData(json.data);
        } catch (e) {
            console.error('[TierPolling] Error:', e);
        }
    }
    
    updateInterval(newInterval) {
        if (newInterval !== this.interval) {
            this.interval = newInterval;
            this.stop();
            this.start();
        }
    }
    
    start() {
        this.fetch(); // Primeira chamada
        this.timer = setInterval(() => this.fetch(), this.interval);
    }
    
    stop() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    }
}
```

---

## ⚠️ Considerações

1. **Cache**: Considerar adicionar cache por tier para evitar muitas requisições
2. **Rate Limit**: Implementar rate limiting por tier se necessário
3. **WebSocket**: Para PRO, considerar migrar para WS real no futuro
4. **Fallback**: Se WS falhar, usar polling com intervalo do tier

---

*Documento criado em: 2026-01-01*
*Versão: 1.0*
