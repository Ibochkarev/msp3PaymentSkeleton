<?php

/**
 * Resolver: optional tables. Bindings live in optional/bindings until init --keep=bindings.
 */

use xPDO\Transport\xPDOTransport;

/** @var xPDOTransport $transport */
if (!$transport->xpdo || !($transport instanceof xPDOTransport)) {
    return true;
}

return true;
