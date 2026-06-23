# SESSION.md — Estado de sesión de trabajo

> Este archivo lo mantiene Claude Code. Se actualiza al final de cada sesión de trabajo
> y se lee al inicio de la siguiente, para no depender del historial de chat ni de
> copiar/pegar resúmenes a mano.

---

## Última actualización
2026-06-23

## Módulo / feature en curso
REQ-0002-A — PMIS Core: Usuarios, Clientes, Proyectos, Actividades, Tareas

## Estado actual

### Completado ✅
- REQ-0001 (Módulo de Solicitudes de Tableros) — cerrado, 23/23 tests en verde.
- Diseño arquitectónico PMIS Core aprobado (sesión 2026-06-22 y 2026-06-23).
- Documentos de requerimientos creados:
  - `docs/requerimientos/0002-A-pmis-core.md`
  - `docs/requerimientos/0002-B-kanban-gantt.md`
  - `docs/requerimientos/0002-C-kpi-dashboard.md`
  - `docs/requerimientos/0002-D-portal-externo.md`
  - `docs/requerimientos/0003-finanzas.md`
  - `docs/requerimientos/0004-control-cambios.md`
- ADR registrado: `docs/adr/0006-arquitectura-pmis-core.md`

### Decisiones de diseño cerradas
- **Enums Filament** para TaskStatus y TaskPriority (label + color + icono).
- **mokhosh/filament-kanban** para Kanban drag-and-drop.
- **frappe-gantt** para Gantt (open-source; regla primordial del proyecto).
- **Códigos legibles de tarea:** formato `TAB-001-T042`.
- **SR → Proyecto:** notificación + Action en lista de aprobadas; modal semi-automático.
- **`program_id` nullable** en `projects`, módulo Programs diferido.
- **OC:** monto total + descripción libre (sin líneas de ítem).
- **Jerarquía:** Proyecto → Actividad → Tarea.
- **Portal externo:** token + dashboard Livewire + Reverb tiempo real.
- **KPIs** a nivel de proyecto: widgets en `ProjectDetailPage`.
- **Solo open-source** — regla primordial del proyecto.
- **FAT:** diferido a su propio REQ (por ahora sin diseño detallado).

## Decisiones pendientes
Ninguna — todo está aprobado, listo para implementar.

## Próximo paso concreto
Usar `/ingeniero` para implementar **REQ-0002-A** (`docs/requerimientos/0002-A-pmis-core.md`).

Orden de entregables dentro del PR de REQ-0002-A:
1. Migraciones: `clients`, `project_statuses`, `projects`, `project_members`, `activities`, `tasks`, `task_user`
2. Enums: `TaskStatus`, `TaskPriority`, `ProjectPriority`
3. Modelos con relaciones, scopes de tenant (`organization_id`) y traits reutilizables
4. Factories y seeders
5. Recursos Filament: `UserResource`, `ClientResource`, `ProjectResource`, `ProjectStatusResource`, y relation managers para actividades y tareas
6. Action `CreateProjectFromSubmission` + notificación al aprobar SubmissionRequest
7. Policies por rol
8. Tests Pest (criterios de aceptación del REQ)
9. Pint + Larastan limpios

---

## Historial de sesiones anteriores

<details>
<summary>2026-06-22/23 — Diseño arquitectónico PMIS Core (roles /arquitecto)</summary>

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
