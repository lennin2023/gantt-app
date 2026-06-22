<?php

namespace App\Exceptions;

use Exception;

class BulkOperationException extends Exception
{
    public static function tasksNotFound(): self
    {
        return new self(__('exceptions.task.bulk.tasks_not_found'));
    }

    public static function tasksMustBelongToSameProject(): self
    {
        return new self(__('exceptions.task.bulk.tasks_must_belong_to_same_project'));
    }

    public static function noTaskIdsProvided(): self
    {
        return new self(__('exceptions.task.bulk.no_task_ids_provided'));
    }
}
