# ADR 0008 — Acordeón de actividades y tareas (UX Proyecto)

**Fecha:** 2026-06-27  
**Estado:** Aceptado

## Contexto

La jerarquía `Proyecto → Actividad → Tarea` estaba implementada correctamente en el modelo de datos, pero era inaccesible en la UI: las actividades aparecían como tab en `RelationManager` al fondo de la vista de proyecto, y el botón para agregar tareas estaba escondido como acción de fila sin label visible. Los usuarios reportaron no poder encontrar cómo acceder a actividades ni agregar tareas.

## Decisión

Se reemplazó `ActivitiesRelationManager` y `TasksRelationManager` por un componente Livewire (`ActivityAccordion`) embebido directamente en el `infolist` de `ProjectResource` via `\Filament\Schemas\Components\Livewire`. El componente usa `InteractsWithActions` + `InteractsWithForms` de Filament para manejar modales de CRUD.

**Comportamiento:**
- Las actividades se muestran como acordeón colapsable con un clic
- Cada actividad muestra su estado, conteo de tareas completadas/total y acciones inline (crear tarea, editar, eliminar)
- Al expandir una actividad se muestran sus tareas con código, nombre, estado, prioridad, asignado y fecha límite
- Crear una tarea auto-expande la actividad correspondiente
- El `ProjectMembersRelationManager` se mantiene como tab separado (equipo)

## Alternativas descartadas

**Opción 2 — Mejorar el slide-over existente:** mantener la tabla de actividades y mejorar el panel lateral de tareas. Descartada porque seguía siendo un flujo de dos clics para llegar a las tareas, y el slide-over no permitía crear tareas directamente desde la lista.

**Opción 3 — `ViewActivity` como página propia:** dar a cada actividad su propia URL `/projects/{p}/activities/{a}`. Descartada por ser excesiva para el volumen de datos esperado y requerir navegación entre páginas, rompiendo el contexto del proyecto.

## Consecuencias

- `ActivitiesRelationManager` y `TasksRelationManager` quedan sin uso y pueden eliminarse en una limpieza posterior si no se reutilizan.
- El estado de expansión del acordeón se guarda como propiedad Livewire (`$expanded`): persiste durante la sesión pero se resetea al navegar.
- Las tareas se cargan eager con `with(['activities.tasks.assignees'])` en cada render; aceptable para el volumen esperado (< 50 actividades × < 100 tareas por proyecto).
- Si el volumen crece, agregar paginación lazy por actividad es el siguiente paso natural.
