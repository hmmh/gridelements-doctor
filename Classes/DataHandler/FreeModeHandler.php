<?php namespace HMMH\GridelementsDoctor\DataHandler {

    use HMMH\GridelementsDoctor\Doctor;
    use HMMH\GridelementsDoctor\Model\Content;
    use HMMH\GridelementsDoctor\Repository\Contents;
    use PDO;
    use TYPO3\CMS\Core\Database\ConnectionPool;
    use TYPO3\CMS\Core\Database\Query\QueryBuilder;
    use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
    use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
    use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
    use TYPO3\CMS\Core\DataHandling\DataHandler;
    use TYPO3\CMS\Core\Exception;
    use TYPO3\CMS\Core\Messaging\FlashMessage;
    use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
    use TYPO3\CMS\Core\Messaging\FlashMessageService;
    use TYPO3\CMS\Core\SingletonInterface;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    /**
     * Class which offers TCE main hook functions.
     */
    class FreeModeHandler implements SingletonInterface
    {
        /**
         * @var QueryBuilder
         */
        protected $queryBuilder;

        /**
         * FreeModeHandler constructor.
         */
        public function __construct()
        {
        }

        /**
         * @param string $status
         * @param string $tableName
         * @param int $identifier
         * @param array $fieldArray
         * @param DataHandler $parentObj
         *
         * @return void
         */
        public function processDatamap_afterDatabaseOperations(&$status, &$tableName, &$identifier, &$fieldArray, DataHandler $parentObj): void
        {
            $languageParent = $fieldArray[Content::COLUMN_LOCALIZATION_PARENT] ?? '';
            $isNewElement = substr($identifier, 0, 3) === 'NEW';

            if ($tableName !== Contents::getTableName() || $isNewElement || $languageParent !== 0 || $parentObj->isImporting) {
                return;
            }

            $this->initQueryBuilder();
            $contentElement = $this->getContentElementByIdentifier($identifier);

            if ($contentElement === false || !($this->isGridElement($contentElement) || $this->isGridChildElement($contentElement))) {
                return;
            }

            $movedElementIdentifiers = $this->moveContentElementAndParentGridsToFreeMode($contentElement);
            $this->addFreeModeFlashMessage($identifier, $movedElementIdentifiers);
        }

        /**
         * @param array $contentElement
         *
         * @return array
         */
        public function moveContentElementAndParentGridsToFreeMode(array $contentElement): array
        {
            $movedContentElementIdentifiers = [];
            $rootContentElement = $this->getRootGridElement($contentElement);

            if ($rootContentElement !== false) {
                $allContentElements = $this->getGridChildElementsByIdentifier($rootContentElement[Content::COLUMN_UNIQUE_IDENTIFIER]);
                $allContentElements[] = $rootContentElement;

                foreach ($allContentElements as $element) {
                    $this->moveContentElementToFreeMode($element);
                    $movedContentElementIdentifiers[] = $element[Content::COLUMN_UNIQUE_IDENTIFIER];
                }
            }

            return $movedContentElementIdentifiers;
        }

        /**
         * @param array $contentElement
         */
        public function moveContentElementToFreeMode(array $contentElement): void
        {
            $this->queryBuilder->update(Contents::getTableName())
                ->where(
                    $this->queryBuilder->expr()->eq(Content::COLUMN_UNIQUE_IDENTIFIER, $contentElement[Content::COLUMN_UNIQUE_IDENTIFIER])
                )
                ->set(Content::COLUMN_LOCALIZATION_PARENT, 0)
                ->execute();
        }

        /**
         * @param int $identifier
         *
         * @return array
         */
        public function getGridChildElementsByIdentifier($identifier): array
        {
            $children = $this->getGridChildsByParentIdentifier($identifier);

            if ($children !== false) {
                foreach ($children as $child) {
                    $children = array_merge($children, $this->getGridChildsByParentIdentifier($child[Content::COLUMN_UNIQUE_IDENTIFIER]));
                }
            }

            return $children;
        }

        /**
         * @param array $contentElement
         *
         * @return array|null
         */
        public function getRootGridElement(array $contentElement): ?array
        {
            if ($contentElement[Content::COLUMN_GRIDELEMENTS_CONTAINER] === 0) {
                return $contentElement;
            }

            $parentContentElement = $this->getContentElementByIdentifier($contentElement[Content::COLUMN_GRIDELEMENTS_CONTAINER]);

            return $this->getRootGridElement($parentContentElement);
        }

        /**
         * @param array $contentElement
         *
         * @return bool
         */
        public function isGridElement(array $contentElement): bool
        {
            return ($contentElement[Content::COLUMN_CONTENT_TYPE] ?? '') === Doctor::GRIDELEMENTS_CONTAINER;
        }

        /**
         * @param array $contentElement
         *
         * @return bool
         */
        public function isGridChildElement(array $contentElement): bool
        {
            return ($contentElement[Content::COLUMN_GRIDELEMENTS_CONTAINER] ?? 0) > 0;
        }

        /**
         * @param int $identifier
         *
         * @return array|null
         */
        public function getContentElementByIdentifier(int $identifier): ?array
        {
            $statement = $this->queryBuilder->select(...Content::getTableColumns())
                ->from(Contents::getTableName())
                ->where($this->queryBuilder->expr()->eq(Content::COLUMN_UNIQUE_IDENTIFIER, $identifier))
                ->execute();

            return $statement->fetch() ?: null;
        }

        /**
         * @param int $identifier
         *
         * @return array
         */
        public function getGridChildsByParentIdentifier(int $identifier): array
        {
            $statement = $this->queryBuilder->select(...Content::getTableColumns())
                ->from(Contents::getTableName())
                ->where($this->queryBuilder->expr()->eq(Content::COLUMN_GRIDELEMENTS_CONTAINER, $identifier))
                ->execute()
            ;

            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }

        /**
         *
         */
        public function initQueryBuilder(): void
        {
            /**@var $queryBuilder QueryBuilder */
            $this->queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Contents::getTableName());

            $this->queryBuilder->getRestrictions()
                ->removeByType(HiddenRestriction::class)
                ->removeByType(StartTimeRestriction::class)
                ->removeByType(EndTimeRestriction::class)
            ;
        }

        /**
         * @param int $identifier
         * @param array $elementIdentifiers
         *
         * @throws Exception
         */
        public function addFreeModeFlashMessage(int $identifier, array $elementIdentifiers): void
        {
            /** @var FlashMessage $flashMessage */
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                sprintf('Elements with the following uids are set to free mode, too: %s', implode(', ', $elementIdentifiers)),
                sprintf('Element uid:%d has been set to free mode', $identifier),
                FlashMessage::WARNING,
                true
            );

            /** @var $flashMessageService FlashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);

            /** @var $defaultFlashMessageQueue FlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }
    }
}
