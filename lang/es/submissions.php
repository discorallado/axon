<?php

return [
    'singular' => 'Solicitud',
    'plural' => 'Solicitudes',

    'fields' => [
        'reference_code' => 'N° de referencia',
        'status' => 'Estado',
        'submitter_name' => 'Solicitante',
        'submitter_email' => 'Correo',
        'submitter_phone' => 'Teléfono',
        'submitter_company' => 'Empresa',
        'submitted_at' => 'Fecha de envío',
        'assigned_to' => 'Responsable',
        'internal_notes' => 'Notas internas',
        'template' => 'Formulario',
        'template_version' => 'Versión',
        'answers' => 'Respuestas',
        'attachments' => 'Adjuntos',
        'history' => 'Historial de estados',
        'comments' => 'Comentarios internos',
    ],

    'status' => [
        'nueva' => 'Nueva',
        'en_revision' => 'En revisión',
        'cotizada' => 'Cotizada',
        'aprobada' => 'Aprobada',
        'rechazada' => 'Rechazada',
    ],

    'actions' => [
        'change_status' => 'Cambiar estado',
        'assign' => 'Asignar responsable',
        'export_excel' => 'Exportar a Excel',
        'export_pdf' => 'Exportar a PDF',
        'add_comment' => 'Agregar comentario',
        'reopen' => 'Reabrir solicitud',
    ],

    'history' => [
        'created' => 'Solicitud creada',
        'changed' => 'Estado cambiado de :from a :to',
        'by' => 'por :user',
    ],

    'notifications' => [
        'new_submission_subject' => 'Nueva solicitud recibida: :reference',
        'new_submission_body' => 'Se ha recibido una nueva solicitud (:reference) de :name (:company). Ingrese al panel para gestionarla.',
        'confirmation_subject' => 'Confirmación de solicitud :reference',
        'confirmation_body' => 'Hemos recibido su solicitud con número de referencia :reference. Nos pondremos en contacto a la brevedad.',
    ],

    'export' => [
        'filename' => 'solicitud-:reference',
        'sheet' => 'Solicitud',
        'header' => 'Solicitud :reference — :date',
        'contact' => 'Datos de contacto',
        'responses' => 'Respuestas',
        'no_answer' => '(sin respuesta)',
    ],

    'errors' => [
        'not_found' => 'Solicitud no encontrada.',
        'forbidden_status' => 'No tiene permiso para cambiar al estado seleccionado.',
        'invalid_transition' => 'La transición de estado no está permitida.',
        'terminal' => 'Esta solicitud se encuentra en un estado final y no puede modificarse.',
    ],
];
