<?php
namespace App\Controllers;

use App\Services\TrendsService;
use App\Core\Application;

class TrendsController extends BaseController
{
    private TrendsService $svc;

    public function __construct()
    {
        $this->svc = new TrendsService();
    }

    private function paramKeywords(): array
    {
        // Simplificado: travado em uma única série. Trocar aqui caso queira outra keyword.
        // Padrão escolhido: 'Ibovespa' (texto simples, mais robusto que MID).
        return ['Ibovespa'];
    }

    private function timeParam(): string
    {
        $allowed = ['today 5-y','today 12-m','today 3-m','today 1-m','now 5-y'];
        $t = trim((string)($_GET['time'] ?? 'today 5-y'));
        if ($t === 'now 5-y') $t = 'today 5-y';
        if (!in_array($t, $allowed, true)) return 'today 5-y';
        return $t;
    }

    private function geoParam(): string
    {
        $g = trim((string)($_GET['geo'] ?? 'BR'));
        return $g ?: 'BR';
    }

    private function rateLimitOrFail(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!$this->svc->rateLimitCheck($ip)) {
            http_response_code(429);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'rate_limited']);
            exit;
        }
    }

    public function series(): void
    {
        $this->rateLimitOrFail();
        $log = Application::getInstance()->logger();
        $kw = $this->paramKeywords(); $geo = $this->geoParam(); $time = $this->timeParam();
        $log->info('[Trends] /series', ['kw' => $kw, 'geo' => $geo, 'time' => $time, 'ip' => ($_SERVER['REMOTE_ADDR'] ?? '-')]);
        try {
            $data = $this->svc->timeseries($kw, $geo, $time);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            $log->error('[Trends] /series error: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Falha ao consultar séries do Trends.'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        }
    }

    public function regions(): void
    {
        $this->rateLimitOrFail();
        $log = Application::getInstance()->logger();
        $kw = $this->paramKeywords(); $geo = $this->geoParam(); $time = $this->timeParam();
        $log->info('[Trends] /regions', ['kw' => $kw, 'geo' => $geo, 'time' => $time, 'ip' => ($_SERVER['REMOTE_ADDR'] ?? '-')]);
        try {
            $data = $this->svc->comparedGeo($kw, $geo, $time);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            $log->error('[Trends] /regions error: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Falha ao consultar regiões do Trends.'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        }
    }

    public function related(): void
    {
        $this->rateLimitOrFail();
        $log = Application::getInstance()->logger();
        $kw = $this->paramKeywords(); $geo = $this->geoParam(); $time = $this->timeParam();
        $log->info('[Trends] /related', ['kw' => $kw, 'geo' => $geo, 'time' => $time, 'ip' => ($_SERVER['REMOTE_ADDR'] ?? '-')]);
        try {
            $data = $this->svc->related($kw, $geo, $time);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            $log->error('[Trends] /related error: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Falha ao consultar consultas relacionadas do Trends.'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        }
    }
}
