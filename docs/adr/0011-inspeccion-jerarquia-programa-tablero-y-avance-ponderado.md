# ADR-0011: Módulo Inspección — jerarquía Programa/Tablero y avance ponderado

- **Fecha:** 2026-08-03
- **Estado:** Aceptado (parcial) — decisiones D1 y D3 cerradas por el usuario;
  D8, D10 y el alcance de 0006-E siguen abiertos
- **Fase / Módulo:** REQ-0006 — Inspección (seguimiento de fabricación de
  tableros, visitas y observaciones)
- **Requerimiento:** [docs/requerimientos/0006-inspeccion.md](../requerimientos/0006-inspeccion.md)

## Contexto

El equipo de inspección hace seguimiento a la fabricación de tableros eléctricos
en taller del fabricante usando dos planillas Excel, que están en `docs/base/`:

- `Seguimiento_Integracion_Tableros_REPROGRAMADO.xlsx` — 6 tableros del contrato
  `IN-17248`, una hoja cada uno, con 8 hitos y 40 sub-actividades ponderadas,
  estados con valor de avance (`Pendiente 0` / `En proceso 0.5` / `Completado 1` /
  `N/A excluido`), fechas plan y real a nivel de hito, responsable por empresa
  (`CSE`/`CMF`/`CLIENTE`) y avance ponderado calculado. Más hojas `Resumen`,
  `NoConformidades`, `ControlCambios` y `Config`.
- `Control_Observaciones_IN17248.xlsx` — actas de visita con número
  `YYYYMMDD-NNN`, observaciones (`OBS-001`, severidad Crítica/Mayor/Menor,
  estados `P`/`OK`/`i`), consultas (`INFO-001`, destinatario "Remite a", estados
  `R`/`P`/`i`) y una hoja `Resumen Subsanadas` que es una vista derivada.

Restricciones relevantes:

- El usuario pidió **integrarlo al núcleo PMIS** (`Project → Activity → Task`) en
  vez de construir una jerarquía paralela, para heredar Kanban (REQ-0002-B),
  Gantt DHTMLX (REQ-0002-E), acordeón de actividades, comentarios, adjuntos,
  dependencias y permisos ya construidos.
- Existe un **prototipo hermano** en `/home/ubuntu/inspector`
  (`Modules/Inspeccion/`) que ya resolvió este dominio completo: 23 ADRs,
  ~103 tests Pest, 22 migraciones, 19 modelos, 17 recursos Filament. Su ADR 0009
  documenta que portó `Actividad`/`Tarea` **desde este repo, commit `25f8fc4`**,
  con la intención declarada de integrarse de vuelta.
- `ControlCambios` de la planilla ya está cubierto por REQ-0004; no se duplica.

## Decisión

### D1 — Jerarquía: el tablero es un `Project`, el contrato un `Program`

```
Client
 └─ Program   ← CONTRATO   IN-17248 · "Remodelación Centro de Datos Magnus 1"
     └─ Project ← TABLERO   TP, T_G2, BUS_A, BUS_B, CLIMA_A, CLIMA_B     (×6)
         └─ Activity ← HITO   1. Armado … 8. Despacho                    (×8)
             └─ Task ← SUB-ACTIVIDAD   1.1, 1.2 …                        (×40)
```

Cada hoja de la planilla de seguimiento es literalmente un proyecto, y la hoja
`Resumen` es el programa. Las visitas y observaciones cuelgan del **programa**,
porque una visita recorre varios tableros a la vez.

Consecuencias asumidas:

- **Se activa `Program`**, entidad hasta ahora diferida.
  `projects.program_id` ya existe como `unsignedBigInteger` sin FK: hay que
  migrarlo a `foreignUlid` con `constrained()->nullOnDelete()`.
- Se crean 6 proyectos por contrato ⇒ cliente, miembros, fechas y estado se
  administran seis veces. Se mitiga con una acción de alta masiva
  ("Crear tableros del contrato").
- Las vistas consolidadas (Resumen por tablero, Gantt multi-tablero) son
  **nuevas**: las páginas actuales son mono-proyecto.
- Los campos propios del tablero (`tag`, fabricante, revisión, ítem de solicitud)
  **no** se agregan a `projects`: van en una extensión 1:1 `board_profiles`, para
  no contaminar la tabla núcleo del PMIS.

### D2 — El peso del hito se deriva de sus tareas, no se captura

Réplica exacta de la planilla (`D10 = SUMIF(estados <> "N/A"; pesos)`):
`activity.weight = Σ tasks.weight` sobre las tareas con factor de avance no nulo.
No hay columna `weight` en `activities`.

### D3 — `weightedProgress()` en paralelo, sin tocar el avance binario existente

`Activity::completionPercentage()` (binario: completadas / total) **queda
intacto**, y con él todos los tests y números de REQ-0002-A/B/E. Se agrega:

