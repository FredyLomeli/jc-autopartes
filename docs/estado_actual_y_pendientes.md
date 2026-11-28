# Estado Actual del Proyecto y Pendientes — JC Autopartes

**Última actualización:** 27 de noviembre de 2026  
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
   - `StockService` (`lockForUpdate`), `PriceService` (campañas + cupones), checkout en 3 pasos, pasarela `MercadoPagoPaymentGateway` + webhook HMAC-SHA256 y correos transaccionales *(actualmente con credenciales vacías / modo simulación; pendiente implementar flujo OAuth Marketplace de Mercado Pago y validación con correo SMTP real).*

---

## 2. Seguridad de Pruebas (PHPUnit vs. Base de Datos Productiva)
- En `phpunit.xml` se definieron `APP_CONFIG_CACHE`, `APP_ROUTES_CACHE` y `APP_EVENTS_CACHE` con `force="true"` apuntando a rutas en `/tmp` para que PHPUnit ignore `bootstrap/cache/config.php` aunque `php artisan optimize` esté activo en el contenedor.
- En `tests/TestCase.php`, `setUp()` aborta con `RuntimeException` si la configuración estuviera cacheada o si el driver efectivo no es `sqlite :memory:`.

---

## 3. Qué Falta por Desarrollar (Pendientes Clave y Acuerdos Cerrados con el Cliente)

### Bloque A — Ajustes de UI/UX Público y Ficha de Producto
1. **Encabezado oscuro compacto (`layouts/public.blade.php`):** reducir altura de la banda superior (`<header class="bg-dark">`), tamaño del logo y bloque del carrito para evitar que ocupe demasiado alto de pantalla y deje ver el banner desde el primer vistazo. **La barra roja de navegación (`bg-primary`) se mantiene intacta.**
2. **Carrusel de banners sin recorte y CTA reubicado (`public/home.blade.php`):** evitar recorte/deformación mediante ajuste proporcional (`object-contain`) con fondo oscuro lateral, y reubicar el bloque de título/botón sobre el banner (en móvil fuera/debajo de la imagen para no taparla; en escritorio alineado hacia la derecha). El botón circular flotante de WhatsApp se conserva en su posición actual.
3. **Imágenes completas en catálogo y ficha (`product-card.blade.php`, `catalog/show.blade.php`):** cambiar `object-cover` por `object-contain` sobre fondo neutro/blanco en tarjetas, galería principal, miniaturas y relacionados para que todas las refacciones se muestren completas dentro del marco.
4. **Compartir enlace del producto (`catalog/show.blade.php`):** botón de compartir en la ficha con copia rápida del enlace (y *Web Share API* / envío directo por WhatsApp), pensado especialmente para usuarios que instalan la tienda como app móvil/PWA donde no se ve la barra de URL.
5. **Botón de WhatsApp editable y simplificado en ficha (`CatalogController@show`, `catalog/show.blade.php`):** leer por defecto `contact_whatsapp_1` desde `CompanySettingService` (`/admin/ajustes`) en lugar del número fijo en `config('business')`, con diseño más limpio y directo.

### Bloque B — Precios (`-10%` vs. ML sin IVA), Facturación, Mercado Pago OAuth, MSI y Reembolsos
1. **Regla de precios públicos (`PriceService` + sync ML):** calcular precio web base con la fórmula:
   $$\text{Precio Web (sin IVA)} = \left(\frac{\text{Precio ML}}{1.16}\right) \times (1 - \text{descuento\_ML})$$
   con porcentaje de ajuste configurable desde `/admin/ajustes` (`10%` por defecto). Mostrar explícitamente **«Sin IVA / + IVA»** en tarjetas, ficha, carrito y checkout. *(Nota: el cliente debe validar el esquema de mostrar/cobrar IVA solo bajo factura ante su contador por reglas SAT/PROFECO).*
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
2. **Reporte auditor de medidas/pesos en `/admin`:** auditoría real sobre los **10,027** productos crudos (`marketplace_raw_items`) detectó **19** artículos sin `SELLER_PACKAGE_*` y **850** artículos en modo `not_specified` donde ML trae pesos genéricos irreales (ej. los motores `Motores` en ML traen `1.75 kg`–`11.48 kg`). Se construirá un reporte/filtro en `/admin` para que el cliente revise y corrija pesos/dimensiones, y una bandera de protección para que futuras sincronizaciones de ML no sobrescriban medidas editadas manualmente.

### Bloque E — Sincronización Bidireccional Continua ML ↔ e-Commerce y Analítica Multicanal (Fase E + Slice 5)
- **Cron actual (`routes/console.php`):** `SyncMercadoLibreIncrementalJob` está configurado con `limit: 50, autoContinue: false` y aún no corre `schedule:run` en producción.
- **Sincronización bidireccional:** habilitar `schedule:run` + Webhooks oficiales de ML (`items`, `orders_v2`), barrido completo (`autoContinue: true`) incluyendo manejo de publicaciones activas y **pausadas** (`paused`), y llamada automática a `MercadoLibreClient::updateAvailableStock()` al confirmarse o reembolsarse (con reingreso) un pedido en tienda web. *(Nota: `MercadoLibreOAuthService::getValidAccessToken()` ya renueva el token ML de 6h usando `refresh_token`)*.
- **Indicadores de ventas por canal y 10 categorías:** módulo/reporte en `/admin` comparando ventas (`$` y piezas) entre **MercadoLibre** (consultando órdenes cerradas de ML vía API) y la **Tienda Web**, desglosado con monto y porcentaje de participación por cada una de las **10 categorías oficiales** (además del cálculo de comisión/margen neto de Slice 5).

### Bloque F — Clientes y WhatsApp Cloud API CRM (Fase F — Confirmado Activo)
- `/admin/clientes` sigue como vista *«Próximamente»* (`PlaceholderModuleController`).
- `WhatsAppChannel` es solo interfaz; este módulo **no se pospone** y queda activo en el roadmap para implementar Meta Cloud API oficial, bandeja/mini CRM de conversaciones, reparto configurable y roles/áreas.
- **Pendiente inmediato del cliente:** confirmar el número único definitivo para el CRM (tentativamente WhatsApp Línea 1) e iniciar la verificación de Meta Business + aprobación de plantillas.
