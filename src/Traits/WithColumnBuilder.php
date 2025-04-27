<?php

namespace Mmt\GenericTable\Traits;

use Closure;
use Mmt\GenericTable\Enums\ColumnSettingFlags;

trait WithColumnBuilder
{
    private ?Closure $formatterCallback;

    public function hide()
    {
        $this->withSettings(ColumnSettingFlags::HIDDEN);
        return $this;
    }

    public function sortable()
    {
        $this->withSettings(ColumnSettingFlags::SORTABLE);
        return $this;
    }

    public function defaultSort()
    {
        $this->withSettings(ColumnSettingFlags::DEFAULT_SORT);
        return $this;
    }

    public function defaultSortAsc()
    {
        $this->withSettings(ColumnSettingFlags::DEFAULT_SORT_ASC);
        return $this;
    }

    public function defaultSortDesc()
    {
        $this->withSettings(ColumnSettingFlags::DEFAULT_SORT_DESC);
        return $this;
    }

    public function empty()
    {
        $this->withSettings(ColumnSettingFlags::EMPTY);
        return $this;
    }

    /**
     * 
     * @deprecated Use notExportable instead
     * 
     */
    public function exportable()
    {
        if(!ColumnSettingFlags::hasFlag($this->settings, ColumnSettingFlags::EXPORTABLE)) {
            $this->withSettings(ColumnSettingFlags::EXPORTABLE);
        }
        return $this;
    }

    public function notExportable()
    {
        if(ColumnSettingFlags::hasFlag($this->settings, ColumnSettingFlags::EXPORTABLE)) {
            ColumnSettingFlags::removeFlag($this->settings, ColumnSettingFlags::EXPORTABLE);
        }
        return $this;
    }

    public function searchable()
    {
        $this->withSettings(ColumnSettingFlags::SEARCHABLE);
        return $this;
    }

    public function hookFormatter(Closure $callback) : self
    {
        $this->formatterCallback = $callback;
        return $this;
    }
}