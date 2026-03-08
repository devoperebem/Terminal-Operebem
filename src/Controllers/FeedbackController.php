<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Application;

/**
 * Controller para Feedback de Usuários
 * Endpoint: /api/feedback/submit
 */
class FeedbackController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * POST /api/feedback/submit
     * Submete feedback do usuário autenticado
     */
    public function submit(): void
    {
        // Validar CSRF
        if (!$this->validateCsrf()) {
            $this->json([
                'success' => false,
                'error' => 'Token CSRF inválido'
            ], 403);
            return;
        }

        // Verificar autenticação
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            $this->json([
                'success' => false,
                'error' => 'Usuário não autenticado'
            ], 401);
            return;
        }

        // Validar dados obrigatórios
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($rating < 1 || $rating > 5) {
            $this->json([
                'success' => false,
                'error' => 'Avaliação inválida. Deve ser entre 1 e 5.'
            ], 400);
            return;
        }

        if (empty($comment) || strlen($comment) > 1000) {
            $this->json([
                'success' => false,
                'error' => 'Comentário é obrigatório e deve ter no máximo 1000 caracteres.'
            ], 400);
            return;
        }

        // Coletar respostas das perguntas (opcionais)
        $q1LikeMost = trim($_POST['q1_like_most'] ?? '');
        $q2Improve = trim($_POST['q2_improve'] ?? '');
        $q3Recommend = trim($_POST['q3_recommend'] ?? '');
        $q4FeatureRequest = trim($_POST['q4_feature_request'] ?? '');
        $q5SupportQuality = trim($_POST['q5_support_quality'] ?? '');

        // Criar tabela se não existir
        try {
            Database::query("
                CREATE TABLE IF NOT EXISTS user_feedback (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL,
                    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
                    comment TEXT NOT NULL,
                    q1_like_most VARCHAR(255),
                    q2_improve VARCHAR(255),
                    q3_recommend VARCHAR(50),
                    q4_feature_request VARCHAR(255),
                    q5_support_quality VARCHAR(50),
                    user_agent TEXT,
                    ip_address VARCHAR(45),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");
            
            Database::query("CREATE INDEX IF NOT EXISTS idx_user_feedback_user_id ON user_feedback(user_id)");
            Database::query("CREATE INDEX IF NOT EXISTS idx_user_feedback_rating ON user_feedback(rating)");
            Database::query("CREATE INDEX IF NOT EXISTS idx_user_feedback_created ON user_feedback(created_at DESC)");
        } catch (\Throwable $t) {
            // Tabela já existe ou erro ao criar
        }

        // Inserir feedback
        try {
            $feedbackId = Database::insert('user_feedback', [
                'user_id' => (int)$userId,
                'rating' => $rating,
                'comment' => $comment,
                'q1_like_most' => $q1LikeMost ?: null,
                'q2_improve' => $q2Improve ?: null,
                'q3_recommend' => $q3Recommend ?: null,
                'q4_feature_request' => $q4FeatureRequest ?: null,
                'q5_support_quality' => $q5SupportQuality ?: null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Log para auditoria
            try {
                Application::getInstance()->logger()->info('[FEEDBACK] User submitted feedback', [
                    'user_id' => $userId,
                    'feedback_id' => $feedbackId,
                    'rating' => $rating
                ]);
            } catch (\Throwable $t) {
                // Ignore logging errors
            }

            $this->json([
                'success' => true,
                'message' => 'Feedback enviado com sucesso! Obrigado por nos ajudar a melhorar.',
                'feedback_id' => $feedbackId
            ]);

        } catch (\Throwable $e) {
            try {
                Application::getInstance()->logger()->error('[FEEDBACK] Error submitting feedback', [
                    'user_id' => $userId,
                    'error' => $e->getMessage()
                ]);
            } catch (\Throwable $t) {
                // Ignore
            }

            $this->json([
                'success' => false,
                'error' => 'Erro ao enviar feedback. Tente novamente mais tarde.'
            ], 500);
        }
    }
}
