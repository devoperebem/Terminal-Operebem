-- Criar tabela de reviews se não existir
CREATE TABLE IF NOT EXISTS reviews (
    id SERIAL PRIMARY KEY,
    author_name VARCHAR(255) NOT NULL,
    author_country VARCHAR(100),
    author_avatar TEXT,
    rating DECIMAL(2,1) NOT NULL CHECK (rating >= 0 AND rating <= 5),
    review_text TEXT NOT NULL,
    main_quote TEXT,
    description TEXT,
    display_order INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Criar índice para melhorar performance
CREATE INDEX IF NOT EXISTS idx_reviews_active_order ON reviews(is_active, display_order, created_at);

-- Inserir reviews de exemplo (apenas se a tabela estiver vazia)
INSERT INTO reviews (author_name, author_country, author_avatar, rating, review_text, main_quote, description, display_order, is_active)
SELECT * FROM (VALUES
    ('Carlos Silva', 'Brasil 🇧🇷', NULL, 5.0, 'Excelente plataforma! As cotações em tempo real me ajudaram muito a tomar decisões mais assertivas.', 'Excelente plataforma!', 'As cotações em tempo real me ajudaram muito a tomar decisões mais assertivas. Recomendo para todos os traders que buscam informações precisas e atualizadas.', 1, true),
    ('Maria Santos', 'Portugal 🇵🇹', NULL, 4.5, 'Interface muito intuitiva e dados confiáveis. O dashboard é completo e fácil de usar.', 'Interface muito intuitiva e dados confiáveis.', 'O dashboard é completo e fácil de usar. Parabéns pelo trabalho! Estou muito satisfeita com a qualidade das informações fornecidas.', 2, true),
    ('João Oliveira', 'Brasil 🇧🇷', NULL, 5.0, 'Finalmente encontrei uma plataforma que reúne tudo que preciso em um só lugar.', 'Finalmente encontrei uma plataforma que reúne tudo que preciso.', 'Os indicadores são precisos e atualizados. A curadoria de notícias é excelente e me mantém sempre informado sobre o mercado.', 3, true),
    ('Ana Costa', 'Brasil 🇧🇷', NULL, 4.5, 'Muito bom! A curadoria de notícias é excelente e me mantém sempre informada sobre o mercado.', 'Muito bom! A curadoria de notícias é excelente.', 'Me mantém sempre informada sobre o mercado. Vale muito a pena para quem quer ter acesso a informações de qualidade.', 4, true),
    ('Pedro Almeida', 'Brasil 🇧🇷', NULL, 5.0, 'Plataforma profissional com recursos avançados. O suporte também é muito atencioso.', 'Plataforma profissional com recursos avançados.', 'O suporte também é muito atencioso. Estou muito satisfeito com a experiência e recomendo para todos os traders.', 5, true),
    ('Luciana Ferreira', 'Brasil 🇧🇷', NULL, 4.5, 'Ótima ferramenta para acompanhar o mercado. Os gráficos são claros e as informações são sempre precisas.', 'Ótima ferramenta para acompanhar o mercado.', 'Os gráficos são claros e as informações são sempre precisas. Facilita muito minha análise diária do mercado financeiro.', 6, true)
) AS v(author_name, author_country, author_avatar, rating, review_text, main_quote, description, display_order, is_active)
WHERE NOT EXISTS (SELECT 1 FROM reviews LIMIT 1);
