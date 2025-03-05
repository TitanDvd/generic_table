<?php

namespace Mmt\GenericTable\Interfaces;

use Illuminate\Http\Response;
use Mmt\GenericTable\Support\ExportEventArgs;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

interface IExportable 
{

    /**
     * 
     * Used to handle a custom export process.
     * 
     */
    public function onExport(ExportEventArgs $args) :  BinaryFileResponse|Response;
}