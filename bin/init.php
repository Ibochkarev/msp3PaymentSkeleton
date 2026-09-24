<?php

declare(strict_types=1);

/**
 * Rename msp3PaymentSkeleton into a new extra.
 *
 * php bin/init.php --name=msp3MyPay --provider=MyPay [--keep=second-method,bindings] [--no-encrypt] [--self-destruct]
 */

$root = dirname(__DIR__);
$opts = getopt('', ['name:', 'provider:', 'keep:', 'no-encrypt', 'self-destruct', 'skip-tests']);
$name = (string) ($opts['name'] ?? '');
$provider = (string) ($opts['provider'] ?? '');
if ($name === '' || $provider === '') {
    fwrite(STDERR, "Usage: php bin/init.php --name=msp3MyPay --provider=MyPay [--keep=second-method,bindings] [--no-encrypt]\n");
    exit(1);
}
if (!preg_match('/^msp3[A-Z][A-Za-z0-9]+$/', $name)) {
    fwrite(STDERR, "name must look like msp3MyPay\n");
    exit(1);
}
if (!preg_match('/^[A-Z][A-Za-z0-9]+$/', $provider)) {
    fwrite(STDERR, "provider must look like MyPay\n");
    exit(1);
}

$keep = array_filter(array_map('trim', explode(',', (string) ($opts['keep'] ?? ''))));
$allowedKeep = ['second-method', 'bindings', 'receipts-extra'];
foreach ($keep as $mod) {
    if (!in_array($mod, $allowedKeep, true)) {
        fwrite(STDERR, "Unknown --keep module: {$mod}\n");
        exit(1);
    }
}

$nameLower = strtolower($name);
$namespace = 'Msp3' . substr($name, 4);
$providerLower = strtolower($provider);
$providerUpper = strtoupper($provider);

echo "Applying optional modules...\n";
applyOptional($root, $keep);

if (array_key_exists('no-encrypt', $opts)) {
    $config = $root . '/_build/config.inc.php';
    $src = (string) file_get_contents($config);
    $src = preg_replace('/\$encryptEnabled = true;/', '$encryptEnabled = false;', $src, 1);
    file_put_contents($config, $src);
}

echo "Replacing tokens...\n";
$replacements = [
    'SkeletonPayment' => $provider . 'Payment',
    'SkeletonSecondPayment' => $provider . 'SecondPayment',
    'msp3PaymentSkeleton' => $name,
    'Msp3PaymentSkeleton' => $namespace,
    'msp3paymentskeleton' => $nameLower,
    'PAYMENTSKELETON' => $providerUpper,
    'Payment Skeleton' => $provider,
    'PaymentSkeleton' => $provider,
    'paymentskeleton' => $providerLower,
];

$files = listTextFiles($root);
foreach ($files as $file) {
    $contents = (string) file_get_contents($file);
    $updated = str_replace(array_keys($replacements), array_values($replacements), $contents);
    if ($updated !== $contents) {
        file_put_contents($file, $updated);
    }
}

echo "Renaming paths...\n";
renamePaths($root, $replacements);

if (!array_key_exists('skip-tests', $opts)) {
    $component = $root . '/core/components/' . $nameLower;
    if (!is_dir($component)) {
        $component = $root . '/core/components/msp3paymentskeleton';
    }
    runOrFail('composer install --no-interaction', $component);
    runOrFail('composer test', $component);
    $phpFiles = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $path = $file->getPathname();
        if (str_contains($path, '/vendor/') || str_contains($path, '/.phpunit.cache/')) {
            continue;
        }
        $phpFiles[] = $path;
    }
    foreach ($phpFiles as $phpFile) {
        runOrFail('php -l ' . escapeshellarg($phpFile), $root);
    }
}

if (array_key_exists('self-destruct', $opts)) {
    $init = $root . '/bin/init.php';
    if (is_file($init)) {
        unlink($init);
    }
}

echo "Done. Next: fill PROVIDER: blocks, then ENCRYPT=0 php _build/build.php\n";
exit(0);

/**
 * @param list<string> $keep
 */
