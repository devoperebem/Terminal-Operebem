<?php
namespace App\Services;

class BunnyVideoApi
{
    private string $libraryId;
    private string $apiKey;

    public function __construct(?string $libraryId = null, ?string $apiKey = null)
    {
        $this->libraryId = trim((string)($libraryId ?? ($_ENV['BUNNY_STREAM_LIBRARY_ID'] ?? '')));
        $this->apiKey     = trim((string)($apiKey     ?? ($_ENV['BUNNY_STREAM_API_KEY'] ?? '')));
    }

    private function request(string $method, string $path, array $query = []): array
    {
        if ($this->libraryId === '' || $this->apiKey === '') return [];
        $base = 'https://video.bunnycdn.com/library/' . rawurlencode($this->libraryId);
        $url  = $base . '/' . ltrim($path, '/');
        if (!empty($query)) {
            $qs = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
            $url .= '?' . $qs;
        }
        $opts = [
            'http' => [
                'method' => $method,
                'header' => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'AccessKey: ' . $this->apiKey,
                ],
                'ignore_errors' => true,
                'timeout' => 20,
            ]
        ];
        $ctx = stream_context_create($opts);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false || $raw === '') return [];
        $json = json_decode($raw, true);
        return is_array($json) ? $json : [];
    }

    public function listCollections(): array
    {
        $res = $this->request('GET', 'collections');
        if (isset($res['items']) && is_array($res['items'])) return $res['items'];
        if (isset($res[0]) || empty($res)) return $res;
        return [];
    }

    public function findCollectionIdByName(string $name): ?string
    {
        $name = trim($name);
        if ($name === '') return null;
        $items = $this->listCollections();
        foreach ($items as $it) {
            $title = (string)($it['name'] ?? $it['title'] ?? '');
            if ($title !== '' && strcasecmp($title, $name) === 0) {
                $id = (string)($it['id'] ?? $it['guid'] ?? '');
                return $id !== '' ? $id : null;
            }
        }
        return null;
    }

    private function normalizeCollectionId(?array $video): string
    {
        if (!$video) return '';
        $cands = ['collectionId','collectionGuid','collection_id','collectionGUID','collection'];
        foreach ($cands as $k) {
            if (!array_key_exists($k, $video)) continue;
            $val = $video[$k];
            if (is_string($val) && $val !== '') return strtolower($val);
            if (is_array($val)) {
                $id = (string)($val['id'] ?? $val['guid'] ?? '');
                if ($id !== '') return strtolower($id);
            }
        }
        return '';
    }

    private function extractOrderFromTitle(string $title): ?int
    {
        $title = trim($title);
        if ($title === '') return null;
        if (preg_match('/^(?:\s*(?:ep(?:is[oó]dio)?\s*)?)(\d{1,3})(?=[)\-._\s])/iu', $title, $m)) {
            return (int)$m[1];
        }
        if (preg_match('/\b(?:ep|epis[oó]dio)\s*(\d{1,3})\b/iu', $title, $m)) {
            return (int)$m[1];
        }
        return null;
    }

    public function listVideosByCollectionId(string $collectionId): array
    {
        $videos = [];
        $page = 1;
        do {
            $res = $this->request('GET', 'videos', [
                'collectionId'   => $collectionId,
                'collectionGuid' => $collectionId,
                'page' => $page,
                'itemsPerPage' => 100,
            ]);
            $items = isset($res['items']) && is_array($res['items']) ? $res['items'] : (is_array($res) ? $res : []);
            foreach ($items as $v) { $videos[] = $v; }
            $hasMore = isset($res['currentPage'], $res['totalPages']) ? ((int)$res['currentPage'] < (int)$res['totalPages']) : (count($items) === 100);
            $page++;
        } while ($hasMore && $page < 50);

        $lcid = strtolower($collectionId);
        $videos = array_values(array_filter($videos, function($v) use ($lcid) {
            $vidCid = $this->normalizeCollectionId(is_array($v) ? $v : []);
            return ($vidCid !== '' ? $vidCid === $lcid : true);
        }));

        if (empty($videos)) {
            $fallback = $this->request('GET', 'collections/' . rawurlencode($collectionId) . '/videos');
            $videos = isset($fallback['items']) && is_array($fallback['items']) ? $fallback['items'] : (is_array($fallback) ? $fallback : []);
        }

        $videos = array_values(array_filter($videos, function($v) use ($lcid) {
            $cid = $this->normalizeCollectionId(is_array($v) ? $v : []);
            return $cid === '' ? true : ($cid === $lcid);
        }));

        return $videos;
    }

    public function gatherLessonsFromCollection(string $collectionName): array
    {
        $cid = $this->findCollectionIdByName($collectionName);
        if (!$cid) return [];
        return $this->gatherLessonsFromCollectionId($cid);
    }

    public function gatherLessonsFromCollectionId(string $collectionId): array
    {
        $collectionId = trim($collectionId);
        if ($collectionId === '') return [];
        $items = $this->listVideosByCollectionId($collectionId);
        $host = trim((string)($_ENV['BUNNY_CDN_HOSTNAME'] ?? ''));
        $lessons = [];
        $pos = 1;
        foreach ($items as $it) {
            $vid = (string)($it['guid'] ?? $it['id'] ?? $it['videoGuid'] ?? '');
            if ($vid === '') continue;
            $title = (string)($it['title'] ?? $it['name'] ?? 'Aula');
            $desc = (string)($it['meta'] ?? $it['description'] ?? '');
            $dur  = (int)($it['length'] ?? $it['duration'] ?? 0);
            $order = (int)($it['order'] ?? $it['index'] ?? $it['position'] ?? 0);
            if ($order <= 0) {
                $guess = $this->extractOrderFromTitle($title);
                if (is_int($guess) && $guess > 0) { $order = $guess; }
            }
            $dt  = (string)($it['dateUploaded'] ?? $it['createdAt'] ?? $it['dateCreated'] ?? '');
            $dtScore = $dt !== '' ? strtotime($dt) : 0;
            $thumb = (string)($it['thumbnailUrl'] ?? $it['thumbnail'] ?? $it['preview'] ?? '');
            if ($thumb === '' && $host !== '') { $thumb = 'https://' . $host . '/' . rawurlencode($vid) . '/thumbnail.jpg'; }
            $lessons[] = [
                'title' => $title,
                'description' => $desc,
                'position' => $order > 0 ? $order : $pos,
                'bunny_video_id' => $vid,
                'duration_seconds' => $dur > 0 ? $dur : null,
                'thumbnail_url' => $thumb,
                '_date_score' => $dtScore,
            ];
            $pos++;
        }
        usort($lessons, function($a,$b){
            if ($a['position'] === $b['position']) { return ($a['_date_score'] <=> $b['_date_score']); }
            return $a['position'] <=> $b['position'];
        });
        $i = 1; foreach ($lessons as &$L) { $L['position'] = $i++; unset($L['_date_score']); } unset($L);
        return $lessons;
    }
}
