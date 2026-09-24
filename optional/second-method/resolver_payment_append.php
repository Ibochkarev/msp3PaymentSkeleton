<?php

/**
 * Fragment for resolver_02_payment.php when --keep=second-method.
 * bin/init.php merges this class into the msPayment list.
 */

return [
    'name' => 'Оплата через Payment Skeleton (второй способ)',
    'description' => 'Второй сценарий оплаты (QR, холд, СБП).',
    'class' => 'Msp3PaymentSkeleton\\Payment\\SkeletonSecondPayment',
];
