<?php

// Replace model file names
$renameModels = [
    'OwnerWallet.php' => 'AdminWallet.php',
    'OwnerWalletTransaction.php' => 'AdminWalletTransaction.php',
];

foreach ($renameModels as $old => $new) {
    $oldPath = __DIR__ . '/app/Models/' . $old;
    $newPath = __DIR__ . '/app/Models/' . $new;
    if (file_exists($oldPath)) {
        rename($oldPath, $newPath);
    }
}

// Rename migration files (optional, but good for consistency)
$migDir = __DIR__ . '/database/migrations';
$migIterator = new DirectoryIterator($migDir);
foreach ($migIterator as $fileinfo) {
    if (!$fileinfo->isDot()) {
        $filename = $fileinfo->getFilename();
        $newFilename = str_replace(['owner_wallets', 'owner_wallet_transactions'], ['admin_wallets', 'admin_wallet_transactions'], $filename);
        if ($filename !== $newFilename) {
            rename($fileinfo->getPathname(), $migDir . '/' . $newFilename);
        }
    }
}

// Define regex replacements
$replacements = [
    // Identifiers
    '/\bowner_id\b/' => 'admin_id',
    '/\btenant_id\b/' => 'user_id',
    '/\bowner_wallets\b/' => 'admin_wallets',
    '/\bowner_wallet_transactions\b/' => 'admin_wallet_transactions',
    '/\bowner_wallet_id\b/' => 'admin_wallet_id',
    '/\bOwnerWallet\b/' => 'AdminWallet',
    '/\bOwnerWalletTransaction\b/' => 'AdminWalletTransaction',
    
    // Roles
    '/\brole:pemilik_kos\b/' => 'role:admin',
    '/\brole:penyewa\b/' => 'role:user',
    "/'pemilik_kos'/" => "'admin'",
    '/"pemilik_kos"/' => '"admin"',
    "/'penyewa'/" => "'user'",
    '/"penyewa"/' => '"user"',

    // Variable names (safe ones like $invoice->owner -> $invoice->admin)
    // Wait, replacing ->owner with ->admin globally might be dangerous if there are other owner variables. 
    // But since we are renaming the relationship, we must do it.
    '/\-\>owner\b/' => '->admin',
    '/\-\>tenant\b/' => '->user',
    '/\$owner\b/' => '$admin',
    '/\$tenant\b/' => '$user',

    // Eloquent relationship methods definition
    '/function owner\(\)/' => 'function admin()',
    '/function tenant\(\)/' => 'function user()',
    
    // Auth helpers
    '/Auth::user\(\)->role === \'pemilik_kos\'/' => 'Auth::user()->role === \'admin\'',
    '/Auth::user\(\)->role === \'penyewa\'/' => 'Auth::user()->role === \'user\'',
    
    // Send via in Whatsapp
    "/'send_via', \['owner', 'admin'\]/" => "'send_via', ['superadmin', 'admin']",
    "/'send_via' => 'owner'/" => "'send_via' => 'admin'",
    "/'owner'/" => "'admin'", // this one is dangerous, limit it to known arrays
];

$paths = [
    __DIR__ . '/app',
    __DIR__ . '/database',
    __DIR__ . '/tests',
    __DIR__ . '/resources',
];

foreach ($paths as $dir) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && in_array($file->getExtension(), ['php', 'vue', 'js'])) {
            $content = file_get_contents($file->getPathname());
            $originalContent = $content;

            // Apply safe replacements
            $content = str_replace('owner_id', 'admin_id', $content);
            $content = str_replace('tenant_id', 'user_id', $content);
            $content = str_replace('owner_wallets', 'admin_wallets', $content);
            $content = str_replace('owner_wallet_transactions', 'admin_wallet_transactions', $content);
            $content = str_replace('owner_wallet_id', 'admin_wallet_id', $content);
            
            $content = str_replace('OwnerWallet', 'AdminWallet', $content);
            $content = str_replace('OwnerWalletTransaction', 'AdminWalletTransaction', $content);

            $content = str_replace('role:pemilik_kos', 'role:admin', $content);
            $content = str_replace('role:penyewa', 'role:user', $content);
            
            $content = str_replace("'pemilik_kos'", "'admin'", $content);
            $content = str_replace('"pemilik_kos"', '"admin"', $content);
            
            $content = str_replace("'penyewa'", "'user'", $content);
            $content = str_replace('"penyewa"', '"user"', $content);

            $content = str_replace('->owner', '->admin', $content);
            $content = str_replace('->tenant', '->user', $content);
            $content = str_replace('$owner', '$admin', $content);
            $content = str_replace('$tenant', '$user', $content);

            $content = str_replace('function owner()', 'function admin()', $content);
            $content = str_replace('function tenant()', 'function user()', $content);
            
            // Revert accidental change for super_admin to super_admin (in case of overlap)
            
            if ($content !== $originalContent) {
                file_put_contents($file->getPathname(), $content);
            }
        }
    }
}

echo "Database, Models, variables and string tokens updated!\n";
