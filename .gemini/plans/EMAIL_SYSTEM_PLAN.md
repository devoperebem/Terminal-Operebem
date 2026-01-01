# 📧 Plano de Implementação: Sistema de Emails Flexível

**Data:** 2026-01-01
**Status:** Planejamento
**Objetivo:** Sistema de emails configurável pelo admin, com templates editáveis e triggers para qualquer situação

---

## 🎯 Requisitos

1. **Possibilidade de enviar email em qualquer situação:**
   - Criação de conta
   - Compra/assinatura
   - Pagamento confirmado
   - Pagamento falhou
   - Trial iniciado
   - Trial expirando (X dias antes)
   - Trial expirado
   - Assinatura renovada
   - Assinatura cancelada
   - Assinatura expirada
   - X dias de inatividade
   - Boas-vindas
   - Recuperação de senha
   - Qualquer evento customizado

2. **Templates editáveis pelo admin:**
   - Texto simples
   - HTML customizado
   - Variáveis dinâmicas
   - Imagens
   - Botões/links
   - Preview antes de enviar

3. **Configurável:**
   - Ativar/desativar cada trigger
   - Editar template de cada evento
   - Testar envio
   - Ver histórico de envios

---

## 🗄️ Banco de Dados

### Tabela: `email_templates`

```sql
CREATE TABLE email_templates (
    id SERIAL PRIMARY KEY,
    
    -- Identificação
    slug VARCHAR(100) UNIQUE NOT NULL, -- 'welcome', 'payment_success', etc
    name VARCHAR(255) NOT NULL, -- Nome amigável
    description TEXT, -- Descrição do quando é usado
    category VARCHAR(50) DEFAULT 'system', -- 'system', 'subscription', 'marketing'
    
    -- Template
    subject VARCHAR(500) NOT NULL,
    body_html TEXT NOT NULL,
    body_text TEXT, -- Versão texto plano (opcional)
    
    -- Configurações
    is_active BOOLEAN DEFAULT TRUE,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER REFERENCES admin_users(id),
    updated_by INTEGER REFERENCES admin_users(id)
);

CREATE INDEX idx_email_templates_slug ON email_templates(slug);
CREATE INDEX idx_email_templates_category ON email_templates(category);
```

### Tabela: `email_triggers`

```sql
CREATE TABLE email_triggers (
    id SERIAL PRIMARY KEY,
    
    -- Identificação
    event_name VARCHAR(100) UNIQUE NOT NULL, -- 'user.created', 'subscription.paid', etc
    description TEXT,
    
    -- Template associado
    template_id INTEGER REFERENCES email_templates(id),
    
    -- Configurações
    is_enabled BOOLEAN DEFAULT FALSE, -- Por padrão, desabilitado
    delay_minutes INTEGER DEFAULT 0, -- Delay antes de enviar (0 = imediato)
    
    -- Condições (JSON) - para lógica avançada
    conditions JSONB DEFAULT '{}',
    -- Ex: {"tier": ["PLUS", "PRO"], "min_days_registered": 7}
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_email_triggers_event ON email_triggers(event_name);
CREATE INDEX idx_email_triggers_enabled ON email_triggers(is_enabled);
```

### Tabela: `email_queue`

```sql
CREATE TABLE email_queue (
    id SERIAL PRIMARY KEY,
    
    -- Destinatário
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    to_email VARCHAR(255) NOT NULL,
    to_name VARCHAR(255),
    
    -- Conteúdo
    template_id INTEGER REFERENCES email_templates(id),
    subject VARCHAR(500) NOT NULL,
    body_html TEXT NOT NULL,
    body_text TEXT,
    
    -- Status
    status VARCHAR(50) DEFAULT 'pending',
    -- Valores: pending, scheduled, sending, sent, failed, cancelled
    
    -- Agendamento
    scheduled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Resultado
    sent_at TIMESTAMP,
    error_message TEXT,
    retry_count INTEGER DEFAULT 0,
    
    -- Metadata
    event_name VARCHAR(100), -- Qual evento gerou
    metadata JSONB DEFAULT '{}', -- Dados extras
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_email_queue_status ON email_queue(status);
CREATE INDEX idx_email_queue_scheduled ON email_queue(scheduled_at);
CREATE INDEX idx_email_queue_user ON email_queue(user_id);
```

