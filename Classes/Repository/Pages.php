<?php namespace HMMH\GridelementsDoctor\Repository {

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

    use HMMH\GridelementsDoctor\Model\Page;

    /**
     * Class Pages
     *
     */
    class Pages extends RepositoryObject
    {
        /**
         * @var string
         */
        protected static $table = 'pages';

        /**
         * @param int $identifier
         *
         * @return Page|null
         */
        public function getElement(int $identifier): ?Page
        {
            return Page::get($this->getObject($identifier));
        }

        /**
         * @return array
         */
        protected function getColumnsFromModelClass(): array
        {
            return Page::getTableColumns();
        }
    }
}
