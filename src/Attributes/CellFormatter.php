<?php

namespace Mmt\GenericTable\Attributes;


#[\Attribute(\Attribute::TARGET_METHOD)]
class CellFormatter
{
    public function __construct(public string $dbColumnName) { }
}