# REQ-0002-D — Portal Externo del Cliente (tiempo real)

- **Estado:** Aprobado — implementar después de REQ-0002-A
- **Depende de:** REQ-0002-A; requiere Laravel Reverb configurado
- **Origen:** Diseño arquitectónico aprobado 2026-06-23

---

## Resumen

Dashboard público (sin login de empleado) para que el cliente vea en tiempo real el avance de su(s) proyecto(s). Acceso mediante token único por proyecto/cliente. El dashboard se actualiza automáticamente vía Laravel Reverb (WebSockets).

---

## Decisiones de diseño

- Acceso por token único (no requiere cuenta de usuario interno).
- Vista de solo lectura — el cliente no puede modificar nada.
- Tiempo real mediante Laravel Reverb + Livewire (ya en el stack).
- Implementado fuera del panel Filament (ruta pública dedicada, como el formulario de solicitudes).

---

## Alcance

### Modelo de acceso
- Tabla `client_portal_tokens`: `id`, `project_id`, `client_id`, `token` (hash), `expires_at` (nullable), `last_accessed_at`, `timestamps`.
- El `super_admin` genera tokens desde el detalle del proyecto en Filament.
- El cliente recibe el enlace: `https://axon.app/portal/{token}`.

### Dashboard del cliente
Secciones visibles:
- Resumen del proyecto (nombre, estado, fechas, % avance general).
- Progreso por actividad (barra de progreso con tareas completadas/total).
- Últimas tareas actualizadas (feed de actividad reciente).
- KPIs básicos: % avance, tareas completadas, tareas vencidas.
- Hitos próximos (actividades con fecha límite en los próximos 30 días).

### Tiempo real
- Al completarse una tarea o cambiar estado de una actividad/proyecto, el dashboard del cliente se actualiza sin recargar la página (Livewire + Reverb broadcast).

---

## Criterios de aceptación

1. `super_admin` puede generar y revocar tokens de portal desde el detalle del proyecto.
2. El cliente accede con el enlace del token y ve el dashboard de solo lectura.
3. Sin token válido, la ruta retorna 404 (no expone información del proyecto).
4. Al completar una tarea en el panel interno, el dashboard del cliente se actualiza en tiempo real (< 2 segundos).
5. El token puede tener fecha de expiración opcional.
6. Tests Pest cubren: acceso con token válido/inválido/expirado, aislamiento por proyecto.
