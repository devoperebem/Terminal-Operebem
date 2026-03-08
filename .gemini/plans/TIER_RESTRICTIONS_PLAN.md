# 🔒 Plano de Implementação: Restrições por Tier

**Data:** 2026-01-01
**Status:** Planejamento
**Dependência:** STRIPE_INTEGRATION_PLAN.md

---

## 📊 Matriz de Features por Tier

| # | Feature | FREE | PLUS | PRO | Tipo |
|---|---------|------|------|-----|------|
| 1 | Dashboard Principal | WS | WS | WS | Acesso + Tempo Real |
| 2 | Dashboard Ouro | ❌ | WS | WS | Bloqueio + Tempo Real |
| 3 | Dashboard NASDAQ | ❌ | WS | WS | Bloqueio + Tempo Real |
| 4 | Indicadores Sentimento | 5 MIN | 1 MIN | WS | Intervalo Variável |
| 5 | Indicadores Operebem | 5 MIN | 1 MIN | WS | Intervalo Variável |
| 6 | Federal Reserve | ✅ | ✅ | ✅ | Todos Real Time |
| 7 | Relógio | ✅ | ✅ | ✅ | Todos Real Time |
| 8 | Snapshot Simples | ✅ | ✅ | ✅ | Todos Têm Acesso |
| 9 | Snapshot Avançada | ❌ | ✅ | ✅ | Bloqueio |
| 10 | Médias Cards Cotação | ❌ | ✅ | ✅ | Bloqueio |
| 11 | Notícias | ✅ | ✅ | ✅ | Todos Real Time |

---

## 🎯 Tipos de Restrição

### Tipo 1: Bloqueio Total
Feature não disponível para o tier.
- Dashboard Ouro (FREE)
- Dashboard NASDAQ (FREE)
- Snapshot Avançada (FREE)
- Médias Cards Cotação (FREE)

**Implementação:**
- Verificar tier antes de renderizar
- Mostrar overlay/modal de upgrade
- Não carregar dados

### Tipo 2: Intervalo de Atualização
Dados atualizados em intervalos diferentes por tier.
- Indicadores Sentimento: FREE=5min, PLUS=1min, PRO=WS
- Indicadores Operebem: FREE=5min, PLUS=1min, PRO=WS

**Implementação:**
- Configurar intervalo de polling por tier
- PRO usa WebSocket
- Outros usam setInterval com tempo do tier

### Tipo 3: Igual para Todos
Sem restrição, todos têm acesso igual.
- Dashboard Principal
- Federal Reserve
- Relógio
- Snapshot Simples
- Notícias

---

## 📁 Arquivos Afetados

### Frontend (JavaScript)

| Arquivo | Feature | Mudança |
|---------|---------|---------|
| `public/assets/js/dashboard.js` | Dashboard Principal | Verificar tier, ajustar WS |
| `public/assets/js/gold-dashboard.js` | Dashboard Ouro | Verificar tier, bloquear FREE |
| `public/assets/dev/js/gold-dashboard.js` | Dashboard Ouro (dev) | Mesmo acima |
| `public/assets/js/boot.js` | Geral | Expor tier global |
| `public/assets/js/home-websocket.js` | WebSocket | Lógica de reconexão |
| (novo) `public/assets/js/indicators.js` | Indicadores | Polling variável |
| (novo) `public/assets/js/nasdaq-dashboard.js` | Dashboard NASDAQ | A implementar |

### Backend (PHP)

| Arquivo | Feature | Mudança |
|---------|---------|---------|
| `src/Controllers/DashboardController.php` | Dashboards | Verificar tier |
| `src/Controllers/IndicatorsController.php` | Indicadores | Passar tier para view |
| `src/Views/app/dashboard.php` | Dashboard Principal | Passar tier para JS |
| `src/Views/app/dashboard-gold.php` | Dashboard Ouro | Bloquear FREE |
| `src/Views/app/indicators/feeling.php` | Indicadores | Intervalo por tier |
| `src/Middleware/AuthMiddleware.php` | Autenticação | Já passa tier |
| (novo) `src/Services/TierService.php` | Utilitário | Configurações de tier |

