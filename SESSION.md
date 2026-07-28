# SESSION.md — Estado de sesión de trabajo

> Este archivo lo mantiene Claude Code. Se actualiza al final de cada sesión de trabajo
> y se lee al inicio de la siguiente, para no depender del historial de chat ni de
> copiar/pegar resúmenes a mano.

---

## Última actualización
2026-07-03

## Módulo / feature en curso
Ninguno — REQ-0002-E completamente cerrado y pusheado a rama `feat/req-0002-e`

## Estado actual

### Completado ✅
- REQ-0001 (Módulo de Solicitudes de Tableros) — cerrado, 23/23 tests en verde.
- REQ-0002-A (PMIS Core) — cerrado, commit `a95aab8`, 33/33 tests en verde.
- REQ-0002-B (Kanban + Gantt + Export) — cerrado, 42/42 tests en verde.
- **UX ActivityAccordion v3** — cerrado, 54/54 tests en verde.
- **REQ-0002-E (UX Gantt + Kanban + Widgets)** — ✅ CERRADO. Commit `19e933e` en rama `feat/req-0002-e`.

#### Resumen REQ-0002-E (finalizado en esta sesión):
**Diagrama Gantt con DHTMLX Community:**
- Reemplaza frappe-gantt → DHTMLX Gantt (MS Project-style, nativo en Community)
- Dark mode completo (25+ selectores `.dark .gantt_*`)
- Zoom via `gantt.ext.zoom` (Ctrl+rueda + botones +/-) sin auto-fit al arrastrar/editar
- Escalas: Día → Semana → Mes → Año (removido Trimestre)
- Columnas redimensionables (`grid_resize: true`, `resize: true` en schema)
- Modal edición tarea (título, descripción, inicio/término); estilo Filament light/dark; sin botón delete
- Barra progreso visible y NO-editable (`drag_progress: false`; weighted by task days + Kanban status)
- Fechas actividad auto-calculadas: `min(start_date de tareas)` / `max(due_date de tareas)`
- Dependencias persistidas en `TaskLink` (nueva migración + modelo)
- `ActivityStatus` convertido a accessor computado (se elimina columna de DB)
- N+1 prevenido: `modifyQueryUsing(fn($q) => $q->with('tasks'))` en ActivitiesRelationManager
- Tests Pest actualizados para ActivityStatus computado

**Archivos nuevos/modificados:**
- `app/Filament/Resources/ProjectResource/Pages/GanttChart.php` — `updateTaskDetails()` + auto-calc fechas actividad
- `resources/views/filament/.../gantt-chart.blade.php` — modal + zoom + escalas + CSS dark mode
- `app/Models/TaskLink.php` — modelo de dependencias (new)
- `database/migrations/2026_07_02_000001_create_task_links_table.php`
- `database/migrations/2026_07_02_000002_drop_status_from_activities_table.php`
- `app/Models/Activity.php` — accessor `getStatusAttribute()` + relación eager loading
- `app/Filament/Resources/ProjectResource/RelationManagers/ActivitiesRelationManager.php` — N+1 fix
- `database/factories/ActivityFactory.php` — removida columna status
- `tests/Feature/Projects/ActivityAccordionTest.php` — 3 tests nuevos para status computado
- `lang/es/projects.php` — claves: `scale_day`, `scale_year`, `modal_edit`, `modal_save`, `modal_cancel`

**Todo hecho, Pint limpio, branch pusheada. Usuario creará PR manualmente.**

### Diseñados — pendientes de implementar (orden sugerido)
1. **REQ-0003** — Finanzas básicas: Proveedores, OC, Facturas
2. **REQ-0005** — Estados de Pago / EPs (requiere REQ-0003)
3. **REQ-0002-C** — KPI Dashboard (widgets en ViewProject)
4. **REQ-0002-D** — Portal externo (token + Livewire + Reverb)
5. **REQ-0004** — Control de Cambios

### Decisiones de diseño cerradas (vigentes)
- **frappe-gantt** (MIT) reemplaza Alpine/CSS custom — flechas de dependencia nativas para el roadmap.
- **`user_settings` table** para preferencias por usuario (sin `organization_id`).
- **`FileUpload` + `users.avatar_url`** para avatar; medialibrary diferido.
- **`Filament::serving()`** en AppServiceProvider para aplicar color dinámico por usuario.
- **ChartWidget** nativo de Filament (Chart.js) para todos los widgets nuevos — sin paquetes extra.
- **Enums Filament** para TaskStatus y TaskPriority (label + color + icono).
- **Solo open-source** — regla primordial del proyecto.
- **Jerarquía PM:** Proyecto → Actividad → Tarea.
- **Códigos legibles:** formato `TAB-001-T042`.

## Decisiones pendientes
Ninguna.

## Próximo paso concreto
Levantar el servidor local (`composer run dev`) y verificar en navegador:
1. Dashboard con los 4 widgets nuevos.
2. `/admin/settings` — paleta de colores y toggles de notificación.
3. Gantt: zoom (Día / Semana / Mes / Trimestre) cambia sin re-render Livewire; toggle Solo lectura desactiva drag; barras frappe-gantt visibles sin errores de consola.
4. Kanban con badge de prioridad y avatares múltiples.

Luego elegir el siguiente REQ del backlog (REQ-0003 Finanzas es prerrequisito de REQ-0005).

---

## Historial de sesiones anteriores

<details>
<summary>2026-06-28 — UX ActivityAccordion v3: drag-drop + refresh + notificaciones</summary>

Fix refresh DOM: propiedad `$refreshCount` incrementada en cada mutación; blade observa
con `$wire.$watch('refreshCount', ...)` y re-inicializa SortableJS vía `$nextTick`.
Notificaciones Filament en todas las acciones CRUD. Drag-drop de actividades (handle en
header de section, filter botones) y tareas (columna grip + Group con data-tasks-container).
Migration `tasks.order`. SortableJS CDN via @assets. 54/54 tests en verde.

</details>

<details>
<summary>2026-06-28 — UX ActivityAccordion v2: refactor a Filament builder components</summary>

ActivityAccordion reescrito sin Blade manual: Section collapsible nativo de Filament 5,
TextEntry para filas de tarea, SchemaActions para botones inline. Patrón clave:
acciones como métodos InteractsWithActions + `(clone $this->action)->arguments([...])` en
headerActions para pre-enlazar contexto. Blade reducido a 4 líneas. Tests: 54/54 en verde.
Widgets dashboard (StatsOverview + TableWidget) y perfil via `->profile()` también cerrados.

</details>

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
