<?php

namespace App\Exceptions;

use Exception;

class BulkOperationException extends Exception
{
    public static function tasksNotFound(): self
    {
        return new self('No tasks found with the provided IDs');
    }

    public static function tasksMustBelongToSameProject(): self
    {
        return new self('All tasks must belong to the same project');
    }

    public static function noTaskIdsProvided(): self
    {
        return new self('No task IDs provided');
    }
}
