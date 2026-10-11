# Plan de Desarrollo v2 — Proyecto de Prioridades JC Autopartes (proyecto nuevo)

Reemplaza al plan anterior. Se actualiza al cerrar cada fase.

## 1. Decisiones cerradas

| Tema | Decisión |
|---|---|
| Punto de partida | Proyecto Laravel 12 nuevo, sin reutilizar el PMV anterior (no está autorizado) |
| Sitio público | **Blade** + Tailwind + Alpine.js (mejor SEO, sin SSR ni Node en el servidor) |
| Administración | **Vue 3 + Inertia + Vite** |
| Catálogo | Se alimenta de **Mercado Libre**: primero una importación de solo lectura para ver qué campos se usan de verdad y después se modela igual |
| Pagos | **Mercado Pago** (Checkout Pro) |
| Envíos | Paquetexpress, cotización por código postal y medidas del paquete |
| WhatsApp | **API oficial de Meta (Cloud API)**, un solo número, reparto configurable |
| Fuera de alcance por ahora | Inventario por pieza, deshuese de vehículos, compras (segunda etapa, si esta funciona) |

## 2. Arquitectura

- Rutas públicas: controladores con vistas Blade. Rutas `/admin`: Inertia con Vue. Un solo proyecto, una sola base de datos y una sola compilación de Vite.
- Tailwind compartido (mismos colores y tipografías) para que público y administración se vean de la misma familia.
- Carrito en **sesión del servidor**, con formularios normales. Valida existencia en cada paso y funciona aunque falle el JavaScript.
- **Existencia = un número por artículo** (el `available_quantity` de su publicación de Mercado Libre). Se encapsula en un `StockService` único que usan la tienda, el pedido web y la sincronización. Ninguna pantalla cuenta existencias por su cuenta, y la baja se hace dentro de una transacción para que dos compras simultáneas no se lleven la última pieza. Cuando exista el inventario por pieza (segunda etapa), solo cambia cómo se calcula ese número.
- WhatsApp y pagos se programan detrás de interfaces (`WhatsAppChannel`, `PaymentGateway`). Cambiar de proveedor después no obliga a reescribir.
- **Compatible con hosting compartido** (PHP 8.2 o superior, cron, SSL, dominio apuntando a `public`): colas con `schedule:run` cada minuto, notificaciones por consulta periódica en vez de WebSockets, y archivos de Vite compilados en local y subidos ya generados. La API oficial de WhatsApp solo requiere llamadas y webhooks web, por eso no hace falta servidor adicional.

## 3. Orden de trabajo y horas (estimación preliminar)

| Fase | Contenido | Horas |
|---|---|:---:|
| A | Base del proyecto, login, roles y áreas, sistema de diseño, vistas públicas (Blade) y de administración (Vue) con datos de ejemplo → **aprobación del diseño** | 73–95 |
| B | Importación de lectura de Mercado Libre → documento de mapeo de campos → catálogo real y administración de artículos | 42–53 |
| C | Tienda: carrito y checkout reales, Mercado Pago, pedidos, correos, páginas legales | 38–50 |
| D | Envíos con Paquetexpress (peso y medidas, empaquetado, cotización, checkout) | 30–38 |
| E | Sincronización completa Mercado Libre ↔ tienda (stock, precio, fotos, ventas) | 55–65 |
| F | WhatsApp mini CRM (un número, reparto configurable, historial en ficha) + clientes | 82–103 |
| | **Total** | **320–404** |

A $275 la hora son entre $88,000 y $111,100 MXN. Es un orden de magnitud: lo afino al terminar las Fases A y B, cuando se vea el tamaño real del catálogo. La cotización que el cliente ya autorizó fue de 268 h y $73,700 porque suponía una base ya construida; hay que hablarlo con él antes de empezar.

## 4. WhatsApp (Cloud API) — detalles que hay que cerrar

**El número del cliente.** Hay tres caminos:
1. **Pasar el número a la API.** Normalmente deja de usarse en la app del celular. El equipo tendría que trabajar solo desde el sistema.
2. **Coexistencia** (el mismo número en la app WhatsApp Business y en la API). Según una guía de un proveedor oficial, sincroniza los últimos 6 meses de chats y refleja los mensajes entre ambos. Pero se pierden funciones: llamadas, listas de difusión, respuestas rápidas y etiquetas de la app, catálogo; no soporta grupos. Esa guía indica que se activa a través de un proveedor oficial, así que hay que confirmar si se puede hacer directo sin pagar intermediario.
3. **Un número nuevo** para el sistema, y el actual sigue como está. Es lo más seguro para empezar.

