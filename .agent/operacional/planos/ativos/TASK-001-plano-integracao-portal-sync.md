# Plano Ativo - TASK-001

## Objetivo

Documentar, versionar e iniciar implementacao da integracao Terminal -> Portal para sincronizacao de pricing e materiais.

## Entregas desta task

1. [ok] Estrutura de contexto `.agent/` regularizada e atualizada.
2. [ok] Documento tecnico versionado de integracao no repositorio (`docs/`).
3. [ok] Implementacao inicial do `PortalSyncService` (assinatura HMAC + POST JSON).
4. [ok] Inicio da implementacao de pricing no admin (modelo/fluxo minimo para sync).
5. [ok] Validacao tecnica local de sintaxe/rotas alteradas.

## Criticos de validacao

- Assinatura enviada em `X-Sync-Signature` usando `TERMINAL_SYNC_SECRET`.
- Payload serializado sem escaping desnecessario de unicode/slashes.
- Falhas de HTTP registradas com contexto suficiente para operacao.

## Fora de escopo nesta task

- Entrega final completa de materiais com upload/CDN.
- Testes E2E em producao do Portal.

## Restante para concluir TASK-001

- Implementar primeiro corte funcional de materiais com payload em `sync-materials`.
- Executar teste integrado contra Portal com evidencias de resposta HTTP.
