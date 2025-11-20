<?php

namespace App\Controllers;

use App\Core\Application;
use App\Core\Database;
use App\Services\BunnyVideoApi;

class AdminAlunoBunnyController extends BaseController
{
    public function tools(): void
    {
        $courses = [];
        try {
            $courses = Database::fetchAll('SELECT id, title, bunny_collection_id FROM courses ORDER BY id DESC', [], 'aluno');
        } catch (\Throwable $__) {}
        $err = (string)($_GET['err'] ?? '');
        $ok  = (string)($_GET['ok'] ?? '');
        $meta = [
            'created' => (int)($_GET['created'] ?? 0),
            'updated' => (int)($_GET['updated'] ?? 0),
            'course_id' => (int)($_GET['course_id'] ?? 0),
        ];
        $this->view('admin_secure/aluno_bunny_tools', [
            'title' => 'Aluno · Bunny Tools',
            'courses' => $courses,
            'err' => $err,
            'ok' => $ok,
            'meta' => $meta,
            'footerVariant' => 'admin-auth'
        ]);
    }

    public function importCollection(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/aluno/bunny?err=csrf'); }
        $name = trim((string)($_POST['name'] ?? ''));
        $collectionId = trim((string)($_POST['collection_id'] ?? ''));
        $targetCourseId = (int)($_POST['course_id'] ?? 0);

        $lib = trim((string)($_ENV['BUNNY_STREAM_LIBRARY_ID'] ?? ''));
        $key = trim((string)($_ENV['BUNNY_STREAM_API_KEY'] ?? ''));
        if ($lib === '' || $key === '') { $this->redirect('/secure/adm/aluno/bunny?err=bunny_env'); }

        $api = new BunnyVideoApi($lib, $key);
        // Resolve collectionId by name if needed
        if ($collectionId === '' && $name !== '') {
            $found = $api->findCollectionIdByName($name);
            if ($found) { $collectionId = $found; }
        }
        if ($collectionId === '' && $name === '') { $this->redirect('/secure/adm/aluno/bunny?err=missing'); }

        // Gather lessons
        $lessons = $collectionId !== '' ? $api->gatherLessonsFromCollectionId($collectionId) : $api->gatherLessonsFromCollection($name);
        if (empty($lessons)) { $this->redirect('/secure/adm/aluno/bunny?err=empty'); }

        // Find or create course in Aluno DB
        $courseId = 0;
        try {
            if ($targetCourseId > 0) {
                $row = Database::fetch('SELECT id FROM courses WHERE id = ?', [$targetCourseId], 'aluno');
                if ($row && !empty($row['id'])) { $courseId = (int)$row['id']; }
            }
            if ($courseId <= 0 && $collectionId !== '') {
                $row = Database::fetch('SELECT id FROM courses WHERE bunny_collection_id = ? LIMIT 1', [$collectionId], 'aluno');
                if ($row && !empty($row['id'])) { $courseId = (int)$row['id']; }
            }
            if ($courseId <= 0 && $name !== '') {
                $row = Database::fetch('SELECT id FROM courses WHERE title = ? LIMIT 1', [$name], 'aluno');
                if ($row && !empty($row['id'])) { $courseId = (int)$row['id']; }
            }
        } catch (\Throwable $__) {}
        if ($courseId <= 0) {
            try {
                Database::beginTransaction('aluno');
                // Try to create with audit fields
                $rowId = 0;
                try {
                    $ins = Database::query('INSERT INTO courses (title, description, is_free, bunny_collection_id, created_at, updated_at) VALUES (:t, :d, FALSE, :bc, NOW(), NOW()) RETURNING id', [
                        't' => ($name ?: 'Curso'), 'd' => '', 'bc' => ($collectionId ?: null)
                    ], 'aluno');
                    $rowId = (int)($ins->fetchColumn() ?: 0);
                } catch (\Throwable $__) {
                    $ins = Database::query('INSERT INTO courses (title, description, is_free, bunny_collection_id) VALUES (:t, :d, FALSE, :bc) RETURNING id', [
                        't' => ($name ?: 'Curso'), 'd' => '', 'bc' => ($collectionId ?: null)
                    ], 'aluno');
                    $rowId = (int)($ins->fetchColumn() ?: 0);
                }
                Database::commit('aluno');
                $courseId = $rowId;
            } catch (\Throwable $__) {
                if (method_exists(Database::class, 'rollback')) { try { Database::rollback('aluno'); } catch (\Throwable $___) {} }
            }
        }
        if ($courseId <= 0) { $this->redirect('/secure/adm/aluno/bunny?err=create_course'); }

        $created = 0; $updated = 0;
        try {
            Database::beginTransaction('aluno');
            // 1) Remove duplicates of these videos in other courses
            $vids = array_values(array_unique(array_map(fn($x)=>$x['bunny_video_id'], $lessons)));
            if (!empty($vids)) {
                $ph = [];$bind = [':c'=>$courseId];
                foreach ($vids as $i=>$v) { $k = ':v'.$i; $ph[] = $k; $bind[$k] = $v; }
                $in = implode(',', $ph);
                Database::query("DELETE FROM lessons WHERE bunny_video_id IN ($in) AND course_id <> :c", $bind, 'aluno');
            }
            // 2) Remove lessons in this course not present in the collection
            if (!empty($vids)) {
                $ph = [];$bind = [':c'=>$courseId];
                foreach ($vids as $i=>$v) { $k = ':vv'.$i; $ph[] = $k; $bind[$k] = $v; }
                $in = implode(',', $ph);
                Database::query("DELETE FROM lessons WHERE course_id = :c AND bunny_video_id NOT IN ($in)", $bind, 'aluno');
            }
            // 3) Upsert
            foreach ($lessons as $L) {
                $exists = Database::fetch('SELECT id FROM lessons WHERE course_id = ? AND bunny_video_id = ? LIMIT 1', [$courseId, $L['bunny_video_id']], 'aluno');
                if ($exists && !empty($exists['id'])) {
                    Database::query('UPDATE lessons SET title = :t, description = :d, position = :p, duration_seconds = :dur, thumbnail_url = :th, updated_at = NOW() WHERE id = :id', [
                        't'=>$L['title'], 'd'=>$L['description'], 'p'=>$L['position'], 'dur'=>$L['duration_seconds'], 'th'=>$L['thumbnail_url'], 'id'=>(int)$exists['id']
                    ], 'aluno');
                    $updated++;
                } else {
                    Database::query('INSERT INTO lessons (course_id,title,description,position,bunny_video_id,duration_seconds,thumbnail_url,is_free_preview,created_at,updated_at) VALUES (:c,:t,:d,:p,:vid,:dur,:th,false,NOW(),NOW())', [
                        'c'=>$courseId, 't'=>$L['title'], 'd'=>$L['description'], 'p'=>$L['position'], 'vid'=>$L['bunny_video_id'], 'dur'=>$L['duration_seconds'], 'th'=>$L['thumbnail_url']
                    ], 'aluno');
                    $created++;
                }
            }
            // 4) Renumber sequential positions
            Database::query('WITH ordered AS (
                SELECT id, ROW_NUMBER() OVER (ORDER BY position, id) AS rn FROM lessons WHERE course_id = :c
            ) UPDATE lessons l SET position = o.rn FROM ordered o WHERE l.id = o.id', ['c'=>$courseId], 'aluno');
            // 5) Mark only first as free preview
            Database::query('UPDATE lessons SET is_free_preview = false WHERE course_id = :c', ['c'=>$courseId], 'aluno');
            Database::query('UPDATE lessons SET is_free_preview = true WHERE course_id = :c AND position = 1', ['c'=>$courseId], 'aluno');
            // 6) Persist bunny_collection_id
            if ($collectionId !== '') { Database::query('UPDATE courses SET bunny_collection_id = :bc, updated_at = NOW() WHERE id = :c', ['bc'=>$collectionId,'c'=>$courseId], 'aluno'); }

            Database::commit('aluno');
        } catch (\Throwable $__) {
            if (method_exists(Database::class, 'rollback')) { try { Database::rollback('aluno'); } catch (\Throwable $___) {} }
            $this->redirect('/secure/adm/aluno/bunny?err=tx');
        }

        $this->redirect('/secure/adm/aluno/bunny?ok=import&created='.$created.'&updated='.$updated.'&course_id='.$courseId);
    }

