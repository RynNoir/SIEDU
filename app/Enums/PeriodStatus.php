<?php

namespace App\Enums;

enum PeriodStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';
}
