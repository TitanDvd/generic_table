<?php

namespace Mmt\GenericTable\Support;

use Illuminate\Database\Eloquent\Builder;
use Mmt\GenericTable\Enums\DatabaseEventQueryState;

class DatabaseEvent extends EventArgs
{
    public function __construct(
        public DatabaseEventQueryState $queryState,
        public Builder $builder,
        public $injectedArguments
    ) { }
}