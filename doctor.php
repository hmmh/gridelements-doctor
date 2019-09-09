#!/usr/bin/php
<?php {
    require_once 'vendor/autoload.php';

    exit(
        // TODO: PHP 7.4 use short functions!

        (function (array $arguments) {
            return HMMH\GridelementsDoctor\Doctor::runInstance($arguments);
        })(
            array_slice($argv, 1)
        )
    );
}
