<?php

namespace App\Enums;

/**
 * Generic transaction status for approvable requests
 * (leave, overtime, claim, correction, etc).
 */
enum RequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Revision = 'revision';
    case Cancelled = 'cancelled';
}
