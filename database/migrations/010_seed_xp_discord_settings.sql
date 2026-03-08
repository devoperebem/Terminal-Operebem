-- Migration: Seed Discord message XP settings
-- Description: Inserts default values for Discord message XP settings
-- Created: 2025-11-15

INSERT INTO xp_settings (setting_key, setting_value, description)
VALUES
    ('xp_discord_msg_amount', 1, 'XP concedido por mensagem no Discord (0 desativa)'),
    ('xp_discord_msg_cooldown_minutes', 10, 'Cooldown em minutos entre premiações por mensagem'),
    ('xp_discord_msg_daily_cap', 25, 'Limite diário de XP vindo de mensagens no Discord (0 desativa)')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
