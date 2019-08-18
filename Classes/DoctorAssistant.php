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

    use HMMH\GridelementsDoctor\Utility\Collector;
    use HMMH\GridelementsDoctor\Utility\DepthCounter;
    use HMMH\GridelementsDoctor\Utility\Summarist;

    /**
     * Class DoctorAssistant
     *
     */
    class DoctorAssistant extends Summarist
    {
        const DEFAULT_COLLECTOR = 'Default';

        const LOCALIZED_COLLECTOR = 'Localized';

        /**
         * @var DepthCounter
         */
        protected $cascadedDepth;

        /**
         * DoctorAssistant constructor.
         *
         * @param Collector[] $collector
         */
        public function __construct(Collector ...$collector)
        {
            parent::__construct(...$collector);

            $this->cascadedDepth = new DepthCounter;
        }

        /**
         * @return DepthCounter
         */
        public function getCascadedDepth(): DepthCounter
        {
            return $this->cascadedDepth;
        }

        /**
         *
         * @throws Exceptions\CollectorNotFound
         */
        public function speak(): void
        {
            $this->echo();
            $this->echo('WARNINGS');

            /**
             * Reference index
             */
            $this->echoCategoryLine('sys_refindex');
            $this->echoStatements('%s wrong children items for container found.', DoctorAssistantToken::DIFFERENT_CONTAINER_ITEMS);

            /**
             * Website language
             */
            $this->echoCategoryLine('sys_language_uid (only manually fixable)');
            $this->echoStatements('%s wrong children languages for container found.', DoctorAssistantToken::INVALID_CHILD_LANGUAGE);

            /**
             * Column position
             */
            $this->echoCategoryLine('colPos (Container)');
            $this->echoStatements('%s invalid column position found.', DoctorAssistantToken::INVALID_COLUMN_POSITION);

            /**
             * Localization parent
             */
            $this->echoCategoryLine('l18n_parent (Container)');
            $this->echoStatements('%s invalid parent for container found.', DoctorAssistantToken::INVALID_LOCALIZATION_PARENT);

            $this->echoCategoryLine('l18n_parent (Children)');
            $this->get(static::LOCALIZED_COLLECTOR)
                ->echoStatement('%s dubious connected container.', DoctorAssistantToken::DUBIOUS_CONNECTED_CONTAINER)
                ->echoStatement('%s dubious connected children in container.', DoctorAssistantToken::DUBIOUS_CONNECTED_CHILDREN)
                ->echoStatement('%s possible connected mode for container found.', DoctorAssistantToken::POSSIBLE_CONNECTED_MODE)
                ->echoStatement('%s possible free mode for container found.', DoctorAssistantToken::POSSIBLE_FREE_MODE)
                ->echoStatement('%s invalid mixed mode of children found.', DoctorAssistantToken::INVALID_MIXED_MODE);

            /**
             * Content type
             */
            $this->echoCategoryLine('CType');
            $this->get(static::LOCALIZED_COLLECTOR)
                ->echoStatement('%s children that are not children found.', DoctorAssistantToken::CHILD_IS_NOT_GRID_CHILD);

            /**
             * Gridelements Container
             */
            $this->echoCategoryLine('tx_gridelements_container');
            $this->get(static::LOCALIZED_COLLECTOR)
                ->echoStatement('%s wrong containers found.', DoctorAssistantToken::WRONG_DEFAULT_CONTAINER)
                ->echoStatement('%s containers that are not containers found.', DoctorAssistantToken::CONTAINER_IS_NOT_GRID_CONTAINER);

            /**
             * Gridelements children
             */
            $this->echoCategoryLine('tx_gridelements_children');
            $this->echoStatements('%s wrong children count for container found.', DoctorAssistantToken::UNEQUAL_CHILDREN_COUNT);

            /**
             * Gridelements column
             */
            $this->echoCategoryLine('tx_gridelements_columns');
            $this->get(static::LOCALIZED_COLLECTOR)
                ->echoStatement('%s invalid column position', DoctorAssistantToken::INVALID_CHILDREN_COLUMN_POSITION);

            $this->echo();
            $this->echo('INFORMATION');

            /**
             * Column position
             */
            $this->echoCategoryLine('colPos');
            $this->echoStatements('%s normal container found.', DoctorAssistantToken::NORMAL_CONTAINER);
            $this->echoHorizontalLine();
            $this->echoStatements('%s unused container found.', DoctorAssistantToken::UNUSED_CONTAINER);
            $this->echoHorizontalLine();
            $this->echoStatements('%s cascaded container found.', DoctorAssistantToken::CASCADED_CONTAINER);
            $this->echo(
                '%s max. %d cascaded depth.',
                str_repeat(' ', Collector::$maxLabelLength + Collector::NUMBER_LENGTH + 3),
                $this->cascadedDepth->getMaxDepth()
            );

            /**
             * Totals
             */
            $this->echoCategoryLine('Totals');
            $this->echoStatements('%s empty container found.', DoctorAssistantToken::EMPTY_GRID_CONTAINER);
            $this->echoHorizontalLine();
            $this->echoStatements('%s container found.', DoctorAssistantToken::CONTAINER);
            $this->echoHorizontalLine();
            $this->echoStatements('%s children found.', DoctorAssistantToken::CHILDREN);

            $this->echo();
        }
    }
}
