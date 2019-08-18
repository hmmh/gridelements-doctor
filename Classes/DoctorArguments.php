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

    use HMMH\GridelementsDoctor\Connector\DatabaseConnector;
    use HMMH\GridelementsDoctor\Exceptions\FileOperationFailure;
    use HMMH\GridelementsDoctor\Exceptions\InvalidArgument;
    use HMMH\GridelementsDoctor\Exceptions\InvalidArgumentChange;
    use HMMH\GridelementsDoctor\Exceptions\UnknownArgument;
    use HMMH\GridelementsDoctor\CommandLine\Arguments;

    /**
     * Class DoctorArguments
     *
     */
    class DoctorArguments
    {
        const OPTION_HEAL = 'heal';

        const OPTION_EXAMINE = 'examine';

        const OPTION_ERROR_CSV = '.err.csv';

        const OPTION_CHANGES_CSV = '.log.csv';

        const OPTION_OUTPUT_LOG = '.out.log';

        /**
         * @return resource
         * @throws InvalidArgument
         */
        public static function getErrorLog()
        {
            return Arguments::get(static::OPTION_ERROR_CSV);
        }

        /**
         * @return resource
         * @throws InvalidArgument
         */
        public static function getOutputLog()
        {
            return Arguments::get(static::OPTION_OUTPUT_LOG);
        }

        /**
         * @return resource
         * @throws InvalidArgument
         */
        public static function getChangesLog()
        {
            return Arguments::get(static::OPTION_CHANGES_CSV);
        }

        /**
         * @param array $arguments
         *^
         *
         * @throws InvalidArgumentChange
         * @throws FileOperationFailure
         * @throws UnknownArgument
         */
        public static function set(array $arguments): void
        {
            Arguments::set(DoctorArguments::OPTION_OUTPUT_LOG, STDOUT);
            Arguments::set(DoctorArguments::OPTION_ERROR_CSV, null);
            Arguments::set(DoctorArguments::OPTION_CHANGES_CSV, null);
            Arguments::set(DoctorArguments::OPTION_EXAMINE, false);
            Arguments::set(DoctorArguments::OPTION_HEAL, false);

            if (count($arguments)) {
                foreach ($arguments as $argument) {
                    $extension = substr($argument, -8);

                    if (Arguments::OPTION_HELP === $argument) {
                        Doctor::showHeader();
                        DoctorHelp::showHelp();
                        exit(0);
                    } elseif (static::OPTION_EXAMINE === $argument) {
                        // TODO: lets examine - show findings
                        Arguments::set(static::OPTION_EXAMINE, true);
                    } elseif (static::OPTION_HEAL === $argument) {
                        Arguments::set(static::OPTION_HEAL, true);
                        DatabaseConnector::setDatabaseWillBeRepaired();
                    } elseif ('.' === $extension[0]) {
                        switch ($extension) {
                            case static::OPTION_CHANGES_CSV:
                                {
                                    $resource = fopen($argument, 'w');

                                    if (false === $resource) {
                                        throw new FileOperationFailure(sprintf('Cannot create file <%s> for changes csv file.', $argument));
                                    }

                                    Arguments::set(static::OPTION_CHANGES_CSV, $resource);

                                    break;
                                }

                            case static::OPTION_ERROR_CSV:
                                {
                                    $resource = fopen($argument, 'w');

                                    if (false === $resource) {
                                        throw new FileOperationFailure(sprintf('Cannot create file <%s> for error csv file.', $argument));
                                    }

                                    Arguments::set(static::OPTION_ERROR_CSV, $resource);

                                    break;
                                }

                            case static::OPTION_OUTPUT_LOG:
                                {
                                    $resource = fopen($argument, 'w');

                                    if (false === $resource) {
                                        throw new FileOperationFailure(sprintf('Cannot create file <%s> for output log file.', $argument));
                                    }

                                    Arguments::set(static::OPTION_ERROR_CSV, $resource);

                                    break;
                                }

                            default:
                                {
                                    throw new UnknownArgument(sprintf('Unknown extension argument <%s> found.', $argument));
                                }
                        }
                    } else {
                        throw new UnknownArgument(sprintf('Unknown argument <%s> found.', $argument));
                    }
                }
            }

            Arguments::seal();
        }
    }
}
