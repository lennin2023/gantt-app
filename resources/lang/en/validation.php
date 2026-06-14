<?php

return [
    'task' => [
        'self_dependency' => 'A task cannot depend on itself.',
        'self_parent' => 'A task cannot be its own parent.',
        'parent_different_project' => 'The parent task must belong to the same project.',
        'dependency_different_project' => 'All dependency tasks must belong to the same project.',
    ],
];
