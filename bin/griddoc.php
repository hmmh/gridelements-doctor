#!/usr/bin/env php
<?php

if ('cli' !== PHP_SAPI) {
    echo sprintf("Warning: SAFE should be invoked via the CLI version of PHP, not the %s SAPI\n\n", PHP_SAPI);
}

if (file_exists(__DIR__ . '/../autoload.php')) {
    require_once __DIR__ . '/../autoload.php';
} elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../../../autoload.php')) {
    require_once __DIR__ . '/../../../autoload.php';
}

use Composer\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

{
    $application = new Application();
    $application->setAutoExit(true);

    try {
        $application->run(new ArrayInput(['command' => 'doctor']), new NullOutput());
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}
