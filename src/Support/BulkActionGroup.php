<?php

namespace Mmt\GenericTable\Support;

class BulkActionGroup
{
    public array $actions = [];


    public function __construct(public string $actionLabel, BulkAction|BulkActionGroup ...$actions)
    {
        array_push($this->actions, ...$actions);
    }


    public static function make(string $actionLabel, BulkAction|BulkActionGroup ...$actions)
    {
        $groupsOrActions = new static($actionLabel, ...$actions);
        return $groupsOrActions;
    }
}