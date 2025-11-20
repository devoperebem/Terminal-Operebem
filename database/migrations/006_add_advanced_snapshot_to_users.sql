-- Migration: Adicionar coluna advanced_snapshot à tabela users (MySQL)
-- Data: 2025-10-30
-- Descrição: Adiciona preferência de snapshot avançado (padrão: true)

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS advanced_snapshot TINYINT(1) DEFAULT 1 COMMENT 'Se 1, mostra ícone de câmera com tooltip completo. Se 0, mostra apenas % no snapshot';
