# ADR-0010 — Gantt: frappe-gantt · Widgets Chart.js · Settings por usuario

**Estado:** Aceptado  
**Fecha:** 2026-07-01  
**REQ:** 0002-E

---

## Contexto

El Gantt actual (Alpine/CSS custom, implementado en ADR-0009) tiene persistencia
drag/resize pero carece de: marcador "Hoy", tooltips, barra de progreso, zoom
trimestral/anual y, críticamente, soporte de flechas de dependencia (roadmap).

El repo de referencia analizado usa **dhtmlxGantt** — librería comercial que viola
la regla de open-source del proyecto. No es replicable.

Los widgets del dashboard actual solo tienen `StatsOverview` y tabla de proyectos
recientes; el repo de referencia muestra que ChartWidget (Chart.js, ya en Filament)
permite dona + línea + barras sin dependencias extra.

El usuario necesita poder personalizar tema de color y preferencias de
notificación; Filament expone un mecanismo de `serving()` para aplicar colores
en runtime.

---

## Decisiones

### D1 — Motor Gantt: frappe-gantt (MIT)

**Alternativas consideradas:**

| Opción | Pros | Contras |
|---|---|---|
| **frappe-gantt** ✅ | MIT, hoy marker, tooltips, zoom, progress, dependencias nativas | Requiere aplanar actividades+tareas en lista plana; panel izquierdo más simple |
| Alpine/CSS custom mejorado | Sin migración, panel izquierdo propio | Flechas de dependencia son inviables manualmente; cada feature es código propio |
| dhtmlxGantt | UX muy pulida, edición inline | **Comercial**. Violación directa de regla open-source. Descartado. |

**Decisión:** frappe-gantt. Las flechas de dependencia (FS/SS/FF/SF) están en el
roadmap del PMIS; frappe-gantt las soporta nativamente con el campo `dependencies`.
Elegir el custom implicaría reimplementar ese feature desde cero en el futuro.

**Estructura de datos para frappe-gantt:**
```js
// Actividades → fila de grupo (non-draggable, custom_class: 'bar-activity')
{ id: 'act-{ulid}', name: 'Nombre actividad', start: 'YYYY-MM-DD',
  end: 'YYYY-MM-DD', progress: 60, custom_class: 'bar-activity' }

// Tareas → fila normal
{ id: 'task-{ulid}', name: 'T001 · Nombre', start: 'YYYY-MM-DD',
  end: 'YYYY-MM-DD', progress: 80, dependencies: '',
  custom_class: 'bar-task' }
```

**Persistencia:**
```js
on_date_change: (task, start, end) => {
    if (task.id.startsWith('act-'))
        $wire.updateActivityDates(id, start, end)
    else
        $wire.updateTaskDates(id, start, end)
}
on_progress_change: (task, progress) => {
    // No persiste en esta versión; progress es calculado en PHP
}
```

**Panel izquierdo:** frappe-gantt renderiza su propio panel (columna `name`).
Status badge y duración se trasladan al tooltip configurado con `popup_trigger`.
El reorder de actividades/tareas se mantiene via SortableJS en un panel HTML
auxiliar que se mantiene sincronizado en altura con las filas de frappe.

**Instalación:** CDN jsDelivr (consistente con SortableJS y Tribute.js):
```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/frappe-gantt/dist/frappe-gantt.css">
<script src="https://cdn.jsdelivr.net/npm/frappe-gantt/dist/frappe-gantt.umd.js"></script>
```

---

### D2 — Avatar de usuario: FileUpload + columna `avatar_url` existente

**Alternativas consideradas:**

| Opción | Pros | Contras |
|---|---|---|
| **FileUpload + avatar_url** ✅ | `avatar_url` ya existe en users, sin paquetes nuevos, Filament 5 nativo | Sin conversiones automáticas, thumbs manuales |
| spatie/laravel-medialibrary | Conversiones, thumbs, S3 robusto, Filament plugin | `filament/spatie-laravel-media-library-plugin` requiere verificar compatibilidad Filament 5; `avatar_url` quedaría redundante |

