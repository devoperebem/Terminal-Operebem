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

## � Arquivos Relevantes

| Arquivo | Descrição |
|---------|-----------|
| `src/Controllers/SsoController.php` | Controller SSO (gera tokens e redireciona) |
| `src/Services/UserJwtService.php` | Serviço de emissão de tokens internos |
| `src/Controllers/AuthController.php` | Login/logout (emite tokens internos) |
| `routes/web.php` | Rotas SSO |

---

## 🛠️ Gerenciamento de Tiers

O gerenciamento de tiers (FREE, PLUS, PRO) é feito **exclusivamente pelo painel administrativo** do Terminal em:

```
/secure/adm/users/edit?id=<user_id>
```

Os tiers são propagados automaticamente via SSO para todos os sistemas conectados.

---

*Documentação atualizada em: 2025-12-29*
*Versão: 1.2*
