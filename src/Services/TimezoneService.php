<?php

namespace App\Services;

use DateTimeZone;
use DateTime;

/**
 * Timezone Service
 * Gerencia conversão de timestamps para diferentes fusos horários
 */
class TimezoneService
{
    /**
     * Lista de timezones suportados (principais fusos horários do Brasil e mundo)
     */
    public static function getSupportedTimezones(): array
    {
        return [
            // Brasil
            'America/Sao_Paulo' => 'Brasília (UTC-3)',
            'America/Manaus' => 'Manaus (UTC-4)',
            'America/Rio_Branco' => 'Rio Branco (UTC-5)',
            'America/Noronha' => 'Fernando de Noronha (UTC-2)',
            
            // América do Norte
            'America/New_York' => 'Nova York (UTC-5/-4)',
            'America/Chicago' => 'Chicago (UTC-6/-5)',
            'America/Denver' => 'Denver (UTC-7/-6)',
            'America/Los_Angeles' => 'Los Angeles (UTC-8/-7)',
            
            // Europa
            'Europe/London' => 'Londres (UTC+0/+1)',
            'Europe/Paris' => 'Paris (UTC+1/+2)',
            'Europe/Berlin' => 'Berlim (UTC+1/+2)',
            'Europe/Moscow' => 'Moscou (UTC+3)',
            
            // Ásia
            'Asia/Tokyo' => 'Tóquio (UTC+9)',
            'Asia/Hong_Kong' => 'Hong Kong (UTC+8)',
            'Asia/Shanghai' => 'Xangai (UTC+8)',
            'Asia/Singapore' => 'Singapura (UTC+8)',
            'Asia/Dubai' => 'Dubai (UTC+4)',
            
            // Oceania
            'Australia/Sydney' => 'Sydney (UTC+10/+11)',
            'Pacific/Auckland' => 'Auckland (UTC+12/+13)',
            
            // UTC
            'UTC' => 'UTC (Tempo Universal)',
        ];
    }
    
    /**
     * Valida se um timezone é suportado
     */
    public static function isValidTimezone(string $timezone): bool
    {
        try {
            new DateTimeZone($timezone);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Converte timestamp Unix para string formatada no timezone especificado
     * 
     * @param int|string $timestamp Unix timestamp ou string de data
     * @param string $timezone Timezone IANA (ex: 'America/Sao_Paulo')
     * @param string $format Formato de saída (padrão: 'Y-m-d H:i:s')
     * @return string|null Data formatada ou null se inválido
     */
    public static function convertTimestamp($timestamp, string $timezone = 'America/Sao_Paulo', string $format = 'Y-m-d H:i:s'): ?string
    {
        if (empty($timestamp)) {
            return null;
        }
        
        try {
            // Se não for válido, retorna null
            if (!self::isValidTimezone($timezone)) {
                $timezone = 'America/Sao_Paulo'; // Fallback para padrão
            }
            
            // Se for numérico, trata como Unix timestamp
            if (is_numeric($timestamp)) {
                $dt = new DateTime('@' . (int)$timestamp, new DateTimeZone('UTC'));
                $dt->setTimezone(new DateTimeZone($timezone));
                return $dt->format($format);
            }
            
            // Se for string, tenta parsear assumindo UTC
            $unixTime = strtotime((string)$timestamp . ' UTC');
            if ($unixTime !== false) {
                $dt = new DateTime('@' . $unixTime, new DateTimeZone('UTC'));
                $dt->setTimezone(new DateTimeZone($timezone));
                return $dt->format($format);
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Converte timestamp para formato de hora apenas (HH:MM:SS)
     */
    public static function convertToTime($timestamp, string $timezone = 'America/Sao_Paulo'): ?string
    {
        return self::convertTimestamp($timestamp, $timezone, 'H:i:s');
    }
    
    /**
     * Converte timestamp para formato de hora curto (HH:MM)
     */
    public static function convertToShortTime($timestamp, string $timezone = 'America/Sao_Paulo'): ?string
    {
        return self::convertTimestamp($timestamp, $timezone, 'H:i');
    }
    
    /**
     * Converte timestamp para formato completo com data e hora
     */
    public static function convertToDateTime($timestamp, string $timezone = 'America/Sao_Paulo'): ?string
    {
        return self::convertTimestamp($timestamp, $timezone, 'd/m/Y H:i:s');
    }
    
    /**
     * Obtém o offset UTC de um timezone (ex: "-03:00")
     */
    public static function getTimezoneOffset(string $timezone): string
    {
        try {
            $tz = new DateTimeZone($timezone);
            $dt = new DateTime('now', $tz);
            $offset = $tz->getOffset($dt);
            
            $hours = abs(floor($offset / 3600));
            $minutes = abs(($offset % 3600) / 60);
            $sign = $offset >= 0 ? '+' : '-';
            
            return sprintf('%s%02d:%02d', $sign, $hours, $minutes);
        } catch (\Exception $e) {
            return '+00:00';
        }
    }
    
    /**
     * Obtém o timezone do usuário atual ou retorna padrão
     */
    public static function getUserTimezone(): string
    {
        // Tenta obter da sessão
        if (isset($_SESSION['user_timezone']) && self::isValidTimezone($_SESSION['user_timezone'])) {
            return $_SESSION['user_timezone'];
        }
        
        // Padrão: Brasília
        return 'America/Sao_Paulo';
    }
}
