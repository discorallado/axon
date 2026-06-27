<?php

return [
    'singular' => 'Tarea',
    'plural' => 'Tareas',

    'fields' => [
        'code' => 'Código',
        'name' => 'Nombre',
        'description' => 'Descripción',
        'status' => 'Estado',
        'priority' => 'Prioridad',
        'assignees' => 'Responsables',
        'start_date' => 'Fecha de inicio',
        'due_date' => 'Fecha límite',
        'completed_at' => 'Fecha de cierre',
        'estimated_hours' => 'Horas estimadas',
        'actual_hours' => 'Horas reales',
        'activity' => 'Actividad',
        'parent_task' => 'Tarea padre',
    ],

    'actions' => [
        'create' => 'Nueva tarea',
        'create_for' => 'Nueva tarea en: :activity',
    ],

    'empty' => 'Sin tareas aún.',

    'activities' => [
        'singular' => 'Actividad',
        'plural' => 'Actividades',
        'fields' => [
            'name' => 'Nombre',
            'description' => 'Descripción',
            'order' => 'Orden',
            'status' => 'Estado',
            'start_date' => 'Fecha de inicio',
            'end_date' => 'Fecha de término',
        ],
        'actions' => [
            'create' => 'Nueva actividad',
        ],
    ],
];
