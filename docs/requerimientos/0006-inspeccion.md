# REQ-0006 — Módulo de Inspección (integrado a Programa / Proyecto / Actividad / Tarea)

- **Estado:** En diseño (propuesto por /arquitecto — pendiente de visto bueno final)
- **Prioridad:** Alta — reemplaza dos planillas Excel en uso operativo hoy
- **Depende de:** REQ-0002-A (proyectos/actividades/tareas), REQ-0002-B y
  REQ-0002-E (Kanban y Gantt DHTMLX). Se relaciona con REQ-0001 (solicitudes) y
  REQ-0004 (control de cambios).
- **Origen:** Planillas reales en `docs/base/`:
  - `Seguimiento_Integracion_Tableros_REPROGRAMADO.xlsx`
  - `Control_Observaciones_IN17248.xlsx`
- **ADR:** [docs/adr/0011-inspeccion-jerarquia-programa-tablero-y-avance-ponderado.md](../adr/0011-inspeccion-jerarquia-programa-tablero-y-avance-ponderado.md)
- **Antecedente clave:** el prototipo `/home/ubuntu/inspector`
  (`Modules/Inspeccion/`) ya implementó este dominio completo — 23 ADRs, ~103
  tests Pest — explícitamente para portarse a Axon. Ver §"Aprendizajes del
  prototipo".

## Decisiones tomadas por el usuario en esta sesión

| # | Decisión | Efecto |
|---|---|---|
| **D1** | **Opción B**: `Tablero = Project`, contrato = `Program` | Obliga a implementar `Program`, hoy diferido |
| **D3** | **Opción A**: `weightedProgress()` **en paralelo** | `completionPercentage()` binario queda intacto; REQ-0002-B/E no cambian |

---

## Resumen

El equipo de inspección (ITO/Calidad) hace seguimiento a la fabricación de
tableros eléctricos en taller del fabricante, y registra visitas, observaciones,
no conformidades y consultas. Hoy eso vive en dos planillas Excel; este REQ lo
lleva a Axon reutilizando el núcleo PMIS.

---

## Análisis de las planillas (hechos extraídos de fórmulas y validaciones)

### `Seguimiento_Integracion_Tableros_REPROGRAMADO.xlsx`

