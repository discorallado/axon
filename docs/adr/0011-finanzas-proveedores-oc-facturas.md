# ADR-0011 — Finanzas básicas: Proveedores, Órdenes de Compra y Facturas (REQ-0003)

- **Fecha:** 2026-08-06
- **Estado:** Aceptado
- **Módulos:** REQ-0003

---

## Contexto

REQ-0003 (`docs/requerimientos/0003-finanzas.md`, aprobado 2026-06-23) define
el alcance funcional: proveedores, órdenes de compra (OC) y facturas (entrada
y salida) vinculadas a proyectos, sin líneas de ítem. Al elaborar el diseño
técnico completo en rol `/arquitecto` surgieron seis decisiones no cubiertas
por el documento original ni por precedente claro en el resto del PMIS. Se
resuelven aquí antes de pasar a implementación.

---

## Decisiones

### 1. `code` autogenerado además de `number` (folio real)

**Decisión:** `purchase_orders` e `invoices` agregan un campo `code` generado
automáticamente (`OC-{año}-{seq}`, `FC-{año}-{seq}`, único por
`organization_id`), igual patrón que `Project`/`Task` (Observer +
`str_pad` correlativo). El campo `number` del documento original se mantiene
como el folio real (factura del proveedor, DTE, etc.), de ingreso libre y no
único.

**Alternativa descartada:** usar solo `number` como identificador. Se
descarta porque puede llegar vacío, repetirse entre proveedores distintos, o
no existir aún al crear el registro en borrador — el sistema necesita un
identificador interno estable desde el día uno, igual que ya ocurre con
`Project.code` y `Task.code`.

---

### 2. Transición automática a `vencida`: job programado, no accessor

**Decisión:** Un comando programado diario (`schedule`) recorre facturas en
estado `pendiente` con `due_date < hoy` y las transiciona a `vencida`,
persistiendo el cambio en la columna `status` (vía el mismo servicio de
máquina de estados, para que quede registrado en `invoice_status_histories`).

**Alternativa descartada:** calcular `vencida` al vuelo con un accessor sin
tocar la columna persistida. Se descarta porque el listado de Filament
necesita filtrar y ordenar por estado real en base de datos (índice sobre
`status`), no solo mostrar un badge calculado en memoria.

---

### 3. Estados de OC/factura: Enum PHP, no tabla configurable

**Decisión:** `PurchaseOrderStatus` e `InvoiceStatus` son enums PHP backed
string (`HasLabel`, `HasColor`, `HasIcon`), igual patrón que `TaskStatus`
(ver ADR-0006 §1).

**Alternativa descartada:** tabla configurable por organización, como
`project_statuses`. Se descarta por la misma razón que en ADR-0006: el flujo
de aprobación/pago tiene semántica fija de negocio (sella `approved_by`,
`payment_date`, dispara notificaciones), no es una taxonomía libre que varíe
por cliente.

---

### 4. Historial de estados dedicado para ambas entidades

**Decisión:** `purchase_order_status_histories` e `invoice_status_histories`,
mismo patrón que `submission_status_histories` (from/to, `changed_by`,
`comment`, timestamp), con un servicio `PurchaseOrderStateMachine` /
`InvoiceStateMachine` análogo a `SubmissionStateMachine`.

**Razón:** Principio de la sección 3 del `CLAUDE.md` — "todo registro
relevante es auditable". Aplica con más fuerza a datos financieros
(aprobación de OC, marcado de pago) que a otros flujos del sistema.

---

### 5. RBAC de finanzas más restrictivo que el resto del PMIS

**Decisión:** `tecnico` y `calidad` no tienen acceso al módulo de finanzas
(ni lectura). `supervisor` tiene solo lectura y puede marcar OC como
`recibida`, pero no puede crear, aprobar ni anular. `create`/`update`/aprobar/
pagar/anular quedan en `super_admin` e `ingeniero`. `delete`/`restore`/
`forceDelete` solo `super_admin`.

**Alternativa descartada:** replicar la matriz de `ProjectPolicy` (los 5
roles con `view`). Se descarta porque expondría montos y datos bancarios de
proveedores a roles de terreno (`tecnico`, `calidad`) sin necesidad
operativa — es información financiera sensible, no de ejecución de obra.

---

### 6. Integridad `type`/`client_id`/`supplier_id`: validación de aplicación, sin `CHECK` de BD

**Decisión:** La regla "`type=outgoing` ⇒ `client_id` requerido;
`type=incoming` ⇒ `supplier_id` requerido" se valida en el `FormRequest`/
schema de Filament y en el modelo, no con un `CHECK` constraint de base de
datos.

**Alternativa descartada:** además, `CHECK` constraint a nivel de BD. Se
descarta por ahora para no acoplar la migración a diferencias de sintaxis
entre MySQL y PostgreSQL (el proyecto soporta ambos motores) y porque toda
escritura pasa por Eloquent/Filament — no hay otra vía de entrada a estas
tablas. Queda como mejora futura si se detecta escritura fuera de la
aplicación (ej. import masivo directo a BD).

---

## Consecuencias

- Se agrega un campo (`code`) no contemplado en el documento original de
  REQ-0003 — es una ampliación menor de esquema, no un cambio de alcance
  funcional.
- El job programado diario para `vencida` requiere que el scheduler de
  Laravel esté corriendo (cron real en producción) — dependencia operativa
  nueva a documentar en el checklist de despliegue (`/release`).
- `tecnico` y `calidad` no podrán ver el estado de pago de una OC asociada a
  un proyecto en el que trabajan; si en el futuro se requiere visibilidad
  parcial (ej. solo el estado, sin montos), es un cambio de policy, no de
  esquema.
- El `CHECK` constraint descartado en la decisión 6 puede revisarse si se
  introduce alguna vía de escritura fuera de Filament (import Excel de
  facturas, por ejemplo).
