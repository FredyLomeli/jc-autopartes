<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General & Identidad
            [
                'key' => 'company_name',
                'value' => 'JC Autopartes',
                'group' => 'general',
                'type' => 'string',
                'label' => 'Nombre Comercial',
            ],
            [
                'key' => 'company_badge',
                'value' => 'Todo pa\' tu máquina!',
                'group' => 'general',
                'type' => 'string',
                'label' => 'Subtítulo / Distintivo',
            ],
            [
                'key' => 'company_slogan',
                'value' => 'Soluciones exactas que le devuelven la vida a tu auto.',
                'group' => 'general',
                'type' => 'string',
                'label' => 'Slogan Institucional',
            ],

            // Contacto y Ubicación
            [
                'key' => 'contact_branch',
                'value' => 'Matriz Tepatitlán',
                'group' => 'contact',
                'type' => 'string',
                'label' => 'Sucursal Principal',
            ],
            [
                'key' => 'contact_address',
                'value' => 'Bertha Padilla Gutierrez #2020',
                'group' => 'contact',
                'type' => 'string',
                'label' => 'Calle y Número',
            ],
            [
                'key' => 'contact_colony',
                'value' => 'Los Adobes',
                'group' => 'contact',
                'type' => 'string',
                'label' => 'Colonia',
            ],
            [
                'key' => 'contact_zip',
                'value' => '47656',
                'group' => 'contact',
                'type' => 'string',
                'label' => 'Código Postal',
            ],
            [
                'key' => 'contact_city',
                'value' => 'Tepatitlán de Morelos, Jalisco',
                'group' => 'contact',
                'type' => 'string',
                'label' => 'Ciudad y Estado',
            ],
            [
                'key' => 'contact_maps_url',
                'value' => 'https://maps.app.goo.gl/HpyvbhFdg5vkYB167',
                'group' => 'contact',
                'type' => 'string',
                'label' => 'Enlace Google Maps',
            ],
            [
                'key' => 'contact_maps_embed',
                'value' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3729.8078864705036!2d-102.7661642!3d20.8037302!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x84294a50d4eb14b5%3A0x6b245e3fbb5bba43!2sJC%20Autopartes!5e0!3m2!1ses-419!2smx!4v1700000000000!5m2!1ses-419!2smx',
                'group' => 'contact',
                'type' => 'text',
                'label' => 'URL Embed Google Maps',
            ],
            [
                'key' => 'contact_phone_office',
                'value' => '378 688 2205',
                'group' => 'contact',
                'type' => 'string',
                'label' => 'Teléfono de Oficina',
            ],
            [
                'key' => 'contact_whatsapp_1',
                'value' => '378 145 5737',
                'group' => 'contact',
                'type' => 'string',
                'label' => 'WhatsApp Ventas Línea 1',
            ],
            [
                'key' => 'contact_whatsapp_1_url',
                'value' => 'https://wa.me/message/MKVOP7GHAWCIK1',
                'group' => 'contact',
                'type' => 'string',
                'label' => 'Enlace WhatsApp Línea 1',
            ],
            [
                'key' => 'contact_whatsapp_2',
                'value' => '378 272 4234',
                'group' => 'contact',
                'type' => 'string',
                'label' => 'WhatsApp Ventas Línea 2',
            ],
            [
                'key' => 'contact_whatsapp_2_url',
                'value' => 'https://wa.me/message/5REIIC2COHFVH1',
                'group' => 'contact',
                'type' => 'string',
                'label' => 'Enlace WhatsApp Línea 2',
            ],
            [
                'key' => 'contact_email',
                'value' => 'jcautopartes.mx@gmail.com',
                'group' => 'contact',
                'type' => 'string',
                'label' => 'Correo Electrónico',
            ],
            [
                'key' => 'schedule_weekday',
                'value' => 'Lunes a Viernes: 9:00 am - 6:00 pm',
                'group' => 'contact',
                'type' => 'string',
                'label' => 'Horario entre semana',
            ],
            [
                'key' => 'schedule_saturday',
                'value' => 'Sábados: 9:00 am - 2:00 pm',
                'group' => 'contact',
                'type' => 'string',
                'label' => 'Horario sábados',
            ],

            // Enlaces Externos / Redes
            [
                'key' => 'social_mercadolibre',
                'value' => 'https://listado.mercadolibre.com.mx/_CustId_546980216',
                'group' => 'social',
                'type' => 'string',
                'label' => 'Tienda Oficial Mercado Libre',
            ],
            [
                'key' => 'social_facebook',
                'value' => 'https://facebook.com/jcautopartestepa',
                'group' => 'social',
                'type' => 'string',
                'label' => 'Página de Facebook',
            ],
            [
                'key' => 'social_instagram',
                'value' => 'https://instagram.com/jcautopartesmx',
                'group' => 'social',
                'type' => 'string',
                'label' => 'Perfil de Instagram',
            ],

            // Filosofía Empresarial 2026 (Juan Carlos Barba)
            [
                'key' => 'company_mission',
                'value' => 'Encontrar la solución exacta para cada vehículo que llega a nuestras manos. Nos apasiona devolverle la movilidad a nuestros clientes brindándoles la refacción precisa, asesoría técnica experta y la tranquilidad de una garantía real que genera absoluta confianza.',
                'group' => 'philosophy',
                'type' => 'text',
                'label' => 'Misión Institucional',
            ],
            [
                'key' => 'company_vision',
                'value' => 'Ser la opción más eficaz y ágil para dar nueva vida a cualquier vehículo en la región, posicionándonos como el proveedor preferido en el mercado local y un referente nacional en e-commerce automotriz, respaldados por tecnología de vanguardia, procesos digitalizados y atención humana de excelencia.',
                'group' => 'philosophy',
                'type' => 'text',
                'label' => 'Visión Institucional',
            ],
            [
                'key' => 'company_values',
                'value' => json_encode([
                    ['num' => '01', 'title' => 'Honestidad Técnica', 'desc' => 'Diagnosticamos y recomendamos la pieza adecuada con total verdad sobre su estado y compatibilidad, anteponiendo la seguridad del cliente sobre la venta.'],
                    ['num' => '02', 'title' => 'Agilidad en la Solución', 'desc' => 'Valoramos el tiempo de nuestros clientes; respondemos y resolvemos con rapidez operativa sin perder precisión en el servicio.'],
                    ['num' => '03', 'title' => 'Transparencia', 'desc' => 'Mantenemos claridad total en nuestros precios, políticas de garantía, estados del producto y condiciones comerciales.'],
                    ['num' => '04', 'title' => 'Acompañamiento Profesional', 'desc' => 'Brindamos un trato empático, amable y experto desde la primera duda hasta que el auto vuelve a rodar.'],
                    ['num' => '05', 'title' => 'Seguridad y Confianza', 'desc' => 'Respaldamos cada venta con procesos formales y garantías claras que protegen la inversión del comprador.'],
                ], JSON_UNESCAPED_UNICODE),
                'group' => 'philosophy',
                'type' => 'json',
                'label' => 'Valores Institucionales',
            ],
            [
                'key' => 'guarantee_banner_text',
                'value' => 'Garantía Escrita JC: Respaldamos cada compra con procesos formales, compatibilidad verificada y garantía clara que protege tu inversión.',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Texto de Cintillo de Garantía',
            ],
        ];

        foreach ($settings as $setting) {
            SiteSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        SiteSetting::firstOrCreate(
            ['key' => 'warranty_policy_pdf_path'],
            [
                'value' => '',
                'group' => 'general',
                'type' => 'file',
                'label' => 'Archivo PDF de Política de Garantía',
            ]
        );

        SiteSetting::firstOrCreate(
            ['key' => 'privacy_policy_pdf_path'],
            [
                'value' => '',
                'group' => 'general',
                'type' => 'file',
                'label' => 'Archivo PDF del Aviso de Privacidad',
            ]
        );
    }
}
