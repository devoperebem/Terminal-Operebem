# Estado Atual

- Data de referencia: 2026-03-05.
- Projeto: Terminal Operebem (PHP 8.1+, arquitetura MVC propria, rotas em `routes/web.php`).
- Integracao atual com Portal do Aluno: SSO ativo (`/sso/start`) e painel admin de cursos/aulas/acessos.
- Integracao de pricing: fase inicial implementada (CRUD local + sync HMAC para Portal).
- Lacuna principal atual: sync de materiais ainda pendente de implementacao.
- Solicitacao vigente do usuario: documentar, versionar e iniciar implementacao da integracao Terminal -> Portal para pricing/materials.

## Pendencias imediatas

- Concluir implementacao de materiais (CRUD + upload CDN + sync `sync-materials`).
- Adicionar trilha de auditoria operacional de sync (historico por tentativa com status).
- Executar validacao funcional ponta a ponta com Portal em ambiente integrado.
