<?php namespace HMMH\GridelementsDoctor\Model {

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

    use PDOStatement;

    /**
     * Class Page
     */
    class Page extends IdentifiedPageModelObject
    {
        const COLUMN_TITLE = 'title';

        /**
         * @var string
         */
        protected $title;

        /**
         * Page constructor.
         */
        public function __construct()
        {
            parent::__construct();

            // Typed property support for older PHP than 7.4
            settype($this->title, 'string');
        }

        /**
         * TODO: PHP 7.4 provides variant return types
         *
         * @param PDOStatement $statement
         *
         * @return Page
         */
        public static function get(PDOStatement $statement): ?ModelObject
        {
            return parent::get($statement);
        }

        /**
         * @return string
         */
        public function getTitle(): string
        {
            return $this->title;
        }
    }
}
