<?php

namespace Mmt\GenericTable\Enums;

use Mmt\GenericTable\Traits\AsFlag;

enum PaginationRack : int
{
    use AsFlag;

    case NONE = 0;

    case TOP = 1<<1;

    case BOTTOM = 1<<2;
}