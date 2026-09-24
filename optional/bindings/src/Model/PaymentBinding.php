<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Model;

use xPDO\Om\xPDOSimpleObject;

/**
 * @property int $id
 * @property int $user_id
 * @property string $kind
 * @property string $request_id
 * @property string $state
 * @property string $masked_pan
 * @property array|string|null $payload
 * @property int $createdon
 * @property int $updatedon
 */
class PaymentBinding extends xPDOSimpleObject
{
    public const KIND_CARD = 'card';
    public const KIND_SBP = 'sbp';
}
