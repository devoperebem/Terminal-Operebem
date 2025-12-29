# 🔐 Documentação do Sistema SSO - Terminal Operebem

## Visão Geral

O Terminal Operebem implementa um sistema de **Single Sign-On (SSO)** baseado em **JWT (JSON Web Tokens)** para permitir autenticação automática em sistemas conectados.

---

## 📋 Sistemas Conectados

### 1. Portal do Aluno
- **URL**: `https://aluno.operebem.com.br`
- **Endpoint SSO**: `/sso/start`
- **Callback**: `https://aluno.operebem.com.br/sso/callback?token=<JWT>`

### 2. Diário Operebem
- **URL**: `https://diario.operebem.com.br`
- **Endpoint SSO**: `/sso/diario/start`
- **Callback**: `https://diario.operebem.com.br/sso/callback?token=<JWT>`

---

## 🔑 Estrutura do Token JWT SSO

### Header
```json
{
  "alg": "HS256",
  "typ": "JWT"
}
```

### Payload (Claims)
| Claim | Tipo | Descrição | Exemplo |
|-------|------|-----------|---------|
| `iss` | string | Issuer - Quem emitiu o token | `https://terminal.operebem.com.br` |
| `aud` | string | Audience - Sistema destino | `https://aluno.operebem.com.br` |
| `sub` | int | Subject - ID do usuário | `123` |
| `email` | string | Email do usuário | `usuario@exemplo.com` |
| `tier` | string | Nível de assinatura do usuário | `FREE`, `PLUS` ou `PRO` |
| `iat` | int | Issued At - Timestamp de emissão | `1703793600` |
| `exp` | int | Expiration - Timestamp de expiração | `1703793660` |
| `jti` | string | JWT ID - Identificador único | `a1b2c3d4e5f6...` (32 hex chars) |

### Exemplo de Payload Completo
```json
{
  "iss": "https://terminal.operebem.com.br",
  "aud": "https://aluno.operebem.com.br",
  "sub": 123,
  "email": "usuario@exemplo.com",
  "tier": "FREE",
  "iat": 1703793600,
  "exp": 1703793660,
  "jti": "a1b2c3d4e5f67890abcdef1234567890"
}
```

---

## 🔄 Fluxo de Autenticação SSO

```
┌─────────────────┐     ┌─────────────────────┐     ┌─────────────────────┐
│     Usuário     │     │   Terminal (IdP)    │     │  Sistema Destino    │
│                 │     │                     │     │  (Aluno/Diário)     │
└────────┬────────┘     └──────────┬──────────┘     └──────────┬──────────┘
         │                         │                           │
         │  1. Clica em link SSO   │                           │
         │─────────────────────────▶                           │
         │                         │                           │
         │  2. Verifica autenticação                           │
         │  (já logado no Terminal?)                           │
         │                         │                           │
         │  3. Gera token JWT      │                           │
         │                         │                           │
         │  4. Redirect com token  │                           │
         │◀─────────────────────────                           │
         │                         │                           │
         │  5. Redirect para callback                          │
         │────────────────────────────────────────────────────▶│
         │                                                     │
         │                         6. Valida token JWT         │
         │                         7. Cria sessão local        │
         │                         8. Redireciona para destino │
         │◀────────────────────────────────────────────────────│
         │                                                     │
```

### Passo a Passo:

1. **Usuário clica em link SSO** no Terminal (ex: "Acessar Portal do Aluno")
2. **Terminal verifica autenticação**:
   - Se não logado → Redireciona para login com `?modal=login`
   - Se logado → Continua para gerar token
3. **Terminal gera token JWT** com claims do usuário
4. **Terminal redireciona** para sistema destino com token na URL
5. **Sistema destino recebe** requisição no `/sso/callback?token=<JWT>`
6. **Sistema destino valida** token JWT (assinatura, exp, aud)
7. **Sistema destino cria sessão** local para o usuário
8. **Usuário é redirecionado** para página solicitada

---

## 🔧 Configuração (Variáveis de Ambiente)

### Portal do Aluno
```env
SSO_SHARED_SECRET=chave_secreta_compartilhada
SSO_ISSUER=https://terminal.operebem.com.br
SSO_AUDIENCE=https://aluno.operebem.com.br
SSO_TTL=60
```

