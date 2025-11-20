<?php

namespace App\Controllers;

use App\Core\Database;

class AdminAlunoLessonsController extends BaseController
{
    private function ensureSchema(): void
    {
        try { Database::query("ALTER TABLE lessons ADD COLUMN IF NOT EXISTS is_enabled BOOLEAN NOT NULL DEFAULT TRUE", [], 'aluno'); } catch (\Throwable $__) {}
        try { Database::query("ALTER TABLE lessons ADD COLUMN IF NOT EXISTS preview_animation_url TEXT NULL", [], 'aluno'); } catch (\Throwable $__) {}
        try { Database::query("ALTER TABLE lessons ADD COLUMN IF NOT EXISTS player_options JSON NULL", [], 'aluno'); } catch (\Throwable $__) {}
    }

    public function index(): void
    {
        $this->ensureSchema();
        $courseId = (int)($_GET['course_id'] ?? 0);
        if ($courseId <= 0) { $this->redirect('/secure/adm/aluno/courses'); }
        $course = Database::fetch('SELECT id, title FROM courses WHERE id = ?', [$courseId], 'aluno');
        if (!$course) { $this->redirect('/secure/adm/aluno/courses'); }
        $lessons = Database::fetchAll('SELECT id, title, position, bunny_video_id, duration_seconds, is_free_preview, COALESCE(is_enabled, TRUE) AS is_enabled FROM lessons WHERE course_id = ? ORDER BY position ASC, id ASC', [$courseId], 'aluno');
        $this->view('admin_secure/aluno_lessons_index', [
            'title' => 'Aulas · ' . (string)($course['title'] ?? ''),
            'course' => $course,
            'lessons' => $lessons,
            'footerVariant' => 'admin-auth',
        ]);
    }

    public function create(): void
    {
        $this->ensureSchema();
        $courseId = (int)($_GET['course_id'] ?? 0);
        if ($courseId <= 0) { $this->redirect('/secure/adm/aluno/courses'); }
        $course = Database::fetch('SELECT id, title FROM courses WHERE id = ?', [$courseId], 'aluno');
        if (!$course) { $this->redirect('/secure/adm/aluno/courses'); }
        $this->view('admin_secure/aluno_lessons_form', [
            'title' => 'Nova Aula',
            'course' => $course,
            'lesson' => null,
            'footerVariant' => 'admin-auth'
        ]);
    }

    public function edit(): void
    {
        $this->ensureSchema();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { $this->redirect('/secure/adm/aluno/courses'); }
        $lesson = Database::fetch('SELECT * FROM lessons WHERE id = ?', [$id], 'aluno');
        if (!$lesson) { $this->redirect('/secure/adm/aluno/courses'); }
        $course = Database::fetch('SELECT id, title FROM courses WHERE id = ?', [(int)$lesson['course_id']], 'aluno');
        $this->view('admin_secure/aluno_lessons_form', [
            'title' => 'Editar Aula',
            'course' => $course,
            'lesson' => $lesson,
            'footerVariant' => 'admin-auth'
        ]);
    }

