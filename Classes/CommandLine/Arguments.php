<?php namespace HMMH\GridelementsDoctor\CommandLine {

    /*
     * This file is part of the TYPO3 CMS project.
     *
     * It is free software; you can redistribute it and/or modify it under
     * the terms of the GNU General Public License, either version 2
     * of the License, or any later version.
     *
     * For the full copyright and license information, please read the
     * LICENSE.txt file that was distributed with this source code.
     *
     * The TYPO3 project - inspiring people to share!
     */

    use HMMH\GridelementsDoctor\Exceptions\InvalidArgument;
    use HMMH\GridelementsDoctor\Exceptions\InvalidArgumentChange;

    /**
     * Class Arguments
     *
     */
    class Arguments
    {
        const OPTION_HELP = '--help';

        /**
         * @var array
         */
        protected static $arguments = [];

        /**
         * @var bool
         */
        protected static $seal = false;

        /**
         * @param string $name
         * @param mixed $value
         *
         * @throws InvalidArgumentChange
         */
        public static function set(string $name, $value)
        {
            if ((isset(static::$arguments[$name])) && (static::$seal)) {
                throw new InvalidArgumentChange(sprintf('Cannot override argument <%s>!', $name));
            }

            static::$arguments[$name] = $value;
        }

        /**
         * @param string $name
         *
         * @return mixed
         * @throws InvalidArgument
         */
        public static function get(string $name)
        {
            if (!array_key_exists($name, static::$arguments)) {
                throw new InvalidArgument(sprintf('Argument <%s> is not defined!', $name));
            }

            return static::$arguments[$name];
        }

        /**
         *
         */
        public static function seal(): void
        {
            static::$seal = true;
        }
    }
}
