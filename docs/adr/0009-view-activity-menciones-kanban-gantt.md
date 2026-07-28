# ADR-0009 — ViewActivity, Menciones con Tribute.js, Kanban mejorado y Gantt custom

**Fecha:** 2026-06-30  
**Estado:** Aprobado e implementado

---

## Contexto

Se requería:
1. Una página dedicada para ver el detalle de una actividad (con lista de tareas y comentarios inline).
2. Menciones `@usuario` en comentarios con notificación al mencionado.
3. Tarjetas de Kanban con descripción, nombre de responsable y botón de navegación.
4. Un Gantt con tabla lateral (nombre, estado, duración), barras interactivas que persistan al mover/estirar, y reordenamiento de filas.

---

## Decisiones

### 1 — ViewActivity como sub-página de `ProjectResource`

**Elegida:** Página Filament estándar (`InteractsWithRecord`) registrada en `getPages()` bajo la ruta `/{record}/activities/{activity}`. El foco a una tarea específica se pasa por query string `?focus={taskId}` y Alpine gestiona el scroll y el highlight.

**Alternativa descartada:** Slide-over/modal desde Kanban — espacio limitado para comentarios e historial largo.

**Alternativa descartada:** `TaskResource` + `ActivityResource` independientes — añade entradas de menú no deseadas y duplica navegación.

### 2 — Menciones via Tribute.js (sin paquete PHP nuevo)

**Elegida:** Se intentó `awcodes/filament-tiptap-editor` pero no existe versión compatible con Filament 5. Se implementó Tribute.js (5 KB CDN) inyectado sobre el `contenteditable` del `RichEditor` existente. El HTML resultante incluye `<span class="mention" data-user-id="...">`, que el `CommentsWithMentions` Livewire parsea con `preg_match_all` para enviar `UserMentionedInComment` (database + mail) a los usuarios mencionados.

El componente Livewire se registra como override en `AppServiceProvider`:
```php
Livewire::component('filament-comments', CommentsWithMentions::class);
```

El endpoint de sugerencias es `GET /admin/api/mention-suggestions?query=...` (autenticado), filtrado por `organization_id`.

**Alternativa descartada:** Extensión Tiptap manual via CDN — mayor complejidad de wiring con Livewire.

### 3 — Kanban: mejoras visuales sin cambios de modelo

Headers de columna con color dinámico via `color-mix()`. Tarjetas con descripción (`line-clamp-2`), avatar+nombre del primer responsable, botón ojo que navega a `ViewActivity?focus=`. Sin cambios en `KanbanBoard.php`.

### 4 — Gantt custom HTML/Alpine (reemplaza frappe-gantt)

**Elegida:** Panel izquierdo fijo (360 px) con SortableJS para reordenar actividades y tareas. Panel derecho scrollable con barras CSS (`left: X%; width: Y%` calculados en PHP). Drag-to-move y drag-to-resize gestionados en Alpine (`mousedown/mousemove/mouseup` sobre `window`). Al soltar, `$wire.updateTaskDates()` / `$wire.updateActivityDates()` persisten las fechas. Toggle "Solo lectura" desactiva interacciones.

**Alternativa descartada:** Mantener frappe-gantt + tabla HTML externa — sincronización de scroll frágil y no permite tabla lateral con estado/duración.

**Alternativa descartada:** DHTMLX/Bryntum — licencias comerciales.

---

## Archivos creados / modificados

| Archivo | Operación |
|---------|-----------|
| `app/Filament/Resources/ProjectResource/Pages/ViewActivity.php` | Nuevo |
| `resources/views/filament/resources/project-resource/pages/view-activity.blade.php` | Nuevo |
| `app/Livewire/CommentsWithMentions.php` | Nuevo |
| `app/Http/Controllers/MentionSuggestionsController.php` | Nuevo |
| `app/Notifications/UserMentionedInComment.php` | Nuevo |
| `resources/views/vendor/filament-comments/comments.blade.php` | Publicado + modificado (Tribute.js, render HTML) |
| `app/Filament/Resources/ProjectResource/Pages/GanttChart.php` | Reescrito |
| `resources/views/filament/resources/project-resource/pages/gantt-chart.blade.php` | Reescrito |
| `resources/views/filament/resources/project-resource/pages/kanban-board.blade.php` | Modificado |
| `app/Filament/Resources/ProjectResource.php` | +1 ruta `view-activity` |
| `app/Providers/AppServiceProvider.php` | Override Livewire component |
| `routes/web.php` | Ruta `/admin/api/mention-suggestions` |
| `lang/es/tasks.php`, `lang/es/projects.php`, `lang/es/notifications.php` | Nuevas claves |

---

## Riesgos residuales

- Tribute.js se engancha al `contenteditable` tras `$nextTick(() => $nextTick(...))`. Si Filament cambia la estructura del RichEditor, el selector CSS puede romper.
- El drag del Gantt usa porcentajes CSS y no resnap a días enteros (se redondea en JS con `Math.round`). Si el usuario mueve muy poco, el delta es 0 y no persiste — comportamiento esperado.
- `color-mix()` en los headers del Kanban no está soportado en Safari <16.2. Fallback a gris sin color.
