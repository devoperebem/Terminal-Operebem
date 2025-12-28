-- Migration: Adicionar campo tier à tabela users
-- Data: 2025-12-28
-- Descrição: Adiciona sistema de tiers (FREE, PLUS, PRO) para controle de assinatura

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS tier ENUM('FREE', 'PLUS', 'PRO') DEFAULT 'FREE' NOT NULL 
COMMENT 'Nível de assinatura do usuário: FREE (gratuito), PLUS (intermediário), PRO (completo)';

-- Índice para buscar usuários por tier
CREATE INDEX IF NOT EXISTS idx_users_tier ON users(tier);

-- Adicionar campo para data de expiração da assinatura (para uso futuro)
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS subscription_expires_at TIMESTAMP NULL 
COMMENT 'Data de expiração da assinatura (NULL = sem expiração ou tier FREE)';