### Diário Operebem
```env
SSO_DIARIO_SECRET=chave_secreta_diario
SSO_DIARIO_ISSUER=https://terminal.operebem.com.br
SSO_DIARIO_AUDIENCE=https://diario.operebem.com.br
SSO_DIARIO_TTL=60
```

| Variável | Descrição | Padrão |
|----------|-----------|--------|
| `SSO_SHARED_SECRET` | Chave secreta HMAC-SHA256 | (obrigatório) |
| `SSO_ISSUER` | Identificador do emissor | URL do Terminal |
| `SSO_AUDIENCE` | URL do sistema destino | Sistema específico |
| `SSO_TTL` | Tempo de vida do token (segundos) | `60` |

---

## 🔒 Tokens Internos do Terminal (User JWT)

Além do SSO, o Terminal também emite tokens JWT para autenticação interna:

### Access Token (curta duração)
| Claim | Descrição |
|-------|-----------|
| `iss` | Issuer |
| `aud` | Audience |
| `sub` | ID do usuário |
| `role` | Papel (`user`) |
| `tier` | Nível de assinatura (`FREE`, `PLUS`, `PRO`) |
| `iat` | Timestamp de emissão |
| `nbf` | Not Before (válido a partir de) |
| `exp` | Expiração |
| `typ` | Tipo (`access`) |

### Refresh Token (longa duração - 30 dias)
| Claim | Descrição |
|-------|-----------|
| `iss` | Issuer |
| `aud` | Audience |
| `sub` | ID do usuário |
| `role` | Papel (`user`) |
| `tier` | Nível de assinatura (`FREE`, `PLUS`, `PRO`) |
| `iat` | Timestamp de emissão |
| `nbf` | Not Before |
| `exp` | Expiração |
| `jti` | JWT ID único |
| `typ` | Tipo (`refresh`) |

### Exemplo de Access Token Payload
```json
{
  "iss": "https://terminal.operebem.com.br",
  "aud": "https://terminal.operebem.com.br",
  "sub": 123,
  "role": "user",
  "tier": "FREE",
  "iat": 1703793600,
  "nbf": 1703793600,
  "exp": 1703794200,
  "typ": "access"
}
```

---

## 📊 Comparação: SSO Token vs User Token

| Característica | SSO Token | User Access Token | User Refresh Token |
|---------------|-----------|-------------------|-------------------|
| **Propósito** | Autenticar em sistemas externos | Autenticar no Terminal | Renovar access token |
| **TTL padrão** | 60 segundos | 600 segundos (10 min) | 30 dias |
| **Claim `tier`** | ✅ Sim | ✅ Sim | ✅ Sim |
| **Claim `role`** | ❌ Não | ✅ Sim | ✅ Sim |
| **Claim `email`** | ✅ Sim | ❌ Não | ❌ Não |
| **Claim `jti`** | ✅ Sim | ❌ Não | ✅ Sim |
| **Armazenamento** | URL (query param) | Cookie httpOnly | Cookie httpOnly |

---

## 🛡️ Segurança

### Boas Práticas Implementadas:
- ✅ **HMAC-SHA256** para assinatura
- ✅ **TTL curto** (60s) para tokens SSO
- ✅ **JTI único** para prevenção de replay
- ✅ **Verificação de audience** para evitar uso indevido
- ✅ **Cookies httpOnly e Secure** para tokens internos
- ✅ **SameSite Strict** para proteção CSRF

### Recomendações para Sistemas Destino:
1. **Validar assinatura** com a chave compartilhada
2. **Verificar expiração** (`exp` > `now`)
3. **Verificar audience** (deve ser a URL do seu sistema)
4. **Verificar issuer** (deve ser `https://terminal.operebem.com.br`)
5. **Usar token apenas uma vez** (implementar blacklist de `jti`)

---

## 📁 Arquivos Relevantes

| Arquivo | Descrição |
|---------|-----------|
| `src/Controllers/SsoController.php` | Controller SSO (gera tokens e redireciona) |
| `src/Services/UserJwtService.php` | Serviço de emissão de tokens internos |
| `src/Controllers/AuthController.php` | Login/logout (emite tokens internos) |
| `routes/web.php` | Rotas SSO (linhas 92-94) |

---

