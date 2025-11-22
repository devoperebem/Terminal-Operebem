# 🔧 Solução para FEF1! e FEF2! (Futuros de Minério de Ferro SGX)

## 📋 Problema Identificado

**FEF1!** e **FEF2!** (Iron Ore Futures da Singapore Exchange) estão cadastrados no banco mas **SEM DADOS** porque:

1. ✅ Estão no banco `dicionario` com origem `barchart`
2. ❌ **NÃO existe scraper ativo para Barchart**
3. ❌ Não possuem `id_api` do Investing.com
4. ⚠️ São críticos: FEF1! tem **35% de peso** no índice IFPV (Feeling VALE3)

---

## ✅ Solução: Migrar para Investing.com

### Passo 1: Executar SQL no Banco PostgreSQL (quotes)

```sql
-- 1. Verificar estado atual
SELECT code, id_api, origem, ativo, nome, last, timestamp
FROM dicionario
WHERE code IN ('FEF1!', 'FEF2!');

-- 2. Atualizar FEF1! para usar Investing.com
UPDATE dicionario
SET id_api = '961741',
    origem = 'investing',
    ativo = 1,
    nome = 'Iron Ore Futures',
    apelido = 'Minério de Ferro',
    icone_bandeira = '🇸🇬',
    bandeira = 'sg',
    bolsa = 'SGX',
    grupo = 'metais'
WHERE code = 'FEF1!';

-- 3. Atualizar FEF2! (usar mesmo ID temporariamente)
UPDATE dicionario
SET id_api = '961741',
    origem = 'investing',
    ativo = 1,
    nome = 'Iron Ore Futures F2',
    apelido = 'Minério de Ferro F2',
    icone_bandeira = '🇸🇬',
    bandeira = 'sg',
    bolsa = 'SGX',
    grupo = 'metais'
WHERE code = 'FEF2!';

-- 4. Verificar resultado
SELECT code, id_api, origem, ativo, nome
FROM dicionario
WHERE code IN ('FEF1!', 'FEF2!');
```

### Passo 2: Aguardar Atualização Automática

- Os dados serão atualizados automaticamente pelo scraper do Investing.com
- Aguarde **até 5 minutos** para a próxima atualização
- Verifique o dashboard do ouro para confirmar

---

## 📊 ID do Investing.com

**Iron Ore Futures (SGX):**
- **ID**: `961741`
- **Nome**: Iron Ore Futures Continuous Contract
- **Bolsa**: SGX (Singapore Exchange)
- **URL**: https://www.investing.com/commodities/us-iron-ore-62-cfr-futures

**Nota**: FEF1! e FEF2! usam o mesmo ID temporariamente. Se precisar de IDs distintos, pesquise no Investing.com.

---

## 🎯 Impacto

Após a correção:

1. ✅ FEF1! e FEF2! receberão dados em tempo real
2. ✅ Índice IFPV (Vale) terá dados reais do minério de ferro (35% do peso)
3. ✅ Dashboard de indicadores mostrará dados completos
4. ✅ Página de sentimento do mercado ficará mais precisa

---

## 🔍 Verificação

Para verificar se funcionou:

```sql
-- Verificar última atualização
SELECT code, last, pc, pcp, timestamp,
       NOW() - TO_TIMESTAMP(timestamp::bigint) as tempo_desde_update
FROM dicionario
WHERE code IN ('FEF1!', 'FEF2!');

-- Se timestamp está recente (< 5 min), está funcionando!
```

---

## 🚀 Script Automatizado (Alternativo)

Se preferir, execute:

```bash
php tools/fix_fef_futures.php
```

O script faz tudo automaticamente (requer PostgreSQL rodando).

---

## 📝 Notas Técnicas

1. **Por que "barchart" não funciona?**
   - Não há scraper implementado para Barchart
   - Investing.com já tem scraper ativo e funcional

2. **Por que FEF1! e FEF2! usam mesmo ID?**
   - São contratos similares do mesmo ativo (minério de ferro)
   - Investing.com usa contrato contínuo (roll automático)
   - Pode-se usar IDs distintos se disponíveis

3. **SGX Trading Hours:**
   - 08:45 - 19:00 SGT (UTC+8)
   - 00:45 - 11:00 UTC
   - Segunda a Sexta

---

## ✅ Checklist

- [ ] Executar SQLs de atualização
- [ ] Aguardar 5 minutos
- [ ] Verificar dashboard do ouro
- [ ] Confirmar que IFPV mostra dados do minério
- [ ] Verificar timestamp foi atualizado

---

## 🆘 Troubleshooting

**Problema**: Dados ainda não aparecem após 10 minutos

**Solução**:
```sql
-- Forçar ativo = 1
UPDATE dicionario SET ativo = 1 WHERE code IN ('FEF1!', 'FEF2!');

-- Verificar se scraper do Investing está rodando
-- Checar logs em storage/logs/
```

**Problema**: ID 961741 não funciona

**Solução**:
1. Acesse https://www.investing.com/commodities/us-iron-ore-62-cfr-futures
2. Inspecione o código da página
3. Procure por "pairId" ou similar
4. Use o novo ID encontrado

---

## 📚 Referências

- **Investing.com Iron Ore**: https://www.investing.com/commodities/us-iron-ore-62-cfr-futures
- **SGX Iron Ore Futures**: https://www.sgx.com/securities/derivatives/commodities
- **Código IFPV**: `src/Services/OBIndicesService.php:28`

---

**Criado em**: 2025-11-22
**Status**: ✅ Solução documentada e pronta para aplicação
