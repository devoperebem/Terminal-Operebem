# Handoff Dev - TASK-001 (Sync Portal + Media Hostinger)

- Data: 2026-03-05
- Escopo: integracao Terminal -> Portal (pricing + materials) com direcao de media no Hostinger.

## O que foi feito

- Pricing admin e sync HMAC ativos (`/secure/adm/aluno/pricing`).
- Materials admin implementado (`/secure/adm/aluno/materials`) com CRUD, upload e sync por curso.
- Auditoria operacional implementada (`/secure/adm/aluno/sync-audit`) com check e retries.
- Upload de materiais ajustado para priorizar Hostinger media origin quando `MEDIA_ORIGIN_*` estiver configurado.
- Fallback mantido: Hostinger -> Bunny -> local.
- Material restrito (`is_free=false`) com suporte a URL assinada por exp/HMAC no sync.
- Documentacao atualizada em `docs/INTEGRACAO_TERMINAL_PORTAL_ALUNO_SYNC.md` e `docs/ARQUITETURA_MEDIA_DELIVERY_HOSTINGER.md`.

## O que NAO foi concluido ainda

- Validacao ponta a ponta real no ambiente integrado (evidencias HTTP de pricing/materials).
- Validacao real de upload Hostinger com 2 arquivos de tipos diferentes.
- Validacao dos cenarios de token assinado no origin:
  - token valido (`200`)
  - token invalido (`403`)
  - token expirado (`410` ou equivalente definido no endpoint)

## O que o dev do usuario precisa fazer agora

1. Configurar variaveis no `.env` do ambiente alvo:
   - `MEDIA_ORIGIN_BASE_URL`
   - `MEDIA_ORIGIN_UPLOAD_PATH` (default recomendado: `/api/upload.php`)
   - `MEDIA_ORIGIN_UPLOAD_TOKEN`
   - `MEDIA_ORIGIN_DOWNLOAD_BASE_URL` (ex.: endpoint `download.php`)
   - `MEDIA_ORIGIN_SIGNING_KEY`
   - `MEDIA_ORIGIN_SIGNING_TTL` (ex.: `600`)
   - `MEDIA_ORIGIN_TIMEOUT` (ex.: `30`)
2. Garantir que o endpoint de upload no Hostinger aceite `multipart/form-data` com bearer token e retorne JSON com `file_url` e `storage_path`.
3. Garantir que o endpoint de download assinado valide `path`, `exp` e `sig` via HMAC SHA-256 (`path|exp`).
4. Executar no admin:
   - retry/check em `/secure/adm/aluno/sync-audit`
   - upload de 2 materiais (tipos diferentes) em `/secure/adm/aluno/materials`
5. Coletar evidencias de aceite:
   - 1 sync pricing com sucesso
   - 1 sync materials com sucesso
   - 1 falha controlada auditada
   - testes 200/403/410 para material restrito

## Arquivos-chave alterados

- `src/Services/MediaOriginService.php`
- `src/Controllers/AdminAlunoMaterialsController.php`
- `src/Services/PortalMaterialsConfigService.php`
- `src/Services/PortalSyncService.php`
- `src/Services/PortalSyncAuditService.php`
- `src/Services/BunnyStorageService.php`
- `src/Controllers/AdminAlunoSyncAuditController.php`
- `src/Views/admin_secure/aluno_materials_index.php`
- `src/Views/admin_secure/aluno_materials_form.php`
- `src/Views/admin_secure/aluno_sync_audit.php`
- `routes/web.php`
- `.env.example`

## Observacao operacional

- Se o ambiente local continuar bloqueando por SSL CA no check do Portal, executar validacao no servidor (VPS) e registrar evidencias no historico da task.
