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

    /**
     * Interface CollectorToken
     *
     */
    interface DoctorAssistantToken
    {
        const CONTAINER = 'container';

        const CHILDREN = 'children';

        const EMPTY_GRID_CONTAINER = 'emptyGridContainer';

        const UNEQUAL_CHILDREN_COUNT = 'unequalChildrenCount';

        const UNUSED_CONTAINER = 'unusedContainer';

        const CASCADED_CONTAINER = 'cascadedContainer';

        const NORMAL_CONTAINER = 'normalContainer';

        const WRONG_DEFAULT_CONTAINER = 'wrongDefaultContainer';

        const DIFFERENT_CONTAINER_ITEMS = 'differentContainerItems';

        const INVALID_LOCALIZATION_PARENT = 'invalidLocalizationParent';

        const POSSIBLE_FREE_MODE = 'possibleFreeMode';

        const POSSIBLE_CONNECTED_MODE = 'possibleConnectedMode';

        const INVALID_MIXED_MODE = 'invalidMixedMode';

        const INVALID_CHILD_LANGUAGE = 'invalidChildLanguage';

        const INVALID_CHILDREN_COLUMN_POSITION = 'invalidChildrenColumnPosition';

        const DUBIOUS_CONNECTED_CONTAINER = 'dubiousConnectedContainer';

        const DUBIOUS_CONNECTED_CHILDREN = 'dubiousConnectedChildren';

        const INVALID_COLUMN_POSITION = 'invalidColumnPosition';

        const CONTAINER_IS_NOT_GRID_CONTAINER = 'containerIsNotGridContainer';

        const CHILD_IS_NOT_GRID_CHILD = 'childIsNotGridChild';
    }
}
