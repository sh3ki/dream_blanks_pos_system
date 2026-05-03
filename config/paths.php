<?php

return [
    'root'    => ROOT_PATH,
    'src'     => SRC_PATH,
    'config'  => CONFIG_PATH,
    'public'  => PUBLIC_PATH,
    'uploads' => UPLOAD_PATH,
    'logs'    => LOG_PATH,
    'views'   => VIEW_PATH,
    'database'=> DB_PATH,

    'uploads' => [
        'products' => UPLOAD_PATH . '/products',
        'clients'  => UPLOAD_PATH . '/clients',
        'invoices' => UPLOAD_PATH . '/invoices',
    ],
];
