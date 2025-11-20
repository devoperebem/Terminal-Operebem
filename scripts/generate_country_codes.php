<?php

/**
 * Download country dialing codes with ISO information and store them in
 * public/assets/data/country-codes.json
 */
$url = 'https://restcountries.com/v3.1/all?fields=name,idd,cca2';
$response = @file_get_contents($url);
if ($response === false) {
    fwrite(STDERR, "Falha ao baixar dados dos países.\n");
    exit(1);
}
$data = json_decode($response, true);
if (!is_array($data)) {
    fwrite(STDERR, "JSON inválido recebido da API de países.\n");
    exit(1);
}
$entries = [];
$seenIso = [];
foreach ($data as $country) {
    $name = $country['name']['common'] ?? null;
    $iso = strtoupper($country['cca2'] ?? '');
    $idd = $country['idd'] ?? [];
    $root = $idd['root'] ?? '';
    $suffixes = $idd['suffixes'] ?? [];
    if (!$name || !$iso || !$root) {
        continue;
    }
    $dialCode = $root;
    if (is_array($suffixes) && count($suffixes) === 1 && $suffixes[0] !== null && $suffixes[0] !== '') {
        $dialCode .= $suffixes[0];
    }
    if ($dialCode === '' || $dialCode === '+') {
        continue;
    }
    if ($dialCode[0] !== '+') {
        $dialCode = '+' . ltrim($dialCode, '+');
    }
    if (isset($seenIso[$iso])) {
        // Prefer first occurrence (geralmente país principal antes dos territórios)
        continue;
    }
    $seenIso[$iso] = true;
    $entries[] = [
        'country' => $name,
        'dial_code' => $dialCode,
        'iso' => $iso
    ];
}
usort($entries, function ($a, $b) {
    $dialA = $a['dial_code'] ?? '';
    $dialB = $b['dial_code'] ?? '';
    if ($dialA === '+55' && $dialB !== '+55') {
        return -1;
    }
    if ($dialB === '+55' && $dialA !== '+55') {
        return 1;
    }
    return strcmp($a['country'] ?? '', $b['country'] ?? '');
});
$targetDir = __DIR__ . '/../public/assets/data';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}
$targetFile = $targetDir . '/country-codes.json';
file_put_contents($targetFile, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Arquivo atualizado em {$targetFile} (" . count($entries) . " países)\n";
