<?php

/**
 * msp3PaymentSkeleton bootstrap — PSR-4 autoload and model package.
 */

$corePath = $modx->getOption('msp3paymentskeleton_core_path', null, $modx->getOption('core_path') . 'components/msp3paymentskeleton/');

$srcPath = rtrim($corePath, '/') . '/src/';
spl_autoload_register(static function (string $class) use ($srcPath): bool {
    if (strpos($class, 'Msp3PaymentSkeleton\\') !== 0) {
        return false;
    }
    $relative = str_replace('Msp3PaymentSkeleton\\', '', $class);
    $file = $srcPath . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
        return true;
    }
    return false;
}, true, true);

$modelPath = rtrim($corePath, '/') . '/src/';
if (method_exists($modx, 'addPackage')) {
    $modx->addPackage('Msp3PaymentSkeleton\\Model', $modelPath, null, 'Msp3PaymentSkeleton\\Model\\');
}

if (defined('IN_MANAGER_MODE') && IN_MANAGER_MODE) {
    $modx->lexicon->load('msp3paymentskeleton:default');
    $modx->lexicon->load('msp3paymentskeleton:setting');
}
