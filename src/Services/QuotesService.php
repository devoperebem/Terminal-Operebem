<?php

namespace App\Services;

use PDO;
use PDOException;

class QuotesService
{
    private PDO $quotesDb;
    private array $cache = [
        'last_update' => 0,
        'data' => []
    ];
    
    const CACHE_DURATION = 30; // 30 segundos

    public function __construct()
    {
        $this->quotesDb = $this->createQuotesConnection();
    }

    public function pdo(): PDO
    {
        return $this->quotesDb;
    }

    /**
     * Retorna todos os registros da tabela dicionario sem filtros (inclui Ativo != 'S').
     * Útil para rotinas de diagnóstico, mapeamento e heurísticas.
     */
    public function getAllRaw(): array
    {
        try {
            $sql = "SELECT * FROM dicionario";
            $stmt = $this->quotesDb->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new \Exception("Erro ao buscar cotações (raw): " . $e->getMessage());
        }
    }

    /**
     * Busca apenas os registros cujo id_api OU code esteja na lista $keys.
     * Não filtra por Ativo, pois o ORM depende desses 8 ativos independentemente do flag.
     * Retorna array associativo semelhante ao getAllQuotes().
     */
    public function getByIdsOrCodes(array $keys): array
    {
        $keys = array_values(array_unique(array_filter(array_map(function($v) { return (string)$v; }, $keys), function($v) { return $v !== ''; })));
        if (empty($keys)) { return []; }
        // Montar placeholders dinamicamente
        $ph = implode(',', array_fill(0, count($keys), '?'));
        $sql = "SELECT * FROM dicionario WHERE id_api IN ($ph) OR code IN ($ph)";
        $stmt = $this->quotesDb->prepare($sql);
        // Bind: primeiro id_api, depois code
        $bind = array_merge($keys, $keys);
        foreach ($bind as $i => $val) {
            $stmt->bindValue($i + 1, $val);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function createQuotesConnection(): PDO
    {
        $host = $_ENV['QUOTES_DB_HOST'];
        $port = $_ENV['QUOTES_DB_PORT'];
        $dbname = $_ENV['QUOTES_DB_DATABASE'];
        $username = $_ENV['QUOTES_DB_USERNAME'];
        $password = $_ENV['QUOTES_DB_PASSWORD'];

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
        
        try {
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            return $pdo;
        } catch (PDOException $e) {
            throw new \Exception("Erro ao conectar com banco de cotações: " . $e->getMessage());
        }
    }

    public function getAllQuotes(): array
    {
        try {
            // Verifica cache
            if (time() - $this->cache['last_update'] < self::CACHE_DURATION && !empty($this->cache['data'])) {
                return $this->cache['data'];
            }

            $sql = "SELECT * FROM dicionario WHERE ativo = 'S' ORDER BY order_tabela ASC";
            $stmt = $this->quotesDb->prepare($sql);
            $stmt->execute();
            $data = $stmt->fetchAll();

            // Atualiza cache
            $this->cache['data'] = $data;
            $this->cache['last_update'] = time();

            return $data;

        } catch (PDOException $e) {
            throw new \Exception("Erro ao buscar cotações: " . $e->getMessage());
        }
    }

    public function getActiveByOrigins(array $origens): array
    {
        try {
            $origens = array_values(array_unique(array_filter(array_map(function($v) { return (string)$v; }, $origens), function($v) { return $v !== ''; })));
            if (empty($origens)) { return []; }
            $ph = implode(',', array_fill(0, count($origens), '?'));
            $sql = "SELECT * FROM dicionario WHERE ativo = 'S' AND origem IN ($ph) ORDER BY order_tabela ASC";
            $stmt = $this->quotesDb->prepare($sql);
            foreach ($origens as $i => $val) { $stmt->bindValue($i + 1, $val); }
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new \Exception("Erro ao buscar cotações por origem: " . $e->getMessage());
        }
    }

    public function getActiveNotInvesting(): array
    {
        try {
            $sql = "SELECT * FROM dicionario WHERE ativo = 'S' AND origem <> 'investing' ORDER BY order_tabela ASC";
            $stmt = $this->quotesDb->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new \Exception("Erro ao buscar cotações complementares: " . $e->getMessage());
        }
    }

    public function updateQuotes(array $data): array
    {
        try {
            $result = ['error' => ''];

            if (empty($data)) {
                return $result;
            }

            $updates = [];

            foreach ($data as $item) {
                $id_api = $item['id_api'] ?? '';
                
                if (empty($id_api)) {
                    continue;
                }

                $updateItem = [
                    "code" => !empty($item['code']) ? $item['code'] : null,
                    "nome" => !empty($item['name']) ? $item['name'] : null,
                    "ask" => !empty($item['ask']) ? $item['ask'] : null,
                    "bid" => !empty($item['bid']) ? $item['bid'] : null,
                    "high" => !empty($item['high']) ? $item['high'] : null,
                    "high_numeric" => array_key_exists('high_numeric', $item) ? $item['high_numeric'] : null,
                    "last" => !empty($item['last']) ? $item['last'] : null,
                    "last_close" => !empty($item['last_close']) ? $item['last_close'] : null,
                    "last_dir" => !empty($item['last_dir']) ? $item['last_dir'] : null,
                    "last_numeric" => !empty($item['last_numeric']) ? $item['last_numeric'] : null,
                    "low" => !empty($item['low']) ? $item['low'] : null,
                    "low_numeric" => array_key_exists('low_numeric', $item) ? $item['low_numeric'] : null,
                    "pc" => !empty($item['pc']) ? $item['pc'] : null,
                    "pc_col" => !empty($item['pc_col']) ? $item['pc_col'] : null,
                    "pcp" => !empty($item['pcp']) ? $item['pcp'] : null,
                    "pid" => !empty($item['pid']) ? $item['pid'] : null,
                    "time_utc" => !empty($item['time_utc']) ? $item['time_utc'] : null,
                    "timestamp" => !empty($item['timestamp']) ? $item['timestamp'] : null,
                    "turnover" => !empty($item['turnover']) ? $item['turnover'] : null,
                    "turnover_numeric" => !empty($item['turnover_numeric']) ? $item['turnover_numeric'] : null,
                    "dt_alteracao" => date("Y-m-d H:i:s"),
                    "status_mercado" => !empty($item['status_mercado']) ? $item['status_mercado'] : null,
                    "status_hr" => !empty($item['status_hr']) ? $item['status_hr'] : null,
                    "origem" => !empty($item['origem']) ? $item['origem'] : null
                ];

                $updateItem = array_filter($updateItem, function($value) {
                    return !is_null($value);
                });

                $updates[$id_api] = $updateItem;
            }

            if (!empty($updates)) {
                $this->bulkUpdate($updates);
            }

            return $result;

        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function bulkUpdate(array $updates): void
    {
        foreach ($updates as $key => $data) {
            $fields = array_keys($data);
            $placeholders = array_map(function($field) { return "$field = :$field"; }, $fields);
            
            $sql = "UPDATE dicionario SET " . implode(', ', $placeholders) . " WHERE code = :key OR id_api = :key";
            
            $stmt = $this->quotesDb->prepare($sql);
            $stmt->bindValue(':key', $key);
            
            foreach ($data as $field => $value) {
                $stmt->bindValue(":$field", $value);
            }
            
            $stmt->execute();
        }
    }
    
    /**
     * Busca cotações por símbolos (code ou id_api)
     * Otimizado para buscar por id_api numérico
     * Retorna array associativo indexado pelo símbolo solicitado
     * 
     * @param array $symbols Array de codes ou id_apis
     * @return array Array associativo [symbol_solicitado => dados]
     */
    public function getQuotesBySymbols(array $symbols): array
    {
        try {
            if (empty($symbols)) {
                return [];
            }
            
            // Limpar e normalizar símbolos
            $symbols = array_values(array_unique(array_filter(array_map(function($v) { return (string)$v; }, $symbols), function($v) { return $v !== ''; })));
            if (empty($symbols)) {
                return [];
            }
            
            // Separar IDs numéricos de códigos alfanuméricos
            $numericIds = [];
            $alphaCodes = [];
            
            foreach ($symbols as $symbol) {
                if (is_numeric($symbol)) {
                    $numericIds[] = $symbol;
                } else {
                    $alphaCodes[] = $symbol;
                }
            }
            
            $results = [];
            
            // Buscar por id_api (numérico) - OTIMIZADO
            if (!empty($numericIds)) {
                $placeholders = implode(',', array_fill(0, count($numericIds), '?'));
                $sql = "SELECT * FROM dicionario WHERE id_api IN ($placeholders)";
                $stmt = $this->quotesDb->prepare($sql);
                
                foreach ($numericIds as $i => $id) {
                    $stmt->bindValue($i + 1, $id);
                }
                
                $stmt->execute();
                $rows = $stmt->fetchAll();
                
                foreach ($rows as $row) {
                    $results[$row['id_api']] = $row;
                }
            }
            
            // Buscar por code (alfanumérico)
            if (!empty($alphaCodes)) {
                $placeholders = implode(',', array_fill(0, count($alphaCodes), '?'));
                $sql = "SELECT * FROM dicionario WHERE code IN ($placeholders)";
                $stmt = $this->quotesDb->prepare($sql);
                
                foreach ($alphaCodes as $i => $code) {
                    $stmt->bindValue($i + 1, $code);
                }
                
                $stmt->execute();
                $rows = $stmt->fetchAll();
                
                foreach ($rows as $row) {
                    $results[$row['code']] = $row;
                }
            }
            
            // Adicionar campos calculados para compatibilidade
            foreach ($results as &$row) {
                $row['price'] = $row['last_numeric'] ?? $row['last'] ?? null;
                $row['change_percent'] = $row['pcp'] ?? null;
                $row['change'] = $row['pc'] ?? null;
            }
            
            return $results;
            
        } catch (PDOException $e) {
            throw new \Exception("Erro ao buscar cotações por símbolos: " . $e->getMessage());
        }
    }

    public function ensureInvestingIdExists(string $id, ?string $code = null, ?string $name = null): void
    {
        try {
            $id = (string)$id;
            if ($id === '' || !is_numeric($id)) { return; }
            $stmt = $this->quotesDb->prepare('SELECT 1 FROM dicionario WHERE id_api = ? LIMIT 1');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row) { return; }
            $code = $code !== null ? (string)$code : null;
            $name = $name !== null ? (string)$name : null;
            $ins = $this->quotesDb->prepare(
                "INSERT INTO dicionario (id_api, code, nome, ativo, origem, order_tabela, dt_alteracao)\n                 VALUES (:id_api, :code, :nome, 'S', 'investing', :order, NOW())\n                 ON CONFLICT (id_api) DO UPDATE SET code = COALESCE(EXCLUDED.code, dicionario.code), nome = COALESCE(EXCLUDED.nome, dicionario.nome), ativo = 'S', origem = 'investing'"
            );
            $ins->bindValue(':id_api', $id);
            $ins->bindValue(':code', $code);
            $ins->bindValue(':nome', $name);
            $ins->bindValue(':order', 9999, PDO::PARAM_INT);
            $ins->execute();
        } catch (PDOException $e) {
            // ignore
        }
    }
}
