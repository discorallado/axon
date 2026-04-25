# 🚀 CONTEXTO DEL PROYECTO

Estoy desarrollando un **Sistema de Gestión de Proyectos Enterprise (PMS)** inspirado en las 90 características de axon-pms, pero con una arquitectura moderna: **API-First + UI Ligera**.

## 📋 Requisitos No Negociables

✅ **Backend**: Laravel 11/12 (PHP 8.2+)  
✅ **API-First**: Todos los endpoints REST/JSON primero, UI después  
✅ **UI Admin**: Blade + HTMX + Tailwind CSS (CERO Livewire, CERO Vue/React, CERO Filament)  
✅ **100% Open-Source**: Licencias MIT, sin features bloqueadas  
✅ **Multi-tenant**: Aislamiento por DB/schema con stancl/tenancy v3  
✅ **NativePHP Ready**: La misma app debe funcionar en web, API y desktop  
✅ **Escalable**: Colas, caché, índices, stateless donde aplique  

---

## 🛠️ STACK TECNOLÓGICO APROBADO

| Capa | Paquete | Propósito |
|------|---------|-----------|
| **Core** | `laravel/framework:^12.0` | Base del framework |
| **DTOs/API** | `spatie/laravel-data` | Validación estricta, serialización controlada |
| **Query Builder** | `spatie/laravel-query-builder` | Filtros, includes, sorts vía API |
| **Multi-tenant** | `stancl/tenancy:^3.0` | DB por tenant, rutas tenant.*, caché aislado |
| **Auditoría** | `spatie/laravel-activitylog` | Log de cambios con autor, metadatos, relaciones |
| **Workflows** | `spatie/laravel-workflow` | Flujos asíncronos con retries y paralelismo |
| **State Machine** | `spatie/laravel-state-machine` | Transiciones: draft→pending→approved/rejected |
| **RBAC** | `spatie/laravel-permission` + `laravel/breeze` | Roles, permisos, policies nativas |
| **Feature Flags** | `laravel/pennant` | Toggle de features por tenant/usuario |
| **UI** | `htmx.org` + `tailwindcss:^4.0` | Interactividad sin JS complejo |
| **Desktop** | `nativephp/laravel` | Empaquetado sin duplicar lógica |
| **Testing** | `pestphp/pest` + `laravel/pint` | Tests limpios y formateo automático |
| **Excel** | `maatwebsite/excel` | Import/export mantenido |
| **Social Auth** | `laravel/socialite` | OAuth externo (Google, GitHub, etc.) |
| **AWS** | `aws/aws-sdk-php` | S3, SES, integraciones cloud |

❌ **PROHIBIDO**: Filament, Livewire, Inertia, Vue, React, Alpine (excepto micro-interacciones)

---

## 📦 FUNCIONALIDADES A IMPLEMENTAR (90 FEATURES)

### 🔐 Módulo: Autenticación y Seguridad (Features 1-12)
- Auth nativo Laravel + Breeze
- Login social con Socialite
- RBAC con spatie/laravel-permission
- Policies y Gates por modelo
- Middleware de tenant detection (subdomain/header)
- Sesiones stateless para API (sanctum/jwt)
- Rate limiting por tenant/usuario
- 2FA opcional (preparado)
- Password policies configurables
- Logs de intentos de acceso
- Tokens API con scopes
- Refresh tokens y revocación

### 🗂️ Módulo: Gestión de Proyectos (Features 13-19)
- CRUD Projects vía API (/api/v1/projects)
- DTOs con spatie/laravel-data para request/response
- Filtros avanzados con spatie/laravel-query-builder
- Relaciones: members (n:m), program (m:1)
- Estados personalizables con state machine
- Fechas con timezone-aware (Carbon)
- Búsqueda full-text en descripción

### ✅ Módulo: Gestión de Tareas (Features 20-26)
- Tasks con UUID, asignación múltiple
- TaskStatus y TaskPriority como entidades configurables
- Historial de cambios con activitylog
- HTMX: Tabla con hx-get, paginación, filtros dinámicos
- Formulario con hx-post, validación Laravel, swap de errores
- Ordenamiento por columnas vía query params

