# Estado Atual

- Data de referencia: 2026-03-05.
- Projeto: Terminal Operebem (PHP 8.1+, arquitetura MVC propria, rotas em `routes/web.php`).
- Integracao atual com Portal do Aluno: SSO ativo (`/sso/start`) e painel admin de cursos/aulas/acessos.
- Integracao de pricing: fase inicial implementada (CRUD local + sync HMAC para Portal).
- Integracao de materiais: fase inicial implementada (CRUD local + upload local + sync por curso).
- Upload para Bunny Storage: implementado com fallback local quando Bunny nao estiver configurado.
- Auditoria admin de sync: implementada com check e retries manuais.
- `TERMINAL_SYNC_SECRET` gerado e configurado no `.env` local (64 bits hex).
- Credenciais Bunny Stream atualizadas no `.env` com `SIGNING_KEY` informado e `TOKEN_TTL=600`.
- Config basica Bunny Storage adicionada no `.env`; faltam `BUNNY_STORAGE_ZONE` e `BUNNY_STORAGE_ACCESS_KEY` para upload CDN real.
- Nova direcao aprovada: mover media delivery para site dedicado na Hostinger com controle PHP/.htaccess.
- Solicitacao vigente do usuario: documentar, versionar e iniciar implementacao da integracao Terminal -> Portal para pricing/materials.
- Fase 1 de media origin iniciada no Terminal: upload de materiais agora prioriza Hostinger (`MEDIA_ORIGIN_*`) com fallback Bunny/local.
- Materiais restritos no sync agora tentam URL assinada com expiracao (HMAC `path|exp`) quando configuracao de assinatura estiver ativa.

## Pendencias imediatas

- Refatorar upload de materiais para origin dedicado Hostinger (despriorizando Bunny nesta fase).
- Trilha de auditoria operacional implementada em arquivo local e exposta no admin (`/secure/adm/aluno/sync-audit`).
- Executar validacao funcional ponta a ponta com Portal em ambiente integrado (pricing + materials).
- Fechar validacao ponta a ponta de upload no Hostinger com 2 arquivos reais e evidencia no Portal.
- Confirmar comportamento no origin de media para token valido/invalido/expirado (200/403/410).

## Falha tecnica observada nesta iteracao

- O que falhou: check automatizado ao endpoint publico do Portal (`/api/terminal/pricing`) via CLI.
- Causa provavel: cadeia de certificado SSL ausente/invalida no ambiente local da CLI (`SSL certificate problem: unable to get local issuer certificate`).
- Evidencia: execucao `php -r ... PortalSyncService::checkPricingPublicEndpoint()` retornou `status=0` com erro de SSL.
- Impacto: validacao integrada real por HTTP ficou bloqueada neste ambiente, apesar de implementacao de codigo concluida.
- Plano de correcao: ajustar cadeia CA/openssl no ambiente de execucao ou configurar bundle CA valido, e repetir check/sync real.

## Falha tecnica observada nesta iteracao

- O que falhou: validacao integrada real em VPS/Portal nao executada nesta iteracao.
- Causa provavel: variaveis `MEDIA_ORIGIN_*` ainda vazias no ambiente atual, impedindo upload remoto e assinatura efetiva.
- Evidencia: check via CLI retornou `MEDIA_ORIGIN_BASE_URL:empty`, `MEDIA_ORIGIN_UPLOAD_TOKEN:empty`, `MEDIA_ORIGIN_DOWNLOAD_BASE_URL:empty`, `MEDIA_ORIGIN_SIGNING_KEY:empty`.
- Impacto: `TASK-001` segue em andamento, sem aceite final de ponta a ponta.
- Plano de correcao: executar ciclo de deploy + validacao real no servidor, registrar evidencias HTTP e concluir task.
