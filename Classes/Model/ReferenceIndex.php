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
    class ReferenceIndex extends ModelObject
    {
        const COLUMN_HASH = 'hash';

        const COLUMN_TABLE_NAME = 'tablename';

        const COLUMN_RECORD_IDENTIFIER = 'recuid';

        const COLUMN_FIELD_NAME = 'field';

        const COLUMN_SORTING = 'sorting';

        const COLUMN_DELETED = 'deleted';

        const COLUMN_WORKSPACE = 'workspace';

        const COLUMN_REFERENCED_TABLE_NAME = 'ref_table';

        const COLUMN_REFERENCED_IDENTIFIER = 'ref_uid';

        /**
         * @var string
         */
        protected $hash;

        /**
         * @var string
         */
        protected $tablename;

        /**
         * @var int
         */
        protected $recuid;

        /**
         * @var string
         */
        protected $field;

        /**
         * @var int
         */
        protected $sorting;

        /**
         * @var int
         */
        protected $deleted;

        /**
         * @var int
         */
        protected $workspace;

        /**
         * @var string
         */
        protected $ref_table;

        /**
         * @var int
         */
        protected $ref_uid;

        /**
         * Page constructor.
         */
        public function __construct()
        {
            // Typed property support for older PHP than 7.4
            settype($this->hash, 'string');
            settype($this->tablename, 'string');
            settype($this->recuid, 'integer');
            settype($this->field, 'string');
            settype($this->sorting, 'integer');
            settype($this->deleted, 'integer');
            settype($this->workspace, 'integer');
            settype($this->ref_table, 'string');
            settype($this->ref_uid, 'integer');
        }

        /**
         * TODO: PHP 7.4 provides variant return types
         *
         * @param PDOStatement $statement
         *
         * @return ReferenceIndex
         */
        public static function get(PDOStatement $statement): ?ModelObject
        {
            return parent::get($statement);
        }

        /**
         * @return string
         */
        public function getHash(): string
        {
            return $this->hash;
        }

        /**
         * @return string
         */
        public function getTableName(): string
        {
            return $this->tablename;
        }

        /**
         * @return int
         */
        public function getRecordIdentifier(): int
        {
            return $this->recuid;
        }

        /**
         * @return string
         */
        public function getFieldName(): string
        {
            return $this->field;
        }

        /**
         * @return int
         */
        public function getSorting(): int
        {
            return $this->sorting;
        }

        /**
         * @return bool
         */
        public function hasDeleted(): bool
        {
            return (1 === $this->deleted);
        }

        /**
         * @return int
         */
        public function getWorkspace(): int
        {
            return $this->workspace;
        }

        /**
         * @return string
         */
        public function getReferencesTableName(): string
        {
            return $this->ref_table;
        }

        /**
         * @return int
         */
        public function getReferencedIdentifier(): int
        {
            return $this->ref_uid;
        }
    }
}
