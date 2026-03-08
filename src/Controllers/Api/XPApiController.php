<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Services\GamificationService;
use App\Services\XPSettingsService;

class XPApiController extends BaseController
{
    private function validateApiKey(): bool
    {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        $expected = $_ENV['ALUNO_API_KEY'] ?? ($_ENV['DISCORD_BOT_API_KEY'] ?? '');
        return (!empty($expected) && hash_equals($expected, $apiKey));
    }

    /**
     * POST /api/xp/lesson-completed
     * Body JSON: { user_id: int, lesson_id: int }
     * Idempotente por (user_id, lesson_id)
     */
    public function lessonCompleted(): void
    {
        header('Content-Type: application/json');
        if (!$this->validateApiKey()) { http_response_code(401); echo json_encode(['success'=>false,'error'=>'Unauthorized']); return; }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $userId = (int)($input['user_id'] ?? 0);
        $lessonId = (int)($input['lesson_id'] ?? 0);
        if ($userId <= 0 || $lessonId <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'user_id e lesson_id são obrigatórios']); return; }

        try {
            // Idempotência
            $exists = Database::fetch(
                "SELECT id FROM xp_history WHERE user_id = :u AND source = 'lesson_completed' AND source_id = :sid LIMIT 1",
                ['u'=>$userId, 'sid'=>$lessonId]
            );
            if ($exists) { echo json_encode(['success'=>true,'awarded'=>false,'reason'=>'already_awarded']); return; }

            // Buscar dados da aula no DB do Aluno (se disponível)
            $lesson = Database::fetch(
                'SELECT id, title, duration_seconds FROM lessons WHERE id = :id',
                ['id' => $lessonId],
                'aluno'
            );

            // Calcular XP: base + bônus por duração
            $base = XPSettingsService::get('xp_lesson_base', 10);
            $bonus30 = XPSettingsService::get('xp_lesson_bonus_30min', 5);
            $bonus1h = XPSettingsService::get('xp_lesson_bonus_1h', 10);
            $amount = $base;
            $dur = (int)($lesson['duration_seconds'] ?? 0);
            if ($dur >= 3600) { $amount += $bonus1h; }
            elseif ($dur >= 1800) { $amount += $bonus30; }

            if ($amount <= 0) { echo json_encode(['success'=>true,'awarded'=>false,'reason'=>'disabled']); return; }

            $desc = 'Aula concluída' . ($lesson && !empty($lesson['title']) ? (': ' . $lesson['title']) : '');
            $service = new GamificationService();
            $ok = $service->addXP($userId, $amount, $desc, 'lesson_completed', $lessonId);
            if (!$ok) { http_response_code(500); echo json_encode(['success'=>false,'error'=>'Falha ao conceder XP']); return; }

            echo json_encode(['success'=>true,'awarded'=>true,'amount'=>$amount]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success'=>false,'error'=>'Erro interno do servidor']);
        }
    }

    /**
     * POST /api/xp/course-completed
     * Body JSON: { user_id: int, course_id: int }
     * Idempotente por (user_id, course_id)
     */
    public function courseCompleted(): void
    {
        header('Content-Type: application/json');
        if (!$this->validateApiKey()) { http_response_code(401); echo json_encode(['success'=>false,'error'=>'Unauthorized']); return; }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $userId = (int)($input['user_id'] ?? 0);
        $courseId = (int)($input['course_id'] ?? 0);
        if ($userId <= 0 || $courseId <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'user_id e course_id são obrigatórios']); return; }

        try {
            // Idempotência
            $exists = Database::fetch(
                "SELECT id FROM xp_history WHERE user_id = :u AND source = 'course_completed' AND source_id = :sid LIMIT 1",
                ['u'=>$userId, 'sid'=>$courseId]
            );
            if ($exists) { echo json_encode(['success'=>true,'awarded'=>false,'reason'=>'already_awarded']); return; }

            // Buscar dados do curso (opcional)
            $course = Database::fetch(
                'SELECT id, title FROM courses WHERE id = :id',
                ['id' => $courseId],
                'aluno'
            );

            $amount = XPSettingsService::get('xp_course_complete', 50);
            if ($amount <= 0) { echo json_encode(['success'=>true,'awarded'=>false,'reason'=>'disabled']); return; }

            $desc = 'Curso concluído' . ($course && !empty($course['title']) ? (': ' . $course['title']) : '');
            $service = new GamificationService();
            $ok = $service->addXP($userId, $amount, $desc, 'course_completed', $courseId);
            if (!$ok) { http_response_code(500); echo json_encode(['success'=>false,'error'=>'Falha ao conceder XP']); return; }

            echo json_encode(['success'=>true,'awarded'=>true,'amount'=>$amount]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success'=>false,'error'=>'Erro interno do servidor']);
        }
    }
}