**Costos de Meta.** La documentación oficial indica que las respuestas libres dentro de la ventana de 24 horas son gratuitas, y que las plantillas de marketing siempre se cobran. Fuera de la ventana solo se puede escribir con plantillas aprobadas. Las tablas de precios por país (en pesos) están en la documentación de Meta y no las pude leer. Lo que la propuesta anterior decía ("a partir del 1 de octubre de 2026 se cobrará por mensaje de servicio, unos $140 por cada 1,000") **venía de guías de terceros y la documentación oficial no lo respalda**. Hay que reescribir esa nota sin fecha ni cifra, y confirmar la tarifa vigente en el administrador de WhatsApp del cliente.

**Reparto de conversaciones (configurable).** Ajuste con tres modos: rotativo entre usuarios activos (por defecto, porque hoy todos hacen de todo), por áreas (opcionales, se crean cuando el cliente las decida) o cola compartida. Cada usuario con estado disponible/no disponible, y el último agente se queda con el cliente recurrente. En modo por áreas, el primer mensaje recibe un menú con botones o lista para elegir área.

**Trámites del cliente:** cuenta de Meta Business verificada, número registrado y plantillas aprobadas para los avisos de pedido. La verificación tarda días, hay que iniciarla en cuanto se autorice el proyecto.

## 5. Decisiones y accesos pendientes del cliente

**Negocio**
- ¿El precio web es igual al de Mercado Libre o menor? En la tienda propia no se paga la comisión de Mercado Libre, así que hay margen para ofrecer un precio distinto.
- ¿Los precios incluyen IVA?
- Política de devoluciones y garantía para piezas usadas, aviso de privacidad y términos de compra.
- Facturación: no está incluida. El checkout debe decir "Para factura, escríbenos por WhatsApp".
- Recolección en tienda: dirección, horario y quién entrega.
- Piezas que no van por paquetería (motores, cofres, defensas): se marcan como "cotizar por WhatsApp".
- Fotos: Mercado Libre probablemente ya tiene fotos reales por publicación; se importan.

**Accesos**
- Cuenta de **Mercado Pago** (pruebas y producción). Pedirles el costo de meses sin intereses.
- Cuenta corporativa de **Paquetexpress** con acceso a su API.
- App de desarrolladores de **Mercado Libre** y autorización de la cuenta del cliente.
- **Meta Business** verificado y decisión sobre el número.
- **Hosting y dominio** (versión de PHP, SSH y cron) y código postal de origen de los envíos.

## 6. Riesgos

| Riesgo | Mitigación |
|---|---|
| Sobreventa entre tienda y Mercado Libre | `StockService` único, baja en transacción y actualización inmediata a Mercado Libre tras cada venta; aun así queda una ventana pequeña mientras llega el aviso de Mercado Libre |
| Los campos de Mercado Libre no encajan con lo supuesto | Fase B antes de modelar el catálogo; guardar el JSON completo de cada publicación |
| Cuentas que tardan (Meta, Mercado Pago, Paquetexpress) | Pedirlas desde el día uno; avanzar en ambiente de pruebas |
| El equipo deja de poder usar WhatsApp en el celular | Decidir el número antes de empezar la Fase F |
| Antigravity trata cada pantalla como un ticket aparte | Reglas de la sección 7 y criterios de aceptación en cada prompt |

## 7. Reglas para trabajar con Antigravity

1. Una fase por vez, cada una en su rama de git, con criterios de aceptación escritos antes.
2. Cada prompt describe el flujo de negocio completo, no solo la tarea.
3. Una sola fuente de verdad para existencias (`StockService`) y para precios.
4. Nada de lógica duplicada entre pantallas: pedido web, sincronización y administración llaman a los mismos servicios.
5. Al terminar cada punto, reportar archivos modificados.

## 8. Prompt para Antigravity — Fase A (base y vistas)

