# SESSION.md — Estado de sesión de trabajo

> Este archivo lo mantiene Claude Code. Se actualiza al final de cada sesión de trabajo
> y se lee al inicio de la siguiente, para no depender del historial de chat ni de
> copiar/pegar resúmenes a mano.

---

## Última actualización
2026-08-03

## Módulo / feature en curso
**REQ-0006 — Módulo de Inspección.** En etapa de **diseño** (`/arquitecto`).
Documentación cerrada; **no se escribió código todavía**. Faltan 3 decisiones
del usuario para pasar a `/ingeniero`.

## Estado actual

### Completado ✅
- REQ-0001 (Solicitudes de Tableros) — cerrado, 23/23 tests en verde.
- REQ-0002-A (PMIS Core) — cerrado, commit `a95aab8`, 33/33 tests en verde.
- REQ-0002-B (Kanban + Gantt + Export) — cerrado, 42/42 tests en verde.
- UX ActivityAccordion v3 — cerrado, 54/54 tests en verde.
- REQ-0002-E (UX Gantt DHTMLX + Kanban + Widgets) — cerrado y **ya mergeado a
  `main`** vía PR #6 (`d0c56c8`, 28-jul-2026), más cleanup post-merge `25f8fc4`
  (elimina el plugin Flowforge no usado + fixes de Pint).
- **REQ-0006 — diseño y documentación** (esta sesión):
  - Análisis completo de las 2 planillas de `docs/base/` (hojas, fórmulas,
    validaciones, rangos nombrados) — no de la vista superficial de las celdas.
  - Auditoría del prototipo hermano `/home/ubuntu/inspector`, que **ya implementó
    este dominio** (23 ADRs, ~103 tests). 13 ideas adoptadas, 4 descartadas.
  - `docs/requerimientos/0006-inspeccion.md` — requerimiento completo.
  - `docs/adr/0011-inspeccion-jerarquia-programa-tablero-y-avance-ponderado.md`.

### A mitad de camino 🚧
Nada implementado. REQ-0006 está en diseño aprobado parcialmente: D1 y D3
cerradas por el usuario, D8/D10/0006-E abiertas.

### Decisiones tomadas esta sesión
- **D1 = B (usuario):** jerarquía
  `Client → Program (contrato IN-17248) → Project (TABLERO) → Activity (HITO) → Task (SUB-ACTIVIDAD)`.
  Cada hoja de la planilla es un proyecto; la hoja `Resumen` es el programa.
  ⇒ **se activa `Program`**, hoy diferido (`projects.program_id` existe como
  `unsignedBigInteger` sin FK → migrar a `foreignUlid`).
  ⇒ campos del tablero (tag, fabricante, revisión) van en extensión 1:1
  `board_profiles`, **no** en `projects`.
- **D3 = A (usuario):** `weightedProgress()` **en paralelo**;
  `Activity::completionPercentage()` binario queda intacto y REQ-0002-B/E no
  cambian de comportamiento.
- **D2:** el peso del hito se **deriva** de sus tareas (`SUMIF(<>"N/A")`), no es
  columna. Reforzado por el hallazgo del prototipo: backfill parejo (=1) **no**
  preserva el avance global.
- **D4:** **una sola tabla `observations`** con `observation_type_id`
  configurable (flags `requires_severity`/`requires_recipient`) — cubre
  observación, NC, consulta, verificación, información y sugerencia. Reemplaza el
  diseño previo de dos tablas separadas.
- **D5:** `TaskStatus` gana el caso `no_aplica` + `progressFactor()`
  (`pendiente 0` · `en_progreso 0.5` · `en_revision 0.75` · `bloqueada 0.5` ·
  `completada 1` · `no_aplica null` = excluido).
- **ControlCambios** de la planilla **no** se implementa acá: es REQ-0004.

