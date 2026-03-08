<?php

namespace App\Services;

use App\Core\Database;

class CustomIndicesService
{
    private const TTL = 60; // segundos
    private static array $cacheSets = []; // key => ['t'=>int,'data'=>array]

    /**
     * Busca índices por nomes a partir de custom_indices (conexão 'quotes')
     * Retorna mapa [NAME => ['name','value','coverage','payload'=>array,'updated_at'=>string]]
     */
    public function getIndicesByNames(array $names): array
    {
        $names = array_values(array_unique(array_filter(array_map(function($v){ return strtoupper((string)$v); }, $names), function($v){ return $v !== ''; })));
        if (empty($names)) { return []; }
        $key = 'set:' . implode(',', $names);
        $now = time();
        $cache = self::$cacheSets[$key] ?? null;
        if ($cache && ($now - (int)($cache['t'] ?? 0)) < self::TTL) {
            return $cache['data'] ?? [];
        }
        $ph = implode(',', array_fill(0, count($names), '?'));
        $sql = "SELECT name, value, coverage, payload::text AS payload, to_char(updated_at AT TIME ZONE 'America/Sao_Paulo', 'YYYY-MM-DD HH24:MI:SS') AS updated_at FROM custom_indices WHERE name IN ($ph)";
        $rows = Database::fetchAll($sql, $names, 'quotes');
        $out = [];
        foreach ($rows as $r) {
            $payload = null;
            if (isset($r['payload'])) {
                try { $payload = json_decode((string)$r['payload'], true, 512, JSON_THROW_ON_ERROR); }
                catch (\Throwable $t) { $payload = null; }
            }
            $out[strtoupper((string)$r['name'])] = [
                'name' => strtoupper((string)$r['name']),
                'value' => isset($r['value']) ? (float)$r['value'] : null,
                'coverage' => isset($r['coverage']) ? (int)$r['coverage'] : null,
                'payload' => $payload,
                'updated_at' => (string)$r['updated_at'],
            ];
        }
        self::$cacheSets[$key] = ['t' => $now, 'data' => $out];
        return $out;
    }

    /**
     * Retorna todos os índices OB prontos para o front (payload descompactado)
     * keys: IFPV, IFPP, IDFE, IDFE2, timestamp, datetime
     */
    public function getAll(): array
    {
        $wanted = ['IFPV','IFPP','IDFE','IDFE2'];
        $rows = $this->getIndicesByNames($wanted);
        $out = [];
        $maxTs = 0;
        $userTz = \App\Services\TimezoneService::getUserTimezone();
        $srcTz = new \DateTimeZone('America/Sao_Paulo');
        foreach ($wanted as $n) {
            $row = $rows[$n] ?? null;
            if ($row && is_array($row['payload'])) {
                $pl = $row['payload'];
                if (is_array($pl['components'] ?? null)) {
                    $pl['components'] = $this->sortComponentsDeep($pl['components']);
                }
                // Normalizar horários no payload para o timezone do usuário
                $pl = $this->normalizePayloadTimes($pl, $userTz);
                $out[$n] = $pl;

                // Calcular maxTs com base no updated_at do registro (string em America/Sao_Paulo)
                $updatedAtStr = (string)($row['updated_at'] ?? '');
                if ($updatedAtStr !== '') {
                    $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $updatedAtStr, $srcTz);
                    if ($dt instanceof \DateTime) {
                        $ts = $dt->getTimestamp();
                        if ($ts > $maxTs) { $maxTs = $ts; }
                    }
                }
            } else {
                $out[$n] = null;
            }
        }
        if ($maxTs > 0) {
            $out['timestamp'] = $maxTs;
            $out['datetime'] = \App\Services\TimezoneService::convertTimestamp($maxTs, $userTz, 'Y-m-d H:i:s');
        }
        return $out;
    }

    /**
     * Retorna um índice específico com payload descompactado
     */
    public function getOne(string $name): ?array
    {
        $name = strtoupper($name);
        $rows = $this->getIndicesByNames([$name]);
        $row = $rows[$name] ?? null;
        if (!$row) { return null; }
        if (is_array($row['payload'])) {
            $pl = $row['payload'];
            if (is_array($pl['components'] ?? null)) {
                $pl['components'] = $this->sortComponentsDeep($pl['components']);
            }
            return $pl;
        }
        return null;
    }

    private function sortComponentsDeep(array $components): array
    {
        uasort($components, function($a, $b){
            $wa = is_array($a) && isset($a['weight']) ? (float)$a['weight'] : 0.0;
            $wb = is_array($b) && isset($b['weight']) ? (float)$b['weight'] : 0.0;
            if ($wb <=> $wa) return ($wb <=> $wa);
            return 0;
        });
        foreach ($components as $k => $comp) {
            if (is_array($comp) && is_array($comp['subcomponents'] ?? null)) {
                $subs = $comp['subcomponents'];
                usort($subs, function($a, $b){
                    $wa = is_array($a) && isset($a['weight_share']) ? (float)$a['weight_share'] : 0.0;
                    $wb = is_array($b) && isset($b['weight_share']) ? (float)$b['weight_share'] : 0.0;
                    if ($wb <=> $wa) return ($wb <=> $wa);
                    return 0;
                });
                $comp['subcomponents'] = $subs;
                $components[$k] = $comp;
            }
        }
        return $components;
    }

    private function normalizePayloadTimes(array $pl, string $timezone): array
    {
        $convert = function($val) use ($timezone) {
            if ($val === null || $val === '') { return null; }
            if (is_numeric($val)) {
                $num = (float)$val;
                if ($num > 20000000000) {
                    $num = (int)round($num / 1000);
                }
                return \App\Services\TimezoneService::convertTimestamp((int)$num, $timezone, 'Y-m-d H:i:s');
            }
            $str = (string)$val;
            $out = \App\Services\TimezoneService::convertTimestamp($str, $timezone, 'Y-m-d H:i:s');
            return $out ?? $str;
        };

        if (isset($pl['spot']) && is_array($pl['spot']) && array_key_exists('updated_at', $pl['spot'])) {
            $pl['spot']['updated_at'] = $convert($pl['spot']['updated_at']);
        }

        if (isset($pl['components']) && is_array($pl['components'])) {
            foreach ($pl['components'] as $k => $comp) {
                if (is_array($comp)) {
                    if (array_key_exists('updated_at', $comp)) {
                        $comp['updated_at'] = $convert($comp['updated_at']);
                    }
                    if (isset($comp['subcomponents']) && is_array($comp['subcomponents'])) {
                        foreach ($comp['subcomponents'] as $i => $sub) {
                            if (is_array($sub) && array_key_exists('updated_at', $sub)) {
                                $sub['updated_at'] = $convert($sub['updated_at']);
                                $comp['subcomponents'][$i] = $sub;
                            }
                        }
                    }
                    $pl['components'][$k] = $comp;
                }
            }
        }

        return $pl;
    }
}
