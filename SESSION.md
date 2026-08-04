# SESSION.md — Estado de sesión de trabajo

> Este archivo lo mantiene Claude Code. Se actualiza al final de cada sesión de trabajo
> y se lee al inicio de la siguiente, para no depender del historial de chat ni de
> copiar/pegar resúmenes a mano.

---

## Última actualización
2026-08-04

## Módulo / feature en curso
Ninguno — deuda de QA saldada. Rama `fix/tests-gantt-dhtmlx` lista para PR.

## Estado actual

### Completado ✅
- REQ-0001 (Módulo de Solicitudes de Tableros) — cerrado.
- REQ-0002-A (PMIS Core) — cerrado, commit `a95aab8`.
- REQ-0002-B (Kanban + Gantt + Export) — cerrado.
- UX ActivityAccordion v3 — cerrado.
- REQ-0002-E (UX Gantt DHTMLX + Kanban + Widgets) — cerrado, mergeado a `main` vía PR #6 (`d0c56c8`).

**Suite completa: 81/81 tests en verde · Pint limpio · Larastan nivel 1 sin errores.**

#### Sesión 2026-08-04 — QA post-merge de REQ-0002-E
Tras el merge del PR #6 la suite estaba en 68/81 (13 fallos). Ninguno era del
código de producción: eran tests que quedaron contra la API de frappe-gantt.

- `UxEnhancementsTest`: 12 llamadas a `actingAs()` como función global (no
  existe; la convención del repo es `$this->actingAs()`).
- `getGanttTasks()` ya no existe → `getGanttData()`, que devuelve
  `['data' => [...], 'links' => [...]]`. Las filas usan `start_date`/`end_date`
  (DHTMLX), no `start`/`end`; los ids de tarea van sin prefijo `task-` y no
  hay `custom_class` (era de frappe).
- `updateActivityDates` se eliminó a propósito en REQ-0002-E. El test se
  reemplazó por uno que verifica que las fechas de actividad se derivan de
  `min(start_date)` / `max(due_date)` de sus tareas.
- **Larastan quedó configurado por primera vez** (`phpstan.neon`, nivel 1). El
  paquete estaba instalado desde siempre pero sin config, así que el paso
  "Larastan limpio" del CLAUDE.md nunca se había podido ejecutar.
- Se eliminaron `FormTemplateFactory`, `FormSectionFactory` y
  `FormQuestionFactory`: apuntaban a modelos y a un enum inexistentes y no
  tenían ninguna referencia en el repo. Impedían pasar incluso el nivel 0.
- `.ddev` dejó de trackearse (ya estaba en `.gitignore`).

Commits en `fix/tests-gantt-dhtmlx`: `22d7a63`, `a57cdbe`, `0e79271`.

### Diseñados — pendientes de implementar (orden sugerido)
1. **REQ-0003** — Finanzas básicas: Proveedores, OC, Facturas
2. **REQ-0005** — Estados de Pago / EPs (requiere REQ-0003)
3. **REQ-0002-C** — KPI Dashboard (widgets en ViewProject)
4. **REQ-0002-D** — Portal externo (token + Livewire + Reverb)
5. **REQ-0004** — Control de Cambios

### Decisiones de diseño cerradas (vigentes)
- **DHTMLX Gantt (Community, GPL)** — reemplazó a frappe-gantt en REQ-0002-E.
  Escalas Día / Semana / Mes / Año, dependencias persistidas en `TaskLink`,
  fechas de actividad derivadas de sus tareas (no editables directamente).
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
Abrir PR de `fix/tests-gantt-dhtmlx` → `main` y mergearlo. Después, arrancar
REQ-0003 (Finanzas: Proveedores, OC, Facturas) en rol `/arquitecto` a partir de
`docs/requerimientos/0003-finanzas.md`, presentando el diseño antes de escribir
código.

## Cómo correr la suite (importante)
Los tests **no corren en el host**: el PHP de WSL no tiene `pdo_mysql` y el host
`db` sólo existe dentro del contenedor. Siempre vía DDEV:

```bash
ddev exec ./vendor/bin/pest
ddev exec ./vendor/bin/pint
ddev exec ./vendor/bin/phpstan analyse --memory-limit=1G
```

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
