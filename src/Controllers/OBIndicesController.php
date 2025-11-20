<?php

namespace App\Controllers;

use App\Services\OBIndicesService;
use App\Core\Application;
use App\Services\CustomIndicesService;

/**
 * OB Indices Controller
 * Gerencia os índices customizados da Operebem
 */
class OBIndicesController extends BaseController
{
    private OBIndicesService $indicesService;
    
    public function __construct()
    {
        parent::__construct();
        $this->indicesService = new OBIndicesService();
    }
    
    /**
     * Página principal dos índices OB
     */
    public function index(): void
    {
        try {
            // Calcular todos os índices
            $indices = $this->indicesService->calculateAllIndices();
            
            // Preparar dados para a view
            $this->view('indices/index', [
                'title' => 'OB Índices - Terminal Operebem',
                'indices' => $indices,
            ]);
            
        } catch (\Exception $e) {
            Application::getInstance()->logger()->error('[OB_INDICES] Erro ao carregar página: ' . $e->getMessage());
            
            $this->view('indices/index', [
                'title' => 'OB Índices - Terminal Operebem',
                'indices' => null,
                'error' => 'Erro ao carregar índices. Tente novamente em alguns instantes.',
            ]);
        }
    }
    
    /**
     * API: Retorna todos os índices em JSON
     * GET /api/indices
     */
    public function getIndices(): void
    {
        try {
            $indices = $this->indicesService->calculateAllIndices();
            
            Application::getInstance()->logger()->info('[OB_INDICES] API chamada com sucesso');
            
            header('Cache-Control: private, max-age=60');
            $this->json([
                'success' => true,
                'data' => $indices,
            ]);
            
        } catch (\Exception $e) {
            Application::getInstance()->logger()->error('[OB_INDICES] Erro na API: ' . $e->getMessage());
            
            $this->json([
                'success' => false,
                'error' => 'Erro ao calcular índices',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * API: Retorna um índice específico
     * GET /api/indices/{name}
     */
    public function getIndex(string $name): void
    {
        try {
            $name = strtoupper($name);
            
            if (!in_array($name, ['IFPV', 'IFPP', 'IDFE', 'IDFE2'])) {
                $this->json([
                    'success' => false,
                    'error' => 'Índice não encontrado',
                ], 404);
                return;
            }
            
            $index = $this->indicesService->getIndexDetails($name);
            
            if (!$index) {
                header('Cache-Control: private, max-age=30');
                $this->json([
                    'success' => false,
                    'error' => 'Índice indisponível',
                ], 503);
                return;
            }
            
            Application::getInstance()->logger()->info('[OB_INDICES] API índice específico: ' . $name);
            
            header('Cache-Control: private, max-age=60');
            $this->json([
                'success' => true,
                'data' => $index,
            ]);
            
        } catch (\Exception $e) {
            Application::getInstance()->logger()->error('[OB_INDICES] Erro na API (índice específico): ' . $e->getMessage());
            
            $this->json([
                'success' => false,
                'error' => 'Erro ao calcular índice',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function selfTest(): void
    {
        try {
            $svc = new CustomIndicesService();
            $rows = $svc->getIndicesByNames(['IFPV','IFPP','IDFE','IDFE2','ORM']);
            $indices = [];
            foreach (['IFPV','IFPP','IDFE','IDFE2'] as $n) {
                $row = $rows[$n] ?? null;
                $pl = is_array($row['payload'] ?? null) ? $row['payload'] : null;
                $ok = is_array($pl) && is_array($pl['components'] ?? null) && count($pl['components']) > 0;
                if ($ok && $n === 'IFPP') {
                    $ok = $ok && isset($pl['components']['MAJORS']['subcomponents']);
                } elseif ($ok && $n === 'IDFE') {
                    $ok = $ok && isset($pl['components']['EMERGENTES']['subcomponents']);
                } elseif ($ok && $n === 'IDFE2') {
                    $ok = $ok && isset($pl['components']['EMERG_CORR']['subcomponents']);
                }
                $indices[$n] = [
                    'ok' => (bool)$ok,
                    'value' => $row['value'] ?? null,
                    'updated_at' => $row['updated_at'] ?? null,
                    'component_count' => is_array($pl['components'] ?? null) ? count($pl['components']) : 0,
                ];
            }
            $ormRow = $rows['ORM'] ?? null;
            $ormPl = is_array($ormRow['payload'] ?? null) ? $ormRow['payload'] : null;
            $assets = $ormPl['data']['assets'] ?? [];
            $ormOk = is_array($ormPl) && isset($assets['eem']) && isset($assets['dxy']);
            $report = [
                'success' => ($indices['IFPV']['ok'] ?? false) && ($indices['IFPP']['ok'] ?? false) && ($indices['IDFE']['ok'] ?? false) && ($indices['IDFE2']['ok'] ?? false) && $ormOk,
                'ts' => time(),
                'indices' => $indices,
                'orm' => [
                    'ok' => (bool)$ormOk,
                    'updated_at' => $ormRow['updated_at'] ?? null,
                    'coverage' => $ormRow['coverage'] ?? null,
                    'assets_present' => array_keys((array)$assets),
                ],
            ];
            $json = json_encode($report, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>OB Indices Self-Test</title><style>body{font-family:system-ui,Segoe UI,Arial,sans-serif;padding:16px;background:#0b0f14;color:#e5e7eb}button{background:#2563eb;color:#fff;border:none;border-radius:8px;padding:10px 14px;cursor:pointer}pre{white-space:pre-wrap;background:#0f172a;color:#e2e8f0;padding:12px;border-radius:8px;border:1px solid #1f2937}</style></head><body>';
            echo '<h3>OB Indices Self-Test</h3><p>Validação dos índices e ORM a partir de custom_indices.</p>';
            echo '<button onclick="navigator.clipboard.writeText(document.getElementById(\'out\').textContent)">Copiar JSON</button>';
            echo '<pre id="out">'.htmlspecialchars((string)$json, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8').'</pre>';
            echo '</body></html>';
        } catch (\Throwable $e) {
            Application::getInstance()->logger()->error('[OB_INDICES] SelfTest error: '.$e->getMessage());
            http_response_code(500);
            echo 'Erro ao executar self-test';
        }
    }
}
