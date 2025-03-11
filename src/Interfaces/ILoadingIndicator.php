<?php

namespace Mmt\GenericTable\Interfaces;

use Illuminate\View\View;

interface ILoadingIndicator
{
    /**
     * 
     * The html view to be use as loading indicator.
     * Keep in mind that the container needs to be
     * possisionated as absolute
     * 
     */
    public function tableLoadingIndicatorView() : View;
}