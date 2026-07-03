# REQ-0002-E — UX Enhancement: Gantt frappe · Kanban · Widgets · Perfil & Settings

## Contexto

Mejora transversal de la capa de presentación del PMIS, inspirada en análisis del
repo de referencia `dewakoding-project-management`. No agrega nuevas entidades de
dominio; mejora la experiencia de visualización y configuración sobre el modelo
ya implementado.

## Alcance

### E1 — Motor Gantt: migración a frappe-gantt
- Reemplazar la implementación Alpine/CSS custom por **frappe-gantt** (MIT).
- La persistencia drag-to-move / drag-to-resize continúa via Livewire
  (`updateTaskDates`, `updateActivityDates`).
- Soporte de modos de vista: Día, Semana, Mes, Trimestre — nativos en frappe-gantt.
- Marcador "Hoy" y tooltips en hover — nativos.
- Barra de progreso calculada desde el porcentaje de completitud de tareas.
- Panel izquierdo: nombre de actividad/tarea + código. Status y duración en tooltip.
- Preparación para flechas de dependencia (roadmap REQ-0002-F): frappe-gantt
  las soporta nativamente con el campo `dependencies`.
- Reorder de actividades/tareas: conservar SortableJS en un panel izquierdo
  HTML separado, sincronizado con las filas de frappe-gantt vía alturas iguales.

### E2 — Kanban: rediseño de tarjetas
- Badge de prioridad en la tarjeta (color del `TaskPriority` enum).
- Hasta 2 avatares de asignados con iniciales + chip `+N` si hay más.
- Ancho de columna responsive (calc-based, ~3 columnas en desktop).
- Tooltip con nombre completo al pasar el mouse sobre cada avatar.
- Se elimina el nombre de texto del primer asignado (reemplazado por avatares).

### E3 — Dashboard Widgets
Nuevos widgets de gráfico (Chart.js via Filament `ChartWidget`):
1. **TasksByStatusWidget** — dona: distribución de tareas por status.
2. **MonthlyTaskCreationWidget** — línea: tareas creadas por mes (últimos 6 meses).
3. **ProjectProgressWidget** — barras horizontales: `completion_percentage` de
   proyectos activos (máx. 10, ordenados por progreso DESC).
4. **TeamContributionWidget** — barras: tareas creadas vs completadas por usuario
   (top 5, scoped al `organization_id`).

Mejora al widget existente `ProjectStatsWidget`: stats separadas por rol
(super_admin ve todo el org; otros ven solo sus proyectos asignados).

### E4 — Perfil de usuario y Settings
**Perfil (extiende el built-in de Filament):**
- Avatar upload → `users.avatar_url` (Filament `FileUpload` + disco `public`/S3).
- Selector de zona horaria → `users.timezone`.
- Nombre, email y cambio de contraseña (ya provistos por Filament).

**Settings Page** (`/admin/settings`):
- Sección Apariencia: color de tema (12 opciones), estilo de navegación
  (sidebar / topbar).
- Sección Notificaciones: toggle email, toggle base de datos.
- Persistencia en tabla `user_settings`.
- Aplicación en tiempo real del color vía `Filament::serving()` en
  `AppServiceProvider`.

## Criterios de aceptación

- [ ] E1: El Gantt muestra barras de actividades y tareas con frappe-gantt; el
      drag move/resize persiste en BD sin recargar la página.
- [ ] E1: Los modos de vista Día/Semana/Mes/Trimestre funcionan y el marcador
      "Hoy" es visible.
- [ ] E1: El reorder de actividades y tareas persiste via SortableJS + Livewire.
- [ ] E2: Las tarjetas Kanban muestran badge de prioridad y hasta 2 avatares + `+N`.
- [ ] E2: El drag-and-drop entre columnas sigue funcionando y persiste el status.
- [ ] E3: Los 4 widgets nuevos se renderizan en el dashboard y muestran datos reales.
- [ ] E3: Los widgets respetan el scope de `organization_id`.
- [ ] E4: El usuario puede subir un avatar; se muestra en el nav y en el Kanban.
- [ ] E4: El cambio de color de tema se aplica inmediatamente sin cerrar sesión.
- [ ] E4: La zona horaria guardada se usa para formatear fechas en vistas del PMIS.
- [ ] Tests Pest pasan, Pint limpio, Larastan sin errores.

## Archivos principales afectados

```
app/Filament/Resources/ProjectResource/Pages/GanttChart.php        ← reescritura
app/Filament/Resources/ProjectResource/Pages/KanbanBoard.php        ← eager load priority
app/Filament/Widgets/ProjectStatsWidget.php                         ← mejora rol
app/Filament/Widgets/TasksByStatusWidget.php                        ← nuevo
app/Filament/Widgets/MonthlyTaskCreationWidget.php                  ← nuevo
app/Filament/Widgets/ProjectProgressWidget.php                      ← nuevo
app/Filament/Widgets/TeamContributionWidget.php                     ← nuevo
app/Filament/Pages/SettingsPage.php                                 ← nuevo
app/Models/UserSetting.php                                          ← nuevo
app/Providers/AppServiceProvider.php                                ← serving() color
resources/views/filament/.../gantt-chart.blade.php                  ← reescritura
resources/views/filament/.../kanban-board.blade.php                 ← rediseño tarjetas
database/migrations/..._add_timezone_to_users_table.php             ← nuevo
database/migrations/..._create_user_settings_table.php             ← nuevo
lang/es/settings.php                                                ← nuevo
lang/es/dashboard.php                                               ← ampliar
package.json                                                        ← frappe-gantt CDN (no npm)
```

## Dependencias externas

- **frappe-gantt** v0.6.x — MIT — CDN jsDelivr (consistente con SortableJS y Tribute.js)
- **Chart.js** — ya incluido por Filament ChartWidget (sin instalación extra)
- **`spatie/laravel-medialibrary`** — EVALUADO y DESCARTADO en este REQ:
  `avatar_url` ya existe como columna string; usar `FileUpload` de Filament es
  suficiente y evita riesgo de compatibilidad con Filament 5 del plugin oficial.
  Medialibrary puede incorporarse en un REQ futuro si se necesitan conversiones
  de imagen o S3 con thumbs automáticos.

## No incluido en este REQ

- Flechas de dependencia entre tareas en el Gantt (requiere modelo de dependencias
  → REQ-0002-F futuro).
- Leaderboard / heatmap de actividad (→ REQ-0002-C KPI Dashboard ya diseñado).
- `spatie/laravel-medialibrary` (diferido).
- Portal externo (→ REQ-0002-D).
