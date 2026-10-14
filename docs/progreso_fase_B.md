# Bitácora de Avance y Documentación Técnica — Fase B
## Integración de Solo Lectura con Mercado Libre y Modelado Definitivo del Catálogo

**Proyecto:** JC Autopartes  
**Rama de Desarrollo:** `fase-b`  
**Fecha de Inicio:** 14 de Octubre de 2026  
**Última Actualización:** 14 de Octubre de 2026 (Sub-paso B1 Completado)  
**Documento de Referencia:** [`docs/plan_desarrollo_v2.md`](plan_desarrollo_v2.md) y [`docs/cierre_fase_A0.md`](cierre_fase_A0.md)  

---

## 1. Contexto y Objetivos de la Fase B

Tras haber concluido al 100% el andamiaje visual y funcional de la **Fase A0** (catálogo de demostración, carrito en servidor y panel de administración), la **Fase B** tiene como propósito reemplazar los datos provisionales de ejemplo por el **catálogo real de autopartes** proveniente de la cuenta oficial de Mercado Libre de **JC Autopartes**.

### Objetivos Clave:
1. **Conectar de forma segura la API oficial de Mercado Libre** mediante el protocolo OAuth 2.0.
2. **Extraer el catálogo de publicaciones en su formato crudo (JSON completo)** para no omitir ningún atributo ni metadato utilizado por el negocio.
3. **Analizar la estructura real de autopartes** (marcas, modelos, años de compatibilidad, números de parte originales, lados, posiciones y medidas de empaque).
4. **Redactar el documento formal de mapeo de campos** ([`docs/mapeo_campos_mercadolibre.md`](mapeo_campos_mercadolibre.md)).
5. **Evolucionar la base de datos de Laravel** hacia el modelo definitivo, asegurando que `StockService` y `PriceService` mantengan la integridad absoluta.
6. **Reflejar los datos reales en la administración y en el sitio público**.

---

## 2. Mapa de Ruta de la Fase B (Sub-pasos)

```mermaid
flowchart LR
    B1[B1: OAuth & Cliente API] --> B2[B2: Extracción JSON Crudo]
    B2 --> B3[B3: Mapeo Formal de Campos]
    B3 --> B4[B4: Esquema Definitivo & Sync]
    B4 --> B5[B5: Admin & Tienda Pública]
    B5 --> B6[B6: Pruebas Integrales & Cierre]
    
    style B1 fill:#22c55e,stroke:#15803d,color:#fff
    style B2 fill:#3b82f6,stroke:#1d4ed8,color:#fff
    style B3 fill:#e2e8f0,stroke:#94a3b8,color:#334155
    style B4 fill:#e2e8f0,stroke:#94a3b8,color:#334155
    style B5 fill:#e2e8f0,stroke:#94a3b8,color:#334155
    style B6 fill:#e2e8f0,stroke:#94a3b8,color:#334155
```

| Sub-paso | Descripción | Estado |
| :---: | :--- | :---: |
| **B1** | Arquitectura de conexión, OAuth 2.0 seguro y Cliente `MarketplaceClient` | **COMPLETADO (100%)** |
| **B2** | Extracción del catálogo y volcado de JSON crudo (`marketplace_raw_items`) | **COMPLETADO (100%)** |
| **B3** | Análisis de atributos de autopartes y documento de mapeo formal | **COMPLETADO (100%)** |
| **B4** | Refactorización del modelo `Product` y comando sincronizador de catálogo | **SIGUIENTE A EJECUTAR** |
| **B5** | Actualización de `/admin/articulos` y ficha pública con metadatos de ML | *Pendiente* |
| **B6** | Pruebas integrales automatizadas, documentación de cierre y QA manual | *Pendiente* |

---

## 3. Lo que se ha Trabajado (Detalle Técnico del Sub-paso B1)

### 3.1. Configuración y Variables de Entorno
* **Archivo de Configuración:** `config/mercadolibre.php`. Contiene el mapeo de `app_id`, `client_secret`, `redirect_uri`, endpoint de autenticación por país (`https://auth.mercadolibre.com.mx`), endpoint de la API (`https://api.mercadolibre.com`) e identificador de país (`MLM`).
* **Plantilla de Entorno:** `.env.example`. Se añadieron las variables oficiales requeridas para operar sin exponer secretos en el repositorio:
  ```ini
  MELI_APP_ID=
  MELI_CLIENT_SECRET=
  MELI_REDIRECT_URI="${APP_URL}/admin/integraciones/mercadolibre/callback"
  MELI_COUNTRY_ID=MLM
  ```

