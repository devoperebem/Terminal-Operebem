# Tasks Ativas

- `TASK-001` - Documentar, versionar e iniciar implementacao da integracao de sync do Portal (pricing/materials).

## TASK-001

- Status: em andamento.
- Escopo aprovado: criar base documental/versionada e implementar primeira camada tecnica (servico de sync + inicio de pricing admin).
- Progresso concluido nesta iteracao:
  - estrutura `.agent/` regularizada;
  - documento versionado de integracao criado em `docs/INTEGRACAO_TERMINAL_PORTAL_ALUNO_SYNC.md`;
  - `PortalSyncService` implementado com HMAC e envio JSON;
  - modulo admin de pricing implementado (CRUD + sync automatico/manual);
  - modulo admin de materiais implementado (CRUD + upload local + sync por curso);
  - trilha de auditoria de sync implementada em `.config/portal_sync_audit.json`.
  - upload Bunny Storage implementado para materiais com fallback local.
  - auditoria de sync exposta em tela admin com check de conectividade e retries manuais.
  - upload de materiais ajustado para priorizar Hostinger media origin via `MEDIA_ORIGIN_*`.
  - suporte a URL assinada para materiais restritos no sync e preview admin quando `storage_path` estiver disponivel.
- Mudanca de direcao aprovada: media delivery em site dedicado Hostinger com controle PHP/.htaccess.
- Proximos passos objetivos:
  - validar endpoint real de upload/download no site de media Hostinger com 2 arquivos de tipos distintos;
  - validar fluxo integrado Terminal -> Portal para pricing/materials em ambiente real com evidencias HTTP;
  - validar cenarios de assinatura em producao (token valido, invalido e expirado).

## Bloqueio atual

- Variaveis `MEDIA_ORIGIN_*` ainda nao configuradas no ambiente em execucao; sem isso, upload Hostinger e assinatura ficam em fallback e nao ha como concluir evidencias reais desta etapa.
