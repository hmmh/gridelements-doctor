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

    use Composer\Script\Event;
    use Exception;
    use HMMH\GridelementsDoctor\CommandLine\Commander;
    use HMMH\GridelementsDoctor\Connector\DatabaseConnector;
    use HMMH\GridelementsDoctor\Exceptions\InvalidArgument;
    use HMMH\GridelementsDoctor\Exceptions\UnknownArgument;
    use HMMH\GridelementsDoctor\Repository\Contents;
    use HMMH\GridelementsDoctor\Repository\Pages;
    use HMMH\GridelementsDoctor\Repository\ReferenceIndexes;
    use HMMH\GridelementsDoctor\Utility\Collector;

    /**
     * Trait Bootstrap
     *
     */
    trait Bootstrap
    {
        /**
         * @param array $arguments
         *
         * @return int
         */
        public static function runInstance(array $arguments): int
        {
            $exitCode = 0;

            try {
                DoctorArguments::set($arguments);

                $outputLog = DoctorArguments::getOutputLog();
                $errorLog = DoctorArguments::getErrorLog();

                $defaultCollector = new Collector(DoctorAssistant::DEFAULT_COLLECTOR, $outputLog, $errorLog);
                $localizedCollector = new Collector(DoctorAssistant::LOCALIZED_COLLECTOR, $outputLog, $errorLog);

                $assistant = new DoctorAssistant($defaultCollector, $localizedCollector);

                $connector = new DatabaseConnector();
                $connector
                    ->addRepository(Doctor::REPOSITORY_CONTENTS, Contents::class)
                    ->addRepository(Doctor::REPOSITORY_PAGES, Pages::class)
                    ->addRepository(Doctor::REPOSITORY_REFERENCE_INDEX, ReferenceIndexes::class);

                /** @var Doctor $doctor */
                $doctor = new static($assistant, $connector);
                $doctor->examine();
            } catch (InvalidArgument|UnknownArgument $e) {
                Doctor::showHeader();

                echo $e->getMessage() . "\n\n";
                fwrite(STDOUT, sprintf("%s\n", str_repeat('-', Commander::LINE_WIDTH)));

                DoctorHelp::showHelp();

                $exitCode = 1;
            } catch (Exception $e) {
                fwrite(STDOUT, sprintf("%s\n", str_repeat('-', Commander::LINE_WIDTH)));

                echo $e->getMessage() . "\n";
                echo $e->getTraceAsString() . "\n";

                $exitCode = 1;
            }

            return $exitCode;
        }

        /**
         * @param Event $event
         *
         * @return int
         */
        public static function visit(Event $event): int
        {
            return self::runInstance($event->getArguments());
        }
    }
}
