<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Exception thrown when trying to delete staff member who has subordinates
 */
class StaffHasSubordinatesException extends BadRequestHttpException
{
    public function __construct(int $staffId, array $subordinates = [], \Throwable $previous = null)
    {
        $subordinateNames = array_map(function($subordinate) {
            return $subordinate->getFirstName() . ' ' . $subordinate->getLastName();
        }, $subordinates);
        
        $message = sprintf(
            'Невозможно удалить сотрудника с ID %d. У данного сотрудника есть %d подчиненный(х): %s',
            $staffId,
            count($subordinates),
            implode(', ', $subordinateNames)
        );
        
        parent::__construct($message, $previous);
    }
}