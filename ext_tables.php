<?php

call_user_func(
    function () use ($_EXTKEY) {
        // Insert process datamap right after grid elements, if it's already loaded
        $processDatamapClass = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'];
        $posGridElements = array_search('GridElementsTeam\Gridelements\Hooks\DataHandler', $processDatamapClass);

        if ($posGridElements !== false) {
            array_splice($processDatamapClass, $posGridElements, 0, \HMMH\GridelementsDoctor\DataHandler\FreeModeHandler::class);
        } else {
            $processDatamapClass[] = \HMMH\GridelementsDoctor\DataHandler\FreeModeHandler::class;
        }
    }
);
