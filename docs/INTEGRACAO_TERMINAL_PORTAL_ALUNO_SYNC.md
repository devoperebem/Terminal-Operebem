# Integracao Terminal -> Portal do Aluno (Sync)

- Versao do documento: `v1.0.0`
- Ultima atualizacao: `2026-03-05`
- Responsavel: equipe Terminal

## Objetivo

Definir como o Terminal sincroniza `pricing` e `materials` com o Portal do Aluno usando assinatura HMAC-SHA256.

## Variaveis de ambiente

- `PORTAL_ALUNO_BASE_URL` (default: `https://aluno.operebem.com.br`)
- `TERMINAL_SYNC_SECRET` (obrigatorio)

## Assinatura

1. Serializar payload JSON com `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`.
2. Calcular assinatura: `hash_hmac('sha256', body, TERMINAL_SYNC_SECRET)`.
3. Enviar header `X-Sync-Signature`.

## Endpoints de destino

- `POST /api/terminal/sync-pricing`
- `POST /api/terminal/sync-materials`

## Implementacao no Terminal (status)

- `src/Services/PortalSyncService.php`
  - status: implementado
  - cobre POST JSON, assinatura HMAC, timeout e retorno padronizado
- `src/Services/PortalPricingConfigService.php`
  - status: implementado
  - persiste planos em `.config/portal_pricing_plans.json`
- `src/Controllers/AdminAlunoPricingController.php`
  - status: implementado (fase inicial)
  - CRUD local de planos e sync automatico/manual

## Rotas admin adicionadas

- `GET /secure/adm/aluno/pricing`
- `GET /secure/adm/aluno/pricing/create`
- `GET /secure/adm/aluno/pricing/edit?id=<id>`
- `POST /secure/adm/aluno/pricing/store`
- `POST /secure/adm/aluno/pricing/update`
- `POST /secure/adm/aluno/pricing/delete`
- `POST /secure/adm/aluno/pricing/sync`

## Estrutura local de pricing

Arquivo de configuracao: `.config/portal_pricing_plans.json`

Campos de cada plano:

- `id`
- `name`
- `slug`
- `price_display`
- `price_subtitle`
- `description`
- `features[]`
- `cta_label`
- `cta_url`
- `is_highlighted`
- `badge_text`
- `position`
- `created_at`
- `updated_at`

## Erros esperados

- `401` assinatura ausente
- `403` assinatura invalida
- `422` payload invalido
- `429` rate limit
- `500` segredo ausente no Portal ou erro de persistencia no Portal

## Historico de versao

- `v1.0.0` (2026-03-05): base de sync criada, admin de pricing iniciado e documentacao consolidada.
