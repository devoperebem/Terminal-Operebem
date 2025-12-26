<?php

namespace App\Services;

use PDO;
use PDOException;
use DateTimeZone;
use DateTime;

/**
 * Serviço de Market Clock
 * 
 * Gerencia status das bolsas mundiais baseado na tabela clock
 * do banco operebem_quotes (PostgreSQL)
 */
class MarketClockService
{
    private ?PDO $db = null;
    
    /**
     * Conectar ao banco de quotes (PostgreSQL)
     */
    private function getConnection(): PDO
    {
        if ($this->db === null) {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $_ENV['QUOTES_DB_HOST'] ?? '147.93.35.184',
                $_ENV['QUOTES_DB_PORT'] ?? '5432',
                $_ENV['QUOTES_DB_DATABASE'] ?? 'operebem_quotes'
            );
            
            $this->db = new PDO($dsn, $_ENV['QUOTES_DB_USERNAME'], $_ENV['QUOTES_DB_PASSWORD'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        }
        
        return $this->db;
    }
    
    /**
     * Obter todas as bolsas
     */
    public function getAllExchanges(): array
    {
        try {
            $db = $this->getConnection();
            $stmt = $db->query("
                SELECT 
                    id, exchange_code, exchange_name, timezone_name, timezone_utc,
                    pre_open_time, open_time, close_time, after_hours_start, after_hours_end,
                    trading_days, current_status, source, last_external_check
                FROM clock
                ORDER BY exchange_code
            ");
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('[MarketClockService] Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter bolsa específica
     */
    public function getExchange(string $code): ?array
    {
        try {
            $db = $this->getConnection();
            $stmt = $db->prepare("
                SELECT * FROM clock WHERE exchange_code = ?
            ");
            $stmt->execute([$code]);
            $result = $stmt->fetch();
            
            return $result ?: null;
        } catch (PDOException $e) {
            error_log('[MarketClockService] Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Calcular status atual de uma bolsa baseado em horários
     */
    public function calculateStatus(array $exchange): string
    {
        try {
            $tz = new DateTimeZone($exchange['timezone_name']);
            $now = new DateTime('now', $tz);
            $currentTime = $now->format('H:i:s');
            $dayOfWeekNum = (int)$now->format('w'); // 0=Dom, 1=Seg, 2=Ter, 3=Qua, 4=Qui, 5=Sex, 6=Sab
            $dayOfWeek = $now->format('D'); // Mon, Tue, Wed, etc
            
            // Verificar se está em dia de trading
            $tradingDays = $exchange['trading_days'] ?? '';
            
            // Converter dia para formato numérico usado no banco (1=Dom, 2=Seg, 3=Ter, etc)
            // Nota: PHP format('w') retorna 0=Dom, 1=Seg, etc
            // Banco usa 1=Dom, 2=Seg, 3=Ter, 4=Qua, 5=Qui, 6=Sex, 7=Sab
            $dayNumForDb = $dayOfWeekNum + 1; // 1-7 (Dom=1, Seg=2, etc)
            
            // Verificar se está em dia de trading
            $isTradingDay = false;
            
            if (is_numeric(str_replace(['1','2','3','4','5','6','7'], '', $tradingDays)) === false && strlen($tradingDays) <= 7 && preg_match('/^[1-7]+$/', $tradingDays)) {
                // Formato numérico: "23456" = Seg-Sex, "1234567" = todos os dias
                $isTradingDay = strpos($tradingDays, (string)$dayNumForDb) !== false;
            } elseif (strpos($tradingDays, 'Sun-Thu') !== false) {
                // Formato especial Sun-Thu (ex: Saudi Arabia)
                $isTradingDay = in_array($dayOfWeek, ['Sun', 'Mon', 'Tue', 'Wed', 'Thu']);
            } elseif (strpos($tradingDays, 'Mon-Sun') !== false) {
                // Todos os dias
                $isTradingDay = true;
            } else {
                // Padrão Mon-Fri
                $isTradingDay = !in_array($dayOfWeek, ['Sat', 'Sun']);
            }
            
            if (!$isTradingDay) {
                return 'closed';
            }
            
            // Verificar horários
            $preOpen = $exchange['pre_open_time'];
            $open = $exchange['open_time'];
            $close = $exchange['close_time'];
            $afterStart = $exchange['after_hours_start'];
            $afterEnd = $exchange['after_hours_end'];
            
            // Pre-market
            if ($preOpen && $currentTime >= $preOpen && $currentTime < $open) {
                return 'pre';
            }
            
            // Market open
            if ($currentTime >= $open && $currentTime < $close) {
                return 'open';
            }
            
            // After-hours
            if ($afterStart && $afterEnd && $currentTime >= $afterStart && $currentTime < $afterEnd) {
                return 'post';
            }
            
            // Closed
            return 'closed';
            
        } catch (\Exception $e) {
            error_log('[MarketClockService] calculateStatus error: ' . $e->getMessage());
            return 'unknown';
        }
    }
    
    /**
     * Atualizar status atual de todas as bolsas
     */
    public function updateAllStatuses(): int
    {
        try {
            $db = $this->getConnection();
            $exchanges = $this->getAllExchanges();
            $updated = 0;
            
            foreach ($exchanges as $exchange) {
                $newStatus = $this->calculateStatus($exchange);
                
                // Atualizar no banco
                $stmt = $db->prepare("
                    UPDATE clock 
                    SET current_status = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$newStatus, $exchange['id']]);
                $updated++;
            }
            
            return $updated;
        } catch (PDOException $e) {
            error_log('[MarketClockService] updateAllStatuses error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obter bolsas com status calculado em tempo real
     */
    public function getExchangesWithStatus(): array
    {
        $exchanges = $this->getAllExchanges();
        
        foreach ($exchanges as &$exchange) {
            $exchange['calculated_status'] = $this->calculateStatus($exchange);
            
            // Adicionar informações de horário local
            try {
                $tz = new DateTimeZone($exchange['timezone_name']);
                $now = new DateTime('now', $tz);
                $exchange['local_time'] = $now->format('H:i:s');
                $exchange['local_date'] = $now->format('Y-m-d');
            } catch (\Exception $e) {
                $exchange['local_time'] = null;
                $exchange['local_date'] = null;
            }
        }
        
        return $exchanges;
    }
    
    /**
     * Obter apenas bolsas principais para exibição no relógio
     * (As que já estavam hardcoded no widget)
     */
    public function getMainExchanges(): array
    {
        $mainCodes = ['XNYS', 'XLON', 'XTKS', 'XHKG', 'XBSP', 'XPAR', 'XETR'];
        
        try {
            $db = $this->getConnection();
            $placeholders = str_repeat('?,', count($mainCodes) - 1) . '?';
            $stmt = $db->prepare("
                SELECT * FROM clock 
                WHERE exchange_code IN ($placeholders)
                ORDER BY exchange_code
            ");
            $stmt->execute($mainCodes);
            
            $exchanges = $stmt->fetchAll();
            
            // Reordenar conforme ordem desejada
            $ordered = [];
            foreach ($mainCodes as $code) {
                foreach ($exchanges as $exchange) {
                    if ($exchange['exchange_code'] === $code) {
                        $exchange['calculated_status'] = $this->calculateStatus($exchange);
                        $ordered[] = $exchange;
                        break;
                    }
                }
            }
            
            return $ordered;
        } catch (PDOException $e) {
            error_log('[MarketClockService] getMainExchanges error: ' . $e->getMessage());
            return [];
        }
    }
}
