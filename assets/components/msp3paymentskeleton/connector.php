<?php

/**
 * Manager connector for msp3PaymentSkeleton processors.
 */

use Msp3PaymentSkeleton\Processors\Mgr\CancelProcessor;
use Msp3PaymentSkeleton\Processors\Mgr\GetListProcessor;
use Msp3PaymentSkeleton\Processors\Mgr\RefundProcessor;
use Msp3PaymentSkeleton\Processors\Mgr\SyncProcessor;

$script = dirname(__FILE__, 4) . '/config.core.php';
if (!file_exists($script)) {
    http_response_code(500);
    exit('Config not found');
}
require_once $script;
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

$modx = new modX();
$modx->initialize('mgr');
$modx->getService('error', 'error.modError');
$modx->lexicon->load('msp3paymentskeleton:default');

$corePath = $modx->getOption('msp3paymentskeleton_core_path', null, MODX_CORE_PATH . 'components/msp3paymentskeleton/');
if (file_exists($corePath . 'bootstrap.php')) {
    require_once $corePath . 'bootstrap.php';
}

if (class_exists(\MiniShop3\MiniShop3::class) && !$modx->services->has('ms3')) {
    $modx->getService('MiniShop3', \MiniShop3\MiniShop3::class);
}

if (!$modx->user || !$modx->user->hasSessionContext('mgr')) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$map = [
    'mgr/getlist' => GetListProcessor::class,
    'mgr/refund' => RefundProcessor::class,
    'mgr/cancel' => CancelProcessor::class,
    'mgr/sync' => SyncProcessor::class,
];

$action = (string) ($_REQUEST['action'] ?? '');
$class = $map[$action] ?? null;
if ($class === null) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

/** @var \MODX\Revolution\Processors\Processor $processor */
$processor = new $class($modx, $_REQUEST);
$response = $processor->run();
$payload = $response instanceof \MODX\Revolution\Processors\ProcessorResponse
    ? $response->getResponse()
    : $response;
header('Content-Type: application/json');
echo is_string($payload) ? $payload : json_encode($payload);
