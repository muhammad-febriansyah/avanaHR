<?php

namespace App\Enums;

enum SubscriptionTier: string
{
    case Essential = 'essential';
    case Professional = 'professional';
    case Enterprise = 'enterprise';
}
