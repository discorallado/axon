# SESSION.md — Estado de sesión de trabajo

> Este archivo lo mantiene Claude Code. Se actualiza al final de cada sesión de trabajo
> y se lee al inicio de la siguiente, para no depender del historial de chat ni de
> copiar/pegar resúmenes a mano.

---

## Última actualización
2026-06-22

## Módulo / feature en curso
Diseño arquitectónico del PMIS Core y módulos de gestión — rol `/arquitecto`

## Estado actual

### Completado ✅
- Módulo de Solicitudes de Tableros (REQ-0001) terminado y cerrado (commits `f7c14d7`, `0f46b26`, `d4f1653`). 23/23 tests en verde.

### En curso — Propuesta arquitectónica presentada, pendiente de validación

Se presentó al usuario una propuesta completa de arquitectura para 5 nuevos requerimientos:

| REQ | Módulo |
|-----|--------|
| REQ-0002 | PMIS Core: Usuarios + Clientes + Proyectos + Actividades + Tareas + Gestión de usuarios super_admin |
| REQ-0003 | Finanzas: Proveedores + OC + Facturas |
| REQ-0004 | Control de Cambios |
| REQ-0005 | FAT en Taller (Factory Acceptance Testing) |
| REQ-0006 | Control de Calidad (checklists + no conformidades) |

**Flujo central diseñado:** SubmissionRequest (aprobada) → [Acción] → Project, con `submission_request_id` como FK nullable en `projects`.

### Tablas nuevas propuestas (sin implementar aún)
`clients`, `projects`, `project_statuses`, `project_members`, `activities`, `tasks`, `task_statuses`, `task_user`, `suppliers`, `purchase_orders`, `invoices`, `change_requests`, `fat_protocols`, `fat_protocol_sections`, `fat_protocol_tests`, `fat_executions`, `fat_results`, `fat_observations`, `quality_templates`, `quality_template_sections`, `quality_template_items`, `quality_checklists`, `quality_checklist_results`, `non_conformances`

## Decisiones pendientes — CRÍTICO, responder antes de implementar

El usuario debe responder las siguientes preguntas de diseño (DQ) antes de usar `/ingeniero`:

- **DQ-1**: Conversión SubmissionRequest → Project: ¿A (botón manual), B (modal semi-automático al aprobar) o C (automático)?
- **DQ-2**: ¿Incluir tabla `programs` (Programa) desde el inicio como capa opcional entre Cliente y Proyecto, o diferirla?
- **DQ-3**: FAT en taller: ¿protocolos editables por el usuario interno (como el form builder) o predefinidos en el sistema?
- **DQ-4**: OC: ¿necesita líneas de ítem (producto/cantidad/precio unitario) o solo monto total + descripción?
- **DQ-5**: Actividades: ¿capa Proyecto → Actividad → Tarea desde el inicio, o empezamos con Proyecto → Tarea directa?
- **DQ-6**: Gestión de usuarios: ¿solo usuarios internos (empleados) o también tokens de portal para clientes externos?

## Próximo paso concreto

1. El usuario responde DQ-1 a DQ-6.
2. Con las respuestas, crear `docs/requerimientos/0002-pmis-core.md` (y los siguientes) con alcance y criterios de aceptación.
3. Registrar decisiones en `docs/adr/0006-arquitectura-pmis-core.md`.
4. Usar `/ingeniero` para implementar REQ-0002 (núcleo del que dependen todos los demás).

---

## Historial de sesiones anteriores

<details>
<summary>2026-06-22 — Fixes post-revisión REQ-0001 (cascade, transacción, adjuntos)</summary>

Fix #4: SubmissionRequestObserver::forceDeleting → forceDelete() en ítems.
Fix #7: DB::transaction() wrappea transition() + FilamentComment::create().
Fix #3: Acción delete_attachments con CheckboxList; policy deleteAttachment con matriz de roles.
Fix A1: IDs de adjuntos acotados a la solicitud y sus ítems (whereIn attachable_id).
Fix M2: dispatch('$refresh') tras eliminación para actualizar infolist.
Fix M1: Placeholder con "Esta solicitud no tiene adjuntos" cuando no hay opciones.
23/23 tests en verde, Pint limpio. ADR: docs/adr/0005-fixes-cascade-transaccion-adjuntos.md.

</details>

<details>
<summary>2026-06-19 — Adjuntos polimórficos, comentarios Parallax, máquina de estados</summary>

Migración de columnas de ruta a modelo Attachment polimórfico con tag. Instalación de
parallax/filament-comments con fix de subject_id para ULIDs. Reescritura completa de
ViewSubmissionRequest. Eliminación de dead code. Observers para cascade delete.
Máquina de estados reforzada con ALLOWED_TRANSITIONS y bloqueo de mismo estado.
13/13 tests en verde. Commit: 210ace1.

</details>

<details>
<summary>2026-06-18 — Mejoras de formulario y back-office (notificaciones, dark mode, acciones)</summary>

Implementadas: notificaciones sync, ActionGroup en back-office, wire:confirm, fix colores, dark mode Alpine.js, modo edición firmada, soft delete en SubmissionRequest. 13/13 tests en verde.

</details>

<details>
<summary>2026-06-18 — Rediseño multi-tablero (submission_items, modal wizard)</summary>

Implementada arquitectura multi-tablero: tabla submission_items, modal wizard
de 3 pasos con Filament Actions, PublicFormWizard reescrito, ViewSubmissionRequest
reescrito con RepeatableEntry. 13/13 tests en verde.

</details>

<details>
<summary>2026-06-17 — Refinamiento del PublicFormWizard (18 cambios UX/campos)</summary>

Aplicadas 18 modificaciones al formulario público: campos renombrados, Select múltiple,
lógica condicional, auto-cálculo de corriente, toggles. Pint limpio, 12/12 tests verde.

</details>
