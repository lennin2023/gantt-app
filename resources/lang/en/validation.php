<?php

return [
    'milestone' => [
        'date_before_project_start' => 'The milestone date cannot be before the project start date.',
        'date_after_project_end' => 'The milestone date cannot be after the project end date.',
    ],
    'task' => [
        'self_dependency' => 'A task cannot depend on itself.',
        'self_parent' => 'A task cannot be its own parent.',
        'parent_different_project' => 'The parent task must belong to the same project.',
        'dependency_different_project' => 'All dependency tasks must belong to the same project.',
    ],
];
