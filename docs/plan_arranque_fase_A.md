# Plan de arranque — Fase A (visual) y modelo de trabajo con Antigravity

## 1. Objetivo de la Fase A
Que el cliente apruebe cómo se ve su tienda y cómo se capturan los artículos **antes** de programar la lógica. Se aprueba sobre un prototipo navegable, que cuesta poco cambiar, y después se construye en Laravel copiándolo fielmente.

## 2. Lo que necesito de ti para arrancar (insumos)
1. Los **enlaces de los dos sitios de referencia** y qué le gusta al cliente de cada uno (el encabezado, los filtros, la tarjeta de producto, el tono, etc.).
2. Las **imágenes del negocio** (fachada, patio, bodega, mostrador, piezas) y el **logo** en la mejor calidad posible, idealmente vectorial.
3. Datos reales para el pie de página y contacto: dirección, teléfono, horarios, WhatsApp, redes.
4. Una lista corta de lo que **no** quiere ver (colores, estilos, frases).

De los sitios de referencia se toman estructura y patrones (navegación, orden de la información, filtros), no textos, imágenes ni código.

## 3. Pasos y compuertas

| # | Paso | Quién | Salida |
|---|---|---|---|
| 0 | Entrega de insumos | Tú | Sección 2 completa |
| 1 | Brief de diseño: paleta, tipografía, tono, estructura de páginas y qué tomar de cada referencia | Claude | Guía de estilo de una página |
| 2 | Prototipo navegable en HTML: inicio, catálogo con filtros, ficha, carrito, checkout, y de administración el panel, la lista de artículos y el formulario de artículo, con datos de ejemplo realistas, en celular y escritorio | Claude | Enlace para el cliente |
| 3 | Revisión con el cliente: máximo 2 rondas de ajustes (según las condiciones comerciales) | Tú | Lista de observaciones |
| 4 | **Compuerta 1:** aprobación del diseño por escrito (correo o WhatsApp) | Cliente | Diseño congelado |
| 5 | Construcción en Laravel fiel al prototipo (Fase A del plan v2) | Antigravity | Rama con la Fase A |
| 6 | Demo en servidor de pruebas y revisión contra la especificación | Tú + Claude | Lista de criterios cumplidos |
| 7 | **Compuerta 2:** entrega de cuentas (sección 4); sin ellas no inicia la Fase B | Cliente | Accesos listos |

**Sobre el momento de pedir las cuentas.** Pedirlas al terminar la Fase A es el límite, no el mejor momento. La verificación de Meta, la cuenta corporativa de Paquetexpress y la aprobación de una app de desarrolladores pueden tardar días y no dependen de ti. Conviene **enviarle la lista el día que autoriza el arranque** y dejar la entrega como condición para iniciar la Fase B.

## 4. Cuentas que debe entregar el cliente

| Cuenta | Fase | Qué se necesita |
|---|---|---|
| Mercado Libre | B | App de desarrolladores creada en su cuenta, o creada por ti y autorizada por él (OAuth). ID de la app, clave secreta y URL de redirección |
| Mercado Pago | C | Cuenta de negocio y credenciales de prueba y de producción |
| Paquetexpress | D | Cuenta corporativa, credenciales de API y la documentación de su API |
| Meta (WhatsApp) | F | Business Manager verificado, cuenta de WhatsApp Business, número decidido y acceso para ti como administrador o socio |
| Correo de envío | C | Cuenta SMTP o servicio de correo transaccional para confirmaciones de pedido |
| Hosting y dominio | A (demo) y despliegue | Acceso (cPanel o SSH), versión de PHP, cron, SSL y control del DNS |

Reglas: todas las cuentas están a nombre del cliente. El acceso se da por invitación o con credenciales de API, nunca contraseñas por chat o correo; si hace falta compartir un secreto, se usa un gestor de contraseñas. Los secretos viven en el archivo `.env` del servidor, nunca en el repositorio.

## 5. Dirección visual preliminar (a confirmar con las imágenes y el logo)
Pediste algo serio y elegante, que transmita profesionalismo y buena atención, no lujo automotriz. Mi propuesta:

