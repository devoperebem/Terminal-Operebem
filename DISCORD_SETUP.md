# 🎮 Discord Integration - Setup Guide

## ⚠️ Migração Necessária

Após fazer o push do código, você **precisa executar as migrações do banco de dados**.

Como você está usando **Hostinger** (sem acesso a terminal), existe um script PHP que faz isso automaticamente.

---

## 🚀 Como Executar as Migrações

### Opção 1: Script Automático (Recomendado para Hostinger)

1. Após fazer push do código, acesse a URL:
   ```
   https://terminal.operebem.com.br/run_migrations.php
   ```

2. Insira o token de segurança:
   ```
   discord123
   ```

3. Clique em **"Executar Migrações"**

4. A página vai mostrar o resultado de cada migração:
   - ✅ Se vir mensagens verdes = sucesso!
   - ❌ Se vir mensagens vermelhas = houve um erro

5. **IMPORTANTE**: Após executar com sucesso, **delete o arquivo** `run_migrations.php` por questões de segurança:
   - Via File Manager da Hostinger
   - Ou via FTP

---

## 📝 O que as Migrações Fazem

### 1. Tabela `discord_users`
Rastreia qual usuário está conectado a qual conta Discord:
- `discord_id` - ID do usuário no Discord
- `discord_username` - Username no Discord
- `verification_code` - Código único para verificação
- `is_verified` - Se a conta está verificada

### 2. Tabela `discord_logs`
Registra todas as ações relacionadas ao Discord (desconexão, etc)

### 3. Seed XP Settings
Insere configurações padrão de XP para mensagens no Discord

---

## ✅ Como Verificar se Tudo Funcionou

Após executar as migrações:

1. Acesse: `https://terminal.operebem.com.br/app/community`
2. Você deve ver a página da comunidade Discord **sem erros 500**
3. Clique em "Gerar Novo Código" - deve funcionar
4. Tente desconectar (se estiver conectado) - deve funcionar

---

## 🔧 Se der Erro

### Erro: "Acesso negado"
- Adicione o token correto na URL: `?token=EXECUTE_DISCORD_MIGRATIONS`

### Erro: "Conexão falhou com o banco"
- Verifique se as variáveis de ambiente `.env` estão configuradas corretamente
- Ou entre em contato com o suporte Hostinger

### Erro: "Tabela já existe"
- Pode ignorar - significa que a migração já foi executada antes
- Ou delete as tabelas e execute novamente

---

## 🔐 Segurança

**NUNCA deixe o arquivo `run_migrations.php` no servidor!**

Após usar:
1. Faça login no painel Hostinger
2. Vá para File Manager
3. Navegue até `/public_html/`
4. Delete `run_migrations.php`

---

## 📞 Suporte

Se encontrar problemas:
- Verifique os logs em `/storage/logs/error.log`
- Verifique se a conexão com o banco está funcionando
- Valide as credenciais do banco de dados

---

## 🎯 Checklist

- [ ] Fazer push do código
- [ ] Executar script de migração (`run_migrations.php?token=discord123`)
- [ ] Verificar que retornou ✅ para todas as migrações
- [ ] Testar acesso em `/app/community`
- [ ] **Deletar o arquivo `run_migrations.php`**
- [ ] Testar desconectar da comunidade (POST `/app/community/disconnect`)

Pronto! 🚀
