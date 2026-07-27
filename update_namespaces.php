<?php

$replacements = [
    'SuperAdmin' => ['search' => 'namespace App\Http\Controllers\Admin;', 'replace' => 'namespace App\Http\Controllers\SuperAdmin;'],
    'Admin' => ['search' => 'namespace App\Http\Controllers\Owner;', 'replace' => 'namespace App\Http\Controllers\Admin;'],
    'User' => ['search' => 'namespace App\Http\Controllers\Tenant;', 'replace' => 'namespace App\Http\Controllers\User;']
];

foreach ($replacements as $folder => $rules) {
    $dir = __DIR__ . '/app/Http/Controllers/' . $folder;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            $content = str_replace($rules['search'], $rules['replace'], $content);
            file_put_contents($file->getPathname(), $content);
        }
    }
}

echo "Namespaces updated!\n";
