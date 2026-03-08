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

- [ok] Implementar primeiro corte funcional de materiais com payload em `sync-materials`.
- Executar teste integrado contra Portal com evidencias de resposta HTTP.

## Execucao atual (2026-03-05 - iteracao 2)

- [ok] Modulo de materiais no admin implementado, com persistencia local, upload e sync por curso.
- [ok] Documentacao ampliada para versao `v1.3.0`.
- [ok] Trilha de auditoria operacional de sync implementada em arquivo local.
- [ok] Upload Bunny Storage implementado para materiais, com fallback local.
- [ok] Exposicao da auditoria de sync em tela admin.
- Pendente: validacao integrada com endpoint real do Portal (bloqueada por SSL local na CLI atual).

## Direcao atualizada

- Storage principal de materiais deixa de priorizar Bunny nesta fase.
- Implementacao alvo passa a ser origin dedicado Hostinger + controle PHP/.htaccess.
- Bunny fica como opcao secundaria/futura.

## Plano de finalizacao para entrega

### Etapa 1 - Validacao integrada de sync (pricing + materials)

- Rodar check/sync no painel de auditoria (`/secure/adm/aluno/sync-audit`) com evidencias de status HTTP.
- Se a CLI local seguir com erro de CA, validar diretamente em VPS/ambiente servidor conforme guia operacional de deploy.
- Evidencias minimas: 1 sync de pricing com sucesso, 1 sync de materials com sucesso, 1 tentativa de falha controlada auditada.

### Etapa 2 - Fechar media origin na Hostinger (fase 1 arquitetural)

- Publicar endpoint autenticado de upload no site de media (Hostinger) e retorno padrao de `file_url` + metadados.
- [ok] Ajustar Terminal para usar Hostinger como destino principal de upload nesta fase.
- Validar upload real de 2 arquivos (tipos distintos) e consumo no Portal.

### Etapa 3 - Seguranca de materiais restritos (fase 2)

- [ok] Implementar desenho de URL assinada com expiracao curta para conteudo restrito.
- Confirmar comportamento: token valido (`200`), token invalido (`403`), token expirado (`410` ou equivalente definido).
- Registrar no runbook quais materiais sao publicos e quais exigem assinatura.

### Etapa 4 - Go-live controlado e aceite

- Publicar via fluxo padrao de Git + deploy no servidor, com verificacao de interface no dominio de producao.
- Rodar checklist de regressao minimo no admin (pricing, materials, auditoria, retries).
- Concluir `TASK-001` somente com evidencias anexadas no historico e criterios de aceite marcados.

## Bloqueio operacional atual

- Variaveis `MEDIA_ORIGIN_*` estao vazias no ambiente atual; falta configuracao para validar upload Hostinger e links assinados em fluxo real.

## Criterios de aceite de fechamento

- Sync Terminal -> Portal validado em ambiente real com evidencias.
- Upload de materiais operando com Hostinger como destino principal.
- Materiais restritos protegidos por assinatura com expiracao.
- Trilhas de auditoria e runbook atualizados para operacao.
