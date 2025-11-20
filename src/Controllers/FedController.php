<?php

namespace App\Controllers;

use App\Services\FedWatchService;

class FedController extends BaseController
{
    public function probabilities(): void
    {
        $date = isset($_GET['date']) ? trim((string)$_GET['date']) : '';
        try {
            $svc = new FedWatchService();
            $data = $svc->getProbabilities($date ?: null);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $t) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(502);
            echo json_encode(['ok' => false, 'error' => 'Upstream error']);
        }
    }
}
