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
     * Class Content
     */
    class Content extends IdentifiedPageModelObject
    {
        const COLUMN_CONTENT_TYPE = 'CType';

        const COLUMN_LOCALIZATION_PARENT = 'l18n_parent';

        const COLUMN_COLUMN_POSITION = 'colPos';

        const COLUMN_WEBSITE_LANGUAGE = 'sys_language_uid';

        const COLUMN_GRIDELEMENTS_CONTAINER = 'tx_gridelements_container';

        const COLUMN_GRIDELEMENTS_CHILDREN = 'tx_gridelements_children';

        const COLUMN_GRIDELEMENTS_COLUMN = 'tx_gridelements_columns';

        /**
         * @var int
         */
        protected $l18n_parent;

        /**
         * @var string
         */
        protected $CType;

        /**
         * @var int
         */
        protected $colPos;

        /**
         * @var int
         */
        protected $sys_language_uid;

        /**
         * @var int
         */
        protected $tx_gridelements_container;

        /**
         * @var int
         */
        protected $tx_gridelements_columns;

        /**
         * @var int
         */
        protected $tx_gridelements_children;

        /**
         * Content constructor.
         */
        public function __construct()
        {
            parent::__construct();

            // Typed property support for older PHP than 7.4
            settype($this->sys_language_uid, 'integer');
            settype($this->l18n_parent, 'integer');
            settype($this->colPos, 'integer');
            settype($this->CType, 'string');
            settype($this->tx_gridelements_container, 'integer');
            settype($this->tx_gridelements_columns, 'integer');
            settype($this->tx_gridelements_children, 'integer');
        }

        /**
         * TODO: PHP 7.4 provides variant return types
         *
         * @param PDOStatement $statement
         *
         * @return Content
         */
        public static function get(PDOStatement $statement): ?ModelObject
        {
            return parent::get($statement);
        }

        /**
         * @return int
         */
        public function getGridelementsChildren(): int
        {
            return $this->tx_gridelements_children;
        }

        /**
         * @param int $children
         */
        public function setGridelementsChildren(int $children): void
        {
            $this->tx_gridelements_children = $children;
        }

        /**
         * @return int
         */
        public function getGridelementsColumn(): int
        {
            return $this->tx_gridelements_columns;
        }

        /**
         * @param int $column
         */
        public function setGridelementsColumn(int $column): void
        {
            $this->tx_gridelements_columns = $column;
        }

        /**
         * @return int
         */
        public function getGridelementsContainer(): int
        {
            return $this->tx_gridelements_container;
        }

        /**
         * @param int $container
         */
        public function setGridelementsContainer(int $container): void
        {
            $this->tx_gridelements_container = $container;
        }

        /**
         * @return int
         */
        public function getWebsiteLanguage(): int
        {
            return $this->sys_language_uid;
        }

        /**
         * @return int
         */
        public function getColumnPosition(): int
        {
            return $this->colPos;
        }

        /**
         * @return string
         */
        public function getContentType(): string
        {
            return $this->CType;
        }

        /**
         * @return int
         */
        public function getLocalizationParent(): int
        {
            return $this->l18n_parent;
        }

        /**
         * @param int $parentIdentifier
         */
        public function setLocalizationParent(int $parentIdentifier): void
        {
            $this->l18n_parent = $parentIdentifier;
        }
    }
}
