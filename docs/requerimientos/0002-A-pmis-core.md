# REQ-0002-A — PMIS Core: Usuarios, Clientes, Proyectos, Actividades, Tareas

- **Estado:** Aprobado — listo para implementar con `/ingeniero`
- **Prioridad:** Inmediata — núcleo del que dependen REQ-0002-B, C y D
- **Origen:** Diseño arquitectónico aprobado 2026-06-23

---

## Resumen

Implementar el núcleo del PMIS: gestión de usuarios internos desde super_admin, clientes, proyectos (con conversión desde solicitud aprobada), actividades y tareas con jerarquía Proyecto → Actividad → Tarea, miembros de equipo por proyecto, comentarios polimórficos y RBAC completo.

---

## Decisiones de diseño aprobadas

- **Estados de tarea:** Enum PHP/Filament con label, color e icono. No tabla configurable.
- **Estados de proyecto:** Tabla `project_statuses` configurable (los de proyecto sí son configurables por el negocio).
- **Identificadores legibles de tarea:** código tipo `TAB-001-T042` (prefijo de proyecto + correlativo de actividad + correlativo de tarea).
- **Conversión SR → Proyecto:** acción custom "Crear Proyecto" disponible en (a) notificación enviada al aprobar la solicitud y (b) columna de acciones en el listado de solicitudes aprobadas.
- **`program_id`:** columna nullable en `projects` desde el inicio; módulo Programs diferido.
- **Comentarios:** reutilizar `parallax/filament-comments` ya instalado (morphMany polimórfico).
- **Adjuntos:** reutilizar modelo `Attachment` polimórfico ya implementado.

---

## Modelo de datos

### `clients`
| Columna | Tipo | Notas |
|---|---|---|
| id | ulid PK | |
| organization_id | FK organizations | multi-tenant-ready |
| name | string | |
| rut | string nullable | RUT empresa |
| email | string nullable | |
| phone | string nullable | |
| address | string nullable | |
| contact_name | string nullable | Nombre del contacto principal |
| notes | text nullable | |
| timestamps | — | |
| deleted_at | — | soft delete |

### `projects`
| Columna | Tipo | Notas |
|---|---|---|
| id | ulid PK | |
| organization_id | FK organizations | |
| client_id | FK clients | |
| program_id | FK programs nullable | diferido, columna existe |
| submission_request_id | FK submission_requests nullable | origen de la solicitud aprobada |
| code | string unique | legible, ej: `TAB-2026-001` |
| name | string | |
| description | text nullable | |
| status_id | FK project_statuses | |
| priority | string | enum: baja/media/alta/critica |
| manager_id | FK users nullable | jefe de proyecto |
| color | string nullable | hex color para UI |
| start_date | date nullable | |
| end_date | date nullable | |
| completed_at | timestamp nullable | |
| timestamps | — | |
| deleted_at | — | soft delete |

### `project_statuses`
| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| organization_id | FK organizations | |
| name | string | |
| color | string | hex |
| order | integer | orden de visualización |
| is_completed | boolean | marca estado final |
| timestamps | — | |

Semilla inicial: `Planificación → En Ejecución → En Pausa → Completado → Cancelado`

### `project_members`
| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| project_id | FK projects | |
| user_id | FK users | |
| role | string | nombre del rol Shield asignado en este proyecto |
| timestamps | — | |

### `activities`
| Columna | Tipo | Notas |
|---|---|---|
| id | ulid PK | |
| organization_id | FK organizations | |
| project_id | FK projects | |
| name | string | |
| description | text nullable | |
| order | integer | orden dentro del proyecto |
| status | string | enum PHP: pendiente/en_progreso/completada |
| start_date | date nullable | |
| end_date | date nullable | |
| timestamps | — | |
| deleted_at | — | soft delete |

### `tasks`
| Columna | Tipo | Notas |
|---|---|---|
| id | ulid PK | |
| organization_id | FK organizations | |
| activity_id | FK activities | |
| parent_task_id | FK tasks nullable | subtareas |
| code | string | legible, ej: `TAB-001-T042` |
| name | string | |
| description | text nullable | |
| status | string | Enum TaskStatus |
| priority | string | Enum TaskPriority |
| start_date | date nullable | |
| due_date | date nullable | |
| completed_at | timestamp nullable | |
| estimated_hours | decimal nullable | |
| actual_hours | decimal nullable | |
| timestamps | — | |
| deleted_at | — | soft delete |

### `task_user` (pivot)
| Columna | Tipo | Notas |
|---|---|---|
| task_id | FK tasks | |
| user_id | FK users | |
| role | string | assignee / reviewer |
| timestamps | — | |

