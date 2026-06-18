# ADR-0003 — Mejoras de formulario y back-office (notificaciones, soft delete, dark mode, edit firmado)

**Fecha:** 2026-06-18  
**Estado:** Aceptado  
**Módulo:** Solicitudes de Tableros Eléctricos

---

## Contexto

Tras implementar la arquitectura multi-tablero (ADR-0002), se identificaron las siguientes necesidades operacionales:

1. Los solicitantes no recibían confirmación al enviar.
2. Los admins no eran notificados de solicitudes nuevas.
3. No había forma de editar una solicitud ya enviada desde el back-office.
4. No había forma de eliminar solicitudes erróneas.
5. El formulario público no tenía modo oscuro.
6. Los botones del formulario eran invisibles (color dependía de CSS variable no siempre cargada).

---

## Decisiones

### D01 — Notificaciones síncronas (sin queue)

Se eliminó `implements ShouldQueue` y `use Queueable` de ambas clases de notificación. El despacho ocurre directamente en `submit()`.

**Alternativas descartadas:**
- **A (Laravel Queue + Horizon):** correcto para producción de alto tráfico, pero añade infraestructura (Redis, worker, Horizon) sin justificación en esta etapa. Se puede migrar después sin cambiar el código de negocio.
- **B (job separado sin queue):** complejidad innecesaria.

### D02 — Edición via URL firmada

Se creó la ruta `GET /solicitud/editar/{submission}` con middleware `signed`, que carga `PublicFormWizard` en modo edición. El admin genera la URL desde el back-office con `URL::signedRoute(..., now()->addHours(4))`. Al re-enviar, se actualiza la solicitud y se recrean los ítems; no se reenvían notificaciones.

**Alternativas descartadas:**
- **Editar inline en Filament:** requeriría duplicar todo el formulario del wizard en Filament. La URL firmada reutiliza el mismo componente Livewire.
- **Token de edición en tabla:** más complejo de gestionar (expiración, revocación). Las URL firmadas de Laravel ya lo resuelven.

### D03 — Soft delete en `submission_requests`

Se agregó `SoftDeletes` al modelo y una migración que añade `deleted_at`. En el back-office, `DeleteAction` de Filament hace soft-delete por defecto. Los registros eliminados son recuperables desde la base de datos.

**Alternativas descartadas:**
- **Hard delete:** irreversible, riesgo de perder datos válidos por error humano.
- **Campo `is_deleted` booleano:** reinventa lo que ya provee `SoftDeletes` de Eloquent.

### D04 — Modo oscuro con Alpine.js + `localStorage`

Se añadió `@variant dark` en `app.css` (Tailwind v4 class-based dark mode). El layout usa `x-data` con `isDark` leído de `localStorage('pf_theme')` y escribe la clase `dark` en `<html>`. Botón sol/luna en el header.

### D05 — Fix de color de botones con Tailwind estático

Se reemplazó `background-color: rgb(var(--pf-600))` (dependía de variables CSS del tema Filament que no siempre cargan en el contexto del formulario público) por `@apply bg-blue-600 hover:bg-blue-500` en `.pf-btn-primary`. El botón "Agregar tablero" en `renderItemsHtml()` también cambió su `style=` inline por clases Tailwind.

### D06 — Query de roles sin `->role()` de Spatie

Para notificar a admins/supervisores se usa `whereHas('roles', fn($q) => $q->whereIn('name', [...]))` en lugar del scope `->role()` de Spatie. El scope de Spatie lanza excepción si el rol no existe en la tabla; el `whereHas` simplemente devuelve vacío.

---

## Consecuencias

- Las notificaciones se envían en el request HTTP de envío del formulario. Si el servidor de correo tarda, aumenta el tiempo de respuesta. Aceptable hasta que el tráfico justifique queues.
- Las URL de edición expiran en 4 horas. El admin puede regenerarlas haciendo clic en "Editar" nuevamente.
- Los registros eliminados no aparecen en la bandeja pero sí en `submission_requests` con `deleted_at` set. Agregar una vista de "papelera" es trabajo futuro.
