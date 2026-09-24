<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

if (!class_exists(MiniShop3\Controllers\Payment\Payment::class)) {
    require __DIR__ . '/Stubs/minishop3.php';
}
