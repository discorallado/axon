# CLAUDE.md — {{PROJECT_NAME}}

> Instrucciones persistentes para Claude Code en este repositorio. Léelas al
> inicio de cada sesión. Este archivo define el proyecto, el stack, el método
> de trabajo y los "roles" que adoptas según la etapa.
>
> Plantilla base derivada del proyecto Axon. Antes de usarla en un proyecto
> nuevo, reemplaza todos los bloques `{{ASI}}` y borra este comentario.

---

## 1. Qué es este proyecto

{{DESCRIPCION_DEL_PROYECTO — una o dos frases: qué es, para qué industria o
usuario, si reemplaza algo existente o parte desde cero.}}

{{Si existe un documento de visión (catálogo de módulos, MVP, roadmap),
referencia su ruta aquí, ej. `docs/catalogo-y-mvp.md`. Léelo antes de proponer
arquitectura.}}

## 2. Stack obligatorio

- Backend: **PHP 8.2+ · Laravel 12**.
- Back-office: **Filament 5** (recursos, páginas, widgets, RBAC). Es la mayor
  parte de la UI interna.
- Interactividad / tiempo real: **Livewire + Laravel Reverb**.
- Cara-a-cliente y campo (si aplica): **React/Vite PWA** solo para portal de
  cliente o herramientas de campo que no calcen en Filament.
- Permisos: `spatie/laravel-permission` + `bezhansalleh/filament-shield`.
- Excel: `maatwebsite/excel` (import y export `.xls`/`.xlsx`/`.csv`).
- Datos: MySQL o PostgreSQL; adjuntos en S3 (o disco local en dev).
- Calidad: **Pest** (tests), **Pint** (estilo), **Larastan** (análisis
  estático).
- UI en **{{IDIOMA (ej. es-CL)}}**. Zona horaria **{{ZONA_HORARIA}}**.

No introduzcas otros frameworks, ni reemplaces Filament por una SPA, ni
cambies el ORM. Si crees que una desviación del stack se justifica,
PROPONLA y espera aprobación; no la implementes por tu cuenta.

## 3. Principios de arquitectura

1. **Modela el dominio primero.** Jerarquía núcleo del dominio:
   {{JERARQUIA_DE_ENTIDADES, ej. Organización → Cliente → Programa →
   Proyecto → Actividad → Tarea → Subtarea}}.
2. **Single-tenant en operación, multi-tenant-ready en diseño.** Por defecto,
   toda entidad relevante lleva `organization_id` desde el día uno, aunque el
   modo `teams` de Shield no esté activo todavía. El objetivo es poder migrar
   a multi-tenant después sin reescribir el esquema. Ajusta esto si el
   proyecto no lo necesita.
3. **Todo registro relevante es auditable.** Donde aplique, diséñalo para ser
   versionable y, si el dominio lo exige, firmable (hash de integridad).
4. **Entidades extensibles.** Estados, prioridades, roles y tipos son
   configurables en base de datos cuando el negocio los cambia con
   frecuencia; nunca hardcodeados en ese caso.
5. **Comentarios/adjuntos polimórficos desde el inicio** (`commentable`,
   `attachable`) si más de una entidad los va a necesitar, para que se
   hereden vía trait en vez de repetirse por entidad.
6. Prefiere paquetes maduros del ecosistema Laravel/Filament antes de
   construir a mano.

## 4. Método de trabajo (CRÍTICO — no lo omitas)

El desarrollo se organiza **por requerimientos**, no por un roadmap rígido.
El usuario decide qué requerimiento se ataca y cuándo; no estás obligado a
seguir una secuencia de fases. Un roadmap de referencia (si existe) es un
**mapa**, no un orden obligatorio. Cuando se pida un requerimiento nuevo,
trabájalo aunque no toque ese roadmap.

Lo que SÍ se mantiene siempre, sea cual sea el requerimiento:

- **Propón antes de implementar.** Para cada módulo o entidad nueva, primero
  presenta el diseño (modelo de datos, relaciones, máquina de estados,
  pantallas Filament, decisiones técnicas con trade-offs y alternativas) y
  **espera el visto bueno antes de escribir código.** Para esto se adopta
  `/arquitecto`; para implementar lo aprobado, `/ingeniero`.
- **Una unidad de trabajo = un PR**, con migraciones, modelos, recursos
  Filament, policies, factories/seeders y tests Pest.
- **ADR por decisión arquitectónica** en `docs/adr/` (contexto, decisión,
  alternativas descartadas).
- **No cierres un requerimiento** sin tests en verde (`./vendor/bin/pest`) y
  Pint/Larastan limpios.
- Respeta SIEMPRE los principios de la sección 3, aunque el requerimiento sea
  pequeño o aislado.
- Cada requerimiento nuevo se documenta como un archivo en
  `docs/requerimientos/` con su alcance y criterios de aceptación, antes de
  diseñar.
- Commits pequeños y descriptivos. Nunca `git push --force` ni tocar ramas de
  producción sin pedirlo explícitamente.

## 5. Requerimiento actual y alcance del producto

