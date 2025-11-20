<?php

namespace App\Controllers;

use App\Core\Application;
use App\Core\Database;

class AdminAlunoCoursesController extends BaseController
{
    private function csrf(): string { return $_SESSION['csrf_token'] ?? ''; }

    private function ensurePositionColumn(): void
    {
        try { Database::query('ALTER TABLE courses ADD COLUMN IF NOT EXISTS position INTEGER', [], 'aluno'); } catch (\Throwable $__) {}
        try { Database::query('CREATE INDEX IF NOT EXISTS idx_courses_position ON courses(position)', [], 'aluno'); } catch (\Throwable $__) {}
        try {
            // Backfill positions for rows missing it
            $rows = Database::fetchAll('SELECT id FROM courses WHERE position IS NULL OR position <= 0 ORDER BY id ASC', [], 'aluno');
            $pos = (int)(Database::fetch('SELECT COALESCE(MAX(position),0) AS m FROM courses', [], 'aluno')['m'] ?? 0);
            foreach ($rows as $r) { $pos++; Database::update('courses', ['position' => $pos], ['id' => (int)$r['id']], 'aluno'); }
        } catch (\Throwable $__) {}
    }

    public function index(): void
    {
        $this->ensurePositionColumn();
        $rows = [];
        try { $rows = Database::fetchAll('SELECT id, title, price_cents, is_free, created_at AS updated_at, COALESCE(position, id) AS position FROM courses ORDER BY position ASC, id ASC', [], 'aluno'); } catch (\Throwable $__) {}
        $this->view('admin_secure/aluno_courses_index', [
            'title' => 'Cursos (Aluno)',
            'courses' => $rows,
            'footerVariant' => 'admin-auth'
        ]);
    }

    public function create(): void
    {
        $this->view('admin_secure/aluno_courses_form', [
            'title' => 'Novo Curso (Aluno)',
            'course' => null,
            'footerVariant' => 'admin-auth'
        ]);
    }

    public function edit(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { $this->redirect('/secure/adm/aluno/courses'); }
        $row = null;
        try { $row = Database::fetch('SELECT * FROM courses WHERE id = ?', [$id], 'aluno'); } catch (\Throwable $__) {}
        if (!$row) { $this->redirect('/secure/adm/aluno/courses'); }
        $this->view('admin_secure/aluno_courses_form', [
            'title' => 'Editar Curso (Aluno)',
            'course' => $row,
            'footerVariant' => 'admin-auth'
        ]);
    }

    public function store(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/aluno/courses'); return; }
        $title = trim((string)($_POST['title'] ?? ''));
        $desc = (string)($_POST['description'] ?? '');
        $isFree = isset($_POST['is_free']) && in_array($_POST['is_free'], ['1','on','true'], true);
        $price = (int)($_POST['price_cents'] ?? 0);
        if ($title === '') { $this->redirect('/secure/adm/aluno/courses?err=title'); return; }
        try {
            Database::insert('courses', [
                'title' => $title,
                'description' => $desc,
                'is_free' => $isFree,
                'price_cents' => $price,
                'created_at' => date('Y-m-d H:i:s')
            ], 'aluno');
            $_SESSION['flash_success'] = 'Curso criado com sucesso!';
        } catch (\Throwable $e) {
            try { Application::getInstance()->logger()->error('Aluno course store fail: '.$e->getMessage()); } catch (\Throwable $__) {}
            $_SESSION['flash_error'] = 'Erro ao criar curso. Tente novamente.';
        }
        $this->redirect('/secure/adm/aluno/courses');
    }

    public function update(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/aluno/courses'); return; }
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { $this->redirect('/secure/adm/aluno/courses'); return; }
        $title = trim((string)($_POST['title'] ?? ''));
        $desc = (string)($_POST['description'] ?? '');
        $isFree = isset($_POST['is_free']) && in_array($_POST['is_free'], ['1','on','true'], true);
        $price = (int)($_POST['price_cents'] ?? 0);
        if ($title === '') { $this->redirect('/secure/adm/aluno/courses?err=title'); return; }
        try {
            Database::update('courses', [
                'title' => $title,
                'description' => $desc,
                'is_free' => $isFree,
                'price_cents' => $price
            ], [ 'id' => $id ], 'aluno');
            $_SESSION['flash_success'] = 'Curso atualizado com sucesso!';
        } catch (\Throwable $e) {
            try { Application::getInstance()->logger()->error('Aluno course update fail: '.$e->getMessage()); } catch (\Throwable $__) {}
            $_SESSION['flash_error'] = 'Erro ao atualizar curso. Tente novamente.';
        }
        $this->redirect('/secure/adm/aluno/courses');
    }

    public function move(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/aluno/courses'); }
        $this->ensurePositionColumn();
        $id = (int)($_POST['id'] ?? 0);
        $dir = trim((string)($_POST['dir'] ?? ''));
        if ($id <= 0 || !in_array($dir, ['up','down'], true)) { $this->redirect('/secure/adm/aluno/courses'); }
        $row = Database::fetch('SELECT id, COALESCE(position, id) AS position FROM courses WHERE id = ?', [$id], 'aluno');
        if (!$row) { $this->redirect('/secure/adm/aluno/courses'); }
        $pos = (int)$row['position'];
        if ($dir === 'up') {
            $swap = Database::fetch('SELECT id, COALESCE(position, id) AS position FROM courses WHERE COALESCE(position, id) < ? ORDER BY COALESCE(position, id) DESC, id DESC LIMIT 1', [$pos], 'aluno');
        } else {
            $swap = Database::fetch('SELECT id, COALESCE(position, id) AS position FROM courses WHERE COALESCE(position, id) > ? ORDER BY COALESCE(position, id) ASC, id ASC LIMIT 1', [$pos], 'aluno');
        }
        if ($swap) {
            try {
                Database::beginTransaction('aluno');
                Database::update('courses', ['position' => (int)$swap['position']], ['id' => $id], 'aluno');
                Database::update('courses', ['position' => $pos], ['id' => (int)$swap['id']], 'aluno');
                Database::commit('aluno');
            } catch (\Throwable $__) {
                try { Database::rollback('aluno'); } catch (\Throwable $___) {}
            }
        }
        $this->redirect('/secure/adm/aluno/courses');
    }

    public function delete(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/aluno/courses'); return; }
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { $this->redirect('/secure/adm/aluno/courses'); return; }
        try {
            // Delete lessons first (foreign key constraint)
            Database::query('DELETE FROM lessons WHERE course_id = :id', ['id' => $id], 'aluno');
            // Delete course
            Database::query('DELETE FROM courses WHERE id = :id', ['id' => $id], 'aluno');
            $_SESSION['flash_success'] = 'Curso excluído com sucesso!';
        } catch (\Throwable $e) {
            try { Application::getInstance()->logger()->error('Aluno course delete fail: '.$e->getMessage()); } catch (\Throwable $__) {}
            $_SESSION['flash_error'] = 'Erro ao excluir curso. Tente novamente.';
        }
        $this->redirect('/secure/adm/aluno/courses');
    }
}
