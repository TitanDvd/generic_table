<?php

namespace Mmt\GenericTable\Interfaces;

use Mmt\GenericTable\Support\BulkActionCollection;

interface IBulkAction
{
    public BulkActionCollection $bulkActionCollection {get; set;}
}