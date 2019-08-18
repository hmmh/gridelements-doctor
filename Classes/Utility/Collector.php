<?php namespace HMMH\GridelementsDoctor\Utility {

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

    use ArrayAccess;
    use HMMH\GridelementsDoctor\Exceptions\FileOperationFailure;
    use HMMH\GridelementsDoctor\Exceptions\InvalidResource;
    use HMMH\GridelementsDoctor\Model\Content;

    /**
     * Class Collector
     *
     */
    class Collector implements ArrayAccess
    {
        const NUMBER_LENGTH = 7;

        /**
         * @var int
         */
        public static $maxLabelLength = 0;

        /**
         * @var int[]
         */
        protected $items = [];

        /**
         * @var string
         */
        protected $name;

        /**
         * @var resource
         */
        protected $outputResource;

        /**
         * @var resource
         */
        protected $errorResource;

        /**
         * Collector constructor.
         *
         * @param string $name
         * @param bool|null|resource $outputResource
         * @param bool|null|resource $errorResource
         *
         * @throws FileOperationFailure
         * @throws InvalidResource
         */
        public function __construct(string $name, $outputResource = STDOUT, $errorResource = STDERR)
        {
            $this->name = $name;

            $this->setOutputResource($outputResource);
            $this->setErrorResource($errorResource);

            static::$maxLabelLength = max(static::$maxLabelLength, strlen($name));
        }

        /**
         * @param resource $resource
         *
         * @throws FileOperationFailure
         * @throws InvalidResource
         */
        protected function setOutputResource($resource)
        {
            if (false === $resource) {
                throw new FileOperationFailure('Cannot create file for output log file.');
            }

            $this->outputResource = $this->setResource($resource);
        }

        /**
         * @param resource $resource
         * @param bool $nullable
         *
         * @return resource
         * @throws InvalidResource
         */
        protected function setResource($resource, $nullable = false)
        {
            if (($nullable !== is_null($resource)) && (!is_resource($resource))) {
                throw new InvalidResource(sprintf('Invalid resource with <%s> found.', gettype($resource)));
            }

            return $resource;
        }

        /**
         * @param $resource
         *
         * @throws FileOperationFailure
         * @throws InvalidResource
         */
        protected function setErrorResource($resource)
        {
            if (false === $resource) {
                throw new FileOperationFailure('Cannot create file for error log file.');
            }

            $this->errorResource = $this->setResource($resource, true);
        }

        /**
         * Offset to retrieve
         *
         * @param mixed $offset
         *
         * @return mixed Can return all value types.
         */
        public function &offsetGet($offset)
        {
            return $this->get($offset);
        }

        /**
         * @param string $offset
         *
         * @return int
         */
        protected function &get(string $offset): int
        {
            $this->ensureOffset($offset);

            return $this->items[$offset];
        }

        /**
         * @param string $offset
         */
        protected function ensureOffset(string $offset): void
        {
            if (!$this->offsetExists($offset)) {
                $this->items[$offset] = 0;
            }
        }

        /**
         * Whether a offset exists
         *
         * @param mixed $offset
         *
         * @return boolean true on success or false on failure.
         */
        public function offsetExists($offset): bool
        {
            return isset($this->items[$offset]);
        }

        /**
         * Offset to set
         *
         * @param mixed $offset
         * @param mixed $value
         *
         * @return void
         */
        public function offsetSet($offset, $value): void
        {
            $this->set($offset, $value);
        }

        /**
         * @param string $offset
         * @param int $value
         */
        protected function set(string $offset, int $value): void
        {
            $this->ensureOffset($offset);
            $this->items[$offset] = $value;
        }

        /**
         * Offset to unset
         *
         * @param mixed $offset
         *
         * @return void
         */
        public function offsetUnset($offset): void
        {
            unset($this->items[$offset]);
        }

        /**
         * @return string
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * @param string $token
         * @param string $message
         * @param array $sprintfArguments
         */
        public function echoCount(string $token, string $message, ...$sprintfArguments): void
        {
            static $lines = 0;
            $this[$token]++;

            if (null !== $this->errorResource) {
                if (!$lines) {
                    // TODO: make it configurable
                    fwrite($this->errorResource, sprintf("Context\tFinding\t%s\tDetail\n", implode("\t", Content::getTableColumns())));
                }

                fwrite($this->errorResource, sprintf("%s\t%s\t%s\n", $this->name, $token, sprintf($message, ...$sprintfArguments)));
                $lines++;
            }
        }

        /**
         * @param string $message
         * @param string $tokenName
         *
         * @return Collector
         */
        public function echoStatement(string $message, string $tokenName): Collector
        {
            fwrite(
                $this->outputResource,
                sprintf(
                    " %s: %s\n",
                    str_pad($this->name, static::$maxLabelLength + 1, ' ', STR_PAD_LEFT),
                    sprintf(
                        $message,
                        str_pad(
                            number_format((isset($this[$tokenName])) ? $this[$tokenName] : 0, 0, '', '.'),
                            static::NUMBER_LENGTH,
                            ' ',
                            STR_PAD_LEFT
                        )
                    )
                )
            );

            return $this;
        }
    }
}
