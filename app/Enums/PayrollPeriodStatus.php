<?php

namespace App\Enums;

enum PayrollPeriodStatus: string
{
    case Draft = 'draft';
    case Calculated = 'calculated';
    case Reviewed = 'reviewed';
    case Approved = 'approved';
    case Locked = 'locked';
    case Disbursed = 'disbursed';
}
