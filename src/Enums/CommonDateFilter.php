<?php

namespace Mmt\GenericTable\Enums;

use DateTime;
use InvalidArgumentException;
use Mmt\GenericTable\Traits\AsFlag;

enum CommonDateFilter : int
{
    use AsFlag;

    case LAST_24_HOURS = 1<<1;
    case LAST_48_HOURS = 1<<2;
    case LAST_72_HOURS = 1<<3;
    case LAST_7_DAYS = 1<<4;
    case LAST_14_DAYS = 1<<5;
    case THIS_MONTH = 1<<6;
    case LAST_2_MONTHS = 1<<7;
    case LAST_3_MONTHS = 1<<8;
    case LAST_6_MONTHS = 1<<9;
    case PAST_MONTH = 1<<10;
    case PAST_2_MONTHS = 1<<11;
    case PAST_4_MONTHS = 1<<12;
    case PAST_6_MONTHS = 1<<13;
    case THIS_YEAR = 1<<14;
    case LAST_2_YEARS = 1<<15;
    case PAST_YEAR = 1<<16;
    case CUSTOM_RANGE = 1<<17;
    case SO_FAR_THIS_MONTH = 1<<18;
    case ALL_RANGES = (1<<18)-18;

    public static function getDateRange(self $flag)
    {
        $now = new DateTime();
        switch ($flag)
        {
            case self::LAST_24_HOURS:
                return [new DateTime('-24 hours'), $now];
            case self::LAST_48_HOURS:
                return [new DateTime('-48 hours'), $now];
            case self::LAST_72_HOURS:
                return [new DateTime('-72 hours'), $now];
            case self::LAST_7_DAYS:
                return [new DateTime('-7 days'), $now];
            case self::LAST_14_DAYS:
                return [new DateTime('-14 days'), $now];
            case self::THIS_MONTH:
                return [new DateTime('first day of this month'), new DateTime('last day of this month')];
            case self::SO_FAR_THIS_MONTH:
                return [new DateTime('first day of this month midnight'), $now];
            case self::LAST_2_MONTHS:
                return [
                    (new DateTime('first day of this month'))->modify('-1 month'),
                    new DateTime('last day of last month 23:59:59')
                ];
            case self::LAST_3_MONTHS:
                return [
                    (new DateTime('first day of this month'))->modify('-2 months'),
                    new DateTime('last day of last month 23:59:59')
                ];
            case self::LAST_6_MONTHS:
                return [
                    (new DateTime('first day of this month'))->modify('-5 months'),
                    new DateTime('last day of last month 23:59:59')
                ];
            case self::PAST_MONTH:
                return [
                    (new DateTime('first day of last month midnight')),
                    (new DateTime('last day of last month 23:59:59'))
                ];
            case self::PAST_2_MONTHS:
                return [
                    (new DateTime('first day of last month midnight'))->modify('-1 month'),
                    (new DateTime('last day of last month 23:59:59'))
                ];
            case self::PAST_4_MONTHS:
                return [
                    (new DateTime('first day of last month midnight'))->modify('-3 months'),
                    (new DateTime('last day of last month 23:59:59'))
                ];
            case self::PAST_6_MONTHS:
                return [
                    (new DateTime('first day of last month midnight'))->modify('-5 months'),
                    (new DateTime('last day of last month 23:59:59'))
                ];
            case self::THIS_YEAR:
                return [
                    new DateTime('first day of January this year midnight'),
                    new DateTime('last day of December this year 23:59:59')
                ];
            case self::LAST_2_YEARS:
                return [
                    (new DateTime('first day of January this year midnight'))->modify('-1 year'),
                    new DateTime('last day of December last year 23:59:59')
                ];
    
            case self::PAST_YEAR:
                return [
                    new DateTime('first day of January last year midnight'),
                    new DateTime('last day of December last year 23:59:59')
                ];
    
            case self::CUSTOM_RANGE:
                throw new InvalidArgumentException("For CUSTOM_RANGE, you need to define the range.");
    
            case self::ALL_RANGES:
                throw new InvalidArgumentException("ALL_RANGES cannot be use as range.");
    
            default:
                throw new InvalidArgumentException("Unknown date range filter");
        }
        
    }

    public static function getDateRangeDescription(self $flag): string
    {
        switch ($flag) {
            case self::LAST_24_HOURS:
                return 'Last 24 hours';
            case self::LAST_48_HOURS:
                return 'Last 48 hours';
            case self::LAST_72_HOURS:
                return 'Last 72 hours';
            case self::LAST_7_DAYS:
                return 'Last 7 days';
            case self::LAST_14_DAYS:
                return 'Last 14 days';
            case self::THIS_MONTH:
                return 'This full month';
            case self::SO_FAR_THIS_MONTH:
                return 'From the start of this month until today';
            case self::LAST_2_MONTHS:
                return 'Last 2 months';
            case self::LAST_3_MONTHS:
                return 'Last 3 months';
            case self::LAST_6_MONTHS:
                return 'Last 6 months';
            case self::PAST_MONTH:
                return 'Last month';
            case self::PAST_2_MONTHS:
                return 'The previous 2 months';
            case self::PAST_4_MONTHS:
                return 'The previous 4 months';
            case self::PAST_6_MONTHS:
                return 'The previous 6 months';
            case self::THIS_YEAR:
                return 'This year';
            case self::LAST_2_YEARS:
                return 'Last 2 years';
            case self::PAST_YEAR:
                return 'Last year';
            case self::CUSTOM_RANGE:
                return 'Custom range';
            case self::ALL_RANGES:
                return 'All ranges';
            default:
                throw new InvalidArgumentException("Unrecognized date filter.");
        }
    }

}