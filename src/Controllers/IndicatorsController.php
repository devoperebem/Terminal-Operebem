<?php

namespace App\Controllers;

class IndicatorsController extends BaseController
{
    public function feeling(): void
    {
        $this->view('app/indicators/feeling', [
            'title' => 'Indicadores - Sentimento'
        ]);
    }

    public function fed(): void
    {
        $this->view('app/indicators/fed', [
            'title' => 'Indicadores - Federal Reserve (US)'
        ]);
    }

    public function marketClock(): void
    {
        // Renderizar página standalone sem layout
        $viewPath = __DIR__ . '/../Views/app/market-clock-fullscreen.php';
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            http_response_code(404);
            echo 'Página não encontrada';
        }
        exit;
    }
}
