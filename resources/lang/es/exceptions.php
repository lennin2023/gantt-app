<?php

return [
    'task' => [
        'not_cancelled' => 'Solo se pueden restaurar tareas canceladas o eliminadas.',
        'already_in_status' => 'La tarea ya se encuentra en estado :status.',
        'deleted_cannot_be_updated' => 'Las tareas eliminadas no pueden ser actualizadas.',
        'not_deleted' => 'Solo las tareas eliminadas pueden ser restauradas.',
        'invalid_status_transition' => 'No se puede cambiar la tarea de :from a :to.',
        'cycle_detected' => 'Agregar esta dependencia crearía un ciclo.',
        'bulk' => [
            'tasks_not_found' => 'No se encontraron tareas con los IDs proporcionados.',
            'tasks_must_belong_to_same_project' => 'Todas las tareas deben pertenecer al mismo proyecto.',
            'no_task_ids_provided' => 'No se proporcionaron IDs de tareas.',
        ],
    ],
    'project' => [
        'already_in_status' => 'El proyecto ya se encuentra en estado :status.',
        'deleted_cannot_be_updated' => 'Los proyectos eliminados no pueden ser actualizados.',
        'not_deleted' => 'Solo los proyectos eliminados pueden ser restaurados.',
        'invalid_status_transition' => 'No se puede cambiar el proyecto de :from a :to.',
    ],
    'project_user' => [
        'not_found' => 'El usuario no está asignado a este proyecto.',
        'already_assigned' => 'El usuario ya está asignado a este proyecto.',
    ],
    'task_assignment' => [
        'already_exists' => 'El usuario ya está asignado a esta tarea.',
    ],
    'not_found' => 'Recurso no encontrado.',
];