- 6 tableros (`TP`, `T_G2`, `BUS_A`, `BUS_B`, `CLIMA_A`, `CLIMA_B`), una hoja
  cada uno, **todos del mismo contrato** ("REMODELACIÓN CENTRO DE DATOS MAGNUS
  1", OC/Contrato `IN-17248`, fabricante `CMF`).
- Misma plantilla de **8 hitos** y **40 sub-actividades**: 1. Armado de Tablero ·
  2. Montaje de protecciones · 3. Fabricación y montaje de barras · 4. Alambrado
  del tablero · 5. Rotulación · 6. Pruebas FAT · 7. Embalaje · 8. Despacho.
- Nombres de sub-actividad **idénticos entre tableros**, pero **pesos distintos**
  (`3.1` pesa 8 en TP y 4 en CLIMA_A) y **fechas plan distintas** (BUS_A/BUS_B
  corren ~2 semanas después).
- **Estados de sub-actividad → avance** (rango `AvanceTbl`, hoja `Config`):
  `Pendiente = 0` · `En proceso = 0.5` · `Completado = 1` · `N/A = excluido`.
- **El peso del hito es derivado, no capturado**:
  `D10 = SUMIF(estados <> "N/A"; pesos)`.
- **Avance del hito**: `Σ(peso_i × avance_i) / Σ(peso_i)` sobre las no-N/A.
- **Avance del tablero** (`G7`): `Σ(peso_hito × avance_hito) / Σ(peso_hito)`.
- **Fechas plan y real están a nivel de hito**, no de sub-actividad.
- **Responsable**: `CSE`, `CMF`, `CLIENTE` — son **empresas, no usuarios**.
- **Estado del tablero** (`Resumen!D6`): `≥100% Completado` · `≥99% Por liberar`
  · `>0% En proceso` · `else No iniciado`.
- `Resumen` consolida por tablero: avance, estado, plan inicio (hito 1), plan fin
  despacho (hito 8), real fin despacho, **NC abiertas** (`COUNTIFS` estado
  `<>Cerrada`), **cambios pendientes** (`Propuesto`).
- `NoConformidades`: `NC-001` autonumerado, fecha detección, tablero,
  **hito/actividad**, descripción, severidad (`Menor/Mayor/Crítica`), acción
  correctiva, estado (`Abierta/En análisis/En corrección/Cerrada`), responsable,
  fecha compromiso, fecha cierre, evidencia, **días abierta** calculado.
- `ControlCambios`: `CC-001`, estados `Propuesto/Aprobado/Rechazado/Implementado`.
  → **Ya cubierto por REQ-0004; no se duplica aquí.**

### `Control_Observaciones_IN17248.xlsx`

- `Registro Visitas`: **N° Acta autogenerado** `YYYYMMDD-NNN`, fecha, contadores
  derivados (obs. levantadas / pendientes / consultas) y notas de la visita.
  **Una visita cubre varios tableros a la vez.**
- `Observaciones`: `OBS-001`, fecha heredada del acta, tablero (lista +
  `General/Varios`), descripción, clasificación (`Obs. Crítica/Mayor/Menor`),
  fecha compromiso, estado (`P`/`OK`/`i`), fecha cierre, observación de cierre,
  **días abierta**.
- `Consultas`: `INFO-001`, clasificación (`Consulta/Verificación/Información/
  Sugerencia`), **Remite a** (`CSE_ING`, `CSE_IND`, `S.E.`, `INTEG.`, `IEC`, `—`),
  estado (`R`/`P`/`i`), fecha cierre, respuesta.
- `Resumen Subsanadas`: **vista derivada** (observaciones en `OK`) → tab, no tabla.
- El campo "Tablero" agrupa varios (`CLIMA_A / CLIMA_B`) → un hallazgo puede
  afectar **N tableros**.

---

## Aprendizajes del prototipo `inspector` (ideas que se adoptan)

El prototipo resolvió este mismo dominio y dejó decisiones ya validadas en
código y tests. Se adoptan las siguientes:

| # | Idea del prototipo | Fuente | Cómo se adopta aquí |
|---|---|---|---|
| **I1** | **Una sola entidad `Observacion`** que generaliza no conformidad, consulta al integrador y sugerencia vía `tipo_observacion_id` (catálogo con flag `requiere_severidad`), en vez de modelos separados | ADR 0001 | **Se adopta y reemplaza mi diseño anterior**, que separaba `findings` de `queries` en dos tablas. Las dos planillas son la misma entidad con distinto tipo. |
| **I2** | Catálogo `estados_avance` con `valor` (0/0.5/1) y **`excluye_calculo`** (N/A) en vez de hardcodear la ponderación | ADR 0001 | Se adopta el **concepto**; en Axon el peso vive en `TaskStatus::progressFactor()` (ver D8 — hay tensión con el enum existente). |
| **I3** | **Máquina de estados configurable en BD**: tabla `transiciones_estado_permitidas` + `TransicionEstadoGuard` + Observers en `saving()`, con los `Select` de Filament ya filtrados por transición válida (el guard es red de seguridad, no primera línea) | ADR 0001, 0010 | Se adopta para `Observacion`. Para `Task` se propone diferir (ver D9). |
| **I4** | **Pivot `tablero_visita` explícita**, no derivada de las observaciones — para poder registrar "visité este tablero y no encontré nada" | ADR 0001 | Se adopta (coincide con mi diseño). |
| **I5** | **Pruebas / checklist IEC 61439** con patrón **librería → plantilla → ejecución snapshot**: la ejecución **copia** el texto del ítem y su referencia normativa, para que el histórico no cambie si el catálogo maestro se edita después | ADR 0001 | **Idea nueva que no tenía.** Cubre el hito 6 "Pruebas FAT" y conecta con el dominio FAT del usuario. Se propone como **REQ-0006-C** aparte. |
| **I6** | `code` de tarea **autogenerado** `{tag}-{actividad.orden}.{tarea.orden}` (`TP-1.1`), recalculado en cascada al reordenar (drag-and-drop de tarea **y** de actividad), con `updateQuietly()` para no disparar el observer de estado | ADR 0023 | Se adopta. Mejor que derivar el número en render: queda persistido, ordenable y exportable. |
| **I7** | **Backfill de peso algebraicamente equivalente**: al introducir peso por actividad, `peso = Σ(pesos de sus tareas no excluidas)` — el peso parejo (=1) **NO** preserva el avance global, es un promedio simple entre actividades | ADR 0022 | Se adopta el hallazgo. Refuerza D2-A: el peso del hito debe **derivarse** de sus tareas, no fijarse a mano. |
| **I8** | Criterio **"no participa, no cuenta como 0%"**: una actividad sin peso computable se excluye del promedio en vez de arrastrarlo a la baja | ADR 0022 | Se adopta: es la semántica correcta de N/A en cascada. |
| **I9** | `avance_global` **cacheado** en el tablero + `avance_calculado_at`, recalculado por Observer en `saved()`/`deleted()`, para ordenar y filtrar en SQL sin N+1 | ADR 0001 | Se adopta (coincide con mi `progress_cached`). |
| **I10** | **`SoftDeletes` + `restrictOnDelete`** en todo lo histórico (observaciones, actas, actividades, tareas): nada de cascada física sobre registros de calidad | ADR 0010 §2.6 | Se adopta. Fue un fix de `/revisor` allá, acá entra desde el diseño. |
| **I11** | **Catálogo `especialidad`** en la observación (Eléctrico / Mecánico / Control / Documentación / HSE / Otro) | ADR 0001 | Se adopta — no lo tenía y es filtro natural para el ITO. |
| **I12** | **Nada de Kanban de terceros**: se probó `flowforge` y se revirtió a tabla agrupada nativa de Filament | ADR 0005 → 0008 | Se adopta. Coincide con el commit `25f8fc4` de axon, que justamente eliminó el plugin Flowforge sin usar. |
| **I13** | **Estado general de visita derivado**: `Sin Observaciones` / `Todo Cerrado` / `Con Pendientes` / `Pendientes Críticos` | ADR 0001 (`CalculadorEstadoVisita`) | Se adopta — reemplaza mis contadores sueltos por un estado legible. |

**Ideas del prototipo que NO se adoptan** (y por qué):

- **Catálogos en BD para todos los estados, sin enums PHP.** Ese principio es del
  CLAUDE.md del prototipo, no del de Axon, que dice explícitamente "Enums de PHP
  para estados/tipos fijos; tablas para los configurables". Axon ya tiene
  `TaskStatus`/`TaskPriority` como enums acoplados a Kanban, Gantt y Filament.
  Se mantiene el enum y se configuran solo los catálogos que sí varían.
- **Ids autoincrementales** (ADR 0010 los eligió por consistencia interna del
  módulo). Axon usa ULID en todo el núcleo → ULID acá.
- **Nombres en español para modelos** (`Actividad`, `Tarea`). Axon ya tiene
  `Activity`/`Task` en inglés; se mantiene inglés en código y español en UI.
- **Máquina de estados de `Task` con secuencia forzada** (ADR 0010 §2.3: sin salto
  directo a Completada). Rompería el Kanban de arrastre libre de REQ-0002-B.

---

## Mapeo a la jerarquía PMIS — decisión D1-B

```
Client                         cliente del contrato
 └─ Program   ← CONTRATO       IN-17248 · "Remodelación Centro de Datos Magnus 1"
     └─ Project ← TABLERO      TP, T_G2, BUS_A, BUS_B, CLIMA_A, CLIMA_B   (×6)
         └─ Activity ← HITO    1. Armado … 8. Despacho                    (×8)
             └─ Task ← SUB-ACTIVIDAD   1.1, 1.2 …                         (×40)
```

Cada hoja de la planilla de seguimiento **es literalmente un proyecto**, y la
hoja `Resumen` **es el programa**. Las visitas y observaciones cuelgan del
**programa** (una visita recorre varios tableros = varios proyectos).

### Lo que D1-B exige y hay que asumir

1. **Implementar `Program`**, hoy diferido. `projects.program_id` ya existe pero
   como `unsignedBigInteger` sin FK — hay que migrarlo a `foreignUlid` con
   `constrained()->nullOnDelete()`.
2. **6 proyectos por contrato**: cliente, miembros, fechas y estado se
   administran seis veces. Mitigación: acción "Crear tableros del contrato" que
   genera los N proyectos + sus 48 actividades desde plantilla en un paso.
3. **Vistas consolidadas a nivel de programa** (Resumen, Gantt multi-tablero,
   listado de observaciones): son **nuevas**, no se heredan de las páginas de
   proyecto existentes, que son mono-proyecto.
4. **Campos propios del tablero** (`tag`, fabricante, revisión, ítem de
   solicitud) NO se agregan a `projects` para no contaminar la tabla núcleo: van
   en una **extensión 1:1 `board_profiles`**.

---

## Modelo de datos propuesto

Tablas nuevas: `id` ULID, `organization_id` (FK indexado + Global Scope),
`timestamps`, `deleted_at` donde se indique.

### A. `programs` — el contrato (entidad diferida que este REQ activa)

`id`, `organization_id`, `client_id` (FK cascade), `manager_id` (FK users
nullable), `code` (`IN-17248`), `name`, `description`, `contract_ref`,
`start_date`, `end_date`, `status_id` (FK `project_statuses`, reutiliza el
catálogo existente), `deleted_at`
Único `(organization_id, code)`

Relaciones: `belongsTo Client`, `hasMany Project`, `hasMany InspectionVisit`,
`hasMany Observation`, `morphMany Attachment`, `morphMany FilamentComment`.

Migración adicional: `projects.program_id` → `foreignUlid` + FK
`nullOnDelete` + índice.

### B. `board_profiles` — extensión 1:1 del proyecto-tablero

`id`, `organization_id`, `project_id` (FK **unique**, cascade),
`submission_item_id` (FK `submission_items` nullable — trazabilidad REQ-0001),
`work_template_id` (FK nullable), `tag` (`TP`), `manufacturer` (`CMF`),
`revision`, `progress_cached` (decimal 5,4 nullable), `progress_calculated_at`,
`order`
Único `(organization_id, project_id)` · Índice `(program_id vía project)`

> El `tag` también queda en `projects.code` para que el código legible
> (`TAB-001-T042`) siga funcionando; `board_profiles.tag` es el TAG corto de la
> planilla. **Decisión menor abierta**: si prefieres, se colapsa a solo
> `projects.code`.

### C. Cambios sobre tablas existentes (migraciones aditivas)

#### `activities` (+2 columnas)
| Columna | Tipo | Motivo |
|---|---|---|
| `actual_start` | date nullable | "Real Inicio" del hito |
| `actual_end` | date nullable | "Real Fin" del hito |

`start_date`/`end_date` existentes = plan inicio/fin. **Peso y avance del hito
son accessors derivados, no columnas** (D2-A, reforzado por I7).

#### `tasks` (+2 columnas)
| Columna | Tipo | Motivo |
|---|---|---|
| `weight` | decimal(6,2) **default 1.00** | peso de la sub-actividad |
| `responsible_party_id` | FK `responsible_parties` nullable | `CSE`/`CMF`/`CLIENTE` son empresas; convive con `assignees` (usuarios) |

`description` cubre la columna "Observaciones" de la planilla. Las fechas reales
de tarea no se agregan: las reales viven en el hito, como en la planilla.

#### `App\Enums\TaskStatus` (+1 caso)
Se agrega `NoAplica = 'no_aplica'` (label "N/A", `gray`, `heroicon-o-minus-circle`)
y `progressFactor(): ?float`:

| Estado | Factor |
|---|---|
| `pendiente` | 0.0 |
| `en_progreso` | 0.5 ← "En proceso" |
| `en_revision` | 0.75 (propuesto; no existe en la planilla) |
| `bloqueada` | 0.5 |
| `completada` | 1.0 |
| `no_aplica` | `null` → **excluido de numerador y denominador** |

> ⚠️ `no_aplica` debe excluirse del Kanban y de `Activity::getStatusAttribute()`,
> o una actividad con tareas N/A nunca llegaría a "Completada".

#### `tasks.code` autogenerado (I6)
Formato `{tag}-{activity.order}.{task.order}` → `TP-1.1`. Método único
`Task::generateCode()`; recálculo en cascada al crear, insertar y reordenar
tareas **y** actividades, con `updateQuietly()`. Se deja de editar a mano.

### D. Catálogos configurables

#### `responsible_parties` — responsables y destinatarios
`id`, `organization_id`, `code` (`CSE`, `CMF`, `CLIENTE`, `CSE_ING`, `CSE_IND`,
`S.E.`, `INTEG.`, `IEC`), `name`, `kind` (`responsable`|`destinatario`|`ambos`),
`color`, `order`, `is_active` · Único `(organization_id, code)`

#### `observation_types` — tipos de hallazgo (I1)
`id`, `organization_id`, `code`, `name`, `requires_severity` (bool),
`requires_recipient` (bool), `color`, `icon`, `order`, `is_active`
Semilla: `Observación a Subsanar` (severidad ✔) · `No Conformidad`
(severidad ✔) · `Consulta` (destinatario ✔) · `Verificación` (destinatario ✔) ·
`Información` · `Sugerencia`

#### `specialties` — especialidad (I11)
`id`, `organization_id`, `name`, `color`, `order`, `is_active`
Semilla: `Eléctrico`, `Mecánico`, `Control`, `Documentación`, `HSE`, `Otro`

#### Plantillas de trabajo (genéricas, no solo tableros)
- `work_templates`: `name`, `description`, `is_default`, `is_active`, `deleted_at`
- `work_template_activities`: `work_template_id` (cascade), `name`, `order`
- `work_template_tasks`: `work_template_activity_id` (cascade), `name`,
  `default_weight` decimal(6,2), `default_party_id` (FK nullable), `order`

Un servicio clona la plantilla en `Activity` + `Task` reales. Reutilizable por
cualquier proyecto del PMIS.

### E. Dominio de inspección

#### `inspection_visits` — actas de visita
`id`, `organization_id`, `program_id` (FK cascade), `act_number`
(`20260722-001`), `visited_on` date, `inspector_id` (FK users), `attendees` text,
`general_notes` text, `created_by` (FK users), `deleted_at`
Único `(program_id, act_number)`
Pivot **`project_inspection_visit`** (`project_id`, `inspection_visit_id`) — I4:
tableros recorridos, incluso sin hallazgos.
`generalStatus` = accessor derivado (I13): `Sin Observaciones` / `Todo Cerrado` /
`Con Pendientes` / `Pendientes Críticos`.

#### `observations` — entidad central unificada (I1)
`id`, `organization_id`, `program_id` (FK cascade), `inspection_visit_id` (FK
nullable `nullOnDelete`), `task_id` (FK nullable `nullOnDelete` — la columna
"Hito/Actividad"), `observation_type_id` (FK), `specialty_id` (FK nullable),
`number` (`OBS-001`/`NC-001`/`INFO-001`, correlativo **por tipo y programa**),
`detected_on` date, `description` text, `severity` (enum
`menor`|`mayor`|`critica`, **nullable** — solo si el tipo lo exige),
`status` (enum `ObservationStatus`), `corrective_action` text nullable,
`responsible_party_id` (FK nullable), `recipient_party_id` (FK nullable —
"Remite a"), `assigned_to` (FK users nullable), `committed_date` date nullable,
`closed_on` date nullable, `resolution` text nullable (unifica "Observación de
cierre" y "Respuesta"), `evidence_ref` string nullable, `created_by`, `deleted_at`
Único `(program_id, number)` · Índices `(organization_id, status)`,
`(program_id, detected_on)`
Pivot **`observation_project`** — tableros afectados; **sin filas =
"General/Varios"**.
`days_open` = accessor. Adjuntos (fotos) y comentarios polimórficos.
`SoftDeletes` + `restrictOnDelete` desde el diseño (I10).

---

## Fórmulas de avance (paridad exacta con la planilla)

```php
// Task
factor = TaskStatus->progressFactor()            // null si no_aplica

// Activity (hito) — replica SUMIF(<>"N/A") + F10
weight            = Σ tasks.weight               donde factor !== null
weightedProgress  = Σ (tasks.weight × factor) / weight     // null si weight = 0

// Project (tablero) — replica G7
weightedProgress  = Σ (activity.weight × activity.weightedProgress)
                    / Σ activity.weight
                    // excluye actividades con weightedProgress null (I8)

// Program (contrato) — replica Resumen!C12
progress = promedio de los weightedProgress de sus proyectos
```

**D3 confirmado**: `completionPercentage()` (binario) queda **intacto**;
`weightedProgress()` es nuevo y en paralelo. Inspección, el Gantt del tablero y
los widgets usan el ponderado; el resto del PMIS sigue igual.

---

## Máquinas de estado

### `BoardStatus` — derivado del avance, nunca almacenado
```
>= 1.00 → completado · >= 0.99 → por_liberar · > 0 → en_proceso · else → no_iniciado
```

### `ObservationStatus`
```
pendiente ──→ subsanada        (exige closed_on + resolution)
pendiente ──→ respondida       (para tipos con destinatario; exige resolution)
pendiente ──→ informativa      (terminal)
subsanada ──→ reabierta ──→ pendiente
```
- `pendiente` = `P` de ambas planillas; `subsanada` = `OK`; `respondida` = `R`;
  `informativa` = `i`.
- El prototipo decidió **sin reapertura** (si reaparece, se crea una observación
  nueva, ADR 0001). Yo propongo permitir `reabierta` — **decisión abierta D10**.
- Transiciones validadas contra `state_transitions` configurable (I3), con los
  `Select` de Filament ya filtrados; el guard es red de seguridad.

### `TaskStatus`
Sin restricción de transición — el Kanban de arrastre libre de REQ-0002-B lo
exige. Al entrar a `completada` se sella `completed_at`; al salir se limpia.

---

## Recursos y páginas Filament

### Se extiende lo existente
| Dónde | Qué se agrega |
|---|---|
| **`ActivityAccordion`** (REQ-0002-E) | Columnas `Peso`, `Responsable` (parte) y avance ponderado en el header; `Select` de estado con opción N/A |
| **Gantt de proyecto** (REQ-0002-E) | Sin cambios estructurales: los 8 hitos del tablero son actividades del proyecto |
| **Kanban** (REQ-0002-B) | Excluir tareas `no_aplica` |
| **`ProjectResource`** | Sección "Tablero" (extensión `board_profiles`), acción `Generar hitos desde plantilla` |

### Recursos nuevos
| Recurso / Página | Qué hace |
|---|---|
| **`ProgramResource`** (Contratos) | CRUD del contrato: cliente, código, fechas, manager. Listado con avance promedio, N° de tableros, observaciones abiertas |
| **`ProgramResource\Pages\ResumenTableros`** | **Réplica de la hoja `Resumen`**: fila por tablero con avance, estado derivado, plan inicio, plan fin despacho, real fin, NC abiertas, cambios pendientes. Tabla nativa con `SelectColumn`/`->poll('10s')` (patrón del prototipo, ADR 0022) |
| **`ProgramResource\Pages\GanttPrograma`** | Gantt multi-tablero: proyectos como filas raíz, hitos anidados. Reutiliza el componente DHTMLX de REQ-0002-E |
| **`ProgramResource\Pages\CrearTableros`** | Acción de alta masiva: N tableros con TAG, fabricante y plantilla → crea N proyectos + 48 actividades cada uno |
| **`InspectionVisitResource`** (Actas de Visita) | `act_number` autogenerado, tableros recorridos, inspector, asistentes, notas, estado general derivado. RelationManager de observaciones. Acta imprimible/PDF |
| **`ObservationResource`** (Observaciones) | **Entidad central.** Tabs: `Todas` · `A subsanar` · `No Conformidades` · `Consultas` · `Pendientes` · `Vencidas` · **`Subsanadas`** (= hoja `Resumen Subsanadas`). Campos condicionales según `requires_severity`/`requires_recipient` del tipo. Acciones `Subsanar` / `Responder` / `Reabrir`. Tabla agrupada nativa, sin Kanban de terceros (I12) |
| **`WorkTemplateResource`** | Plantillas de actividades/tareas con pesos y responsables por defecto |
| **`ResponsiblePartyResource`** · **`ObservationTypeResource`** · **`SpecialtyResource`** | Catálogos |
| **`StateTransitionResource`** | Matriz de transiciones configurable (I3) |
| **Exportaciones** | `maatwebsite/excel`: "Seguimiento de Integración" (hoja por tablero + Resumen) y "Control de Observaciones" (Visitas + Observaciones + Consultas + Subsanadas) |

---

## Matriz de permisos

| Acción | super_admin | ingeniero | supervisor | calidad | tecnico |
|---|---|---|---|---|---|
| Ver contratos, tableros y avance | ✅ | ✅ | ✅ | ✅ | ✅ |
| Crear/editar contrato (`Program`) | ✅ | ❌ | ✅ | ❌ | ❌ |
| Crear tableros · generar desde plantilla | ✅ | ✅ | ✅ | ❌ | ❌ |
| Eliminar tablero/contrato | ✅ | ❌ | ✅ | ❌ | ❌ |
| Cambiar estado de sub-actividad | ✅ | ✅ | ✅ | ✅ | ✅ |
| Editar pesos y fechas plan | ✅ | ✅ | ✅ | ❌ | ❌ |
| Registrar fechas reales de hito | ✅ | ✅ | ✅ | ✅ | ❌ |
| Crear acta de visita | ✅ | ✅ | ✅ | ✅ | ❌ |
| Crear observación (cualquier tipo) | ✅ | ✅ | ✅ | ✅ | ❌ |
| **Subsanar / cerrar observación** | ✅ | ❌ | ✅ | ✅ | ❌ |
| Responder consulta | ✅ | ✅ | ✅ | ❌ | ❌ |
| Reabrir observación | ✅ | ❌ | ✅ | ✅ | ❌ |
| Editar catálogos, plantillas y transiciones | ✅ | ❌ | ✅ | ❌ | ❌ |
| Exportar Excel/PDF | ✅ | ✅ | ✅ | ✅ | ❌ |

Criterio: **quien detecta no cierra** — `calidad`/`supervisor` cierran hallazgos;
`ingeniero` responde consultas técnicas pero no cierra sus propios hallazgos.

---

## Decisiones técnicas

### Cerradas
- **D1 = B** (usuario): tablero = `Project`, contrato = `Program`.
- **D3 = A** (usuario): `weightedProgress()` en paralelo al binario.
- **D2 = A**: peso del hito **derivado** de sus tareas (`SUMIF`), reforzado por
  el hallazgo I7 del prototipo (el backfill parejo no preserva el número).
- **D4 = A + I1**: **una sola tabla `observations`** con `observation_type_id`
  configurable — no dos tablas, y tampoco una tabla por planilla.
- **D5 = A**: hallazgo ↔ tablero `belongsToMany` (`observation_project`), sin
  filas = "General/Varios".
- **D6 = A**: `responsible_parties` como catálogo (son empresas, no usuarios).
- **D7 = A**: correlativos con transacción + `lockForUpdate` por programa.

### Abiertas
- **D8 — Ponderación del estado: ¿enum o catálogo?**
  (a) `TaskStatus::progressFactor()` en el enum PHP ✅ *recomendada* — coherente
  con CLAUDE.md §7 y con el Kanban/Gantt que ya dependen del enum.
  (b) Catálogo `estados_avance` en BD como el prototipo (I2) — configurable sin
  deploy, pero duplica la fuente de verdad del estado de tarea y obliga a
  reescribir Kanban, Gantt y filtros.
  (c) Enum + tabla de settings solo para los factores numéricos — el estado queda
  fijo y la ponderación configurable. Punto medio.
- **D9 — ¿Máquina de estados configurable también para `Task`?**
  El prototipo la aplicó (ADR 0010 §2.3) con secuencia forzada. Recomiendo
  **no**: rompe el arrastre libre del Kanban. Solo para `Observation`.
- **D10 — ¿Se permite reabrir una observación?**
  El prototipo dijo no (crear una nueva, para no perder historial de cierre); yo
  propongo `reabierta`. Recomiendo permitirlo, con el historial en el log de
  transiciones.
- **D11 — ¿`board_profiles.tag` o solo `projects.code`?**
  Recomiendo mantener `tag` corto separado del código legible del PMIS.

---

## Requisitos funcionales

- RF-01: Crear un contrato (`Program`) con cliente, código y fechas.
- RF-02: Crear N tableros (proyectos) del contrato en una acción, con TAG,
  fabricante, revisión y plantilla, opcionalmente vinculados a un ítem de
  solicitud (REQ-0001).
- RF-03: Generar los 8 hitos y 40 sub-actividades del tablero desde plantilla,
  con pesos y responsables por defecto.
- RF-04: Ajustar pesos de tareas y fechas plan/real de cada hito por tablero.
- RF-05: Cambiar el estado de una sub-actividad (incluido `N/A`) y ver el avance
  del hito, del tablero y del contrato recalculado.
- RF-06: El avance excluye las tareas `N/A` de numerador y denominador, y las
  actividades sin peso computable del promedio superior.
- RF-07: `code` de tarea autogenerado (`TP-1.1`) y recalculado al reordenar.
- RF-08: Registrar actas de visita con `YYYYMMDD-NNN` autogenerado, inspector,
  asistentes, notas y tableros recorridos (incluso sin hallazgos).
- RF-09: Registrar observaciones de cualquier tipo (a subsanar, NC, consulta,
  verificación, información, sugerencia) con campos condicionales según el tipo,
  tableros afectados, sub-actividad relacionada, especialidad, responsable o
  destinatario, fecha de compromiso y fotos de evidencia.
- RF-10: Subsanar/responder con fecha y texto de cierre; ver "días abierta" y
  destacar vencidas (`committed_date < hoy` y pendiente).
- RF-11: Estado general derivado de cada visita.
- RF-12: Página de resumen consolidado por tablero a nivel de contrato.
- RF-13: Gantt multi-tablero a nivel de contrato.
- RF-14: Exportar `.xlsx` con la estructura de las dos planillas actuales.

## Requisitos no funcionales

- `organization_id` + Global Scope en todas las tablas nuevas.
- Migraciones **aditivas y reversibles**; `weight` default 1 y `board_profiles`
  aparte ⇒ ningún proyecto existente cambia de comportamiento.
- `SoftDeletes` + `restrictOnDelete` en actividades, tareas, actas y
  observaciones (I10).
- Adjuntos y comentarios polimórficos vía `HasAttachments` /
  `HasFilamentComments` (programa, tablero, acta, observación).
- Sin N+1: `with`/`withCount` en acordeón, resumen, Gantt y widgets;
  `progress_cached` para ordenar y filtrar en SQL (I9).
- Textos en `lang/es/inspection.php`.
- Factories para todo modelo + seeder de la plantilla estándar real (8 hitos, 40
  sub-actividades) y de los catálogos.

## Criterios de aceptación

1. Crear un contrato y generar 6 tableros produce 6 proyectos, 48 actividades y
   240 tareas con sus pesos por defecto.
2. **Paridad numérica con la planilla**: con los datos de la hoja `TP` el avance
   del tablero es `0,364942…` (`G7`); `CLIMA_A` da `0,448369…`; el promedio del
   contrato, `0,443723…` (`Resumen!C12`).
3. Marcar una tarea como `N/A` altera el avance igual que `SUMIF(<>"N/A")`.
4. Una actividad sin tareas con peso computable **no** arrastra el avance a 0.
5. El estado del tablero es `por_liberar` con avance ≥ 99% y < 100%.
6. `completionPercentage()` y todos los tests de REQ-0002-A/B/E siguen en verde
   sin cambios.
7. Reordenar una actividad recalcula el `code` de todas sus tareas.
8. Dos actas el mismo día generan `YYYYMMDD-001` y `YYYYMMDD-002`.
9. Una observación con dos tableros aparece en el conteo de ambos.
10. Un tipo con `requires_severity` no permite guardar sin severidad; uno sin él
    no muestra el campo.
11. No se puede pasar a `subsanada` sin `closed_on` ni `resolution`.
12. Una transición no sembrada en `state_transitions` es rechazada por el guard.
13. `días abierta` = `closed_on − detected_on`, o `hoy − detected_on` si abierta.
14. Una visita a un tablero sin hallazgos queda registrada y su estado general es
    `Sin Observaciones`.
15. `tecnico` e `ingeniero` no pueden cerrar observaciones; `calidad` y
    `supervisor` sí.
16. Aislamiento por `organization_id` en contratos, tableros, actas y
    observaciones.
17. El `.xlsx` exportado reproduce la estructura de ambas planillas.
18. `./vendor/bin/pest` en verde, Pint y Larastan limpios.

---

## Plan de PRs propuesto

| PR | Contenido |
|---|---|
| **0006-A** | `Program` + FK de `projects.program_id` + `ProgramResource` + `board_profiles` + `ProjectResource` extendido |
| **0006-B** | `work_templates` + `tasks.weight` + `TaskStatus::NoAplica` + `weightedProgress()` + `code` autogenerado + acordeón extendido + `progress_cached` + **test de paridad con la planilla** |
| **0006-C** | Catálogos + `inspection_visits` + `observations` + `state_transitions` + guard + tabs y acciones |
| **0006-D** | Resumen de tableros + Gantt de programa + widgets + exportación `.xlsx` |
| **0006-E** *(opcional)* | **Pruebas / checklist IEC 61439** con patrón librería → plantilla → ejecución snapshot (I5) — cubre el hito 6 "Pruebas FAT" |

---

## Preguntas abiertas

1. **¿Confirmas D1-B a pesar de lo que muestra el prototipo?** `inspector`
   resolvió esto con **`Tablero` como entidad propia bajo `Proyecto`** y las
   actividades colgando del tablero — es decir, un nivel intermedio, no
   "tablero = proyecto". Con D1-B hay que implementar `Program`, se crean 6
   proyectos por contrato y las vistas consolidadas (Resumen, Gantt
   multi-tablero) hay que construirlas nuevas. Con el enfoque del prototipo, el
   port es mucho más directo y ya está validado con ~103 tests. **Lo pregunto
   una sola vez más porque es información nueva; si confirmas B, sigo con B.**
2. **D8** — ¿ponderación en el enum (a), catálogo en BD (b) o híbrido (c)?
3. **D10** — ¿se puede reabrir una observación, o se crea una nueva?
4. **¿Incluimos 0006-E (Pruebas IEC 61439)?** Es la idea más valiosa del
   prototipo que no estaba en tus dos planillas.
5. **¿Portamos código del prototipo o reimplementamos?** El prototipo está en
   español, con ids autoincrementales y sin multi-tenancy real: se puede portar
   la **lógica** (servicios, guard, calculador, snapshot de pruebas) adaptando
   nombres y tipos, o reimplementar leyéndolo como especificación.
6. **Datos reales de IN-17248** — ¿importo los 6 tableros con su avance actual,
   4 actas, 13 observaciones y 23 consultas como seeder?
7. **Observaciones vencidas** — ¿notificación automática (command programado) o
   solo indicador visual?
