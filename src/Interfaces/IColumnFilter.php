<?php

namespace Mmt\GenericTable\Interfaces;

use Mmt\GenericTable\Support\FilterCollection;

interface IColumnFilter
{
    public FilterCollection $filters {get; set;}
}