<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Exception thrown when trying to assign non-existent parent staff (pid)
 */
class ParentStaffNotFoundException extends BadRequestHttpException
{
    public function __construct(int $pid, \Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Сотрудник с ID %d не найден для назначения руководителем', $pid),
            $previous
        );
    }
}