- **Fondo claro y neutro** (blancos y grises cálidos) para que las fotos de las piezas se vean bien, con encabezado y pie en carbón oscuro y **un solo color de acento** tomado del logo, usado con moderación. El logo que vi en el BRD es oscuro y monocromático, así que el acento lo definimos con tus fotos.
- **Tipografía** sans-serif legible, con titulares firmes. Sin neón, degradados vistosos ni sombras exageradas.
- **Confianza visible:** garantía, atención, dirección, horarios y WhatsApp siempre a la mano, con fotos reales del negocio.
- **Datos claros en cada pieza:** compatibilidad, existencia, precio y condición.

El PMV anterior era oscuro con rosa vivo (se ve en el código de su layout público), y no encaja con este tono.

## 6. Modelo de trabajo con Antigravity
Recomiendo mantener la división de roles (tú diriges, yo especifico y reviso, Antigravity construye), pero cambiando **cómo** se le dirige. Lo que falló con el PMV no fue el reparto, fue que recibió una lista de módulos sueltos y trabajó cada uno como un ticket aparte.

1. **Contexto fijo en el repositorio.** Un archivo `AGENTS.md` en la raíz (o el archivo de reglas que use tu versión de Antigravity; verifica cómo lo llama) con el negocio, el stack y las reglas de abajo. Así no se repite en cada prompt.
2. **Una especificación por fase:** objetivo, flujo de negocio completo, criterios de aceptación y una lista de "fuera de alcance".
3. **Plan antes de código.** Pídele un plan de implementación y apruébalo (o pásamelo) antes de que escriba. Si tu versión ya lo genera, úsalo.
4. **Trozos pequeños:** una rama por fase, un commit por sección, pruebas automáticas y un reporte de archivos tocados.
5. **Evidencia por criterio:** que muestre capturas o resultado de pruebas para cada criterio de aceptación, no solo "listo".
6. **Revisión cruzada al cerrar cada fase.** Me mandas el zip y capturas y lo reviso contra la especificación. Así encontramos las 7 desconexiones del PMV.
7. **Lo que no se delega sin revisión humana:** pagos y webhooks (revisar línea por línea), migraciones que borran datos, cualquier cosa que toque secretos, y las decisiones de negocio.

### Plantilla de `AGENTS.md`

```
# JC Autopartes — Contexto del proyecto

Negocio: tienda en línea y administración de un deshuesadero de autopartes usadas.
Los artículos vienen de Mercado Libre. WhatsApp y pagos usan APIs oficiales.

Stack
- Laravel 12 (PHP 8.2+), MySQL.
- Sitio público: Blade + Tailwind + Alpine.js (renderizado en servidor, por SEO).
- Administración: Vue 3 + Inertia + Vite, bajo /admin.
- Hosting compartido: SIN procesos permanentes. Colas por cron (schedule:run),
  sin WebSockets, sin Node en el servidor. Los assets de Vite se compilan en local.

Reglas
1. Trabaja solo dentro del alcance de la fase indicada. Lo marcado "fuera de
   alcance" no se toca.
2. Existencias: una sola fuente de verdad, StockService. Precios: PriceService.
   Ninguna pantalla ni controlador cuenta existencias o calcula precios por su cuenta.
3. Integraciones detrás de interfaces: PaymentGateway, ShippingQuoteProvider,
   WhatsAppChannel, MarketplaceClient.
4. La lógica de negocio vive en servicios, no en controladores ni en componentes Vue.
5. Nunca confíes en precios o totales que manden el navegador o un webhook sin
   verificar. Recalcula en el servidor.
6. Los secretos solo en .env. Nunca en el repositorio ni en logs.
7. Webhooks: verificar firma e idempotencia (el mismo aviso puede llegar varias veces).
8. Antes de codificar, presenta un plan y espera aprobación. Commits pequeños,
   pruebas por cada criterio de aceptación y reporte de archivos modificados.
9. Si falta una decisión de negocio, pregunta. No la supongas.

Nunca hagas
- Regenerar módulos que ya funcionan.
- Duplicar lógica que ya existe en un servicio.
- Agregar dependencias sin explicar por qué.
```

## 7. Siguiente paso
Mándame los insumos de la sección 2. Con eso armo el brief de diseño y el prototipo, y el prompt de Antigravity para la Fase A se actualiza para que construya el diseño ya aprobado.