### Layouts

| Arquivo | Mudança |
|---------|---------|
| `src/Views/layouts/app.php` | Expor tier como variável JS global |

---

## 🏗️ Arquitetura da Solução

### 1. Serviço de Configuração de Tier

```php
// src/Services/TierService.php

class TierService
{
    /**
     * Configurações de features por tier
     */
    private static array $features = [
        'dashboard_principal' => [
            'FREE' => ['access' => true, 'update_type' => 'websocket'],
            'PLUS' => ['access' => true, 'update_type' => 'websocket'],
            'PRO'  => ['access' => true, 'update_type' => 'websocket'],
        ],
        'dashboard_ouro' => [
            'FREE' => ['access' => false],
            'PLUS' => ['access' => true, 'update_type' => 'websocket'],
            'PRO'  => ['access' => true, 'update_type' => 'websocket'],
        ],
        'dashboard_nasdaq' => [
            'FREE' => ['access' => false],
            'PLUS' => ['access' => true, 'update_type' => 'websocket'],
            'PRO'  => ['access' => true, 'update_type' => 'websocket'],
        ],
        'indicadores_sentimento' => [
            'FREE' => ['access' => true, 'update_type' => 'polling', 'interval_ms' => 300000], // 5 min
            'PLUS' => ['access' => true, 'update_type' => 'polling', 'interval_ms' => 60000],  // 1 min
            'PRO'  => ['access' => true, 'update_type' => 'websocket'],
        ],
        'indicadores_operebem' => [
            'FREE' => ['access' => true, 'update_type' => 'polling', 'interval_ms' => 300000],
            'PLUS' => ['access' => true, 'update_type' => 'polling', 'interval_ms' => 60000],
            'PRO'  => ['access' => true, 'update_type' => 'websocket'],
        ],
        'snapshot_avancada' => [
            'FREE' => ['access' => false],
            'PLUS' => ['access' => true],
            'PRO'  => ['access' => true],
        ],
        'medias_cards_cotacao' => [
            'FREE' => ['access' => false],
            'PLUS' => ['access' => true],
            'PRO'  => ['access' => true],
        ],
    ];

    /**
     * Verifica se um tier tem acesso a uma feature
     */
    public static function hasAccess(string $tier, string $feature): bool
    {
        $tier = strtoupper($tier ?: 'FREE');
        return self::$features[$feature][$tier]['access'] ?? false;
    }

    /**
     * Retorna configuração completa de uma feature para um tier
     */
    public static function getFeatureConfig(string $tier, string $feature): array
    {
        $tier = strtoupper($tier ?: 'FREE');
        return self::$features[$feature][$tier] ?? ['access' => false];
    }

    /**
     * Retorna todas as configurações de features para um tier
     * (para passar para o frontend)
     */
    public static function getAllFeaturesForTier(string $tier): array
    {
        $tier = strtoupper($tier ?: 'FREE');
        $result = [];
        foreach (self::$features as $feature => $tiers) {
            $result[$feature] = $tiers[$tier] ?? ['access' => false];
        }
        return $result;
    }
}
```

### 2. Expor Tier no Frontend

```php
// src/Views/layouts/app.php (no <head>)

<script>
window.OPEREBEM = window.OPEREBEM || {};
window.OPEREBEM.user = {
    tier: '<?= htmlspecialchars($user['tier'] ?? 'FREE') ?>',
    isLoggedIn: <?= isset($user) ? 'true' : 'false' ?>
};
window.OPEREBEM.features = <?= json_encode(\App\Services\TierService::getAllFeaturesForTier($user['tier'] ?? 'FREE')) ?>;
</script>
```

### 3. Utilitário JavaScript

