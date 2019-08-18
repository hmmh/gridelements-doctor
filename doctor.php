#!/usr/bin/php
<?php {
    require_once 'vendor/autoload.php';

    exit(
        (function (array $arguments) {
            return HMMH\GridelementsDoctor\Doctor::runInstance($arguments);
        })(
            array_slice($argv, 1)
        )
    );
}
