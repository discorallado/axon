# Axon PMIS — Catálogo de capacidades, MVP y prompt para agente

> **Propósito de este documento.** Servir como mapa de dominio y guía de construcción para desarrollar desde cero un PMIS (Project Management Information System) enterprise orientado a construcción eléctrica e infraestructura crítica (data centers, tableros, commissioning). Está pensado para entregarse a un agente de IA de desarrollo.
>
> **Stack objetivo.** PHP 8.2+ · Laravel 12 · Filament 4 (back-office) · Livewire + Laravel Reverb (interactividad y tiempo real) · React/Vite PWA solo para portal de cliente y herramientas de campo · spatie/laravel-permission + filament-shield · MySQL/PostgreSQL · S3 · maatwebsite/excel · Pest/Pint/Larastan · UI en español (es-CL).
>
> **Decisión de arquitectura clave.** El back-office se construye en Filament (CRUD, tablas densas, RBAC y formularios complejos "casi gratis"). React/PWA se reserva para lo cara-a-cliente y de campo. Se diseña **single-tenant pero multi-tenant-ready**: todas las entidades llevan `organization_id` desde el día uno; el modo `teams` de Shield se activa más adelante sin migración traumática.

---

## Convención de estado

| Marca | Significado |
|-------|-------------|
| ✓ | Ya existe en el sistema actual (paridad a conservar) |
| ◐ | Parcial o pendiente cercano (debe completarse) |
| ＋ | Nuevo, a incorporar en la visión objetivo |
| **[MVP]** | Incluido en el alcance del primer release |

---

## 1. Núcleo de gestión de proyectos

- ✓ **[MVP]** Jerarquía Cliente → Programa → Proyecto → Actividad → Tarea
- ✓ **[MVP]** Proyectos con color, fechas, estado y fijado (pin)
- ✓ **[MVP]** Identificadores legibles por proyecto (prefijo + correlativo, p. ej. `SCL03-AB12CD`)
- ＋ **[MVP]** Fases / hitos (milestones) con fecha comprometida vs. real
- ＋ Portafolios / agrupación multi-programa para vista ejecutiva
- ＋ Plantillas de proyecto (clonar estructura típica de obra o tablero)
- ＋ Baseline de cronograma (línea base congelada para medir desviación)
- ＋ WBS formal (estructura de desglose de trabajo) con codificación jerárquica

## 2. Tareas y planificación

- ✓ **[MVP]** Tareas con responsables múltiples, prioridad, fecha de inicio y fecha límite
- ✓ **[MVP]** Estados configurables con color, orden y bandera de completado
- ✓ **[MVP]** Historial de cambios por tarea
- ◐ **[MVP]** Comentarios extendidos a Actividades (hoy solo en tareas; debe agregarse el trait comentable a `Activity`)
- ＋ **[MVP]** Dependencias entre tareas (FS, SS, FF, SF) — base para Gantt y ruta crítica
- ＋ **[MVP]** Subtareas / checklist dentro de una tarea
- ＋ Cálculo y resaltado de ruta crítica
- ＋ Tareas recurrentes y plantillas de tarea
- ＋ Estimación vs. horas reales (esfuerzo)
- ＋ Etiquetas / categorías transversales

## 3. Visualización

- ✓ **[MVP]** Tablero Kanban por proyecto (columnas por estado, drag-and-drop)
- ✓ **[MVP]** Línea de tiempo de proyectos y de tareas
- ＋ **[MVP]** Gantt interactivo con dependencias y arrastre de barras
- ＋ Vista de calendario
- ＋ Vista de carga de trabajo por persona (workload / capacity)
- ＋ Tablero a nivel portafolio (cross-project)

## 4. Recursos y equipo

