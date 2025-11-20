<?php

namespace App\Controllers;

use App\Core\Application;
use App\Core\Database;

class AdminAlunoEnrollmentsController extends BaseController
{
    private function extendByMode(?string $mode, ?string $current): ?string
    {
        $base = $current ? strtotime($current) : time();
        switch ($mode) {
            case '7d': return date('Y-m-d H:i:s', $base + 7*24*3600);
            case '30d': return date('Y-m-d H:i:s', $base + 30*24*3600);
            case 'lifetime': return null; // null = sem expiração
            default: return null;
        }
    }

    public function index(): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per = 50; $off = ($page-1)*$per;
        $where = []; $params = [];
        if ($q !== '') {
            $where[] = '(u.email ILIKE :qq OR u.name ILIKE :qq OR CAST(e.user_id AS TEXT) = :qid OR c.title ILIKE :qq)';
            $params[':qq'] = '%'.$q.'%';
            $params[':qid'] = $q;
        }
        $sql = 'SELECT e.user_id, e.course_id, e.status, e.expires_at, e.created_at, c.title AS course_title, u.name AS user_name, u.email AS user_email
                FROM enrollments e
                JOIN courses c ON c.id = e.course_id
                LEFT JOIN users u ON u.id = e.user_id
                ' . ($where ? ('WHERE ' . implode(' AND ', $where)) : '') . '
                ORDER BY e.created_at DESC
                LIMIT :lim OFFSET :off';
        $params[':lim'] = $per; $params[':off'] = $off;
        // pdo named integers: use query then bind manually to avoid string casting
        $stmt = Database::connection('aluno')->prepare($sql);
        foreach ($params as $k=>$v) {
            if ($k === ':lim' || $k === ':off') { $stmt->bindValue($k, (int)$v, \PDO::PARAM_INT); }
            else { $stmt->bindValue($k, $v); }
        }
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $this->view('admin_secure/aluno_enrollments', [
            'title' => 'Matrículas (Aluno)',
            'items' => $rows,
            'q' => $q,
            'page' => $page,
            'footerVariant' => 'admin-auth'
        ]);
    }

    public function extend(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/aluno/enrollments?err=csrf'); }
        $uid = (int)($_POST['user_id'] ?? 0);
        $cid = (int)($_POST['course_id'] ?? 0);
        $mode = trim((string)($_POST['mode'] ?? ''));
        if ($uid <= 0 || $cid <= 0) { $this->redirect('/secure/adm/aluno/enrollments?err=invalid'); }
        $row = Database::fetch('SELECT expires_at FROM enrollments WHERE user_id = ? AND course_id = ? AND status = ? LIMIT 1', [$uid,$cid,'paid'], 'aluno');
        $cur = $row['expires_at'] ?? null;
        $new = $this->extendByMode($mode, $cur ?: null);
        try {
            if ($new === null && $mode !== 'lifetime') { $this->redirect('/secure/adm/aluno/enrollments?err=mode'); }
            Database::query('UPDATE enrollments SET expires_at = :e WHERE user_id = :u AND course_id = :c', ['e'=>$new, 'u'=>$uid, 'c'=>$cid], 'aluno');
        } catch (\Throwable $__) {}
        $this->redirect('/secure/adm/aluno/enrollments?ok=extended');
    }

    public function cancel(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/aluno/enrollments?err=csrf'); }
        $uid = (int)($_POST['user_id'] ?? 0);
        $cid = (int)($_POST['course_id'] ?? 0);
        if ($uid <= 0 || $cid <= 0) { $this->redirect('/secure/adm/aluno/enrollments?err=invalid'); }
        Database::query("UPDATE enrollments SET status = 'canceled' WHERE user_id = :u AND course_id = :c", ['u'=>$uid,'c'=>$cid], 'aluno');
        $this->redirect('/secure/adm/aluno/enrollments?ok=canceled');
    }

    public function reactivate(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/aluno/enrollments?err=csrf'); }
        $uid = (int)($_POST['user_id'] ?? 0);
        $cid = (int)($_POST['course_id'] ?? 0);
        if ($uid <= 0 || $cid <= 0) { $this->redirect('/secure/adm/aluno/enrollments?err=invalid'); }
        Database::query("UPDATE enrollments SET status = 'paid' WHERE user_id = :u AND course_id = :c", ['u'=>$uid,'c'=>$cid], 'aluno');
        $this->redirect('/secure/adm/aluno/enrollments?ok=reactivated');
    }
}
