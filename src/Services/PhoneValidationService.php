<?php

namespace App\Services;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumber;

/**
 * Serviço de validação robusta de números de telefone
 * Verifica não apenas formato, mas também se o número pode existir
 */
class PhoneValidationService
{
    /**
     * DDDs válidos no Brasil (atualizado 2024)
     */
    private const VALID_BR_AREA_CODES = [
        '11', '12', '13', '14', '15', '16', '17', '18', '19', // SP
        '21', '22', '24', // RJ
        '27', '28', // ES
        '31', '32', '33', '34', '35', '37', '38', // MG
        '41', '42', '43', '44', '45', '46', // PR
        '47', '48', '49', // SC
        '51', '53', '54', '55', // RS
        '61', // DF
        '62', '64', // GO
        '63', // TO
        '65', '66', // MT
        '67', // MS
        '68', // AC
        '69', // RO
        '71', '73', '74', '75', '77', // BA
        '79', // SE
        '81', '87', // PE
        '82', // AL
        '83', // PB
        '84', // RN
        '85', '88', // CE
        '86', '89', // PI
        '91', '93', '94', // PA
        '92', '97', // AM
        '95', // RR
        '96', // AP
        '98', '99' // MA
    ];

    /**
     * Verifica se o número contém padrões repetitivos ou sequenciais inválidos
     */
    private function hasInvalidPattern(string $digitsOnly): bool
    {
        if (strlen($digitsOnly) < 6) {
            return false;
        }

        // Pegar apenas o número local (últimos 10 dígitos)
        $localNumber = strlen($digitsOnly) > 10 ? substr($digitsOnly, -10) : $digitsOnly;

        // Verificar se todos os dígitos são iguais (ex: 1111111111)
        if (preg_match('/^(\d)\1+$/', $localNumber)) {
            return true;
        }

        // Verificar sequências muito longas do mesmo dígito (ex: 111111 ou 999999)
        if (preg_match('/(\d)\1{5,}/', $localNumber)) {
            return true;
        }

        // Verificar sequências crescentes ou decrescentes (ex: 12345 ou 54321)
        for ($i = 0; $i < strlen($localNumber) - 4; $i++) {
            $slice = substr($localNumber, $i, 5);
            $digits = str_split($slice);
            $isSequential = true;
            $diff = (int)$digits[1] - (int)$digits[0];

            if (abs($diff) === 1) {
                for ($j = 1; $j < count($digits) - 1; $j++) {
                    if ((int)$digits[$j + 1] - (int)$digits[$j] !== $diff) {
                        $isSequential = false;
                        break;
                    }
                }
                if ($isSequential) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Valida número brasileiro
     */
    private function validateBrazilianNumber(PhoneNumber $phoneNumber): array
    {
        $nationalNumber = (string)$phoneNumber->getNationalNumber();
        $digitsOnly = preg_replace('/\D/', '', $nationalNumber);

        // Número brasileiro deve ter 10 (fixo) ou 11 (móvel) dígitos
        if (strlen($digitsOnly) < 10 || strlen($digitsOnly) > 11) {
            return [
                'valid' => false,
                'message' => 'Número brasileiro deve ter 10 ou 11 dígitos'
            ];
        }

        // Extrair DDD (2 primeiros dígitos)
        $areaCode = substr($digitsOnly, 0, 2);

        // Verificar se DDD é válido
        if (!in_array($areaCode, self::VALID_BR_AREA_CODES, true)) {
            return [
                'valid' => false,
                'message' => 'DDD inválido. Verifique o código de área.'
            ];
        }

        // Para números móveis (11 dígitos), o terceiro dígito deve ser 9
        if (strlen($digitsOnly) === 11) {
            $thirdDigit = $digitsOnly[2];
            if ($thirdDigit !== '9') {
                return [
                    'valid' => false,
                    'message' => 'Número de celular brasileiro deve começar com 9 após o DDD'
                ];
            }
        }

        // Para números fixos (10 dígitos), o terceiro dígito não pode ser 9
        if (strlen($digitsOnly) === 10) {
            $thirdDigit = $digitsOnly[2];
            if ($thirdDigit === '9') {
                return [
                    'valid' => false,
                    'message' => 'Número fixo não pode começar com 9 após o DDD'
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Valida número internacional (validações básicas)
     */
    private function validateInternationalNumber(PhoneNumber $phoneNumber): array
    {
        $nationalNumber = (string)$phoneNumber->getNationalNumber();
        $digitsOnly = preg_replace('/\D/', '', $nationalNumber);

        // Verificar tamanho mínimo e máximo
        if (strlen($digitsOnly) < 6 || strlen($digitsOnly) > 15) {
            return [
                'valid' => false,
                'message' => 'Número tem comprimento inválido'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Valida um número de telefone de forma robusta
     *
     * @param PhoneNumber $phoneNumber Número já parseado pela libphonenumber
     * @param PhoneNumberUtil $phoneUtil Instância do PhoneNumberUtil
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validatePhoneNumber(PhoneNumber $phoneNumber, PhoneNumberUtil $phoneUtil): array
    {
        // 1. Verificar se o número é válido segundo a biblioteca
        if (!$phoneUtil->isValidNumber($phoneNumber)) {
            return [
                'valid' => false,
                'message' => 'Número de telefone inválido para o país selecionado'
            ];
        }

        // 2. Obter apenas os dígitos do número completo
        $nationalNumber = (string)$phoneNumber->getNationalNumber();
        $digitsOnly = preg_replace('/\D/', '', $nationalNumber);

        // 3. Verificar padrões inválidos (sequências, repetições)
        if ($this->hasInvalidPattern($digitsOnly)) {
            return [
                'valid' => false,
                'message' => 'Este número contém um padrão inválido. Por favor, verifique o número.'
            ];
        }

        // 4. Validações específicas por país
        $regionCode = $phoneUtil->getRegionCodeForNumber($phoneNumber);

        if ($regionCode === 'BR') {
            return $this->validateBrazilianNumber($phoneNumber);
        } else {
            return $this->validateInternationalNumber($phoneNumber);
        }
    }

    /**
     * Valida se um número pode ser móvel (celular)
     *
     * @param PhoneNumber $phoneNumber
     * @param PhoneNumberUtil $phoneUtil
     * @return bool
     */
    public function isMobileNumber(PhoneNumber $phoneNumber, PhoneNumberUtil $phoneUtil): bool
    {
        $numberType = $phoneUtil->getNumberType($phoneNumber);

        return in_array($numberType, [
            \libphonenumber\PhoneNumberType::MOBILE,
            \libphonenumber\PhoneNumberType::FIXED_LINE_OR_MOBILE
        ], true);
    }

    /**
     * Obtém informações detalhadas sobre a validação
     *
     * @param PhoneNumber $phoneNumber
     * @param PhoneNumberUtil $phoneUtil
     * @return array
     */
    public function getValidationDetails(PhoneNumber $phoneNumber, PhoneNumberUtil $phoneUtil): array
    {
        $validation = $this->validatePhoneNumber($phoneNumber, $phoneUtil);
        $regionCode = $phoneUtil->getRegionCodeForNumber($phoneNumber);
        $numberType = $phoneUtil->getNumberType($phoneNumber);

        $typeNames = [
            \libphonenumber\PhoneNumberType::FIXED_LINE => 'Fixo',
            \libphonenumber\PhoneNumberType::MOBILE => 'Móvel',
            \libphonenumber\PhoneNumberType::FIXED_LINE_OR_MOBILE => 'Fixo ou Móvel',
            \libphonenumber\PhoneNumberType::TOLL_FREE => 'Gratuito',
            \libphonenumber\PhoneNumberType::PREMIUM_RATE => 'Premium',
            \libphonenumber\PhoneNumberType::SHARED_COST => 'Custo Compartilhado',
            \libphonenumber\PhoneNumberType::VOIP => 'VoIP',
            \libphonenumber\PhoneNumberType::PERSONAL_NUMBER => 'Número Pessoal',
            \libphonenumber\PhoneNumberType::PAGER => 'Pager',
            \libphonenumber\PhoneNumberType::UAN => 'UAN',
            \libphonenumber\PhoneNumberType::VOICEMAIL => 'Voicemail',
            \libphonenumber\PhoneNumberType::UNKNOWN => 'Desconhecido'
        ];

        return [
            'valid' => $validation['valid'],
            'message' => $validation['message'] ?? null,
            'country' => $regionCode,
            'is_mobile' => $this->isMobileNumber($phoneNumber, $phoneUtil),
            'number_type' => $typeNames[$numberType] ?? 'Desconhecido',
            'national_number' => $phoneNumber->getNationalNumber(),
            'country_code' => $phoneNumber->getCountryCode()
        ];
    }
}
