<?php

namespace App\Enums;

enum SummaryScope: string
{
    case Global = 'global';
    case Department = 'department';
    case Employee = 'employee';
}
