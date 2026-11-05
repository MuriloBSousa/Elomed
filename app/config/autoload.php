<?php
    // Simple autoloader: looks for the class in the folders below by itself.
    spl_autoload_register(function ($class) {
        $folders = [
            __DIR__ . '/../controllers/',
            __DIR__ . '/../models/',
            __DIR__ . '/../middleware/',
            __DIR__ . '/../helpers/',
            __DIR__ . '/../exceptions/',
            __DIR__ . '/../config/',                 // Config, Constants, Env
            dirname(__DIR__, 2) . '/config/',        // Database (project root)
        ];
        foreach ($folders as $folder) {
            $file = $folder . $class . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    });