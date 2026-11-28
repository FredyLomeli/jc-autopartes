# Estado Actual del Proyecto y Pendientes — JC Autopartes

**Última actualización:** 27 de noviembre de 2026  
**Rama activa:** `feat/meli-sync-real`

---

## 1. Avance Real Construido (~55%–60% del alcance total del proyecto)

### Módulos operativos en código y probados con datos reales
1. **Sitio público SSR (Blade + Tailwind + Alpine.js):**
   - Portada con carrusel de banners administrables, marcas, filosofía empresarial, contacto y WhatsApp compacto en 2 líneas.
   - Encabezado superior oscuro compacto (`bg-dark`) con logotipo ampliado y editable desde `/admin/ajustes`, manteniendo intacta la barra roja de navegación (`bg-primary`).
   - Portada con carrusel de banners a ancho completo proporcional (`w-full h-auto block`) sin recorte, CTA móvil debajo de la imagen y CTA en escritorio alineado a la derecha al 65% vertical.
   - Imágenes completas de productos (`object-contain` sobre fondo blanco) en tarjetas, galería, carrito y checkout; ficha de producto (`/refacciones/{slug}`) con botón WhatsApp simplificado leyendo `contact_whatsapp_1` de `/admin/ajustes` y módulo de **Compartir / Copiar enlace + WhatsApp** para PWA/móvil.
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
   - Artículos (`/admin/articulos`), **Auditoría de Pesos y Medidas (`/admin/articulos/auditoria-medidas`)** con edición inline, exportación CSV UTF-8, detección de incompletos (`19`) / motores (`<50 kg`) / transmisiones (`<20 kg`) y protección `dimensions_locked` ante futuras sincronizaciones ML (`php artisan catalog:audit-dimensions`), Pedidos (`/admin/pedidos`), Banners (`/admin/banners`), FAQs (`/admin/faqs`), Promociones y Cupones (`/admin/promociones`), Ajustes de Empresa (`/admin/ajustes` con subida de PDFs legales y logotipo).
5. **Carrito, Promociones, Checkout y Pagos (Fase C):**
   - `StockService` (`lockForUpdate`), `PriceService` (campañas + cupones), checkout en 3 pasos, pasarela `MercadoPagoPaymentGateway` + webhook HMAC-SHA256 y correos transaccionales *(actualmente con credenciales vacías / modo simulación; pendiente implementar flujo OAuth Marketplace de Mercado Pago y validación con correo SMTP real).*

---

## 2. Seguridad de Pruebas (PHPUnit vs. Base de Datos Productiva)
- En `phpunit.xml` se definieron `APP_CONFIG_CACHE`, `APP_ROUTES_CACHE` y `APP_EVENTS_CACHE` con `force="true"` apuntando a rutas en `/tmp` para que PHPUnit ignore `bootstrap/cache/config.php` aunque `php artisan optimize` esté activo en el contenedor.
- En `tests/TestCase.php`, `setUp()` aborta con `RuntimeException` si la configuración estuviera cacheada o si el driver efectivo no es `sqlite :memory:`.

---

## 3. Qué Falta por Desarrollar (Pendientes Clave y Acuerdos Cerrados con el Cliente)

### Bloque A — Ajustes de UI/UX Público y Ficha de Producto *(Completado — QA Aprobado)*
- Encabezado oscuro compacto (`layouts/public.blade.php`), banners proporcionales a ancho completo (`w-full h-auto block`) con CTA reubicado en móvil y al 65% derecho en escritorio (`home.blade.php`), fotos `object-contain`, botón de compartir enlace y botón WhatsApp editable (`CompanySettingService` -> `contact_whatsapp_1`) ya implementados y validados.

### Bloque B — Precios (`-10%` vs. ML sin IVA), Facturación, Mercado Pago OAuth, MSI y Reembolsos
1. **Regla de precios públicos (`PriceService` + `mercadolibre_price`) *(Completado — QA Aprobado)*:**
   - Columna `mercadolibre_price` en `products`, fórmula `(Precio ML / 1.16) * (1 - descuento_ML)` (`10%` configurable en `/admin/ajustes` con botón **Recalcular Precios Web**), comando `catalog:recalculate-web-prices` y etiquetas **«Precio sin IVA / + IVA»** en catálogo, ficha, carrito y checkout.
   - *(Nota: validar ante contador el esquema de mostrar precios sin IVA y cobrar +IVA bajo demanda por reglas SAT/PROFECO).*
