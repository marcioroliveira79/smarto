<?php
declare(strict_types=1);

// Fails if any PHP file has UTF-8 BOM at start.
// Usage: php scripts/check_bom_whitespace.php

$root = dirname(__DIR__);
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$errors = [];

foreach ($it as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    $rel  = ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR);
    if (str_ends_with(strtolower($rel), '.php')) {
        $fh = fopen($path, 'rb');
        if (!$fh) continue;
        $bom = fread($fh, 3);
        fclose($fh);
        if ($bom === "\xEF\xBB\xBF") {
            $errors[] = "$rel: contém BOM UTF-8 (remova)";
            continue;
        }
    }
}

if ($errors) {
    fwrite(STDERR, "Arquivos com BOM encontrado:\n" . implode("\n", $errors) . "\n");
    exit(1);
}

echo "OK: sem BOM em arquivos PHP.\n";

