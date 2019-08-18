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
    use HMMH\GridelementsDoctor\Exceptions\ForcedFreeContainer;
    use HMMH\GridelementsDoctor\Model\Content;
    use HMMH\GridelementsDoctor\Repository\Contents;

    /**
     * Trait LocalizedContainer
     *
     * @property DoctorAssistant $assistant
     * @property Content $childElementData
     * @property Content $containerElementData
     *
     * @method Contents getContentsRepository
     */
    trait LocalizedContainer
    {
        /**
         * @throws ForcedFreeContainer
         */
        protected function checkLocalizedCascadedContainer(): void
        {
            if (static::GRIDELEMENTS_CONTAINER === $this->childElementData->getContentType()) {
                $this->assistant->getCascadedDepth()->increment();

                $previousContainer = $this->containerElementData;
                $previousChild = $this->childElementData;

                $this->containerElementData = $this->childElementData;

                $this->checkLocalizedContainer(false);

                $this->childElementData = $previousChild;
                $this->containerElementData = $previousContainer;

                $this->assistant->getCascadedDepth()->decrement();
            }
        }

        /**
         * @param bool $rooted
         *
         * @throws ForcedFreeContainer
         */
        protected function checkLocalizedContainer(bool $rooted): void
        {
            $this->checkColumnPosition($this->containerElementData, !$rooted);

            /** @var int[] $freeChildren */
            $freeChildren = [];

            /** @var int[] $connectedChildren */
            $connectedChildren = [];

            $this->checkContainerChildren(
                function () use (&$freeChildren, &$connectedChildren) {
                    $this->checkLocalizedCascadedContainer();
                    $this->collectChildrenTypes($freeChildren, $connectedChildren);
                }
            );

            $this->checkMatchOfAmountOfChildrenAndTheirOrigins($freeChildren, $connectedChildren);
            $this->checkLocalizationMode($freeChildren, $connectedChildren);
            $this->checkGridColumnPositionForChildrenWithOrigin($connectedChildren);
        }

        /**
         * @param array $freeChildren
         * @param array $connectedChildren
         *
         * @throws ForcedFreeContainer
         */
        protected function collectChildrenTypes(array &$freeChildren, array &$connectedChildren)
        {
            if (0 < $this->childElementData->getLocalizationParent()) {
                $originChild = $this->getContentsRepository()->getElement($this->childElementData->getLocalizationParent());

                if (!$this->checkOriginElementIsGridChild($originChild)) {
                    throw new ForcedFreeContainer();
                }

                $connectedChildren[$this->childElementData->getUid()] = $originChild->getUid();
            } else {
                $freeChildren[$this->childElementData->getUid()] = 0;
            }
        }

        /**
         * @param Content|null $childElement
         *
         * @return bool
         */
        protected function checkOriginElementIsGridChild(?Content $childElement): bool
        {
            if (null === $childElement) {
                $this->getContentsRepository()->updateContentElementToNoOrigin($this->childElementData);
            } elseif (0 === $childElement->getGridelementsContainer()) {
                $this->echoCount(
                    DoctorAssistantToken::CHILD_IS_NOT_GRID_CHILD,
                    $this->childElementData,
                    sprintf('%s: %%s, %s -> (child) <=> (origin) %%s ', Content::COLUMN_GRIDELEMENTS_CONTAINER, Content::COLUMN_CONTENT_TYPE),
                    $childElement->getGridelementsContainer(),
                    $childElement->getContentType()
                );

                $this->getContentsRepository()->updateContentElementToNoOrigin($this->childElementData);
                $childElement = null;
            }

            return (null !== $childElement);
        }

        /**
         * @param array $freeChildren
         * @param array $connectedChildren
         *
         * @throws ForcedFreeContainer
         */
        protected function checkMatchOfAmountOfChildrenAndTheirOrigins(array &$freeChildren, array &$connectedChildren): void
        {
            $originChildren = array_unique(array_values($connectedChildren));

            if (count($connectedChildren) !== count($originChildren)) {
                $this->echoCount(
                    DoctorAssistantToken::DUBIOUS_CONNECTED_CHILDREN,
                    $this->childElementData,
                    'l18n_parent --> %s (children) <=> (origin children) %s',
                    implode(', ', array_keys($connectedChildren)),
                    implode(', ', $originChildren)
                );

                $this->getContentsRepository()->updateChildrenToNoOrigin($connectedChildren);

                $freeChildren = $freeChildren + $connectedChildren;
                $connectedChildren = [];

                if (static::CONNECTED_MODE === $this->getLocationModeOfContainer()) {
                    $this->getContentsRepository()->updateContentElementToNoOrigin($this->containerElementData);

                    throw new ForcedFreeContainer();
                }
            }
        }

        /**
         * @return string
         */
        protected function getLocationModeOfContainer(): string
        {
            return (0 < $this->containerElementData->getLocalizationParent()) ? static::CONNECTED_MODE : static::FREE_MODE;
        }

        /**
         * @param array $freeChildren
         * @param array $connectedChildren
         *
         * @throws ForcedFreeContainer
         */
        protected function checkLocalizationMode(array $freeChildren, array $connectedChildren): void
        {
            if ($this->forcedFreeLocalizationMode) {
                $allChildren = array_flip(array_unique(array_merge(array_keys($freeChildren), array_keys($connectedChildren))));

                $this->getContentsRepository()->updateChildrenToNoOrigin($allChildren);
                $this->getContentsRepository()->updateContentElementToNoOrigin($this->containerElementData);

                return;
            }

            // if children are all free for connected container
            if ((!empty($freeChildren)) && (empty($connectedChildren))) {
                $this->checkLocalizationModeOfConnectedContainer($freeChildren);
            }

            // if children are all connected for free container
            if ((empty($freeChildren)) && (!empty($connectedChildren))) {
                $this->checkValidContainerForChildren($connectedChildren);
                $this->checkLocalizationModeOfFreeContainer($connectedChildren);
            }

            // if children are mixed for any container
            if ((!empty($freeChildren)) && (!empty($connectedChildren))) {
                $this->checkLocalizationModeOfAnyContainer($freeChildren, $connectedChildren);
            }
        }

        /**
         * @param array $freeChildren
         *
         * @throws ForcedFreeContainer
         */
        protected function checkLocalizationModeOfConnectedContainer(array $freeChildren): void
        {
            if (static::CONNECTED_MODE === $this->getLocationModeOfContainer()) {
                $this->echoCount(
                    DoctorAssistantToken::POSSIBLE_FREE_MODE,
                    $this->childElementData,
                    'children modes --> %d (free)',
                    count($freeChildren)
                );

                // TODO: can we find out where the parent child (in default language) in the connected container is?
                // TODO: only if the number of connected children per column matches the number of children in the standard container per column.
                // TODO: if this impossible, so we must change the connected container to free container

                $this->getContentsRepository()->updateContentElementToNoOrigin($this->containerElementData);

                throw new ForcedFreeContainer();
            }
        }

        /**
         * @param array $connectedChildren
         *
         * @throws ForcedFreeContainer
         */
        protected function checkValidContainerForChildren(array $connectedChildren): void
        {
            if (static::CONNECTED_MODE === $this->getLocationModeOfContainer()) {
                $connectedContainers = [];

                foreach ($connectedChildren as $originChild) {
                    $originChild = $this->getContentsRepository()->getElement($originChild);

                    if (0 < $originChild->getGridelementsContainer()) {
                        $connectedContainers[] = $originChild->getGridelementsContainer();
                    }
                }

                $differentContainers = array_unique($connectedContainers);

                if (1 === count($differentContainers)) {
                    $originContainer = reset($differentContainers);
                    $originContainerData = $this->getContentsRepository()->getElement($originContainer);

                    if ($this->containerElementData->getLocalizationParent() !== $originContainerData->getUid()) {
                        $this->echoCount(
                            DoctorAssistantToken::WRONG_DEFAULT_CONTAINER,
                            $this->childElementData,
                            'children modes --> %d (connected)',
                            count($connectedChildren)
                        );

                        // TODO: or can we also changed the new origin of that container?

                        $this->getContentsRepository()->updateChildrenToNoOrigin($connectedChildren);
                        $this->getContentsRepository()->updateContentElementToNoOrigin($this->containerElementData);

                        throw new ForcedFreeContainer();
                    } elseif (!$this->checkOriginElementIsGridContainer($originContainerData)) {
                        throw new ForcedFreeContainer();
                    }
                } else {
                    $this->echoCount(
                        DoctorAssistantToken::DUBIOUS_CONNECTED_CONTAINER,
                        $this->childElementData,
                        'container --> %s (children) <=> (origin children) %s',
                        implode(', ', $connectedContainers),
                        implode(', ', $differentContainers)
                    );

                    $this->getContentsRepository()->updateChildrenToNoOrigin($connectedChildren);
                    $this->getContentsRepository()->updateContentElementToNoOrigin($this->containerElementData);

                    throw new ForcedFreeContainer();
                }
            }
        }

        /**
         * @param Content|null $containerElement
         *
         * @return bool
         */
        protected function checkOriginElementIsGridContainer(?Content $containerElement): bool
        {
            if (null === $containerElement) {
                $this->getContentsRepository()->updateContentElementToNoOrigin($this->containerElementData);
            } elseif (Content::COLUMN_CONTENT_TYPE !== $containerElement->getContentType()) {
                $this->echoCount(
                    DoctorAssistantToken::CONTAINER_IS_NOT_GRID_CONTAINER,
                    $containerElement
                );

                $containerElement = null;
                $this->getContentsRepository()->updateContentElementToNoOrigin($this->containerElementData);
            }

            return (null !== $containerElement);
        }

        /**
         * @param array $connectedChildren
         */
        protected function checkLocalizationModeOfFreeContainer(array $connectedChildren): void
        {
            if (static::FREE_MODE === $this->getLocationModeOfContainer()) {
                $connectedContainers = [];

                foreach ($connectedChildren as $originChild) {
                    $originChild = $this->getContentsRepository()->getElement($originChild);
                    $connectedContainers[] = $originChild->getGridelementsContainer();
                }

                $differentContainers = array_unique($connectedContainers);

                // We need to find out if all children have only one container in common,
                // so that we can reconnect the origin container for the current container
                if (1 === count($differentContainers)) {
                    $this->echoCount(
                        DoctorAssistantToken::POSSIBLE_CONNECTED_MODE,
                        $this->childElementData,
                        'children modes --> %d (connected)',
                        count($connectedChildren)
                    );

                    $originContainer = reset($differentContainers);
                    $this->getContentsRepository()->updateContentElementToNewOrigin($this->containerElementData, $originContainer);
                } else {
                    $this->echoCount(
                        DoctorAssistantToken::DUBIOUS_CONNECTED_CONTAINER,
                        $this->childElementData,
                        'container --> %s (child) <=> (origin child) %s',
                        implode(', ', array_keys($connectedChildren)),
                        implode(', ', array_values($connectedChildren))
                    );

                    // TODO: Can we find out where the children belong in the connected container (default language)?
                    // TODO: only possible if the number of connected children per column matches the number of children in the standard container per column.
                    // TODO: if this impossible, so we must change the connected container to free container

                    $this->getContentsRepository()->updateChildrenToNoOrigin($connectedChildren);
                }
            }
        }

        /**
         * @param array $freeChildren
         * @param array $connectedChildren
         *
         * @throws ForcedFreeContainer
         */
        protected function checkLocalizationModeOfAnyContainer(array $freeChildren, array $connectedChildren): void
        {
            $this->echoCount(
                DoctorAssistantToken::INVALID_MIXED_MODE,
                $this->childElementData,
                'children modes --> %d (free) <=> (connected) %d',
                count($freeChildren),
                count($connectedChildren)
            );

            // TODO: Can we find out where the children belong in the connected container (default language)?
            // TODO: only possible if the number of connected children per column matches the number of children in the default container per column.
            // TODO: if this impossible, so we must change the connected container to free container

            $this->getContentsRepository()->updateChildrenToNoOrigin($connectedChildren);

            if (static::CONNECTED_MODE === $this->getLocationModeOfContainer()) {
                $this->getContentsRepository()->updateContentElementToNoOrigin($this->containerElementData);

                throw new ForcedFreeContainer();
            }
        }

        /**
         * @param array $connectedChildren
         */
        protected function checkGridColumnPositionForChildrenWithOrigin(array $connectedChildren): void
        {
            if (static::CONNECTED_MODE === $this->getLocationModeOfContainer()) {
                foreach ($connectedChildren as $child => $container) {
                    $this->childElementData = $this->getContentsRepository()->getElement($child);
                    $this->checkGridColumnPositionForChildWithOrigin();
                }
            }
        }

        /**
         *
         */
        protected function checkGridColumnPositionForChildWithOrigin(): void
        {
            if (0 < $this->childElementData->getLocalizationParent()) {
                $originChildElementData = $this->getContentsRepository()->getElement($this->childElementData->getLocalizationParent());

                if ($this->childElementData->getGridelementsColumn() !== $originChildElementData->getGridelementsColumn()) {
                    $this->echoCount(
                        DoctorAssistantToken::INVALID_CHILDREN_COLUMN_POSITION,
                        $this->childElementData,
                        'tx_gridelements_columns --> %d (sys_language_uid: %d)',
                        $originChildElementData->getGridelementsColumn(),
                        $originChildElementData->getWebsiteLanguage()
                    );

                    $this->getContentsRepository()->updateContentElementToOriginColumnPosition(
                        $this->childElementData,
                        $originChildElementData->getGridelementsColumn()
                    );
                }
            }
        }

        /**
         * @throws CollectorNotFound
         */
        protected function checkAllLocalizedContainers()
        {
            $this->currentCollector = $this->assistant->get(DoctorAssistant::LOCALIZED_COLLECTOR);

            $containerElements = $this->getContentsRepository()->findAllLocalizedContainer();

            while ($containerElement = Content::get($containerElements)) {
                do {
                    $this->containerElementData = $containerElement;

                    try {
                        $this->checkLocalizedContainer(true);
                        $this->forcedFreeLocalizationMode = false;
                    } catch (ForcedFreeContainer $e) {
                        $this->assistant->getCascadedDepth()->resetCurrent();
                        $this->forcedFreeLocalizationMode = true;
                    }
                } while ($this->forcedFreeLocalizationMode);

                $this->rebuildReferenceIndex();
            }
        }
    }
}