2. **Facturación opcional con +16% IVA en Checkout (`OrderService`, `checkout/index.blade.php`):** agregar checkbox *«Requiero factura (+16% IVA)»*; al marcarse solicita datos fiscales (**RFC, Razón Social, Régimen Fiscal, Uso de CFDI, C.P. fiscal**) y suma **+16% de IVA tanto al subtotal de productos (tras descuentos/cupones) como al costo de envío**.
3. **Mercado Pago OAuth (Modelo Marketplace) + Control de MSI:**
   - Implementar flujo OAuth 2.0 (`authorization_code` + `offline_access`) desde aplicación Marketplace propia para que el cliente vincule su cuenta desde `/admin/ajustes` sin compartir llaves técnicas.
   - Renovación automática de `access_token`/`refresh_token` en segundo plano (vigencia rotativa de 180 días; solo requiere reautorizar manualmente si vence sin renovarse), `notification_url` + Webhook centralizado y pruebas en Sandbox (cuenta Marketplace + vendedor + comprador de prueba).
   - Toggle en `/admin/ajustes` para **habilitar o deshabilitar Meses Sin Intereses (MSI)** por completo; cuando esté activo, topar máximo de **3 a 6 MSI** (`installments: 6`, costo financiero absorbido por la tienda).
4. **Devoluciones y Reembolsos desde `/admin/pedidos`:**
   - Modal en detalle del pedido para emitir reembolsos **totales o parciales** vía API de Mercado Pago (`POST /v1/payments/{id}/refunds`), con motivo obligatorio.
   - Casilla explícita (*checkbox*) para decidir si las piezas devueltas **reingresan al inventario** (`StockService`, con sincronización automática hacia ML en Fase E) y correo automático al cliente notificando el reembolso.

### Bloque C — Infraestructura de Salida a Producción
1. **Dominio definitivo:** `https://jc-autopartes-tepa.com` (DNS, SSL/HTTPS, `APP_URL`, callbacks OAuth y URLs finales de webhooks).
2. **Correo transaccional vía Gmail SMTP:** configurar `MAIL_MAILER=smtp` con cuenta Gmail y Contraseña de Aplicación (2FA) para confirmaciones de pedido, contacto y reembolsos *(considerando el límite diario ~500 envíos en cuentas gratuitas de Gmail)*.

### Bloque D — Paquetexpress Real + Auditoría de Medidas/Pesos (Fase D)
1. **Cotización universal (sin excepciones, incluye motores):** se elimina la excepción previa de ocultar motores/cofres/defensas bajo *«Cotizar por WhatsApp»*; **todos** los artículos cotizan por medidas y peso desde origen **C.P. `47600`** (convenio corporativo Paquetexpress en trámite de accesos Sandbox/Producción). Validar soporte **LTL / tarima (>70 kg)** en la API contratada y definir si se automatiza emisión de guías PDF.
2. **Auditoría de medidas/pesos en `/admin` *(Completado)*:** implementado en `/admin/articulos/auditoria-medidas` (`ProductDimensionsAuditService`, `DimensionsAudit.vue`, CSV UTF-8 y `dimensions_locked` en `MercadoLibreCatalogSyncService`). **Pendiente operativo del cliente:** revisar y corregir pesos reales sobre los artículos detectados (`19` sin `SELLER_PACKAGE_*` y motores/cajas con pesos bajos en `not_specified`) antes de conectar Paquetexpress en vivo.

### Bloque E — Sincronización Bidireccional Continua ML ↔ e-Commerce y Analítica Multicanal (Fase E + Slice 5)
- **Cron actual (`routes/console.php`):** `SyncMercadoLibreIncrementalJob` está configurado con `limit: 50, autoContinue: false` y aún no corre `schedule:run` en producción.
- **Sincronización bidireccional:** habilitar `schedule:run` + Webhooks oficiales de ML (`items`, `orders_v2`), barrido completo (`autoContinue: true`) incluyendo manejo de publicaciones activas y **pausadas** (`paused`), y llamada automática a `MercadoLibreClient::updateAvailableStock()` al confirmarse o reembolsarse (con reingreso) un pedido en tienda web. *(Nota: `MercadoLibreOAuthService::getValidAccessToken()` ya renueva el token ML de 6h usando `refresh_token`)*.
- **Indicadores de ventas por canal y 10 categorías:** módulo/reporte en `/admin` comparando ventas (`$` y piezas) entre **MercadoLibre** (consultando órdenes cerradas de ML vía API) y la **Tienda Web**, desglosado con monto y porcentaje de participación por cada una de las **10 categorías oficiales** (además del cálculo de comisión/margen neto de Slice 5).

### Bloque F — Clientes y WhatsApp Cloud API CRM (Fase F — Confirmado Activo)
- `/admin/clientes` sigue como vista *«Próximamente»* (`PlaceholderModuleController`).
- `WhatsAppChannel` es solo interfaz; este módulo **no se pospone** y queda activo en el roadmap para implementar Meta Cloud API oficial, bandeja/mini CRM de conversaciones, reparto configurable y roles/áreas.
- **Pendiente inmediato del cliente:** confirmar el número único definitivo para el CRM (tentativamente WhatsApp Línea 1) e iniciar la verificación de Meta Business + aprobación de plantillas.
