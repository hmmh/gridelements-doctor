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

    use HMMH\GridelementsDoctor\Connector\DatabaseConnector;
    use HMMH\GridelementsDoctor\Model\Content;
    use HMMH\GridelementsDoctor\Model\IdentifiedPageModelObject;
    use PDO;
    use PDOException;
    use PDOStatement;

    /**
     * Class RepositoryObject
     *
     */
    abstract class RepositoryObject
    {
        /**
         * @var string
         */
        protected static $table;

        /**
         * @var DatabaseConnector
         */
        protected $connector;

        /**
         * @var array
         */
        protected $columns = [];

        /**
         * @var Content[]
         */
        protected $driedRepository;

        /**
         * @var int
         */
        protected $startTime;

        /**
         * RepositoryObject constructor.
         *
         * @param DatabaseConnector $connector
         */
        public function __construct(DatabaseConnector $connector)
        {
            $this->startTime = (int)microtime(true);
            $this->connector = $connector;
        }

        /**
         * @return string
         */
        public static function getTableName(): string
        {
            return static::$table;
        }

        /**
         * @param int $identifier
         *
         * @return PDOStatement
         */
        protected function getObject(int $identifier): PDOStatement
        {
            $statement = $this->getConnection()->prepare("select {$this->getTableColumns()} from {$this->getTable()} where uid = :identifier");

            $statement->bindParam(':identifier', $identifier, PDO::PARAM_INT);

            $this->ensureStatementExecution($statement);

            return $statement;
        }

        /**
         * @return PDO
         */
        protected function getConnection(): PDO
        {
            return $this->connector->getConnection();
        }

        /**
         * @return string
         */
        protected function getTableColumns(): string
        {
            if (empty($this->columns[Content::class])) {
                $this->columns[Content::class] = implode(', ', $this->getColumnsFromModelClass());
            }

            return $this->columns[Content::class];
        }

        /**
         * @return array
         */
        abstract protected function getColumnsFromModelClass(): array;

        /**
         * @return string
         */
        public function getTable(): string
        {
            return static::$table;
        }

        /**
         * @param PDOStatement $statement
         * @param IdentifiedPageModelObject $modelObject
         */
        protected function ensureStatementExecution(PDOStatement $statement, IdentifiedPageModelObject $modelObject = null): void
        {
            if ($this->queryChangedData($statement->queryString)) {
                if (!DatabaseConnector::databaseWillBeRepaired()) {
                    if (null !== $modelObject) {
                        if (!isset($this->driedRepository[$modelObject->getUid()])) {
                            $this->driedRepository[$modelObject->getUid()] = $modelObject;
                        }
                    }

                    return;
                }

                $this->connector->setDatabaseHasChanged();
            }

            try {
                $this->ensureSuccess($statement->execute());
            } catch (PDOException $e) {
                fwrite(STDERR, $e->getMessage() . "\n");
                debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                fwrite(STDERR, "\n\n");
                exit(1);
            }
        }

        /**
         * @param string $queryString
         *
         * @return bool
         */
        protected function queryChangedData(string $queryString): bool
        {
            return (
                (false !== strpos(strtolower($queryString), 'insert into '))
                || (false !== strpos(strtolower($queryString), 'delete from '))
                || (false !== strpos(strtolower($queryString), 'update '))
            );
        }

        /**
         * @param $result
         */
        protected function ensureSuccess($result): void
        {
            if (false === $result) {
                fwrite(STDERR, sprintf("%s\n\n", $this->getConnection()->errorCode()));
                debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                exit(1);
            }
        }
    }
}
