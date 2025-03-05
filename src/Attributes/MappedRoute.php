<?php

namespace Mmt\GenericTable\Attributes;

use Attribute;
use Mmt\GenericTable\Enums\HrefTarget;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MappedRoute
{
    public function __construct(public string $route, public array $routeParams = [], public string $label = '', public HrefTarget $target = HrefTarget::BLANK) { }

    public static function make(string $route, array $routeParams = [], string $label = '', HrefTarget $target = HrefTarget::BLANK) : static
    {
        return new static($route, $routeParams, $label, $target);
    } 
}