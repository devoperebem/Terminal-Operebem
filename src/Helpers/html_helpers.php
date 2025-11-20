<?php
/**
 * HTML Helpers - Funções para escape seguro de dados em templates
 */

if (!function_exists('e')) {
    /**
     * Escape HTML entities
     * @param mixed $value
     * @return string
     */
    function e($value): string {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('ejs')) {
    /**
     * Escape for safe JavaScript string embedding
     * Converte para JSON e remove aspas externas
     * @param mixed $value
     * @return string
     */
    function ejs($value): string {
        if ($value === null) {
            return '';
        }
        $encoded = json_encode((string)$value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
        // Remove aspas externas do JSON
        return trim($encoded, '"');
    }
}

if (!function_exists('ejson')) {
    /**
     * Encode to safe JSON for HTML attributes
     * @param mixed $value
     * @return string
     */
    function ejson($value): string {
        return htmlspecialchars(
            json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}
