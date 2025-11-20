<?php

namespace App\Controllers;

use App\Core\Database;
use App\Services\AdminAuthService;
use PDO;

/**
 * Controller Admin para Reviews (CRUD Completo)
 * Endpoints: /api/admin/reviews/*
 * Requer autenticação admin
 */
class AdminReviewsController extends BaseController
{
    private PDO $db;
    private AdminAuthService $adminAuth;

    public function __construct()
    {
        parent::__construct();
        // Usar banco default configurado no .env (pgsql ou mysql)
        $this->db = Database::connection();
        $this->adminAuth = new AdminAuthService();
    }

    /**
     * Middleware: Verifica se é admin
     */
    private function requireAdmin(): bool
    {
        if ($this->adminAuth->isAuthenticated()) {
            return true;
        }
        try {
            $at = $_COOKIE['adm_at'] ?? '';
            if ($at) {
                $jwt = new \App\Services\JwtService();
                $payload = $jwt->decode($at);
                if (($payload['typ'] ?? '') === 'access' && ($payload['aud'] ?? '') !== '') {
                    return true;
                }
            }
        } catch (\Throwable $e) { /* ignore */ }
        $this->json([
            'success' => false,
            'error' => 'Acesso negado. Autenticação de admin necessária.'
        ], 401);
        return false;
    }

