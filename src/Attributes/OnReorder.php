<?php

namespace Mmt\GenericTable\Attributes;

use Attribute;


#[Attribute(Attribute::TARGET_METHOD)]
class OnReorder 
{
    public function __construct() { }
}