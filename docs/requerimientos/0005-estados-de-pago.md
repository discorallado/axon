# REQ-0005 — Estados de Pago (EPs) a Subcontratistas

- **Estado:** Diseño aprobado — pendiente de implementación con `/ingeniero`
- **Depende de:** REQ-0002-A (proyectos), REQ-0003 (suppliers, invoices)
- **Origen:** Diseño arquitectónico aprobado 2026-06-23 (sesión actual)

---

## Resumen

La empresa emite **Estados de Pago (EPs)** a sus subcontratistas, certificando
el avance de una fase de obra y autorizando el cobro. El subcontratista luego
emite una factura respaldada en el EP aprobado.

El flujo es completamente de salida (accounts payable). La empresa NO recibe
EPs — los genera y entrega.

```
Proyecto define Fases de obra
    └── cada Fase tiene un Tramo de pago (monto fijo)
          └── al completar la Fase → se genera un EP
                └── EP tiene cuadro de partidas descriptivo
                └── EP genera PDF con plantilla Blade
                └── Proveedor emite Factura respaldada en el EP
                      └── Se registra el pago
```

---

## Decisiones de diseño

- **Phases son distintas de Activities.** `Activity` (REQ-0002-A) es una
  agrupación de tareas para planificación. `Phase` es una etapa de alto nivel
  de la obra (Ingeniería, Fabricación, Instalación, Commissioning) que ancla
  un tramo de pago al subcontratista. Conviven en el mismo proyecto sin
  conflicto.
- **Monto fijo por tramo.** El valor del EP viene del tramo definido, no de
  un % de avance calculado. Las partidas en el EP son descriptivas.
- **PDF via Blade + dompdf.** Template fijo con logo y datos de empresa desde
  `config/company.php`. Almacenado como `Attachment` polimórfico (tag: `ep_pdf`).
- **Un tramo → un EP.** Relación one-to-one entre `PaymentTranche` y
  `PaymentCertificate`.

---

## Modelo de datos

### `phases` (Fases de obra del proyecto)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | ULID PK | |
| `organization_id` | FK → organizations | |
| `project_id` | FK → projects | |
| `name` | string | Ej. "Ingeniería", "Fabricación", "Instalación" |
| `sort_order` | integer | |
| `status` | enum PHP: `pendiente/en_curso/completada` | |
| `start_date` | date nullable | |
| `end_date` | date nullable | |
| timestamps | | |

Relaciones: `belongsTo(Project)`, `hasOne(PaymentTranche)`.

FSM Phase: `pendiente → en_curso → completada`
Al completar: acción disponible para generar el EP del tramo vinculado.

---

### `payment_tranches` (Tramos de pago vinculados a fases)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | ULID PK | |
| `organization_id` | FK → organizations | |
| `project_id` | FK → projects | |
| `phase_id` | FK nullable → phases | Fase que origina el tramo |
| `tranche_number` | integer | Correlativo por proyecto |
| `label` | string | Ej. "Hito 1 — Montaje", "Tramo 2" |
| `description` | text nullable | Alcance comprometido |
| `amount` | decimal(12,2) | Monto fijo del tramo |
| `due_date` | date nullable | Fecha comprometida |
| `completed_at` | datetime nullable | Cuándo se certificó |
| timestamps | | |

Relaciones: `belongsTo(Project)`, `belongsTo(Phase)`, `hasOne(PaymentCertificate)`.

---

### `payment_certificates` (EPs emitidos)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | ULID PK | |
| `organization_id` | FK → organizations | |
| `project_id` | FK → projects | |
| `payment_tranche_id` | FK → payment_tranches | |
| `supplier_id` | FK → suppliers | Subcontratista receptor |
| `ep_number` | string | Correlativo por proyecto (EP-001…) |
| `period_label` | string nullable | Ej. "Julio 2026", "Tramo 2" |
| `status` | enum PHP | Ver FSM |
| `amount_net` | decimal(12,2) | Del tramo (editable antes de emitir) |
| `tax_rate` | decimal(5,2) default 19.00 | IVA % |
| `tax_amount` | decimal(12,2) | |
| `amount_total` | decimal(12,2) | |
| `currency` | string default `CLP` | |
| `issued_date` | date nullable | |
| `due_date` | date nullable | |
| `paid_at` | datetime nullable | |
| `invoice_id` | FK nullable → invoices | Factura del proveedor respaldada en este EP |
| `notes` | text nullable | |
| timestamps | softDeletes | |

