<?php

/**
 * @var \MODX\Revolution\modX $modx
 * @var array $scriptProperties
 */

use Msp3PaymentSkeleton\Service\BindingService;

$corePath = $modx->getOption('msp3paymentskeleton_core_path', null, $modx->getOption('core_path') . 'components/msp3paymentskeleton/');
if (file_exists($corePath . 'bootstrap.php')) {
    require_once $corePath . 'bootstrap.php';
}

$userId = (int) ($scriptProperties['user'] ?? ($modx->user ? $modx->user->get('id') : 0));
if ($userId < 1) {
    return '';
}

$tpl = (string) ($scriptProperties['tpl'] ?? '');
$service = new BindingService($modx);
$action = (string) ($scriptProperties['action'] ?? ($_POST['paymentskeleton_action'] ?? ''));
$successUrl = (string) ($scriptProperties['successUrl'] ?? '');
$failUrl = (string) ($scriptProperties['failUrl'] ?? '');
if ($action === 'link' && $successUrl !== '' && $failUrl !== '') {
    try {
        $started = $service->startBinding($userId, $successUrl, $failUrl);
        $link = (string) ($started['confirmation_url'] ?? $started['link'] ?? '');
        if ($link !== '') {
            $modx->sendRedirect($link);
        }
    } catch (\Throwable $e) {
        $modx->log(modX::LOG_LEVEL_ERROR, '[msp3PaymentSkeleton] link failed: ' . $e->getMessage());
    }
}

$rows = [];
foreach ($service->listForUser($userId) as $binding) {
    $rows[] = $binding->toArray();
}

if ($tpl !== '' && $modx->getService('pdoTools')) {
    $pdoTools = $modx->getService('pdoTools');
    if (is_object($pdoTools) && method_exists($pdoTools, 'getChunk')) {
        $out = '';
        foreach ($rows as $row) {
            $out .= $pdoTools->getChunk($tpl, $row);
        }
        return $out;
    }
}

if ($rows === []) {
    return '';
}

$html = '<ul class="paymentskeleton-bindings">';
foreach ($rows as $row) {
    $html .= '<li>'
        . htmlspecialchars((string) $row['kind'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        . ' '
        . htmlspecialchars((string) $row['state'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        . '</li>';
}
$html .= '</ul>';

return $html;
