# Arquitetura de Media Delivery - Hostinger + PHP

- Versao: `v1.1.0`
- Data: `2026-03-05`
- Status: implementacao fase 1 iniciada no Terminal (upload priorizando Hostinger)

## Contexto

O custo projetado para manter materiais em Bunny Storage foi considerado alto para o momento. A direcao escolhida e isolar o delivery de arquivos em um site dedicado na Hostinger, preservando a VPS principal para servicos criticos.

## Decisao arquitetural

- Nao construir CDN propria completa.
- Criar um site dedicado para materiais (origin) em hosting separado.
- Usar PHP + `.htaccess` para controle de acesso quando necessario.
- Usar cache de borda (Cloudflare) para reduzir latencia e banda no origin.

## Topologia alvo

1. Terminal (origem de gestao): admin cria/edita material e aciona sync.
2. Site de media (Hostinger): recebe upload e serve arquivos.
3. Portal do Aluno: consome `file_url` apontando para o dominio de media.
4. Cloudflare (opcional, recomendado): cache para conteudo publico.

## Estrategia de acesso a arquivos

### Conteudo publico

- URL direta estavel.
- Cache agressivo por extensao (`pdf`, `xlsx`, `zip`, etc.).

### Conteudo restrito

- Endpoint PHP de entrega (`download.php`) com token assinado e expiracao curta.
- Validacao de permissao antes de liberar arquivo.
- Evitar URL permanente para conteudo pago.

## Regras tecnicas obrigatorias

- Isolar armazenamento de materiais fora da raiz publica quando possivel.
- Sanitizar nome de arquivo e bloquear extensoes nao permitidas.
- Limitar tamanho de upload e taxa por IP/usuario.
- Gerar log de auditoria para upload/download negado.
- Backup diario com politica de retencao.

## Estrutura sugerida (site de media)

- `public_html/download.php` (controlado)
- `storage/materials/` (origem de arquivos)
- `storage/tmp/` (temporarios)
- `logs/media_access.log`

## Fluxo recomendado de upload

1. Terminal envia arquivo para endpoint autenticado no site de media.
2. Site de media valida metadados e persiste arquivo.
3. Site de media retorna URL final (`file_url`) e metadados.
4. Terminal salva material e sincroniza com Portal (`sync-materials`).

## Fluxo recomendado de download restrito

1. Usuario acessa material no Portal.
2. Portal/Terminal gera link temporario assinado.
3. Site de media valida assinatura, exp e escopo.
4. Arquivo e servido (200) ou negado (403/410).

## Operacao e observabilidade

- Expor metricas basicas: uploads por dia, downloads por dia, 4xx/5xx.
- Alerta para disco > 80% e erro de upload > limiar.
- Revisar periodicamente arquivos orfaos e politicas de limpeza.

## Rollout incremental

### Fase 1 (rapida)

- Manter fluxo atual de materiais no Terminal.
- Trocar destino de upload para site de media Hostinger.
- Validar 2 arquivos reais ponta a ponta.

Status atual:

- Terminal ja prioriza upload para Hostinger quando variaveis `MEDIA_ORIGIN_*` estao configuradas.
- Sem Hostinger configurado, fallback operacional permanece em Bunny/local.

### Fase 2

- Habilitar links assinados para materiais restritos.
- Ajustar cache Cloudflare para publico vs restrito.

### Fase 3

- Desativar fallback legado (se estabilidade comprovada).
- Consolidar runbook operacional e incidente.

## Riscos e mitigacoes

- Gargalo no PHP de download: mitigar com cache e entrega estatica quando publico.
- Exposicao de conteudo pago: mitigar com assinatura curta e validacao obrigatoria.
- Saturacao de storage: mitigar com monitoramento e politica de retencao.

## Criterios de aceite

- Upload e sync de materiais funcionando sem Bunny.
- Arquivos publicos entregues com cache estavel.
- Arquivos restritos exigem token valido e expiram corretamente.
- Auditoria operacional com trilha minima de erro/sucesso.
