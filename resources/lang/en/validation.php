<?php

return [
    'task' => [
        'self_dependency' => 'A task cannot depend on itself.',
    ],
    'milestone' => [
        'date_before_project_start' => 'The milestone date cannot be before the project start date.',
        'date_after_project_end' => 'The milestone date cannot be after the project end date.',
    ],
];
