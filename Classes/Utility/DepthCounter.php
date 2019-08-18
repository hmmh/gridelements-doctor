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

    /**
     * Class DepthCounter
     *
     */
    class DepthCounter
    {
        /**
         * @var int
         */
        protected $currentDepth = 0;

        /**
         * @var int
         */
        protected $maxDepth = 0;

        /**
         *
         */
        public function increment(): void
        {
            $this->currentDepth++;
            $this->maxDepth = max($this->currentDepth, $this->maxDepth);
        }

        /**
         *
         */
        public function resetCurrent(): void
        {
            $this->currentDepth = 0;
        }

        /**
         *
         */
        public function decrement(): void
        {
            $this->currentDepth--;
        }

        /**
         * @return int
         */
        public function getMaxDepth(): int
        {
            return $this->maxDepth;
        }
    }
}
