<?php
declare(strict_types=1);

/**
 * FlexiDiet Backend Bootstrap & PSR-4-like Autoloader
 * Tự động nạp các class Controllers, Models, Services mà không cần composer
 */
spl_autoload_register(function (string $class): void {
    // Chỉ nạp class không có namespace hoặc trong project
    $baseDir = __DIR__;
    $directories = [
        $baseDir . '/controllers/',
        $baseDir . '/models/',
        $baseDir . '/services/',
        $baseDir . '/src/',
    ];

    $filename = str_replace('\\', '/', $class) . '.php';

    foreach ($directories as $dir) {
        $file = $dir . $filename;
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
