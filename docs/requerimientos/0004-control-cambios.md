# REQ-0004 — Control de Cambios

- **Estado:** Aprobado — implementar después de REQ-0002-A
- **Depende de:** REQ-0002-A (proyectos)
- **Origen:** Diseño arquitectónico aprobado 2026-06-23
- **Origen adicional (2026-08-03):** la hoja `ControlCambios` de
  `docs/base/Seguimiento_Integracion_Tableros_REPROGRAMADO.xlsx` es una instancia
  real de este requerimiento (`CC-001`, tablero, descripción, motivo, impacto,
  estados `Propuesto/Aprobado/Rechazado/Implementado`, solicitante, aprobado por,
  fecha de aprobación). REQ-0006 **no** implementa control de cambios: solo
  consume el contador de "cambios pendientes" (estado `Propuesto`) para el resumen
  por tablero, y ese contador queda en 0 hasta que este REQ exista.

---

## Resumen

Registro y flujo de aprobación de cambios de alcance, costo o plazo en proyectos. Permite trazabilidad de por qué un proyecto se desvió del plan original.

---

## Modelo de datos

### `change_request_types` (configurable)
`id`, `organization_id`, `name`, `color`, `order`, `timestamps`
Semilla: `Alcance / Costo / Plazo / Técnico`

### `change_requests`
`id (ulid)`, `organization_id`, `project_id`, `type_id (FK change_request_types)`, `requested_by (FK users)`, `number (CR-001)`, `title`, `description`, `justification`, `status`, `cost_impact (decimal nullable)`, `schedule_impact_days (int nullable)`, `reviewed_by (FK users nullable)`, `reviewed_at`, `approved_by (FK users nullable)`, `approved_at`, `implemented_at`, `timestamps`, `deleted_at`

Status enum PHP: `borrador → enviada → en_revision → aprobada → rechazada → implementada`

---

## Criterios de aceptación

1. Cualquier miembro del proyecto puede crear un CR en estado `borrador`.
2. El flujo de revisión/aprobación requiere rol `supervisor` o superior.
3. Un CR aprobado puede marcarse como `implementada` con fecha real.
4. El historial de CRs es visible en el detalle del proyecto.
5. Adjuntos disponibles en cada CR.
6. Tests Pest cubren: flujo completo de estados, permisos por rol.
