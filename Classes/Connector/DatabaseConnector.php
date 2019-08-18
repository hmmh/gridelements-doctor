<?php namespace HMMH\GridelementsDoctor\Connector {

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

    use HMMH\GridelementsDoctor\Exceptions\RepositoryNotFound;
    use HMMH\GridelementsDoctor\Repository\RepositoryObject;
    use PDO;
    use PDOException;

    /**
     * class DatabaseConnector
     *
     */
    class DatabaseConnector
    {
        /**
         * @var bool
         */
        protected static $repair = false;

        /**
         * @var PDO
         */
        protected $connection;

        /**
         * @var bool
         */
        protected $changed = false;

        /**
         * @var RepositoryObject[]
         */
        protected $repositories;

        /**
         *
         */
        public static function setDatabaseWillBeRepaired(): void
        {
            static::$repair = true;
        }

        /**
         * @return bool
         */
        public static function databaseWillBeRepaired(): bool
        {
            return static::$repair;
        }

        /**
         *
         */
        public function __destruct()
        {
            $this->connection = null;
        }

        /**
         * @return bool
         */
        public function databaseHasChanged(): bool
        {
            return $this->changed;
        }

        /**
         *
         */
        public function setDatabaseHasChanged(): void
        {
            $this->changed = true;
        }

        /**
         *
         */
        public function getConnection(): PDO
        {
            return $this->connection;
        }

        /**
         * @param string $key
         * @param string $className
         *
         * @return DatabaseConnector
         */
        public function addRepository(string $key, string $className): DatabaseConnector
        {
            $this->repositories[$key] = new $className($this);

            return $this;
        }

        /**
         * @param string $key
         *
         * @return RepositoryObject
         * @throws RepositoryNotFound
         */
        public function getRepository(string $key): RepositoryObject
        {
            if (!isset($this->repositories[$key])) {
                throw new RepositoryNotFound(sprintf('Cannot found repository key <%s> in DatabaseConnector.', $key));
            }

            return $this->repositories[$key];
        }

        /**
         *
         */
        public function connectToDatabase()
        {
            $dsn = sprintf(
                '%s:host=%s;port=%d;dbname=%s',
                getenv('TYPO3_DATABASE_DRIVER'),
                getenv('TYPO3_DATABASE_HOSTNAME'),
                getenv('TYPO3_DATABASE_PORT'),
                getenv('TYPO3_DATABASE_NAME')
            );

            try {
                $this->connection = new PDO(
                    $dsn,
                    getenv('TYPO3_DATABASE_USERNAME'),
                    getenv('TYPO3_DATABASE_PASSWORD')
                );

                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                fwrite(STDERR, sprintf("    Data Source Name: %s\n", $dsn));
                fwrite(STDERR, sprintf("       Error message: %s\n\n", $e->getMessage()));
                exit(1);
            }
        }
    }
}