{{REQUERIMIENTO_EN_CURSO — describe el primer requerimiento a construir y
enlaza su documento en `docs/requerimientos/000X-nombre.md`.}}

{{MAPA_DE_REFERENCIA (opcional) — si existe una visión de producto más amplia
(MVP, catálogo de módulos), resúmela aquí y dej claro que es un mapa de
referencia, NO el orden de trabajo obligatorio.}}

## 6. Roles por etapa

Cuando se pida un rol, adopta SOLO ese foco hasta que cambie. Esto reduce
ciclos de revisión y mantiene la salida enfocada.

- **/arquitecto** — Diseña modelo de datos, relaciones, máquinas de estado y
  recursos Filament. NO escribe código de implementación; entrega el diseño
  para validar.
- **/ingeniero** — Implementa el módulo ya aprobado: migraciones, modelos,
  recursos, policies. Sigue las convenciones de Laravel y de este archivo.
- **/qa** — Solo pruebas. Escribe/ejecuta tests Pest, busca casos borde,
  reporta fallas. No agrega features.
- **/revisor** — Revisa un diff o PR: seguridad, N+1, fugas de tenant
  (`organization_id`), permisos faltantes, cobertura de tests. Señala, no
  reescribe salvo que se le pida.
- **/release** — Prepara la entrega: changelog, notas de migración, checklist
  de despliegue. No toca lógica de negocio.
- **/seguro** — Modo prevención de accidentes: pide confirmación antes de
  comandos destructivos y puede restringir ediciones a una ruta.

## 7. Convenciones Laravel/Filament para este repo

- Sigue PSR-12; ejecuta `./vendor/bin/pint` antes de cada commit.
- Form Requests para validación; nunca validar dentro del controlador/recurso
  si se puede en un Request o en el schema de Filament.
- Cada modelo con `organization_id` usa un Global Scope de tenant
  (transparente en single-tenant, activable después).
- Migraciones reversibles (`down()` real). Nada de editar migraciones ya
  corridas en main; crea una nueva.
- Enums de PHP para estados/tipos fijos; tablas para los configurables.
- Texto visible al usuario va por archivos de traducción (`lang/{{LOCALE}}/`),
  no hardcodeado.
- Tests: un feature test por flujo de usuario; factories para todo modelo.

## 8. Seguridad operativa

- Nunca pongas secretos en el repo. Usa `.env` y `config/`.
- Nunca ejecutes comandos destructivos (`rm -rf`, `migrate:fresh` en datos
  reales, `DROP`, `push --force`) sin confirmación explícita.
- No conectes el agente a bases de datos o servicios de PRODUCCIÓN durante el
  desarrollo. Trabaja contra SQLite/MySQL local o de staging.
- Mantén Claude Code actualizado.

## 9. Tu primer paso al iniciar

Si el repo está vacío o en fase inicial, NO escribas código todavía. Adopta el
rol **/arquitecto** y propón el diseño del MVP (Fase 0 + Fase 1):

1. Diagrama de entidades y relaciones (incluyendo `organization_id` si
   aplica).
2. Máquinas de estado de las entidades principales del dominio.
3. Modelo de dependencias/relaciones clave y cómo alimentan cualquier vista
   agregada (Gantt, Kanban, etc.) si el producto lo requiere.
4. Lista de recursos/páginas Filament y matriz de permisos por rol.
5. Decisiones técnicas con alternativas (paquetes, estrategia de tiempo real,
   modelado multi-tenant-ready, librería de import/export, etc.).

Se revisa junto al usuario y, con su visto bueno, se comienza a implementar
la Fase 0.

## 10. Gestión de contexto entre sesiones

Este repo usa `SESSION.md` en la raíz para preservar el estado de trabajo
entre sesiones de Claude Code y evitar perder contexto al limpiar el
historial.

**Al INICIO de cada sesión de trabajo:**
- Leer `SESSION.md` antes de empezar a trabajar, si existe.
- Confirmar brevemente con el usuario que el "Próximo paso concreto"
  registrado sigue siendo válido antes de continuar.

**Al FINAL de cada sesión de trabajo** (o cuando el usuario lo pida, o cuando
se note que la conversación se está volviendo muy larga):
- Mover el contenido actual de las secciones de estado a "Historial de
  sesiones anteriores" (como bloque `<details>` colapsado, con fecha y
  módulo).
- Completar de nuevo las secciones de estado con el estado real al cierre:
  completado, a mitad de camino, decisiones tomadas, archivos tocados, dudas
  abiertas, y el próximo paso concreto.
- No usar lenguaje vago en "Próximo paso concreto" — debe ser una acción
  ejecutable sin ambigüedad.
- Avisar al usuario que `SESSION.md` fue actualizado, antes de que cierre o
  limpie la conversación.

**No usar SESSION.md para:**
- Decisiones de arquitectura permanentes → eso va en un ADR (`docs/adr/`).
- Convenciones de código → eso va en este mismo `CLAUDE.md`.
- `SESSION.md` es solo el "estado en proceso", se reescribe constantemente.
