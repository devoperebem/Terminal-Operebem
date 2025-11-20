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
            // Brasil - Todos os fusos horários
            'America/Noronha' => 'Fernando de Noronha, BR (UTC-2)',
            'America/Sao_Paulo' => 'São Paulo, Brasília, BR (UTC-3)',
            'America/Bahia' => 'Salvador, BR (UTC-3)',
            'America/Belem' => 'Belém, BR (UTC-3)',
            'America/Fortaleza' => 'Fortaleza, BR (UTC-3)',
            'America/Recife' => 'Recife, BR (UTC-3)',
            'America/Cuiaba' => 'Cuiabá, BR (UTC-4)',
            'America/Manaus' => 'Manaus, BR (UTC-4)',
            'America/Porto_Velho' => 'Porto Velho, BR (UTC-4)',
            'America/Boa_Vista' => 'Boa Vista, BR (UTC-4)',
            'America/Rio_Branco' => 'Rio Branco, BR (UTC-5)',
            'America/Eirunepe' => 'Eirunepé, BR (UTC-5)',

            // UTC-12 a UTC-1
            'Pacific/Wake' => 'Ilha Wake (UTC+12)',
            'Pacific/Samoa' => 'Samoa Americana (UTC-11)',
            'Pacific/Honolulu' => 'Havaí, EUA (UTC-10)',
            'America/Anchorage' => 'Alasca, EUA (UTC-9/-8)',
            'America/Los_Angeles' => 'Los Angeles, EUA (UTC-8/-7)',
            'America/Denver' => 'Denver, EUA (UTC-7/-6)',
            'America/Chicago' => 'Chicago, EUA (UTC-6/-5)',
            'America/Mexico_City' => 'Cidade do México (UTC-6/-5)',
            'America/New_York' => 'Nova York, EUA (UTC-5/-4)',
            'America/Toronto' => 'Toronto, Canadá (UTC-5/-4)',
            'America/Lima' => 'Lima, Peru (UTC-5)',
            'America/Caracas' => 'Caracas, Venezuela (UTC-4)',
            'America/Santiago' => 'Santiago, Chile (UTC-4/-3)',
            'America/Argentina/Buenos_Aires' => 'Buenos Aires, Argentina (UTC-3)',
            'Atlantic/Cape_Verde' => 'Cabo Verde (UTC-1)',
            'Atlantic/Azores' => 'Açores, Portugal (UTC-1/+0)',

            // UTC+0 (Portugal e Reino Unido)
            'Europe/Lisbon' => 'Lisboa, Portugal (UTC+0/+1)',
            'Europe/London' => 'Londres, Reino Unido (UTC+0/+1)',
            'Africa/Casablanca' => 'Casablanca, Marrocos (UTC+0/+1)',

            // UTC+1 a UTC+3
            'Europe/Paris' => 'Paris, França (UTC+1/+2)',
            'Europe/Berlin' => 'Berlim, Alemanha (UTC+1/+2)',
            'Europe/Madrid' => 'Madrid, Espanha (UTC+1/+2)',
            'Europe/Rome' => 'Roma, Itália (UTC+1/+2)',
            'Europe/Amsterdam' => 'Amsterdã, Holanda (UTC+1/+2)',
            'Africa/Cairo' => 'Cairo, Egito (UTC+2)',
            'Europe/Athens' => 'Atenas, Grécia (UTC+2/+3)',
            'Europe/Istanbul' => 'Istambul, Turquia (UTC+3)',
            'Europe/Moscow' => 'Moscou, Rússia (UTC+3)',
            'Africa/Nairobi' => 'Nairobi, Quênia (UTC+3)',

            // UTC+4 a UTC+7
            'Asia/Dubai' => 'Dubai, Emirados Árabes (UTC+4)',
            'Asia/Karachi' => 'Karachi, Paquistão (UTC+5)',
            'Asia/Kolkata' => 'Mumbai, Índia (UTC+5:30)',
            'Asia/Dhaka' => 'Dhaka, Bangladesh (UTC+6)',
            'Asia/Bangkok' => 'Bangkok, Tailândia (UTC+7)',

            // UTC+8 a UTC+14
            'Asia/Singapore' => 'Singapura (UTC+8)',
            'Asia/Hong_Kong' => 'Hong Kong (UTC+8)',
            'Asia/Shanghai' => 'Xangai, China (UTC+8)',
            'Asia/Taipei' => 'Taipei, Taiwan (UTC+8)',
            'Asia/Tokyo' => 'Tóquio, Japão (UTC+9)',
            'Asia/Seoul' => 'Seul, Coreia do Sul (UTC+9)',
            'Australia/Sydney' => 'Sydney, Austrália (UTC+10/+11)',
            'Australia/Melbourne' => 'Melbourne, Austrália (UTC+10/+11)',
            'Pacific/Guadalcanal' => 'Ilhas Salomão (UTC+11)',
            'Pacific/Auckland' => 'Auckland, Nova Zelândia (UTC+12/+13)',
            'Pacific/Fiji' => 'Fiji (UTC+12/+13)',
            'Pacific/Tongatapu' => 'Tonga (UTC+13)',
            'Pacific/Kiritimati' => 'Kiribati (UTC+14)',

            // UTC
            'UTC' => 'UTC (Tempo Universal Coordenado)',
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
