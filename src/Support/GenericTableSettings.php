<?php

namespace Mmt\GenericTable\Support;

use Illuminate\Database\Eloquent\Model;
use Mmt\GenericTable\Components\ColumnCollection;

final class GenericTableSettings
{
    public function __construct(
        public Model|string $model,
        public ?ColumnCollection $columns = null,
        public bool $useBuilder = false
    ){ }
}