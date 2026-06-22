# ADR-0005 — Correcciones: cascade forceDelete, transacción atómica en cambio de estado y eliminación de adjuntos por rol

**Fecha:** 2026-06-22
**Estado:** Aceptado

## Contexto

Tras la revisión del módulo de solicitudes (commit `210ace1`) quedaron tres deudas técnicas identificadas:

1. **Cascade incompleto en forceDelete:** `SubmissionRequestObserver::forceDeleting` llamaba `->delete()` (soft) en ítems hijos, dejando filas en estado soft-deleted huérfanas cuando la solicitud padre se eliminaba permanentemente.

2. **Falta de atomicidad en cambio de estado:** En `ViewSubmissionRequest`, el bloque `SubmissionStateMachine::transition()` + `FilamentComment::create()` no estaba dentro de una sola transacción DB. Si `FilamentComment::create()` fallaba, el estado ya había cambiado pero sin el comentario de auditoría asociado.

3. **Sin mecanismo de eliminación de adjuntos:** Los adjuntos se acumulaban correctamente en edición, pero no había forma de eliminar adjuntos incorrectos o duplicados. Se necesitaba una UI restringida por rol.

## Decisiones

### #4 — forceDelete en cascade
Se cambió `->delete()` por `->forceDelete()` en `SubmissionRequestObserver::forceDeleting` para ítems. Esto garantiza que al eliminar permanentemente una solicitud, sus ítems también se eliminan permanentemente (disparando `SubmissionItemObserver::deleting` para limpiar archivos de disco).

### #7 — Transacción atómica en cambio de estado
Se envolvió `SubmissionStateMachine::transition()` y `FilamentComment::create()` en `DB::transaction()` en `ViewSubmissionRequest::getHeaderActions()`. Así ambas operaciones son atómicas: si el comentario falla, el cambio de estado se revierte.

### #3 — Eliminación de adjuntos por rol
Se agregó:
- Método `deleteAttachment` en `SubmissionRequestPolicy`, con la siguiente matriz:
  - `super_admin` / `ingeniero`: pueden eliminar cualquier adjunto de la organización
  - `supervisor`: solo si la solicitud le está asignada (`assigned_to === $user->id`)
  - `tecnico` / `calidad`: sin acceso
- Acción `delete_attachments` en `ViewSubmissionRequest` que abre un modal con `CheckboxList` listando todos los adjuntos (solicitud + ítems), eliminando los seleccionados del disco y de la BD.

## Alternativas descartadas

- **Soft-delete en Attachment:** Considerado para la eliminación de adjuntos, descartado porque el archivo de disco queda huérfano si no se borra físicamente en el mismo acto.
- **RelationManager para adjuntos:** Habría requerido convertir `ViewRecord` a `EditRecord` o añadir un `RelationManager`. El enfoque de modal es más liviano y consistente con la UI existente.
- **Dejar cascade como soft-delete:** Aceptable si se planeaba una papelera de ítems, pero no hay ese requisito; mantener filas huérfanas en soft-delete es deuda sin valor.
