-- Fix: Remover constraint único de discord_id que causa conflito com múltiplos NULL
-- PostgreSQL trata NULL != NULL, então a constraint não deveria existir

-- Dropar a constraint se existir
ALTER TABLE discord_users DROP CONSTRAINT IF EXISTS discord_users_discord_id_key;

-- Remover o índice se existir (criado manualmente)
DROP INDEX IF EXISTS idx_discord_id;

-- Recriar o índice sem uniqueness (apenas para performance em buscas)
CREATE INDEX IF NOT EXISTS idx_discord_id ON discord_users(discord_id);