### 💬 Módulo: Colaboración y Comentarios (Features 27-32)
- Comentarios anidados (threads) con mentions @user
- Reacciones (emojis) con tabla pivot
- Suscripciones a hilos (notifications)
- Adjuntos en S3 con aws-sdk-php
- Notificaciones en tiempo real (broadcasting opcional)
- HTMX: Swap parcial al añadir comentario sin recargar

### 💰 Módulo: Financiero (Features 33-37)
- CRUD Suppliers con validación estricta
- PurchaseOrders con estados y aprobaciones (workflow)
- Invoices vinculadas a POs
- Estados de pago: pending→paid→overdue
- Reportes exportables a Excel

### 🎨 Módulo: UI Admin con HTMX (Features 38-44)
- Dashboard con widgets HTMX (hx-trigger="load")
- Tablas con búsqueda en tiempo real (hx-trigger="keyup changed delay:300ms")
- Filtros como query params, no estado JS
- Formularios con validación server-side + hx-swap="outerHTML"
- Acciones masivas con checkboxes + hx-post
- Modales con hx-get + hx-target="#modal-container"
- Tema oscuro con class swap en <html> + persistencia en cookie

### 📤 Módulo: Import/Export (Features 45-48)
- Export Excel con Maatwebsite, queueado para grandes datasets
- Import con validación por fila, reporte de errores
- Endpoints API para export programática
- Feature flag para habilitar/deshabilitar exports pesados

### ⚡ Módulo: Performance (Features 49-54)
- Octane-ready (sin dependencias de sesión en memoria)
- Queries con eager loading explícito
- Caché de consultas frecuentes con tags por tenant
- Índices compuestos en migraciones
- Lazy loading desactivado en producción
- Uploads directos a S3 con presigned URLs

### 🔔 Módulo: Notificaciones y Eventos (Features 55-58)
- Notificaciones database + mail con queue
- Eventos Laravel para desacoplar lógica
- Listeners para: task_assigned, comment_added, approval_required
- Activitylog en todos los modelos críticos

### 🌐 Módulo: Integraciones (Features 59-62)
- AWS S3 para archivos, configuración por tenant
- Webhooks salientes configurables por evento
- API documentada con OpenAPI/Scribe (opcional)
- Health check endpoint para monitoreo

### 🗄️ Módulo: Base de Datos (Features 63-67)
- Migraciones con nombres descriptivos y reversibles
- Seeders con factories y estados realistas
- Relaciones Eloquent con tipos de retorno
- Soft deletes + scopes para incluir/excluir
- Timestamps con mutadores para formato API

### 🧪 Módulo: Calidad (Features 68-71)
- Tests Pest: Feature para endpoints API, Unit para services
- Pint configurado con reglas estrictas
- PHPStan nivel 8 en CI
- Comentarios PHPDoc en DTOs y Services

### 🐳 Módulo: DevOps (Features 72-76)
- Sail para desarrollo local con Redis/MySQL
- Dockerfile multi-stage para producción
- .env.example completo con comentarios
- Comandos Artisan personalizados para tareas comunes
- Vite con hot-reload para CSS/JS mínimo

### 📱 Módulo: NativePHP Prep (Features 77-81)
- Estructura de rutas que funcione en file:// y https://
- Offline-first: caché local con sincronización diferida
- Autenticación persistente entre sesiones desktop
- System tray con estado de sincronización
- Build scripts para Windows/macOS/Linux

### 🔄 Módulo: UX Avanzada (Features 82-90)
- Búsqueda global con endpoint dedicado y ranking
- Filtros persistidos en URL (compartibles)
- Ordenamiento multi-columna vía query string
- Paginación con cursor para datasets grandes
- State machine visual para flujos de aprobación
- Tabs en formularios con HTMX hx-select
- Multi-idioma con Laravel localization + tenant override
- Logs estructurados con channel por tenant
- Backup automático con spatie/laravel-backup

---

## 🔄 ESTRATEGIA DE IMPLEMENTACIÓN (ORDEN CRÍTICO)

