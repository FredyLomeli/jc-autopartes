# Estado Actual del Proyecto y Pendientes — JC Autopartes

**Última actualización:** 26 de noviembre de 2026  
**Rama activa:** `feat/meli-sync-real`

---

## 1. Avance Real Construido (~55%–60% del alcance total del proyecto)

### Módulos operativos en código y probados con datos reales
1. **Sitio público SSR (Blade + Tailwind + Alpine.js):**
   - Portada con carrusel de banners administrables, marcas, filosofía empresarial, contacto y WhatsApp compacto en 2 líneas.
   - Encabezado superior oscuro (`bg-dark`) con logotipo ampliado y editable desde `/admin/ajustes`.
   - Catálogo (`/refacciones`) con **10,027** publicaciones reales sincronizadas desde Mercado Libre (`34,059` fotos), paginación inicial SSR (12 tarjetas) y **Lazy Load** (`GET /refacciones/lote` con `simplePaginate` + `IntersectionObserver` + botón manual + fallback `<noscript>`).
   - Filtros cacheados (`CatalogService::getFilterOptions()`, 15 min) e índices compuestos + `FULLTEXT` en `products` (`2026_11_26_000001_add_performance_indexes_to_products_table`).
   - Buscador combinado por palabras, abreviaturas automotrices y años (`CatalogService::applySearchFilter()`), compartido con `/admin/articulos`.
   - Ficha de producto (`/refacciones/{slug}`) con galería, JSON-LD y **Subcategoría oficial de Mercado Libre** (`products.subcategory`) sin exponerla como filtro en el catálogo general.
2. **Catálogo unificado en 10 categorías (`config/catalog.php`):**
   - `Carrocería y Colisión` (7d), `Iluminación` (7d), `Interior y Seguridad` (7d), `Suspensión y Dirección` (7d), `Frenos` (7d), `Eléctrico y Sensores` (7d), `Enfriamiento y Clima (A/C)` (7d), `Motores` (30d), `Transmisiones` (30d), `Partes de Motor e Inyección` (15d).
   - Clasificación mediante `MercadoLibreCategoryResolver` + árbol oficial en español de los `465` `category_id` (`storage/app/meli_categories_tree.json` generado por `php artisan meli:fetch-categories`).
3. **Limpieza y mapeo real de Mercado Libre (`--from-raw`):**
   - `MercadoLibreProductMapper` soporta años de 2 dígitos (`10-14`, `96-99`, `01-07`), medidas/pesos desde `SELLER_PACKAGE_*`, `SELLER_SKU`, `OEM`, `SIDE_POSITION` y limpieza de marcas/modelos frente a atributos contaminados.
   - `MercadoLibreCatalogSyncService::syncFromRawChunked()` procesa `marketplace_raw_items` en bloques de 250.
   - Comando `php artisan catalog:purge-seeded` (`--dry-run` / `--force`) para retirar artículos semilla sin `mercadolibre_id`, protegiendo los referenciados en `order_items`.
4. **Panel Administrativo (`/admin`, Vue 3 + Inertia.js):**
   - Artículos (`/admin/articulos`), Pedidos (`/admin/pedidos`), Banners (`/admin/banners`), FAQs (`/admin/faqs`), Promociones y Cupones (`/admin/promociones`), Ajustes de Empresa (`/admin/ajustes` con subida de PDFs legales y logotipo).
5. **Carrito, Promociones, Checkout y Pagos (Fase C):**
   - `StockService` (`lockForUpdate`), `PriceService` (campañas + cupones), checkout en 3 pasos, pasarela `MercadoPagoPaymentGateway` + webhook HMAC-SHA256 y correos transaccionales *(requiere validación final con credenciales productivas de Mercado Pago).*

---

## 2. Seguridad de Pruebas (PHPUnit vs. Base de Datos Productiva)
- En `phpunit.xml` se definieron `APP_CONFIG_CACHE`, `APP_ROUTES_CACHE` y `APP_EVENTS_CACHE` con `force="true"` apuntando a rutas en `/tmp` para que PHPUnit ignore `bootstrap/cache/config.php` aunque `php artisan optimize` esté activo en el contenedor.
- En `tests/TestCase.php`, `setUp()` aborta con `RuntimeException` si la configuración estuviera cacheada o si el driver efectivo no es `sqlite :memory:`.

---

## 3. Qué Falta por Desarrollar (Pendientes Clave)
1. **Fase D — Paquetexpress Real:**
   - Actualmente `AppServiceProvider` enlaza `SimulatedPaquetexpressQuoteProvider` (tarifas estimadas de ejemplo). Falta conectar la API corporativa de Paquetexpress para cotización real y generación de guías/rastreo.
2. **Fase E — Sincronización Bidireccional Continua con Mercado Libre:**
   - **Cron actual (`routes/console.php`):** `SyncMercadoLibreIncrementalJob` está configurado con `limit: 50, autoContinue: false` y aún no corre `schedule:run` en producción, además de consultar solo `status = active`.
   - **Falta:** Webhook oficial de Mercado Libre (`items` y `orders_v2`) para actualizar en tiempo real cambios de stock/precio/pausa desde ML, barrido completo periódico (`autoContinue: true` + detección de pausadas/cerradas) y llamada a `MercadoLibreClient::updateAvailableStock()` al confirmarse un pedido pagado en la tienda web.
   - **Nota sobre tokens OAuth:** `MercadoLibreOAuthService::getValidAccessToken()` ya renueva automáticamente bajo demanda el `access_token` (cada 6h) usando el `refresh_token` (`offline_access`) cuando se llama a la API de ML.
3. **Slice 5 (Rediseño UI) — Comisiones de Mercado Libre y Precio Web Neto:**
   - Consultar comisiones por categoría de ML y transparentar/automatizar el margen entre precio ML y precio directo en tienda.
4. **Fase F — Clientes y WhatsApp Cloud API (mini CRM):**
   - `/admin/clientes` sigue como vista *«Próximamente»* (`PlaceholderModuleController`).
   - `WhatsAppChannel` es solo interfaz; falta la implementación oficial de Meta Cloud API, módulo de conversaciones, reparto configurable y gestión de roles/áreas.
