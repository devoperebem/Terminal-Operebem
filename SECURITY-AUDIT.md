# Auditoria de Segurança — Admin Dashboard & Integrações

**Data:** 2026-03-06  
**Escopo:** Terminal Operebem (admin) + Portal do Aluno (integração pricing)  
**Autor:** Cascade AI  

---

## 1. Fluxo de Autenticação Admin

### 1.1 PIN de Segurança (NOVO)
| Item | Status |
|------|--------|
| PIN de 6 dígitos via `ADMIN_PIN` env var | ✅ Implementado |
| Comparação timing-safe (`hash_equals`) | ✅ |
| Rate limit: 5 falhas / 15 min por IP | ✅ |
| Sessão com expiração de 30 min | ✅ |
| Regeneração de session ID após PIN correto | ✅ |
| Logging de tentativas (sucesso/falha) | ✅ |
| Bypass automático se `ADMIN_PIN` não configurado | ✅ (graceful degradation) |

### 1.2 Login Admin
| Item | Status |
|------|--------|
| CSRF token obrigatório | ✅ |
| Honeypot anti-bot | ✅ |
| Captcha customizado (OpereBemCaptcha) | ✅ |
| reCAPTCHA v3 (opcional, se configurado) | ✅ |
| Rate limit: 5 falhas / 15 min por username | ✅ |
| Password hashing: Argon2ID | ✅ |
| Logging de todas as tentativas | ✅ |

### 1.3 2FA (Segundo Fator)
| Item | Status |
|------|--------|
| Código de 6 dígitos por email | ✅ |
| Expiração: 5 minutos | ✅ |
| Rate limit: 10 falhas / 10 min | ✅ |
| JWT access token (12h TTL) após 2FA | ✅ |
| JWT refresh token (30d TTL) | ✅ |
| Token em cookie HttpOnly + Secure + SameSite | ✅ |

### 1.4 Middleware de Proteção
| Item | Status |
|------|--------|
| `SecureAdminMiddleware` valida JWT em todas as rotas admin | ✅ |
| `CsrfMiddleware` em todos os POSTs | ✅ |
| `SameOriginAjaxMiddleware` nas APIs internas | ✅ |

---

## 2. Segurança das APIs

### 2.1 API Pública: `GET /api/plans/front-display`
| Item | Status | Nota |
|------|--------|------|
| Read-only (GET) | ✅ | Sem modificação de dados |
| Sem autenticação (público) | ✅ | Dados de pricing são públicos |
| Cache-Control: 5 min | ✅ | Reduz carga no DB |
| Input validation (system_key whitelist) | ✅ | Apenas `terminal`, `portal_aluno`, `diario_trades` |
| SQL injection prevention (prepared statements) | ✅ | |
| Error handling sem leak de stack trace | ✅ | |

### 2.2 API Sync (Portal do Aluno)
| Item | Status |
|------|--------|
| HMAC-SHA256 signature obrigatória | ✅ |
| Rate limit: 30 req/min | ✅ |
| Input validation | ✅ |
| Transação atômica (rollback em falha) | ✅ |

### 2.3 API Admin (saveFrontDisplay)
| Item | Status |
|------|--------|
| SecureAdminMiddleware | ✅ |
| CSRF validation | ✅ |
| Input sanitization | ✅ |
| SQL injection prevention (prepared statements) | ✅ |
| Logging de ações | ✅ |
| Upsert com ON CONFLICT (idempotente) | ✅ |

---

## 3. Proteção de Dados

| Item | Status |
|------|--------|
| Senhas: Argon2ID | ✅ |
| CSRF tokens: 32 bytes random | ✅ |
| JWT secrets: 128+ chars | ✅ |
| Session regeneration em login/PIN | ✅ |
| HttpOnly cookies para JWT | ✅ |
| XSS prevention: `htmlspecialchars()` em todas as views | ✅ |
| SQL injection: prepared statements em 100% das queries | ✅ |

---

## 4. Rate Limiting Resumo

| Endpoint | Limite | Janela |
|----------|--------|--------|
| PIN gate | 5 falhas | 15 min |
| Admin login | 5 falhas | 15 min |
| Admin 2FA | 10 falhas | 10 min |
| Sync pricing (Portal) | 30 req | 1 min |
| Sync materials (Portal) | 30 req | 1 min |

---

## 5. Pontos de Atenção

### 5.1 Recomendações para Produção
1. **Altere o `ADMIN_PIN`** para um valor diferente do `ADMIN_IMPORT_SECRET`
2. **Não exponha `.env`** — verifique que o webserver bloqueia acesso a arquivos dotfiles
3. **Monitore logs** em `storage/logs/admin/` para tentativas de acesso suspeitas
4. **Considere IP allowlist** para o admin se o acesso for sempre do mesmo IP
5. **HTTPS obrigatório** — todas as URLs devem usar HTTPS em produção

### 5.2 Hardcoded Credentials (ALERTA)
- `AdminAuthService.php` line 30: senha admin hardcoded (`admin1016@`)
- **Recomendação:** Mover para variável de ambiente (`ADMIN_BOOTSTRAP_PASS`) ou remover após seed inicial

### 5.3 Headers de Segurança
Verifique que o webserver envia:
```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Strict-Transport-Security: max-age=31536000; includeSubDomains
Content-Security-Policy: default-src 'self'; ...
Referrer-Policy: strict-origin-when-cross-origin
```

---

## 6. Arquivos Modificados (Esta Sprint)

### Terminal Operebem
| Arquivo | Alteração |
|---------|-----------|
| `src/Controllers/AdminSecureController.php` | PIN gate (pinForm, verifyPin, isPinVerified) |
| `src/Controllers/Admin/SubscriptionPlansAdminController.php` | saveFrontDisplay, load frontDisplay in edit |
| `src/Controllers/Api/PlanFrontDisplayApiController.php` | **NOVO** — API pública front-display |
| `src/Views/admin_secure/pin.php` | **NOVO** — Tela de PIN |
| `src/Views/admin_secure/login.php` | Fix: ícones nos inputs |
| `src/Views/admin_secure/index.php` | Redesign completo dashboard |
| `src/Views/admin_secure/aluno_portal.php` | Remoção seção Pricing |
| `src/Views/admin_secure/subscription_plans/edit.php` | Seção "Exibição no Front" |
| `src/Views/layouts/app.php` | Remoção Suporte/Privacidade do footer admin |
| `public/assets/css/theme-dark.css` | Fix card headers, table-light, modals |
| `public/assets/css/theme-light.css` | Fix card headers, table styling |
| `routes/web.php` | Rotas PIN gate + front-display API |
| `database/migrations/027_create_plan_front_display_table.sql` | **NOVO** |
| `.env` | `ADMIN_PIN` adicionado |

### Portal do Aluno
| Arquivo | Alteração |
|---------|-----------|
| `src/Controllers/TerminalSyncController.php` | getPlans() agora consulta API do Terminal primeiro |
| `.env` | `TERMINAL_BASE_URL` adicionado |
| `.env.example` | `TERMINAL_BASE_URL` adicionado |

---

## 7. Conclusão

O sistema apresenta uma postura de segurança **sólida** para uma aplicação admin:
- Autenticação em 3 camadas (PIN → Login+Captcha → 2FA)
- Rate limiting em todos os pontos de entrada
- CSRF em todos os POSTs
- JWT com TTL adequado
- Prepared statements universais

**Risco residual principal:** senha admin hardcoded no seed. Recomenda-se migrar para env var.
