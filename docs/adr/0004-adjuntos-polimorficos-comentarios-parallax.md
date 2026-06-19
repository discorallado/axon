# ADR-0004: Adjuntos polimórficos y comentarios con Parallax

**Fecha:** 2026-06-19  
**Estado:** Aceptado  
**Módulo:** Solicitudes de Tableros Eléctricos

---

## Contexto

El módulo de solicitudes guardaba las rutas de archivos como columnas directas
(`technical_specs_path`, `site_photos_paths`, `load_list_file_path`, etc.), y los
comentarios internos se implementaban con un modelo `Comment` polimórfico propio.
Ambas aproximaciones generaron problemas:

- Los archivos no eran visibles en el back-office ni eliminables en cascada.
- Los comentarios con `RepeatableEntry` en Filament 5 no renderizaban correctamente.

---

## Decisión

### D01 — Adjuntos → modelo `Attachment` polimórfico

Se migran todos los archivos al modelo `Attachment` ya existente, agregando una
columna `tag` (`VARCHAR(50)`) que identifica el rol del archivo (`load_list`,
`unilineal_diagram`, `mechanical_plans`, `technical_specs`, `site_photo`).

Las columnas de ruta fueron eliminadas de `submission_requests` y `submission_items`.

**Eliminación en cascada:** se implementa mediante observers:
- `SubmissionRequestObserver::forceDeleting` → borra ítems + adjuntos de la solicitud
- `SubmissionItemObserver::deleting` → borra adjuntos del ítem del disco y la BD

La eliminación en cascada en BD se hace via modelo (no FK ON DELETE CASCADE) para
garantizar que los archivos del disco se borren junto con los registros.

**Alternativas descartadas:**
- Columnas de ruta (A): sin trazabilidad ni cascada fácil.
- FK ON DELETE CASCADE (C): borra la BD pero no el disco.

### D02 — Arrays (protecciones, marcas) → `listWithLineBreaks()`

En el `infolist` del back-office, los campos JSON array se muestran con
`TextEntry::listWithLineBreaks()`, sin componente extra.

### D03 — `project_observations` → columna TEXT en `submission_requests`

Se agrega como columna real; sin fallback de JSON/raw_data ya que estamos en
etapa de desarrollo.

### M03 — Comentarios → `parallax/filament-comments` v3.0.0

Se reemplaza el modelo `Comment` propio y el trait `HasComments` por el plugin
`parallax/filament-comments ^3.0`. Este plugin es compatible con Filament ^5.0,
usa su propia tabla (`filament_comments`) y provee el componente `CommentsEntry`
para infolists.

La acción "Cambiar estado" también crea un `FilamentComment` cuando el usuario
deja texto, para mantener un hilo unificado de conversación.

**Archivos eliminados:** `app/Models/Comment.php`, `app/Models/Concerns/HasComments.php`,
`app/Policies/RolePolicy.php`, `app/Policies/FormSectionPolicy.php`,
`app/Policies/FormTemplatePolicy.php`, `app/Livewire/PublicFormWizard_backup2.php`,
`app/Models/FormTemplate.php`, `app/Models/SubmissionAnswer.php`.

**Tablas eliminadas:** `comments`, `form_templates`, `form_sections`, `form_questions`,
`form_conditional_rules`, `submission_answers`.

---

## Consecuencias

- El back-office ahora muestra los adjuntos con sus nombres originales (sin URL
  pública; el `AttachmentController` sirve los archivos autenticados).
- Los comentarios se guardan en `filament_comments` y son accesibles para todos
  los roles con permiso `view` sobre la solicitud.
- Los cambios de estado con comentario quedan registrados tanto en
  `submission_status_histories` como en `filament_comments`.
- Las pruebas de tenant isolation se actualizaron para no depender de `FormTemplate`.