---

## Enums PHP/Filament

### `TaskStatus`
| Valor | Label | Color Filament | Icono |
|---|---|---|---|
| `pendiente` | Pendiente | gray | heroicon-o-clock |
| `en_progreso` | En Progreso | info | heroicon-o-play |
| `en_revision` | En Revisión | warning | heroicon-o-eye |
| `completada` | Completada | success | heroicon-o-check-circle |
| `bloqueada` | Bloqueada | danger | heroicon-o-x-circle |

### `TaskPriority`
| Valor | Label | Color | Icono |
|---|---|---|---|
| `baja` | Baja | gray | heroicon-o-arrow-down |
| `media` | Media | info | heroicon-o-minus |
| `alta` | Alta | warning | heroicon-o-arrow-up |
| `critica` | Crítica | danger | heroicon-o-fire |

### `ProjectPriority`
Igual que TaskPriority.

---

## Recursos Filament

| Recurso / Página | Descripción |
|---|---|
| `UserResource` | CRUD usuarios internos; asignación de rol Shield; activar/desactivar; solo `super_admin` |
| `ClientResource` | CRUD clientes con tab de proyectos asociados |
| `ProjectResource` | Listado con filtros; CreateAction desde solicitud aprobada; vista detalle con tabs |
| `ProjectStatusResource` | Configuración de estados de proyecto; acceso restringido a `super_admin` |
| `ActivityResource` | Gestión de actividades dentro de un proyecto (relation manager en ProjectResource) |
| `TaskResource` | Gestión de tareas dentro de una actividad (relation manager en ActivityResource) |

**Página especial:** `ProjectDetailPage` — vista 360° con tabs: Actividades+Tareas / Equipo / Adjuntos / Comentarios

---

## Flujo conversión SubmissionRequest → Project

1. Al cambiar estado a `aprobada`, se dispara notificación in-app al `super_admin` e `ingeniero` con botón "Crear Proyecto".
2. La Action `CreateProjectFromSubmission` también aparece en el listado de solicitudes con estado `aprobada`.
3. Al ejecutarla, abre un modal con campos pre-rellenados desde la solicitud (nombre, cliente, descripción) — el usuario confirma o ajusta antes de guardar.
4. Se crea el `Project` con `submission_request_id` apuntando a la solicitud de origen.

---

## Matriz de permisos REQ-0002-A

| Recurso | super_admin | ingeniero | supervisor | tecnico | calidad |
|---|---|---|---|---|---|
| Usuarios | CRUD | — | — | — | — |
| Clientes | CRUD | R | R | — | — |
| Project Statuses | CRUD | — | — | — | — |
| Proyectos | CRUD | CRU | R | R | R |
| Actividades | CRUD | CRUD | CRU | R | R |
| Tareas | CRUD | CRUD | CRUD | CRUD† | R |
| Miembros proyecto | CRUD | CRU | R | — | — |

† Solo tareas asignadas al usuario.

---

## Criterios de aceptación

1. `super_admin` puede crear, editar y desactivar usuarios internos y asignarles roles Shield.
2. Se puede crear un `Client` y asociarle múltiples proyectos.
3. Al aprobar una `SubmissionRequest`, se envía notificación con acción "Crear Proyecto"; la misma acción aparece en el listado de solicitudes aprobadas.
4. El modal de creación de proyecto pre-rellena datos desde la solicitud; al confirmar, el proyecto queda con `submission_request_id` correcto.
5. Dentro de un proyecto se pueden crear Actividades y dentro de cada Actividad, Tareas.
6. Las Tareas tienen código legible (`TAB-001-T042`), estados con color/icono (Enum), prioridad y múltiples responsables.
7. Comentarios funcionan en Tareas y Actividades usando `parallax/filament-comments`.
8. Adjuntos polimórficos funcionan en Proyectos, Actividades y Tareas.
9. Toda entidad tiene `organization_id`.
10. Tests Pest cubren: creación de proyecto desde solicitud, jerarquía actividad→tarea, transiciones de estado, aislamiento por `organization_id`, permisos por rol.

---

## Notas para el implementador

- Generar `code` del proyecto con un observer o en el `boot()` del modelo: `{PREFIJO}-{AÑO}-{CORRELATIVO}`.
- El código de tarea se genera al crear: `{prefijo_proyecto}-{nro_actividad}-T{correlativo}`.
- Reutilizar el trait `HasAttachments` y `HasComments` que ya existen en el proyecto.
- No activar el modo `teams` de Shield — single-tenant operativo, multi-tenant-ready en diseño.
