<?php

return [
    'singular' => 'Proyecto',
    'plural' => 'Proyectos',

    'fields' => [
        'code' => 'Código',
        'code_prefix' => 'Prefijo',
        'name' => 'Nombre',
        'description' => 'Descripción',
        'client' => 'Cliente',
        'status' => 'Estado',
        'priority' => 'Prioridad',
        'manager' => 'Responsable',
        'start_date' => 'Fecha de inicio',
        'end_date' => 'Fecha de término',
        'completed_at' => 'Fecha de cierre',
        'color' => 'Color',
        'submission_request' => 'Solicitud de origen',
    ],

    'sections' => [
        'details' => 'Detalle del proyecto',
        'team' => 'Equipo',
        'planning' => 'Planificación',
        'activities' => 'Actividades',
        'tasks' => 'Tareas',
        'attachments' => 'Adjuntos',
        'comments' => 'Comentarios',
    ],

    'actions' => [
        'create_from_submission' => 'Crear Proyecto',
        'create_from_submission_label' => 'Crear proyecto desde solicitud',
    ],

    'notifications' => [
        'submission_approved_subject' => 'Solicitud aprobada — crear proyecto',
        'submission_approved_body' => 'La solicitud :reference de :company fue aprobada. Crea el proyecto para iniciar el seguimiento.',
        'project_created' => 'Proyecto creado exitosamente.',
    ],

    'statuses' => [
        'singular' => 'Estado de Proyecto',
        'plural' => 'Estados de Proyecto',
        'fields' => [
            'name' => 'Nombre',
            'color' => 'Color',
            'order' => 'Orden',
            'is_completed' => '¿Es estado de cierre?',
        ],
        'seed' => [
            'planificacion' => 'Planificación',
            'en_ejecucion' => 'En Ejecución',
            'en_pausa' => 'En Pausa',
            'completado' => 'Completado',
            'cancelado' => 'Cancelado',
        ],
    ],

    'members' => [
        'singular' => 'Miembro',
        'plural' => 'Equipo del Proyecto',
        'fields' => [
            'user' => 'Usuario',
            'role' => 'Rol en el proyecto',
        ],
    ],
];
