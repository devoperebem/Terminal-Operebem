-- Fix: Remover constraint único de discord_id que causa conflito com múltiplos NULL em MySQL

-- Dropar a chave única se existir
ALTER TABLE discord_users DROP INDEX IF EXISTS discord_id;

-- Recriar o índice sem uniqueness (apenas para performance em buscas)
CREATE INDEX IF NOT EXISTS idx_discord_id ON discord_users(discord_id);
