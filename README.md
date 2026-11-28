# JC Autopartes — Plataforma de Comercio Electrónico y Administración

Sistema web para **JC Autopartes** (deshuesadero y venta de autopartes usadas originales en Tepatitlán de Morelos, Jalisco, México).

Este proyecto combina un **sitio público renderizado en servidor** (Blade + Tailwind CSS + Alpine.js) optimizado para SEO y velocidad en redes móviles, con un **panel administrativo privado** (Vue 3 + Inertia.js + Vite) bajo la ruta `/admin`.

---

## 🚀 Estado del Proyecto — Fases A0, B y C Completadas (100%)

- **Fase A0: Andamiaje visual, capa pública, carrito y administración en Laravel** — Concluida al 100% y respaldada por 50 pruebas automatizadas. Ver detalles en [`docs/cierre_fase_A0.md`](docs/cierre_fase_A0.md).
- **Fase B: Sincronización de Lectura con Mercado Libre y Modelado Definitivo del Catálogo** — Concluida al 100%, certificada con OAuth 2.0 seguro, ingesta cruda, normalización de catálogo automotriz, panel administrativo y comandos de consola. Ver detalles en [`docs/cierre_fase_B.md`](docs/cierre_fase_B.md).
- **Fase C: Pasarela de Pago con Mercado Pago, Checkout Seguro y Gestión de Pedidos** — Concluida al 100%, con flujo transaccional seguro, decremento atómico de inventario (`lockForUpdate`), Webhook IPN verificado criptográficamente con HMAC-SHA256, correos transaccionales, panel de pedidos en Vue 3 y páginas legales formalizadas. Ver detalles en [`docs/cierre_fase_C.md`](docs/cierre_fase_C.md).
- **Optimización de Catálogo (10,000+ piezas) y Lazy Load Público** — Implementado en `CatalogService` (consultas agregadas cacheadas 15 min y memoizadas por petición en `getFilterOptions()`), índices compuestos y `FULLTEXT` en `products` (`2026_11_26_000001_add_performance_indexes_to_products_table`), endpoint ligero `GET /refacciones/lote` con `simplePaginate` y carga progresiva en `/refacciones` con Alpine.js (`IntersectionObserver` + botón *«Cargar más refacciones»* + fallback `<noscript>`). Además se aisló PHPUnit en `phpunit.xml` (`APP_CONFIG_CACHE` / `APP_ROUTES_CACHE` / `APP_EVENTS_CACHE` con `force="true"`) y se añadió bloqueo de seguridad en `tests/TestCase.php` para exigir `sqlite :memory:`.
- **Buscador Combinado, Hardening de Mapper ML, Identidad Oscura y 10 Categorías Reales (Noviembre 2026)** — Ver resumen operativo vigente y pendientes en [`docs/estado_actual_y_pendientes.md`](docs/estado_actual_y_pendientes.md):
  - Búsqueda tokenizada (`CatalogService::applySearchFilter()`) compartida con `/admin/articulos`, con abreviaturas automotrices y años en texto libre.
  - Limpieza de artículos de prueba (`catalog:purge-seeded`), reprocesamiento en bloques `--from-raw` (`syncFromRawChunked()`), años de 2 dígitos, medidas `SELLER_PACKAGE_*`, `SELLER_SKU`, `SIDE_POSITION` y limpieza de marca/modelo.
  - Barra superior oscura (`bg-dark`) con logotipo ampliado y administrable en `/admin/ajustes` (`POST/DELETE /admin/ajustes/logo`).
  - Fuente única de las **10 categorías oficiales** en `config/catalog.php`, árbol oficial de ML (`meli:fetch-categories` + `MercadoLibreCategoryResolver`) y columna `subcategory` visible en `/refacciones/{slug}`.

---

## 🛠️ Stack Tecnológico

- **Framework:** Laravel 12 (PHP 8.2+)
- **Base de Datos:** MySQL / MariaDB (SQLite en pruebas automatizadas)
- **Sitio Público:** Blade + Tailwind CSS + Alpine.js (Server-Side Rendering para indexación y SEO)
- **Panel Administrativo (`/admin`):** Vue 3 + Inertia.js + Vite
- **Reglas de Dominio Centrales:**
  - **Existencias:** Única fuente de verdad en `App\Services\StockService`.
  - **Precios:** Única fuente de verdad en `App\Services\PriceService`.
  - **Lógica de Negocio:** Encapsulada exclusivamente en `app/Services/`.
  - **Arquitectura de Integración:** Abstracciones preparadas detrás de interfaces (`PaymentGateway`, `ShippingQuoteProvider`, `WhatsAppChannel`, `MarketplaceClient`).
- **Compatibilidad con Hosting Compartido:** Diseñado sin WebSockets ni procesos en segundo plano permanentes. Manejo de colas y tareas mediante el cron nativo de Laravel (`schedule:run`).

---

## 🎨 Tokens de Diseño Oficiales

Definidos en `resources/css/app.css` y mapeados en `tailwind.config.js`:
- **Rojo JC (Primario):** `#E31221` (Hover: `#C40E1B`)
- **Carbón (Oscuro):** `#111111`
- **Superficie Neutra:** `#F8F9FA`
- **Bordes:** `#E5E7EB`
- **WhatsApp:** `#25D366`
- **Tipografía:** Inter, Roboto, sans-serif

---

## 💻 Instalación y Configuración Local

