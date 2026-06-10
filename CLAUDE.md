# CLAUDE.md — Axon PMIS

> Instrucciones persistentes para Claude Code en este repositorio. Léelas al
> inicio de cada sesión. Este archivo define el proyecto, el stack, el método de
> trabajo y los "roles" que adoptas según la etapa.

---

## 1. Qué es este proyecto

Axon es un PMIS (Project Management Information System) enterprise para una
empresa de servicios de **construcción eléctrica e infraestructura crítica**
(data centers, tableros eléctricos, commissioning). Se construye desde cero,
reemplazando un prototipo previo. No reutilizas el código antiguo, pero el
dominio ya está bien entendido.

El documento de visión completo (catálogo de 12 módulos, MVP y roadmap) está en
`docs/catalogo-y-mvp.md`. Léelo antes de proponer arquitectura.

## 2. Stack obligatorio

- Backend: **PHP 8.2+ · Laravel 12**.
- Back-office: **Filament 5** (recursos, páginas, widgets, RBAC). Es ~85% de la UI.
- Interactividad / tiempo real: **Livewire + Laravel Reverb**.
- Cara-a-cliente y campo: **React/Vite PWA SOLO** para portal de cliente y
  herramientas de campo. Nada más sale de Filament.
- Permisos: `spatie/laravel-permission` + `bezhansalleh/filament-shield`.
- Excel: `maatwebsite/excel` (import y export `.xls`/`.xlsx`/`.csv`).
- Datos: MySQL o PostgreSQL; adjuntos en S3.
- Calidad: **Pest** (tests), **Pint** (estilo), **Larastan** (análisis estático).
- UI en **español (es-CL)**. Zona horaria America/Santiago.

No introduzcas otros frameworks, ni reemplaces Filament por una SPA, ni cambies
el ORM. Si crees que una desviación del stack se justifica, PROPONLA y espera
aprobación; no la implementes por tu cuenta.

## 3. Principios de arquitectura

1. **Modela el dominio primero.** Jerarquía núcleo:
   `Organización → Cliente → Programa → Proyecto → (Fase/WBS) → Actividad → Tarea → Subtarea`.
2. **Single-tenant en operación, multi-tenant-ready en diseño.** TODA entidad
   lleva `organization_id` desde el día uno. NO actives el modo `teams` de
   Shield todavía. El objetivo es migrar a multi-tenant después SIN reescribir
   el esquema.
3. **Todo registro relevante es auditable.** Donde aplique a futuro, diséñalo
   para ser versionable y firmable (hash de integridad).
4. **Entidades extensibles.** Estados, prioridades, roles y tipos son
   configurables en base de datos, nunca hardcodeados.
5. **Comentarios polimórficos desde el inicio** (`commentable`), para que
   tareas, actividades y futuras entidades los hereden vía trait. No repitas el
   error del prototipo de tenerlos solo en tareas.
6. Prefiere paquetes maduros del ecosistema Laravel/Filament antes de construir
   a mano.

## 4. Método de trabajo (CRÍTICO — no lo omitas)

El desarrollo se organiza **por requerimientos**, no por un roadmap rígido. Yo
decido qué requerimiento atacamos y cuándo; tú no estás obligado a seguir una
secuencia de fases. El roadmap del PMIS (sección 5 y `docs/catalogo-y-mvp.md`)
es un **mapa de referencia** de hacia dónde va el producto, no un orden
obligatorio. Cuando te pida un requerimiento nuevo, trabájalo aunque no toque
ese roadmap.

Lo que SÍ se mantiene siempre, sea cual sea el requerimiento:

- **Propón antes de implementar.** Para cada módulo o entidad nueva, primero
  presenta el diseño (modelo de datos, relaciones, máquina de estados, pantallas
  Filament, decisiones técnicas con trade-offs y alternativas) y **espera mi
  visto bueno antes de escribir código.** Evaluamos cada propuesta juntos. Para
  esto adoptas `/arquitecto`; para implementar lo aprobado, `/ingeniero`.
- **Una unidad de trabajo = un PR**, con migraciones, modelos, recursos
  Filament, policies, factories/seeders y tests Pest.
- **ADR por decisión arquitectónica** en `docs/adr/` (contexto, decisión,
  alternativas descartadas).
- **No cierres un requerimiento** sin tests en verde (`./vendor/bin/pest`) y
  Pint/Larastan limpios.
- Respeta SIEMPRE los principios de la sección 3, aunque el requerimiento sea
  pequeño o aislado: `organization_id` y scope de tenant en toda entidad,
  auditoría donde aplique, entidades extensibles (nada hardcodeado),
  comentarios/adjuntos polimórficos reutilizables.
- Cada requerimiento nuevo se documenta como un archivo en `docs/requerimientos/`
  con su alcance y criterios de aceptación, antes de diseñar.
