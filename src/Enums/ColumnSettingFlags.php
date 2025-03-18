<?php

namespace Mmt\GenericTable\Enums;

use Mmt\GenericTable\Traits\AsFlag;

enum ColumnSettingFlags : int
{
    use AsFlag;
    
    case SORTABLE           = 1<< 1;
    case EXPORTABLE         = 1<< 2;
    case SEARCHABLE         = 1<< 3;
    case HIDDEN             = 1<< 4;
    case TOGGLE_VISIBILITY  = 1<< 5;
    case DEFAULT_SORT       = 1<< 6;
    case DEFAULT_SORT_ASC   = 1<< 7;
    case DEFAULT_SORT_DESC  = 1<< 8;

    public static function sanitizeSortFlags(int $flags): int
    {
        $conflictingFlags = [
            self::DEFAULT_SORT->value,
            self::DEFAULT_SORT_ASC->value,
            self::DEFAULT_SORT_DESC->value
        ];
        
        $active = array_filter($conflictingFlags, fn($flag) => ($flags & $flag) !== 0);

        if (count($active) > 1) {
            // If there is more than one, keep only the one with the highest priority
            return ($flags & ~array_sum($conflictingFlags)) | max($active);
        }

        return $flags;
    }
}