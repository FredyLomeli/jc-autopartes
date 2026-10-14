# Documento Formal de Mapeo de Campos: Mercado Libre ↔ JC Autopartes

**Proyecto:** JC Autopartes  
**Fase:** Fase B (Sub-paso B3)  
**Fecha de Publicación:** 14 de Octubre de 2026  
**Insumo de Auditoría:** `storage/app/mercadolibre_sample.json` (Publicaciones y atributos reales de autopartes)  
**Estado:** **APROBADO PARA IMPLEMENTACIÓN DE ESQUEMA (SUB-PASO B4)**  

---

## 1. Introducción y Propósito del Mapeo

El objetivo de este documento es definir formalmente la correspondencia técnica entre la carga útil (JSON crudo) entregada por la API de **Mercado Libre México (MLM)** y el modelo de datos de la tienda y administración de **JC Autopartes**.

Este mapeo garantiza que:
1. Ningún atributo automotriz relevante (número de parte, posición, lado, medidas de paquete, compatibilidad) se pierda al sincronizar.
2. La actualización de precios y existencias se realice exclusivamente a través de las fuentes de verdad centrales: [`PriceService`](../app/Services/PriceService.php) y [`StockService`](../app/Services/StockService.php).
3. Los datos de empaque y peso volumétrico queden listos para la futura integración con la API de **Paquetexpress (Fase D)**.

---

## 2. Matriz General de Mapeo de Campos

| # | Campo Mercado Libre (JSON) | Tipo ML | Campo en Base de Datos (`products`) | Tipo en Laravel | Regla de Transformación y Validación |
| :---: | :--- | :--- | :--- | :--- | :--- |
| **1** | `id` | `string` | `mercadolibre_id` | `string(32)` (Unique) | Identificador unívoco de la publicación en ML (ej. `MLM3001001`). Es la llave de sincronización. |
| **2** | `permalink` | `string` | `mercadolibre_permalink` | `string(500)` (Nullable) | URL canónica de la publicación en Mercado Libre para enlace directo desde la administración y ficha. |
| **3** | `title` | `string` | `title` | `string(255)` | Título comercial de la refacción (máx. 60 caracteres en ML, hasta 255 en tienda). |
| **4** | *Derivado de title + id* | `string` | `slug` | `string(255)` (Unique) | Slug amigable para SEO generado con `Str::slug("{$title}-{$id}")`. |
| **5** | Atributo `PART_NUMBER` | `string` | `part_number` | `string(64)` (Nullable, Index) | Número de parte original del fabricante (ej. `HR16DE-7782`, `32010-4KH0B`). |
| **6** | Atributo `OEM_NUMBER` | `string` | `oem_number` | `string(64)` (Nullable) | Número de parte de equipo original de agencia automotriz. |
| **7** | Atributo `PART_NUMBER` o `id` | `string` | `sku` | `string(64)` (Unique) | Código interno SKU. Si existe `PART_NUMBER` se usa en mayúsculas; de lo contrario se usa el prefijo `JC-` + `mercadolibre_id`. |
| **8** | `category_id` (MLMxxxx) | `string` | `category` | `string(64)` | Mapeo de la categoría de Mercado Libre a las 7 categorías oficiales de JC Autopartes (ver Sección 4). |
| **9** | Atributo `BRAND` | `string` | `vehicle_brand` | `string(64)` (Index) | Marca del vehículo (ej. `Nissan`, `Chevrolet`, `Toyota`, `Volkswagen`, `Ford`, `Honda`). |
| **10** | Atributo `MODEL` | `string` | `vehicle_model` | `string(64)` (Index) | Modelo del vehículo (ej. `Versa`, `Silverado 1500`, `NP300 Frontier`, `Kicks`, `Hilux`, `Figo`). |
| **11** | Atributo `VEHICLE_YEAR` / Título | `string` | `year_start` | `integer` (Index) | Año inicial de compatibilidad. Extraído del atributo o mediante expresión regular sobre el título. |
| **12** | Atributo `VEHICLE_YEAR` / Título | `string` | `year_end` | `integer` (Index) | Año final de compatibilidad. Extraído del atributo o mediante expresión regular sobre el título. |
| **13** | Atributo `SIDE` | `string` | `side` | `string(32)` (Nullable) | Lado de instalación (`Derecho`, `Izquierdo`, `Ambos`, `Central`, `N/A`). |
| **14** | Atributo `POSITION` | `string` | `position` | `string(32)` (Nullable) | Posición (`Delantero`, `Trasero`, `Superior`, `Inferior`, `N/A`). |
| **15** | `condition` | `string` | `condition` | `string(16)` | Siempre `"used"` en Mercado Libre. Se traduce a `"Pieza usada original verificada"`. |
| **16** | `price` | `float` | `price` | `decimal(10,2)` | **Única fuente de verdad:** [`PriceService::setPrice()`](../app/Services/PriceService.php). Multiplicado por el factor web (`MELI_PRICE_MULTIPLIER`, por defecto 1.0). |
| **17** | `available_quantity` | `integer` | `stock` | `integer` | **Única fuente de verdad:** [`StockService::setStock()`](../app/Services/StockService.php) con bloqueo transaccional (`lockForUpdate`). |
| **18** | `status` | `string` | `status` | `string(16)` | `active` con stock > 0 se mapea a `published`. Publicaciones `paused`, `closed` o con stock = 0 se mapean a `draft`. |
| **19** | `shipping.dimensions` | `string` | `height_cm`, `width_cm`, `length_cm`, `weight_kg` | `decimal(8,2)` | Desglose de medidas y peso para cotización de paquetería (ver Sección 5). |
| **20** | `pictures` (array) | `array` | Tabla `product_photos` | Relación `HasMany` | Descarga/sincronización de URLs de alta resolución (`secure_url`), ordenadas por posición (`sort_order`). |
| **21** | Atributos secundarios | `array` | `raw_attributes` | `json` (Nullable) | Almacenamiento íntegro de especificaciones técnicas (cilindrada, válvulas, amperaje, voltaje, etc.). |
| **22** | Timestamp de importación | `timestamp` | `last_synced_at` | `timestamp` (Nullable) | Fecha y hora exacta de la última sincronización con la API de Mercado Libre. |

