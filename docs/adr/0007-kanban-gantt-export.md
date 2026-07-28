# ADR-0007 — Kanban, Gantt y Exportación de tareas (REQ-0002-B)

- **Fecha:** 2026-06-25
- **Estado:** Aprobado e implementado

---

## Contexto

REQ-0002-B requería visualizaciones interactivas sobre las tareas de un proyecto:
tablero Kanban con drag-and-drop, diagrama Gantt con zoom y exportación a
`.xlsx`/`.csv`. El stack es Filament 5 + Livewire 3 + PHP 8.3.

---

## Decisiones

### 1. Kanban — Livewire + SortableJS (CDN) en lugar de `mokhosh/filament-kanban`

`mokhosh/filament-kanban` fue diseñado para Filament 3 y es incompatible con
Filament 5 (`composer require` falla por conflictos de versión). Se construyó
una página Livewire custom (`KanbanBoard`) que:

- Agrupa las tareas por `TaskStatus` enum en el servidor.
- Usa **SortableJS 1.15.6** (CDN via `@assets` de Livewire 3) para
  drag-and-drop entre columnas sin dependencias de build.
- Al soltar una tarjeta, llama `$wire.updateTaskStatus()` marcado con
  `#[Renderless]`: el DOM ya fue actualizado por SortableJS, Livewire solo
  persiste el cambio en BD sin re-renderizar.
- Filtros reactivos (`wire:model.live`) por actividad y prioridad.

**Alternativa descartada:** Construir el Kanban con `@alpinejs/sort` —
requiere inicialización Alpine compleja y no tiene soporte nativo de
drag-entre-contenedores tan maduro como SortableJS.

### 2. Gantt — frappe-gantt (CDN) en página Livewire custom

`frappe-gantt` (MIT, 0.6.1) se carga via CDN en `@assets`. La inicialización
ocurre en un componente Alpine (`x-data`) dentro de un `wire:ignore` para
evitar que Livewire deshaga el DOM del SVG. El zoom (`viewMode`) se controla
con `$wire` + `x-effect`.

**Alternativa descartada:** Instalar frappe-gantt via npm + Vite — requería
configuración adicional de entrypoints, complicaba el flujo de build del
proyecto y no aportaba beneficio adicional dado que la librería no tiene
módulos ES propios relevantes.

### 3. Exportación — `maatwebsite/excel` (instalado en esta sesión)

`TasksExport` implementa `FromQuery + WithHeadings + WithMapping + WithStyles`.
Las acciones "Exportar Excel" y "Exportar CSV" se agregaron al header de
`ViewProject` para acceso rápido desde la vista de detalle del proyecto.

**Nota:** `pestphp/pest-plugin-livewire` fue instalado como dev-dependency para
habilitar `livewire()` en los tests de Pest.

---

## Consecuencias

- No hay dependencias de build adicionales para Kanban/Gantt (CDN en dev y prod).
- SortableJS y frappe-gantt se cargan por página solo cuando se visitan esas vistas.
- El modelo de datos no cambió; todo opera sobre las entidades de REQ-0002-A.
- Dependencias de tareas (FS/SS/FF/SF) quedan diferidas a un REQ-0002-B.1 futuro.
