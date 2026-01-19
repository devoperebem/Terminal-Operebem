<?php
$file = __DIR__ . '/../../src/Views/admin_secure/subscription_plans/index.php';
$content = file($file);
// Procurar a linha '$scripts = ob_get_clean();'
$newContent = [];
$found = false;
foreach ($content as $line) {
    if ($found) break; 
    
    $newContent[] = $line;
    if (strpos($line, '$scripts = ob_get_clean();') !== false) {
        $found = true;
    }
}

if ($found) {
    // Adicionar o final correto
    $newContent[] = "\ninclude __DIR__ . '/../../layouts/app.php';\n";
    $newContent[] = "?>\n";
    file_put_contents($file, implode('', $newContent));
    echo "Files fixed.\n";
} else {
    echo "Marker not found.\n";
}
