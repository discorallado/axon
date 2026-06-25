# REQ-0002 — PMIS Núcleo: RBAC, Clientes, Proyectos y Bridge desde Solicitud

- **Estado:** Diseño aprobado — pendiente de implementación con `/ingeniero`
- **Prioridad:** Segundo módulo tras REQ-0001
- **Objetivo de negocio:** Permitir que una solicitud aceptada se convierta en un
  proyecto gestionable, con jerarquía `Client → Project → Phase`, equipo
  múltiple, y RBAC operativo con roles reales.

---

## Resumen

Tres piezas que se implementan juntas porque se bloquean entre sí:

1. **RBAC operativo**: `filament-shield` ya instalado pero sin permisos generados
   ni roles seeded. Se genera, seedea y configura el `UserResource`.
2. **Clientes y Proyectos**: modelos + recursos Filament con FSM de proyecto y
   fases (`Phase`) vinculadas a los tramos de pago del REQ-0003.
3. **Bridge**: acción "Convertir en Proyecto" en la bandeja de solicitudes;
   la solicitud queda en estado `convertida` con link al proyecto creado.

---

## Modelo de datos

### `clients`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | ULID PK | |
| `organization_id` | FK → organizations | Global scope |
| `name` | string | |
| `rut` | string nullable | RUT empresa (es-CL) |
| `contact_name` | string nullable | |
| `contact_email` | string nullable | |
| `contact_phone` | string nullable | |
| `address` | string nullable | |
| `notes` | text nullable | |
| `active` | boolean default true | |
| timestamps | softDeletes | |

Relaciones: `hasMany(Project)`, `morphMany(Attachment)`.

---

### `projects`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | ULID PK | |
| `organization_id` | FK → organizations | |
| `client_id` | FK → clients | |
| `submission_request_id` | FK nullable → submission_requests | Bridge |
| `supplier_id` | FK nullable → suppliers | Subcontratista principal |
| `reference_code` | string unique | Auto-gen: `PRY-2026-001` |
| `name` | string | |
| `description` | text nullable | |
| `status` | string | Ver FSM |
| `priority` | enum: `baja/media/alta/urgente` | PHP Enum |
| `start_date` | date nullable | |
| `end_date` | date nullable | Plazo comprometido |
| `actual_end_date` | date nullable | |
| `location` | string nullable | Nombre de la obra |
| `budget` | decimal(12,2) nullable | Presupuesto referencial |
| `contract_amount` | decimal(12,2) nullable | Monto contrato con proveedor |
| `assigned_to` | FK → users | Jefe de proyecto |
| `notes` | text nullable | |
| timestamps | softDeletes | |

Relaciones: `belongsTo(Client)`, `belongsTo(User, 'assigned_to')`,
`belongsTo(SubmissionRequest)`, `belongsTo(Supplier)`,
`hasMany(ProjectStatusHistory)`, `hasMany(Phase)`,
`hasMany(ProjectMember)`, `hasMany(PurchaseOrder)`,
`hasMany(PaymentCertificate)`, `morphMany(Attachment)`,
`hasFilamentComments`.

---

### `project_status_histories`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | PK bigint | |
| `project_id` | FK → projects | |
| `from_status` | string nullable | null si es el primer estado |
| `to_status` | string | |
| `comment` | text nullable | |
| `changed_by` | FK nullable → users | |
| timestamps | | |

---

### `phases` (Fases del proyecto)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | ULID PK | |
| `organization_id` | FK → organizations | |
| `project_id` | FK → projects | |
| `name` | string | Ej. "Ingeniería", "Fabricación", "Instalación" |
| `sort_order` | integer | |
| `status` | enum: `pendiente/en_curso/completada` | PHP Enum |
| `start_date` | date nullable | |
| `end_date` | date nullable | |
| timestamps | | |

Relaciones: `belongsTo(Project)`, `hasOne(PaymentTranche)`.

> Al completar una fase (transición `en_curso → completada`), se puede
> disparar la creación del EP correspondiente al tramo vinculado.

---

### `project_members` (Equipo del proyecto)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | PK bigint | |
| `project_id` | FK → projects | |
| `user_id` | FK → users | |
| `role_label` | string nullable | Rol descriptivo en el proyecto |
| timestamps | unique(project_id, user_id) | |

Relaciones: `belongsTo(Project)`, `belongsTo(User)`.

---

