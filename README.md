# Scaffold de arranque — Axon PMIS

Esta carpeta contiene la base de archivos para arrancar el desarrollo del PMIS
con Claude Code. Copia su contenido a la raíz de tu repositorio nuevo.

## Qué hay aquí

```
.
├── CLAUDE.md                      # Instrucciones persistentes que Claude Code lee al inicio
├── docs/
│   ├── catalogo-y-mvp.md          # Visión completa: 12 módulos, MVP, roadmap y prompt
│   ├── convenciones.md            # Estilo y patrones de código del proyecto
│   └── adr/
│       ├── 0000-plantilla.md      # Plantilla para registrar decisiones
│       └── 0001-stack-y-multitenancy.md  # Primera decisión, ya redactada
└── .claude/
    └── commands/                  # Slash commands propios (roles por etapa)
        ├── arquitecto.md          # /arquitecto — diseña sin implementar
        ├── ingeniero.md           # /ingeniero — implementa lo aprobado
        ├── revisor.md             # /revisor — audita diff/PR
        ├── qa.md                  # /qa — prueba flujos
        ├── release.md             # /release — prepara la entrega
        └── seguro.md              # /seguro — guardarraíles de seguridad
```

## Cómo usarlo

### 1. Crea el proyecto Laravel (si aún no existe)

```bash
composer create-project laravel/laravel axon
cd axon
composer require filament/filament
composer require bezhansalleh/filament-shield spatie/laravel-permission
composer require maatwebsite/excel
composer require --dev pestphp/pest pestphp/pest-plugin-laravel larastan/larastan
```

### 2. Copia el scaffold a la raíz del repo

Copia `CLAUDE.md`, la carpeta `docs/` y la carpeta `.claude/` a la raíz de tu
proyecto. Los slash commands en `.claude/commands/` quedan disponibles
automáticamente en Claude Code (escribe `/` para verlos).

### 3. Arranca con Claude Code

Desde la raíz del repo, abre Claude Code. Leerá `CLAUDE.md` solo. Luego:

```
/arquitecto diseña el modelo de datos del MVP (Fase 0 + Fase 1)
```

Claude propondrá el diseño SIN escribir código. Lo revisas, ajustan juntos, y
cuando lo apruebes:

```
/ingeniero implementa la Fase 0 (auth, RBAC, organization_id, auditoría)
```

Antes de abrir cada PR:

```
/revisor
/qa
```

Y para preparar la entrega:

```
/release
```

Para trabajo cerca de datos sensibles o destructivo, activa primero:

```
/seguro
```

## Notas

- Los slash commands son archivos Markdown nativos de Claude Code; no dependen
  de herramientas externas (no usan gstack ni su runtime). Están inspirados en
  el *patrón* de roles de gstack, pero escritos para Laravel/Filament.
- Si quieres además las skills de seguridad reales de gstack (`/careful`,
  `/freeze`, `/guard`), puedes instalarlas aparte en `~/.claude/skills/`; son
  agnósticas al stack. Aquí ya tienes un equivalente propio en `/seguro`.
- El método de trabajo central está en la sección 4 de `CLAUDE.md`: **proponer
  antes de implementar, por fases, un PR por módulo, ADR por decisión.**
