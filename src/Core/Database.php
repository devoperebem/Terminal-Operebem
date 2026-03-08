<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static array $connections = [];
    private static array $config = [];

    public static function init(array $config): void
    {
        self::$config = $config;
    }

    public static function connection(?string $name = null): PDO
    {
        // Usar banco default do config se nenhum nome for especificado
        if ($name === null) {
            $name = self::$config['default'] ?? 'mysql';
        }
        
        if (!isset(self::$connections[$name])) {
            self::$connections[$name] = self::createConnection($name);
        }

        return self::$connections[$name];
    }

    private static function createConnection(string $name): PDO
    {
        $config = self::$config['connections'][$name] ?? null;
        
        if (!$config) {
            throw new \InvalidArgumentException("Configuração de banco '$name' não encontrada");
        }

        try {
            $dsn = self::buildDsn($config);
            $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options'] ?? []);
            
            return $pdo;
        } catch (PDOException $e) {
            Application::getInstance()->logger()->error("Erro ao conectar com banco '$name': " . $e->getMessage());
            throw new \Exception("Erro ao conectar com o banco de dados");
        }
    }

    private static function buildDsn(array $config): string
    {
        switch ($config['driver']) {
            case 'mysql':
                return sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $config['host'],
                    $config['port'],
                    $config['database'],
                    $config['charset'] ?? 'utf8mb4'
                );
                
            case 'pgsql':
                return sprintf(
                    'pgsql:host=%s;port=%s;dbname=%s',
                    $config['host'],
                    $config['port'],
                    $config['database']
                );
                
            default:
                throw new \InvalidArgumentException("Driver '{$config['driver']}' não suportado");
        }
    }

    public static function beginTransaction(?string $connection = null): void
    {
        self::connection($connection)->beginTransaction();
    }

    public static function commit(?string $connection = null): void
    {
        self::connection($connection)->commit();
    }

    public static function rollback(?string $connection = null): void
    {
        self::connection($connection)->rollBack();
    }

    public static function query(string $sql, array $params = [], ?string $connection = null): \PDOStatement
    {
        $pdo = self::connection($connection);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt;
    }

    public static function fetch(string $sql, array $params = [], ?string $connection = null): ?array
    {
        $stmt = self::query($sql, $params, $connection);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    public static function fetchAll(string $sql, array $params = [], ?string $connection = null): array
    {
        $stmt = self::query($sql, $params, $connection);
        return $stmt->fetchAll();
    }

    /**
     * Executa uma query e retorna o valor da primeira coluna da primeira linha
     */
    public static function fetchColumn(string $sql, array $params = [], int $column = 0, ?string $connection = null): mixed
    {
        $stmt = self::query($sql, $params, $connection);
        return $stmt->fetchColumn($column);
    }

    /**
     * Executa uma query e retorna o número de linhas afetadas
     */
    public static function execute(string $sql, array $params = [], ?string $connection = null): int
    {
        $stmt = self::query($sql, $params, $connection);
        return $stmt->rowCount();
    }

    public static function insert(string $table, array $data, ?string $connection = null): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        // Converter booleanos para PostgreSQL antes de bind
        $params = [];
        foreach ($data as $key => $value) {
            if (is_bool($value)) {
                $params[$key] = $value ? 'true' : 'false';
            } else {
                $params[$key] = $value;
            }
        }
        
        self::query($sql, $params, $connection);
        
        return (int) self::connection($connection)->lastInsertId();
    }

    public static function update(string $table, array $data, array $where, ?string $connection = null): int
    {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setParts);
        
        $whereParts = [];
        foreach (array_keys($where) as $column) {
            $whereParts[] = "{$column} = :where_{$column}";
        }
        $whereClause = implode(' AND ', $whereParts);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$whereClause}";
        
        // Converter booleanos para PostgreSQL antes de bind (PostgreSQL requer 'true'/'false' ou 1/0)
        $params = [];
        foreach ($data as $key => $value) {
            if (is_bool($value)) {
                $params[$key] = $value ? 'true' : 'false';
            } else {
                $params[$key] = $value;
            }
        }
        
        // Prefixar parâmetros WHERE para evitar conflitos
        foreach ($where as $key => $value) {
            if (is_bool($value)) {
                $params["where_{$key}"] = $value ? 'true' : 'false';
            } else {
                $params["where_{$key}"] = $value;
            }
        }
        
        $stmt = self::query($sql, $params, $connection);
        
        return $stmt->rowCount();
    }

    public static function delete(string $table, array $where, ?string $connection = null): int
    {
        $whereParts = [];
        foreach (array_keys($where) as $column) {
            $whereParts[] = "{$column} = :{$column}";
        }
        $whereClause = implode(' AND ', $whereParts);
        
        $sql = "DELETE FROM {$table} WHERE {$whereClause}";
        
        $stmt = self::query($sql, $where, $connection);
        
        return $stmt->rowCount();
    }
}
