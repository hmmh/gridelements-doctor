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

    use HMMH\GridelementsDoctor\Model\Content;
    use PDO;
    use PDOStatement;

    /**
     * Class ReferenceIndex
     *
     */
    class ReferenceIndexes extends RepositoryObject
    {
        /**
         * @var string
         */
        protected static $table = 'sys_refindex';

        /**
         * @param int $containerIdentifier
         */
        public function deleteChildrenPointer(int $containerIdentifier): void
        {
            $statement = $this->getConnection()->prepare(
                <<<SQL
delete from {$this->getTable()}
where tablename = 'tt_content' and field = :column and ref_table = 'tt_content' and recuid = :identifier
SQL
            );

            $column = Content::COLUMN_GRIDELEMENTS_CHILDREN;

            $statement->bindParam(':identifier', $containerIdentifier, PDO::PARAM_INT);
            $statement->bindParam(':column', $column, PDO::PARAM_STR);

            $this->ensureStatementExecution($statement);
        }

        /**
         * @param int $containerIdentifier
         */
        public function deleteContainerPointer(int $containerIdentifier): void
        {
            $statement = $this->getConnection()->prepare(
                <<<SQL
delete from {$this->getTable()}
where tablename = 'tt_content' and field = :column and ref_table = 'tt_content' and ref_uid = :identifier
SQL
            );

            $column = Content::COLUMN_GRIDELEMENTS_CONTAINER;

            $statement->bindParam(':identifier', $containerIdentifier, PDO::PARAM_INT);
            $statement->bindParam(':column', $column, PDO::PARAM_STR);

            $this->ensureStatementExecution($statement);
        }

        /**
         * @param int $containerIdentifier
         * @param string $childIdentifier
         * @param int $sorting
         */
        public function insertContainerPointsToChild(int $containerIdentifier, string $childIdentifier, int $sorting): void
        {
            $statement = $this->getConnection()->prepare(
                <<<SQL
insert into {$this->getTable()} ({$this->getTableColumns()}) 
values (:hash, 'tt_content', :recuid, :column, :sorting, 0, 0, 'tt_content', :ref_uid)
SQL
            );

            $hash = $this->getRelationHash('tt_content', $containerIdentifier, Content::COLUMN_GRIDELEMENTS_CHILDREN, $sorting, $childIdentifier);

            $column = Content::COLUMN_GRIDELEMENTS_CHILDREN;

            $statement->bindParam(':hash', $hash, PDO::PARAM_STR);
            $statement->bindParam(':recuid', $containerIdentifier, PDO::PARAM_INT);
            $statement->bindParam(':ref_uid', $childIdentifier, PDO::PARAM_INT);
            $statement->bindParam(':sorting', $sorting, PDO::PARAM_INT);
            $statement->bindParam(':column', $column, PDO::PARAM_STR);

            $this->ensureStatementExecution($statement);
        }

        /**
         * @param string $tableName
         * @param int $recordIdentifier
         * @param string $field
         * @param string $sorting
         * @param string $referenceIdentifier
         *
         * @return string
         */
        protected function getRelationHash(
            string $tableName,
            int $recordIdentifier,
            string $field,
            string $sorting,
            string $referenceIdentifier
        ): string {
            $relation = [
                'tablename' => $tableName,
                'recuid' => $recordIdentifier,
                'field' => $field,
                'flexpointer' => '',
                'softref_key' => '',
                'softref_id' => '',
                'sorting' => $sorting,
                'deleted' => '0',
                'workspace' => '0',
                'ref_table' => $tableName,
                'ref_uid' => $referenceIdentifier,
                'ref_string' => mb_substr('', 0, 1024),
            ];

            return md5(implode('///', $relation) . '///' . 1);
        }

        /**
         * @param string $childIdentifier
         * @param int $containerIdentifier
         */
        public function insertChildPointsToContainer(string $childIdentifier, int $containerIdentifier): void
        {
            $statement = $this->getConnection()->prepare(
                <<<SQL
insert into {$this->getTable()} ({$this->getTableColumns()}) 
values (:hash, 'tt_content', :recuid, 'tx_gridelements_container', 0, 0, 0, 'tt_content', :ref_uid)
SQL
            );

            $hash = $this->getRelationHash(
                Contents::getTableName(),
                $childIdentifier,
                Content::COLUMN_GRIDELEMENTS_CONTAINER,
                0,
                $containerIdentifier
            );

            $statement->bindParam(':hash', $hash, PDO::PARAM_STR);
            $statement->bindParam(':ref_uid', $containerIdentifier, PDO::PARAM_INT);
            $statement->bindParam(':recuid', $childIdentifier, PDO::PARAM_INT);

            $this->ensureStatementExecution($statement);
        }

        /**
         * @param int $containerIdentifier
         *
         * @return PDOStatement
         */
        public function findChildrenOfContainer(int $containerIdentifier): PDOStatement
        {
            $statement = $this->getConnection()->prepare(
                <<<SQL
select ref_uid
from {$this->getTable()}
where deleted = 0 and tablename = 'tt_content' and field = 'tx_gridelements_children' and ref_table = 'tt_content' and recuid = :identifier
SQL
            );

            $statement->bindParam(':identifier', $containerIdentifier, PDO::PARAM_INT);

            $this->ensureStatementExecution($statement);

            return $statement;
        }

        /**
         * @return array
         */
        protected function getColumnsFromModelClass(): array
        {
            return [];
        }
    }
}