### 3.2. Base de Datos y Almacenamiento Cifrado de Tokens
* **Migración:** `database/migrations/2026_10_14_000001_create_marketplace_tokens_table.php`. Crea la tabla `marketplace_tokens` con columnas para `marketplace`, `user_id`, `access_token`, `refresh_token`, `token_type`, `scope` y `expires_at`.
* **Modelo Eloquent:** `app/Models/MarketplaceToken.php`. Implementa los casts de cifrado nativos de Laravel:
  ```php
  protected function casts(): array
  {
      return [
          'access_token' => 'encrypted',
          'refresh_token' => 'encrypted',
          'expires_at' => 'datetime',
      ];
  }
  ```
  Esto garantiza que los tokens nunca se almacenen en texto plano en la base de datos (protegidos mediante AES-256-CBC con la `APP_KEY` del sistema).
* Métodos utilitarios: `isExpired()` y `willExpireSoon($minutes = 5)` para prevenir llamadas con tokens a punto de expirar.

### 3.3. Servicios de Integración
* **Contrato:** `app/Contracts/MarketplaceClient.php`. Se actualizó la interfaz incorporando `searchListings(string $userId, array $params = []): array`.
* **Servicio de Autenticación:** `app/Services/MercadoLibre/MercadoLibreOAuthService.php`.
  * `getAuthorizationUrl($state)`: Genera la URL con los parámetros requeridos por Mercado Libre.
  * `handleCallback($code)`: Realiza la petición POST hacia `/oauth/token` y persiste o actualiza los tokens.
  * `refreshToken($token)`: Renueva automáticamente los tokens antes de su vencimiento sin requerir intervención manual del usuario.
  * `getValidAccessToken()`: Retorna un token válido garantizado, renovándolo si expira en menos de 5 minutos.
  * `getConnectionStatus()`: Expone el estado seguro para la interfaz (con enmascaramiento del `App ID`).
* **Cliente de API:** `app/Services/MercadoLibre/MercadoLibreClient.php`.
  * Implementa `MarketplaceClient`.
  * Inyecta el token en las peticiones mediante cabecera `Authorization: Bearer {token}`.
  * Métodos implementados: `fetchListing($externalId)`, `searchListings($userId, $params)`, `updateAvailableStock($externalId, $qty)` y `getUserProfile()`.
* **Contenedor de Inyección:** Registrado el enlace en `app/Providers/AppServiceProvider.php` para que cualquier componente del sistema pueda inyectar `MarketplaceClient`.

### 3.4. Controlador y Rutas Administrativas
* **Controlador:** `app/Http/Controllers/Admin/MercadoLibreAuthController.php`.
  * `redirect()`: Valida existencia de credenciales y redirige a Mercado Libre con token CSRF `state` en sesión.
  * `callback()`: Valida coincidencia de `state`, captura posibles errores de denegación del usuario y guarda credenciales con mensaje flash.
  * `disconnect()`: Elimina los tokens y regresa a la vista de configuración con mensaje flash.
* **Rutas:** Registradas en `routes/web.php` bajo `/admin/integraciones/mercadolibre/*` protegidas por el middleware `auth`.

### 3.5. Interfaz de Administración
* **Vista:** `resources/js/Pages/Admin/Settings/Index.vue`.
* Se sustituyó la etiqueta estática de "Siguiente etapa" por una tarjeta interactiva con detección automática:
  * Si no hay credenciales: Mensaje explicativo indicando configurar `.env`.
  * Si hay credenciales y no está vinculado: Botón destacado "Conectar con Mercado Libre".
  * Si ya está vinculado: Indicador en verde "Conectado", ID del vendedor en Mercado Libre, fecha y hora de vencimiento del token y botón para desconectar la cuenta.