### Tabela: `email_log`

```sql
CREATE TABLE email_log (
    id SERIAL PRIMARY KEY,
    
    -- Referências
    queue_id INTEGER REFERENCES email_queue(id),
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    template_id INTEGER REFERENCES email_templates(id),
    
    -- Detalhes
    to_email VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    event_name VARCHAR(100),
    
    -- Status
    status VARCHAR(50) NOT NULL, -- 'sent', 'failed', 'bounced', 'opened', 'clicked'
    
    -- Métricas
    opened_at TIMESTAMP,
    clicked_at TIMESTAMP,
    
    -- Resultado
    provider_response TEXT, -- Resposta do provedor de email
    error_message TEXT,
    
    -- Timestamps
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_email_log_user ON email_log(user_id);
CREATE INDEX idx_email_log_status ON email_log(status);
CREATE INDEX idx_email_log_event ON email_log(event_name);
```

---

## 📁 Estrutura de Arquivos

```
src/
├── Services/
│   ├── EmailService.php              # Serviço principal de envio
│   ├── EmailTemplateService.php      # Gerenciamento de templates
│   ├── EmailQueueService.php         # Fila de emails
│   └── EmailTriggerService.php       # Gerenciamento de triggers
│
├── Controllers/
│   └── Admin/
│       └── EmailController.php       # CRUD de templates e triggers
│
├── Views/
│   └── admin_secure/
│       └── emails/
│           ├── templates/
│           │   ├── index.php         # Lista templates
│           │   ├── edit.php          # Editar template
│           │   └── preview.php       # Preview
│           ├── triggers/
│           │   ├── index.php         # Lista triggers
│           │   └── edit.php          # Configurar trigger
│           ├── queue/
│           │   └── index.php         # Fila de emails
│           └── log/
│               └── index.php         # Histórico de envios
│
├── Events/
│   └── EmailEvents.php               # Constantes de eventos

database/
└── migrations/
    ├── 026_create_email_templates_table.sql
    ├── 027_create_email_triggers_table.sql
    ├── 028_create_email_queue_table.sql
    └── 029_create_email_log_table.sql
```

---

## 🏗️ Arquitetura

### EmailService (Principal)

```php
namespace App\Services;

class EmailService
{
    /**
     * Dispara um evento de email
     * O sistema verifica se há trigger ativo para esse evento
     */
    public static function trigger(string $eventName, int $userId, array $data = []): void
    {
        $trigger = EmailTriggerService::findByEvent($eventName);
        
        if (!$trigger || !$trigger['is_enabled']) {
            return; // Trigger não existe ou está desabilitado
        }
        
        // Verificar condições
        if (!self::checkConditions($trigger, $userId, $data)) {
            return;
        }
        
        // Buscar usuário
        $user = Database::fetch('SELECT * FROM users WHERE id = ?', [$userId]);
        if (!$user) return;
        
        // Buscar template
        $template = EmailTemplateService::find($trigger['template_id']);
        if (!$template || !$template['is_active']) return;
        
        // Processar variáveis no template
        $variables = self::buildVariables($user, $data);
        $subject = self::processVariables($template['subject'], $variables);
        $bodyHtml = self::processVariables($template['body_html'], $variables);
        $bodyText = self::processVariables($template['body_text'] ?? '', $variables);
        
        // Adicionar à fila
        $scheduledAt = new DateTime();
        if ($trigger['delay_minutes'] > 0) {
            $scheduledAt->modify("+{$trigger['delay_minutes']} minutes");
        }
        
        EmailQueueService::add([
            'user_id' => $userId,
            'to_email' => $user['email'],
            'to_name' => $user['name'],
            'template_id' => $template['id'],
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
            'event_name' => $eventName,
            'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
            'metadata' => json_encode($data)
        ]);
    }
    
    /**
     * Envia email diretamente (sem trigger)
     */
    public static function send(string $to, string $subject, string $bodyHtml, ?string $bodyText = null): bool
    {
        // Usar MailService existente
        return (new MailService())->send($to, $subject, $bodyHtml);
    }
    
    /**
     * Processa variáveis no template
     */
    private static function processVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        return $content;
    }
    
    /**
     * Monta array de variáveis disponíveis
     */
    private static function buildVariables(array $user, array $extra = []): array
    {
        return array_merge([
            'user_name' => $user['name'] ?? '',
            'user_first_name' => explode(' ', $user['name'] ?? '')[0],
            'user_email' => $user['email'] ?? '',
            'user_tier' => $user['tier'] ?? 'FREE',
            'user_id' => $user['id'] ?? '',
            'date' => date('d/m/Y'),
            'time' => date('H:i'),
            'year' => date('Y'),
            'app_name' => 'Terminal Operebem',
            'app_url' => 'https://terminal.operebem.com.br',
            'support_email' => 'suporte@operebem.com.br',
        ], $extra);
    }
}
```

