<?php

namespace Mmt\GenericTable\Enums;

enum HrefTarget : string
{
    case BLANK  = "_blank";
    case PARENT = "_parent";
    case SELF   = "_self";
    case TOP    = "_top";
}