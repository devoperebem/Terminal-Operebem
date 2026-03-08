# Integracao Terminal -> Portal do Aluno (Sync)

- Versao do documento: `v1.5.0`
- Ultima atualizacao: `2026-03-05`
- Responsavel: equipe Terminal

## Objetivo

Definir como o Terminal sincroniza `pricing` e `materials` com o Portal do Aluno usando assinatura HMAC-SHA256.

## Variaveis de ambiente

- `PORTAL_ALUNO_BASE_URL` (default: `https://aluno.operebem.com.br`)
- `TERMINAL_SYNC_SECRET` (obrigatorio)
- `BUNNY_STORAGE_ZONE`
- `BUNNY_STORAGE_ACCESS_KEY`
- `BUNNY_STORAGE_REGION` (opcional)
- `BUNNY_STORAGE_PUBLIC_BASE_URL` (URL publica da CDN/Pull Zone)
- `MEDIA_ORIGIN_BASE_URL` (dominio/API do origin dedicado Hostinger)
- `MEDIA_ORIGIN_UPLOAD_PATH` (default: `/api/upload.php`)
- `MEDIA_ORIGIN_UPLOAD_TOKEN` (obrigatorio para upload remoto)
- `MEDIA_ORIGIN_DOWNLOAD_BASE_URL` (endpoint de download assinado)
- `MEDIA_ORIGIN_SIGNING_KEY` (chave HMAC para links restritos)
- `MEDIA_ORIGIN_SIGNING_TTL` (default: `600` segundos)
- `MEDIA_ORIGIN_TIMEOUT` (default: `30` segundos)

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
- `src/Services/PortalMaterialsConfigService.php`
  - status: implementado
  - persiste materiais em `.config/portal_materials.json`
- `src/Controllers/AdminAlunoMaterialsController.php`
  - status: implementado (fase inicial)
  - CRUD local de materiais, upload com prioridade Hostinger media origin (fallback Bunny/local) e sync por curso
- `src/Services/MediaOriginService.php`
  - status: implementado
  - upload para origin dedicado Hostinger e geracao de URL assinada por exp/HMAC
- `src/Services/PortalSyncAuditService.php`
  - status: implementado
  - registra trilha de sincronizacao em `.config/portal_sync_audit.json`
- `src/Services/BunnyStorageService.php`
  - status: implementado
  - envia arquivos para Bunny Storage com `PUT` e `AccessKey`
- `src/Controllers/AdminAlunoSyncAuditController.php`
  - status: implementado
  - tela de auditoria, check do Portal e retries manuais

## Rotas admin adicionadas

- `GET /secure/adm/aluno/pricing`
- `GET /secure/adm/aluno/pricing/create`
- `GET /secure/adm/aluno/pricing/edit?id=<id>`
- `POST /secure/adm/aluno/pricing/store`
- `POST /secure/adm/aluno/pricing/update`
- `POST /secure/adm/aluno/pricing/delete`
- `POST /secure/adm/aluno/pricing/sync`
- `GET /secure/adm/aluno/materials`
- `GET /secure/adm/aluno/materials/create`
- `GET /secure/adm/aluno/materials/edit?id=<id>`
- `POST /secure/adm/aluno/materials/store`
- `POST /secure/adm/aluno/materials/update`
- `POST /secure/adm/aluno/materials/delete`
- `POST /secure/adm/aluno/materials/sync-course`
- `GET /secure/adm/aluno/sync-audit`
- `POST /secure/adm/aluno/sync-audit/check`
- `POST /secure/adm/aluno/sync-audit/retry-pricing`
- `POST /secure/adm/aluno/sync-audit/retry-materials`

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

## Estrutura local de materiais

Arquivo de configuracao: `.config/portal_materials.json`

Campos de cada material:

- `id`
- `course_id`
- `lesson_id` (`null` para material de curso)
- `title`
- `description`
- `file_url`
- `file_type`
- `file_size`
- `is_free`
- `created_at`
- `updated_at`

## Upload de materiais (fase atual)

- Upload via admin prioriza origin dedicado Hostinger quando configurado.
- Sem Hostinger configurado, fluxo usa Bunny Storage (secundario) e local como fallback final.
- URL final preferencialmente absoluta com base em `APP_URL`.
- Extensoes aceitas: `pdf`, `xlsx`, `xls`, `ppt`, `pptx`, `doc`, `docx`, `zip`, `csv`, `txt`.
- Limite de tamanho por arquivo: `50 MB`.

## Materiais restritos (URL assinada)

- Para materiais com `is_free=false`, o sync gera URL assinada quando `storage_path` e chaves de assinatura estao configurados.
- Formato atual de assinatura: `?path=<storage_path>&exp=<unix_ts>&sig=<hmac_sha256(path|exp)>`.
- Se configuracao de assinatura estiver ausente, o sistema usa `file_url` persistida como fallback operacional.

## Direcao de custo (atual)

- Direcao aprovada: migrar media delivery para site dedicado Hostinger com controle PHP/.htaccess.
- Bunny Storage deixa de ser storage principal nesta fase.
- Documento arquitetural detalhado: `docs/ARQUITETURA_MEDIA_DELIVERY_HOSTINGER.md`.

## Auditoria operacional de sync

- Arquivo de auditoria: `.config/portal_sync_audit.json`
- Campos registrados por tentativa: timestamp, endpoint, status, sucesso, hash do payload, erro e amostra da resposta.
- Segredo e payload bruto nao sao persistidos no log de auditoria.
- Painel admin para auditoria e retry: `/secure/adm/aluno/sync-audit`.

## Erros esperados

- `401` assinatura ausente
- `403` assinatura invalida
- `422` payload invalido
- `429` rate limit
- `500` segredo ausente no Portal ou erro de persistencia no Portal

## Troubleshooting

- Erro de TLS na CLI (`SSL certificate problem: unable to get local issuer certificate`):
  - atualizar/instalar cadeia CA do ambiente PHP/cURL;
  - validar `openssl.cafile`/`curl.cainfo` apontando para bundle CA valido;
  - repetir check em `/secure/adm/aluno/sync-audit` ou via CLI apos ajuste.

## Historico de versao

- `v1.5.0` (2026-03-05): prioridade de upload para Hostinger media origin e URL assinada para materiais restritos.
- `v1.4.0` (2026-03-05): painel admin de auditoria de sync e retries manuais implementado.
- `v1.3.0` (2026-03-05): upload de materiais com Bunny Storage implementado (com fallback local).
- `v1.2.0` (2026-03-05): trilha de auditoria de sync implementada em arquivo configuravel local.
- `v1.1.0` (2026-03-05): modulo inicial de materiais implementado (CRUD + upload local + sync por curso).
- `v1.0.0` (2026-03-05): base de sync criada, admin de pricing iniciado e documentacao consolidada.