### Fase 1: Cimientos (Semana 1-2)
1. Instalar Laravel + paquetes base
2. Configurar stancl/tenancy (DB por tenant)
3. Setup spatie/laravel-permission + Breeze
4. Crear estructura de DTOs con spatie/laravel-data
5. Configurar HTMX + Tailwind en Blade layouts

### Fase 2: API-First Core (Semana 3-4)
```bash
// Ejemplo: Endpoint de Proyectos
// Route::apiResource('projects', ProjectController::class);
```
Controller usa:
- ProjectData DTO para request/response
- QueryBuilder para filtros: ?filter[name]=&sort=-created_at
- Policy para autorización
- ActivityLog para auditoría automática
### Fase 3: UI con HTMX (Semana 5-6)
Ejemplo: Tabla de Proyectos con HTMX (debe desarrollarse más en la aplicació)
```blade
<div id="projects-table" 
     hx-get="/api/projects" 
     hx-trigger="load, searchChanged from:body"
     hx-include="#filters"
     hx-target="#projects-table"
     hx-swap="outerHTML">
    {{-- Blade renderiza filas --}}
</div>
```
Ejemplo filtros que disparan recarga parcial
```blade
<input name="filter[name]" 
       type="search" 
       hx-trigger="keyup changed delay:300ms"
       hx-dispatch="searchChanged">
```
### Fase 4: Workflows + Auditoría (Semana 7)
Workflow de aprobación de PurchaseOrder
```php
Workflow::define(PurchaseOrder::class)
    ->step(NotifyManager::class)
    ->step(ValidateBudget::class)
    ->step(Approve::class)
    ->onFailed(LogFailure::class);
```
ActivityLog automático en modelo
```php

class PurchaseOrder extends Model {
    use HasActivityLog;
    
    public function registerActivity(): array {
        return ['causer_id' => auth_id(), 'properties' => $this->getChanges()];
    }
}
```
### Fase 5: NativePHP + Polish (Semana 8)
Configurar nativephp/laravel para consumir misma app
- Rutas detectan si es request de desktop vs web
- Assets se cargan desde file:// o CDN según entorno
- Sync queue se ejecuta en background del desktop app

### REGLAS DE CALIDAD PARA EL CÓDIGO GENERADO
- DTOs primero: Nunca usar Request directo en controller. Siempre ProjectData::from(request())
- API responde JSON: Controllers API devuelven ProjectData::collection($projects)
- UI consume API o mismo controller: Si Accept: text/html → retorna Blade; si Accept: application/json → retorna JSON
- HTMX sin JS personalizado: Toda interactividad vía atributos HTML. Si necesitas JS, máximo 10 líneas con Alpine
- Tenant-aware por defecto: Todos los queries incluyen where('tenant_id', current_tenant()) vía global scope
- Auditoría automática: Todo cambio en modelo crítico se loguea sin intervención manual
- Tests para flujos críticos: Cada workflow/approbation tiene test de integración
- Documentación en código: PHPDoc en DTOs, Services y Workflow steps

### FORMATO DE RESPUESTA ESPERADO
Cuando te pida implementar una feature, responde con:

> ## 🎯 Feature: [Nombre]
> 
> ### Arquitectura
> - Endpoint: `GET /api/v1/[resource]`
> - DTO: `[Resource]Data`
> - Policy: `[Resource]Policy@[method]`
> - Tenant-scope: ✅
> 
> ### Código Backend
> ```php
> // [Archivo completo o snippet clave con comentarios]
> ```
> ### Código Frontend (HTMX/Blade)
> ```blade
> {{-- [Snippet Blade con atributos HTMX explicados] --}}
> ```
> ### Test Example
> ```php
> // [Test Pest mínimo que valida el flujo]
> ```
> ### Consideraciones
> -[Notas de performance, seguridad o edge cases]

---

## 🚀 COMANDO DE INICIO

Para comenzar, responde con:
1. ✅ Confirmación de comprensión del stack y restricciones
2. 📋 Lista priorizada de las primeras 5 features a implementar (Fase 1)
3. 🔧 Comando artisan exacto para inicializar el proyecto con todos los paquetes aprobados

**No sugieras alternativas al stack definido. Si una feature requiere un paquete no listado, propón una implementación nativa con Laravel primero.**
