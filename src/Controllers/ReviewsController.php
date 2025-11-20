<?php

namespace App\Controllers;

use App\Core\Database;
use PDO;

/**
 * Controller para Reviews (Público)
 * Endpoint: /api/reviews
 */
class ReviewsController extends BaseController
{
    private PDO $db;

    public function __construct()
    {
        parent::__construct();
        // Usar banco default configurado no .env (pgsql ou mysql)
        $this->db = Database::connection();
    }

    /**
     * GET /api/reviews
     * Lista todos os reviews ativos
     */
    public function index(): void
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    author_name,
                    author_country,
                    author_avatar,
                    rating,
                    review_text,
                    display_order
                FROM reviews
                WHERE is_active = true
                ORDER BY display_order ASC, created_at DESC
            ");
            
            $stmt->execute();
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->json([
                'success' => true,
                'data' => $reviews,
                'count' => count($reviews)
            ]);

        } catch (\PDOException $e) {
            $this->json([
                'success' => false,
                'error' => 'Erro ao buscar reviews',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/reviews/:id
     * Busca um review específico (se ativo)
     */
    public function show(int $id): void
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    author_name,
                    author_country,
                    author_avatar,
                    rating,
                    review_text,
                    display_order
                FROM reviews
                WHERE id = ? AND is_active = true
            ");
            
            $stmt->execute([$id]);
            $review = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$review) {
                $this->json([
                    'success' => false,
                    'error' => 'Review não encontrado'
                ], 404);
                return;
            }

            $this->json([
                'success' => true,
                'data' => $review
            ]);

        } catch (\PDOException $e) {
            $this->json([
                'success' => false,
                'error' => 'Erro ao buscar review',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
