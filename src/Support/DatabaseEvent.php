<?php

namespace Mmt\GenericTable\Support;

use Illuminate\Database\Eloquent\Builder;

class DatabaseEvent extends EventArgs
{
    public function __construct(
        public Builder $builder,
        public $injectedArguments
    ) { }
}