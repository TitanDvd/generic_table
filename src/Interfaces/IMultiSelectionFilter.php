<?php

namespace Mmt\GenericTable\Interfaces;

use Mmt\GenericTable\Support\SelectionFilterSettings;

interface IMultiSelectionFilter
{
    public SelectionFilterSettings $multiSelectionFilterSettings {get; set;}
}