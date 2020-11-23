<?php
declare(strict_types=1);
mb_internal_encoding('UTF-8');
error_reporting(E_ALL); // for debug - TODO delete on production

// settings
const BASE_DIR = __DIR__;

// autoload
spl_autoload_register(function ($class) {
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class . '.php');
    $fullPath = BASE_DIR . DIRECTORY_SEPARATOR . $path;

    if (file_exists($fullPath)) {
        require_once "{$fullPath}";
    }
});