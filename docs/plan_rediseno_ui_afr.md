# Plan de Rediseño UI (Estilo AFR), Gestión de Contenidos y Nuevas Funcionalidades

**Rama:** `rediseno-ui-afr`  
**Referencia visual:** [AFR Autopartes](https://afrautopartes.com/)  
**Fecha:** Octubre 2026  
**Documento Fuente:** Identidad y Filosofía Corporativa 2026 (Juan Carlos Barba) + Datos Oficiales Tepatitlán

---

## 1. Información Oficial del Negocio (Incorporada en Configuración/BD)

* **Razón/Nombre:** JC Autopartes — Matriz Tepatitlán ("Todo pa' tu máquina!")
* **Slogan Institucional:** *"Soluciones exactas que le devuelven la vida a tu auto."*
* **Misión, Visión y Valores:** Extraídos del documento oficial (Honestidad Técnica, Agilidad en la Solución, Transparencia, Acompañamiento Profesional, Seguridad y Confianza).
* **Domicilio Matriz:** Bertha Padilla Gutierrez #2020, Col. Los Adobes, C.P. 47656, Tepatitlán de Morelos, Jalisco.
* **Ubicación Google Maps:** [https://maps.app.goo.gl/HpyvbhFdg5vkYB167](https://maps.app.goo.gl/HpyvbhFdg5vkYB167)
* **Teléfono Oficina:** 378 688 2205
* **WhatsApp Ventas Línea 1:** 378 145 5737 (`https://wa.me/message/MKVOP7GHAWCIK1`)
* **WhatsApp Ventas Línea 2:** 378 272 4234 (`https://wa.me/message/5REIIC2COHFVH1`)
* **Email:** jcautopartes.mx@gmail.com
* **Horarios:** Lunes a Viernes: 9:00 am – 6:00 pm | Sábados: 9:00 am – 2:00 pm
* **Tienda Oficial Mercado Libre:** `https://listado.mercadolibre.com.mx/_CustId_546980216`

---

## 2. Requerimientos Analizados

1. **Gestor de Contenidos en Panel Admin (`/admin` en Vue 3 + Inertia):**
   - Panel para editar: Datos de contacto, redes sociales, horarios, textos de empresa (misión/visión/valores/slogan), banners/carruseles y preguntas frecuentes (FAQ).
   - Tabla `site_settings` o `company_profiles` con clave/valor o campos tipados + tabla `home_banners` (título, subtítulo, imagen, link, orden, activo) + tabla `faqs`.
2. **Rediseño Header y Menú (estilo AFR):**
   - Logo de mayor tamaño y presencia visual.
   - Top-bar con teléfono, WhatsApp y horario.
   - Navegación limpia: Inicio, Catálogo, Nosotros, Preguntas Frecuentes, Contacto, Carrito.
3. **Página Principal (Home) Estilo AFR:**
   - Carrusel/Hero Banner visual dinámico con imágenes automotrices de calidad y buscador principal integrado.
   - Menos saturación de texto, espacios generosos y tarjetas limpias.
   - Sección de Filosofía Empresarial e Institucional (Misión, Visión, Valores con iconos limpios).
   - Sección de Garantía Escrita destacada.
   - Módulo de Contacto interactivo (Mapa Google Maps + Formulario de contacto directo).
4. **Elementos Globales:**
   - Botón de WhatsApp flotante más pequeño y estilizado (selector de línea 1 o 2).
   - Vínculos a redes sociales y tienda oficial de Mercado Libre.
   - Cintillo/Línea de garantía al final de cada página antes del footer.
   - Apartado de Preguntas Frecuentes (FAQ) interactivo con Alpine.js.
5. **Checkout en Etapas:**
   - Separar el checkout en pasos claros (Paso 1: Datos de envío/contacto → Paso 2: Método de entrega/cotización → Paso 3: Revisión y Pago Mercado Pago).
6. **Módulo de Cupones de Descuento:**
   - Tabla `coupons` (código, tipo: porcentaje/fijo, valor, fecha exp, uso max, activo).
   - `PriceService` como única fuente de verdad para aplicar descuentos.
7. **Precios y Comisiones de Mercado Libre:**
   - Sincronización API para calcular y transparentar la comisión.
   - Separación de precio tienda web (sin comisión ML) vs precio Mercado Libre.

---

## 3. Segmentación en Slices de Trabajo (Sub-pasos)

Para mantener el control y permitir revisiones de QA manual, dividimos en 5 slices:

### 🔹 Slice 1: Modelo de Configuración, Banners, FAQs y Panel Admin
- Migración para tablas: `site_settings`, `home_banners`, `faqs`.
- `CompanySettingService` para cachear y suministrar la información a las vistas Blade y controladores.
- Vistas y CRUD en Panel Admin (`/admin/ajustes`, `/admin/banners`, `/admin/faqs`) en Vue 3 + Inertia.
- Seeder con toda la información oficial y textos de filosofía 2026.

### 🔹 Slice 2: Header, Footer, WhatsApp Compacto y Componentes Globales (Estilo AFR)
- Rediseño de `layouts/app.blade.php`: Header con logo más grande, navegación limpia, topbar con teléfonos/horarios.
- Botón de WhatsApp flotante miniaturizado.
- Cintillo global de garantía antes del footer.
- Footer con mapa embebido, formulario de contacto, redes sociales y links legales.

### 🔹 Slice 3: Rediseño Integral de la Página de Inicio (Home) y Página FAQs
- Vista `home.blade.php`: Carrusel administrable de banners, buscador visual centrado, bloques de valor corporativo (Misión/Visión/Valores según documento de Juan Carlos Barba), refacciones destacadas y módulo de contacto.
- Componente/Página de FAQs con Alpine.js.

### 🔹 Slice 4: Checkout en Etapas y Cupones de Descuento
- Migración para `coupons` y campo `coupon_id` / `discount_amount` en órdenes.
- Extensión de `PriceService` para validar y calcular cupones.
- Rediseño de `checkout.blade.php` con etapas (stepper interactivo).

### 🔹 Slice 5: Comisión Mercado Libre, Precio Web Neto (sin IVA + Factura) y Analítica Multicanal
- Regla oficial definida con el cliente: el precio de MercadoLibre incluye ~10% de comisión ML + 16% IVA, por lo que en la tienda web se calcula:
  `Precio Web (sin IVA) = (Precio ML / 1.16) * (1 - margen_ML)` (`10%` por defecto configurable en `/admin/ajustes`).
- Etiquetado explícito **"Sin IVA / + IVA"** en tarjetas, ficha, carrito y checkout; checkbox *"Requiero factura (+16% IVA)"* en checkout con captura de datos fiscales aplicando 16% sobre productos y envío.
- Consulta de órdenes/comisiones en ML para mostrar en `/admin` indicador de ventas de MercadoLibre y comparativa por las 10 categorías oficiales (`$` y % de participación web vs. ML).

### 🔹 Slice 6: Refinamientos UI/UX Post-Revisión Cliente
- **Encabezado oscuro compacto:** reducir altura de `<header class="bg-dark">`, logo y carrito sin modificar la barra roja de navegación (`bg-primary`).
- **Carrusel de banners sin recorte:** ancho completo proporcional (`w-full h-auto block`) sin deformar ni recortar imágenes y reubicación de texto/botón CTA (debajo/fuera de la imagen en móvil; orientado a la derecha al 65% vertical en escritorio). Se conserva el botón flotante de WhatsApp actual.
- **Fotos de producto completas:** `object-contain` con fondo neutro/blanco en tarjetas, galería principal, miniaturas y relacionados.
- **Compartir enlace en ficha:** botón para copiar URL y compartir por WhatsApp (pensado también para vista instalada como app/PWA móvil sin barra de URL).
- **WhatsApp en ficha editable y simplificado:** tomar `contact_whatsapp_1` de `/admin/ajustes` por defecto y simplificar visualmente el botón.

---

## 4. Estado de Progreso
- [x] Creación de rama `rediseno-ui-afr`.
- [x] Recopilación de información oficial y documento de identidad 2026.
- [x] Slice 1: Modelos, migraciones y panel administrativo de contenidos.
- [x] Slice 2: Header, Footer, Redes, WhatsApp y cintillo de garantía.
- [x] Slice 3: Home estilo AFR, carrusel y FAQs.
- [x] Slice 4: Checkout por etapas, cotizador por C.P., cupones y documentos legales en PDF.
- [x] Optimización de Rendimiento y Lazy Load en Catálogo (`/refacciones` + `GET /refacciones/lote`): caché de filtros (`CatalogService::getFilterOptions()`), índices compuestos/FULLTEXT en `products`, scroll infinito con Alpine.js (`IntersectionObserver` + botón manual + `<noscript>`) y aislamiento/bloqueo preventivo de tests en `phpunit.xml` y `TestCase.php`.
- [x] Buscador combinado por palabras, abreviaturas y años (`CatalogService::applySearchFilter()`), limpieza de artículos semilla (`catalog:purge-seeded`) y reprocesamiento `--from-raw` en bloques (`syncFromRawChunked()`).
- [x] Barra superior oscura (`bg-dark`), logotipo ampliado y editable en `/admin/ajustes`, y unificación de las **10 categorías oficiales** (`config/catalog.php`) + subcategoría oficial de ML en ficha (`/refacciones/{slug}`).
- [ ] Slice 5: Precio web neto (`-10%` ML sin IVA + factura opcional), comisiones ML y comparativa de ventas por 10 categorías.
- [x] Slice 6: Refinamientos UI/UX post-revisión cliente (header compacto, banner ancho completo sin recorte con CTA al 65% derecho, fotos `object-contain`, compartir enlace y WhatsApp editable simplificado).
