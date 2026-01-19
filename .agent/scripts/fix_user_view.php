<?php
$file = __DIR__ . '/../../src/Views/admin_secure/user_view.php';
$content = file($file);
// Procurar a linha '$scripts = ob_get_clean();'
$newContent = [];
$found = false;
foreach ($content as $line) {
    $newContent[] = $line;
    if (strpos($line, '$scripts = ob_get_clean();') !== false) {
        $found = true;
        break; 
    }
}

if ($found) {
    // Adicionar o final correto
    $newContent[] = "include __DIR__ . '/../../layouts/app.php';\n"; // Ajustado path (../../)
    $newContent[] = "?>\n";
    file_put_contents($file, implode('', $newContent));
    echo "Files fixed.\n";
} else {
    echo "Marker not found.\n";
}
