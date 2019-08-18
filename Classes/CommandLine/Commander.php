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

    /**
     * Class Commander
     *
     */
    class Commander
    {
        const LINE_WIDTH = 120;

        /**
         * @var int
         */
        protected $startTime;

        /**
         * Commander constructor.
         */
        protected function __construct()
        {
            $this->startTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
        }

        /**
         *
         */
        public function __destruct()
        {
            fwrite(STDOUT, sprintf("%s\n", str_repeat('-', Commander::LINE_WIDTH)));
            fwrite(STDOUT, "\n");

            $this->showElapsedTime();
            $this->showMemoryPeak();

            fwrite(STDOUT, "\n");
        }

        /**
         *
         */
        protected function showElapsedTime(): void
        {
            $endTime = microtime(true);
            $elapsedTime = $endTime - $this->startTime;

            fwrite(STDOUT, sprintf("     Elapsed time: %s\n\n", $this->getElapsedTime($elapsedTime)));
        }

        /**
         * @param int $seconds
         *
         * @return string
         */
        protected function getElapsedTime(int $seconds): string
        {
            if (0 === $seconds) {
                return 'a moment';
            }

            [$days, $hours, $minutes, $seconds] = explode(':', strftime('%j:%H:%M:%S', $seconds));

            return sprintf('%d days %d hours %d minutes %d seconds', max(0, $days - 1), max(0, $hours - 1), $minutes, $seconds);
        }

        /**
         *
         */
        protected function showMemoryPeak(): void
        {
            fwrite(STDOUT, sprintf("      Memory peak: %s bytes\n", number_format(memory_get_peak_usage(true), 0, '', '.')));
        }
    }
}
