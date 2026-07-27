<?php

$paths = [
    __DIR__ . '/app/Http/Controllers',
    __DIR__ . '/app/Http/Middleware',
];

foreach ($paths as $dir) {
    if (!is_dir($dir)) continue;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            $originalContent = $content;
            
            $content = str_replace("'owner:id,name,whatsapp_number,email'", "'admin:id,name,whatsapp_number,email'", $content);
            $content = str_replace("with('owner')", "with('admin')", $content);
            $content = str_replace("with(['owner'", "with(['admin'", $content);
            $content = str_replace("boardingHouse.owner", "boardingHouse.admin", $content);
            $content = str_replace("load(['owner'", "load(['admin'", $content);
            $content = str_replace("route('owner.dashboard')", "route('admin.dashboard')", $content);
            $content = str_replace("ban_owner", "ban_admin", $content);
            
            if ($content !== $originalContent) {
                file_put_contents($file->getPathname(), $content);
            }
        }
    }
}
echo "Controller relationships updated!\n";
