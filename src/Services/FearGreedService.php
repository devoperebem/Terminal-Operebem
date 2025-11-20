<?php

namespace App\Services;

use App\Core\Application;

class FearGreedService
{
    private string $baseUrl;
    private string $apiKey;
    private int $cacheTtl;
    private int $timeout;
    private int $connectTimeout;
    private string $cacheDir;
    private $httpHandler; // callable|null for tests/mocks

    public function __construct(array $options = [])
    {
        $this->baseUrl = rtrim($options['base_url'] ?? ($_ENV['FG_BASE_URL'] ?? 'https://api.operebem.com.br/v2/cnn-fear-greed'), '/');
        $this->apiKey = $options['api_key'] ?? ($_ENV['FG_API_KEY'] ?? '');
        $this->cacheTtl = (int)($options['cache_ttl'] ?? ($_ENV['FG_CACHE_TTL'] ?? 300));
        $this->timeout = (int)($options['timeout'] ?? ($_ENV['FG_TIMEOUT'] ?? 8));
        $this->connectTimeout = (int)($options['connect_timeout'] ?? ($_ENV['FG_CONNECT_TIMEOUT'] ?? 5));
        $basePath = Application::getInstance()->getBasePath();
        $this->cacheDir = $options['cache_dir'] ?? ($basePath . '/storage/cache/fear_greed');
        $this->httpHandler = $options['http_handler'] ?? null;

        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    private function isV2(): bool
    {
        return str_contains($this->baseUrl, '/v2/');
    }

    public function getCurrent(): array
    {
        return $this->requestJson('current');
    }

    public function getSummary(?string $date = null): array
    {
        // V2 não oferece summary por data e a UI espera campos do "current"
        if ($this->isV2()) {
            $res = $this->requestJson('current');
            if (($res['success'] ?? false) && isset($res['data']['current']) && is_array($res['data']['current'])) {
                $cur = $res['data']['current'];
                // Normalizar para a UI existente: data.score, data.rating, previous_*
                return [
                    'success' => true,
                    'data' => [
                        'score' => $cur['score'] ?? null,
                        'rating' => $cur['rating'] ?? null,
                        'timestamp' => $cur['updated_at'] ?? ($res['timestamp'] ?? ($cur['date'] ?? null)),
                        'previous_close' => $cur['previous_close'] ?? null,
                        'previous_1_week' => $cur['previous_1_week'] ?? null,
                        'previous_1_month' => $cur['previous_1_month'] ?? null,
                        'previous_1_year' => $cur['previous_1_year'] ?? null,
                    ],
                ];
            }
            return $res;
        }
        if ($date !== null) {
            if (!$this->isValidDate($date)) {
                return [ 'success' => false, 'message' => 'Data inválida. Use formato Y-m-d.' ];
            }
            return $this->requestJson('summary/' . $date);
        }
        return $this->requestJson('summary');
    }

    public function getByDate(string $date): array
    {
        if (!$this->isValidDate($date)) {
            return [ 'success' => false, 'message' => 'Data inválida. Use formato Y-m-d.' ];
        }
        return $this->requestJson('date/' . $date);
    }

    public function getHistorical(string $startDate, string $endDate, ?int $limit = null): array
    {
        if (!$this->isValidDate($startDate) || !$this->isValidDate($endDate)) {
            return [ 'success' => false, 'message' => 'Datas inválidas. Use formato Y-m-d.' ];
        }
        $params = ['start_date' => $startDate, 'end_date' => $endDate];
        // Permitir override explícito de limit (para V2 retornar >30 dias)
        if ($limit !== null) {
            $params['limit'] = max(1, min(365, (int)$limit));
        } else {
            // fallback: para não-V2, estimar limite pela janela
            if (!$this->isV2()) {
                try {
                    $d1 = new \DateTime($startDate);
                    $d2 = new \DateTime($endDate);
                    $days = max(1, $d1->diff($d2)->days + 1);
                    $params['limit'] = min(365, $days);
                } catch (\Throwable $t) {
                    $params['limit'] = 365;
                }
            }
        }
        $qs = http_build_query($params);
        // histórico pode ser mais pesado/lento: aumentar timeout
        return $this->requestJson('historical', $qs, [
            'timeout' => max($this->timeout, 15),
            'connect_timeout' => max($this->connectTimeout, 8),
        ]);
    }

    public function getIndicator(string $indicator, ?string $date = null): array
    {
        $indicator = trim($indicator);
        if ($indicator === '') {
            return [ 'success' => false, 'message' => 'Indicador é obrigatório.' ];
        }
        // V2: não há /indicator/{slug}; usar /indicators e mapear.
        if ($this->isV2()) {
            $qs = '';
            if ($date !== null) {
                if (!$this->isValidDate($date)) {
                    return [ 'success' => false, 'message' => 'Data inválida. Use formato Y-m-d.' ];
                }
                $qs = http_build_query(['date' => $date]);
            }
            $res = $this->requestJson('indicators', $qs);
            if (!($res['success'] ?? false) || !isset($res['data']['indicators']) || !is_array($res['data']['indicators'])) {
                return $res;
            }
            $map = [
                'market-momentum-sp500' => 'market_momentum_sp500',
                'market-momentum-sp125' => 'market_momentum_sp125',
                'stock-price-strength' => 'stock_price_strength',
                'stock-price-breadth' => 'stock_price_breadth',
                'put-call-options' => 'put_call_options',
                'market-volatility-vix' => 'market_volatility_vix',
                'vix-50-day' => 'market_volatility_vix_50',
                'junk-bond-demand' => 'junk_bond_demand',
                'safe-haven-demand' => 'safe_haven_demand',
            ];
            $table = $map[$indicator] ?? $indicator;
            $found = null;
            foreach ($res['data']['indicators'] as $it) {
                if (($it['table'] ?? '') === $table || str_contains(strtolower($it['name'] ?? ''), str_replace('-', ' ', strtolower($indicator)))) {
                    $found = $it;
                    break;
                }
            }
            if ($found === null) {
                return [ 'success' => false, 'message' => 'Indicador não encontrado na resposta V2.' ];
            }
            $dateStr = $found['date'] ?? date('Y-m-d');
            $ts = strtotime($dateStr . ' 00:00:00 UTC');
            $ms = $ts ? ($ts * 1000) : (time() * 1000);
            return [
                'success' => true,
                'data' => [
                    'indicator' => $indicator,
                    'data' => [ 'data' => [ [ 'x' => $ms, 'y' => (float)($found['current_value'] ?? 0) ] ] ],
                ],
            ];
        }
        if ($date !== null) {
            if (!$this->isValidDate($date)) {
                return [ 'success' => false, 'message' => 'Data inválida. Use formato Y-m-d.' ];
            }
            return $this->requestJson('indicator/' . rawurlencode($indicator) . '/' . $date);
        }
        return $this->requestJson('indicator/' . rawurlencode($indicator));
    }

    public function getIndicators(?string $date = null): array
    {
        $qs = '';
        if ($date !== null) {
            if (!$this->isValidDate($date)) {
                return [ 'success' => false, 'message' => 'Data inválida. Use formato Y-m-d.' ];
            }
            $qs = http_build_query(['date' => $date]);
        }
        return $this->requestJson('indicators', $qs);
    }

    public function getIndicatorsHistorical(?string $startDate = null, ?string $endDate = null, ?int $limit = 365): array
    {
        $params = [];
        if ($startDate !== null) {
            if (!$this->isValidDate($startDate)) {
                return [ 'success' => false, 'message' => 'Data inicial inválida. Use formato Y-m-d.' ];
            }
            $params['start_date'] = $startDate;
        }
        if ($endDate !== null) {
            if (!$this->isValidDate($endDate)) {
                return [ 'success' => false, 'message' => 'Data final inválida. Use formato Y-m-d.' ];
            }
            $params['end_date'] = $endDate;
        }
        if ($limit !== null) {
            $params['limit'] = max(1, min(365, (int)$limit));
        }
        $qs = http_build_query($params);
        return $this->requestJson('historical-all-indicators', $qs);
    }

    private function isValidDate(string $date): bool
    {
        // Validate both format and actual calendar date
        $dt = \DateTime::createFromFormat('Y-m-d', $date);
        return $dt !== false && $dt->format('Y-m-d') === $date;
    }

    private function requestJson(string $path, string $queryString = '', ?array $opts = null, string $method = 'GET'): array
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        if ($queryString !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?') . $queryString;
        }

        $method = strtoupper($method);
        $useCache = ($method === 'GET');
        $cacheKey = $this->cacheKey($url);
        if ($useCache) {
            $cached = $this->readCache($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $logger = Application::getInstance()->logger();
        $safeUrl = $url; // sem chave (vai via header)
        $start = microtime(true);
        $logger->info('[FG] Request: ' . $method . ' ' . $safeUrl);

        try {
            $timeout = $opts['timeout'] ?? $this->timeout;
            $connectTimeout = $opts['connect_timeout'] ?? $this->connectTimeout;
            [$status, $body] = $this->doHttpRequest($url, $timeout, $connectTimeout, $method);
            $durationMs = (int)round((microtime(true) - $start) * 1000);
            $logger->info('[FG] Response: HTTP ' . $status . ' in ' . $durationMs . 'ms, bytes=' . strlen((string)$body));

            if ($status < 200 || $status >= 300) {
                // tentar extrair mensagem de erro
                $message = 'Erro na API Fear & Greed (HTTP ' . $status . ')';
                $decoded = json_decode((string)$body, true);
                if (is_array($decoded) && isset($decoded['message'])) {
                    $message = (string)$decoded['message'];
                }
                $logger->warning('[FG] HTTP error ' . $status . ' - ' . $message);
                return [ 'success' => false, 'message' => $message, 'status' => $status ];
            }

            $data = json_decode((string)$body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $logger->error('[FG] Erro ao decodificar JSON: ' . json_last_error_msg());
                return [ 'success' => false, 'message' => 'Erro ao decodificar resposta JSON' ];
            }

            // Esperado: { success: bool, data: ... }
            if (isset($data['success']) && $data['success'] === true) {
                // Normalizações específicas da V2
                try {
                    if ($this->isV2()) {
                        if (str_contains($url, '/historical') && !str_contains($url, '/historical-all-indicators')) {
                            if (isset($data['data']['historical']) && is_array($data['data']['historical'])) {
                                $data = [ 'success' => true, 'data' => $data['data']['historical'] ];
                            } elseif (isset($data['data']) && is_array($data['data']) && isset($data['data'][0])) {
                                // já veio como lista diretamente em data[]
                                $data = [ 'success' => true, 'data' => $data['data'] ];
                            } elseif (isset($data['data']['fear_and_greed_historical']['data']) && is_array($data['data']['fear_and_greed_historical']['data'])) {
                                $data = [ 'success' => true, 'data' => $data['data']['fear_and_greed_historical']['data'] ];
                            }
                        } elseif (str_contains($url, '/date/') && isset($data['data']['data']) && is_array($data['data'])) {
                            // manter formato, UI não depende
                        }
                    }
                } catch (\Throwable $t) {
                    // ignore
                }
                // Diagnostics: log counts for historical/indicator to help detect API shape
                try {
                    if (str_contains($url, '/historical-all-indicators')) {
                        $total = 0;
                        if (isset($data['data']) && is_array($data['data'])) {
                            $collection = (isset($data['data']['indicators']) && is_array($data['data']['indicators'])) ? $data['data']['indicators'] : $data['data'];
                            foreach ($collection as $k => $obj) {
                                if (isset($obj['data']) && is_array($obj['data'])) {
                                    $cnt = count($obj['data']);
                                    $total += $cnt;
                                    $logger->info('[FG] HistAllInd "' . $k . '" points=' . $cnt);
                                }
                            }
                        }
                        $logger->info('[FG] HistAllInd total points=' . $total);
                    } elseif (str_contains($url, '/historical')) {
                        $cnt = 0;
                        if (isset($data['data']) && is_array($data['data'])) {
                            // V2 pode retornar a lista diretamente em data[] (array de linhas)
                            if (isset($data['data'][0])) {
                                $cnt = count($data['data']);
                            } elseif (isset($data['data']['data']) && is_array($data['data']['data'])) {
                                $cnt = count($data['data']['data']);
                            } elseif (isset($data['data']['historical']) && is_array($data['data']['historical'])) {
                                $cnt = count($data['data']['historical']);
                            } elseif (isset($data['data']['fear_and_greed_historical']['data']) && is_array($data['data']['fear_and_greed_historical']['data'])) {
                                $cnt = count($data['data']['fear_and_greed_historical']['data']);
                            }
                        }
                        $logger->info('[FG] Historical points=' . $cnt);
                    } elseif (str_contains($url, '/indicator/')) {
                        $cnt = 0;
                        if (isset($data['data']['data']['data']) && is_array($data['data']['data']['data'])) {
                            $cnt = count($data['data']['data']['data']);
                        }
                        $logger->info('[FG] Indicator points=' . $cnt . ' (' . ($data['data']['indicator'] ?? 'n/a') . ')');
                    } elseif (str_contains($url, '/indicators')) {
                        $cnt = 0;
                        if (isset($data['data']['indicators']) && is_array($data['data']['indicators'])) {
                            $cnt = count($data['data']['indicators']);
                        }
                        $logger->info('[FG] Indicators count=' . $cnt);
                    }
                } catch (\Throwable $t) {
                    // ignore logging errors
                }
                if ($useCache) {
                    $this->writeCache($cacheKey, $data);
                }
                return $data;
            }

            // fallback: se vier outro formato válido
            if (!isset($data['success']) && isset($data['data'])) {
                $wrapped = [ 'success' => true, 'data' => $data['data'] ];
                $this->writeCache($cacheKey, $wrapped);
                return $wrapped;
            }

            $msg = is_array($data) && isset($data['message']) ? (string)$data['message'] : 'Resposta inesperada da API';
            $logger->warning('[FG] Resposta sem success=true - ' . $msg);
            return [ 'success' => false, 'message' => $msg ];

        } catch (\Throwable $t) {
            $logger->error('[FG] Exceção na requisição: ' . $t->getMessage());
            return [ 'success' => false, 'message' => 'Falha na requisição: ' . $t->getMessage() ];
        }
    }

    private function doHttpRequest(string $url, ?int $timeout = null, ?int $connectTimeout = null, string $method = 'GET'): array
    {
        $timeout = $timeout ?? $this->timeout;
        $connectTimeout = $connectTimeout ?? $this->connectTimeout;
        if (is_callable($this->httpHandler)) {
            return call_user_func($this->httpHandler, $url, $this->buildHeaders(), $timeout, $connectTimeout);
        }
        $ch = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_HTTPHEADER => $this->buildHeaders(),
            CURLOPT_USERAGENT => 'Terminal-Operebem/1.0 (+fear-greed) PHP/' . PHP_VERSION,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
        ];
        $method = strtoupper($method);
        if ($method !== 'GET') {
            $options[CURLOPT_CUSTOMREQUEST] = $method;
        }
        curl_setopt_array($ch, $options);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
        $err = curl_error($ch);
        curl_close($ch);

        if ($body === false && $err) {
            throw new \RuntimeException('Erro de conexão cURL: ' . $err);
        }
        return [$status, $body ?: ''];
    }

    private function buildHeaders(): array
    {
        return [
            'X-API-Key: ' . $this->apiKey,
            'Accept: application/json',
        ];
    }

    private function cacheKey(string $url): string
    {
        return sha1($url);
    }

    private function cacheFile(string $key): string
    {
        return rtrim($this->cacheDir, '/\\') . DIRECTORY_SEPARATOR . $key . '.json';
    }

    private function readCache(string $key): ?array
    {
        $file = $this->cacheFile($key);
        if (!is_file($file)) return null;
        $mtime = @filemtime($file) ?: 0;
        if ((time() - $mtime) > $this->cacheTtl) return null;
        $raw = @file_get_contents($file);
        if ($raw === false) return null;
        $data = json_decode($raw, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $data : null;
    }

    private function writeCache(string $key, array $payload): void
    {
        $file = $this->cacheFile($key);
        $tmp = $file . '.tmp';
        @file_put_contents($tmp, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        @rename($tmp, $file);
    }

    public function getSyncStatus(): array
    {
        return $this->requestJson('sync-status');
    }

    public function clearCache(string $key): array
    {
        $key = trim($key);
        if ($key === '') {
            return [ 'success' => false, 'message' => 'Chave do cache é obrigatória.' ];
        }
        return $this->requestJson('cache/' . rawurlencode($key), '', null, 'DELETE');
    }

    public function getUptime(): array
    {
        return $this->requestJson('uptime');
    }
}

