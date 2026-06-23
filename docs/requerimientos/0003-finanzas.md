# REQ-0003 — Finanzas: Proveedores, Órdenes de Compra, Facturas

- **Estado:** Aprobado — implementar después de REQ-0002-A
- **Depende de:** REQ-0002-A (proyectos, clientes)
- **Origen:** Diseño arquitectónico aprobado 2026-06-23

---

## Resumen

Módulo de finanzas básicas: proveedores, órdenes de compra (OC) y facturas (entrada y salida), vinculadas a proyectos. Sin líneas de ítem — monto total + descripción libre.

---

## Modelo de datos

### `suppliers`
`id (ulid)`, `organization_id`, `name`, `rut`, `email`, `phone`, `address`, `contact_name`, `bank_name`, `bank_account`, `notes`, `timestamps`, `deleted_at`

### `purchase_orders`
`id (ulid)`, `organization_id`, `supplier_id`, `project_id (nullable)`, `number`, `date`, `currency (CLP/USD/EUR)`, `amount_net`, `tax_amount`, `amount_total`, `status`, `description`, `notes`, `approved_by (FK users)`, `approved_at`, `timestamps`, `deleted_at`

Status enum PHP: `borrador → emitida → recibida → anulada`

### `invoices`
`id (ulid)`, `organization_id`, `type (incoming/outgoing)`, `client_id (nullable)`, `supplier_id (nullable)`, `project_id (nullable)`, `purchase_order_id (nullable)`, `number`, `date`, `due_date`, `currency`, `amount_net`, `tax_amount`, `amount_total`, `status`, `payment_date`, `notes`, `timestamps`, `deleted_at`

Status enum PHP: `pendiente → pagada → vencida → anulada`

---

## Criterios de aceptación

1. Se pueden crear proveedores y asociarlos a OC.
2. Las OC se pueden vincular a un proyecto y tienen flujo de aprobación.
3. Las facturas pueden ser de entrada (proveedor) o salida (cliente) y vincularse a proyecto y/o OC.
4. Adjuntos polimórficos disponibles en OC y facturas (PDF de la factura).
5. Listado en Filament con filtros por proyecto, estado y proveedor/cliente.
6. Tests Pest cubren: creación de OC/factura, aislamiento por `organization_id`.
