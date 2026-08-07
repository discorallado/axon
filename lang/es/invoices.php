<?php

return [
    'singular' => 'Factura',
    'plural' => 'Facturas',

    'fields' => [
        'code' => 'Código',
        'number' => 'Folio',
        'type' => 'Tipo',
        'date' => 'Fecha',
        'due_date' => 'Fecha de vencimiento',
        'payment_date' => 'Fecha de pago',
        'client' => 'Cliente',
        'supplier' => 'Proveedor',
        'project' => 'Proyecto',
        'purchase_order' => 'Orden de compra',
        'currency' => 'Moneda',
        'amount_net' => 'Monto neto',
        'tax_amount' => 'IVA',
        'amount_total' => 'Monto total',
        'status' => 'Estado',
        'notes' => 'Notas',
        'attachment_file' => 'Archivo (PDF)',
    ],

    'sections' => [
        'details' => 'Datos de la factura',
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
