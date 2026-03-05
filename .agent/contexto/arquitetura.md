# Arquitetura (Task Atual)

## Direcao tecnica

- Introduzir `PortalSyncService` para centralizar HTTP + assinatura HMAC.
- Criar mapeadores de payload para `sync-pricing` e `sync-materials`.
- Integrar chamadas de sync a fluxos admin de persistencia local.
- Preservar separacao: controllers orchestram, services implementam regras e IO.

## Contratos externos

- `POST /api/terminal/sync-pricing`
- `POST /api/terminal/sync-materials`
- Header obrigatorio: `X-Sync-Signature`

## Seguranca

- Segredo em `.env`: `TERMINAL_SYNC_SECRET`.
- Assinatura: `hash_hmac('sha256', body, secret)`.
