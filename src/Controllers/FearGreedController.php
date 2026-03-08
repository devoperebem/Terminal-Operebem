<?php

namespace App\Controllers;

use App\Core\Application;
use App\Services\FearGreedService;

class FearGreedController
{
    private FearGreedService $service;

    public function __construct(?FearGreedService $service = null)
    {
        $this->service = $service ?? new FearGreedService();
    }

    public function current(): void
    {
        header('Content-Type: application/json');
        $res = $this->service->getCurrent();
        echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function summary(array $params = []): void
    {
        header('Content-Type: application/json');
        $date = $params['date'] ?? null;
        $res = $this->service->getSummary($date);
        echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function byDate(array $params): void
    {
        header('Content-Type: application/json');
        $date = $params['date'] ?? '';
        $res = $this->service->getByDate($date);
        echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function historical(): void
    {
        header('Content-Type: application/json');
        $start = isset($_GET['start_date']) ? (string)$_GET['start_date'] : '';
        $end   = isset($_GET['end_date']) ? (string)$_GET['end_date'] : '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
        try { Application::getInstance()->logger()->info('[FG] historical', ['start'=>$start,'end'=>$end,'limit'=>$limit]); } catch (\Throwable $t) {}
        $res = $this->service->getHistorical($start, $end, $limit);
        echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function indicator(array $params): void
    {
        header('Content-Type: application/json');
        $indicator = $params['indicator'] ?? '';
        $date = $params['date'] ?? null;
        $res = $this->service->getIndicator($indicator, $date);
        echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function indicators(): void
    {
        header('Content-Type: application/json');
        $date = isset($_GET['date']) ? (string)$_GET['date'] : null;
        $res = $this->service->getIndicators($date);
        echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function syncStatus(): void
    {
        header('Content-Type: application/json');
        $res = $this->service->getSyncStatus();
        echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function uptime(): void
    {
        header('Content-Type: application/json');
        $res = $this->service->getUptime();
        echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function cache(array $params): void
    {
        header('Content-Type: application/json');
        $key = $params['key'] ?? 'all';
        $res = $this->service->clearCache($key);
        echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function indicatorsHistorical(): void
    {
        header('Content-Type: application/json');
        $start = isset($_GET['start_date']) ? (string)$_GET['start_date'] : null;
        $end   = isset($_GET['end_date']) ? (string)$_GET['end_date'] : null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 365;
        try { Application::getInstance()->logger()->info('[FG] indicatorsHistorical', ['start'=>$start,'end'=>$end,'limit'=>$limit]); } catch (\Throwable $t) {}
        $raw = $this->service->getIndicatorsHistorical($start, $end, $limit);
        if (!(is_array($raw) && ($raw['success'] ?? false))) {
            echo json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }
        $inner = $raw['data'] ?? $raw;
        $collection = [];
        if (isset($inner['indicators']) && is_array($inner['indicators'])) {
            $collection = $inner['indicators'];
        } elseif (isset($inner['data']['indicators']) && is_array($inner['data']['indicators'])) {
            $collection = $inner['data']['indicators'];
        } elseif (is_array($inner)) {
            $collection = $inner;
        }
        try { Application::getInstance()->logger()->info('[FG] indicatorsHistorical inspect', [ 'inner_has_ind' => isset($inner['indicators']) && is_array($inner['indicators']), 'inner_keys' => array_keys(is_array($inner)?$inner:[]) ]); } catch (\Throwable $t) {}
        // Mapear objetos com chave => { data: [...] }
        $map = [
            'market_momentum_sp500' => 'market-momentum-sp500',
            'market_momentum_sp125' => 'market-momentum-sp125',
            'stock_price_strength' => 'stock-price-strength',
            'stock_price_breadth' => 'stock-price-breadth',
            'put_call_options' => 'put-call-options',
            'market_volatility_vix' => 'market-volatility-vix',
            'market_volatility_vix_50' => 'market-volatility-vix-50',
            'safe_haven_demand' => 'safe-haven-demand',
            'junk_bond_demand' => 'junk-bond-demand',
        ];
        $series = [];
        $iter = $collection;
        $isList = isset($iter[0]) && is_array($iter[0]);
        foreach ($iter as $k => $obj) {
            if (!is_array($obj)) continue;
            $key = $isList ? ($obj['table'] ?? ($obj['key'] ?? ($obj['id'] ?? (string)$k))) : (string)$k;
            $slug = $map[$key] ?? null;
            if (!$slug) {
                // tentar derivar slug substituindo '_' por '-'
                $slug = str_replace('_', '-', (string)$key);
                if (!in_array($slug, array_values($map), true)) {
                    continue;
                }
            }
            try {
                $objKeys = array_keys($obj);
                $hasData = isset($obj['data']) && is_array($obj['data']);
                $dataCount = $hasData ? count($obj['data']) : 0;
                Application::getInstance()->logger()->info('[FG] indicatorsHistorical obj', ['indicator_key'=>$key, 'slug'=>$slug, 'obj_keys'=>$objKeys, 'has_data'=>$hasData, 'data_count'=>$dataCount]);
            } catch (\Throwable $t) {}
            $arr = [];
            $list = null;
            if (isset($obj['data']) && is_array($obj['data'])) { $list = $obj['data']; }
            if ($list === null) {
                foreach ($obj as $k2 => $v2) {
                    if (is_array($v2) && isset($v2[0])) { $list = $v2; break; }
                }
            }
            if (is_array($list)) {
                try {
                    $sampleKeys = [];
                    if (isset($list[0]) && is_array($list[0])) { $sampleKeys = array_keys($list[0]); }
                    Application::getInstance()->logger()->info('[FG] indicatorsHistorical sample', ['indicator_key'=>$key, 'count'=>count($list), 'item0_keys'=>$sampleKeys]);
                } catch (\Throwable $t) {}
                $cntIn = count($list);
                $valueMissing = 0;
                $timeMissing = 0;
                $added = 0;
                $firstItemDbg = null;
                foreach ($list as $dKey => $item) {
                    if ($firstItemDbg === null && is_array($item)) {
                        $firstItemDbg = [
                            'date' => $item['date'] ?? null,
                            'timestamp' => $item['timestamp'] ?? null,
                            'time' => $item['time'] ?? ($item['day'] ?? ($item['dt'] ?? ($item['x'] ?? null))),
                            'x' => $item['x'] ?? null,
                            'data_keys' => isset($item['data']) && is_array($item['data']) ? array_keys($item['data']) : [],
                        ];
                    }
                    if (!is_array($item)) {
                        $val = null;
                        if (is_numeric($item)) {
                            $val = (float)$item;
                        } elseif (is_string($item) && is_numeric($item + 0)) {
                            $val = (float)$item;
                        }
                        if ($val !== null) {
                            $ms = null;
                            if (is_string($dKey) && $dKey !== '') {
                                $ts3 = strtotime($dKey . ' 00:00:00 UTC');
                                if ($ts3 !== false) { $ms = $ts3 * 1000; }
                            } elseif (is_numeric($dKey)) {
                                $tsInt = (int)$dKey;
                                $ms = ($tsInt < 20000000000) ? ($tsInt * 1000) : $tsInt;
                            }
                            if ($ms !== null) {
                                $arr[] = [ 'x' => $ms, 'y' => $val ];
                            }
                        }
                        continue;
                    }
                    $date = $item['date'] ?? null;
                    $score = $item['score'] ?? ($item['value'] ?? ($item['metadata']['value'] ?? ($item['y'] ?? ($item['current_value'] ?? null))));
                    if ($score === null) {
                        $inner = $item['data'] ?? null;
                        if (is_array($inner)) {
                            $score = $inner['score'] ?? ($inner['value'] ?? ($inner['y'] ?? ($inner['current_value'] ?? ($inner['close'] ?? ($inner['v'] ?? null)))));
                        } elseif (is_numeric($inner)) {
                            $score = (float)$inner;
                        }
                    }
                    if ($score === null) {
                        $meta = $item['metadata'] ?? null;
                        if (is_array($meta)) {
                            $score = $meta['score'] ?? ($meta['value'] ?? ($meta['y'] ?? ($meta['current_value'] ?? null)));
                        }
                    }
                    if ($score === null) {
                        // Deep-scan fallback: find first numeric value excluding time-like keys
                        $exclude = ['timestamp','time','date','created_at','updated_at','day','dt','x','id','count'];
                        $stack = [$item];
                        while ($score === null && $stack) {
                            $node = array_pop($stack);
                            if (is_array($node)) {
                                foreach ($node as $kk => $vv) {
                                    if (is_array($vv)) { $stack[] = $vv; continue; }
                                    if (is_numeric($vv) && !in_array((string)$kk, $exclude, true)) { $score = (float)$vv; break; }
                                }
                            }
                        }
                    }
                    if ($score === null) { $valueMissing++; continue; }
                    $ms = null;
                    if ($date) {
                        if (is_numeric($date)) {
                            $tsNum = (int)$date;
                            $ms = ($tsNum < 20000000000) ? ($tsNum * 1000) : $tsNum;
                        } else {
                            $ts = strtotime($date);
                            if ($ts === false) { $ts = strtotime($date . ' UTC'); }
                            if ($ts !== false) { $ms = $ts * 1000; }
                        }
                    }
                    if ($ms === null) {
                        $tsRaw = $item['timestamp'] ?? null;
                        if (is_numeric($tsRaw)) {
                            $tsInt = (int)$tsRaw;
                            $ms = ($tsInt < 20000000000) ? ($tsInt * 1000) : $tsInt; // já em ms se grande
                        }
                    }
                    if ($ms === null) {
                        $alt = $item['time'] ?? ($item['day'] ?? ($item['dt'] ?? ($item['x'] ?? null)));
                        if ($alt !== null) {
                            if (is_numeric($alt)) {
                                $tsInt = (int)$alt;
                                $ms = ($tsInt < 20000000000) ? ($tsInt * 1000) : $tsInt;
                            } else {
                                $ts2 = strtotime((string)$alt);
                                if ($ts2 !== false) { $ms = $ts2 * 1000; }
                            }
                        }
                    }
                    if ($ms === null) {
                        $keys = array_keys($item);
                        $isList = ($keys === range(0, count($item)-1));
                        if ($isList && count($item) >= 2) {
                            $tCand = $item[0];
                            $vCand = $item[1];
                            if ($score === null && (is_numeric($vCand) || (is_string($vCand) && is_numeric($vCand + 0)))) {
                                $score = (float)$vCand;
                            }
                            if (is_numeric($tCand)) {
                                $tsInt = (int)$tCand;
                                $ms = ($tsInt < 20000000000) ? ($tsInt * 1000) : $tsInt;
                            } else {
                                $ts3 = strtotime((string)$tCand);
                                if ($ts3 !== false) { $ms = $ts3 * 1000; }
                            }
                        }
                    }
                    if ($ms === null) {
                        $created = $item['created_at'] ?? null;
                        $updated = $item['updated_at'] ?? null;
                        $dstr = $created ?: $updated;
                        if (is_string($dstr) && $dstr !== '') {
                            $ts2 = strtotime($dstr . ' UTC');
                            if ($ts2 !== false) { $ms = $ts2 * 1000; }
                        }
                    }
                    if ($ms === null) {
                        if (is_string($dKey) && $dKey !== '') {
                            $tsKey = strtotime($dKey . ' 00:00:00 UTC');
                            if ($tsKey !== false) { $ms = $tsKey * 1000; }
                        } elseif (is_numeric($dKey)) {
                            $tsInt = (int)$dKey;
                            $ms = ($tsInt < 20000000000) ? ($tsInt * 1000) : $tsInt;
                        }
                    }
                    if ($ms === null) { $timeMissing++; continue; }
                    $arr[] = [ 'x' => $ms, 'y' => (float)$score ];
                    $added++;
                }
            }
            try {
                Application::getInstance()->logger()->info('[FG] indicatorsHistorical diag', [
                    'indicator_key' => $key,
                    'slug' => $slug,
                    'in_count' => $cntIn ?? 0,
                    'added' => $added ?? 0,
                    'miss_value' => $valueMissing ?? 0,
                    'miss_time' => $timeMissing ?? 0,
                    'first_item_date' => $firstItemDbg['date'] ?? null,
                    'first_item_ts' => $firstItemDbg['timestamp'] ?? null,
                    'first_item_time' => $firstItemDbg['time'] ?? null,
                    'first_item_x' => $firstItemDbg['x'] ?? null,
                    'first_item_data_keys' => $firstItemDbg['data_keys'] ?? [],
                ]);
            } catch (\Throwable $t) {}
            if ($arr) {
                usort($arr, function($a, $b){ return $a['x'] <=> $b['x']; });
                $series[$slug] = $arr;
            }
        }
        $keys = array_keys($series);
        $sizes = [];
        foreach ($series as $k => $v) { $sizes[$k] = is_array($v) ? count($v) : 0; }
        try { Application::getInstance()->logger()->info('[FG] indicatorsHistorical result', ['series_keys'=>$keys, 'series_sizes'=>$sizes]); } catch (\Throwable $t) {}
        echo json_encode([
            'success' => true,
            'data' => [ 'series' => $series ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

