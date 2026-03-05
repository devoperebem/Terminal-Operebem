# Tasks Ativas

- `TASK-001` - Documentar, versionar e iniciar implementacao da integracao de sync do Portal (pricing/materials).

## TASK-001

- Status: em andamento.
- Escopo aprovado: criar base documental/versionada e implementar primeira camada tecnica (servico de sync + inicio de pricing admin).
- Progresso concluido nesta iteracao:
  - estrutura `.agent/` regularizada;
  - documento versionado de integracao criado em `docs/INTEGRACAO_TERMINAL_PORTAL_ALUNO_SYNC.md`;
  - `PortalSyncService` implementado com HMAC e envio JSON;
  - modulo admin de pricing implementado (CRUD + sync automatico/manual).
- Proximos passos objetivos:
  - implementar modulo de materiais (incluindo upload e metadados);
  - adicionar trilha de auditoria detalhada de sync;
  - validar fluxo integrado Terminal -> Portal para pricing/materials.
