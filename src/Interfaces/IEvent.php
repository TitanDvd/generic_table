<?php

namespace Mmt\GenericTable\Interfaces;

use Mmt\GenericTable\Enums\EventType;

interface IEvent
{
    /**
     * 
     * A callback for each trigered event on generic table system
     * 
     */
    public function dispatchCallback(EventType $eventType, &$params) : void;
}