---

## 3. Reglas de Extracción Especiales

### 3.1. Extracción de Años de Compatibilidad (`year_start` y `year_end`)
Mercado Libre suele guardar un solo año en el atributo `VEHICLE_YEAR` (ej. `2017`), pero en el título de la refacción el vendedor coloca el rango completo compatible (ej. `"Nissan Versa 2015-2019"` o `"2016 a 2022"`).

**Algoritmo de Extracción:**
1. Buscar en el `title` un patrón de rango: `/(19\d{2}|20\d{2})\s*(?:-|a|\/)\s*(19\d{2}|20\d{2})/i`.
   - Si se encuentra: `year_start = $1` y `year_end = $2`.
2. Si no hay rango en el título, buscar un año único en el título: `/(19\d{2}|20\d{2})/`.
   - Si se encuentra: `year_start = $1` y `year_end = $1`.
3. Si no hay año en el título, tomar el valor numérico del atributo `VEHICLE_YEAR`.
4. Si ninguno existe: `year_start = 2015` y `year_end = año_actual` (fallback seguro).

---

### 3.2. Mapeo de Categorías de Mercado Libre a JC Autopartes

La tabla de correspondencia entre el árbol de categorías de Mercado Libre y las 7 familias oficiales de JC Autopartes:

| Categoría Oficial JC Autopartes | Categorías y Raíces de Mercado Libre | Plazo de Garantía Oficial |
| :--- | :--- | :---: |
| **Motores** | `MLM187514` (Motores de Autos), `MLM187514` (Motores 3/4 y Completos) | **30 días** |
| **Transmisiones** | `MLM187515` (Cajas de Cambio Manuales y Automáticas), Transmisión CVT | **30 días** |
| **Partes Internas** | Cabezas de motor, Cigüeñales, Árboles de levas, Monoblocks, Pistones | **15 días** |
| **Colisión y Carrocería** | `MLM187516` (Faros, Calaveras, Salpicaderas, Cofres, Puertas, Fascias) | **7 días** |
| **Suspensión y Dirección** | `MLM187519` (Cajas de dirección, Horquillas, Amortiguadores, Mangos) | **7 días** |
| **Eléctrico y Sensores** | `MLM187517` (Alternadores, Marchas, Computadoras ECU, Cuerpos Aceleración) | **7 días** |
| **Enfriamiento** | `MLM187518` (Compresores A/C, Radiadores, Motoventiladores, Condensadores) | **7 días** |

