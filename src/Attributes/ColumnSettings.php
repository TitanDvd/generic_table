<?php

namespace Mmt\GenericTable\Attributes;

use Mmt\GenericTable\Enums\ColumnSettingFlags;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ColumnSettings
{
    public array $settings;

    public function __construct(ColumnSettingFlags $settings, ColumnSettingFlags ...$moreSettings)
    {
        $this->settings = array_merge([$settings], ...$moreSettings);
    }
}