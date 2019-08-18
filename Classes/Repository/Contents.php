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

    use HMMH\GridelementsDoctor\Doctor;
    use HMMH\GridelementsDoctor\Model\Content;
    use HMMH\GridelementsDoctor\Model\IdentifiedPageModelObject;
    use PDO;
    use PDOStatement;

    /**
     * Class Contents
     *
     */
    class Contents extends RepositoryObject
    {
        /**
         * @var string
         */
        protected static $table = 'tt_content';

        /**
         * @var array
         */
        protected $affectedPages = [];

        /**
         * @return PDOStatement
         */
        public function findAllDefaultContainer(): PDOStatement
        {
            $statement = $this->getConnection()->prepare(
                <<<SQL
select {$this->getTableColumns()}
from {$this->getTable()}
where sys_language_uid = 0 and CType = :contentType and deleted = 0 and colPos >= 0
order by pid, sorting
SQL
            );

            $contentType = Doctor::GRIDELEMENTS_CONTAINER;

            $statement->bindParam(':contentType', $contentType, PDO::PARAM_STR);

            $this->ensureStatementExecution($statement);

            return $statement;
        }

        /**
         * @return PDOStatement
         */
        public function findAllLocalizedContainer(): PDOStatement
        {
            $statement = $this->getConnection()->prepare(
                <<<SQL
select {$this->getTableColumns()}
from {$this->getTable()}
where sys_language_uid > 0 and CType = :contentType and deleted = 0 and colPos >= 0
order by pid, sys_language_uid, sorting
SQL
            );

            $contentType = Doctor::GRIDELEMENTS_CONTAINER;

            $statement->bindParam(':contentType', $contentType, PDO::PARAM_STR);

            $this->ensureStatementExecution($statement);

            return $statement;
        }

        /**
         * @param int $referenceIdentifier
         * @param string $orderBy
         *
         * @return PDOStatement
         */
        public function findChildrenOfContainerByContentData(int $referenceIdentifier, string $orderBy = 'sorting'): PDOStatement
        {
            $statement = $this->getConnection()->prepare(
                "select uid from {$this->getTable()} where deleted = 0 and tx_gridelements_container = :identifier order by :orderBy"
            );

            $statement->bindParam(':identifier', $referenceIdentifier, PDO::PARAM_INT);
            $statement->bindParam(':orderBy', $orderBy, PDO::PARAM_STMT);

            $this->ensureStatementExecution($statement);

            return $statement;
        }

        /**
         * @param array $children
         */
        public function updateChildrenToNoOrigin(array $children): void
        {
            foreach ($children as $child => $originChild) {
                $childObject = $this->getElement($child);
                $this->updateContentElementToNoOrigin($childObject);
            }
        }

        /**
         * @param int $identifier
         *
         * @return Content
         */
        public function getElement(int $identifier): ?Content
        {
            if (isset($this->driedRepository[$identifier])) {
                return $this->driedRepository[$identifier];
            }

            return Content::get($this->getObject($identifier));
        }

        /**
         * @param Content $modelObject
         */
        public function updateContentElementToNoOrigin(Content $modelObject): void
        {
            $identifier = $modelObject->getUid();

            $this->addAffectedPage($modelObject, '!l18n_parent');

            $origin = 0;
            $modelObject->setLocalizationParent($origin);

            $statement = $this->getConnection()->prepare(
                "update {$this->getTable()} set l18n_parent = :localizationParent, tstamp = :timestamp where uid = :identifier"
            );

            $statement->bindParam(':identifier', $identifier, PDO::PARAM_INT);
            $statement->bindParam(':localizationParent', $origin, PDO::PARAM_INT);
            $statement->bindParam(':timestamp', $this->startTime, PDO::PARAM_INT);

            $this->ensureStatementExecution($statement, $modelObject);
        }

        /**
         * @param IdentifiedPageModelObject $modelObject
         * @param string $change
         */
        protected function addAffectedPage(IdentifiedPageModelObject $modelObject, string $change): void
        {
            $pageIdentifier = $modelObject->getPid();
            $identifier = $modelObject->getUid();

            if (!isset($this->affectedPages[$pageIdentifier][$identifier])) {
                if (!isset($this->affectedPages[$pageIdentifier])) {
                    $this->affectedPages[$pageIdentifier] = [];
                }

                $this->affectedPages[$pageIdentifier][$identifier] = [];
            }

            $this->affectedPages[$pageIdentifier][$identifier][] = $change;
        }

        /**
         * @param Content $modelObject
         * @param int $childrenCount
         */
        public function updateContainerWithNewChildrenCount(Content $modelObject, int $childrenCount): void
        {
            $identifier = $modelObject->getUid();

            $modelObject->setGridelementsChildren($childrenCount);

            $statement = $this->getConnection()->prepare(
                "update {$this->getTable()} set tx_gridelements_children = :children, tstamp = :timestamp where uid = :identifier"
            );

            $statement->bindParam(':identifier', $identifier, PDO::PARAM_INT);
            $statement->bindParam(':children', $childrenCount, PDO::PARAM_INT);
            $statement->bindParam(':timestamp', $this->startTime, PDO::PARAM_INT);

            $this->ensureStatementExecution($statement, $modelObject);
        }

        /**
         * @param Content $modelObject
         * @param int $columnPosition
         */
        public function updateContentElementToOriginColumnPosition(Content $modelObject, int $columnPosition): void
        {
            $identifier = $modelObject->getUid();

            $this->addAffectedPage($modelObject, 'tx_gridelements_columns');
            // TODO: check if child is already checked earlier - than this child must be re-validated

            $modelObject->setGridelementsColumn($columnPosition);

            $statement = $this->getConnection()->prepare(
                "update {$this->getTable()} set tx_gridelements_columns = :columnPosition, tstamp = :timestamp where uid = :identifier"
            );

            $statement->bindParam(':identifier', $identifier, PDO::PARAM_INT);
            $statement->bindParam(':columnPosition', $columnPosition, PDO::PARAM_INT);
            $statement->bindParam(':timestamp', $this->startTime, PDO::PARAM_INT);

            $this->ensureStatementExecution($statement, $modelObject);
        }

        /**
         * @param Content $modelObject
         * @param int $originIdentifier
         */
        public function updateContentElementToNewOrigin(Content $modelObject, int $originIdentifier): void
        {
            $identifier = $modelObject->getUid();

            $this->addAffectedPage($modelObject, 'l18n_parent');

            $modelObject->setLocalizationParent($originIdentifier);

            $statement = $this->getConnection()->prepare(
                "update {$this->getTable()} set l18n_parent = :originIdentifier, tstamp = :timestamp where uid = :identifier"
            );

            $statement->bindParam(':identifier', $identifier, PDO::PARAM_INT);
            $statement->bindParam(':originIdentifier', $originIdentifier, PDO::PARAM_INT);
            $statement->bindParam(':timestamp', $this->startTime, PDO::PARAM_INT);

            $this->ensureStatementExecution($statement, $modelObject);
        }

        /**
         * @return array
         */
        public function getAffectedPages(): array
        {
            return $this->affectedPages;
        }

        /**
         * @return array
         */
        protected function getColumnsFromModelClass(): array
        {
            return Content::getTableColumns();
        }
    }
}