```
Task:     factor = TaskStatus->progressFactor()          // null si no_aplica
Activity: weight = Σ tasks.weight  (factor !== null)
          weightedProgress = Σ (weight_i × factor_i) / weight     // null si weight = 0
Project:  weightedProgress = Σ (act.weight × act.weightedProgress) / Σ act.weight
                             // excluye actividades con weightedProgress null
Program:  progress = promedio de los weightedProgress de sus proyectos
```

Inspección, el Gantt del tablero y los widgets usan el ponderado; el resto del
PMIS sigue con el binario.

### D4 — Una sola entidad `observations`, con tipo configurable

Observaciones, no conformidades, consultas, verificaciones, informaciones y
sugerencias son **una tabla** con `observation_type_id` (catálogo con flags
`requires_severity` / `requires_recipient`). Las dos planillas son la misma
entidad con distinto tipo; de hecho hay ítems `INFO-0xx` que en la práctica son
observaciones a subsanar. Relación con tableros vía pivot
`observation_project` (sin filas = "General/Varios" de la planilla).

### D5 — `TaskStatus` gana `NoAplica` y un factor de avance

Nuevo caso `no_aplica` con `progressFactor()` que retorna `null`, excluyéndolo de
numerador y denominador. Los tres estados de la planilla calzan exacto:
`pendiente → 0.0`, `en_progreso → 0.5`, `completada → 1.0`; se completan
`en_revision → 0.75` y `bloqueada → 0.5`, que no existen en la planilla.

Efecto lateral a manejar: `no_aplica` debe excluirse del Kanban y de
`Activity::getStatusAttribute()`, o una actividad con tareas N/A nunca llegaría
a "Completada".

### D6 — Ideas adoptadas del prototipo `inspector`

Se adoptan, con su ADR de origen (detalle completo en el requerimiento):

| Idea | Origen |
|---|---|
| Una sola entidad de observación con tipo configurable (`requiere_severidad`) | ADR 0001 |
| Pivot visita ↔ tablero **explícita**, para registrar una visita sin hallazgos | ADR 0001 |
| Máquina de estados **configurable en BD** (`state_transitions` + guard en `saving()` + `Select` ya filtrados) para observaciones | ADR 0001, 0010 |
| Pruebas / checklist IEC 61439 con patrón **librería → plantilla → ejecución snapshot** (la ejecución copia el texto, el histórico no cambia si el catálogo se edita) | ADR 0001 |
| `code` de tarea autogenerado `{tag}-{actividad.orden}.{tarea.orden}`, recalculado en cascada al reordenar, con `updateQuietly()` | ADR 0023 |
| El backfill de peso parejo (=1) **no** preserva el avance global: hay que usar `Σ(pesos de las tareas no excluidas)` | ADR 0022 |
| Criterio "no participa, no cuenta como 0%" para lo que no tiene peso computable | ADR 0022 |
| Avance cacheado + `*_calculated_at`, recalculado por Observer, para ordenar y filtrar en SQL sin N+1 | ADR 0001 |
| `SoftDeletes` + `restrictOnDelete` en todo lo histórico (actas, observaciones, actividades, tareas) | ADR 0010 §2.6 |
| Catálogo de especialidad (Eléctrico / Mecánico / Control / Documentación / HSE / Otro) | ADR 0001 |
| Estado general de visita derivado (`Sin Observaciones` / `Todo Cerrado` / `Con Pendientes` / `Pendientes Críticos`) | ADR 0001 |
| Nada de Kanban de terceros: tabla agrupada nativa de Filament | ADR 0005 → 0008 |

## Alternativas consideradas

### Jerarquía del tablero (D1)

1. **Tablero = `Project`, contrato = `Program`** — *elegida por el usuario*.
   Cada hoja de la planilla es un proyecto y el `Resumen` es el programa; el
   modelo queda conceptualmente limpio. Contra: obliga a implementar `Program`,
   multiplica ×6 la administración de proyecto y las vistas consolidadas hay que
   construirlas nuevas.
2. **Tablero como agrupador transversal** (`activities.board_id` nullable) —
   recomendada originalmente por `/arquitecto`. Un solo proyecto real con 48
   actividades etiquetadas por tablero; hereda Gantt, Kanban y acordeón sin
   vistas nuevas. Contra: los listados de actividades del proyecto crecen ×6 y
   necesitan filtro por tablero. **Descartada por decisión del usuario.**
3. **Tablero como nivel intermedio propio** (`Proyecto → Tablero → Actividad →
   Tarea`) — es lo que implementó y validó el prototipo `inspector`
   (`actividades.tablero_id` obligatorio, sin `proyecto_id` duplicado). Haría el
   port mucho más directo. **Descartada por la misma decisión**; se le señaló al
   usuario como información nueva antes de cerrar D1.