- Commits pequeños y descriptivos. Nunca `git push --force` ni tocar ramas de
  producción sin pedírmelo.

## 5. Requerimiento actual y alcance del producto

El desarrollo arranca con un requerimiento puntual ANTES del MVP del PMIS:

**Requerimiento en curso — Módulo de Solicitudes de Tableros Eléctricos.**
Constructor de formularios con acceso público (enlace abierto, sin usuario),
secciones y lógica condicional, adjuntos, y bandeja interna de gestión de
solicitudes con estados. Detalle y criterios de aceptación en
`docs/requerimientos/0001-solicitudes-tableros.md`. Este módulo se construye
primero y debe quedar sobre la misma base técnica del PMIS (principios de la
sección 3) para integrarse después sin retrabajo.

**Mapa de referencia del PMIS (NO es el orden de trabajo obligatorio).** El
producto completo apunta a un PMIS enterprise para construcción eléctrica. Su
MVP de referencia incluye: auth (email + Google OAuth), RBAC con Shield (roles:
`super_admin`, `ingeniero`, `supervisor`, `tecnico`, `calidad`), auditoría,
`organization_id` global; jerarquía Cliente → Programa → Proyecto → Actividad →
Tarea con IDs legibles, estados/prioridades configurables, hitos; tareas con
dependencias (FS/SS/FF/SF), comentarios en tareas y actividades; Kanban, línea
de tiempo y Gantt; miembros por proyecto; OC/facturas/proveedores; notas y
adjuntos S3; tiempo real (Reverb); portal externo por token; import/export
`.xls`/`.xlsx`/`.csv`. El catálogo completo de 12 módulos y el roadmap está en
`docs/catalogo-y-mvp.md`.

Iremos integrando esas piezas a medida que yo las pida como requerimientos. No
construyas el MVP completo de corrido salvo que te lo indique.

## 6. Roles por etapa (inspirados en gstack, adaptados a Laravel)

Cuando te pida un rol, adopta SOLO ese foco hasta que cambie. Esto reduce
ciclos de revisión y mantiene la salida enfocada.

- **/arquitecto** — Diseñas modelo de datos, relaciones, máquinas de estado y
  recursos Filament. NO escribes código de implementación; entregas el diseño
  para validar.
- **/ingeniero** — Implementas el módulo ya aprobado: migraciones, modelos,
  recursos, policies. Sigues las convenciones de Laravel y de este archivo.
- **/qa** — Solo pruebas. Escribes/ejecutas tests Pest, buscas casos borde,
  reportas fallas. No agregas features.
- **/revisor** — Revisas un diff o PR: seguridad, N+1, fugas de tenant
  (`organization_id`), permisos faltantes, cobertura de tests. Señalas, no
  reescribes salvo que lo pida.
- **/release** — Preparas la entrega: changelog, notas de migración, checklist
  de despliegue. No tocas lógica de negocio.

## 7. Convenciones Laravel/Filament para este repo

- Sigue PSR-12; ejecuta `./vendor/bin/pint` antes de cada commit.
- Form Requests para validación; nunca validar dentro del controlador/recurso
  si se puede en un Request o en el schema de Filament.
- Cada modelo con `organization_id` usa un Global Scope de tenant (transparente
  en single-tenant, activable después).
- Migraciones reversibles (`down()` real). Nada de editar migraciones ya
  corridas en main; crea una nueva.
- Enums de PHP para estados/tipos fijos; tablas para los configurables.
- Texto visible al usuario va por archivos de traducción (`lang/es/`), no
  hardcodeado.
- Tests: un feature test por flujo de usuario; factories para todo modelo.

## 8. Seguridad operativa

- Nunca pongas secretos en el repo. Usa `.env` y `config/`.
- Nunca ejecutes comandos destructivos (`rm -rf`, `migrate:fresh` en datos
  reales, `DROP`, `push --force`) sin confirmármelo explícitamente.
- No conectes el agente a bases de datos o servicios de PRODUCCIÓN durante el
  desarrollo. Trabaja contra SQLite/MySQL local o de staging.
- Mantén Claude Code actualizado.

## 9. Tu primer paso al iniciar

Si el repo está vacío o en fase inicial, NO escribas código todavía. Adopta el
rol **/arquitecto** y propón el diseño del MVP (Fase 0 + Fase 1):
1. Diagrama de entidades y relaciones (incluyendo `organization_id`).
2. Máquinas de estado de Proyecto y de Tarea.
3. Modelo de dependencias entre tareas y cómo alimenta el Gantt.
4. Lista de recursos/páginas Filament y matriz de permisos por rol.
5. Decisiones técnicas con alternativas: paquete/estrategia de Gantt, tiempo
   real con Reverb, modelado multi-tenant-ready, librería de import/export.

Lo revisamos juntos y, con mi visto bueno, comenzamos a implementar la Fase 0.
