# Fluxos

## Fluxo de sync de pricing

1. Admin salva planos no Terminal.
2. Terminal persiste localmente.
3. Terminal monta payload completo (`plans[]`) e assina.
4. Terminal envia para Portal em `sync-pricing`.
5. Resultado de sync e registrado para auditoria operacional.

## Fluxo de sync de materiais

1. Admin salva material por curso/aula no Terminal.
2. Arquivo e enviado para CDN.
3. Terminal persiste metadados locais.
4. Terminal envia payload completo (`materials[]`) por `course_id` para `sync-materials`.
5. Resultado e registrado para auditoria operacional.
