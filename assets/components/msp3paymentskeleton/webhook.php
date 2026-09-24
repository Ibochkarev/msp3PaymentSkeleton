<?php

/**
 * Optional package webhook when the provider cabinet allows only one URL.
 * Core URL: {site}/assets/components/minishop3/api.php/api/v1/payment/webhook/{payment_method_id}
 */

$script = dirname(__FILE__, 4) . '/config.core.php';
if (!file_exists($script)) {
    http_response_code(500);
    exit('Config not found');
}
require_once $script;
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

$modx = new modX();
$modx->initialize('web');
$modx->getService('error', 'error.modError');
$modx->setLogLevel(modX::LOG_LEVEL_ERROR);
$modx->setLogTarget('FILE');

$corePath = $modx->getOption('msp3paymentskeleton_core_path', null, MODX_CORE_PATH . 'components/msp3paymentskeleton/');
if (file_exists($corePath . 'bootstrap.php')) {
    require_once $corePath . 'bootstrap.php';
}

if (class_exists(\MiniShop3\MiniShop3::class) && !$modx->services->has('ms3')) {
    $modx->getService('MiniShop3', \MiniShop3\MiniShop3::class);
}

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
if ($raw === false || $raw === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Empty body']);
    exit;
}

$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$headers = [];
foreach ($_SERVER as $key => $value) {
    if (str_starts_with($key, 'HTTP_')) {
        $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
        $headers[$name] = (string) $value;
    }
}

$handler = new \Msp3PaymentSkeleton\Service\WebhookHandler($modx);
$result = $handler->handle($raw, $payload, $headers);
http_response_code($result['http']);
echo json_encode([
    'success' => $result['ok'],
    'message' => $result['message'],
]);