    public function importDefault(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/aluno/bunny?err=csrf'); }
        $set = [ 'Trading System Vencedor', 'Introdução ao Trading' ];
        $totCreated = 0; $totUpdated = 0; $lastCourse = 0; $err = '';
        foreach ($set as $name) {
            $_POST['name'] = $name; $_POST['collection_id'] = ''; // force name lookup
            ob_start();
            try { $this->importCollection(); } catch (\Throwable $e) { $err = 'tx'; }
            ob_end_clean();
        }
        // Apenas redirecionar com ok simples (detalhes por coleção não triviais sem refatorar para serviço interno)
        $this->redirect('/secure/adm/aluno/bunny?ok=import_default');
    }

    public function refreshThumbnails(): void
    {
        if (!$this->validateCsrf()) { $this->redirect('/secure/adm/aluno/bunny?err=csrf'); }
        $courseId = (int)($_POST['course_id'] ?? 0);
        try {
            if ($courseId > 0) {
                Database::query('UPDATE lessons SET updated_at = NOW() WHERE course_id = :c', ['c'=>$courseId], 'aluno');
            } else {
                Database::query('UPDATE lessons SET updated_at = NOW()', [], 'aluno');
            }
            $this->redirect('/secure/adm/aluno/bunny?ok=refresh');
        } catch (\Throwable $__) {
            $this->redirect('/secure/adm/aluno/bunny?err=refresh');
        }
    }
}