    /**
     * Audit log de ações admin
     */
    private function audit(string $action, array $meta = []): void
    {
        try {
            $root = dirname(__DIR__, 2);
            $logDir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'admin';
            if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
            $file = $logDir . DIRECTORY_SEPARATOR . date('Y-m-d') . '_reviews.log';
            $entry = [
                'ts' => date('c'),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'action' => $action,
                'meta' => $meta,
            ];
            @file_put_contents($file, json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
        } catch (\Throwable $t) { /* ignore */ }
    }

    /**
     * GET /api/admin/reviews
     * Lista TODOS os reviews (incluindo inativos)
     */
    public function index(): void
    {
        if (!$this->requireAdmin()) return;

        try {
            $stmt = $this->db->query("
                SELECT 
                    id,
                    author_name,
                    author_country,
                    author_avatar,
                    rating,
                    review_text,
                    is_active,
                    display_order,
                    created_at,
                    updated_at
                FROM reviews
                ORDER BY display_order ASC, created_at DESC
            ");
            
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
     * GET /api/admin/reviews/:id
     * Busca review específico (incluindo inativos)
     */
    public function show(int $id): void
    {
        if (!$this->requireAdmin()) return;

        try {
            $stmt = $this->db->prepare("
                SELECT * FROM reviews WHERE id = ?
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

    /**
     * POST /api/admin/reviews
     * Cria novo review
     */
    public function create(): void
    {
        if (!$this->requireAdmin()) return;

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Validação
            $errors = [];
            if (empty($data['author_name'])) {
                $errors[] = 'Nome do autor é obrigatório';
            }
            if (empty($data['review_text'])) {
                $errors[] = 'Texto da avaliação é obrigatório';
            }
            if (!isset($data['rating']) || $data['rating'] < 0 || $data['rating'] > 5) {
                $errors[] = 'Rating deve ser entre 0 e 5';
            }

            if (!empty($errors)) {
                $this->json([
                    'success' => false,
                    'errors' => $errors
                ], 400);
                return;
            }

            $stmt = $this->db->prepare("
                INSERT INTO reviews (
                    author_name,
                    author_country,
                    author_avatar,
                    rating,
                    review_text,
                    is_active,
                    display_order
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $data['author_name'],
                $data['author_country'] ?? null,
                $data['author_avatar'] ?? null,
                $data['rating'],
                $data['review_text'],
                isset($data['is_active']) ? (bool)$data['is_active'] : true,
                $data['display_order'] ?? 0
            ]);

            $reviewId = $this->db->lastInsertId();

            $this->audit('review_created', ['review_id' => $reviewId, 'author' => $data['author_name']]);

            $this->json([
                'success' => true,
                'message' => 'Review criado com sucesso',
                'data' => ['id' => $reviewId]
            ], 201);

        } catch (\PDOException $e) {
            $this->json([
                'success' => false,
                'error' => 'Erro ao criar review',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/admin/reviews/:id
     * Atualiza review existente
     */
    public function update(int $id): void
    {
        if (!$this->requireAdmin()) return;

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Verificar se review existe
            $stmt = $this->db->prepare("SELECT id FROM reviews WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                $this->json([
                    'success' => false,
                    'error' => 'Review não encontrado'
                ], 404);
                return;
            }

            // Validação
            $errors = [];
            if (isset($data['author_name']) && empty($data['author_name'])) {
                $errors[] = 'Nome do autor não pode ser vazio';
            }
            if (isset($data['review_text']) && empty($data['review_text'])) {
                $errors[] = 'Texto da avaliação não pode ser vazio';
            }
            if (isset($data['rating']) && ($data['rating'] < 0 || $data['rating'] > 5)) {
                $errors[] = 'Rating deve ser entre 0 e 5';
            }

            if (!empty($errors)) {
                $this->json([
                    'success' => false,
                    'errors' => $errors
                ], 400);
                return;
            }

            // Construir query dinâmica (apenas campos fornecidos)
            $fields = [];
            $values = [];
            $allowedFields = ['author_name', 'author_country', 'author_avatar', 'rating', 'review_text', 'is_active', 'display_order'];

            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $fields[] = "{$field} = ?";
                    $values[] = $data[$field];
                }
            }

            if (empty($fields)) {
                $this->json([
                    'success' => false,
                    'error' => 'Nenhum campo para atualizar'
                ], 400);
                return;
            }

            $values[] = $id;
            $sql = "UPDATE reviews SET " . implode(', ', $fields) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);

            $this->audit('review_updated', ['review_id' => $id, 'fields' => array_keys($data)]);

            $this->json([
                'success' => true,
                'message' => 'Review atualizado com sucesso'
            ]);

        } catch (\PDOException $e) {
            $this->json([
                'success' => false,
                'error' => 'Erro ao atualizar review',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/admin/reviews/:id
     * Deleta review permanentemente
     */
    public function delete(int $id): void
    {
        if (!$this->requireAdmin()) return;

        try {
            // Verificar se review existe
            $stmt = $this->db->prepare("SELECT author_name FROM reviews WHERE id = ?");
            $stmt->execute([$id]);
            $review = $stmt->fetch();
            
            if (!$review) {
                $this->json([
                    'success' => false,
                    'error' => 'Review não encontrado'
                ], 404);
                return;
            }

            $stmt = $this->db->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->execute([$id]);

            $this->audit('review_deleted', ['review_id' => $id, 'author' => $review['author_name']]);

            $this->json([
                'success' => true,
                'message' => 'Review deletado com sucesso'
            ]);

        } catch (\PDOException $e) {
            $this->json([
                'success' => false,
                'error' => 'Erro ao deletar review',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PATCH /api/admin/reviews/:id/toggle
     * Ativa/desativa review
     */
    public function toggle(int $id): void
    {
        if (!$this->requireAdmin()) return;

        try {
            $stmt = $this->db->prepare("
                UPDATE reviews 
                SET is_active = NOT is_active 
                WHERE id = ?
            ");
            
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                $this->json([
                    'success' => false,
                    'error' => 'Review não encontrado'
                ], 404);
                return;
            }

            // Buscar novo estado
            $stmt = $this->db->prepare("SELECT is_active FROM reviews WHERE id = ?");
            $stmt->execute([$id]);
            $review = $stmt->fetch();

            $this->audit('review_toggled', ['review_id' => $id, 'is_active' => $review['is_active']]);

            $this->json([
                'success' => true,
                'message' => 'Status do review atualizado',
                'data' => ['is_active' => (bool)$review['is_active']]
            ]);

        } catch (\PDOException $e) {
            $this->json([
                'success' => false,
                'error' => 'Erro ao alterar status',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PATCH /api/admin/reviews/reorder
     * Reordena múltiplos reviews de uma vez
     */
    public function reorder(): void
    {
        if (!$this->requireAdmin()) return;

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['reviews']) || !is_array($data['reviews'])) {
                $this->json([
                    'success' => false,
                    'error' => 'Array de reviews inválido'
                ], 400);
                return;
            }

            $this->db->beginTransaction();

            $stmt = $this->db->prepare("UPDATE reviews SET display_order = ? WHERE id = ?");

            foreach ($data['reviews'] as $review) {
                if (!isset($review['id']) || !isset($review['display_order'])) {
                    $this->db->rollBack();
                    $this->json([
                        'success' => false,
                        'error' => 'Dados de ordenação inválidos'
                    ], 400);
                    return;
                }
                $stmt->execute([$review['display_order'], $review['id']]);
            }

            $this->db->commit();

            $this->audit('reviews_reordered', ['count' => count($data['reviews'])]);

            $this->json([
                'success' => true,
                'message' => 'Reviews reordenados com sucesso'
            ]);

        } catch (\PDOException $e) {
            $this->db->rollBack();
            $this->json([
                'success' => false,
                'error' => 'Erro ao reordenar reviews',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
