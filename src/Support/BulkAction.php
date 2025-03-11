<?php

namespace Mmt\GenericTable\Support;

use Closure;
use Str;

class BulkAction
{
    public private(set) bool $requireConfirmation = true;

    public function __construct(public string $actionName, public Closure $callback) { }

    public static function make(string $actionName, Closure $callback) : static
    {
        return new static($actionName, $callback);
    }

    public function ignoreUserConfirm()
    {
        $this->requireConfirmation = false;
    }
}