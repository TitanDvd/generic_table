<?php

namespace Mmt\GenericTable\Interfaces;

interface IColumn
{
    public string $columnTitle {get; set;}

    public ?string $databaseColumnName {get; set;}
}