- ✓ **[MVP]** Miembros del equipo por proyecto
- ◐ **[MVP]** Asignación del rol del sistema a cada miembro dentro del proyecto (membresía existe; falta vincular el rol global por miembro-proyecto)
- ✓ Ranking de rendimiento / contribuciones de usuario
- ＋ Gestión de capacidad y disponibilidad (calendarios laborales, feriados CL)
- ＋ Roles/oficios de obra (eléctrico, instrumentista, supervisor, calidad)
- ＋ Asignación de cuadrillas y turnos
- ＋ Registro de horas (timesheets) con flujo de aprobación

## 5. Costos y finanzas

- ✓ **[MVP]** Órdenes de compra y facturas (vínculo polimórfico a cliente/proveedor/programa/proyecto)
- ✓ **[MVP]** Proveedores
- ＋ Presupuesto del proyecto por partidas / centros de costo
- ＋ Control de avance físico vs. financiero — valor ganado (EVM: PV, EV, AC, CPI, SPI)
- ＋ Estados de pago / certificaciones de avance de obra
- ＋ Gestión de contratos y subcontratos
- ＋ Órdenes de cambio (change orders) con impacto en costo y plazo
- ＋ Flujo de caja proyectado del proyecto
- ＋ Multimoneda con tipo de cambio histórico

## 6. Documentación y control documental

- ✓ **[MVP]** Notas de proyecto
- ✓ **[MVP]** Adjuntos en S3
- ＋ Gestión documental con versiones y estados (borrador / emitido / superado)
- ＋ Transmittals (envío formal de documentos con acuse de recibo)
- ＋ Planos y revisiones (control de revisión A/B/C, "as-built")
- ＋ RFI (solicitudes de información) con flujo y plazos
- ＋ Submittals / aprobación de materiales
- ＋ Anotación de planos integrada (PWA: pin-anchoring, comentarios con hilos, adjuntos)

## 7. Calidad y commissioning

- ＋ Checklists / protocolos FAT-SAT reutilizables (librería → plantilla → ejecución)
- ＋ Punch list / listas de pendientes y no-conformidades (NC)
- ＋ Inspecciones de campo con evidencia fotográfica
- ＋ Protocolos de prueba (luminarias, enchufes, cableado) con trazabilidad por zona
- ＋ Firmas digitales / integridad por hash (SHA-256) de registros
- ＋ Flujo de aprobación calidad → cliente

## 8. Riesgos, seguridad e incidencias

- ＋ Registro de riesgos (probabilidad / impacto, plan de mitigación)
- ＋ Registro de incidencias/issues con escalamiento
- ＋ HSE: reporte de incidentes, charlas de seguridad, permisos de trabajo
- ＋ Lecciones aprendidas

## 9. Colaboración y comunicación

- ✓ **[MVP]** Comentarios con menciones, reacciones y adjuntos
- ✓ **[MVP]** Notificaciones in-app
- ＋ **[MVP]** Tiempo real (Laravel Reverb) en Kanban y comentarios
- ＋ Notificaciones por email / push / digest periódico
- ＋ Bitácora / diario de obra (daily logs)
- ＋ Reuniones y actas con acuerdos rastreables

## 10. Portal de cliente y acceso externo

- ✓ **[MVP]** Acceso externo por token + contraseña, dashboard de solo lectura
- ＋ Portal con aprobaciones del cliente (documentos, estados de pago)
- ＋ Branding por cliente
- ＋ Compartir reportes/dashboards con enlace seguro y expiración

## 11. Reportería y BI

- ✓ **[MVP]** Widgets de estadísticas y gráficos (totales, tendencia, tareas por proyecto, actividad reciente)
- ◐ **[MVP]** Import/Export en `.xls`, `.xlsx` y `.csv` (hoy solo export parcial; falta importación y cobertura de los tres formatos)
- ＋ Dashboards configurables por rol
- ＋ Reportes exportables a PDF/Excel con plantillas corporativas
- ＋ KPIs de portafolio (salud de proyectos, semáforos)
- ＋ Programador de reportes (envío automático periódico)

## 12. Administración, seguridad y plataforma

