<?php

namespace App\Controllers;

use App\Core\Application;
use App\Core\Database;

class AdminAlunoAccessController extends BaseController
{
    private function ensureAlunoGrants(): void
    {
        try {
            // expires_at em enrollments
            Database::query("ALTER TABLE enrollments ADD COLUMN IF NOT EXISTS expires_at TIMESTAMP NULL", [], 'aluno');
        } catch (\Throwable $__) {}
        try {
            // course_access
            Database::query("CREATE TABLE IF NOT EXISTS course_access (
                user_id INTEGER NOT NULL,
                course_id INTEGER NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
                expires_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT NOW(),
                PRIMARY KEY (user_id, course_id)
            )", [], 'aluno');
            Database::query("CREATE INDEX IF NOT EXISTS idx_course_access_course ON course_access(course_id)", [], 'aluno');
        } catch (\Throwable $__) {}
        try {
            // lesson_access
            Database::query("CREATE TABLE IF NOT EXISTS lesson_access (
                user_id INTEGER NOT NULL,
                lesson_id INTEGER NOT NULL REFERENCES lessons(id) ON DELETE CASCADE,
                expires_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT NOW(),
                PRIMARY KEY (user_id, lesson_id)
            )", [], 'aluno');
            Database::query("CREATE INDEX IF NOT EXISTS idx_lesson_access_lesson ON lesson_access(lesson_id)", [], 'aluno');
        } catch (\Throwable $__) {}
    }
    public function index(): void
    {
        $this->ensureAlunoGrants();
        $courses = [];
        $lessons = [];
        $selectedCourseId = (int)($_GET['course_id'] ?? 0);
        $selectedUserId = (int)($_GET['user_id'] ?? 0);
        try { $courses = Database::fetchAll('SELECT id, title FROM courses ORDER BY title ASC', [], 'aluno'); } catch (\Throwable $__) {}
        if ($selectedCourseId > 0) {
            try { $lessons = Database::fetchAll('SELECT id, title, position FROM lessons WHERE course_id = :c ORDER BY position ASC', ['c'=>$selectedCourseId], 'aluno'); } catch (\Throwable $__) {}
        }
        $courseGrants = [];
        $lessonGrants = [];
        if ($selectedUserId > 0) {
            try {
                $courseGrants = Database::fetchAll('SELECT ca.user_id, ca.course_id, ca.expires_at, c.title FROM course_access ca JOIN courses c ON c.id = ca.course_id WHERE ca.user_id = :u ORDER BY c.title ASC', ['u'=>$selectedUserId], 'aluno');
            } catch (\Throwable $__) {}
            try {
                $lessonGrants = Database::fetchAll('SELECT la.user_id, la.lesson_id, la.expires_at, l.title, l.position, c.title AS course_title, l.course_id FROM lesson_access la JOIN lessons l ON l.id = la.lesson_id JOIN courses c ON c.id = l.course_id WHERE la.user_id = :u ORDER BY c.title ASC, l.position ASC', ['u'=>$selectedUserId], 'aluno');
            } catch (\Throwable $__) {}
        }
        $this->view('admin_secure/aluno_access', [
            'title' => 'Acessos (Aluno)',
            'courses' => $courses,
            'lessons' => $lessons,
            'selectedCourseId' => $selectedCourseId,
            'selectedUserId' => $selectedUserId,
            'courseGrants' => $courseGrants,
            'lessonGrants' => $lessonGrants,
            'footerVariant' => 'admin-auth'
        ]);
    }

