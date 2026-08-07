<?php

return [
    'singular' => 'Orden de Compra',
    'plural' => 'Órdenes de Compra',

    'fields' => [
        'code' => 'Código',
        'number' => 'Folio (proveedor)',
        'date' => 'Fecha',
        'supplier' => 'Proveedor',
        'project' => 'Proyecto',
        'currency' => 'Moneda',
        'amount_net' => 'Monto neto',
        'tax_amount' => 'IVA',
        'amount_total' => 'Monto total',
        'status' => 'Estado',
        'description' => 'Descripción',
        'notes' => 'Notas',
        'approved_by' => 'Aprobada por',
        'approved_at' => 'Fecha de aprobación',
        'attachment_file' => 'Archivo (PDF)',
    ],

    'sections' => [
        'details' => 'Datos de la orden',
        'amounts' => 'Montos',
        'status_history' => 'Historial de estados',
        'attachments' => 'Adjuntos',
    ],

    'actions' => [
        'change_status' => 'Cambiar estado',
        'upload_attachment' => 'Adjuntar archivo',
        'delete_attachments' => 'Eliminar adjuntos',
    ],
];