**Decisión:** `FileUpload` nativo de Filament almacenando en disco `public` (o S3
via `FILESYSTEM_DISK` en .env). Escribe `users.avatar_url` directamente. Si en el
futuro se necesitan thumbs automáticos o conversiones para S3, la migración a
medialibrary es un cambio acotado.

---

### D3 — Chart Widgets: Filament ChartWidget (Chart.js)

Chart.js ya está incluido por Filament sin instalación adicional.
`ChartWidget::getData()` retorna un array compatible con Chart.js v3.

Widgets a implementar:

| Widget | Tipo | Datos |
|---|---|---|
| `TasksByStatusWidget` | Donut | count tareas por `TaskStatus`, scoped a org |
| `MonthlyTaskCreationWidget` | Line | tasks grouped by `DATE_FORMAT(created_at,'%Y-%m')`, últimos 6 meses |
| `ProjectProgressWidget` | HorizontalBar | `completion_percentage` proyectos activos, top 10 |
| `TeamContributionWidget` | Bar | tareas creadas + completadas por usuario, top 5, scoped a org |

No se añaden paquetes. No se crean modelos nuevos.

---

### D4 — Settings por usuario: tabla `user_settings` + Filament serving()

**Tabla `user_settings`:**
```
id              ULID PK
user_id         ULID FK → users (cascade delete)
key             VARCHAR(64) NOT NULL
value           TEXT NULL
timestamps

UNIQUE (user_id, key)
```

Sin `organization_id`: preferencias personales, no del tenant.

**Keys iniciales:**
- `theme_color` → string (e.g. `blue`, `indigo`, `violet`, ...)
- `nav_style` → `sidebar` | `topbar`
- `notify_email` → `1` | `0`
- `notify_database` → `1` | `0`

**Aplicación del color en runtime:**
```php
// AppServiceProvider::boot()
Filament::serving(function () {
    if ($user = auth()->user()) {
        $color = UserSetting::get($user->id, 'theme_color', 'blue');
        FilamentColor::register(['primary' => Color::$color]);
    }
});
```

**Alternativa descartada:** Columnas adicionales en `users` (e.g. `theme_color`,
`nav_style`). Se descartó porque mezcla preferencias de UI con datos de identidad
del usuario, y haría la tabla `users` más ancha con cada nueva preferencia.

---

### D5 — Timezone: columna en `users`

`users.timezone VARCHAR(64) DEFAULT 'America/Santiago'` — usada en `casts()`
de modelos para localizar fechas y en vistas Blade con `->setTimezone()`.

---

## Consecuencias

- El Gantt tiene menos columnas en el panel izquierdo (solo nombre), compensado
  por tooltips ricos y zoom nativo.
- Reorder de actividades en el Gantt requiere sincronización manual de alturas
  entre el panel HTML auxiliar y las filas de frappe-gantt.
- frappe-gantt v0.6.x no soporta flechas de dependencia custom en el panel
  izquierdo — las flechas son en el área de barras (suficiente para el roadmap).
- `avatar_url` sigue siendo una URL simple; si el tamaño de archivo no es
  controlado en el FileUpload, puede llegar un archivo grande al disco.
  Mitigar con `->maxSize(2048)` en el FileUpload.

## Archivos tocados (resumen)

```
docs/requerimientos/0002-E-ux-gantt-kanban-widgets-perfil.md     ← REQ
docs/adr/0010-frappe-gantt-chart-widgets-settings.md             ← este ADR
app/Filament/Resources/ProjectResource/Pages/GanttChart.php      ← reescritura
resources/views/.../gantt-chart.blade.php                        ← reescritura
resources/views/.../kanban-board.blade.php                       ← rediseño
app/Filament/Resources/ProjectResource/Pages/KanbanBoard.php     ← eager load
app/Filament/Widgets/{4 widgets nuevos}
app/Filament/Pages/SettingsPage.php
app/Models/UserSetting.php
app/Providers/AppServiceProvider.php
database/migrations/ (2 migraciones: timezone + user_settings)
lang/es/settings.php
```
