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
     * Class ModelObject
     *
     */
    abstract class ModelObject
    {
        /**
         * @param PDOStatement $statement
         *
         * @return ModelObject
         */
        public static function get(PDOStatement $statement): ?ModelObject
        {
            $model = $statement->fetchObject(static::class);

            if (false === $model) {
                $model = null;
            }

            return $model;
        }

        /**
         * @return array
         */
        public function toArray()
        {
            return get_object_vars($this);
        }

        /**
         * @return array
         */
        public function getColumns(): array
        {
            return static::getTableColumns();
        }

        /**
         * @return array
         */
        public static function getTableColumns(): array
        {
            return array_keys(get_class_vars(static::class));
        }
    }
}