Relaciones: `belongsTo(Project)`, `belongsTo(PaymentTranche)`,
`belongsTo(Supplier)`, `hasMany(PaymentCertificateItem)`,
`belongsTo(Invoice)`, `morphMany(Attachment)`.

FSM Estado de Pago:
```
borrador ──► emitido ──► conformado ──► pagado
    │            │
    └────────────┴──► anulado
```

| Transición | Descripción | Quién puede |
|---|---|---|
| `borrador → emitido` | Genera PDF y entrega al subcontratista | super_admin, supervisor |
| `emitido → conformado` | Subcontratista da conformidad | super_admin, supervisor |
| `emitido → anulado` | Anular antes de conformidad | super_admin |
| `conformado → pagado` | Registrar pago efectuado | super_admin |
| `conformado → anulado` | Excepcional | super_admin |

---

### `payment_certificate_items` (Cuadro de partidas)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | PK bigint | |
| `payment_certificate_id` | FK → payment_certificates | |
| `sort_order` | integer | |
| `description` | string | Descripción de la partida |
| `unit` | string nullable | Ej. "m²", "gl", "un" |
| `quantity` | decimal(10,2) nullable | |
| `unit_price` | decimal(12,2) nullable | |
| `amount` | decimal(12,2) | Monto de la partida |
| timestamps | | |

> Suma de `amount` debe cuadrar con `amount_net` del EP antes de emitir.

---

### Ajuste a `invoices` (REQ-0003)

Agregar columna `payment_certificate_id FK nullable → payment_certificates`
para que una factura pueda respaldarse en un EP además de en una OC.

---

### Ajuste a `projects`

Agregar columnas:
- `supplier_id FK nullable → suppliers` — subcontratista principal de la obra
- `contract_amount decimal(12,2) nullable` — monto total del contrato con ese proveedor

---

## Resumen financiero del proyecto (calculado)

Widget en `ViewProject`:

| Campo | Cálculo |
|---|---|
| Monto contrato | `projects.contract_amount` |
| Total EPs emitidos | SUM(`payment_certificates.amount_total`) donde status ≠ `anulado` |
| Total EPs pagados | SUM donde status = `pagado` |
| Saldo pendiente | Contrato − Total pagado |

---

## Recursos Filament

| Recurso | Descripción |
|---|---|
| `PhaseResource` (o RelationManager en ProjectResource) | CRUD de fases; acción "Completar fase" que dispara creación del EP |
| `PaymentCertificateResource` | List, Create, Edit, View; editor de partidas inline (Repeater); acción "Generar PDF"; acción "Registrar conformidad"; acción "Registrar pago" |
| `ProjectResource` → ViewProject | Widget resumen financiero; RelationManager de Fases + Tramos |

---

## Matriz de permisos

| Recurso | super_admin | supervisor | ingeniero | tecnico | calidad |
|---|:---:|:---:|:---:|:---:|:---:|
| Fases del proyecto | CRUD | CRUD | CRUD | Ver | Ver |
| Tramos de pago | CRUD | CRUD | Ver | — | — |
| Estados de Pago (EP) | CRUD + todas las transiciones | Crear + emitir + conformar | Ver | — | — |

---

## Criterios de aceptación

1. Un proyecto puede tener fases ordenadas; cada fase puede vincularse a un
   tramo de pago con monto fijo.
2. Al completar una fase, se dispara la opción de generar el EP del tramo.
3. El EP tiene cuadro de partidas editable (Repeater Filament) y validación de
   que la suma cuadra con el monto del tramo antes de emitir.
4. Se puede generar el PDF del EP (Blade + dompdf) y queda guardado como
   adjunto polimórfico del EP.
5. El EP transiciona correctamente por su FSM; cada transición queda auditada.
6. La vista del proyecto muestra el resumen financiero acumulado.
7. Tests Pest cubren: ciclo completo EP (borrador → emitido → conformado →
   pagado), validación de suma de partidas, generación de PDF, aislamiento
   por `organization_id`.