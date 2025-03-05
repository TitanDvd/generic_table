<?php

namespace Mmt\GenericTable\Support;

use Mmt\GenericTable\Enums\CommonDateFilter;

class DateFilterSettings
{

    /**
     * 
     * @param \Mmt\GenericTable\Enums\CommonDateFilter $options
     * 
     */
    public private(set) array $options = [];

    public function __construct(public string $databseColumnName, ...$options) 
    {
        $this->options = $options;
    }


    public function getBitMask()
    {
        $state = 0;
        if(count($this->options) == 0){
            return $state;
        }

        CommonDateFilter::addFlags($state, ...$this->options);
        return $state;
    }
}