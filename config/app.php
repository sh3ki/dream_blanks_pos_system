<?php

return [
    'name'    => $_ENV['APP_NAME'] ?? 'Dream Blanks POS',
    'env'     => $_ENV['APP_ENV'] ?? 'production',
    'debug'   => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url'     => $_ENV['APP_URL'] ?? 'http://localhost',
    'timezone' => 'Asia/Manila',
    'charset'  => 'UTF-8',

    'session' => [
        'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 120),
        'name'     => 'dream_blanks_session',
    ],

    'upload' => [
        'max_size'     => (int)($_ENV['UPLOAD_MAX_SIZE'] ?? 10485760),
        'image_types'  => explode(',', $_ENV['ALLOWED_IMAGE_TYPES'] ?? 'jpg,jpeg,png,gif,webp'),
        'path'         => __DIR__ . '/../public/uploads/',
    ],

    'pagination' => [
        'per_page' => 10,
        'max_per_page' => 100,
    ],
];
