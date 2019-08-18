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
     * Class IdentifiedPageModelObject
     *
     */
    abstract class IdentifiedPageModelObject extends ModelObject
    {
        const COLUMN_UNIQUE_IDENTIFIER = 'uid';

        const COLUMN_PAGE_IDENTIFIER = 'pid';

        const COLUMN_HIDDEN = 'hidden';

        /**
         * @var int
         */
        protected $uid;

        /**
         * @var int
         */
        protected $pid;

        /**
         * @var int
         */
        protected $hidden;

        /**
         * ModelObject constructor.
         */
        public function __construct()
        {
            // Typed property support for older PHP than 7.4
            settype($this->uid, 'integer');
            settype($this->pid, 'integer');
            settype($this->hidden, 'integer');
        }

        /**
         * TODO: PHP 7.4 provides variant return types
         *
         * @param PDOStatement $statement
         *
         * @return IdentifiedPageModelObject
         */
        public static function get(PDOStatement $statement): ?ModelObject
        {
            return parent::get($statement);
        }

        /**
         * @return int
         */
        public function getPid(): int
        {
            return $this->pid;
        }

        /**
         * @return int
         */
        public function getUid(): int
        {
            return $this->uid;
        }

        /**
         * @return bool
         */
        public function isHidden(): bool
        {
            return (1 === $this->hidden);
        }

        /**
         * @param bool $hidden
         */
        public function setHidden(bool $hidden): void
        {
            $this->hidden = (true === $hidden) ? 1 : 0;
        }
    }
}
