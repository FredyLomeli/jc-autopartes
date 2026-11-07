# Desglose de Sub-Pasos (Entregables Pequeños y Verificables) — Rediseño UI y Panel Admin

**Rama:** `rediseno-ui-afr`  
**Enfoque:** Entregas atómicas, funcionales e inmediatamente verificables visualmente en el navegador.

---

## 📌 RUTA DE ENTREGABLES (SUB-PASOS)

### 🔹 SUB-PASO 1.1: Base de Datos de Ajustes y Panel de Información de Empresa
* **Objetivo:** Permitir al cliente editar en `/admin/ajustes` todos los datos de su negocio (teléfonos, WhatsApps, correos, dirección de Tepatitlán, horarios, redes sociales, slogan, misión, visión y valores).
* **Entregable Verificable en Navegador:**
  - Menú lateral en `/admin` con acceso a **"Ajustes de Empresa"**.
  - Formulario en Vue 3 con pestañas organizadas (Contacto, Redes, Filosofía Empresarial, Horarios).
  - Al guardar cambios, los datos se persisten en base de datos.
  - Precargado con los datos oficiales de Tepatitlán y el documento 2026 de Juan Carlos Barba.

---

### 🔹 SUB-PASO 1.2: Panel de Administración del Carrusel de Banners
* **Objetivo:** Crear el gestor de imágenes para el banner principal de la página.
* **Entregable Verificable en Navegador:**
  - Sección `/admin/banners` en Vue 3 + Inertia.
  - Tabla para ver banners existentes, activar/desactivar, cambiar orden numérico, título, subtítulo, enlace y subir/seleccionar imagen.
  - Modal o formulario para agregar/editar/eliminar banners.

---

### 🔹 SUB-PASO 1.3: Panel de Administración de Preguntas Frecuentes (FAQ)
* **Objetivo:** Permitir gestionar las dudas frecuentes (garantías, envíos, métodos de pago, devoluciones).
* **Entregable Verificable en Navegador:**
  - Sección `/admin/faqs` en Vue 3 + Inertia.
  - Formulario para crear preguntas y respuestas, ordenarlas y asignar categoría.

---

### 🔹 SUB-PASO 2.1: Header estilo AFR, Topbar y Logotipo
* **Objetivo:** Nuevo encabezado público limpio, con logotipo más grande y barras de navegación claras.
* **Entregable Verificable en Navegador:**
  - Visitar cualquier página pública (`/` o `/catalogo`) y ver el nuevo Header estilo AFR.
  - Topbar con teléfono de oficina, WhatsApp y horario tomados dinámicamente de los ajustes.
  - Menú simplificado y responsive (Inicio, Catálogo, Nosotros, FAQs, Contacto, Carrito).

---

### 🔹 SUB-PASO 2.2: Footer, Mapa Interactivo, Formulario y Botón WhatsApp Compacto
* **Objetivo:** Completar el marco global de la tienda pública.
* **Entregable Verificable en Navegador:**
  - Pie de página completo con datos de Tepatitlán, redes sociales, horarios y mapa embebido de Google Maps.
  - Formulario de contacto funcional en el pie de página.
  - Cintillo/Línea horizontal destacada de garantía JC Autopartes antes del footer.
  - Botón flotante de WhatsApp más pequeño y estilizado con menú para elegir Ventas Línea 1 o Línea 2.

---

### 🔹 SUB-PASO 3.1: Hero Banner Carrusel y Buscador Principal (Home estilo AFR)
* **Objetivo:** Rediseñar la cabecera visual de la página de inicio.
* **Entregable Verificable en Navegador:**
  - Entrar a `/` y ver el carrusel de imágenes administrables funcionando (Alpine.js / Swiper / Tailwind), con buscador centrado y destacado de refacciones.

---

### 🔹 SUB-PASO 3.2: Cuerpo de Página de Inicio (Filosofía, Confianza y Garantía)
* **Objetivo:** Home limpia, espaciada, sin exceso de texto, enfocada en la empresa y catálogo clave.
* **Entregable Verificable en Navegador:**
  - Bloque visual con Misión, Visión y Valores (según documento oficial 2026).
  - Categorías principales con tarjetas modernas.
  - Acordeón de FAQs integrado en Home o página dedicada `/preguntas-frecuentes`.

---

### 🔹 SUB-PASO 4.1: Módulo de Cupones de Descuento (Admin + Lógica)
* **Objetivo:** Crear cupones de descuento y conectarlos al `PriceService`.
* **Entregable Verificable en Navegador:**
  - Sección `/admin/cupones` para crear cupones (código, porcentaje o monto fijo, vigencia).
  - Pruebas unitarias de cálculo en `PriceService`.

---

### 🔹 SUB-PASO 4.2: Rediseño de Checkout en Etapas con Cupones
* **Objetivo:** Checkout guiado por pasos para mejorar conversión.
* **Entregable Verificable en Navegador:**
  - Proceso de compra en `/checkout` dividido en: Paso 1 (Datos del Cliente y Dirección) -> Paso 2 (Método de Envío) -> Paso 3 (Cupón de Descuento y Pago Mercado Pago).

---

### 🔹 SUB-PASO 5.1: API Mercado Libre — Revisión de Comisiones y Precios Netos
* **Objetivo:** Transparentar comisiones y permitir que el precio en la web no incluya la comisión de ML.
* **Entregable Verificable en Navegador:**
  - Verificación en `/admin/articulos` del precio de lista web vs precio con comisión de Mercado Libre.
  - Tienda pública mostrando el precio web sin recargo de comisión.
