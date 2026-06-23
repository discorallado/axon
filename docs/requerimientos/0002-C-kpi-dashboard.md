# REQ-0002-C — Dashboard de KPIs por Proyecto

- **Estado:** Aprobado — implementar después de REQ-0002-A
- **Depende de:** REQ-0002-A
- **Origen:** Diseño arquitectónico aprobado 2026-06-23

---

## Resumen

Widgets de KPIs a nivel de proyecto en el panel Filament. Cada widget responde a métricas de rendimiento del proyecto seleccionado.

---

## KPIs a implementar (widgets Filament)

| Widget | Métrica | Tipo visual |
|---|---|---|
| Tareas a tiempo | % tareas completadas antes de su `due_date` | StatsOverview |
| Porcentaje de avance | Tareas completadas / total de tareas del proyecto | Progress bar / Stat |
| Tiempo promedio de resolución | Promedio de días entre creación y `completed_at` de tareas completadas | StatsOverview |
| Tasa de cambios | Número de change requests activas por proyecto | StatsOverview |
| Tareas por estado | Distribución de tareas por `TaskStatus` | Pie chart / Bar chart |
| Tareas vencidas | Tareas con `due_date` pasada y no completadas | Stat con color danger |

---

## Criterios de aceptación

1. Los widgets aparecen en la vista de detalle del proyecto (`ProjectDetailPage`).
2. Cada widget calcula su métrica en tiempo real sobre las tareas del proyecto.
3. El widget de tareas vencidas resalta en rojo cuando el valor > 0.
4. Los datos son correctos según los tests Pest (valores esperados vs. calculados).
