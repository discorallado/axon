# Convenciones — Axon PMIS

Guía de estilo y patrones para todo el código del proyecto. Claude Code y
cualquier colaborador humano deben seguirla. Complementa la sección 7 de
`CLAUDE.md`.

## PHP / Laravel

- PSR-12. Ejecutar `./vendor/bin/pint` antes de cada commit.
- PHP 8.2+: usa tipos de retorno, propiedades tipadas, enums nativos y
  `readonly` donde aplique.
- Validación en **Form Requests** o en el schema de Filament. Nunca validar
  dentro del controlador o recurso si puede ir en un Request.
- Lógica de negocio en **Actions** o **Services**, no en controladores ni en
  modelos gordos. Modelos = relaciones, casts, scopes; no orquestación.
- Migraciones siempre reversibles (`down()` real). Prohibido editar una
  migración ya corrida en `main`: crea una nueva.
- Enums de PHP para estados/tipos fijos. Tablas para los configurables por el
  usuario (estados de tarea, prioridades).
- Nada de N+1: usa `with()` / eager loading. Agrega índices a columnas que se
  filtran u ordenan.

## Multi-tenancy (multi-tenant-ready)

- TODA entidad de negocio lleva `organization_id` (FK a `organizations`).
- Cada modelo con `organization_id` usa un **Global Scope** de tenant. En
  single-tenant el scope es transparente (una sola organización), pero existe
  desde el día uno para no migrar después.
- Nunca consultes una entidad de negocio sin que pase por el scope de tenant.
- El modo `teams` de Filament Shield queda DESACTIVADO hasta la fase de
  multi-tenancy activo.

## Permisos (RBAC)

- Roles base: `super_admin`, `ingeniero`, `supervisor`, `tecnico`, `calidad`.
- Todo recurso Filament tiene su **Policy**. Todo permiso se registra en Shield.
- `super_admin` tiene acceso total vía Gate global; el resto, por permiso
  explícito.

## Comentarios y auditoría

- Sistema de comentarios **polimórfico** (`commentable`) desde el inicio, vía
  trait reutilizable. Tareas y actividades lo usan; futuras entidades también.
- Entidades clave registran auditoría (quién, qué, cuándo).

## Filament

- Un Resource por entidad principal; RelationManagers para las hijas.
- Formularios: agrupa con secciones/pestañas; usa `->required()`,
  reglas y helper text. Nada de campos sin validar.
- Tablas: columnas con `->searchable()` / `->sortable()` donde tenga sentido;
  filtros para estado, proyecto, responsable, fechas.
- Acciones masivas e individuales con confirmación en las destructivas.

## Internacionalización

- Idioma UI: español (es-CL). Todo texto visible va en `lang/es/`.
- Fechas/horas en zona `America/Santiago`.

## Tests (Pest)

- Un feature test por flujo de usuario relevante.
- Factories para todo modelo. Seeders para datos base (roles, estados,
  prioridades).
- Al arreglar un bug, agrega un test de regresión que capture el escenario.
- Meta: la suite verde es condición para cerrar cualquier fase.

## Git

- Commits pequeños y descriptivos (imperativo: "agrega...", "corrige...").
- Una unidad de trabajo = un PR. PR con descripción de qué/por qué/cómo probar.
- Nunca `push --force` ni merge a producción sin aprobación explícita.
