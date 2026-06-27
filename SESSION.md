# SESSION.md — Estado de sesión de trabajo

> Este archivo lo mantiene Claude Code. Se actualiza al final de cada sesión de trabajo
> y se lee al inicio de la siguiente, para no depender del historial de chat ni de
> copiar/pegar resúmenes a mano.

---

## Última actualización
2026-06-23

## Módulo / feature en curso
REQ-0002-B — Kanban + Gantt + Export — **cerrado**

## Estado actual

### Completado ✅
- REQ-0001 (Módulo de Solicitudes de Tableros) — cerrado, 23/23 tests en verde.
- REQ-0002-A (PMIS Core) — cerrado, commit `a95aab8`, 33/33 tests en verde.
- REQ-0002-B (Kanban + Gantt + Export) — **cerrado**, 42/42 tests en verde.
  - `KanbanBoard` page: Livewire + SortableJS CDN, filtros por actividad/prioridad, `#[Renderless]` en updateTaskStatus.
  - `GanttChart` page: frappe-gantt CDN + Alpine `wire:ignore`, zoom día/semana/mes.
  - `TasksExport`: maatwebsite/excel, xlsx + csv desde ViewProject.
  - `mokhosh/filament-kanban` descartado (incompatible con Filament 5) → Kanban custom.
  - `pestphp/pest-plugin-livewire` instalado como dev-dependency.
  - ADR: `docs/adr/0007-kanban-gantt-export.md`.
  - 65 archivos: migraciones, modelos, enums, observers, resources Filament, policies, factories, seeders, tests.
  - Bug fixes: `completionPercentage()` cualifica `tasks.status` en JOIN ambiguo.
  - Constraint `projects.code` cambiada a `unique(['organization_id', 'code'])` (multi-tenant-ready).
  - `db_test` creado y migrado; todos los tests usan `RefreshDatabase`.

### Diseñados — pendientes de implementar (orden sugerido)
1. **REQ-0002-B** — Kanban + Gantt + Export CSV (`docs/requerimientos/0002-B-kanban-gantt.md`)
2. **REQ-0003** — Finanzas básicas: Proveedores, OC, Facturas (`docs/requerimientos/0003-finanzas.md`)
3. **REQ-0005** — Estados de Pago / EPs a subcontratistas (`docs/requerimientos/0005-estados-de-pago.md`) ← **nuevo, diseñado en esta sesión**
4. **REQ-0002-C** — KPI Dashboard (`docs/requerimientos/0002-C-kpi-dashboard.md`)
5. **REQ-0002-D** — Portal externo (`docs/requerimientos/0002-D-portal-externo.md`)
6. **REQ-0004** — Control de Cambios (`docs/requerimientos/0004-control-cambios.md`)

> ⚠️ REQ-0005 depende de REQ-0003 (necesita `suppliers` e `invoices`).

### Decisiones de diseño cerradas (vigentes)
- **Enums Filament** para TaskStatus y TaskPriority (label + color + icono).
- **mokhosh/filament-kanban** para Kanban drag-and-drop.
- **frappe-gantt** para Gantt (open-source; regla primordial del proyecto).
- **Solo open-source** — regla primordial del proyecto.
- **Códigos legibles de tarea:** formato `TAB-001-T042`.
- **SR → Proyecto:** notificación + Action en lista de aprobadas; modal semi-automático.
- **`program_id` nullable** en `projects`, módulo Programs diferido.
- **OC:** monto total + descripción libre (sin líneas de ítem).
- **Jerarquía PM:** Proyecto → Actividad → Tarea.
- **Phases (fases de obra):** concepto distinto de Activities; anclan tramos de pago al subcontratista (REQ-0005).
- **EPs:** monto fijo por tramo; partidas descriptivas; PDF via Blade + `barryvdh/laravel-dompdf`.
- **Portal externo:** token + dashboard Livewire + Reverb tiempo real (REQ-0002-D, diferido).
- **KPIs** a nivel de proyecto: widgets en `ViewProject` (REQ-0002-C, diferido).
- **FAT:** diferido a su propio REQ.

