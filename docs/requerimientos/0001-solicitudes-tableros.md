# REQ-0001 — Módulo de Solicitudes de Tableros Eléctricos

- **Estado:** Propuesto (pendiente de diseño con `/arquitecto`)
- **Prioridad:** Primero, antes del MVP del PMIS
- **Objetivo de negocio:** Capturar solicitudes de fabricación/cotización de
  tableros eléctricos desde un formulario público, y gestionarlas internamente.

## Resumen

Rebanada vertical completa: acceso público → captura → gestión interna. Un
visitante SIN cuenta abre un enlace público, rellena un formulario (con
secciones y lógica condicional), adjunta archivos y envía. Internamente, el
equipo gestiona cada envío como una "solicitud" con un flujo de estados.

Las preguntas del formulario deben ser **modificables por un usuario interno**
sin tocar código → se requiere un **constructor de formularios** (form builder),
no un formulario fijo.

## Decisiones de alcance ya tomadas

- **Acceso:** enlace público abierto, sin autenticación ni token.
- **Formulario:** con secciones y **lógica condicional** (saltos / mostrar-ocultar
  según respuestas).
- **Adjuntos:** sí; el solicitante puede subir archivos (unilineales, hojas de
  carga, fotos, specs). Almacenar en S3.

## Modelo conceptual sugerido (a validar en diseño)

Patrón plantilla → instancia (mismo espíritu que el sistema de checklists FAT):

- **FormTemplate** — una plantilla de formulario (p. ej. "Solicitud de Tablero").
  Versionable: editar preguntas no debe corromper respuestas ya enviadas.
- **FormSection** — secciones ordenables dentro de una plantilla.
- **FormQuestion** — pregunta con tipo (texto, número, textarea, select,
  multiselect, booleano, fecha, archivo, etc.), obligatoria u opcional, orden,
  ayuda, y opciones (para selects).
- **FormConditionalRule** — regla de visibilidad: "mostrar pregunta/sección X si
  respuesta de pregunta Y {=, ≠, >, <, incluye} valor Z". Soporta encadenar.
- **SubmissionRequest** — un envío del público. Lleva estado, datos de contacto
  del solicitante, fecha, y referencia a la versión de plantilla usada.
- **SubmissionAnswer** — respuesta a cada pregunta (valor + adjuntos si aplica).
- Adjuntos polimórficos reutilizables (no exclusivos de este módulo).

Estados sugeridos de SubmissionRequest (configurables, no hardcodeados):
`nueva → en_revision → cotizada → aprobada / rechazada`. Con historial de
cambios de estado.

## Requisitos funcionales

- RF-01: Un usuario interno puede crear/editar plantillas de formulario con
  secciones, preguntas tipadas y opciones, sin tocar código.
- RF-02: Soporte de reglas condicionales que muestran/ocultan preguntas o
  secciones según respuestas previas.
- RF-03: Cada plantilla expone un enlace público único; al editar la plantilla,
  el enlace se mantiene.
- RF-04: El público accede sin login, rellena, adjunta archivos y envía. Con
  validación en cliente y servidor.
- RF-05: Al enviar, se crea una SubmissionRequest en estado `nueva` y se notifica
  al equipo (notificación in-app; email opcional).
- RF-06: Bandeja interna (Filament) para listar, filtrar y gestionar solicitudes;
  ver respuestas y adjuntos; cambiar estado; comentar internamente.
- RF-07: Versionado: las respuestas enviadas conservan la versión de plantilla
  con que se rellenaron.
- RF-08: Exportar una solicitud (o lote) a Excel/PDF para cotización.

## Requisitos no funcionales / restricciones

- Toda entidad lleva `organization_id` (multi-tenant-ready), aunque el formulario
  público sea de una sola organización por ahora.
- Adjuntos en S3; validar tipo y tamaño; nombres saneados.
- La página pública NO debe exponer el panel Filament ni requerir sesión.
- Protección anti-spam en el endpoint público (rate limit; honeypot o captcha a
  evaluar — NO usar captcha que el equipo deba resolver).
- Comentarios internos vía el trait `commentable` polimórfico reutilizable.
- Auditoría de cambios de estado de cada solicitud.

## Criterios de aceptación

1. Un interno crea una plantilla "Solicitud de Tablero" con al menos una sección
   condicional, la publica y obtiene un enlace.
2. Un visitante anónimo abre el enlace, ve solo las preguntas que correspondan
   según sus respuestas, adjunta un archivo y envía con éxito.
3. La solicitud aparece en la bandeja interna en estado `nueva`, con todas las
   respuestas y el adjunto accesible.
4. El interno cambia el estado y comenta; el cambio queda auditado.
5. Editar la plantilla después no altera las respuestas ya enviadas.
6. La solicitud se puede exportar a Excel/PDF.
7. Tests Pest cubren: render condicional, envío público válido/ inválido,
   creación de solicitud, cambio de estado, aislamiento por `organization_id`.

## Notas de diseño a resolver con /arquitecto

- ¿Construir el form builder a mano o apoyarse en un paquete de Filament para
  esquemas dinámicos? Evaluar trade-offs (control vs. velocidad).
- Cómo modelar la página pública: ruta Livewire dedicada fuera del panel, o
  panel Filament público separado.
- Representación de las reglas condicionales (JSON por pregunta vs. tabla de
  reglas) y cómo evaluarlas en cliente para UX fluida.
- Estrategia anti-spam concreta acorde a "sin captcha para el equipo".
