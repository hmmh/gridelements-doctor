<?php namespace HMMH\GridelementsDoctor {

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

    use Closure;
    use HMMH\GridelementsDoctor\CommandLine\Commander;
    use HMMH\GridelementsDoctor\Connector\DatabaseConnector;
    use HMMH\GridelementsDoctor\Examination\DefaultContainer;
    use HMMH\GridelementsDoctor\Examination\LocalizedContainer;
    use HMMH\GridelementsDoctor\Exceptions\CollectorNotFound;
    use HMMH\GridelementsDoctor\Exceptions\InvalidArgument;
    use HMMH\GridelementsDoctor\Exceptions\RepositoryNotFound;
    use HMMH\GridelementsDoctor\Model\Content;
    use HMMH\GridelementsDoctor\Model\Page;
    use HMMH\GridelementsDoctor\Repository\Contents;
    use HMMH\GridelementsDoctor\Repository\Pages;
    use HMMH\GridelementsDoctor\Repository\ReferenceIndexes;
    use HMMH\GridelementsDoctor\Utility\Collector;
    use PDO;
    use PDOStatement;

    /**
     * Class Doctor
     *
     */
    class Doctor extends Commander
    {
        use Bootstrap;
        use DefaultContainer;
        use LocalizedContainer;

        const FREE_MODE = 'free';

        const CONNECTED_MODE = 'connected';

        const CONTAINER_POSITION = -1;

        const CONTAINER_UNUSED_POSITION = -2;

        const GRIDELEMENTS_CONTAINER = 'gridelements_pi1';

        const REPOSITORY_CONTENTS = 'contents';

        const REPOSITORY_PAGES = 'pages';

        const REPOSITORY_REFERENCE_INDEX = 'referenceIndex';

        /**
         * @var Content
         */
        protected $containerElementData;

        /**
         * @var Content
         */
        protected $childElementData;

        /**
         * @var DoctorAssistant
         */
        protected $assistant;

        /**
         * @var string
         */
        protected $forcedFreeLocalizationMode = false;

        /**
         * @var DatabaseConnector
         */
        protected $connector;

        /**
         * @var Collector
         */
        protected $currentCollector;

        /**
         * Doctor constructor.
         *
         * @param DoctorAssistant $assistant
         * @param DatabaseConnector $connector
         */
        protected function __construct(DoctorAssistant $assistant, DatabaseConnector $connector)
        {
            parent::__construct();

            static::showHeader();

            $this->startTime = microtime(true);
            $this->assistant = $assistant;

            $this->connector = $connector;
            $this->connector->connectToDatabase();
        }

        /**
         *
         */
        public static function showHeader()
        {
            // FIGlet
            $header = [
                '               _     __     __                          __          ____             __            ',
                '   ____ ______(_)___/ /__  / /__  ____ ___  ___  ____  / /______   / __ \____  _____/ /_____  _____',
                '  / __ `/ ___/ / __  / _ \/ / _ \/ __ `__ \/ _ \/ __ \/ __/ ___/  / / / / __ \/ ___/ __/ __ \/ ___/',
                ' / /_/ / /  / / /_/ /  __/ /  __/ / / / / /  __/ / / / /_(__  )  / /_/ / /_/ / /__/ /_/ /_/ / /    ',
                ' \__, /_/  /_/\__,_/\___/_/\___/_/ /_/ /_/\___/_/ /_/\__/____/  /_____/\____/\___/\__/\____/_/     ',
                '/____/                                                                                             ',
            ];

            fwrite(STDOUT, "\n");

            foreach ($header as $line) {
                fwrite(STDOUT, str_pad($line, Commander::LINE_WIDTH, ' ', STR_PAD_BOTH) . "\n");
            }

            fwrite(STDOUT, sprintf("\n%s\n", str_repeat('-', Commander::LINE_WIDTH)));

            echo "\n";
        }

        /**
         * @throws CollectorNotFound
         * @throws RepositoryNotFound
         * @throws InvalidArgument
         */
        public function examine(): void
        {
            $this->checkAllDefaultContainers();
            $this->checkAllLocalizedContainers();

            $this->summarizeAffectedPages();

            $this->showAssistantStatements();
            $this->showDoctorFinalStatement();
        }

        /**
         * @throws RepositoryNotFound
         * @throws InvalidArgument
         */
        protected function summarizeAffectedPages(): void
        {
            $affectedPages = $this->getContentsRepository()->getAffectedPages();

            if ((null !== DoctorArguments::getChangesLog()) && (count($affectedPages))) {
                fwrite(
                    DoctorArguments::getChangesLog(),
                    sprintf("PID\tHidden\tTitle\tHidden Count\tLanguages\tChanged Columns (Affected amount of elements)\n")
                );

                foreach ($affectedPages as $page => $elements) {
                    $pageElement = $this->getPagesRepository()->getElement($page);
                    $languages = [];
                    $columnsList = [];
                    $hiddenCount = 0;

                    foreach ($elements as $element => $columns) {
                        $contentElement = $this->getContentsRepository()->getElement($element);

                        if (!isset($languages[$contentElement->getWebsiteLanguage()])) {
                            $languages[$contentElement->getWebsiteLanguage()] = 0;
                        }

                        if ($contentElement->isHidden()) {
                            $hiddenCount++;
                        }

                        $languages[] = $contentElement->getWebsiteLanguage();

                        foreach ($columns as $column) {
                            if (!isset($columnsList[$column])) {
                                $columnsList[$column] = [];
                            }

                            $columnsList[$column][] = $element;
                        }
                    }

                    $languagesString = implode(',', array_unique($languages));
                    $columns = [];

                    foreach ($columnsList as $column => $affectedElements) {
                        $columns[] = sprintf('%s: %d', $column, count(array_unique($affectedElements)));
                    }

                    $columnsString = implode(',', $columns);

                    fwrite(
                        DoctorArguments::getChangesLog(),
                        sprintf(
                            "%s\t%s\t%s\t%d\t%s\t%s\n",
                            $page,
                            (null !== $pageElement) ? $pageElement->isHidden() : 'Page not found',
                            (null !== $pageElement) ? $this->getPageTitle($pageElement) : 'and can be deleted',
                            $hiddenCount,
                            $languagesString,
                            $columnsString
                        )
                    );
                }
            }
        }

        /**
         * @return Contents
         * @throws RepositoryNotFound
         */
        protected function getContentsRepository(): Contents
        {
            return $this->connector->getRepository(static::REPOSITORY_CONTENTS);
        }

        /**
         * @return Pages
         * @throws RepositoryNotFound
         */
        protected function getPagesRepository(): Pages
        {
            return $this->connector->getRepository(static::REPOSITORY_PAGES);
        }

        /**
         * @param Page $pageElement
         * @param array $rootLine
         *
         * @return string
         * @throws RepositoryNotFound
         */
        protected function getPageTitle(Page $pageElement, array &$rootLine = []): string
        {
            if (0 !== $pageElement->getPid()) {
                array_unshift($rootLine, $pageElement->getTitle());
                $pageElement = $this->getPagesRepository()->getElement($pageElement->getPid());

                return $this->getPageTitle($pageElement, $rootLine);
            }

            return implode('/', $rootLine);
        }

        /**
         * @throws CollectorNotFound
         */
        protected function showAssistantStatements(): void
        {
            $this->assistant->speak();
        }

        /**
         * @throws RepositoryNotFound
         */
        protected function showDoctorFinalStatement(): void
        {
            if ((0 < count($this->getContentsRepository()->getAffectedPages())) && (!DatabaseConnector::databaseWillBeRepaired())) {
                fwrite(STDOUT, "    The doctor says that you are not in a good condition!\n\n");
                fwrite(STDOUT, sprintf("    Please execute the command with argument '%s'.\n\n", DoctorArguments::OPTION_HEAL));
            } elseif ($this->connector->databaseHasChanged()) {
                fwrite(STDOUT, "    The doctor has alleviate your pain.\n\n");
            } else {
                fwrite(STDOUT, "    The doctor says that you are in good health.\n\n");
            }
        }

        /**
         * @param Closure $childHandler
         *
         * @throws RepositoryNotFound
         */
        protected function checkContainerChildren(Closure $childHandler): void
        {
            $this->currentCollector[DoctorAssistantToken::CONTAINER]++;

            $containerChildrenResultContent = $this->getContentsRepository()
                ->findChildrenOfContainerByContentData($this->containerElementData->getUid());

            $this->checkSynchronizationBetweenContentDataAndReferenceIndex($containerChildrenResultContent);

            // TODO: change iteration strategy over this results, not execute it again

            $containerChildrenResultContent = $this->getContentsRepository()
                ->findChildrenOfContainerByContentData($this->containerElementData->getUid());

            if (0 === $containerChildrenResultContent->rowCount()) {
                $this->currentCollector[DoctorAssistantToken::EMPTY_GRID_CONTAINER]++;

                return;
            }

            while ($childData = Content::get($containerChildrenResultContent)) {
                $this->childElementData = $this->getContentsRepository()->getElement($childData->getUid());

                $this->checkColumnPosition($this->childElementData, true);
                $this->checkChildLanguage();

                $childHandler();

                $this->currentCollector[DoctorAssistantToken::CHILDREN]++;
            }
        }

        /**
         * @param PDOStatement $containerChildrenResultContent
         *
         * @throws RepositoryNotFound
         */
        protected function checkSynchronizationBetweenContentDataAndReferenceIndex(PDOStatement $containerChildrenResultContent): void
        {
            $childrenFromContent = $containerChildrenResultContent->fetchAll(PDO::FETCH_COLUMN, 0);
            sort($childrenFromContent);

            $containerChildrenResultContent = $this->getReferenceIndexRepository()
                ->findChildrenOfContainer($this->containerElementData->getUid());

            // TODO: compare "sys_refindex" items of "tx_gridelements_container" and "tx_gridelements_children"?

            $childrenFromIndex = $containerChildrenResultContent->fetchAll(PDO::FETCH_COLUMN, 0);
            sort($childrenFromIndex);

            if ($childrenFromContent !== $childrenFromIndex) {
                $this->echoCount(
                    DoctorAssistantToken::DIFFERENT_CONTAINER_ITEMS,
                    $this->containerElementData,
                    'IRRE --> %d (tt_content) <=> (sys_refindex) %d',
                    implode(', ', $childrenFromContent),
                    implode(', ', $childrenFromIndex)
                );
            }

            if ($this->containerElementData->getGridelementsChildren() !== $containerChildrenResultContent->rowCount()) {
                $this->echoCount(
                    DoctorAssistantToken::UNEQUAL_CHILDREN_COUNT,
                    $this->containerElementData,
                    'tx_gridelements_children --> (tt_content) <=> (sys_refindex) %d',
                    $containerChildrenResultContent->rowCount()
                );

                $this->getContentsRepository()
                    ->updateContainerWithNewChildrenCount($this->containerElementData, $containerChildrenResultContent->rowCount());
            }
        }

        /**
         * @return ReferenceIndexes
         * @throws RepositoryNotFound
         */
        protected function getReferenceIndexRepository(): ReferenceIndexes
        {
            return $this->connector->getRepository(static::REPOSITORY_REFERENCE_INDEX);
        }

        /**
         * @param string $token
         * @param Content $contentObject
         * @param string $message
         * @param array $sprintfArguments
         */
        protected function echoCount(string $token, Content $contentObject, string $message = '', ...$sprintfArguments)
        {
            $sprintfItems = array_fill(0, count(Content::getTableColumns()), '%s');

            $this->currentCollector->echoCount(
                $token,
                implode("\t", $sprintfItems) . (!empty($message) ? "\t" . $message : ''),
                ...array_values($contentObject->toArray()),
                ...$sprintfArguments
            );
        }

        /**
         * @param Content $contentObject
         * @param bool $cascaded
         */
        protected function checkColumnPosition(Content $contentObject, bool $cascaded = false): void
        {
            switch ($contentObject->getColumnPosition()) {
                case static::CONTAINER_POSITION:
                    {
                        if (!$cascaded) {
                            $this->echoCount(
                                DoctorAssistantToken::INVALID_COLUMN_POSITION,
                                $contentObject,
                                'cascaded container, used'
                            );
                        }

                        if ($cascaded) {
                            $this->currentCollector[DoctorAssistantToken::CASCADED_CONTAINER]++;
                        } else {
                            $this->currentCollector[DoctorAssistantToken::NORMAL_CONTAINER]++;
                        }

                        break;
                    }

                case static::CONTAINER_UNUSED_POSITION:
                    {
                        if (!$cascaded) {
                            $this->echoCount(
                                DoctorAssistantToken::INVALID_COLUMN_POSITION,
                                $contentObject,
                                'cascaded container, unused'
                            );
                        }

                        $this->currentCollector[DoctorAssistantToken::UNUSED_CONTAINER]++;

                        break;
                    }

                default:
                    {
                        if ($cascaded) {
                            $this->echoCount(
                                DoctorAssistantToken::INVALID_COLUMN_POSITION,
                                $contentObject,
                                'normal container, backend layout'
                            );
                        }

                        $this->currentCollector[DoctorAssistantToken::NORMAL_CONTAINER]++;
                    }
            }
        }

        /**
         *
         */
        protected function checkChildLanguage(): void
        {
            if ($this->childElementData->getWebsiteLanguage() !== $this->containerElementData->getWebsiteLanguage()) {
                $this->echoCount(
                    DoctorAssistantToken::INVALID_CHILD_LANGUAGE,
                    $this->childElementData,
                    'sys_language_uid --> (Child) <=> (Container) %d !!!DECISION!!!',
                    $this->containerElementData->getWebsiteLanguage()
                );
            }
        }

        /**
         * @throws RepositoryNotFound
         */
        protected function rebuildReferenceIndex(): void
        {
            $identifier = $this->containerElementData->getUid();

            $this->getReferenceIndexRepository()->deleteContainerPointer($identifier);
            $this->getReferenceIndexRepository()->deleteChildrenPointer($identifier);

            $containerChildrenResultContent = $this->getContentsRepository()->findChildrenOfContainerByContentData($identifier, 'sorting');

            $sorting = 0;

            while ($containerChild = Content::get($containerChildrenResultContent)) {
                $this->getReferenceIndexRepository()
                    ->insertContainerPointsToChild($identifier, $containerChild->getUid(), $sorting);

                $this->getReferenceIndexRepository()
                    ->insertChildPointsToContainer($containerChild->getUid(), $identifier);

                $sorting++;
            }
        }
    }
}
