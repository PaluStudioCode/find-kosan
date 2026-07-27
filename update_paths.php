<?php

$paths = [
    __DIR__ . '/tests',
    __DIR__ . '/resources/views',
];

$replacements = [
    "route('admin." => "route('__TEMP_SUPERADMIN__.",
    "route('owner." => "route('admin.",
    "route('tenant." => "route('user.",
    "route('__TEMP_SUPERADMIN__." => "route('superadmin.",

    "'/admin/" => "'/__TEMP_SUPERADMIN__/",
    "'/owner/" => "'/admin/",
    "'/tenant/" => "'/user/",
    "'/__TEMP_SUPERADMIN__/" => "'/superadmin/",

    '"/admin/' => '"/__TEMP_SUPERADMIN__/',
    '"/owner/' => '"/admin/',
    '"/tenant/' => '"/user/',
    '"/__TEMP_SUPERADMIN__/' => '"/superadmin/',
];

foreach ($paths as $dir) {
    if (!is_dir($dir)) continue;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && in_array($file->getExtension(), ['php', 'vue', 'js'])) {
            $content = file_get_contents($file->getPathname());
            $originalContent = $content;
            
            $content = str_replace("route('admin.", "route('__TEMP_SUPERADMIN__.", $content);
            $content = str_replace("route('owner.", "route('admin.", $content);
            $content = str_replace("route('tenant.", "route('user.", $content);
            $content = str_replace("route('__TEMP_SUPERADMIN__.", "route('superadmin.", $content);

            $content = str_replace("'/admin/", "'/__TEMP_SUPERADMIN__/", $content);
            $content = str_replace("'/owner/", "'/admin/", $content);
            $content = str_replace("'/tenant/", "'/user/", $content);
            $content = str_replace("'/__TEMP_SUPERADMIN__/", "'/superadmin/", $content);

            $content = str_replace('"/admin/', '"/__TEMP_SUPERADMIN__/', $content);
            $content = str_replace('"/owner/', '"/admin/', $content);
            $content = str_replace('"/tenant/', '"/user/', $content);
            $content = str_replace('"/__TEMP_SUPERADMIN__/', '"/superadmin/', $content);
            
            if ($content !== $originalContent) {
                file_put_contents($file->getPathname(), $content);
            }
        }
    }
}
echo "Tests routes updated!\n";
