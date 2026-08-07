# SESSION.md — Estado de sesión de trabajo

> Este archivo lo mantiene Claude Code. Se actualiza al final de cada sesión de trabajo
> y se lee al inicio de la siguiente, para no depender del historial de chat ni de
> copiar/pegar resúmenes a mano.

---

## Última actualización
2026-08-06

## Módulo / feature en curso
REQ-0003 — Finanzas (Proveedores, OC, Facturas). **Implementado** en rama
`feat/req-0003-finanzas`, PR #8 abierto hacia `main`, pendiente de `/revisor`
y merge.

## Estado actual

### REQ-0003 — Implementado, PR #8 abierto (pendiente de revisión y merge)

Basado en `docs/requerimientos/0003-finanzas.md` (aprobado 2026-06-23, alcance
y criterios de aceptación) + patrones existentes del repo (`HasOrganizationScope`,
`HasAttachments`, `HasFilamentComments`, generación de código vía Observer como
en `Project`/`Task`, máquina de estados con `ALLOWED_TRANSITIONS` + tabla de
historial como `SubmissionStateMachine`). Diseño cerrado en ADR-0011,
implementado íntegro en un commit (`a779ed7` en la rama `feat/req-0003-finanzas`).

**Modelo de datos:**
- `suppliers` — igual patrón que `Client` (ulid, organization_id, datos de
  contacto + bancarios, notes, soft deletes, attachments, comments).
- `purchase_orders` — ulid; `supplier_id` (restrict), `project_id` nullable
  (null on delete); `code` autogenerado `OC-{año}-{seq}` único por org;
  `number` (folio real, libre); `date`; `currency` (enum CLP/USD/EUR);
  `amount_net`/`tax_amount`/`amount_total` `decimal(15,2) unsigned`; `status`
  (enum PHP `PurchaseOrderStatus`); `approved_by`/`approved_at`; soft deletes.
- `purchase_order_status_histories` — mismo patrón que
  `submission_status_histories`.
- `invoices` — ulid; `type` (enum `incoming`/`outgoing`); `client_id` y
  `supplier_id` nullable (uno u otro según `type`); `project_id` y
  `purchase_order_id` nullable; `code` autogenerado `FC-{año}-{seq}`; `number`
  (folio real); `date`/`due_date`; mismos campos de moneda que OC; `status`
  (enum PHP `InvoiceStatus`); `payment_date`; soft deletes.
- `invoice_status_histories` — mismo patrón.
- Ambas entidades usan `HasOrganizationScope, HasAttachments,
  HasFilamentComments, HasUlids, SoftDeletes`.

**Máquinas de estado:**
- `PurchaseOrderStatus`: `borrador → emitida → recibida`, o `→ anulada` desde
  `borrador`/`emitida`. `recibida` y `anulada` terminales. Aprobar
  (`borrador→emitida`) sella `approved_by`/`approved_at`; roles: aprobar y
  anular = `super_admin, ingeniero`; marcar recibida = + `supervisor`.
- `InvoiceStatus`: `pendiente → pagada` (sella `payment_date`), `pendiente →
  vencida` (automático, ver Q2), `pendiente/vencida → anulada`. `pagada` y
  `anulada` terminales. Marcar pagada/anular: `super_admin, ingeniero`.

**Recursos Filament:** nuevo grupo de navegación "Finanzas" — `SupplierResource`
(List/Create/Edit, como `ClientResource`), `PurchaseOrderResource` y
`InvoiceResource` (List/Create/Edit/View, con línea de tiempo de estado como
`ViewSubmissionRequest`, filtros por proyecto/estado/proveedor-cliente).

**Matriz de permisos:** view = `super_admin, ingeniero, supervisor`;
create/update/aprobar/pagar/anular = `super_admin, ingeniero`; marcar recibida
= + `supervisor`; delete/restore/forceDelete = solo `super_admin`. `tecnico` y
`calidad` sin acceso (más restrictivo que `ProjectPolicy`, dato financiero).

**Preguntas Q1–Q6 resueltas** adoptando las opciones recomendadas (el usuario
no dio respuestas puntuales, se avanzó con el diseño). Decisiones y su porqué
documentadas en `docs/adr/0011-finanzas-proveedores-oc-facturas.md`:
1. **Q1:** sí, se agrega `code` autogenerado además de `number` (folio real).
2. **Q2:** comando programado diario que persiste `vencida` en BD.
3. **Q3:** enum PHP para `PurchaseOrderStatus`/`InvoiceStatus`.
4. **Q4:** sí, historial dedicado para ambas entidades.
5. **Q5:** `tecnico`/`calidad` sin acceso; `supervisor` solo lectura (+
   marcar recibida).
6. **Q6:** validación en FormRequest/Filament, sin `CHECK` de BD por ahora.

**Riesgos/supuestos identificados:** multimoneda sin tipo de cambio histórico
(fuera de alcance MVP, afecta reportes agregados futuros); sin líneas de ítem
(monto total + descripción libre, confirmado); sin integración con
facturación electrónica SII; `purchase_order_id` opcional en `invoices`
(compras menores sin OC); `program_id` no referenciado (diferido, igual que en
`Project`).

