<?php

return [
    'singular' => 'Tarea',
    'plural' => 'Tareas',
    'comments' => 'Comentarios de tarea',
    'activity_comments' => 'Comentarios de actividad',
    'empty' => 'Sin tareas',

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
        'edit' => 'Editar tarea',
        'view' => 'Ver detalle',
        'delete' => 'Eliminar tarea',
        'insert_before' => 'Insertar tarea encima',
        'insert_after' => 'Insertar tarea debajo',
        'schedule_from_previous' => 'Asignar fechas desde tarea anterior',
        'previous_task_label' => 'Tarea anterior (referencia)',
        'previous_task_info' => ':name — vence el :date',
        'no_previous_with_date' => 'No hay tarea anterior con fecha límite.',
    ],

    'notifications' => [
        'created' => 'Tarea creada',
        'updated' => 'Tarea actualizada',
        'deleted' => 'Tarea eliminada',
        'dates_updated' => 'Fechas actualizadas',
    ],

    'empty' => 'Sin tareas aún.',

    'activities' => [
        'singular' => 'Actividad',
        'plural' => 'Actividades',
        'empty' => 'Sin actividades aún.',
        'empty_hint' => 'Crea la primera actividad para comenzar a estructurar el proyecto.',
        'progress' => ':done/:total tareas',
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
            'edit' => 'Editar actividad',
            'delete' => 'Eliminar actividad',
            'delete_confirm' => '¿Eliminar actividad y todas sus tareas?',
            'reorder' => 'Arrastrar para reordenar',
            'expand_all' => 'Expandir todas',
            'collapse_all' => 'Contraer todas',
        ],

        'notifications' => [
            'created' => 'Actividad creada',
            'updated' => 'Actividad actualizada',
            'deleted' => 'Actividad eliminada',
        ],
    ],
];
