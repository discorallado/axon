<?php

return [
    'template' => [
        'singular' => 'Plantilla de formulario',
        'plural' => 'Plantillas de formularios',
        'fields' => [
            'name' => 'Nombre',
            'slug' => 'Identificador (slug)',
            'description' => 'Descripción',
            'is_active' => 'Enlace público activo',
            'view_type' => 'Tipo de vista',
            'version' => 'Versión',
        ],
        'view_types' => [
            'default' => 'Estándar',
            'wizard' => 'Asistente (wizard)',
            'fat_checklist' => 'Checklist FAT',
        ],
        'actions' => [
            'copy_link' => 'Copiar enlace público',
            'preview' => 'Vista previa',
            'new_version' => 'Publicar nueva versión',
        ],
        'messages' => [
            'link_copied' => 'Enlace copiado al portapapeles.',
            'inactive' => 'Este formulario no está disponible actualmente.',
        ],
    ],

    'section' => [
        'singular' => 'Sección',
        'plural' => 'Secciones',
        'fields' => [
            'title' => 'Título',
            'description' => 'Descripción (opcional)',
            'sort_order' => 'Orden',
        ],
    ],

    'question' => [
        'singular' => 'Pregunta',
        'plural' => 'Preguntas',
        'fields' => [
            'label' => 'Etiqueta',
            'key' => 'Clave interna',
            'type' => 'Tipo',
            'placeholder' => 'Texto de ayuda (placeholder)',
            'help_text' => 'Texto de ayuda adicional',
            'is_required' => 'Obligatoria',
            'sort_order' => 'Orden',
            'options' => 'Opciones',
            'option_value' => 'Valor',
            'option_label' => 'Etiqueta',
            'validation_rules' => 'Reglas de validación',
        ],
        'types' => [
            'text' => 'Texto corto',
            'textarea' => 'Texto largo',
            'number' => 'Número',
            'select' => 'Selección única',
            'multiselect' => 'Selección múltiple',
            'boolean' => 'Sí / No',
            'date' => 'Fecha',
            'file' => 'Archivo adjunto',
            'email' => 'Correo electrónico',
            'phone' => 'Teléfono',
        ],
    ],

    'rule' => [
        'singular' => 'Regla condicional',
        'plural' => 'Reglas condicionales',
        'fields' => [
            'trigger_question' => 'Si la pregunta',
            'operator' => 'Cumple la condición',
            'trigger_value' => 'Valor',
            'action' => 'Entonces',
            'target_type' => 'Aplicar a',
            'target_question' => 'Pregunta objetivo',
            'target_section' => 'Sección objetivo',
        ],
        'operators' => [
            'eq' => 'es igual a',
            'neq' => 'no es igual a',
            'gt' => 'es mayor que',
            'lt' => 'es menor que',
            'gte' => 'es mayor o igual a',
            'lte' => 'es menor o igual a',
            'contains' => 'contiene',
            'not_contains' => 'no contiene',
            'is_empty' => 'está vacío',
            'is_not_empty' => 'no está vacío',
        ],
        'actions' => [
            'show' => 'Mostrar',
            'hide' => 'Ocultar',
        ],
        'target_types' => [
            'question' => 'Pregunta',
            'section' => 'Sección',
        ],
    ],

    'public' => [
        'title' => 'Formulario de solicitud',
        'submit' => 'Enviar solicitud',
        'submitting' => 'Enviando...',
        'required' => 'Campo obligatorio',
        'optional' => 'Opcional',
        'file_hint' => 'Haga clic o arrastre un archivo aquí',
        'file_types' => 'Tipos aceptados: :types',
        'file_max' => 'Tamaño máximo: :size MB',
        'contact_info' => 'Datos de contacto',
        'submitter_name' => 'Nombre completo',
        'submitter_email' => 'Correo electrónico',
        'submitter_phone' => 'Teléfono (opcional)',
        'submitter_company' => 'Empresa (opcional)',
        'spam_error' => 'Se ha detectado actividad sospechosa. Por favor inténtelo más tarde.',
        'throttle_error' => 'Ha enviado demasiadas solicitudes. Por favor espere unos minutos.',
    ],

    'thanks' => [
        'title' => '¡Solicitud enviada con éxito!',
        'message' => 'Hemos recibido su solicitud. Nos pondremos en contacto a la brevedad.',
        'reference' => 'Número de referencia: :code',
        'email_sent' => 'Se ha enviado una confirmación a :email.',
    ],
];