- ✓ **[MVP]** RBAC con Filament Shield
- ✓ **[MVP]** Autenticación email/contraseña + Google OAuth
- ＋ **[MVP]** Auditoría global (quién cambió qué y cuándo) en entidades clave
- ＋ **[MVP]** `organization_id` en todas las entidades (multi-tenant-ready, sin activar `teams` aún)
- ＋ Multi-tenancy activo (modo `teams`) por organización
- ＋ Permisos a nivel de campo/registro, no solo de recurso
- ＋ SSO empresarial (Azure AD / SAML)
- ＋ API REST documentada + webhooks
- ＋ App móvil / PWA offline para campo
- ＋ Integraciones: SharePoint, MS Project (import/export), correo, almacenamiento
- ＋ Internacionalización (es-CL), zona horaria y feriados locales
- ＋ Logs, backups, monitoreo, rate limiting

---

## Definición del MVP (primer release)

**Objetivo del MVP:** lograr paridad funcional con el sistema actual **más** dependencias de tareas y vista Gantt, sobre una base técnica nueva, limpia y *multi-tenant-ready*. Single-tenant en operación, pero modelado para escalar a multi-empresa sin migración traumática.

### Incluido en el MVP

1. **Fundaciones**: auth (email + Google OAuth), RBAC con Shield, auditoría en entidades clave, `organization_id` en todo el esquema, settings.
2. **Núcleo PM**: jerarquía Cliente → Programa → Proyecto → Actividad → Tarea; IDs legibles; estados y prioridades configurables; hitos.
3. **Tareas**: responsables múltiples, fechas, historial, subtareas/checklist, **dependencias (FS/SS/FF/SF)**, comentarios (tareas **y** actividades).
4. **Visualización**: Kanban, línea de tiempo y **Gantt interactivo con dependencias**.
5. **Equipo**: miembros por proyecto con rol del sistema asignado por miembro-proyecto.
6. **Finanzas básicas**: OC, facturas y proveedores (paridad actual, sin EVM ni presupuesto aún).
7. **Documentos**: notas de proyecto y adjuntos en S3.
8. **Colaboración**: comentarios con menciones/reacciones/adjuntos, notificaciones in-app, tiempo real (Reverb) en Kanban y comentarios.
9. **Portal externo**: acceso por token, dashboard de solo lectura.
10. **Reportería**: widgets actuales + **import/export `.xls`/`.xlsx`/`.csv`**.

### Explícitamente fuera del MVP (fases posteriores)

WBS formal, baseline/ruta crítica, EVM y presupuesto por partidas, estados de pago, contratos y órdenes de cambio, control documental versionado, RFI/submittals/transmittals, módulo de calidad/commissioning completo (FAT-SAT, punch list, inspecciones), riesgos/HSE, multi-tenancy activo, SSO, API pública, PWA offline e integraciones externas.

### Criterios de aceptación del MVP

- Un usuario interno puede gestionar el ciclo completo: crear cliente → programa → proyecto → actividades → tareas, asignarlas, moverlas en Kanban y verlas en Gantt con dependencias.
- Las dependencias entre tareas se respetan visualmente en el Gantt y se pueden crear/editar.
- Un cliente externo entra por token y ve el dashboard de solo lectura de su proyecto.
- Se pueden importar y exportar tareas en los tres formatos de planilla.
- Todo cambio relevante queda auditado y todas las entidades llevan `organization_id`.
- RBAC operativo con al menos los roles: `super_admin`, `ingeniero`, `supervisor`, `tecnico`, `calidad`.

---

## Roadmap por fases

| Fase | Foco | Entregable principal |
|------|------|----------------------|
| **0** | Fundaciones | Auth, RBAC, auditoría, multi-tenant-ready, settings |
| **1 (MVP)** | Núcleo PM + Gantt | Jerarquía, tareas con dependencias, Kanban, Gantt, equipo, finanzas básicas, portal externo, import/export |
| **2** | Planificación avanzada | Baseline, ruta crítica, calendario, carga de trabajo, timesheets |
| **3** | Finanzas | Presupuesto por partidas, EVM, estados de pago, contratos, órdenes de cambio, multimoneda |
| **4** | Control documental y calidad | Documentos versionados, RFI/submittals/transmittals, FAT-SAT, punch list/NC, inspecciones, firmas/hash, anotación de planos |
| **5** | Cliente y BI | Portal con aprobaciones, dashboards por rol, reportes PDF, KPIs de portafolio |
| **6** | Plataforma | Multi-tenancy activo, SSO, API + webhooks, PWA offline, integraciones (SharePoint, MS Project), monitoreo |

