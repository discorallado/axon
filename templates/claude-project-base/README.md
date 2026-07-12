# Base de configuración Claude Code — plantilla de proyecto

Esta carpeta es una base reutilizable para arrancar proyectos nuevos
Laravel/Filament con Claude Code, extraída de la configuración usada en
Axon (`CLAUDE.md`, comandos de rol en `.claude/commands/`, permisos en
`.claude/settings.local.json`).

## Cómo usarla en un proyecto nuevo

1. Copia el contenido de esta carpeta a la raíz del proyecto nuevo:
   - `CLAUDE.md`
   - `.claude/commands/*.md`
   - `.claude/settings.local.json`
   - `docs/adr/` y `docs/requerimientos/` (carpetas vacías con su estructura)
2. Abre `CLAUDE.md` y reemplaza todos los bloques `{{...}}`:
   - Sección 1: qué es el proyecto.
   - Sección 2: idioma y zona horaria (el stack Laravel/Filament ya viene
     fijado por defecto).
   - Sección 3: jerarquía de entidades del dominio.
   - Sección 5: primer requerimiento a construir.
   - Sección 7: locale de `lang/`.
3. Ajusta `.claude/commands/ingeniero.md` si el locale no es `es`.
4. Crea `SESSION.md` en la raíz (puede empezar vacío o con las secciones
   descritas en la sección 10 de `CLAUDE.md`: estado actual, decisiones,
   próximo paso concreto).
5. Borra este README o adáptalo al proyecto nuevo.

## Qué NO se copió

- Contenido específico del dominio de Axon (construcción eléctrica, tableros
  eléctricos, catálogo de 12 módulos) — eso vive en `docs/catalogo-y-mvp.md`
  y `docs/requerimientos/` de Axon, no en la plantilla.
- El primer requerimiento de Axon (`docs/requerimientos/0001-...`).
- ADRs ya tomados en Axon.

## Roles incluidos

`/arquitecto`, `/ingeniero`, `/qa`, `/revisor`, `/release`, `/seguro` — mismo
método de trabajo de Axon (proponer antes de implementar, ADRs, un PR por
unidad de trabajo, `SESSION.md` entre sesiones), generalizado para no asumir
el dominio específico del proyecto.