    public function grantCourse(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/aluno/access'); }
        $this->ensureAlunoGrants();
        $email = trim((string)($_POST['email'] ?? ''));
        $userId = (int)($_POST['user_id'] ?? 0);
        $courseId = (int)($_POST['course_id'] ?? 0);
        $expiresAtStr = trim((string)($_POST['expires_at'] ?? ''));
        $lifetime = isset($_POST['lifetime']);
        $ttl = trim((string)($_POST['ttl'] ?? ''));
        $tzOffsetMin = (int)($_POST['tz_offset'] ?? 0);
        if ($userId <= 0 && $email !== '') {
            try {
                $u = Database::fetch('SELECT id FROM users WHERE email = ? AND deleted_at IS NULL', [$email]);
                $userId = (int)($u['id'] ?? 0);
            } catch (\Throwable $__) { $userId = 0; }
        }
        if ($userId <= 0 || $courseId <= 0) { $this->redirect('/secure/adm/aluno/access?err=invalid'); }
        $expiresAt = null;
        if ($lifetime || $ttl === 'lifetime') {
            $expiresAt = null;
        } elseif ($ttl === '3m') {
            $expiresAt = date('Y-m-d H:i:s', time() + 3*60);
        } elseif ($ttl === '7d') {
            $expiresAt = date('Y-m-d H:i:s', time() + 7*24*3600);
        } elseif ($ttl === '30d') {
            $expiresAt = date('Y-m-d H:i:s', time() + 30*24*3600);
        } elseif ($expiresAtStr !== '') {
            $ts = strtotime($expiresAtStr);
            if ($ts !== false) {
                // Ajustar para UTC: offset do navegador (minutos até UTC)
                $tsUtc = $ts + ($tzOffsetMin * 60);
                $expiresAt = date('Y-m-d H:i:s', $tsUtc);
            }
        }
        try {
            $sql = 'INSERT INTO course_access (user_id, course_id, expires_at, created_at) VALUES (:u,:c,:e,NOW()) ON CONFLICT (user_id,course_id) DO UPDATE SET expires_at = EXCLUDED.expires_at';
            Database::query($sql, ['u'=>$userId,'c'=>$courseId,'e'=>$expiresAt], 'aluno');
        } catch (\Throwable $e) {
            try { Application::getInstance()->logger()->error('grantCourse error: '.$e->getMessage(), ['user_id'=>$userId,'course_id'=>$courseId]); } catch (\Throwable $__) {}
        }
        $this->redirect('/secure/adm/aluno/access?ok=1');
    }

    public function grantLesson(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/aluno/access'); }
        $this->ensureAlunoGrants();
        $email = trim((string)($_POST['email'] ?? ''));
        $userId = (int)($_POST['user_id'] ?? 0);
        $courseId = (int)($_POST['course_id'] ?? 0);
        $lessonId = (int)($_POST['lesson_id'] ?? 0);
        $expiresAtStr = trim((string)($_POST['expires_at'] ?? ''));
        $lifetime = isset($_POST['lifetime']);
        $ttl = trim((string)($_POST['ttl'] ?? ''));
        $tzOffsetMin = (int)($_POST['tz_offset'] ?? 0);
        if ($userId <= 0 && $email !== '') {
            try {
                $u = Database::fetch('SELECT id FROM users WHERE email = ? AND deleted_at IS NULL', [$email]);
                $userId = (int)($u['id'] ?? 0);
            } catch (\Throwable $__) { $userId = 0; }
        }
        if ($userId <= 0 || $courseId <= 0 || $lessonId <= 0) { $this->redirect('/secure/adm/aluno/access?err=invalid'); }
        $expiresAt = null;
        if ($lifetime || $ttl === 'lifetime') {
            $expiresAt = null;
        } elseif ($ttl === '3m') {
            $expiresAt = date('Y-m-d H:i:s', time() + 3*60);
        } elseif ($ttl === '7d') {
            $expiresAt = date('Y-m-d H:i:s', time() + 7*24*3600);
        } elseif ($ttl === '30d') {
            $expiresAt = date('Y-m-d H:i:s', time() + 30*24*3600);
        } elseif ($expiresAtStr !== '') {
            $ts = strtotime($expiresAtStr);
            if ($ts !== false) {
                $tsUtc = $ts + ($tzOffsetMin * 60);
                $expiresAt = date('Y-m-d H:i:s', $tsUtc);
            }
        }
        try {
            // Validar se a lesson pertence ao curso informado (opcional)
            $L = Database::fetch('SELECT id FROM lessons WHERE id = :l AND course_id = :c', ['l'=>$lessonId,'c'=>$courseId], 'aluno');
            if (!$L) { $this->redirect('/secure/adm/aluno/access?err=lesson'); }
            $sql = 'INSERT INTO lesson_access (user_id, lesson_id, expires_at, created_at) VALUES (:u,:l,:e,NOW()) ON CONFLICT (user_id,lesson_id) DO UPDATE SET expires_at = EXCLUDED.expires_at';
            Database::query($sql, ['u'=>$userId,'l'=>$lessonId,'e'=>$expiresAt], 'aluno');
        } catch (\Throwable $e) {
            try { Application::getInstance()->logger()->error('grantLesson error: '.$e->getMessage(), ['user_id'=>$userId,'lesson_id'=>$lessonId]); } catch (\Throwable $__) {}
        }
        $this->redirect('/secure/adm/aluno/access?ok=1');
    }

