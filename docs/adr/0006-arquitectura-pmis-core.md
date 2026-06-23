# ADR-0006 — Arquitectura PMIS Core y módulos de gestión

- **Fecha:** 2026-06-23
- **Estado:** Aceptado
- **Módulos:** REQ-0002-A/B/C/D, REQ-0003, REQ-0004

---

## Contexto

Tras completar el módulo de Solicitudes de Tableros (REQ-0001), se diseñó el núcleo del PMIS y los módulos de gestión. Se tomaron varias decisiones de diseño que afectan la arquitectura global del sistema.

---

## Decisiones

### 1. Estados de tarea: Enum PHP/Filament (no tabla configurable)

**Decisión:** Los estados de tarea se implementan como un Enum PHP backed string que implementa `HasLabel`, `HasColor` y `HasIcon` de Filament.

**Alternativa descartada:** Tabla `task_statuses` configurable (como `project_statuses`).

**Razón:** Los estados de tarea tienen semántica fija en el dominio de construcción (pendiente / en progreso / en revisión / completada / bloqueada). Hacer la tabla configurable agrega complejidad sin beneficio real. Los estados de *proyecto* sí son configurables porque el cliente puede necesitar estados como "En Licitación" o "En Garantía" según el contrato.

---

### 2. Kanban: mokhosh/filament-kanban

**Decisión:** Usar el paquete `mokhosh/filament-kanban` (open-source, MIT).

**Alternativa descartada:** Componente custom con Livewire + SortableJS.

**Razón:** El paquete ya está integrado con Filament y reduce el tiempo de implementación. La regla del proyecto es preferir paquetes maduros del ecosistema antes de construir a mano.

---

### 3. Gantt: frappe-gantt

**Decisión:** Usar la librería `frappe-gantt` (open-source, MIT) embebida en una página Livewire.

**Alternativa descartada:** dhtmlx Gantt (comercial, ~$500).

**Razón:** Regla del proyecto: solo dependencias open-source. frappe-gantt cubre el 90% de los casos del MVP (barras, zoom, grupos). Si en producción se necesitan features avanzadas, se evalúa migración.

---

### 4. Identificadores legibles de tareas

**Decisión:** Las tareas tienen un campo `code` generado automáticamente con formato `{PREFIJO_PROYECTO}-{NRO_ACTIVIDAD}-T{CORRELATIVO}`, ej: `TAB-001-T042`.

**Razón:** Estándar en PMISs. Facilita la comunicación verbal y la referencia en comentarios. El ULID sigue siendo la PK interna.

---

### 5. Conversión SubmissionRequest → Project: acción mixta

**Decisión:** Al aprobar una solicitud se dispara (a) una notificación in-app con botón "Crear Proyecto" y (b) una Action en el listado de solicitudes aprobadas. Ambas abren un modal con datos pre-rellenados desde la solicitud.

**Alternativa descartada:** Conversión automática (sin intervención del usuario).

**Razón:** El usuario puede necesitar ajustar el nombre, cliente o código del proyecto antes de crearlo. La acción mixta mantiene control sin fricción extra.

---

### 6. Programs: columna diferida

**Decisión:** `program_id` existe como columna nullable en `projects` desde el inicio, pero no se construye el módulo Programs ni el recurso Filament.

**Razón:** Multi-tenant-ready en diseño. Cuando se necesite agrupar proyectos bajo un contrato marco, la columna ya existe y no requiere migración de datos.

---

### 7. Portal externo del cliente: token + Reverb

**Decisión:** Dashboard público en ruta `/portal/{token}`, sin login de Filament. Tiempo real via Laravel Reverb + Livewire.

**Razón:** El cliente no debe tener acceso al panel interno. El token es la credencial mínima suficiente para acceso de solo lectura.

---

### 8. OC sin líneas de ítem

**Decisión:** Las Órdenes de Compra no tienen tabla de ítems desglosados. Se usan campos `amount_net`, `tax_amount`, `amount_total` y `description` libre.

**Razón:** El usuario confirmó que el nivel de detalle actual es suficiente para el MVP. Se puede agregar `purchase_order_items` en el futuro sin romper el esquema.

---

### 9. Solo dependencias open-source

**Decisión (regla del proyecto):** Toda librería, paquete o herramienta debe ser open-source. No se introducen dependencias comerciales o de pago.

**Razón:** Declarado por el usuario como regla primordial del desarrollo.

---

## Consecuencias

- Los estados de tarea no son modificables por usuarios finales (solo por el equipo de desarrollo).
- El Gantt del MVP no tendrá dependencias FS/SS/FF/SF (se agregan en REQ-0002-B o posterior).
- El portal externo requiere que Laravel Reverb esté configurado antes de implementar REQ-0002-D.
- Los módulos REQ-0002-B, C y D pueden implementarse en paralelo tras completar REQ-0002-A.
