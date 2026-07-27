<?php

$dir = __DIR__ . '/resources/js';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($iterator as $file) {
    if ($file->isFile() && in_array($file->getExtension(), ['vue', 'js'])) {
        $content = file_get_contents($file->getPathname());
        $originalContent = $content;
        
        $content = str_replace(".owner?", ".admin?", $content);
        $content = str_replace(".owner.", ".admin.", $content);
        $content = str_replace(" withdrawal.owner ", " withdrawal.admin ", $content);
        $content = str_replace(" kos.owner ", " kos.admin ", $content);
        $content = str_replace(" wd.owner ", " wd.admin ", $content);
        
        // Also just replace `.owner` to `.admin` if it doesn't break CSS (.owner-card etc)
        // Wait, .owner-card shouldn't be touched.
        // Let's explicitly replace the ones I saw in the grep result.
        
        // From grep:
        $content = str_replace("kos.owner?.name", "kos.admin?.name", $content);
        $content = str_replace("kos.owner?.email", "kos.admin?.email", $content);
        $content = str_replace("kos.owner?.whatsapp_number", "kos.admin?.whatsapp_number", $content);
        
        $content = str_replace("withdrawal.owner?.name", "withdrawal.admin?.name", $content);
        $content = str_replace("withdrawal.owner?.email", "withdrawal.admin?.email", $content);
        
        $content = str_replace("report.boarding_house?.owner?.name", "report.boarding_house?.admin?.name", $content);
        $content = str_replace("report.boarding_house.owner?.name", "report.boarding_house.admin?.name", $content);
        $content = str_replace("report.boarding_house.owner?.whatsapp_number", "report.boarding_house.admin?.whatsapp_number", $content);
        $content = str_replace("'owner'", "'admin'", $content); // In getWaLink('...', 'owner')
        
        $content = str_replace("tenancy.boarding_house?.owner?.name", "tenancy.boarding_house?.admin?.name", $content);
        $content = str_replace("wd.owner?.name", "wd.admin?.name", $content);
        
        if ($content !== $originalContent) {
            file_put_contents($file->getPathname(), $content);
        }
    }
}
echo "Vue owner to admin updated!\n";
