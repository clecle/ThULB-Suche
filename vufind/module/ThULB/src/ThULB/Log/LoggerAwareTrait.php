<?php

namespace ThULB\Log;

use \Exception;
use \VuFind\Log\LoggerAwareTrait as OriginalLoggerAwareTrait;

trait LoggerAwareTrait{

    use OriginalLoggerAwareTrait;

    protected function logException(Exception $exception) : void {
        if($this->logger) {
            if (is_callable([$this->logger, 'logException'])) {
                $this->logger->logException($exception, new \Laminas\Stdlib\Parameters($_SERVER));
            }
            else {
                $this->logError($exception->getMessage(), ['Exception logging not available.']);
            }
        }
    }
}