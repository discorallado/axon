# ADR-0005 — Exportación de Solicitudes a PDF

**Fecha:** 2026-06-22  
**Estado:** Aceptado

---

## Contexto

El RF-08 del módulo de solicitudes requiere exportar una solicitud a PDF para uso en cotización y archivo. El PDF debe incluir todos los datos del formulario: contacto, proyecto, tableros (con todas sus especificaciones eléctricas y constructivas), adjuntos y historial de estados.

## Decisión

Se usa **`barryvdh/laravel-dompdf`** (HTML → PDF con motor DomPDF), una vista Blade dedicada `resources/views/pdf/submission-report.blade.php`, y un controller web (`SubmissionPdfController`) apuntado por una ruta `GET /solicitudes/{submission}/pdf` con middleware `auth`.

La descarga se dispara desde un botón "Descargar PDF" en los `headerActions` de `ViewSubmissionRequest`.

Se aprovechó la oportunidad para configurar `redirectGuestsTo` en `bootstrap/app.php` apuntando al login de Filament — faltaba y causaba 500 para rutas web protegidas.

## Alternativas descartadas

| Opción | Razón de descarte |
|---|---|
| `spatie/laravel-pdf` (Browsershot) | Requiere Chrome headless instalado; sobrecarga de dependencias para un informe tabulado |
| `mpdf/mpdf` | Menor adopción en el ecosistema Laravel; no añade ventajas para este caso |
| PDF generado en el frontend (JS) | Introduce complejidad innecesaria y duplica la lógica de presentación |

## Consecuencias

- El PDF se genera en el servidor; no requiere JavaScript ni estado del cliente.
- El diseño de la vista Blade es independiente del layout de Filament: se puede iterar sin afectar el panel.
- DomPDF no soporta CSS Grid/Flexbox — el layout usa `display: table` y `table-cell`, compatible con el motor.
- PHP 8.x requiere que las ternarias anidadas estén entre paréntesis; la vista usa bloques `@if / @php match()` para evitar el error `Unparenthesized a ? b : c ?: d`.
