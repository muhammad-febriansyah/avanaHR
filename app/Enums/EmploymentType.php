<?php

namespace App\Enums;

enum EmploymentType: string
{
    case Permanent = 'permanent';
    case Contract = 'contract';
    case Intern = 'intern';
    case Outsource = 'outsource';
}
