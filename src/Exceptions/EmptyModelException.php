<?php

namespace Mmt\GenericTable\Exceptions;

use Exception;
use Throwable;

class EmptyModelException extends Exception
{
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct('Eloquent model cannot be null', $code, $previous);
    }

    public function __toString() : string
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}