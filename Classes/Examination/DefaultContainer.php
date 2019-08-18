<?php namespace HMMH\GridelementsDoctor\Examination {

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

    use HMMH\GridelementsDoctor\DoctorAssistant;
    use HMMH\GridelementsDoctor\DoctorAssistantToken;
    use HMMH\GridelementsDoctor\Exceptions\CollectorNotFound;
    use HMMH\GridelementsDoctor\Model\Content;
    use HMMH\GridelementsDoctor\Repository\Contents;

    /**
     * Trait DefaultContainer
     *
     * @property DoctorAssistant $assistant
     * @property Content $childElementData
     *
     * @method Contents getContentsRepository
     */
    trait DefaultContainer
    {
        /**
         *
         */
        protected function checkDefaultCascadedContainer(): void
        {
            if (static::GRIDELEMENTS_CONTAINER === $this->childElementData->getContentType()) {
                $this->assistant->getCascadedDepth()->increment();

                $previousContainer = $this->containerElementData;
                $previousChild = $this->childElementData;

                $this->containerElementData = $this->childElementData;

                $this->checkDefaultContainer(true);

                $this->childElementData = $previousChild;
                $this->containerElementData = $previousContainer;

                $this->assistant->getCascadedDepth()->decrement();
            }
        }

        /**
         * @param bool $cascaded
         */
        protected function checkDefaultContainer($cascaded = false): void
        {
            $this->checkColumnPosition($this->containerElementData, $cascaded);
            $this->checkContainerChildren(
                function () {
                    $this->checkDefaultCascadedContainer();
                    $this->checkNoLocalizationParent();
                    $this->checkChildLanguage();
                }
            );
        }

        /**
         *
         */
        protected function checkNoLocalizationParent(): void
        {
            if (0 < $this->childElementData->getLocalizationParent()) {
                $this->getContentsRepository()->updateContentElementToNoOrigin($this->childElementData);

                $this->echoCount(
                    DoctorAssistantToken::INVALID_LOCALIZATION_PARENT,
                    $this->childElementData
                );
            }
        }

        /**
         * @throws CollectorNotFound
         */
        protected function checkAllDefaultContainers()
        {
            $this->currentCollector = $this->assistant->get(DoctorAssistant::DEFAULT_COLLECTOR);

            $containerElements = $this->getContentsRepository()->findAllDefaultContainer();

            while ($this->containerElementData = Content::get($containerElements)) {
                $this->checkDefaultContainer();
                $this->rebuildReferenceIndex();
            }
        }
    }
}