---

## 📝 Eventos Disponíveis

### Sistema (padrão)
| Evento | Descrição | Variáveis Extras |
|--------|-----------|------------------|
| `user.created` | Conta criada | - |
| `user.verified` | Email verificado | - |
| `user.password_reset_requested` | Recuperação solicitada | `reset_link` |
| `user.password_changed` | Senha alterada | - |

### Assinaturas
| Evento | Descrição | Variáveis Extras |
|--------|-----------|------------------|
| `subscription.trial_started` | Trial iniciado | `trial_end_date`, `plan_name` |
| `subscription.trial_ending` | Trial expira em X dias | `days_remaining`, `plan_name` |
| `subscription.trial_expired` | Trial expirou | `plan_name` |
| `subscription.created` | Assinatura criada | `plan_name`, `price` |
| `subscription.renewed` | Assinatura renovada | `plan_name`, `next_billing_date` |
| `subscription.canceled` | Assinatura cancelada | `end_date`, `plan_name` |
| `subscription.expired` | Assinatura expirou | `plan_name` |

### Pagamentos
| Evento | Descrição | Variáveis Extras |
|--------|-----------|------------------|
| `payment.succeeded` | Pagamento confirmado | `amount`, `plan_name`, `invoice_url` |
| `payment.failed` | Pagamento falhou | `amount`, `failure_reason`, `retry_date` |
| `payment.refunded` | Reembolso processado | `amount` |

### Engajamento
| Evento | Descrição | Variáveis Extras |
|--------|-----------|------------------|
| `user.inactive_7d` | Inativo há 7 dias | `last_login` |
| `user.inactive_30d` | Inativo há 30 dias | `last_login` |
| `gamification.level_up` | Subiu de nível | `new_level`, `xp` |
| `gamification.streak_milestone` | Streak de X dias | `streak_days` |

---

## 🎨 Interface Admin

### Lista de Templates
- Nome, categoria, status (ativo/inativo)
- Preview inline
- Botões: Editar, Duplicar, Testar

### Editor de Template
- Campo: Nome
- Campo: Slug (auto-gerado)
- Campo: Assunto (com variáveis)
- Editor HTML: WYSIWYG + código fonte
- Lista de variáveis disponíveis
- Preview em tempo real
- Botão: Enviar teste para meu email

### Lista de Triggers
- Evento, template associado, status
- Delay configurável
- Condições (JSON)
- Toggle ativar/desativar

### Fila de Emails
- Lista de emails pendentes
- Status, agendamento
- Botões: Enviar agora, Cancelar

### Log de Emails
- Histórico de todos os emails enviados
- Filtros: evento, status, período
- Detalhes com conteúdo enviado

---

## 🔧 Rotas Admin

