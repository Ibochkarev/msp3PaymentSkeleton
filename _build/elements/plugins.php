<?php

return [
    'msp3paymentskeleton_bootstrap' => [
        'file' => 'msp3paymentskeleton_bootstrap',
        'description' => 'Loads msp3PaymentSkeleton (autoload + MiniShop3 order tab)',
        'events' => [
            'OnMODXInit' => ['priority' => 0],
            'msOnManagerCustomCssJs' => ['priority' => 0],
        ],
    ],
];
