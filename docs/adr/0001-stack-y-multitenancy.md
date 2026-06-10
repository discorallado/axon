# ADR-0001: Stack base y estrategia multi-tenant-ready

- **Fecha:** 2026-06-09
- **Estado:** Aceptado
- **Fase / Módulo:** Fase 0 — Fundaciones

## Contexto

Se construye Axon PMIS desde cero, reemplazando un prototipo previo en Laravel
12 + Filament. El equipo (un desarrollador principal) ya domina Filament. El
producto es un PMIS enterprise para construcción eléctrica e infraestructura
crítica, con back-office de datos densos (proyectos, tareas, OC, facturas,
protocolos) y necesidades futuras de portal de cliente y herramientas de campo.
A futuro podría ofrecerse a varias empresas (multi-tenant), pero el primer
despliegue es para una sola organización.

## Decisión

1. **Back-office en Filament 4** sobre Laravel 12 / PHP 8.2+. Cubre ~85% de la
   UI (CRUD, tablas, filtros, RBAC, formularios complejos).
2. **Livewire + Laravel Reverb** para interactividad y tiempo real (Kanban,
   comentarios), sin salir de PHP.
3. **React/Vite PWA solo** para portal de cliente y herramientas de campo
   (p. ej. anotación de planos), no para el back-office.
4. **Single-tenant en operación, multi-tenant-ready en diseño:** `organization_id`
   en todas las entidades desde el día uno, con Global Scope de tenant
   transparente. El modo `teams` de Shield se activa en una fase posterior, sin
   reescribir el esquema.

## Alternativas consideradas

1. **SPA React + API Laravel para todo** — máxima flexibilidad de UI, pero
   triplica el trabajo: hay que reconstruir a mano CRUD, tablas, RBAC y forms
   que Filament da casi gratis. El cuello de botella del proyecto es modelar el
   dominio, no la UI. Descartada para el núcleo.
2. **Multi-tenant activo desde el MVP (`teams` de Shield)** — evita una
   migración futura, pero añade complejidad (aislamiento, pruebas, UX de
   selección de tenant) que no aporta valor al primer cliente único. Se difiere,
   pero se deja el esquema preparado.
3. **Otro framework / stack distinto** — descartado: el equipo ya es productivo
   en Filament y el ecosistema Laravel cubre todas las necesidades del catálogo.

## Consecuencias

- **Más fácil:** velocidad de desarrollo del back-office, reutilización de
  patrones Filament, una sola base de lenguaje (PHP) para casi todo.
- **Más difícil / deuda asumida:** algunas pantallas muy custom o cara-a-cliente
  requerirán la capa React; hay que mantener dos paradigmas de frontend.
- **A revisar al activar multi-tenancy:** los Global Scopes, la resolución de
  tenant por usuario, y la activación del modo `teams` en Shield. El esquema ya
  lo soporta; será trabajo de configuración y pruebas, no de migración de datos
  estructural.
