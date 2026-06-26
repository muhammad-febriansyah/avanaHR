<?php

namespace App\Approvals;

use RuntimeException;

/**
 * Thrown when an approval action is invalid (wrong approver, already decided,
 * self-approval blocked by segregation of duties, etc).
 */
class ApprovalException extends RuntimeException {}
