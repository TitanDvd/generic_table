<?php

namespace Mmt\GenericTable\Interfaces;

use Mmt\GenericTable\Support\EventArgs;

interface IEvent
{
    /**
     * 
     * A callback for each trigered event on generic table system
     * 
     */
    public function dispatchCallback(EventArgs $arguments) : void;
}