<?php

namespace Mmt\GenericTable\Interfaces;

use Mmt\GenericTable\Support\DateFilterSettings;

interface IDateRangeFilter
{
    public DateFilterSettings $dateFilterSettings { get; set; }
}