function applyOptional(string $root, array $keep): void
{
    $optional = $root . '/optional';
    if (in_array('second-method', $keep, true)) {
        $src = $optional . '/second-method/SkeletonSecondPayment.php';
        $dest = $root . '/core/components/msp3paymentskeleton/src/Payment/SkeletonSecondPayment.php';
        copy($src, $dest);
        $append = include $optional . '/second-method/resolver_payment_append.php';
        $resolver = $root . '/_build/resolvers/resolver_02_payment.php';
        $code = (string) file_get_contents($resolver);
        if (!str_contains($code, 'SkeletonSecondPayment')) {
            $snippet = "    [\n"
                . "        'name' => " . var_export($append['name'], true) . ",\n"
                . "        'description' => " . var_export($append['description'], true) . ",\n"
                . "        'class' => " . var_export($append['class'], true) . ",\n"
                . "    ],\n    // INIT:SECOND_METHOD";
            $code = str_replace('    // INIT:SECOND_METHOD', $snippet, $code);
            file_put_contents($resolver, $code);
        }
    }

    if (in_array('bindings', $keep, true)) {
        copyDir($optional . '/bindings/src/Model', $root . '/core/components/msp3paymentskeleton/src/Model');
        copy($optional . '/bindings/src/Service/BindingService.php', $root . '/core/components/msp3paymentskeleton/src/Service/BindingService.php');
        @mkdir($root . '/core/components/msp3paymentskeleton/elements/snippets', 0777, true);
        copy(
            $optional . '/bindings/elements/snippets/paymentskeletonbindings.php',
            $root . '/core/components/msp3paymentskeleton/elements/snippets/paymentskeletonbindings.php'
        );
        copy(
            $optional . '/bindings/elements/snippets/paymentskeletonbindingreturn.php',
            $root . '/core/components/msp3paymentskeleton/elements/snippets/paymentskeletonbindingreturn.php'
        );
        copy($optional . '/bindings/resolver_03_tables.php', $root . '/_build/resolvers/resolver_03_tables.php');
        copy($optional . '/bindings/snippets.php', $root . '/_build/elements/snippets.php');
    }

    if (in_array('receipts-extra', $keep, true)) {
        foreach (['ReceiptsProcessor', 'FullPaymentProcessor', 'CorrectionProcessor'] as $class) {
            copy(
                $optional . '/receipts-extra/src/Processors/Mgr/' . $class . '.php',
                $root . '/core/components/msp3paymentskeleton/src/Processors/Mgr/' . $class . '.php'
            );
        }
        $connector = $root . '/assets/components/msp3paymentskeleton/connector.php';
        $src = (string) file_get_contents($connector);
        $use = "use Msp3PaymentSkeleton\\Processors\\Mgr\\CancelProcessor;\n"
            . "use Msp3PaymentSkeleton\\Processors\\Mgr\\CorrectionProcessor;\n"
            . "use Msp3PaymentSkeleton\\Processors\\Mgr\\FullPaymentProcessor;\n"
            . "use Msp3PaymentSkeleton\\Processors\\Mgr\\GetListProcessor;\n"
            . "use Msp3PaymentSkeleton\\Processors\\Mgr\\ReceiptsProcessor;\n"
            . "use Msp3PaymentSkeleton\\Processors\\Mgr\\RefundProcessor;\n"
            . "use Msp3PaymentSkeleton\\Processors\\Mgr\\SyncProcessor;\n";
        $src = preg_replace('/use Msp3PaymentSkeleton\\\\Processors\\\\Mgr\\\\CancelProcessor;.*?use Msp3PaymentSkeleton\\\\Processors\\\\Mgr\\\\SyncProcessor;/s', trim($use), $src);
        $src = str_replace(
            "'mgr/sync' => SyncProcessor::class,\n];",
            "'mgr/sync' => SyncProcessor::class,\n    'mgr/receipts' => ReceiptsProcessor::class,\n    'mgr/fullpayment' => FullPaymentProcessor::class,\n    'mgr/correction' => CorrectionProcessor::class,\n];",
            $src
        );
        file_put_contents($connector, $src);
    }
}

/**
 * @return list<string>
 */
function listTextFiles(string $root): array
{
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $path = $file->getPathname();
        if (str_contains($path, '/.git/') || str_contains($path, '/vendor/') || str_contains($path, '/.phpunit.cache/')) {
            continue;
        }
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, ['php', 'md', 'txt', 'js', 'css', 'json', 'xml', 'inc'], true)) {
            continue;
        }
        $out[] = $path;
    }

    return $out;
}

/**
 * @param array<string, string> $replacements
 */
function renamePaths(string $root, array $replacements): void
{
    $dirs = [];
    $files = [];
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $file) {
        $path = $file->getPathname();
        if (str_contains($path, '/.git/') || str_contains($path, '/vendor/')) {
            continue;
        }
        $base = $file->getFilename();
        $newBase = str_replace(array_keys($replacements), array_values($replacements), $base);
        if ($newBase === $base) {
            continue;
        }
        $target = $file->getPath() . '/' . $newBase;
        if ($file->isDir()) {
            $dirs[] = [$path, $target];
        } else {
            $files[] = [$path, $target];
        }
    }
    foreach ($files as [$from, $to]) {
        @mkdir(dirname($to), 0777, true);
        rename($from, $to);
    }
    foreach ($dirs as [$from, $to]) {
        if (is_dir($from) && !is_dir($to)) {
            rename($from, $to);
        }
    }
}

function copyDir(string $from, string $to): void
{
    @mkdir($to, 0777, true);
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $item) {
        $target = $to . '/' . substr($item->getPathname(), strlen($from) + 1);
        if ($item->isDir()) {
            @mkdir($target, 0777, true);
        } else {
            @mkdir(dirname($target), 0777, true);
            copy($item->getPathname(), $target);
        }
    }
}

function runOrFail(string $command, string $cwd): void
{
    $full = 'cd ' . escapeshellarg($cwd) . ' && ' . $command;
    echo $full . "\n";
    passthru($full, $code);
    if ($code !== 0) {
        fwrite(STDERR, "Command failed ({$code}): {$command}\n");
        exit($code);
    }
}