    public function revokeCourse(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/aluno/access'); }
        $uid = (int)($_POST['user_id'] ?? 0);
        $cid = (int)($_POST['course_id'] ?? 0);
        if ($uid <= 0 || $cid <= 0) { $this->redirect('/secure/adm/aluno/access?err=invalid'); }
        try { Database::query('DELETE FROM course_access WHERE user_id = :u AND course_id = :c', ['u'=>$uid,'c'=>$cid], 'aluno'); } catch (\Throwable $__) {}
        $this->redirect('/secure/adm/aluno/access?ok=revoked&user_id=' . $uid);
    }

    public function revokeLesson(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/aluno/access'); }
        $uid = (int)($_POST['user_id'] ?? 0);
        $lid = (int)($_POST['lesson_id'] ?? 0);
        if ($uid <= 0 || $lid <= 0) { $this->redirect('/secure/adm/aluno/access?err=invalid'); }
        try { Database::query('DELETE FROM lesson_access WHERE user_id = :u AND lesson_id = :l', ['u'=>$uid,'l'=>$lid], 'aluno'); } catch (\Throwable $__) {}
        $this->redirect('/secure/adm/aluno/access?ok=revoked&user_id=' . $uid);
    }

    public function extendCourse(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/aluno/access'); }
        $uid = (int)($_POST['user_id'] ?? 0);
        $cid = (int)($_POST['course_id'] ?? 0);
        $mode = trim((string)($_POST['mode'] ?? '7d'));
        if ($uid <= 0 || $cid <= 0) { $this->redirect('/secure/adm/aluno/access?err=invalid'); }
        $expires = null;
        if ($mode === 'lifetime') {
            $expires = null;
        } else {
            $add = '+7 days';
            if ($mode === '30d') $add = '+30 days';
            $expires = date('Y-m-d H:i:s', strtotime($add));
        }
        try { Database::query('INSERT INTO course_access (user_id,course_id,expires_at,created_at) VALUES (:u,:c,:e,NOW()) ON CONFLICT (user_id,course_id) DO UPDATE SET expires_at = EXCLUDED.expires_at', ['u'=>$uid,'c'=>$cid,'e'=>$expires], 'aluno'); } catch (\Throwable $__) {}
        $this->redirect('/secure/adm/aluno/access?ok=extended&user_id=' . $uid);
    }

    public function extendLesson(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/aluno/access'); }
        $uid = (int)($_POST['user_id'] ?? 0);
        $lid = (int)($_POST['lesson_id'] ?? 0);
        $mode = trim((string)($_POST['mode'] ?? '7d'));
        if ($uid <= 0 || $lid <= 0) { $this->redirect('/secure/adm/aluno/access?err=invalid'); }
        $expires = null;
        if ($mode === 'lifetime') {
            $expires = null;
        } else {
            $add = '+7 days';
            if ($mode === '30d') $add = '+30 days';
            $expires = date('Y-m-d H:i:s', strtotime($add));
        }
        try { Database::query('INSERT INTO lesson_access (user_id,lesson_id,expires_at,created_at) VALUES (:u,:l,:e,NOW()) ON CONFLICT (user_id,lesson_id) DO UPDATE SET expires_at = EXCLUDED.expires_at', ['u'=>$uid,'l'=>$lid,'e'=>$expires], 'aluno'); } catch (\Throwable $__) {}
        $this->redirect('/secure/adm/aluno/access?ok=extended&user_id=' . $uid);
    }
}
