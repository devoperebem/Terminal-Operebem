<?php

namespace App\Services;

use App\Core\Application;

class CpfService
{
    private string $apiKey;
    private string $baseUrl = 'https://apicpf.com';

    public function __construct()
    {
        $this->apiKey = $_ENV['APICPF_KEY'] ?? '';
    }

    /**
     * Consulta dados de uma pessoa pelo CPF
     */
    public function consultarCpf(string $cpf): array
    {
        try {
            // Limpar CPF (remover pontos, traços, espaços)
            $cpf = preg_replace('/[^0-9]/', '', $cpf);

            // Validar CPF
            if (!$this->validarCpf($cpf)) {
                return [
                    'success' => false,
                    'message' => 'CPF inválido'
                ];
            }

            // Fazer requisição para API
            $response = $this->makeApiRequest($cpf);

            if ($response['success']) {
                return [
                    'success' => true,
                    'data' => [
                        'cpf' => $response['data']['cpf'],
                        'nome' => $response['data']['nome'],
                        'genero' => $response['data']['genero'],
                        'data_nascimento' => $response['data']['data_nascimento']
                    ]
                ];
            } else {
                return $response;
            }

        } catch (\Exception $e) {
            Application::getInstance()->logger()->error('Erro na consulta CPF: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno na consulta. Tente novamente.'
            ];
        }
    }

    /**
     * Valida se o CPF é válido
     */
    private function validarCpf(string $cpf): bool
    {
        // Verificar se tem 11 dígitos
        if (strlen($cpf) !== 11) {
            return false;
        }

        // Verificar se não são todos números iguais
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Validar dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    /**
     * Faz requisição para a API
     */
    private function makeApiRequest(string $cpf): array
    {
        $url = $this->baseUrl . '/api/consulta?cpf=' . $cpf;
        
        // Log da requisição para debug
        Application::getInstance()->logger()->info("Consultando CPF na API: {$url}");

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'X-API-KEY: ' . $this->apiKey,
                'Content-Type: application/json',
                'User-Agent: Terminal-Operebem/1.0',
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => false, // Desabilitar verificação SSL para desenvolvimento
            CURLOPT_SSL_VERIFYHOST => false, // Desabilitar verificação SSL para desenvolvimento
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        // Log detalhado para debug
        Application::getInstance()->logger()->info("Resposta da API CPF - HTTP: {$httpCode}, Tamanho: " . strlen($response));

        if ($error) {
            Application::getInstance()->logger()->error('Erro cURL na consulta CPF: ' . $error);
            Application::getInstance()->logger()->error('Info cURL: ' . json_encode($info));
            return [
                'success' => false,
                'message' => 'Erro de conexão com o serviço de consulta: ' . $error
            ];
        }

        if ($response === false) {
            Application::getInstance()->logger()->error('Resposta vazia da API CPF');
            return [
                'success' => false,
                'message' => 'Serviço de consulta indisponível'
            ];
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Application::getInstance()->logger()->error('Erro ao decodificar JSON da API CPF: ' . json_last_error_msg());
            Application::getInstance()->logger()->error('Resposta raw: ' . substr($response, 0, 500));
            return [
                'success' => false,
                'message' => 'Resposta inválida do serviço de consulta'
            ];
        }

        if ($httpCode === 200 && $data && isset($data['code']) && $data['code'] === 200) {
            Application::getInstance()->logger()->info('Consulta CPF bem-sucedida para: ' . $cpf);
            return [
                'success' => true,
                'data' => $data['data']
            ];
        }

        // Tratar erros específicos da API
        $errorMessage = $this->getErrorMessage($httpCode, $data);
        
        Application::getInstance()->logger()->warning("Consulta CPF falhou - HTTP {$httpCode}: " . ($data['message'] ?? 'Erro desconhecido'));
        Application::getInstance()->logger()->warning("Resposta completa: " . json_encode($data));

        return [
            'success' => false,
            'message' => $errorMessage
        ];
    }

    /**
     * Converte códigos de erro da API em mensagens amigáveis
     */
    private function getErrorMessage(int $httpCode, ?array $data): string
    {
        if ($data && isset($data['message'])) {
            switch ($httpCode) {
                case 400:
                    return 'CPF inválido ou mal formatado';
                case 401:
                    return 'Erro de autenticação no serviço';
                case 403:
                    return 'Acesso negado ao serviço de consulta';
                case 404:
                    return 'CPF não encontrado na base de dados';
                case 429:
                    return 'Limite de consultas excedido. Tente novamente mais tarde.';
                default:
                    return $data['message'];
            }
        }

        return 'Erro na consulta do CPF. Tente novamente.';
    }

    /**
     * Formatar CPF para exibição
     */
    public static function formatarCpf(string $cpf): string
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }

    /**
     * Formatar data de nascimento
     */
    public static function formatarDataNascimento(string $data): string
    {
        try {
            $date = new \DateTime($data);
            return $date->format('d/m/Y');
        } catch (\Exception $e) {
            return $data;
        }
    }

    /**
     * Converter gênero para texto
     */
    public static function formatarGenero(string $genero): string
    {
        switch (strtoupper($genero)) {
            case 'M':
                return 'Masculino';
            case 'F':
                return 'Feminino';
            case 'I':
                return 'Não informado';
            default:
                return 'Não informado';
        }
    }
}