```javascript
// public/assets/js/tier-utils.js

window.TierUtils = {
    /**
     * Verifica se usuário tem acesso a feature
     */
    hasAccess: function(feature) {
        return window.OPEREBEM?.features?.[feature]?.access ?? false;
    },

    /**
     * Retorna tipo de atualização (websocket/polling)
     */
    getUpdateType: function(feature) {
        return window.OPEREBEM?.features?.[feature]?.update_type ?? 'polling';
    },

    /**
     * Retorna intervalo de polling em ms
     */
    getInterval: function(feature) {
        return window.OPEREBEM?.features?.[feature]?.interval_ms ?? 60000;
    },

    /**
     * Retorna tier do usuário
     */
    getTier: function() {
        return window.OPEREBEM?.user?.tier ?? 'FREE';
    },

    /**
     * Mostra modal de upgrade
     */
    showUpgradeModal: function(feature, requiredTier) {
        // TODO: Implementar modal bonito
        const modal = document.createElement('div');
        modal.className = 'tier-upgrade-modal';
        modal.innerHTML = `
            <div class="tier-upgrade-content">
                <h3>🔒 Feature Premium</h3>
                <p>Esta funcionalidade requer o plano <strong>${requiredTier}</strong>.</p>
                <a href="/subscription/plans" class="btn btn-primary">Ver Planos</a>
                <button onclick="this.closest('.tier-upgrade-modal').remove()">Fechar</button>
            </div>
        `;
        document.body.appendChild(modal);
    },

    /**
     * Configura polling com intervalo baseado no tier
     */
    setupPolling: function(feature, callback) {
        const config = window.OPEREBEM?.features?.[feature];
        if (!config?.access) return null;

        if (config.update_type === 'websocket') {
            // Retorna null - usar WebSocket diretamente
            return null;
        }

        return setInterval(callback, config.interval_ms || 60000);
    }
};
```

---

## 🔧 Implementação por Feature

### Feature 1: Dashboard Principal
**Status:** ✅ Todos têm acesso WS

**Mudanças necessárias:** Nenhuma (já funciona para todos)

---

### Feature 2: Dashboard Ouro
**Status:** Bloquear FREE

**Arquivo:** `src/Views/app/dashboard-gold.php`

```php
<?php
// No início do arquivo
$userTier = $user['tier'] ?? 'FREE';
$hasAccess = \App\Services\TierService::hasAccess($userTier, 'dashboard_ouro');

if (!$hasAccess) {
    // Renderizar versão bloqueada
    include __DIR__ . '/partials/upgrade-required.php';
    return;
}
// Resto do dashboard...
?>
```

**Arquivo:** `public/assets/js/gold-dashboard.js`

```javascript
// No início
if (!TierUtils.hasAccess('dashboard_ouro')) {
    console.warn('Dashboard Ouro requer tier PLUS ou superior');
    TierUtils.showUpgradeModal('dashboard_ouro', 'PLUS');
    return;
}
```

---

### Feature 3: Dashboard NASDAQ
**Status:** A implementar futuramente

**Nota:** Seguirá mesma lógica do Dashboard Ouro

---

### Feature 4 & 5: Indicadores (Sentimento e Operebem)
**Status:** Intervalo variável por tier

**Arquivo:** `src/Views/app/indicators/feeling.php`

```php
<?php
$userTier = $user['tier'] ?? 'FREE';
$config = \App\Services\TierService::getFeatureConfig($userTier, 'indicadores_sentimento');
?>

<script>
const indicatorConfig = <?= json_encode($config) ?>;

if (indicatorConfig.update_type === 'websocket') {
    // Conectar WebSocket
    initIndicatorWebSocket();
} else {
    // Usar polling
    setInterval(fetchIndicatorData, indicatorConfig.interval_ms);
}
</script>
```

---

### Feature 9 & 10: Snapshot Avançada e Médias Cards
**Status:** Bloquear FREE

**Implementação:** Esconder elementos no HTML + não carregar dados