## Ajuste a `submission_requests`

- Agregar columna `project_id` FK nullable → projects.
- Agregar estado `convertida` al PHP Enum `SubmissionStatus` (terminal,
  junto a `rechazada`).

---

## Máquinas de estado

### Proyecto

```
borrador ──► activo ──► en_pausa ──► activo  (ciclo)
               │
               ├──► completado
               └──► cancelado

en_pausa ──► cancelado
```

| Transición | Quién puede |
|---|---|
| `borrador → activo` | super_admin, supervisor |
| `activo → en_pausa` | super_admin, supervisor |
| `activo → completado` | super_admin, supervisor |
| `activo → cancelado` | super_admin |
| `en_pausa → activo` | super_admin, supervisor |
| `en_pausa → cancelado` | super_admin |

### Fase

```
pendiente ──► en_curso ──► completada
```

Al completar: dispara acción para generar EP del tramo vinculado (si existe).

---

## Recursos Filament

| Recurso | Páginas | Descripción |
|---|---|---|
| `UserResource` | List, Create, Edit | Gestión de usuarios + asignar roles. Solo `super_admin` puede acceder. |
| `ClientResource` | List, Create, Edit, View | CRUD de clientes; infolist con proyectos vinculados. |
| `ProjectResource` | List, Create, Edit, View | CRUD de proyectos; panel lateral con fases, miembros, OC/EPs; acción "Cambiar estado"; adjuntos y comentarios. |
| `SubmissionRequestResource` (ya existe) | + acción nueva | Acción **"Convertir en Proyecto"** visible solo cuando `status = aprobada`. Abre modal pre-rellenado. Al confirmar: crea `Project`, vincula `submission_request_id`, transiciona solicitud a `convertida`. La solicitud muestra badge "Convertida" con link al proyecto. |

---

## Bridge — Acción "Convertir en Proyecto"

El modal pre-rellena desde la solicitud:
- `name` ← `project_name`
- `location` ← `installation_location`
- `start_date` ← `desired_delivery_date`
- `client_id` ← select (obligatorio, la solicitud no tiene cliente aún)
- `assigned_to` ← select de usuarios internos
- `reference_code` ← auto-generado al crear

---

## Matriz de permisos

| Recurso | super_admin | supervisor | ingeniero | tecnico | calidad |
|---|:---:|:---:|:---:|:---:|:---:|
| Usuarios | CRUD | — | — | — | — |
| Clientes | CRUD | CRUD | Ver | — | — |
| Proyectos | CRUD + estados | CRUD + estados | CRUD | Ver | Ver |
| Fases | CRUD | CRUD | CRUD | Ver | Ver |
| Miembros de proyecto | CRUD | CRUD | Ver | — | — |
| Solicitudes | CRUD | CRUD + aprobar | Ver | — | — |

---

## Decisiones técnicas

### RBAC con Shield

Correr `shield:generate --all` para generar permisos por convención de recursos.
Seeder de roles y permisos base al inicio de REQ-0002.
Los casos especiales (supervisor puede cambiar estado pero no gestionar usuarios)
se manejan con policies puntuales sobre acciones Filament.

---

## Supuestos y riesgos

1. **Shield no está seeded**: el seeder de roles es lo primero que se implementa;
   sin él, nada del RBAC funciona.
2. **Enum `SubmissionStatus`**: agregar `convertida` requiere una migración de
   columna. Verificar si es `varchar` o `enum` nativo en BD para evitar `ALTER`
   lento.
3. **`supplier_id` en Project**: asume un subcontratista principal por proyecto.
   Si a futuro un proyecto tiene múltiples subcontratistas con contratos
   separados, se migrará a una tabla `project_contracts`.

---

## Criterios de aceptación

1. Un `super_admin` puede crear usuarios y asignarles roles; un `supervisor`
   no puede acceder a `UserResource`.
2. Se puede crear un cliente y asignarle proyectos.
3. Un proyecto tiene fases ordenables; al completar una fase queda registrado.
4. Desde una solicitud en estado `aprobada` se puede ejecutar "Convertir en
   Proyecto"; la solicitud queda con badge "Convertida" y link al proyecto.
5. Un proyecto tiene múltiples miembros del equipo.
6. Tests Pest cubren: acceso por rol, CRUD de cliente/proyecto, bridge
   solicitud→proyecto, aislamiento por `organization_id`.
