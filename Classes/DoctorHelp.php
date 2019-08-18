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
     * Trait DoctorHelp
     *
     */
    class DoctorHelp
    {
        /**
         *
         */
        public static function showHelp(): void
        {
            fwrite(STDOUT, "Usage: gridoc [command] [options]\n");
            fwrite(STDOUT, "\n");
            fwrite(STDOUT, "  STDOUT: Normal output\n");
            fwrite(STDOUT, "  STDERR: Error output\n");
            fwrite(STDOUT, "\n");

            fwrite(STDOUT, "Commands:\n");
            fwrite(STDOUT, "         examine\tRuns a health check of gridelements data structure\n");
            fwrite(STDOUT, "            heal\tRuns a healing of gridelements data structure\n");
            fwrite(STDOUT, "\n");

            fwrite(STDOUT, "Options:\n");
            fwrite(STDOUT, "          --help\tThis screen.\n");
            fwrite(STDOUT, "       --changes\tWrites changes to STDOUT\n");
            fwrite(STDOUT, "  [name].log.csv\tWrites changes to a CSV file\n");
            fwrite(STDOUT, "  [name].err.csv\tWrites errors to a CSV file (or STDERR: no CSV).\n");
            fwrite(STDOUT, "  [name].out.log\tWrites output to a file (or STDOUT).\n");
            fwrite(STDOUT, "\n");
        }
    }
}
