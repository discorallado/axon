# REQ-0002-B — Visualización: Kanban interactivo + Gantt + Exportación CSV

- **Estado:** Aprobado — implementar después de REQ-0002-A
- **Depende de:** REQ-0002-A (proyectos, actividades, tareas)
- **Origen:** Diseño arquitectónico aprobado 2026-06-23

---

## Resumen

Agregar visualizaciones interactivas al núcleo de proyectos: tablero Kanban drag-and-drop para mover tareas entre estados, vista Gantt con barras y dependencias usando frappe-gantt, y exportación de tareas a CSV/Excel.

---

## Decisiones de diseño aprobadas

- **Kanban:** paquete `mokhosh/filament-kanban` (open-source, compatible con Filament 5).
- **Gantt:** librería `frappe-gantt` (open-source, MIT) embebida en una página Livewire dentro del panel.
- **Exportación:** `maatwebsite/excel` ya instalado; exportar tareas de un proyecto a `.csv` y `.xlsx`.
- **Regla:** solo dependencias open-source.

---

## Alcance

### Kanban
- Vista de tareas de un proyecto agrupadas por columnas de `TaskStatus`.
- Drag-and-drop para cambiar estado (actualiza `tasks.status` vía Livewire).
- Filtro por actividad, responsable y prioridad.
- Acceso rápido a detalle de tarea desde la tarjeta.

### Gantt
- Barras por tarea con fecha inicio y fecha límite.
- Agrupación por actividad.
- Visualización de dependencias entre tareas (si se implementan — ver nota).
- Zoom: día / semana / mes.
- Click en barra abre detalle de tarea.

### Exportación
- Botón "Exportar CSV" y "Exportar Excel" en el listado de tareas de un proyecto.
- Columnas exportadas: código, nombre, actividad, estado, prioridad, responsables, fechas, horas estimadas/reales.

---

## Nota sobre dependencias de tareas (task dependencies)

El catálogo MVP incluye dependencias FS/SS/FF/SF para el Gantt. No se diseñan en REQ-0002-A para mantener el alcance acotado. Se agregarán en este REQ o en un REQ-0002-B.1 posterior. Decisión: **diferir dependencias al REQ-0002-B** para no bloquear el Gantt básico.

---

## Criterios de aceptación

1. El Kanban muestra las tareas del proyecto en columnas por estado con los colores del Enum.
2. Arrastrar una tarjeta a otra columna cambia el estado en base de datos y refresca la vista.
3. El Gantt muestra barras por tarea, agrupadas por actividad, con zoom semana/mes.
4. La exportación genera un `.xlsx` y un `.csv` válidos con todas las columnas requeridas.
5. Tests Pest cubren: cambio de estado vía Kanban, generación del archivo de exportación.