### Datos clave extraídos de las planillas (no volver a derivarlos)
- 6 tableros del contrato `IN-17248`, fabricante `CMF`, 8 hitos × 40
  sub-actividades. Nombres idénticos entre tableros, **pesos y fechas plan
  distintos** por tablero.
- Peso del hito = `SUMIF(estados <> "N/A"; pesos)`; avance hito =
  `Σ(peso×valor)/Σpeso`; avance tablero (`G7`) = `Σ(peso_hito×avance_hito)/Σpeso_hito`.
- Fechas plan y real están **a nivel de hito**, no de sub-actividad.
- Estado del tablero (`Resumen!D6`): ≥100% Completado · ≥99% Por liberar · >0%
  En proceso · else No iniciado.
- Responsables `CSE`/`CMF`/`CLIENTE` son **empresas, no usuarios** ⇒ catálogo
  `responsible_parties`, compartido con el "Remite a" de las consultas
  (`CSE_ING`, `CSE_IND`, `S.E.`, `INTEG.`, `IEC`).
- Actas `YYYYMMDD-NNN`; observaciones `OBS-001`; consultas `INFO-001`.
- `Resumen Subsanadas` es una **vista derivada**, no una entidad (es un tab).
- Cifras para el test de paridad: `TP = 0,364942…` · `CLIMA_A = 0,448369…` ·
  promedio del contrato (`Resumen!C12`) `= 0,443723…`.

### Ideas adoptadas del prototipo `/home/ubuntu/inspector`
Su ADR 0009 documenta que portó `Actividad`/`Tarea` **desde axon commit
`25f8fc4`** para integrarse de vuelta. Adoptadas (I1–I13 en el requerimiento):
entidad única de observación con tipo configurable · pivot visita↔tablero
explícita (permite "visité y no encontré nada") · máquina de estados
configurable en BD (`state_transitions` + guard en `saving()`) · **checklist IEC
61439 con patrón librería → plantilla → ejecución snapshot** (idea nueva, cubre
el hito 6 "Pruebas FAT") · `code` autogenerado `TP-1.1` recalculado al reordenar ·
criterio "no participa, no cuenta como 0%" · avance cacheado por Observer ·
`SoftDeletes` + `restrictOnDelete` en lo histórico · catálogo de especialidad ·
estado general de visita derivado · sin Kanban de terceros (probaron Flowforge y
lo revirtieron — coincide con el cleanup `25f8fc4` de axon).

**Descartado del prototipo:** catálogos en BD para todos los estados (el
CLAUDE.md de axon pide enums para lo fijo), ids autoincrementales (axon usa
ULID), modelos en español, y máquina de estados forzada para `Task` (rompería el
arrastre libre del Kanban).

### Archivos tocados esta sesión
- `docs/requerimientos/0006-inspeccion.md` (nuevo, reescrito 3 veces al ir
  cerrando decisiones).
- `docs/adr/0011-inspeccion-jerarquia-programa-tablero-y-avance-ponderado.md` (nuevo).
- `docs/catalogo-y-mvp.md` (§7 anotado con REQ-0006).
- `docs/requerimientos/0004-control-cambios.md` (nota de origen: hoja
  `ControlCambios`).
- `SESSION.md`.
- **Sin cambios en código.** El working tree tiene modificados solo assets
  compilados (`public/js/filament/*`, `public/css/*`, `.ddev/*`, `composer.lock`)
  de un build/install local previo — no son cambios de esta sesión.

## Decisiones pendientes
1. **D1 revalidación** — se le señaló al usuario que el prototipo resolvió esto
   con **`Tablero` como nivel intermedio** (`Proyecto → Tablero → Actividad →
   Tarea`), no como "tablero = proyecto", y que ese camino ya está validado con
   ~103 tests. El usuario aún no respondió a esa observación. Si confirma B, se
   sigue con B.
