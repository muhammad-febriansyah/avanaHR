<?php

namespace App\Enums;

enum EmployeeStatus: string
{
    case Probation = 'probation';
    case Active = 'active';
    case OnLeave = 'on_leave';
    case Suspended = 'suspended';
    case Resigned = 'resigned';
    case Terminated = 'terminated';
}
