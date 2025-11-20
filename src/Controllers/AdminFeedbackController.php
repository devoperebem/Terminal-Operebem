<?php

namespace App\Controllers;

use App\Core\Database;

/**
 * Controller Admin para visualizar Feedbacks de Usuários
 * Endpoints: /secure/adm/feedback
 */
class AdminFeedbackController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * GET /api/admin/feedbacks
     * API para listar feedbacks (usado na página mesclada)
     */
    public function api(): void
    {
        $sql = "
            SELECT 
                f.*,
                u.name as user_name,
                u.email as user_email
            FROM user_feedback f
            LEFT JOIN users u ON f.user_id = u.id
            ORDER BY f.created_at DESC
            LIMIT 500
        ";
        
        $feedbacks = [];
        try {
            $feedbacks = Database::fetchAll($sql);
        } catch (\Throwable $t) {
            // Tabela pode não existir ainda
        }
        
        $this->json([
            'success' => true,
            'data' => $feedbacks
        ]);
    }

    /**
     * GET /secure/adm/feedback
     * Lista todos os feedbacks dos usuários
     */
    public function index(): void
    {
        // Buscar feedbacks com informações do usuário
        $sql = "
            SELECT 
                f.*,
                u.name as user_name,
                u.email as user_email
            FROM user_feedback f
            LEFT JOIN users u ON f.user_id = u.id
            ORDER BY f.created_at DESC
            LIMIT 500
        ";
        
        $feedbacks = [];
        try {
            $feedbacks = Database::fetchAll($sql);
        } catch (\Throwable $t) {
            // Tabela pode não existir ainda
        }
        
        // Estatísticas
        $stats = [
            'total' => 0,
            'avg_rating' => 0,
            'rating_5' => 0,
            'rating_4' => 0,
            'rating_3' => 0,
            'rating_2' => 0,
            'rating_1' => 0,
            'recommend_yes' => 0,
            'recommend_no' => 0,
        ];
        
        try {
            $r = Database::fetch('SELECT COUNT(*) AS c, AVG(rating) AS avg FROM user_feedback');
            $stats['total'] = (int)($r['c'] ?? 0);
            $stats['avg_rating'] = round((float)($r['avg'] ?? 0), 1);
        } catch (\Throwable $t) {}
        
        try {
            $r = Database::fetch("SELECT COUNT(*) AS c FROM user_feedback WHERE rating = 5");
            $stats['rating_5'] = (int)($r['c'] ?? 0);
        } catch (\Throwable $t) {}
        
        try {
            $r = Database::fetch("SELECT COUNT(*) AS c FROM user_feedback WHERE rating = 4");
            $stats['rating_4'] = (int)($r['c'] ?? 0);
        } catch (\Throwable $t) {}
        
        try {
            $r = Database::fetch("SELECT COUNT(*) AS c FROM user_feedback WHERE rating = 3");
            $stats['rating_3'] = (int)($r['c'] ?? 0);
        } catch (\Throwable $t) {}
        
        try {
            $r = Database::fetch("SELECT COUNT(*) AS c FROM user_feedback WHERE rating = 2");
            $stats['rating_2'] = (int)($r['c'] ?? 0);
        } catch (\Throwable $t) {}
        
        try {
            $r = Database::fetch("SELECT COUNT(*) AS c FROM user_feedback WHERE rating = 1");
            $stats['rating_1'] = (int)($r['c'] ?? 0);
        } catch (\Throwable $t) {}
        
        try {
            $r = Database::fetch("SELECT COUNT(*) AS c FROM user_feedback WHERE q3_recommend IN ('definitely', 'probably')");
            $stats['recommend_yes'] = (int)($r['c'] ?? 0);
        } catch (\Throwable $t) {}
        
        try {
            $r = Database::fetch("SELECT COUNT(*) AS c FROM user_feedback WHERE q3_recommend IN ('probably_not', 'definitely_not')");
            $stats['recommend_no'] = (int)($r['c'] ?? 0);
        } catch (\Throwable $t) {}
        
        $this->view('admin_secure/feedback', [
            'title' => 'Secure Admin - Feedbacks',
            'footerVariant' => 'admin-auth',
            'feedbacks' => $feedbacks,
            'stats' => $stats,
        ]);
    }

    /**
     * POST /secure/adm/feedback/promote/:id
     * Promove um feedback para review público
     */
    public function promote(): void
    {
        if (!$this->validateCsrf()) {
            $this->json(['success' => false, 'error' => 'CSRF token inválido'], 403);
            return;
        }

        $feedbackId = (int)($_POST['feedback_id'] ?? 0);
        
        if ($feedbackId <= 0) {
            $this->json(['success' => false, 'error' => 'ID inválido'], 400);
            return;
        }

        try {
            // Buscar feedback
            $feedback = Database::fetch('SELECT f.*, u.name, u.email FROM user_feedback f LEFT JOIN users u ON f.user_id = u.id WHERE f.id = ?', [$feedbackId]);
            
            if (!$feedback) {
                $this->json(['success' => false, 'error' => 'Feedback não encontrado'], 404);
                return;
            }

            // Verificar se já existe como review
            $existing = Database::fetch("SELECT id FROM reviews WHERE review_text = ?", [$feedback['comment']]);
            
            if ($existing) {
                $this->json(['success' => false, 'error' => 'Este feedback já foi promovido para review'], 400);
                return;
            }

            // Criar tabela reviews se não existir
            Database::query("
                CREATE TABLE IF NOT EXISTS reviews (
                    id SERIAL PRIMARY KEY,
                    author_name VARCHAR(255) NOT NULL,
                    author_country VARCHAR(100),
                    author_avatar TEXT,
                    rating DECIMAL(2,1) NOT NULL,
                    review_text TEXT NOT NULL,
                    is_active BOOLEAN DEFAULT false,
                    display_order INTEGER DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Inserir review
            $reviewId = Database::insert('reviews', [
                'author_name' => $feedback['name'] ?? 'Usuário',
                'author_country' => null,
                'author_avatar' => null,
                'rating' => (float)$feedback['rating'],
                'review_text' => $feedback['comment'],
                'is_active' => false, // Admin precisa ativar manualmente
                'display_order' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $this->json([
                'success' => true,
                'message' => 'Feedback promovido para review com sucesso!',
                'review_id' => $reviewId
            ]);

        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'error' => 'Erro ao promover feedback: ' . $e->getMessage()
            ], 500);
        }
    }
}