2. **D8** — ponderación 0/0.5/1: ¿en el enum `TaskStatus::progressFactor()`
   (recomendado), como catálogo `estados_avance` en BD (como el prototipo), o
   híbrido (enum + settings para los factores)?
3. **D10** — ¿se permite **reabrir** una observación (propuesto) o se crea una
   nueva (criterio del prototipo, para no perder el historial de cierre)?
4. **0006-E** — ¿entra el checklist IEC 61439?
5. ¿**Portar** la lógica del prototipo (calculador, guard, snapshot) o
   reimplementar leyéndolo como especificación?
6. ¿Importar los datos reales de `IN-17248` (6 tableros con avance actual, 4
   actas, 13 observaciones, 23 consultas) como seeder?
7. ¿Notificación automática de observaciones vencidas (command programado) o solo
   indicador visual?

## Próximo paso concreto
Obtener del usuario las respuestas a D1-revalidación, D8 y D10 (puntos 1–3 de
"Decisiones pendientes"). Con eso, arrancar **PR 0006-A** en rol `/ingeniero`:
crear la migración `create_programs_table` (`id` ULID, `organization_id`,
`client_id`, `manager_id`, `code` único por org, `name`, `description`,
`contract_ref`, `start_date`, `end_date`, `status_id` → `project_statuses`,
`softDeletes`) y la migración que convierte `projects.program_id` de
`unsignedBigInteger` a `foreignUlid` con `constrained()->nullOnDelete()` +
índice; luego modelo `Program` con `HasOrganizationScope`, `HasUlids`,
`HasAttachments`, `HasFilamentComments`, su factory y `ProgramResource`.

El plan de PRs completo (0006-A a 0006-E) está al final de
`docs/requerimientos/0006-inspeccion.md`.


## Historial de sesiones anteriores

<details>
<summary>2026-07-03 — Cierre REQ-0002-E (Gantt DHTMLX, dark mode, widgets, settings)</summary>

REQ-0002-E cerrado: Gantt DHTMLX Community reemplaza frappe-gantt (dark mode con 25+
selectores, zoom vía `gantt.ext.zoom`, escalas Día→Semana→Mes→Año, columnas
redimensionables, modal de edición de tarea estilo Filament, barra de progreso visible y
no editable). Fechas de actividad auto-calculadas `min(start_date)`/`max(due_date)` de sus
tareas. Dependencias persistidas en `TaskLink` (migración nueva). `ActivityStatus`
convertido a accessor computado (se eliminó la columna de DB). N+1 prevenido con
`modifyQueryUsing(...->with('tasks'))` en ActivitiesRelationManager. Widgets de dashboard
(ChartWidget nativo) y `/admin/settings` (paleta de colores + toggles) cerrados.
Commit `19e933e` en rama `feat/req-0002-e`.

**Cerrado después, fuera de sesión:** la rama se mergeó a `main` en PR #6 (`d0c56c8`,
28-jul-2026) + commit `fdc28f1`, y luego el cleanup `25f8fc4` (elimina el plugin
Flowforge no usado, fixes de Pint). La verificación manual en navegador que quedó como
"próximo paso" en esa sesión no está registrada — se asume cubierta por el merge.

Backlog de REQs diseñados y pendientes de implementar al cierre de esa sesión:
REQ-0003 (Finanzas: proveedores/OC/facturas) → REQ-0005 (Estados de Pago, depende de
0003) → REQ-0002-C (KPI Dashboard) → REQ-0002-D (Portal externo con token + Reverb) →
REQ-0004 (Control de Cambios).

Decisiones vigentes de esa etapa: `user_settings` para preferencias por usuario (sin
`organization_id`) · `FileUpload` + `users.avatar_url` para avatar (medialibrary
diferido) · `Filament::serving()` para color dinámico por usuario · ChartWidget nativo
(Chart.js) sin paquetes extra · enums Filament para TaskStatus/TaskPriority · solo
open-source · códigos legibles `TAB-001-T042`.

</details>

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