**Implementación (rol `/ingeniero`, commit `a779ed7`):** 53 archivos — 5
migraciones, 4 enums, 5 modelos + 2 observers, 2 state machines, comando
programado `invoices:mark-overdue` (`routes/console.php`, `dailyAt('01:00')`),
3 policies, 3 recursos Filament (grupo "Finanzas": `SupplierResource`,
`PurchaseOrderResource`, `InvoiceResource`) con adjuntos polimórficos
(subir/eliminar PDF) y timeline de estado en las vistas, 3 factories, 4
archivos de lang, 27 tests Pest nuevos (código autogenerado, aislamiento
`organization_id`, transiciones de estado y roles, comando de vencidas, RBAC,
smoke test de render de las páginas Filament). Suite completa: **108/108
verde**, Pint limpio, Larastan nivel 1 sin errores.

**Nota de proceso:** el primer commit se hizo por error directo sobre `main`;
se corrigió moviéndolo a la rama `feat/req-0003-finanzas` (`git reset --hard
origin/main` en local, ya que no se había pusheado) antes de abrir el PR.

**Efecto colateral fuera de REQ-0003:** al hacer `git checkout main` + `merge
--ff-only` más temprano en la sesión, el fast-forward borró del working tree
los archivos de `.ddev/` que habían quedado como untracked-pero-coincidentes
(`config.yaml`, addon phpMyAdmin, `.bash_aliases`) — no afectó a los
contenedores Docker (siguieron corriendo), pero rompió el CLI local de `ddev`
hasta que se restauraron desde el historial de git (`git show cbfb361:<path>`).
Quedaron restaurados como archivos locales no trackeados, tal como corresponde.

### Completado ✅
- REQ-0001 (Módulo de Solicitudes de Tableros) — cerrado.
- REQ-0002-A (PMIS Core) — cerrado, commit `a95aab8`.
- REQ-0002-B (Kanban + Gantt + Export) — cerrado.
- UX ActivityAccordion v3 — cerrado.
- REQ-0002-E (UX Gantt DHTMLX + Kanban + Widgets) — cerrado, mergeado a `main` vía PR #6 (`d0c56c8`).
- QA post-merge REQ-0002-E (fix tests Gantt DHTMLX + Larastan + limpieza `.ddev`) —
  cerrado, mergeado a `main` vía PR #7 (`a1c9e16`).

**Suite completa: 81/81 tests en verde · Pint limpio · Larastan nivel 1 sin errores.**

### Diseñados — pendientes de implementar (orden sugerido)
1. **REQ-0005** — Estados de Pago / EPs (requiere REQ-0003 — ya implementado,
   ver arriba, falta mergear PR #8)
2. **REQ-0002-C** — KPI Dashboard (widgets en ViewProject)
3. **REQ-0002-D** — Portal externo (token + Livewire + Reverb)
4. **REQ-0004** — Control de Cambios

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
Ninguna de diseño. Falta el "vamos" explícito del usuario para empezar a
escribir código de REQ-0003 (el CLAUDE.md exige visto bueno antes de
implementar, aunque el diseño ya esté cerrado con las opciones recomendadas).

## Próximo paso concreto
Correr rol `/revisor` sobre el PR #8
(https://github.com/discorallado/axon/pull/8, rama `feat/req-0003-finanzas`)
antes de mergear: revisar seguridad, N+1 en los recursos Filament (relaciones
`supplier`/`project`/`client`/`purchaseOrder` en tablas y listados), fugas de
`organization_id`, permisos faltantes y cobertura de tests. Con el visto
bueno, mergear a `main` (mismo método usado en PR #6/#7: merge commit normal)
y luego decidir si se sigue con REQ-0005 (Estados de Pago, ahora desbloqueado)
u otro requerimiento.

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
<summary>2026-08-04/06 — QA post-merge de REQ-0002-E: fix tests Gantt DHTMLX, Larastan, limpieza .ddev</summary>

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
- Un commit intermedio (`cbfb361`, "update") volvió a trackear por error
  varios archivos de `.ddev/` (addon phpMyAdmin, `config.yaml`,
  `.bash_aliases`): el `.gitignore` previo solo ignoraba un archivo suelto
  (`.ddev/.ddev-docker-compose-full.yaml`), no la carpeta completa. Corregido
  en `26eca83`: `.gitignore` ahora ignora `.ddev/` completo (cada entorno de
  trabajo tiene su propia config local de DDEV y no debe versionarse nunca;
  `.claude/` sí se sincroniza).

PR #7 abierto y mergeado a `main` (merge commit `a1c9e16`, mismo método —
merge commit normal— usado en PR #6). Suite verificada en verde (81/81) antes
del merge. Commits en `fix/tests-gantt-dhtmlx`: `22d7a63`, `a57cdbe`,
`0e79271`, `9927389`, `26eca83`.

De paso (fuera del repo): se configuró la statusline del usuario
(`~/.claude/statusline.sh`) para mostrar contexto/cuota 5h/cuota semanal con
barras de bloques y color.

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
