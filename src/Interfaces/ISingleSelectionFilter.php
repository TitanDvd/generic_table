<?php

namespace Mmt\GenericTable\Interfaces;

use Mmt\GenericTable\Support\SelectionFilterSettings;

interface ISingleSelectionFilter
{
    public SelectionFilterSettings $singleSelectionFilterSettings {get; set;}
}