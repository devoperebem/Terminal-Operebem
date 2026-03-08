<?php

/**
 * Script de teste para validação robusta de números de telefone
 * Testa a validação com diferentes cenários
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\PhoneValidationService;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;

echo "=== TESTE DE VALIDAÇÃO DE TELEFONES ===\n\n";

$phoneUtil = PhoneNumberUtil::getInstance();
$validator = new PhoneValidationService();

// Casos de teste
$testCases = [
    // Brasil - Números válidos
    ['number' => '11987654321', 'country' => 'BR', 'expected' => true, 'description' => 'Celular SP válido'],
    ['number' => '21987654321', 'country' => 'BR', 'expected' => true, 'description' => 'Celular RJ válido'],
    ['number' => '1133334444', 'country' => 'BR', 'expected' => true, 'description' => 'Fixo SP válido'],
    ['number' => '4733334444', 'country' => 'BR', 'expected' => true, 'description' => 'Fixo SC válido'],

    // Brasil - Números inválidos (padrões repetitivos)
    ['number' => '11111111111', 'country' => 'BR', 'expected' => false, 'description' => 'Todos 1s (inválido)'],
    ['number' => '11999999999', 'country' => 'BR', 'expected' => false, 'description' => 'Muitos 9s seguidos (inválido)'],
    ['number' => '11912345678', 'country' => 'BR', 'expected' => false, 'description' => 'Sequência 12345678 (inválido)'],
    ['number' => '11987654321', 'country' => 'BR', 'expected' => false, 'description' => 'Sequência 87654321 (inválido)'],

    // Brasil - DDD inválido
    ['number' => '00987654321', 'country' => 'BR', 'expected' => false, 'description' => 'DDD 00 inválido'],
    ['number' => '99987654321', 'country' => 'BR', 'expected' => false, 'description' => 'DDD 99 inválido (não existe)'],

    // Brasil - Formato incorreto (celular sem 9)
    ['number' => '11887654321', 'country' => 'BR', 'expected' => false, 'description' => 'Celular sem 9 após DDD'],

    // Brasil - Formato incorreto (fixo com 9)
    ['number' => '1193334444', 'country' => 'BR', 'expected' => false, 'description' => 'Fixo com 9 após DDD'],

    // Internacional - Números válidos
    ['number' => '2025551234', 'country' => 'US', 'expected' => true, 'description' => 'Número dos EUA válido'],
    ['number' => '912345678', 'country' => 'PT', 'expected' => true, 'description' => 'Celular Portugal válido'],
    ['number' => '612345678', 'country' => 'ES', 'expected' => true, 'description' => 'Celular Espanha válido'],

    // Internacional - Números inválidos (padrões)
    ['number' => '1111111111', 'country' => 'US', 'expected' => false, 'description' => 'EUA - Todos 1s (inválido)'],
];

$passed = 0;
$failed = 0;

foreach ($testCases as $index => $test) {
    $number = $test['number'];
    $country = $test['country'];
    $expected = $test['expected'];
    $description = $test['description'];

    try {
        // Parse o número
        $phoneNumber = $phoneUtil->parse($number, $country);

        // Valida
        $result = $validator->validatePhoneNumber($phoneNumber, $phoneUtil);
        $isValid = $result['valid'];

        // Verifica se o resultado está correto
        if ($isValid === $expected) {
            echo "✅ PASSOU: {$description}\n";
            echo "   Número: +{$phoneNumber->getCountryCode()} {$number}\n";
            if (!$isValid) {
                echo "   Motivo: {$result['message']}\n";
            }
            $passed++;
        } else {
            echo "❌ FALHOU: {$description}\n";
            echo "   Número: +{$phoneNumber->getCountryCode()} {$number}\n";
            echo "   Esperado: " . ($expected ? 'válido' : 'inválido') . "\n";
            echo "   Obtido: " . ($isValid ? 'válido' : 'inválido') . "\n";
            if (isset($result['message'])) {
                echo "   Mensagem: {$result['message']}\n";
            }
            $failed++;
        }
    } catch (NumberParseException $e) {
        if (!$expected) {
            echo "✅ PASSOU: {$description} (exceção esperada)\n";
            echo "   Erro: {$e->getMessage()}\n";
            $passed++;
        } else {
            echo "❌ FALHOU: {$description}\n";
            echo "   Exceção não esperada: {$e->getMessage()}\n";
            $failed++;
        }
    } catch (\Exception $e) {
        echo "❌ ERRO: {$description}\n";
        echo "   Exceção: {$e->getMessage()}\n";
        $failed++;
    }

    echo "\n";
}

echo "=== RESUMO ===\n";
echo "Total de testes: " . ($passed + $failed) . "\n";
echo "Passou: {$passed}\n";
echo "Falhou: {$failed}\n";
echo "\n";

if ($failed === 0) {
    echo "🎉 Todos os testes passaram!\n";
    exit(0);
} else {
    echo "⚠️  Alguns testes falharam.\n";
    exit(1);
}
