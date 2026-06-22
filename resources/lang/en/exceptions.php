<?php

return [
    'task' => [
        'not_cancelled' => 'Only cancelled or deleted tasks can be restored.',
        'already_in_status' => 'The task is already in status :status.',
        'deleted_cannot_be_updated' => 'Deleted tasks cannot be updated.',
        'not_deleted' => 'Only deleted tasks can be restored.',
        'invalid_status_transition' => 'Cannot transition task from :from to :to.',
        'cycle_detected' => 'Adding this dependency would create a cycle.',
        'bulk' => [
            'tasks_not_found' => 'No tasks found with the provided IDs.',
            'tasks_must_belong_to_same_project' => 'All tasks must belong to the same project.',
            'no_task_ids_provided' => 'No task IDs provided.',
        ],
    ],
    'project' => [
        'already_in_status' => 'The project is already in status :status.',
        'deleted_cannot_be_updated' => 'Deleted projects cannot be updated.',
        'not_deleted' => 'Only deleted projects can be restored.',
        'invalid_status_transition' => 'Cannot transition project from :from to :to.',
    ],
    'project_user' => [
        'not_found' => 'The user is not assigned to this project.',
        'already_assigned' => 'The user is already assigned to this project.',
    ],
    'task_assignment' => [
        'already_exists' => 'The user is already assigned to this task.',
    ],
    'not_found' => 'Resource not found.',
];
