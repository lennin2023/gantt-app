<?php

return [
    'milestone' => [
        'date_before_project_start' => 'La fecha del milestone no puede ser anterior a la fecha de inicio del proyecto.',
        'date_after_project_end' => 'La fecha del milestone no puede ser posterior a la fecha de fin del proyecto.',
    ],
    'task' => [
        'self_dependency' => 'Una tarea no puede depender de sí misma.',
        'self_parent' => 'Una tarea no puede ser su propio padre.',
        'parent_different_project' => 'La tarea padre debe pertenecer al mismo proyecto.',
        'dependency_different_project' => 'Todas las dependencias deben pertenecer al mismo proyecto.',
    ],
];