```php
<?php if (\App\Services\TierService::hasAccess($user['tier'], 'snapshot_avancada')): ?>
    <!-- Conteúdo do snapshot avançado -->
<?php else: ?>
    <div class="feature-locked">
        <i class="fas fa-lock"></i>
        <p>Disponível no plano PLUS</p>
        <a href="/subscription/plans">Fazer Upgrade</a>
    </div>
<?php endif; ?>
```

---

## 🌐 WebSocket - Análise

### APIs Atuais que usam WebSocket

| API/Endpoint | Uso | Suporta WS? |
|--------------|-----|-------------|
| Cotações Ouro | Dashboard Ouro | ✅ Já usa WS |
| News | Notícias | Verificar |
| Indicadores | Sentimento/Operebem | ❌ Polling atual |
| Federal Reserve | Datas FOMC | Não precisa (dados estáticos) |

### Para implementar WS nos Indicadores (PRO)

**Opção A:** Criar servidor WebSocket próprio
- Mais complexo
- Mais controle
- Precisa de servidor dedicado

**Opção B:** Usar polling rápido (5 segundos) simulando "tempo real"
- Mais simples
- Funciona com infraestrutura atual
- Usa mais recursos

**Recomendação:** Começar com **Opção B** (polling rápido para PRO) e migrar para WS depois se necessário.

---

## 📋 Checklist de Implementação

### Fase 1: Infraestrutura (1-2 horas)
- [ ] Criar `TierService.php`
- [ ] Criar `tier-utils.js`
- [ ] Modificar `app.php` para expor tier no JS
- [ ] Criar CSS para `.feature-locked`

### Fase 2: Dashboard Ouro (1 hora)
- [ ] Adicionar verificação de tier no controller
- [ ] Adicionar verificação no JavaScript
- [ ] Criar partial `upgrade-required.php`
- [ ] Testar bloqueio para FREE

### Fase 3: Indicadores (2-3 horas)
- [ ] Modificar `feeling.php` para usar intervalo por tier
- [ ] Criar arquivo JS para indicadores com polling configurável
- [ ] Testar intervalos: FREE=5min, PLUS=1min, PRO=5s

### Fase 4: Snapshot e Médias (1-2 horas)
- [ ] Identificar onde estão os componentes
- [ ] Adicionar verificação de tier
- [ ] Esconder/bloquear para FREE

### Fase 5: Testes (1 hora)
- [ ] Testar como FREE
- [ ] Testar como PLUS
- [ ] Testar como PRO
- [ ] Verificar transição de tier

---

## ⚠️ Considerações Importantes

### 1. Cache do Tier
O tier deve ser verificado em CADA requisição, não cacheado no frontend por muito tempo.
- JWT já contém tier (usar `getEffectiveTier` que verifica expiração)
- Sessão tem tier atualizado

### 2. Segurança
- Verificação de tier deve ser feita TAMBÉM no backend
- Não confiar apenas em verificação JavaScript
- APIs devem validar tier antes de retornar dados sensíveis

### 3. UX de Upgrade
- Mostrar claramente o que está bloqueado
- Facilitar caminho para upgrade
- Não frustrar usuário FREE demais

### 4. Fallback
- Se WebSocket falhar, fazer fallback para polling
- Se tier não puder ser determinado, assumir FREE

---

## 🔗 Dependências

1. **STRIPE_INTEGRATION_PLAN.md** - Sistema de assinaturas
2. **TierService** - Verificação de tier (criar)
3. **getEffectiveTier** - Já existe no SsoController (mover para Service)

---

## 📅 Estimativa Total

| Fase | Tempo |
|------|-------|
| Infraestrutura | 2h |
| Dashboard Ouro | 1h |
| Indicadores | 3h |
| Snapshot/Médias | 2h |
| Testes | 1h |
| **Total** | **~9 horas** |

---

*Documento criado em: 2026-01-01*
*Versão: 1.0*
