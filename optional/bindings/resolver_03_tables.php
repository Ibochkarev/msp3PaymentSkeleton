<?php

/**
 * Resolver: create PaymentBinding table.
 */

use xPDO\Transport\xPDOTransport;

/** @var xPDOTransport $transport */
if (!$transport->xpdo || !($transport instanceof xPDOTransport)) {
    return true;
}

$modx = $transport->xpdo;

$table = $modx->getOption('table_prefix') . 'msp3paymentskeleton_bindings';

if ($options[xPDOTransport::PACKAGE_ACTION] === xPDOTransport::ACTION_UNINSTALL) {
    $modx->query('DROP TABLE IF EXISTS `' . $table . '`');
    return true;
}

if ($options[xPDOTransport::PACKAGE_ACTION] !== xPDOTransport::ACTION_INSTALL
    && $options[xPDOTransport::PACKAGE_ACTION] !== xPDOTransport::ACTION_UPGRADE
) {
    return true;
}

$sql = 'CREATE TABLE IF NOT EXISTS `' . $table . '` ('
    . '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
    . '`user_id` INT UNSIGNED NOT NULL DEFAULT 0,'
    . '`kind` VARCHAR(16) NOT NULL DEFAULT \'card\','
    . '`request_id` VARCHAR(191) NOT NULL DEFAULT \'\','
    . '`state` VARCHAR(32) NOT NULL DEFAULT \'\','
    . '`masked_pan` VARCHAR(32) NOT NULL DEFAULT \'\','
    . '`payload` TEXT NULL,'
    . '`createdon` INT UNSIGNED NOT NULL DEFAULT 0,'
    . '`updatedon` INT UNSIGNED NOT NULL DEFAULT 0,'
    . 'PRIMARY KEY (`id`),'
    . 'KEY `user_kind` (`user_id`, `kind`),'
    . 'KEY `request_id` (`request_id`)'
    . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

$modx->query($sql);
$modx->log(modX::LOG_LEVEL_INFO, '[msp3PaymentSkeleton] Ensured table ' . $table);

return true;