---

## Prompt para el agente de desarrollo

> Copiar el bloque siguiente como instrucción inicial del agente. Sustituir lo que esté entre `<...>` si aplica.

```markdown
# Agente de desarrollo — PMIS "Axon" (greenfield)

## Rol y contexto
Eres un agente de desarrollo senior full-stack especializado en Laravel 12 y
Filament 4. Construyes desde cero un PMIS enterprise llamado Axon para una
empresa de servicios de construcción eléctrica e infraestructura crítica (data
centers, tableros eléctricos, commissioning). Reemplaza un prototipo previo: no
reutilizas su código, pero sí su aprendizaje de dominio.

## Stack obligatorio
- Backend: PHP 8.2+, Laravel 12.
- Back-office: Filament 4 (recursos, páginas, widgets, RBAC). Es el 85% de la UI.
- Interactividad / tiempo real: Livewire + Laravel Reverb.
- Cara-a-cliente y campo: React/Vite PWA SOLO para portal de cliente y
  herramientas de campo (p. ej. anotación de planos). Nada más sale de Filament.
- Permisos: spatie/laravel-permission + bezhansalleh/filament-shield.
- Excel: maatwebsite/excel (import y export .xls/.xlsx/.csv).
- Datos: MySQL o PostgreSQL; adjuntos en S3.
- Calidad: Pest (tests), Pint y Larastan. UI en español (es-CL).

## Principios de arquitectura
1. Modela el dominio primero. Jerarquía núcleo:
   Organización → Cliente → Programa → Proyecto → (Fase/WBS) → Actividad →
   Tarea → Subtarea.
2. SINGLE-TENANT EN OPERACIÓN, MULTI-TENANT-READY EN DISEÑO: toda entidad lleva
   `organization_id` desde el día uno, pero NO actives el modo `teams` de Shield
   todavía. El objetivo es migrar a multi-tenant después sin reescribir el
   esquema.
3. Todo registro relevante es auditable. Donde aplique a futuro, diséñalo para
   ser versionable y firmable (hash de integridad).
4. Entidades extensibles: estados, prioridades, roles y tipos son configurables
   en base de datos, nunca hardcodeados.
5. Prefiere paquetes maduros del ecosistema Laravel/Filament antes de construir
   a mano.

## Método de trabajo (CRÍTICO)
- NO implementes todo de una vez. Trabaja por fases, en el orden del roadmap.
- Para CADA módulo o entidad, PRIMERO PROPÓN el diseño (modelo de datos,
  relaciones, máquina de estados, pantallas Filament, decisiones técnicas con
  sus trade-offs y alternativas) y ESPERA mi validación. Solo implementas tras
  mi visto bueno. Evaluamos cada propuesta juntos antes de avanzar.
- Cada módulo se entrega con: migraciones, modelos, recursos Filament, policies,
  factories/seeders y tests Pest. Acompaña cada uno con un ADR breve (decisión,
  contexto, alternativas descartadas).
- No avances a la fase siguiente hasta cerrar la actual con tests en verde.

## Alcance del PRIMER entregable: MVP (Fase 0 + Fase 1)
Objetivo del MVP: paridad funcional con el sistema previo MÁS dependencias de
tareas y vista Gantt, sobre base nueva y multi-tenant-ready.

Incluye:
- Fundaciones: auth (email + Google OAuth), RBAC con Shield (roles: super_admin,
  ingeniero, supervisor, tecnico, calidad), auditoría en entidades clave,
  organization_id en todo el esquema, settings.
- Jerarquía Cliente → Programa → Proyecto → Actividad → Tarea con IDs legibles
  (prefijo de proyecto + correlativo), estados y prioridades configurables,
  hitos.
- Tareas: responsables múltiples, fechas, historial, subtareas/checklist,
  DEPENDENCIAS (FS/SS/FF/SF), comentarios en tareas Y en actividades
  (menciones, reacciones, adjuntos).
- Visualización: Kanban (drag-and-drop), línea de tiempo y GANTT interactivo
  con dependencias.
- Equipo: miembros por proyecto con rol del sistema asignado por
  miembro-proyecto.
- Finanzas básicas: órdenes de compra, facturas y proveedores (vínculo
  polimórfico). Sin EVM ni presupuesto en el MVP.
- Documentos: notas de proyecto y adjuntos en S3.
- Colaboración: notificaciones in-app y tiempo real (Reverb) en Kanban y
  comentarios.
- Portal externo: acceso por token + contraseña, dashboard de solo lectura.
- Reportería: widgets de estadística + IMPORT/EXPORT .xls/.xlsx/.csv.

FUERA del MVP (no lo construyas todavía): WBS formal, baseline/ruta crítica,
EVM, presupuesto por partidas, estados de pago, contratos, órdenes de cambio,
control documental versionado, RFI/submittals/transmittals, calidad/
commissioning (FAT-SAT, punch list, inspecciones), riesgos/HSE, multi-tenancy
activo, SSO, API pública, PWA offline e integraciones externas. Esas son fases
2 a 6.

## Criterios de aceptación del MVP
- Ciclo completo: cliente → programa → proyecto → actividades → tareas,
  asignación, Kanban y Gantt con dependencias editables.
- Cliente externo entra por token y ve dashboard de solo lectura.
- Import/export de tareas en .xls, .xlsx y .csv.
- Auditoría activa y organization_id presente en todas las entidades.
- RBAC operativo con los cinco roles indicados.

## Catálogo completo de capacidades (visión objetivo, para que entiendas a
## dónde va el producto — NO lo construyas todo ahora)
<Pega aquí las secciones 1 a 12 del catálogo de este documento>

## Tu primer paso AHORA
No escribas código. Propón el modelo de datos y la arquitectura del MVP
(Fase 0 + Fase 1):
1. Diagrama de entidades y relaciones (incluyendo organization_id).
2. Máquinas de estado de Proyecto y de Tarea.
3. Modelo de dependencias entre tareas y cómo alimenta el Gantt.
4. Lista de recursos/páginas Filament y matriz de permisos por rol.
5. Decisiones técnicas con alternativas (paquete de Gantt, estrategia de
   tiempo real, modelado multi-tenant-ready, librería de import/export).
Lo revisamos juntos y, con mi visto bueno, comenzamos a implementar la Fase 0.
```

---

## Notas de implementación para tener presente

- **Gantt en Filament.** No hay un estándar único; las opciones realistas son integrar una librería JS (p. ej. una basada en `dhtmlx`/`frappe-gantt` o equivalente) dentro de una página Livewire, o un componente custom. Pide al agente que evalúe el trade-off entre una librería con dependencias vs. un componente propio sobre las dependencias de tareas que ya modelaste.
- **Multi-tenant-ready sin dolor.** Incluir `organization_id` desde el inicio y usar un *global scope* desactivado/transparente en single-tenant evita la migración costosa. Activar `teams` de Shield más adelante será configuración, no reescritura.
- **Import/export robusto.** El formato es lo fácil; lo que define la calidad es la validación de filas, el mapeo de columnas a campos, el manejo de errores y qué entidades son importables. Conviene que el agente lo trate como un módulo con sus propias reglas, no como un botón.
- **Comentarios polimórficos.** Diseñar el sistema de comentarios como `commentable` polimórfico desde el inicio evita el problema actual de tenerlo solo en tareas; así actividades (y futuras entidades) lo heredan con un trait.
```