Copiar y pegar tal cual. Adjuntar al mensaje las capturas de los sitios de referencia.

```
Contexto de negocio: JC Autopartes es un deshuesadero de autopartes usadas. Vamos a
construir su tienda en línea y su administración desde cero. Los artículos reales se
importarán después desde Mercado Libre, así que el esquema de datos de esta fase es
PROVISIONAL y mínimo: solo lo necesario para dibujar las pantallas con datos de ejemplo.
No modeles inventario por pieza, compras, deshuese ni condiciones A/B/C; la condición
será un texto libre (condition_label) hasta que veamos los campos reales de Mercado Libre.

FASE A: base del proyecto y vistas. No implementes pagos, envíos, WhatsApp, Mercado Libre
ni correos. Cada sección en su propio commit.

1. Proyecto
   - Laravel 12 nuevo con el starter kit de Vue + Inertia (autenticación incluida), Vite
     y Tailwind. PHP 8.2 como mínimo; no uses nada que requiera procesos permanentes
     (el hosting final es compartido: colas por cron, sin WebSockets).
   - Rutas públicas con controladores y vistas Blade. Rutas /admin con Inertia + Vue.
   - Tailwind compartido: define colores, tipografías y espaciados en un solo
     tailwind.config y reutilízalos en público y administración.
   - Alpine.js solo en el sitio público, para detalles pequeños.

2. Datos de ejemplo (provisionales)
   - Modelos y migraciones mínimas: Product (title, slug, description, price,
     stock_quantity, condition_label, category_id, part_number, technical_specs,
     is_active, photos), Category, VehicleBrand, VehicleModel y la relación de
     compatibilidad producto ↔ modelo con rango de años (year_start, year_end).
   - Factories y un seeder que cree unos 40 productos de ejemplo de 7 categorías, con
     compatibilidades y fotos de marcador de posición.

3. Sitio público (Blade, renderizado en servidor)
   - Layout con encabezado, buscador, contador de carrito, pie de página y botón de
     WhatsApp. Diseño mobile-first (360 px), accesible (contraste, foco visible,
     etiquetas en formularios).
   - Inicio, catálogo con filtros por marca, modelo, año, categoría y texto (todo en
     la URL con query string, paginación en servidor), y ficha de producto en
     /refacciones/{slug} con foto, ficha técnica, compatibilidades, precio y existencia.
   - SEO: <title> y meta description propios, canonical (las páginas filtradas
     apuntan a la versión sin filtros), Open Graph, datos estructurados JSON-LD de
     tipo Product con offers y availability, /sitemap.xml y /robots.txt.
   - Carrito en sesión del servidor con rutas POST/PATCH/DELETE; valida contra
     stock_quantity en cada operación y muestra el aviso si no alcanza.
   - Checkout como maqueta: datos del comprador, elegir envío o recolección y resumen.
     El botón de pago dice "Pago disponible próximamente".
   - Páginas estáticas con texto de marcador: aviso de privacidad, términos y
     devoluciones y garantía.

4. Administración (Vue + Inertia, bajo /admin, requiere login)
   - Layout con menú lateral organizado por tareas, no por módulos de desarrollo:
     Panel, Pedidos, Artículos, Clientes, Conversaciones, Configuración. Pedidos,
     Clientes y Conversaciones son pantallas vacías con marcador por ahora.
   - Panel con tarjetas de ejemplo.
   - Artículos: tabla con búsqueda, filtros y paginación, y formulario de alta y edición
     con los campos provisionales.

5. Calidad
   - Pruebas de Feature para: filtros del catálogo, que el carrito no acepte más
     cantidad que el stock y que las rutas /admin exijan autenticación.

Criterios de aceptación (verifícalos y repórtalos uno por uno):
- Con el seeder se navega inicio → catálogo filtrado → ficha → carrito → checkout.
- No se puede agregar al carrito más cantidad que stock_quantity.
- El HTML de la ficha de producto trae el contenido y el JSON-LD sin ejecutar JavaScript.
- Ninguna pantalla pública hace scroll horizontal a 360 px de ancho.
- Las rutas /admin redirigen al login si no hay sesión.
- php artisan test pasa completo.

Al terminar cada sección, dime qué archivos tocaste. No avances a pagos, envíos,
WhatsApp ni Mercado Libre.
```