4. **Tablero = `Activity`**, hito = `Task`, sub-actividad = subtarea
   (`parent_task_id`, ya existe). Cero tablas nuevas de jerarquía, pero el
   tablero no puede tener adjuntos propios naturalmente, las fechas plan/real del
   hito caen en una tarea y el Kanban mostraría hitos en vez de trabajo real.

### Avance ponderado (D3)

1. **`weightedProgress()` en paralelo** — *elegida*. No rompe REQ-0002-B/E ni sus
   tests.
2. Reemplazar el binario por el ponderado en todo el PMIS: una sola verdad y más
   correcto, pero cambia números existentes y obliga a reescribir tests.
3. Flag `progress_mode` por proyecto: flexible, dos caminos que mantener.

### Modelado de observaciones (D4)

1. **Una tabla con tipo configurable** — *elegida* (coincide con el prototipo).
2. Dos tablas (`findings` / `queries`): esquema más estricto, duplica recurso,
   policy y tests, y obliga a `UNION` para "hallazgos abiertos".
3. Una tabla por planilla (3 entidades): reproduce el problema que las planillas
   ya tienen.

### Ponderación de estados (D8, abierta)

1. `TaskStatus::progressFactor()` en el enum PHP — recomendada; coherente con
   CLAUDE.md §7 y con el Kanban/Gantt que ya dependen del enum.
2. Catálogo `estados_avance` en BD como el prototipo: configurable sin deploy,
   pero duplica la fuente de verdad del estado de tarea y obliga a reescribir
   Kanban, Gantt y filtros.
3. Enum para el estado + tabla de settings solo para los factores numéricos.

### Ideas del prototipo descartadas

- **Catálogos en BD para todos los estados, sin enums PHP**: es el principio del
  CLAUDE.md de ese repo, no del de Axon, que pide "enums de PHP para
  estados/tipos fijos; tablas para los configurables". `TaskStatus` ya está
  acoplado a Kanban, Gantt y Filament.
- **Ids autoincrementales**: Axon usa ULID en todo el núcleo.
- **Modelos en español** (`Actividad`, `Tarea`): Axon ya tiene `Activity`/`Task`;
  se mantiene inglés en código y español en UI.
- **Máquina de estados forzada para `Task`** (sin salto directo a Completada):
  rompería el arrastre libre del Kanban de REQ-0002-B.

## Consecuencias

**Más fácil**

- El seguimiento de fabricación hereda Gantt, Kanban, acordeón, comentarios,
  adjuntos y dependencias sin construir vistas de seguimiento nuevas.
- `Program` deja de ser deuda diferida y habilita agrupar proyectos por contrato
  para el resto del PMIS.
- `work_templates` queda como plantilla de actividades/tareas **genérica**, útil
  para cualquier proyecto, no solo tableros.
- Una sola entidad de observación cubre las dos planillas y cualquier tipo futuro
  sin migración de esquema.

**Más difícil / deuda asumida**

- 6 proyectos por contrato: administración repetida, mitigada con alta masiva.
- Resumen consolidado y Gantt multi-tablero son desarrollo nuevo a nivel de
  programa.
- `board_profiles` como extensión 1:1 agrega un join en la mayoría de las vistas
  de tablero.
- Dos fórmulas de avance conviviendo (binaria y ponderada) hasta que se unifiquen
  en algún REQ posterior.
- `no_aplica` obliga a revisar todos los consumidores de `TaskStatus`
  (Kanban, `getStatusAttribute()`, filtros, widgets).
- El port desde el prototipo deja de ser mecánico: cambia la jerarquía, los ids
  (ULID), el idioma de los modelos y la multi-tenancy. Se porta la **lógica**
  (calculador, guard, snapshot), no el código tal cual.

**A revisar si cambia el alcance**

- Si más adelante se decide que el tablero sea un nivel intermedio (alternativa 3),
  la migración es real: mover 6 proyectos a filas de `boards` y repuntar
  `activities`. Conviene decidirlo antes de cargar datos de producción.
- `manufacturer` es string hoy; pasa a FK a `Supplier` cuando exista REQ-0003.
- Al activar multi-tenancy, `programs` y todas las tablas nuevas ya llevan
  `organization_id` + Global Scope.

## Pendiente de cerrar antes de `/ingeniero`

- **D8** — ponderación en el enum, en catálogo de BD, o híbrido.
- **D10** — ¿se permite reabrir una observación (propuesto) o se crea una nueva
  (criterio del prototipo, para no perder el historial de cierre)?
- **0006-E** — ¿entra el checklist IEC 61439 (librería → plantilla → ejecución
  snapshot)?
- ¿Portar lógica del prototipo o reimplementar leyéndolo como especificación?
- ¿Importar los datos reales de `IN-17248` como seeder?
