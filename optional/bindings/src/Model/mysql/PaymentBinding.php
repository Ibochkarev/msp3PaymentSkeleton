<?php

declare(strict_types=1);

namespace Msp3PaymentSkeleton\Model\mysql;

use Msp3PaymentSkeleton\Model\PaymentBinding as Base;

class PaymentBinding extends Base
{
    public static $metaMap = [
        'package' => 'Msp3PaymentSkeleton\\Model',
        'version' => '3.0',
        'table' => 'msp3paymentskeleton_bindings',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'tableMeta' => [
            'engine' => 'InnoDB',
        ],
        'fields' => [
            'user_id' => 0,
            'kind' => 'card',
            'request_id' => '',
            'state' => '',
            'masked_pan' => '',
            'payload' => null,
            'createdon' => 0,
            'updatedon' => 0,
        ],
        'fieldMeta' => [
            'user_id' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'integer', 'null' => false, 'default' => 0],
            'kind' => ['dbtype' => 'varchar', 'precision' => '16', 'phptype' => 'string', 'null' => false, 'default' => 'card'],
            'request_id' => ['dbtype' => 'varchar', 'precision' => '191', 'phptype' => 'string', 'null' => false, 'default' => ''],
            'state' => ['dbtype' => 'varchar', 'precision' => '32', 'phptype' => 'string', 'null' => false, 'default' => ''],
            'masked_pan' => ['dbtype' => 'varchar', 'precision' => '32', 'phptype' => 'string', 'null' => false, 'default' => ''],
            'payload' => ['dbtype' => 'text', 'phptype' => 'json', 'null' => true],
            'createdon' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'integer', 'null' => false, 'default' => 0],
            'updatedon' => ['dbtype' => 'int', 'precision' => '10', 'attributes' => 'unsigned', 'phptype' => 'integer', 'null' => false, 'default' => 0],
        ],
        'indexes' => [
            'user_kind' => ['alias' => 'user_kind', 'primary' => false, 'unique' => false, 'type' => 'BTREE', 'columns' => ['user_id' => [], 'kind' => []]],
            'request_id' => ['alias' => 'request_id', 'primary' => false, 'unique' => false, 'type' => 'BTREE', 'columns' => ['request_id' => []]],
        ],
    ];
}