### 3.6. Batería de Pruebas Automatizadas
* `tests/Unit/MercadoLibreServiceTest.php`:
  * Validación de detección de credenciales.
  * Generación de URL OAuth válida.
  * Enmascaramiento de `App ID` en el reporte de estado.
  * Consumo autenticado de `/items/{id}` con `Http::fake()`.
  * Búsqueda paginada en `/users/{id}/items/search`.
  * Actualización de existencias mediante `PUT`.
  * Consulta de perfil de usuario `/users/me`.
  * Excepción controlada cuando no existe token activo.
  * Resolución del contenedor de `MarketplaceClient`.
* `tests/Feature/MercadoLibreOAuthTest.php`:
  * Protección de rutas para usuarios no autenticados.
  * Redirección con error cuando faltan variables en `.env`.
  * Redirección hacia el portal de Mercado Libre con `state` en sesión.
  * Control de errores remotos y rechazos de autorización.
  * Protección CSRF contra discrepancia en el parámetro `state`.
* **Resultado: 15 pruebas ejecutadas, 40 aserciones, 100% aprobadas.**

---

## 4. Detalle Técnico del Sub-paso B2 (Extracción de Catálogo y JSON Crudo)

### 4.1. Almacenamiento de Carga Útil Cruda
* **Migración:** `database/migrations/2026_10_14_000002_create_marketplace_raw_items_table.php`. Crea la tabla `marketplace_raw_items` con campos: `external_id` (único e indexado), `title`, `status`, `raw_payload` (JSON) y `fetched_at`.
* **Modelo:** `app/Models/MarketplaceRawItem.php`. Cast de `raw_payload` como array asociativo y métodos utilitarios:
  * `getMeliAttribute(string $attributeId)`: Extrae valores limpios desde el arreglo de atributos de Mercado Libre (ej. `BRAND`, `PART_NUMBER`, `POSITION`, `SIDE`).
  * `getPictureUrls()`: Extrae las URLs completas en alta definición de las imágenes.

### 4.2. Dataset Fixture de Autopartes Reales
* **Archivo:** `database/fixtures/mercadolibre_autoparts_fixture.json`.
* Incluye un lote realista de 8 autopartes representativas del deshuesadero en Tepatitlán:
  * Motor 3/4 1.6L HR16DE (Nissan Versa).
  * Transmisión Estándar 6 Vel (Nissan NP300 Frontier).
  * Motor Completo 5.3L V8 EcoTec3 (Chevrolet Silverado 1500).
  * Faro Delantero Derecho LED (Nissan Kicks) con posición y lado.
  * Alternador Valeo 110A (Nissan Urvan NV350).
  * Marcha Arranque (Volkswagen Jetta A6 MK6).
  * Compresor A/C Denso (Toyota Hilux).
  * Cremallera Electroasistida (Ford Figo).
* Contiene estructura idéntica a la API de Mercado Libre: atributos automotrices, dimensiones físicas para logística (`shipping.dimensions`), fotos, precio, stock (`available_quantity`), condición (`used`) y descripción comercial.

### 4.3. Servicio de Inspección y Comando Artisan
* **Servicio:** `app/Services/MercadoLibre/MercadoLibreInspectorService.php`.
  * `fetchAndStoreFromApi($limit, $status, $onProgress)`: Consulta en vivo mediante `MarketplaceClient`.
  * `loadAndStoreFromFixture($fixturePath, $onProgress)`: Carga el dataset fixture para desarrollo local y testing sin cuellos de botella de red.
  * `exportSampleJson($outputPath)`: Genera el archivo consolidado en `storage/app/mercadolibre_sample.json`.
  * Manejo resiliente de almacenamiento para entornos con o sin driver de base de datos local.
* **Comando de Consola:** `app/Console/Commands/MercadoLibreFetchRawItemsCommand.php` (`php artisan meli:fetch-raw`).
  * Opciones: `--limit`, `--status`, `--fixture` y `--output`.
  * Salida interactiva con barra de progreso, tabla de muestra de refacciones con sus marcas y números de parte, y resumen métrico de la extracción.
  * Archivo de muestra generado: `storage/app/mercadolibre_sample.json` (21 KB).

### 4.4. Batería de Pruebas Automatizadas (Sub-paso B2)
* `tests/Unit/MercadoLibreInspectorTest.php`:
  * Carga y validación estructural del fixture de autopartes.
  * Verificación de persistencia y exportación de archivo JSON válido.
  * Acceso rápido a atributos de autopartes (`BRAND`, `MODEL`, `PART_NUMBER`, `SIDE`, `POSITION`).
