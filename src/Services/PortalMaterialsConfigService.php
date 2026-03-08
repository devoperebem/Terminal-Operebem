<?php

namespace App\Services;

class PortalMaterialsConfigService
{
    private string $filePath;

    public function __construct()
    {
        $this->filePath = dirname(__DIR__, 2) . '/.config/portal_materials.json';
    }

    public function all(): array
    {
        $raw = $this->readRaw();
        $materials = [];
        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }
            $materials[] = $this->normalizeRecord($item);
        }

        usort($materials, static function (array $a, array $b): int {
            $cmpCourse = ((int)$a['course_id']) <=> ((int)$b['course_id']);
            if ($cmpCourse !== 0) {
                return $cmpCourse;
            }

            $aLesson = $a['lesson_id'] === null ? -1 : (int)$a['lesson_id'];
            $bLesson = $b['lesson_id'] === null ? -1 : (int)$b['lesson_id'];
            $cmpLesson = $aLesson <=> $bLesson;
            if ($cmpLesson !== 0) {
                return $cmpLesson;
            }

            return strcmp((string)$a['title'], (string)$b['title']);
        });

        return $materials;
    }

    public function allByCourse(int $courseId): array
    {
        return array_values(array_filter($this->all(), static function (array $item) use ($courseId): bool {
            return (int)($item['course_id'] ?? 0) === $courseId;
        }));
    }

    public function find(string $id): ?array
    {
        foreach ($this->all() as $item) {
            if ((string)($item['id'] ?? '') === $id) {
                return $item;
            }
        }
        return null;
    }

    public function create(array $input): array
    {
        $all = $this->all();
        $record = $this->normalizeRecord(array_merge($input, [
            'id' => bin2hex(random_bytes(8)),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]));

        $all[] = $record;
        $this->writeAll($all);
        return $record;
    }

    public function update(string $id, array $input): ?array
    {
        $all = $this->all();
        $updated = null;

        foreach ($all as $idx => $item) {
            if ((string)($item['id'] ?? '') !== $id) {
                continue;
            }

            $merged = array_merge($item, $input, [
                'id' => $id,
                'created_at' => (string)($item['created_at'] ?? date('Y-m-d H:i:s')),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $all[$idx] = $this->normalizeRecord($merged);
            $updated = $all[$idx];
            break;
        }

        if ($updated === null) {
            return null;
        }

        $this->writeAll($all);
        return $updated;
    }

    public function delete(string $id): ?array
    {
        $all = $this->all();
        $deleted = null;
        $filtered = [];

        foreach ($all as $item) {
            if ((string)($item['id'] ?? '') === $id) {
                $deleted = $item;
                continue;
            }
            $filtered[] = $item;
        }

        if ($deleted === null) {
            return null;
        }

        $this->writeAll($filtered);
        return $deleted;
    }

    public function buildSyncMaterialsForCourse(int $courseId): array
    {
        $materials = [];
        $mediaOrigin = new MediaOriginService();

        foreach ($this->allByCourse($courseId) as $item) {
            $isFree = (bool)$item['is_free'];
            $storagePath = trim((string)($item['storage_path'] ?? ''), '/');
            $fileUrl = (string)$item['file_url'];

            if (!$isFree && $storagePath !== '') {
                $signedUrl = $mediaOrigin->signDownloadUrl($storagePath);
                if ($signedUrl !== '') {
                    $fileUrl = $signedUrl;
                }
            }

            $materials[] = [
                'course_id' => (int)$item['course_id'],
                'lesson_id' => $item['lesson_id'] === null ? null : (int)$item['lesson_id'],
                'title' => (string)$item['title'],
                'description' => (string)$item['description'],
                'file_url' => $fileUrl,
                'file_type' => (string)$item['file_type'],
                'file_size' => (int)$item['file_size'],
                'is_free' => $isFree,
                'storage_path' => $storagePath,
                'storage_driver' => (string)($item['storage_driver'] ?? ''),
            ];
        }
        return $materials;
    }

    private function normalizeRecord(array $item): array
    {
        $fileType = trim((string)($item['file_type'] ?? ''));
        if ($fileType === '') {
            $fileType = 'pdf';
        }

        $lessonIdRaw = $item['lesson_id'] ?? null;
        $lessonId = null;
        if ($lessonIdRaw !== null && (int)$lessonIdRaw > 0) {
            $lessonId = (int)$lessonIdRaw;
        }

        return [
            'id' => (string)($item['id'] ?? ''),
            'course_id' => max(1, (int)($item['course_id'] ?? 0)),
            'lesson_id' => $lessonId,
            'title' => trim((string)($item['title'] ?? '')),
            'description' => trim((string)($item['description'] ?? '')),
            'file_url' => trim((string)($item['file_url'] ?? '')),
            'file_type' => strtolower($fileType),
            'file_size' => max(0, (int)($item['file_size'] ?? 0)),
            'is_free' => !empty($item['is_free']),
            'storage_path' => trim((string)($item['storage_path'] ?? ''), '/'),
            'storage_driver' => trim((string)($item['storage_driver'] ?? '')),
            'created_at' => (string)($item['created_at'] ?? date('Y-m-d H:i:s')),
            'updated_at' => (string)($item['updated_at'] ?? date('Y-m-d H:i:s')),
        ];
    }

    private function readRaw(): array
    {
        if (!is_file($this->filePath)) {
            return [];
        }

        $content = file_get_contents($this->filePath);
        if (!is_string($content) || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function writeAll(array $materials): void
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $json = json_encode(array_values($materials), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            throw new \RuntimeException('Falha ao serializar materiais');
        }

        file_put_contents($this->filePath, $json . PHP_EOL);
    }
}
