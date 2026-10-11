# Prompt para Antigravity — Fase A0: andamiaje visual en Laravel

Antes de pegar el prompt:
1. Crea una carpeta vacía y un repositorio git. Abre la carpeta en Antigravity.
2. Pega el contenido de la plantilla `AGENTS.md` (está en `plan_arranque_fase_A.md`, sección 6) como archivo `AGENTS.md` en la raíz, o en el archivo de reglas que use tu versión.
3. Pega el prompt de abajo. Pídele el plan primero y apruébalo antes de que escriba código.

---

## Prompt

Vamos a construir desde cero la tienda en línea y la administración de JC Autopartes (deshuesadero de autopartes usadas en México). Lee `AGENTS.md`: son reglas fijas del proyecto.

**Esta fase (A0) es SOLO la capa visual y el andamiaje, con datos de ejemplo.** No hay pagos, envíos, sincronización con Mercado Libre ni WhatsApp. El objetivo es que el cliente pueda abrir un sitio de pruebas y ver cómo se verá su tienda y cómo se capturan los artículos.

**Primero entrégame un plan** (estructura de carpetas, migraciones, rutas, lista de vistas y orden de commits) y espera mi aprobación antes de escribir código.

### 1. Proyecto
- Laravel 12, PHP 8.2+, MySQL. Rama `fase-a0`, commits pequeños.
- Sitio público: Blade + Tailwind + Alpine.js. Administración: Vue 3 + Inertia + Vite bajo `/admin`, con login (Laravel Breeze con Inertia/Vue o equivalente) y un usuario administrador de prueba en el seeder.
- El Tailwind debe ser compartido y los **colores, tipografías y radios deben vivir en un solo lugar como variables** (tokens), porque la paleta definitiva llega después de que veamos las referencias y el logo. Usa por ahora una paleta provisional: fondo claro neutro, encabezado y pie en carbón oscuro, un solo acento azul sobrio. Nada de neón, degradados vistosos ni sombras exageradas. Tono serio y profesional.
- Los assets de Vite se compilan en local. El proyecto debe funcionar en hosting compartido (sin Node ni procesos permanentes en el servidor).

### 2. Datos de ejemplo (provisionales)
Un modelo `Product` (artículo) con campos provisionales: título, slug, SKU, categoría, marca y modelo del vehículo, año inicial y final, condición (A/B/C), precio, existencia, descripción, peso y medidas (alto, ancho, largo), estado (publicado/borrador) y fotos (tabla aparte). Aclara en un comentario que **estos campos se reemplazarán por los campos reales de Mercado Libre en la Fase B**, así que no agregues lógica que dependa de ellos.
Seeders idempotentes con 30–40 artículos realistas (marcas comunes en México: Nissan, Chevrolet, Toyota, Ford, Honda, Volkswagen) y fotos de relleno (placeholders locales, no descargues imágenes de otros sitios).

### 3. Sitio público (Blade)
- Inicio: encabezado con buscador por pieza y por vehículo (marca, modelo, año), categorías, piezas destacadas, bloque de confianza (garantía, atención, horarios, WhatsApp), pie de página con datos de ejemplo.
- Catálogo `/refacciones` con filtros funcionales sobre los datos de ejemplo (categoría, marca, modelo, año, condición, rango de precio), orden y paginación. Los filtros viajan en la URL.
- Ficha `/refacciones/{slug}`: galería, precio, condición, existencia, compatibilidad, datos técnicos, botón de agregar al carrito y botón de WhatsApp (enlace `wa.me` con el nombre de la pieza).
- Carrito en sesión del servidor (agregar, cambiar cantidad, quitar) y pantalla de checkout **solo visual**: captura de datos y código postal, resumen y un botón de pago deshabilitado con la leyenda "Disponible en la siguiente etapa". Sin cálculo de envío todavía.
- Contacto, y páginas de aviso de privacidad, términos y garantía con texto de relleno marcado como "pendiente de revisión legal".
- SEO desde ya: títulos y meta descripciones por página, URLs limpias, canonical (las páginas con filtros apuntan al catálogo sin filtros), Open Graph, JSON-LD de Product en la ficha, `sitemap.xml` y `robots.txt`. Un solo `<h1>` por página, HTML semántico, imágenes con `alt`.
- Diseño adaptable: celular primero, luego escritorio.

### 4. Administración (Vue 3 + Inertia)
- Layout con menú lateral y encabezado. Menú simple: Panel, Artículos, Pedidos (vacío, "Próximamente"), Clientes (vacío, "Próximamente"), Configuración. Sin etiquetas internas de desarrollo ("M1", "Módulo 3").
- Panel: tarjetas de resumen con cifras de ejemplo claramente marcadas como demostrativas.
- Artículos: tabla con búsqueda, filtros (estado, categoría), paginación y acciones; formulario de crear/editar con secciones (datos generales, vehículos compatibles, precio y existencia, peso y medidas, fotos con carga y orden, publicación). Debe guardar de verdad en la base de datos con validación. El precio y la existencia se leen y se guardan solo a través de `PriceService` y `StockService` (pueden ser implementaciones mínimas de un solo campo por ahora).
- Estados vacíos, de carga y de error cuidados.

### 5. Servidor de pruebas
Documenta en un `README` los pasos para desplegar en hosting compartido (PHP 8.2+, `public` como raíz, `.env`, `php artisan migrate --seed`, subir `public/build` ya compilado, cron de `schedule:run`) y deja un script o comando que prepare el paquete de despliegue.

### Criterios de aceptación (muéstrame evidencia de cada uno: captura o salida de prueba)
1. `migrate:fresh --seed` corre sin errores y crea los datos de ejemplo.
2. Todas las rutas públicas listadas cargan en celular y escritorio sin errores en consola.
3. Los filtros del catálogo cambian los resultados y la URL, y la página filtrada tiene canonical al catálogo sin filtros.
4. La ficha incluye JSON-LD válido, y `sitemap.xml` lista los artículos publicados.
5. El carrito conserva su contenido entre páginas y no deja cantidades mayores a la existencia.
6. Un administrador puede entrar a `/admin`, crear, editar y despublicar un artículo, y el cambio se ve en el sitio público.
7. Un usuario sin sesión no puede entrar a `/admin`.
8. Cambiar un color en el archivo de tokens cambia todo el sitio (público y administración).
9. Pruebas automáticas para el carrito, el filtro del catálogo y la protección de `/admin`.

### Fuera de alcance (no lo toques)
Pagos, cálculo de envío, sincronización con Mercado Libre, WhatsApp, inventario por pieza, deshuese, compras, facturación, roles y permisos finos. Tampoco copies textos, imágenes ni código de otros sitios.

Si falta una decisión de negocio, pregunta; no la supongas.
