<?php

namespace Mmt\GenericTable\Traits;

trait WithGenericTable
{
    public function refreshGenericTable()
    {
        $this->dispatch('refreshGenericTable');
    }

    private function injectParams(array $params)
    {
        $this->dispatch('injectParams', $params);
    }
}