    public function store(): void
    {
        $this->ensureSchema();
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/aluno/courses'); return; }
        $courseId = (int)($_POST['course_id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $desc = (string)($_POST['description'] ?? '');
        $position = (int)($_POST['position'] ?? 0);
        $bvid = trim((string)($_POST['bunny_video_id'] ?? ''));
        $dur = (int)($_POST['duration_seconds'] ?? 0);
        $thumb = trim((string)($_POST['thumbnail_url'] ?? ''));
        $previewAnim = trim((string)($_POST['preview_animation_url'] ?? ''));
        $isFree = in_array(($_POST['is_free_preview'] ?? ''), ['1','on','true'], true);
        $isEnabled = in_array(($_POST['is_enabled'] ?? ''), ['1','on','true'], true);
        $playerOpts = trim((string)($_POST['player_options'] ?? ''));
        if ($courseId <= 0 || $title === '' || $bvid === '') {
            $_SESSION['flash_error'] = 'Dados inválidos. Verifique o formulário.';
            $this->redirect('/secure/adm/aluno/courses');
            return;
        }
        try {
            $pos = $position > 0 ? $position : 999999;
            Database::query('INSERT INTO lessons (course_id, title, description, position, bunny_video_id, duration_seconds, thumbnail_url, preview_animation_url, is_free_preview, is_enabled, player_options, created_at, updated_at)
                             VALUES (:c,:t,:d,:p,:vid,:dur,:th,:pa,:free,:en, :opts, NOW(), NOW())', [
                'c'=>$courseId,'t'=>$title,'d'=>$desc,'p'=>$pos,'vid'=>$bvid,'dur'=>$dur ?: null,'th'=>$thumb ?: null,'pa'=>$previewAnim ?: null,'free'=>$isFree ? 'true':'false','en'=>$isEnabled ? 'true':'false','opts'=>$playerOpts !== '' ? $playerOpts : null
            ], 'aluno');
            // Normalize positions sequentially
            Database::query('WITH ordered AS (SELECT id, ROW_NUMBER() OVER (ORDER BY position, id) AS rn FROM lessons WHERE course_id = :c) UPDATE lessons l SET position = o.rn, updated_at = NOW() FROM ordered o WHERE l.id = o.id', ['c'=>$courseId], 'aluno');
            $_SESSION['flash_success'] = 'Aula criada com sucesso!';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Erro ao criar aula. Tente novamente.';
        }
        $this->redirect('/secure/adm/aluno/lessons?course_id=' . $courseId);
    }

    public function update(): void
    {
        $this->ensureSchema();
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/aluno/courses'); return; }
        $id = (int)($_POST['id'] ?? 0);
        $courseId = (int)($_POST['course_id'] ?? 0);
        if ($id <= 0 || $courseId <= 0) {
            $_SESSION['flash_error'] = 'ID inválido.';
            $this->redirect('/secure/adm/aluno/courses');
            return;
        }
        $title = trim((string)($_POST['title'] ?? ''));
        $desc = (string)($_POST['description'] ?? '');
        $position = (int)($_POST['position'] ?? 0);
        $bvid = trim((string)($_POST['bunny_video_id'] ?? ''));
        $dur = (int)($_POST['duration_seconds'] ?? 0);
        $thumb = trim((string)($_POST['thumbnail_url'] ?? ''));
        $previewAnim = trim((string)($_POST['preview_animation_url'] ?? ''));
        $isFree = in_array(($_POST['is_free_preview'] ?? ''), ['1','on','true'], true);
        $isEnabled = in_array(($_POST['is_enabled'] ?? ''), ['1','on','true'], true);
        $playerOpts = trim((string)($_POST['player_options'] ?? ''));
        if ($title === '' || $bvid === '') {
            $_SESSION['flash_error'] = 'Título e Video ID são obrigatórios.';
            $this->redirect('/secure/adm/aluno/lessons?course_id=' . $courseId);
            return;
        }
        try {
            $pos = $position > 0 ? $position : 999999;
            Database::query('UPDATE lessons SET title=:t, description=:d, position=:p, bunny_video_id=:vid, duration_seconds=:dur, thumbnail_url=:th, preview_animation_url=:pa, is_free_preview=:free, is_enabled=:en, player_options=:opts, updated_at=NOW() WHERE id=:id AND course_id=:c', [
                't'=>$title,'d'=>$desc,'p'=>$pos,'vid'=>$bvid,'dur'=>$dur ?: null,'th'=>$thumb ?: null,'pa'=>$previewAnim ?: null,'free'=>$isFree ? 'true':'false','en'=>$isEnabled ? 'true':'false','opts'=>$playerOpts !== '' ? $playerOpts : null,'id'=>$id,'c'=>$courseId
            ], 'aluno');
            // Keep only one preview? If is_free_preview true, ensure others false
            if ($isFree) {
                Database::query('UPDATE lessons SET is_free_preview = false WHERE course_id = :c AND id <> :id', ['c'=>$courseId,'id'=>$id], 'aluno');
            }
            Database::query('WITH ordered AS (SELECT id, ROW_NUMBER() OVER (ORDER BY position, id) AS rn FROM lessons WHERE course_id = :c) UPDATE lessons l SET position = o.rn WHERE l.id = o.id', ['c'=>$courseId], 'aluno');
            $_SESSION['flash_success'] = 'Aula atualizada com sucesso!';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Erro ao atualizar aula. Tente novamente.';
        }
        $this->redirect('/secure/adm/aluno/lessons?course_id=' . $courseId);
    }

    public function delete(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/aluno/courses'); return; }
        $id = (int)($_POST['id'] ?? 0);
        $courseId = (int)($_POST['course_id'] ?? 0);
        try {
            if ($id > 0) {
                Database::query('DELETE FROM lessons WHERE id = :id', ['id'=>$id], 'aluno');
                $_SESSION['flash_success'] = 'Aula excluída com sucesso!';
            }
            if ($courseId > 0) {
                Database::query('WITH ordered AS (SELECT id, ROW_NUMBER() OVER (ORDER BY position, id) AS rn FROM lessons WHERE course_id = :c) UPDATE lessons l SET position = o.rn WHERE l.id = o.id', ['c'=>$courseId], 'aluno');
            }
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Erro ao excluir aula. Tente novamente.';
        }
        $this->redirect('/secure/adm/aluno/lessons?course_id=' . max(0,$courseId));
    }

    public function reorder(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/aluno/courses'); }
        $id = (int)($_POST['id'] ?? 0);
        $dir = (string)($_POST['dir'] ?? '');
        if ($id <= 0) { $this->back(); }
        $row = Database::fetch('SELECT id, course_id, position FROM lessons WHERE id = ?', [$id], 'aluno');
        if (!$row) { $this->back(); }
        $courseId = (int)$row['course_id'];
        $pos = (int)$row['position'];
        $targetPos = $dir === 'up' ? ($pos - 1) : ($pos + 1);
        if ($targetPos <= 0) { $this->redirect('/secure/adm/aluno/lessons?course_id='.$courseId); }
        $neighbor = Database::fetch('SELECT id FROM lessons WHERE course_id = ? AND position = ? LIMIT 1', [$courseId, $targetPos], 'aluno');
        if ($neighbor && !empty($neighbor['id'])) {
            Database::query('UPDATE lessons SET position = :p WHERE id = :id', ['p'=>$targetPos,'id'=>$id], 'aluno');
            Database::query('UPDATE lessons SET position = :p WHERE id = :id', ['p'=>$pos,'id'=>(int)$neighbor['id']], 'aluno');
        }
        $this->redirect('/secure/adm/aluno/lessons?course_id='.$courseId);
    }

    public function toggleEnabled(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/aluno/courses'); }
        $id = (int)($_POST['id'] ?? 0);
        $courseId = (int)($_POST['course_id'] ?? 0);
        if ($id > 0) { Database::query('UPDATE lessons SET is_enabled = NOT COALESCE(is_enabled, TRUE), updated_at = NOW() WHERE id = :id', ['id'=>$id], 'aluno'); }
        $this->redirect('/secure/adm/aluno/lessons?course_id=' . $courseId);
    }

    public function setPreview(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/aluno/courses'); }
        $id = (int)($_POST['id'] ?? 0);
        $courseId = (int)($_POST['course_id'] ?? 0);
        if ($id > 0 && $courseId > 0) {
            Database::query('UPDATE lessons SET is_free_preview = false WHERE course_id = :c', ['c'=>$courseId], 'aluno');
            Database::query('UPDATE lessons SET is_free_preview = true, updated_at = NOW() WHERE id = :id', ['id'=>$id], 'aluno');
        }
        $this->redirect('/secure/adm/aluno/lessons?course_id=' . $courseId);
    }
}
