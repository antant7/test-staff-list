<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Exception thrown when staff member is not found
 */
class StaffNotFoundException extends NotFoundHttpException
{
    public function __construct(int $id, \Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Staff member with ID %d not found', $id),
            $previous
        );
    }
}