---

### 3.3. Desglose Logístico de Paquetes (`shipping.dimensions`)

Mercado Libre entrega las dimensiones en una sola cadena formateada como:
`"{alto}x{ancho}x{largo},{peso_gramos}"` (ejemplo: `"85x65x70,85000"`).

**Algoritmo de Conversión:**
1. Separar dimensiones lineales y peso mediante coma `,`.
2. Separar las dimensiones lineales por `x`:
   - `height_cm = (float) $dim[0]`
   - `width_cm = (float) $dim[1]`
   - `length_cm = (float) $dim[2]`
3. Convertir gramos a kilogramos:
   - `weight_kg = round((float) $gramos / 1000, 2)` (ej. `85000 gr -> 85.00 kg`).
4. Si `shipping.dimensions` viene vacío o no especificado:
   - Se aplican valores por defecto basados en la categoría del artículo (ej. Alternador: 5.0 kg, Faro: 4.0 kg, Motor: 85.0 kg).

---

### 3.4. Regla de Precios Web vs. Mercado Libre
* Mercado Libre descuenta una comisión por venta que oscila entre el 13% y el 17% según la categoría.
* Se añade el parámetro de configuración `mercadolibre.price_factor` en `config/mercadolibre.php` (por defecto `1.00`).
* Si el cliente autoriza un precio especial en la tienda web (ej. 5% de descuento por compra directa sin comisión de ML), se configura `MELI_PRICE_FACTOR=0.95`, y el importador calculará automáticamente:
  $$\text{Precio Web} = \text{round}(\text{price\_ml} \times \text{factor}, 2)$$
* Todo precio guardado pasa obligatoriamente por [`PriceService::setPrice()`](../app/Services/PriceService.php).

---

### 3.5. Regla de Existencias (Stock)
* El valor de `available_quantity` de la publicación se asigna a la refacción pasando obligatoriamente por [`StockService::setStock()`](../app/Services/StockService.php).
* Si una publicación en Mercado Libre tiene `available_quantity = 0`, el sistema automáticamente ajusta `stock = 0` y coloca `status = 'draft'`, retirándola del catálogo visible público para evitar clics vacíos o compras no surtibles.

---

## 4. Cambios Requeridos en la Base de Datos para el Sub-paso B4

Para reflejar este mapeo en el Sub-paso B4, la tabla `products` se actualizará con una migración no destructiva agregando:
- `mercadolibre_id` (`string(32)`, unique, nullable).
- `mercadolibre_permalink` (`string(500)`, nullable).
- `part_number` (`string(64)`, index, nullable).
- `oem_number` (`string(64)`, nullable).
- `side` (`string(32)`, nullable).
- `position` (`string(32)`, nullable).
- `raw_attributes` (`json`, nullable).
- `last_synced_at` (`timestamp`, nullable).

---

## 5. Conclusión y Visto Bueno

Este diseño desacopla completamente el catálogo de la tienda de las dependencias externas, almacena los datos de forma normalizada y optimizada para búsquedas en MySQL, y prepara el terreno para la sincronización continua y la cotización de envíos.
