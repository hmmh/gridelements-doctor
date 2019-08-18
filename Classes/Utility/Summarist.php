<?php namespace HMMH\GridelementsDoctor\Utility {

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

    use HMMH\GridelementsDoctor\CommandLine\Commander;
    use HMMH\GridelementsDoctor\Exceptions\CollectorNotFound;

    /**
     * Class Summarist
     *
     */
    abstract class Summarist
    {
        /**
         * @var Collector[]
         */
        public $collectors = [];

        /**
         * Summarist constructor.
         *
         * @param Collector[] $collector
         */
        public function __construct(Collector ...$collector)
        {
            $this->collectors = $collector;
        }

        /**
         * @param string $name
         *
         * @return Collector|null
         * @throws CollectorNotFound
         */
        public function get(string $name): ?Collector
        {
            foreach ($this->collectors as $collector) {
                if ($name === $collector->getName()) {
                    return $collector;
                }
            }

            throw new CollectorNotFound('Cannot find collector named <%s>!', $name);
        }

        /**
         *
         */
        abstract public function speak(): void;

        /**
         * @param string $message
         * @param array $sprintfArguments
         */
        protected function echo(string $message = '', ...$sprintfArguments)
        {
            fwrite(STDOUT, sprintf(" %s\n", sprintf($message, ...$sprintfArguments)));
        }

        /**
         * @param string $headline
         */
        protected function echoCategoryLine(string $headline): void
        {
            fwrite(STDOUT, sprintf("%s\n", str_pad(sprintf(' %s', $headline), Commander::LINE_WIDTH, '-', STR_PAD_LEFT)));
        }

        /**
         * @param string $message
         * @param string $tokenName
         */
        protected function echoStatements(string $message, string $tokenName): void
        {
            foreach ($this->collectors as $collector) {
                $collector->echoStatement($message, $tokenName);
            }
        }

        /**
         *
         */
        protected function echoHorizontalLine(): void
        {
            fwrite(STDOUT, sprintf("%s\n", str_repeat('-', Commander::LINE_WIDTH)));
        }
    }
}
