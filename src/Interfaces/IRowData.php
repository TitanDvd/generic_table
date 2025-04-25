<?php

namespace Mmt\GenericTable\Interfaces;

use Illuminate\Database\Eloquent\Model;
use stdClass;

interface IRowData
{
    public Model|stdClass $origin {get; set;}

    public function get(string $property) : mixed;
}