```php
// Emails - Templates
$router->get('/secure/adm/emails/templates', [EmailController::class, 'templates'], [SecureAdminMiddleware::class]);
$router->get('/secure/adm/emails/templates/create', [EmailController::class, 'createTemplate'], [SecureAdminMiddleware::class]);
$router->get('/secure/adm/emails/templates/edit', [EmailController::class, 'editTemplate'], [SecureAdminMiddleware::class]);
$router->post('/secure/adm/emails/templates/store', [EmailController::class, 'storeTemplate'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/emails/templates/update', [EmailController::class, 'updateTemplate'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/emails/templates/delete', [EmailController::class, 'deleteTemplate'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/emails/templates/test', [EmailController::class, 'testTemplate'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/secure/adm/emails/templates/preview', [EmailController::class, 'previewTemplate'], [SecureAdminMiddleware::class]);

// Emails - Triggers
$router->get('/secure/adm/emails/triggers', [EmailController::class, 'triggers'], [SecureAdminMiddleware::class]);
$router->get('/secure/adm/emails/triggers/edit', [EmailController::class, 'editTrigger'], [SecureAdminMiddleware::class]);
$router->post('/secure/adm/emails/triggers/update', [EmailController::class, 'updateTrigger'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/emails/triggers/toggle', [EmailController::class, 'toggleTrigger'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);

// Emails - Fila e Log
$router->get('/secure/adm/emails/queue', [EmailController::class, 'queue'], [SecureAdminMiddleware::class]);
$router->post('/secure/adm/emails/queue/send-now', [EmailController::class, 'sendNow'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/secure/adm/emails/queue/cancel', [EmailController::class, 'cancelQueue'], [SecureAdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/secure/adm/emails/log', [EmailController::class, 'log'], [SecureAdminMiddleware::class]);
```

---

## 📧 Template Padrão de Exemplo

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; }
        .header img { max-width: 150px; }
        .header h1 { color: #fff; margin: 20px 0 0; font-size: 24px; }
        .content { padding: 30px; }
        .content h2 { color: #333; }
        .content p { color: #666; line-height: 1.6; }
        .button { display: inline-block; background: #667eea; color: #fff !important; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { background: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="https://terminal.operebem.com.br/assets/img/logo.png" alt="Operebem">
            <h1>{{app_name}}</h1>
        </div>
        <div class="content">
            <h2>Olá, {{user_first_name}}! 👋</h2>
            <p>
                Seja bem-vindo ao Terminal Operebem!
            </p>
            <p>
                Sua conta foi criada com sucesso. Agora você tem acesso a:
            </p>
            <ul>
                <li>Dashboard de cotações em tempo real</li>
                <li>Indicadores de sentimento de mercado</li>
                <li>Notícias financeiras</li>
                <li>E muito mais!</li>
            </ul>
            <a href="{{app_url}}/app/dashboard" class="button">Acessar Terminal</a>
        </div>
        <div class="footer">
            <p>© {{year}} {{app_name}}. Todos os direitos reservados.</p>
            <p>
                <a href="{{app_url}}/terms">Termos</a> | 
                <a href="{{app_url}}/privacy">Privacidade</a> |
                <a href="mailto:{{support_email}}">Suporte</a>
            </p>
        </div>
    </div>
</body>
</html>
```

---

## 📅 Cronograma

| Fase | Tempo |
|------|-------|
| Migrations | 1h |
| EmailService + TemplateService | 2h |
| EmailQueueService + processador | 2h |
| EmailTriggerService | 1h |
| Controller Admin | 2h |
| Views Admin (Templates) | 3h |
| Views Admin (Triggers, Queue, Log) | 2h |
| Templates padrão | 1h |
| Testes | 1h |
| **Total** | **~15 horas** |

---

## ⚠️ Considerações

1. **Processador de Fila**: Implementar cron job para processar fila a cada minuto
2. **Rate Limiting**: Limitar quantidade de emails por hora para evitar spam
3. **Unsubscribe**: Considerar link de unsubscribe em emails de marketing
4. **Tracking**: Pixel de abertura e tracking de cliques (opcional)

---

*Documento criado em: 2026-01-01*
*Versão: 1.0*