* `tests/Feature/MercadoLibreFetchRawTest.php`:
  * Disponibilidad de firma y opciones del comando `meli:fetch-raw --help`.
  * Ejecución exitosa en modo `--fixture` y validación del archivo exportado.
  * Control y mensaje descriptivo ante ejecución sin credenciales.
* **Resultado Acumulado Fase B:** **21 pruebas automatizadas / 76 aserciones (100% OK).**

---

## 5. Detalle Técnico del Sub-paso B3 (Análisis de Atributos y Mapeo Formal)

### 5.1. Auditoría y Análisis del Catálogo Real
* A partir del archivo exportado `storage/app/mercadolibre_sample.json`, se auditaron los campos utilizados en publicaciones reales de autopartes:
  * Identificadores: `id` (MLM), `permalink`, `status`.
  * Precios e inventario: `price`, `currency_id`, `available_quantity`.
  * Atributos vehiculares: `BRAND`, `MODEL`, `VEHICLE_YEAR`.
  * Números de pieza: `PART_NUMBER` y `OEM_NUMBER`.
  * Ubicación y montaje: `POSITION` y `SIDE`.
  * Medidas de logística: `shipping.dimensions` (`alto x ancho x largo,peso_gramos`).

### 5.2. Documento Formal de Mapeo
* Se redactó y publicó el documento oficial: [`docs/mapeo_campos_mercadolibre.md`](mapeo_campos_mercadolibre.md).
* **Decisiones Clave Acordadas:**
  1. **Integridad de Existencias:** `available_quantity` se sincroniza exclusivamente mediante [`StockService::setStock()`](../app/Services/StockService.php) con bloqueo transaccional (`lockForUpdate`).
  2. **Integridad de Precios:** `price` pasa por [`PriceService::setPrice()`](../app/Services/PriceService.php). Se habilita el factor multiplicador `MELI_PRICE_FACTOR` (por defecto `1.0`) para permitir descuentos futuros en la tienda propia.
  3. **Extracción Inteligente de Años:** Algoritmo por expresiones regulares para detectar rangos en el título (ej. `"2015-2019" -> year_start=2015, year_end=2019`) cuando en los atributos de Mercado Libre solo venga un año puntual.
  4. **Logística y Paquetería:** Desglose matemático de `shipping.dimensions` en `height_cm`, `width_cm`, `length_cm` y `weight_kg` (gramos / 1000).
  5. **Metadatos Crudos:** Campo `raw_attributes` tipo JSON en `Product` para conservar especificaciones técnicas secundarias (amperaje, cilindrada, válvulas, etc.) sin saturar columnas de la tabla.

---

## 6. Lo que se Realizará a Continuación (Sub-paso B4)

El **Sub-paso B4: Refactorización del Modelo `Product` y Migración del Catálogo Real** consiste en:

1. **Migración no destructiva:** Crear `database/migrations/2026_10_14_000003_add_mercadolibre_fields_to_products_table.php` para incorporar:
   * `mercadolibre_id` (string único e indexado).
   * `mercadolibre_permalink` (string nullable).
   * `part_number` (string indexado nullable).
   * `oem_number` (string nullable).
   * `side` (string nullable).
   * `position` (string nullable).
   * `raw_attributes` (JSON nullable).
   * `last_synced_at` (timestamp nullable).
2. **Actualización del Modelo `Product`:** Actualizar fillables, casts y scopes en [`app/Models/Product.php`](../app/Models/Product.php).
3. **Servicio Mapper:** Crear `App\Services\MercadoLibre\MercadoLibreProductMapper` que aplique las reglas de transformación definidas en el documento de mapeo.
4. **Comando Sincronizador de Catálogo:** Crear comando Artisan `php artisan meli:sync-catalog` para transformar los artículos crudos en registros de `products`, actualizando stock con `StockService` y precios con `PriceService`.
5. **Pruebas Automatizadas:** Crear `tests/Feature/MercadoLibreSyncCatalogTest.php`.

---

*Esta bitácora se actualizará al concluir cada sub-paso de la Fase B.*
