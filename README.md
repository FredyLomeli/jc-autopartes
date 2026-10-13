# JC Autopartes — Plataforma de Comercio Electrónico y Administración

Sistema web para **JC Autopartes** (deshuesadero y venta de autopartes usadas originales en Tepatitlán de Morelos, Jalisco, México).

Este proyecto combina un **sitio público renderizado en servidor** (Blade + Tailwind CSS + Alpine.js) optimizado para SEO y velocidad en redes móviles, con un **panel administrativo privado** (Vue 3 + Inertia.js + Vite) bajo la ruta `/admin`.

---

## 🚀 Estado del Proyecto — Fase A0 Completada (100%)

La **Fase A0: Andamiaje visual, capa pública, carrito y administración en Laravel** se encuentra concluida al 100% y respaldada por 50 pruebas automatizadas.

Para consultar la matriz completa de criterios de aceptación y evidencias técnicas, consulta [`docs/cierre_fase_A0.md`](docs/cierre_fase_A0.md).

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
APP_URL=https://tudominio.com

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

## 🧪 Pruebas Automatizadas

El proyecto cuenta con una batería completa de pruebas funcionales y de integración (Feature Tests):

```bash
php artisan test
```

**Resultado:**
- `Tests\Feature\PublicLayoutTest`: Verificación de layout público Blade, tokens de diseño y SEO básico.
- `Tests\Feature\StockAndPriceServiceTest`: Idempotencia de seeders, contratos y fuentes de verdad en `StockService` y `PriceService`.
- `Tests\Feature\PublicCatalogAndSeoTest`: Filtros por query string, canonicals, JSON-LD Schema.org/Product, sitemap y robots.txt.
- `Tests\Feature\CartAndCheckoutTest`: Persistencia de carrito en sesión, validación de stock y checkout visual.
- `Tests\Feature\AdminAccessAndDashboardTest`: Protección por autenticación, redirecciones y métricas de panel.
- `Tests\Feature\AdminProductsCrudTest`: Operaciones CRUD completas y sincronización en tiempo real con el catálogo público.
- **50 pruebas / 301 aserciones — 100% en verde.**