## 🔗 Endpoints SSO

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/sso/start` | Iniciar SSO para Portal do Aluno |
| GET | `/sso/start?return=/courses` | SSO com redirecionamento após login |
| GET | `/sso/diario/start` | Iniciar SSO para Diário Operebem |

---

## 🔄 API de Subscription (Gerenciamento de Tiers)

Esta API permite que **sistemas externos** (Portal do Aluno, etc) **atualizem o tier** do usuário no Terminal.

### Configuração

```env
SUBSCRIPTION_API_KEY=sua_chave_secreta_aqui
```

### Endpoints

#### 1. Health Check (público)
```http
GET /api/subscription/ping
```

**Resposta:**
```json
{
  "success": true,
  "message": "Subscription API is running",
  "timestamp": "2025-12-29 08:00:00",
  "version": "1.0"
}
```

---

#### 2. Consultar Status da Assinatura
```http
GET /api/subscription/status?user_id=123
GET /api/subscription/status?email=user@example.com

Headers:
  X-API-KEY: sua_chave_secreta
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "user_id": 123,
    "email": "user@example.com",
    "tier": "PLUS",
    "is_active": true,
    "expires_at": "2025-12-31 23:59:59",
    "member_since": "2024-01-15 10:30:00"
  }
}
```

---

#### 3. Atualizar Tier do Usuário
```http
POST /api/subscription/update

Headers:
  X-API-KEY: sua_chave_secreta
  Content-Type: application/json

Body:
{
  "user_id": 123,
  "tier": "PLUS",
  "expires_at": "2025-12-31 23:59:59"
}
```

**Parâmetros do Body:**

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `user_id` | int | Sim* | ID do usuário |
| `email` | string | Sim* | Email do usuário (alternativa ao user_id) |
| `tier` | string | Sim | Novo tier: `FREE`, `PLUS` ou `PRO` |
| `expires_at` | string | Não | Data de expiração (YYYY-MM-DD ou YYYY-MM-DD HH:MM:SS) |

*Pelo menos um dos dois (`user_id` ou `email`) é obrigatório.

**Resposta de Sucesso:**
```json
{
  "success": true,
  "message": "Tier updated from FREE to PLUS",
  "data": {
    "user_id": 123,
    "email": "user@example.com",
    "old_tier": "FREE",
    "new_tier": "PLUS",
    "expires_at": "2025-12-31 23:59:59"
  }
}
```

**Erros Possíveis:**

| Código | Erro | Descrição |
|--------|------|-----------|
| 401 | Unauthorized | API Key inválida ou ausente |
| 400 | Bad Request | JSON inválido ou parâmetros faltando |
| 404 | Not Found | Usuário não encontrado |
| 500 | Internal Server Error | Erro ao atualizar no banco |

---

### Exemplos de Uso

#### cURL - Atualizar para PLUS
```bash
curl -X POST https://terminal.operebem.com.br/api/subscription/update \
  -H "X-API-KEY: sua_chave_secreta" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "usuario@exemplo.com",
    "tier": "PLUS",
    "expires_at": "2025-12-31"
  }'
```

#### cURL - Downgrade para FREE
```bash
curl -X POST https://terminal.operebem.com.br/api/subscription/update \
  -H "X-API-KEY: sua_chave_secreta" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 123,
    "tier": "FREE"
  }'
```

#### PHP - Exemplo de Integração
```php
<?php
$data = [
    'user_id' => 123,
    'tier' => 'PRO',
    'expires_at' => '2025-12-31 23:59:59'
];

$ch = curl_init('https://terminal.operebem.com.br/api/subscription/update');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'X-API-KEY: sua_chave_secreta',
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);
$result = json_decode($response, true);

if ($result['success']) {
    echo "Tier atualizado com sucesso!";
}
```

---

## 📁 Arquivos Relevantes

| Arquivo | Descrição |
|---------|-----------|
| `src/Controllers/SsoController.php` | Controller SSO (gera tokens e redireciona) |
| `src/Controllers/Api/SubscriptionApiController.php` | API de gerenciamento de tiers |
| `src/Services/UserJwtService.php` | Serviço de emissão de tokens internos |
| `src/Controllers/AuthController.php` | Login/logout (emite tokens internos) |
| `routes/web.php` | Rotas SSO e Subscription API |

---

*Documentação atualizada em: 2025-12-29*
*Versão: 1.1*
