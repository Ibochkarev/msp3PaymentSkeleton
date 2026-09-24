<?php

if (!defined('MODX_CORE_PATH')) {
    if (getenv('MODX_CORE_PATH')) {
        define('MODX_CORE_PATH', rtrim(getenv('MODX_CORE_PATH'), '/') . '/');
    } else {
        $path = dirname(__FILE__);
        while (!file_exists($path . '/core/config/config.inc.php') && (strlen($path) > 1)) {
            $path = dirname($path);
        }
        define('MODX_CORE_PATH', $path . '/core/');
    }
}

$encrypt = getenv('ENCRYPT');
if ($encrypt === false || $encrypt === '') {
    $encryptEnabled = true;
} else {
    $encryptEnabled = !in_array(strtolower((string) $encrypt), ['0', 'false', 'no', 'off'], true);
}

return [
    'name' => 'msp3PaymentSkeleton',
    'name_lower' => 'msp3paymentskeleton',
    'version' => '1.0.0',
    'release' => 'pl',
    'install' => false,
    'update' => [
        'chunks' => false,
        'menus' => true,
        'permission' => true,
        'plugins' => true,
        'policies' => true,
        'policy_templates' => true,
        'resources' => false,
        'settings' => false,
        'snippets' => true,
        'templates' => false,
        'widgets' => false,
    ],
    'static' => [
        'plugins' => false,
        'snippets' => false,
        'chunks' => false,
    ],
    'log_level' => !empty($_REQUEST['download']) ? 0 : 3,
    'log_target' => getenv('BUILD_LOG') ? getenv('BUILD_LOG') : (php_sapi_name() === 'cli' ? 'ECHO' : 'HTML'),
    'download' => !empty($_REQUEST['download']),
    // true — только если пакет в каталоге modstore.pro с поддержкой encode. Локально: ENCRYPT=0.
    'encrypt' => $encryptEnabled,
];
