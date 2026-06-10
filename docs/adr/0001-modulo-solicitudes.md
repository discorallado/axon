# ADR-0001 — Módulo de Solicitudes de Tableros Eléctricos

**Fecha:** 2026-06-10
**Estado:** Aceptado
**Requerimiento:** REQ-0001 (`docs/requerimientos/0001-solicitudes-tableros.md`)

---

## Contexto

Se necesita capturar solicitudes de fabricación/cotización de tableros eléctricos desde un formulario
público (sin autenticación), con soporte de secciones, lógica condicional, adjuntos y un flujo interno
de gestión de estados. El formulario debe ser configurable por usuarios internos sin tocar código
(form builder), y las respuestas ya enviadas deben ser inmutables respecto a cambios futuros en la
plantilla (trazabilidad de auditoría).

---

## Decisiones

### 1. Página pública: Blade + Alpine.js (sin Livewire en la capa pública)

**Decisión:** La ruta `/f/{slug}` sirve una vista Blade estática con lógica condicional evaluada
íntegramente en Alpine.js, sin round-trips al servidor durante el relleno del formulario.

**Alternativa descartada:** Livewire standalone — cada interacción del usuario dispara una petición
al servidor, lo que puede dar una experiencia lenta en conexiones móviles deficientes. Alpine.js
evalúa las reglas serializadas en JSON al renderizar la página y responde en tiempo real sin latencia.

**Consecuencia:** las reglas condicionales se re-validan obligatoriamente en el servidor al recibir
el POST, descartando respuestas de preguntas que debían estar ocultas.

**Extensibilidad:** `form_templates.view_type` permite asignar layouts distintos por formulario
(wizard, fat_checklist, etc.) consumiendo el mismo módulo de evaluación de reglas Alpine.js.

### 2. Form builder: implementación propia (modelo relacional)

**Decisión:** Se construye a mano usando el modelo relacional diseñado en el ADR, gestionado desde
Filament con `RelationManagers` y recursos anidados.

**Alternativa descartada:** Paquetes de terceros (`coolsam/filament-form-builder`, etc.) — inmaduros
en el ecosistema Filament 5, modelos de datos incompatibles con nuestro versionado y multi-tenant-ready.

### 3. Reglas condicionales: tabla separada `form_conditional_rules`

**Decisión:** Cada regla es un registro en tabla propia con FK a pregunta/sección trigger y
pregunta/sección target.

**Alternativa descartada:** JSON embebido en `form_questions.conditions` — opaco para auditoría, no
permite consultar reglas directamente, dificulta agregar reglas a nivel de sección.

### 4. Versionado de plantillas: registros versionados con `template_version`

**Decisión:** Secciones y preguntas llevan `template_version`. Al editar la plantilla, se incrementa
el número de versión y se duplican los registros afectados con la nueva versión. Las
`SubmissionRequest` guardan `template_version` y referencian siempre sus preguntas originales.

**Por qué no corrompe datos históricos:** los registros de versiones anteriores permanecen en la base
de datos intocados. Una solicitud enviada con v1 siempre reconstruye su vista a partir de preguntas
v1, aunque hoy exista v2 o v3.

**Alternativa descartada:** Snapshot JSON — no permite queries sobre preguntas de versiones pasadas
(ej. "filtrar solicitudes donde potencia > 100kW en v1"), y serializar/deserializar JSON es frágil
ante cambios de schema.

### 5. Anti-spam: honeypot + rate limit + tiempo mínimo de relleno

**Decisión:** Tres capas sin dependencias externas:
1. Campo oculto `_hp` (honeypot) — bots lo rellenan, humanos no.
2. `ThrottleRequests` por IP (5 envíos / 10 minutos).
3. `submitted_at` en cookie/campo: se rechaza si el POST llega en menos de 3 segundos.

**Alternativa descartada:** Cloudflare Turnstile — dependencia externa, requiere cuenta y configuración
de DNS. Se puede agregar como capa adicional si hay abuso real.

### 6. Adjuntos: disco local por ahora, S3 en iteración posterior

**Decisión:** `Storage::disk('local')` + jobs en cola para el procesamiento. El modelo `Attachment`
tiene columna `disk` para migrar a S3 sin cambiar la lógica de negocio.

### 7. Export PDF: `barryvdh/laravel-dompdf`

**Decisión:** Sin dependencias de Node.js; adecuado para PDFs de datos tabulares como los de este módulo.

**Alternativa descartada:** `spatie/browsershot` — más fiel visualmente pero requiere Node.js y
Puppeteer en el servidor de producción.

### 8. Notificaciones: in-app (database) + email

**Decisión:** Al recibir un nuevo envío, se notifica a todos los usuarios con rol `super_admin` o
`supervisor` de la organización (in-app + email). Al submitter se envía un email de confirmación.

### 9. Estados: tabla configurable `submission_statuses`

**Decisión:** Los estados son registros de base de datos, no un PHP Enum. Los defaults del seeder son
`nueva → en_revision → cotizada → aprobada / rechazada`. Los flags `is_initial` e `is_terminal`
controlan el flujo; la `SubmissionStateMachine` evalúa transiciones según rol.

---

## Riesgos aceptados

- **R2 (S3):** Upload local mientras S3 no está configurado; el modelo soporta migración sin retrabajo.
- **R6 (Filament 5):** Incompatibilidades de paquetes se resuelven en el momento.
