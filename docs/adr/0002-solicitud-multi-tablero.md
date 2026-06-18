# ADR 0002 — Solicitud multi-tablero: modelo de datos e interfaz

**Estado:** Aceptado  
**Fecha:** 2026-06-18  
**Módulo:** Módulo de Solicitudes de Tableros Eléctricos

---

## Contexto

La versión inicial del formulario público asumía que cada solicitud correspondía
a exactamente un tablero eléctrico. El dominio real exige que un mismo proyecto
pueda solicitar varios tableros de distinto tipo (TG, alumbrado, control, etc.)
y en cantidades distintas, todos bajo un mismo proyecto y contacto.

Almacenar todos los campos de tablero repetidos en `submission_requests` o usar
un campo de cantidad simple no permite capturar diferencias técnicas entre
unidades. La solución aprobada desacopla los datos de proyecto/contacto de los
datos específicos de cada ítem de tablero.

---

## Decisión

### 1. Modelo de datos

Se introduce la tabla `submission_items` con una fila por *tipo* de tablero.
El campo `quantity` en esa tabla indica cuántas unidades de ese tipo se
solicitan, evitando saturar el formulario con N copias idénticas.

```
submission_requests (proyecto + contacto + documentos del proyecto)
  └── submission_items[] (uno por tipo de tablero, con quantity)
```

Cada `SubmissionItem` almacena todos los campos técnicos del tablero:
identificación, necesidades, instalación, eléctrico/constructivo, y rutas de
archivos propios del ítem.

Los documentos a nivel de proyecto (`technical_specs`, `site_photos`) quedan
como columnas en `submission_requests`.

### 2. Interfaz pública

El formulario exterior mantiene un wizard de 3 pasos:
1. **Contacto y Proyecto** — datos del proyecto y del contacto.
2. **Tableros** — lista de ítems ya agregados + botón "Agregar Tablero".
3. **Documentación** — specs técnicas, fotos del sitio, observaciones finales.

Cada ítem se agrega/edita a través de un modal con wizard propio de 3 pasos
(Identificación, Instalación y Eléctrico, Documentación del ítem). Los ítems se
almacenan en `$items` como array Livewire hasta el envío final.

### 3. Borrador (draft)

El localStorage guarda tanto `$data` (formulario externo) como `$items`
(arreglo de tableros) bajo la misma clave `axon_solicitud_draft`.

---

## Alternativas descartadas

| Alternativa | Razón de descarte |
|---|---|
| Repeater Filament embebido en el paso 2 | Muestra todos los ítems expandidos simultáneamente; satura la vista con muchos campos cuando hay múltiples tableros. |
| Drawer lateral con formulario | El usuario prefirió un modal con wizard para coherencia visual con el formulario externo. |
| Tabla separada `submission_media` para todos los archivos | Complejidad innecesaria para V1; los archivos se referencian por ruta directa en las columnas del modelo. |
| Copiar los campos de tablero N veces en el mismo registro | No escala (20 tableros del mismo tipo = campo `quantity`); esquema rígido. |

---

## Consecuencias

- `SubmissionAnswer` queda en desuso para nuevas solicitudes; la tabla se
  mantiene para no romper registros históricos.
- El back-office debe mostrar `SubmissionItems` en lugar de `SubmissionAnswers`.
- En el futuro se puede agregar tabla `submission_media` polimórfica para manejo
  de archivos más robusto sin cambiar el esquema de ítems.
