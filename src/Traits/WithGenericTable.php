<?php

namespace Mmt\GenericTable\Traits;

trait WithGenericTable
{
    public function refreshGenericTable()
    {
        $this->dispatch('refresh_generic_table');
    }
}