<?php

namespace App\Services;

class RecaptchaService
{
    private ?string $secret;
    private float $threshold;

    public function __construct(?string $secret = null, float $threshold = 0.3)
    {
        $this->secret = $secret ?: ($_ENV['RECAPTCHA_V3_SECRET'] ?? null);
        $this->threshold = $threshold;
    }

    public function isConfigured(): bool
    {
        return !empty($this->secret);
    }

    public function verify(?string $token, ?string $remoteIp = null, string $action = 'login'): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => true, 'score' => 1.0, 'skipped' => true];
        }
        if (!$token) {
            return ['ok' => false, 'score' => 0.0, 'error' => 'missing-token'];
        }
        $data = http_build_query([
            'secret' => $this->secret,
            'response' => $token,
            'remoteip' => $remoteIp ?? ''
        ]);
        try {
            $j = null;
            if (function_exists('curl_init')) {
                $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                $res = curl_exec($ch);
                if ($res === false) {
                    $err = curl_error($ch);
                    curl_close($ch);
                    return ['ok' => false, 'score' => 0.0, 'error' => 'curl:' . ($err ?: 'error')];
                }
                curl_close($ch);
                $j = json_decode($res, true);
            } else {
                $opts = [
                    'http' => [
                        'method' => 'POST',
                        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                        'content' => $data,
                        'timeout' => 5,
                    ]
                ];
                $ctx = stream_context_create($opts);
                $res = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $ctx);
                if ($res === false) {
                    return ['ok' => false, 'score' => 0.0, 'error' => 'network'];
                }
                $j = json_decode($res, true);
            }
            $success = (bool)($j['success'] ?? false);
            $score = (float)($j['score'] ?? 0.0);
            $act = (string)($j['action'] ?? '');
            $ok = $success && $score >= $this->threshold && ($act === '' || $act === $action);
            return ['ok' => $ok, 'score' => $score, 'raw' => $j];
        } catch (\Throwable $t) {
            return ['ok' => false, 'score' => 0.0, 'error' => 'exception'];
        }
    }
}