## Decisiones pendientes
Ninguna.

## Próximo paso concreto
Esperar instrucción del usuario. Opciones según backlog:
- **REQ-0003** (Finanzas: Proveedores + OC + Facturas) — prerrequisito obligatorio de REQ-0005.
- **REQ-0002-C** (KPI Dashboard) — widgets de estadísticas en ViewProject.
- **REQ-0005** (Estados de Pago / EPs) — requiere REQ-0003 implementado primero.

---

## Historial de sesiones anteriores

<details>
<summary>2026-06-23 — Implementación REQ-0002-A PMIS Core (/ingeniero)</summary>

Implementación completa del núcleo PMIS: clientes, proyectos, actividades, tareas.
65 archivos en commit a95aab8. 33/33 tests en verde. Pint limpio.
Fix: ambigüedad SQL en completionPercentage (tasks.status). Fix: unique constraint
projects.code cambiada a (organization_id, code). db_test creado para tests.

</details>

<details>
<summary>2026-06-22/23 — Diseño arquitectónico PMIS Core (rol /arquitecto)</summary>

Propuesta y aprobación de arquitectura para REQ-0002-A/B/C/D, REQ-0003, REQ-0004.
Decisiones clave: enum Filament para TaskStatus, mokhosh/filament-kanban, frappe-gantt (open-source),
códigos legibles TAB-001-T042, conversión SR→Proyecto con modal semi-automático,
program_id nullable diferido, portal externo con Reverb, KPIs a nivel proyecto, FAT diferido.
ADR: docs/adr/0006-arquitectura-pmis-core.md. Sin código implementado aún.

</details>

<details>
<summary>2026-06-22 — Fixes post-revisión REQ-0001 (cascade, transacción, adjuntos)</summary>

Fix #4: SubmissionRequestObserver::forceDeleting → forceDelete() en ítems.
Fix #7: DB::transaction() wrappea transition() + FilamentComment::create().
Fix #3: Acción delete_attachments con CheckboxList; policy deleteAttachment con matriz de roles.
Fix A1: IDs de adjuntos acotados a la solicitud y sus ítems (whereIn attachable_id).
Fix M2: dispatch('$refresh') tras eliminación para actualizar infolist.
Fix M1: Placeholder con "Esta solicitud no tiene adjuntos" cuando no hay opciones.
23/23 tests en verde, Pint limpio. ADR: docs/adr/0005-fixes-cascade-transaccion-adjuntos.md.

</details>

<details>
<summary>2026-06-19 — Adjuntos polimórficos, comentarios Parallax, máquina de estados</summary>

Migración de columnas de ruta a modelo Attachment polimórfico con tag. Instalación de
parallax/filament-comments con fix de subject_id para ULIDs. Reescritura completa de
ViewSubmissionRequest. Eliminación de dead code. Observers para cascade delete.
Máquina de estados reforzada con ALLOWED_TRANSITIONS y bloqueo de mismo estado.
13/13 tests en verde. Commit: 210ace1.

</details>

<details>
<summary>2026-06-18 — Mejoras de formulario y back-office (notificaciones, dark mode, acciones)</summary>

Implementadas: notificaciones sync, ActionGroup en back-office, wire:confirm, fix colores, dark mode Alpine.js, modo edición firmada, soft delete en SubmissionRequest. 13/13 tests en verde.

</details>

<details>
<summary>2026-06-18 — Rediseño multi-tablero (submission_items, modal wizard)</summary>

Implementada arquitectura multi-tablero: tabla submission_items, modal wizard
de 3 pasos con Filament Actions, PublicFormWizard reescrito, ViewSubmissionRequest
reescrito con RepeatableEntry. 13/13 tests en verde.

</details>

<details>
<summary>2026-06-17 — Refinamiento del PublicFormWizard (18 cambios UX/campos)</summary>

Aplicadas 18 modificaciones al formulario público: campos renombrados, Select múltiple,
lógica condicional, auto-cálculo de corriente, toggles. Pint limpio, 12/12 tests verde.

</details>