### 1. Requisitos Previos
- PHP 8.2 o superior con extensiones activas (`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `curl`, `fileinfo`, `gd`).
- Composer 2+.
- Node.js 18+ y npm.
- MySQL 8.0+ o MariaDB 10.4+.

### 2. Pasos de Instalación
```bash
# 1. Clonar el repositorio y entrar a la carpeta
git clone <url-del-repositorio> jc-autopartes
cd jc-autopartes

# 2. Instalar dependencias de PHP y JavaScript
composer install
npm install

# 3. Configurar variables de entorno
cp .env.example .env
php artisan key:generate

# 4. Configurar tu base de datos en el archivo .env:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=jc_autopartes
# DB_USERNAME=root
# DB_PASSWORD=

# 5. Ejecutar migraciones y semillero idempotente (36 refacciones y admin)
php artisan migrate --seed

# 6. Compilar assets de frontend
npm run build
# O para desarrollo con recarga en caliente:
# npm run dev

# 7. Iniciar el servidor local
php artisan serve
```

### 3. Credenciales de Acceso al Panel Administrativo (Seeder)
- **URL de Acceso:** `http://localhost:8000/login` o `http://localhost:8000/admin`
- **Correo Electrónico:** `admin@jcautopartes.mx`
- **Contraseña:** `password`

---

## 🌐 Guía de Despliegue en Hosting Compartido (cPanel / Apache)

El proyecto está optimizado para entornos de hosting compartido donde no se cuenta con acceso root ni procesos permanentes de Node/Supervisord.

### 1. Estructura de Directorios en el Servidor
En la mayoría de los servidores cPanel existen dos esquemas habituales:
- **Esquema A (Recomendado):** El repositorio se aloja en una carpeta fuera de la raíz web (ej. `/home/usuario/jc-autopartes`) y el DocumentRoot del dominio apunta directamente a `/home/usuario/jc-autopartes/public`.
- **Esquema B (Carpeta `public_html` fija):** Los archivos de Laravel se colocan en `/home/usuario/laravel_app` y el contenido de la carpeta `public/` se copia directamente dentro de `/home/usuario/public_html`.

### 2. Variables de Entorno Clave (`.env`)
```ini
APP_NAME="JC Autopartes"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://jc-autopartes-tepa.com

# Correo transaccional (Gmail SMTP con Contraseña de Aplicación)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_SCHEME=tls

# Base de datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=usuario_jcautopartes
DB_USERNAME=usuario_dbuser
DB_PASSWORD="password_seguro"

# Configuración de Almacenamiento e Imágenes
# Si storage:link no está permitido por tu proveedor, define la ruta física absoluta de public_html/storage:
FILESYSTEM_DISK=public
FILESYSTEM_PUBLIC_ROOT=/home/usuario/public_html/storage
```

### 3. Tareas Programadas y Colas (Cron de cPanel)
Configura una tarea programada (Cron Job) que se ejecute **cada minuto**:
```bash
* * * * * cd /home/usuario/jc-autopartes && php artisan schedule:run >> /dev/null 2>&1
```

### 4. Permisos de Archivos
Asegúrate de otorgar permisos de escritura al servidor web en las carpetas de almacenamiento y caché:
```bash
chmod -R 775 storage bootstrap/cache
```

### 5. Optimización de Caché de Producción
Al desplegar o actualizar en producción, ejecuta:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 📦 Empaquetado Automático para Despliegue

Se incluye un script en PowerShell para preparar paquetes de despliegue listos para subir a cPanel (sin `node_modules`, sin `.git` y con los assets de producción compilados):

```powershell
# Ejecutar desde la raíz del proyecto
.\scripts\package-deploy.ps1
```
El script generará un archivo `dist/jc-autopartes-deploy-YYYYMMDD.zip` listo para extraer en tu hosting.

---

## 🔄 Integración con Mercado Libre (Fase B)

El sistema incluye sincronización unidireccional de lectura con Mercado Libre para transformar publicaciones comerciales en refacciones estructuradas para el e-commerce:

### 1. Variables de Entorno Requeridas (`.env`)
```ini
MELI_APP_ID=tu_app_id
MELI_CLIENT_SECRET=tu_client_secret
MELI_REDIRECT_URI="${APP_URL}/admin/integraciones/mercadolibre/callback"
MELI_COUNTRY_ID=MLM
MELI_PRICE_FACTOR=1.0
```

### 2. Comandos de Consola Artisan
```bash
# 1. Descargar árbol oficial de categorías ML desde marketplace_raw_items
php artisan meli:fetch-categories

# 2. Reprocesar publicaciones crudas ya almacenadas en BD (en bloques de 250)
php artisan meli:sync-catalog --from-raw

# 3. Limpiar artículos semilla sin mercadolibre_id (--dry-run por defecto, --force para ejecutar)
php artisan catalog:purge-seeded --force
```

### 3. Sincronización desde la Web
En `/admin/articulos`, los administradores pueden pulsar **«Sincronizar ML»** para importar o refrescar refacciones por lotes mediante scroll (`POST /admin/integraciones/mercadolibre/sincronizar-lote`).

---

## 🧪 Pruebas Automatizadas y Seguridad en Contenedor

Las pruebas corren exclusivamente en `sqlite :memory:` (aisladas en `phpunit.xml` y protegidas con bloqueo de seguridad en `tests/TestCase.php` para nunca afectar la base de datos MariaDB aunque `php artisan optimize` esté activo):

```bash
docker exec -u www-data -i jc-autopartes-app php artisan test
```

*(Nota: la suite incluye pruebas nuevas para caché de filtros, Lazy Load `/refacciones/lote`, búsqueda combinada, subida de logotipo, `catalog:purge-seeded`, `meli:sync-catalog --from-raw` y las 10 categorías oficiales. Algunas pruebas antiguas de Fase A0/B que dependían de `database/fixtures/mercadolibre_autoparts_fixture.json` o del diseño previo de portada quedaron desactualizadas tras el rediseño UI y el catálogo real).*

