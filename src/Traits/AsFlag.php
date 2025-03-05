<?php

namespace Mmt\GenericTable\Traits;

trait AsFlag
{
    public static function makeBitMask(\BackedEnum ...$rack): int
    {
        $bitMask = 0;
        foreach ($rack as $enum)
            $bitMask |= $enum->value;
        return $bitMask;
    }

    public function isFlagOf(int $flag): bool
    {
        return ($this->value & $flag) === $this->value;
    }

    public static function hasFlag(int $state, self $flag) : bool
    {
        return ($state & $flag->value) !== 0;
    }

    public static function removeFlag(int &$state, self $flag) : void
    {
        $state &= ~$flag->value;
    }

    public static function addFlag(int &$state, self $flag) : void
    {
        $state |= $flag->value;
    }

    public static function addFlags(int &$state, self ...$flags) : void
    {
        foreach ($flags as $flag) {
            self::addFlag($state, $flag);
        }
    }
}