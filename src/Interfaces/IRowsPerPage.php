<?php

namespace Mmt\GenericTable\Interfaces;

interface IRowsPerPage
{
    public int $rowsPerPage {get; set;}
    public array $rowsPerPageOptions {get; set;}
}