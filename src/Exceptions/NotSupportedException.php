<?php

namespace Mmt\GenericTable\Exceptions;

use Exception;
use Throwable;

class NotSupportedException extends Exception
{
    public function __construct($message, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function __toString() : string
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}