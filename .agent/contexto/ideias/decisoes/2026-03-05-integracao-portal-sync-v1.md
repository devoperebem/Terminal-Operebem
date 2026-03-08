# Decisao: Integracao Portal Sync v1

- Data: 2026-03-05
- Contexto: solicitacao de documentacao completa + inicio de implementacao de pricing/materials sync.
- Decisao: implementar servico de sync assinado por HMAC e acoplar ao admin do Terminal em etapas.
- Motivo: reduzir duplicacao de logica de assinatura, aumentar previsibilidade operacional e facilitar evolucao.
- Impacto: novos endpoints internos no admin, novas tabelas locais para gestao de pricing/materials e logs de sync.
- Confirmacao do usuario: pedido explicito para documentar, versionar e comecar implementacao.
