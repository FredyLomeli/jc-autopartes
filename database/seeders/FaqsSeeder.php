<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqsSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question' => '¿Cómo sé si una refacción es compatible con mi vehículo?',
                'answer' => 'Puedes consultar el número de parte original (OEM) o la descripción en la ficha del producto. Si tienes dudas, contáctanos directamente a nuestras líneas oficiales de WhatsApp con el número de serie (VIN), año, marca y modelo de tu vehículo para verificar la compatibilidad exacta antes de tu compra.',
                'category' => 'general',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'question' => '¿Cómo funciona la Garantía Escrita de JC Autopartes?',
                'answer' => 'Todas nuestras refacciones cuentan con garantía escrita respaldada contra defectos de fabricación o funcionamiento. Cada pieza es inspeccionada y probada antes de su embalaje. En caso de requerir aplicar garantía, nuestro equipo técnico te guiará para la validación y solución rápida.',
                'category' => 'garantias',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'question' => '¿Cuánto tiempo tarda en llegar mi pedido?',
                'answer' => 'Realizamos envíos seguros a toda la República Mexicana. Los paquetes se despachan habitualmente en 24 a 48 horas hábiles tras la confirmación del pago. El tiempo de entrega promedio por paquetería certificada (FedEx, DHL, Estafeta) es de 2 a 5 días hábiles dependiendo de tu código postal.',
                'category' => 'envios',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'question' => '¿Puedo recoger mi compra directamente en sucursal?',
                'answer' => '¡Sí! Puedes visitarnos directamente en nuestra Matriz en Tepatitlán de Morelos, Jalisco (Bertha Padilla Gutierrez #2020, Col. Los Adobes) en nuestro horario habitual: Lunes a Viernes de 9:00 am a 6:00 pm y Sábados de 9:00 am a 2:00 pm.',
                'category' => 'envios',
                'order' => 4,
                'is_active' => true,
            ],
            [
                'question' => '¿Cuáles son los métodos de pago aceptados?',
                'answer' => 'Aceptamos pagos seguros en línea mediante Mercado Pago (tarjetas de crédito y débito Visa, Mastercard, American Express, transferencias SPEI y pagos en efectivo en tiendas de conveniencia). En mostrador aceptamos efectivo y transferencias bancarias directas.',
                'category' => 'pagos',
                'order' => 5,
                'is_active' => true,
            ],
            [
                'question' => '¿Facturan las compras realizadas?',
                'answer' => 'Sí, todos nuestros precios ya incluyen IVA. Al realizar tu pedido o una vez confirmado el pago, puedes proporcionarnos tu Constancia de Situación Fiscal y uso de CFDI a nuestro correo oficial o WhatsApp para emitir tu factura electrónica.',
                'category' => 'pagos',
                'order' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($faqs as $item) {
            Faq::updateOrCreate(
                ['question' => $item['question']],
                $item
            );
        }
    }
}
