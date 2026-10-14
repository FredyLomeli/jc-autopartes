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
    style B2 fill:#22c55e,stroke:#15803d,color:#fff
    style B3 fill:#22c55e,stroke:#15803d,color:#fff
    style B4 fill:#22c55e,stroke:#15803d,color:#fff
    style B5 fill:#22c55e,stroke:#15803d,color:#fff
    style B6 fill:#22c55e,stroke:#15803d,color:#fff
```

| Sub-paso | Descripción | Estado |
| :---: | :--- | :---: |
| **B1** | Arquitectura de conexión, OAuth 2.0 seguro y Cliente `MarketplaceClient` | **COMPLETADO (100%)** |
| **B2** | Extracción del catálogo y volcado de JSON crudo (`marketplace_raw_items`) | **COMPLETADO (100%)** |
| **B3** | Análisis de atributos de autopartes y documento de mapeo formal | **COMPLETADO (100%)** |
| **B4** | Refactorización del modelo `Product` y comando sincronizador de catálogo | **COMPLETADO (100%)** |
| **B5** | Actualización de `/admin/articulos` y ficha pública con metadatos de ML | **COMPLETADO (100%)** |
| **B6** | Pruebas integrales automatizadas, documentación de cierre y QA manual | **COMPLETADO (100%)** |

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

## 6. Detalle Técnico del Sub-paso B4 (Refactorización de Modelo y Sincronizador)

### 6.1. Migración no Destructiva de la Base de Datos
* **Archivo:** `database/migrations/2026_10_14_000003_add_mercadolibre_fields_to_products_table.php`.
* Incorpora las columnas oficiales mapeadas sin alterar ni borrar datos previos:
  * `mercadolibre_id` (`string(32)`, único e indexado).
  * `mercadolibre_permalink` (`string(500)`, nullable).
  * `part_number` (`string(64)`, indexado, nullable).
  * `oem_number` (`string(64)`, nullable).
  * `side` (`string(32)`, nullable).
  * `position` (`string(32)`, nullable).
  * `raw_attributes` (`json`, nullable).
  * `last_synced_at` (`timestamp`, nullable).

### 6.2. Modelo `Product` Enriquecido
* **Archivo:** [`app/Models/Product.php`](../app/Models/Product.php).
* Fillables y casts actualizados con soporte de array nativo para `raw_attributes` y datetime para `last_synced_at`.
* Nuevos métodos utilitarios:
  * `isMercadoLibreSynced(): bool`: Comprueba si la pieza proviene de Mercado Libre.
  * `getMeliSpec(string $attributeId): ?string`: Consulta directa a atributos técnicos (ej. cilindrada, amperaje, refrigerante).
  * `sideAndPositionLabel(): ?string`: Etiqueta limpia para ficha y tarjeta (ej. `"Delantero • Derecho / Pasajero"`).

### 6.3. Servicio Mapper (`MercadoLibreProductMapper`)
* **Archivo:** `app/Services/MercadoLibre/MercadoLibreProductMapper.php`.
* Convierte payloads heterogéneos de Mercado Libre a datos homogéneos y listos para persistir:
  * Extracción regex de rangos de años en el título (ej. `"2015-2019"` $\rightarrow$ `year_start=2015`, `year_end=2019`).
  * Normalización de marcas y modelos automotrices.
  * Mapeo de categorías ML a las 7 familias de JC Autopartes.
  * Conversión matemática de `shipping.dimensions` a cm y kg.
  * Cálculo de precio web considerando el multiplicador `MELI_PRICE_FACTOR`.

### 6.4. Comando Sincronizador Artisan (`php artisan meli:sync-catalog`)
* **Archivo:** `app/Console/Commands/MercadoLibreSyncCatalogCommand.php`.
* Opciones: `--from-raw`, `--fixture`, `--limit`.
* Transacciones protegidas:
  * Existencias gestionadas exclusivamente mediante [`StockService::setStock()`](../app/Services/StockService.php) con `lockForUpdate`.
  * Precios gestionados exclusivamente mediante [`PriceService::setPrice()`](../app/Services/PriceService.php).
  * Sincronización y actualización de imágenes en la relación `product_photos`.
* Resumen en consola con tabla formateada e indicadores de catálogo.

### 6.5. Batería de Pruebas Automatizadas (Sub-paso B4)
* `tests/Unit/MercadoLibreProductMapperTest.php`:
  * Mapeo completo de refacción mecánica (Motor 3/4 Versa).
  * Mapeo de colisión con posición y lado (Faro LED Kicks).
  * Aplicación del factor de descuento web `MELI_PRICE_FACTOR`.
  * Asignación automática de estado `draft` cuando el stock es 0.
  * Extracción de años multiformato en títulos.
  * Conversión de dimensiones y peso de gramos a kilogramos.
* `tests/Feature/MercadoLibreSyncCatalogTest.php`:
  * Firma y opciones de ayuda del comando.
  * Ejecución exitosa de sincronización en modo `--fixture`.
  * Control del parámetro de límite `--limit`.
* **Resultado Acumulado Fase B:** **30 pruebas automatizadas / 122 aserciones (100% OK).**

---

## 7. Detalle Técnico del Sub-paso B5 (Actualización del Panel Administrativo y Catálogo Público)

### 7.1. Servicio de Sincronización Desacoplado (`MercadoLibreCatalogSyncService`)
* **Archivo:** `app/Services/MercadoLibre/MercadoLibreCatalogSyncService.php`.
* Centraliza el flujo completo de sincronización de catálogo tanto para la consola (`php artisan meli:sync-catalog`) como para el panel administrativo web:
  * Orquesta la lectura de payloads crudos desde BD (`MarketplaceRawItem`), fixtures de pruebas o archivos muestra.
  * Mapea atributos con `MercadoLibreProductMapper`.
  * Persiste de manera segura delegando inventario a `StockService::setStock()` (`lockForUpdate`) y precios a `PriceService::setPrice()`.
  * Sincroniza la galería de fotografías remotas en `product_photos`.
  * Retorna métricas cuantitativas (`total`, `created`, `updated`, `items`).

### 7.2. Controlador y Rutas Administrativas
* **Controlador:** [`app/Http/Controllers/Admin/ProductController.php`](../app/Http/Controllers/Admin/ProductController.php).
  * Nuevo método `syncFromMercadoLibre(MercadoLibreCatalogSyncService $syncService)`.
  * Ejecuta la sincronización bajo demanda y redirecciona con mensaje flash descriptivo.
* **Rutas:** [`routes/web.php`](../routes/web.php).
  * `Route::post('/articulos/sincronizar-meli', [ProductController::class, 'syncFromMercadoLibre'])->name('products.sync-meli');` protegida por autenticación.

### 7.3. Interfaz Vue 3 / Inertia en el Panel Administrativo
* **Listado de Artículos (`resources/js/Pages/Admin/Products/Index.vue`):**
  * Botón en cabecera: **«Sincronizar ML»** con estado de carga interactivo (`isSyncing`) y confirmación previa.
  * Celda de Refacción enriquecida:
    * Badge de número de parte (`P/N: ...`).
    * Badge de ubicación/montaje (`side_position_label`, ej. `Delantero • Lado Izquierdo`).
    * Badge distintivo de Mercado Libre con enlace externo directo a la publicación activa (`ML ↗` / `mercadolibre_permalink`).
* **Formulario de Alta y Edición (`resources/js/Pages/Admin/Products/Form.vue`):**
  * Nuevos campos integrados en formulario reactivo `useForm`:
    * `part_number`: Número de parte del fabricante.
    * `oem_number`: Número de parte OEM original.
    * `side`: Selector de lado (`izquierdo`, `derecho`, `ambos`, no aplica).
    * `position`: Selector de posición (`delantero`, `trasero`, `superior`, `inferior`, `central`, no aplica).
  * Nueva tarjeta lateral: **«Mercado Libre»**:
    * Indicador de estado de sincronización (`Sincronizado`).
    * Enlace directo a la publicación en Mercado Libre.
    * Campos editables de `mercadolibre_id` y `mercadolibre_permalink`.

### 7.4. Vistas Públicas y SEO (Blade)
* **Tarjeta de Catálogo (`resources/views/public/partials/product-card.blade.php`):**
  * Despliegue de `part_number` y `sideAndPositionLabel()` para que mecánicos y clientes identifiquen la pieza inmediatamente desde la rejilla de resultados.
* **Ficha Técnica Detallada (`resources/views/public/catalog/show.blade.php`):**
  * Filas adicionales en la tabla de especificaciones técnicas: Número de parte fabricante, número de parte OEM, lado y posición, y botón externo hacia la publicación en Mercado Libre.
* **Servicio de Catálogo y Microdatos (`app/Services/CatalogService.php`):**
  * `LISTING_COLUMNS` actualizado para incluir `part_number`, `oem_number`, `side`, `position`, `mercadolibre_id` y `mercadolibre_permalink`.
  * `buildProductJsonLd()` actualizado para asignar el `mpn` (Manufacturer Part Number) según `part_number` o fallback a `sku`.

### 7.5. Compilación y Validación de Frontend
* Compilación exitosa de activos de producción con Vite: `npm run build` (0 errores, 3.06s).

### 7.6. Batería de Pruebas Automatizadas
* `tests/Unit/MercadoLibreCatalogSyncServiceTest.php`:
  * Verificación de sincronización con fixtures y límites.
  * Extracción y estructura de payloads.
* **Resultado Acumulado Fase B:** **33 pruebas automatizadas / 133 aserciones (100% OK).**

---

## 8. Detalle Técnico del Sub-paso B6 (Pruebas Integrales y Certificación de Fase B)

### 8.1. Suite de Pruebas Automatizadas de Fase B
* Se ejecutó y consolidó la batería de pruebas automatizadas:
  * Pruebas Unitarias de OAuth, Inspector, Mapper y Sincronizador Desacoplado.
  * Pruebas Funcionales (Feature) de comandos de consola (`meli:fetch-raw`, `meli:sync-catalog`), endpoints OAuth y ruta de sincronización administrativa (`POST /admin/articulos/sincronizar-meli`).
* **Resultado de Certificación Fase B:** **35 pruebas automatizadas / 139 aserciones — 100% OK.**
* **Total Acumulado del Proyecto:** **85 pruebas automatizadas** (50 de Fase A0 + 35 de Fase B).

### 8.2. Documento Formal de Cierre de Fase B
* Se generó el documento maestro [`docs/cierre_fase_B.md`](cierre_fase_B.md), el cual contiene:
  * Resumen ejecutivo y matriz de cumplimiento estricto contra requerimientos.
  * Diagrama de arquitectura del flujo de datos desde la API de Mercado Libre hasta la vista pública.
  * Manual operativo con ejemplos de comandos Artisan y configuración `.env`.
  * Registro de pruebas de certificación.

### 8.3. Actualización de Documentación Raíz
* Se actualizó [`README.md`](../README.md) reflejando el estado de **Fase B completada al 100%**, la guía de comandos de sincronización de catálogo y el resumen global de 85 pruebas automatizadas.

---

## 9. Conclusión de la Fase B y Preparación para la Fase C

Con el **Sub-paso B6** finalizado, la **Fase B queda formalmente cerrada y certificada al 100%**. 

El sistema cuenta ahora con:
1. Una conexión OAuth 2.0 segura con tokens AES-256 autorrefrescables.
2. Un catálogo de autopartes normalizado con números de parte, OEM, lados, posiciones y rangos de años reales.
3. Un canal de sincronización tolerante a fallos que protege el inventario con `StockService::setStock()` (`lockForUpdate`) y los precios con `PriceService::setPrice()`.
4. Controles visuales en `/admin/articulos` y visualización enriquecida en la tienda pública con microdatos Schema.org.

La base queda lista para arrancar la **Fase C: Pasarela de Pago con Mercado Pago y Checkout Seguro** (integración de Mercado Pago Checkout Pro/API, generación de pedidos, decremento de existencias atómico y notificaciones por correo).

---

*Fin de la bitácora de seguimiento de la Fase B.*


