-- Tabela para registrar logs de ações do Discord (PostgreSQL)
CREATE TABLE IF NOT EXISTS discord_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    action VARCHAR(50) NOT NULL,
    details JSONB NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_user_id_logs ON discord_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_action ON discord_logs(action);
CREATE INDEX IF NOT EXISTS idx_created_at ON discord_logs(created_at);
