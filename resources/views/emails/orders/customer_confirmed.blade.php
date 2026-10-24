<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Compra — JC Autopartes</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1f2937; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f3f4f6; padding: 24px 0; }
        .main-card { background-color: #ffffff; margin: 0 auto; max-width: 600px; border-radius: 8px; border: 1px solid #e5e7eb; overflow: hidden; }
        .header { background-color: #111827; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 800; color: #ffffff; letter-spacing: 0.5px; text-transform: uppercase; }
        .header p { margin: 4px 0 0 0; font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px; }
        .content { padding: 32px 24px; }
        .badge-success { display: inline-block; background-color: #d1fae5; color: #065f46; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; padding: 4px 12px; border-radius: 9999px; }
        .order-number { font-size: 24px; font-weight: 800; color: #D9381E; margin: 8px 0; font-family: monospace; }
        .info-box { background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px; margin: 20px 0; font-size: 13px; line-height: 1.5; }
        .table-items { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 13px; }
        .table-items th { background-color: #f9fafb; border-bottom: 2px solid #e5e7eb; padding: 10px; text-align: left; font-size: 11px; text-transform: uppercase; color: #6b7280; font-weight: 700; }
        .table-items td { padding: 12px 10px; border-bottom: 1px solid #f3f4f6; }
        .totals-table { width: 100%; max-width: 280px; margin-left: auto; margin-top: 16px; font-size: 13px; }
        .totals-table td { padding: 4px 0; }
        .totals-table .grand-total { font-size: 16px; font-weight: 800; color: #D9381E; border-top: 1px solid #e5e7eb; padding-top: 8px; }
        .btn-whatsapp { display: inline-block; background-color: #25D366; color: #ffffff; text-decoration: none; font-size: 13px; font-weight: 700; padding: 12px 24px; border-radius: 6px; text-align: center; }
        .footer { background-color: #f9fafb; padding: 24px; border-top: 1px solid #e5e7eb; font-size: 11px; color: #6b7280; line-height: 1.5; text-align: center; }
    </style>
</head>
<body>
    <div class="wrapper">
        <table role="presentation" class="main-card" align="center" cellpadding="0" cellspacing="0">
            <!-- Encabezado de Marca -->
            <tr>
                <td class="header">
                    <h1>JC AUTOPARTES</h1>
                    <p>{{ $business['tagline'] ?? 'Refacciones Usadas con Garantía' }}</p>
                </td>
            </tr>

            <!-- Contenido Principal -->
            <tr>
                <td class="content">
                    <div style="text-align: center; margin-bottom: 24px;">
                        <span class="badge-success">✓ Pago Acreditado en Mercado Pago</span>
                        <h2 style="font-size: 20px; font-weight: 800; color: #111827; margin: 12px 0 4px 0;">
                            ¡Gracias por tu compra, {{ $order->customer_name }}!
                        </h2>
                        <p style="font-size: 13px; color: #4b5563; margin: 0;">
                            Hemos recibido tu pago con éxito. Tu pedido ya está en fila de empaque en nuestro almacén.
                        </p>
                        <div class="order-number">{{ $order->order_number }}</div>
                        <span style="font-size: 11px; color: #6b7280;">Fecha de compra: {{ $order->paid_at ? $order->paid_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }} hrs</span>
                    </div>

                    <!-- Datos de Entrega -->
                    <div class="info-box">
                        <strong style="color: #111827; display: block; margin-bottom: 6px;">
                            Modalidad de Entrega: {{ $order->deliveryMethodLabel() }}
                        </strong>
                        @if($order->isShipping())
                            <div><strong>Destinatario:</strong> {{ $order->customer_name }} (Tel: {{ $order->customer_phone }})</div>
                            <div><strong>Dirección:</strong> {{ $order->shipping_address }}, Col. {{ $order->shipping_neighborhood }}</div>
                            <div><strong>Ciudad y Estado:</strong> {{ $order->shipping_city }}, {{ $order->shipping_state }} (C.P. {{ $order->shipping_postal_code }})</div>
                            @if($order->shipping_notes)
                                <div style="color: #6b7280; margin-top: 4px; font-style: italic;">Notas: {{ $order->shipping_notes }}</div>
                            @endif
                        @else
                            <div><strong>Recolección en mostrador:</strong> {{ $business['address'] }}</div>
                            <div><strong>Horarios de atención:</strong> {{ $business['hours']['weekdays'] }} · {{ $business['hours']['saturday'] }}</div>
                            <div style="color: #6b7280; margin-top: 4px;">Presenta tu número de folio <strong>{{ $order->order_number }}</strong> e identificación oficial.</div>
                        @endif
                    </div>

                    <!-- Desglose de Refacciones -->
                    <table class="table-items" cellpadding="0" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Refacción</th>
                                <th style="text-align: center;">Cant.</th>
                                <th style="text-align: right;">Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        <strong style="color: #111827; display: block;">{{ $item->product_title }}</strong>
                                        <span style="font-size: 11px; color: #6b7280;">
                                            SKU: {{ $item->product_sku }}
                                            @if($item->part_number)
                                                · No. Parte: {{ $item->part_number }}
                                            @endif
                                            · Garantía: {{ $item->warranty_days }} días
                                        </span>
                                    </td>
                                    <td style="text-align: center; color: #111827; font-weight: 700;">
                                        {{ $item->quantity }}
                                    </td>
                                    <td style="text-align: right; color: #111827; font-weight: 700; white-space: nowrap;">
                                        ${{ number_format($item->subtotal, 2) }} MXN
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Totales -->
                    <table class="totals-table" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="color: #6b7280;">Subtotal de piezas:</td>
                            <td style="text-align: right; font-weight: 700;">${{ number_format($order->items_subtotal, 2) }} MXN</td>
                        </tr>
                        <tr>
                            <td style="color: #6b7280;">Costo de envío:</td>
                            <td style="text-align: right; font-weight: 700;">
                                {{ $order->shipping_cost > 0 ? '$' . number_format($order->shipping_cost, 2) . ' MXN' : 'Sin costo' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="grand-total">Total Pagado:</td>
                            <td class="grand-total" style="text-align: right;">${{ number_format($order->total, 2) }} MXN</td>
                        </tr>
                    </table>

                    <!-- Soporte WhatsApp -->
                    <div style="text-align: center; margin-top: 32px; padding-top: 24px; border-top: 1px solid #e5e7eb;">
                        <p style="font-size: 13px; color: #4b5563; margin-bottom: 12px;">
                            ¿Tienes dudas sobre el envío o requieres tu número de guía de paquetería?
                        </p>
                        <a
                            href="https://wa.me/{{ $business['whatsapp_number'] }}?text={{ urlencode("Hola JC Autopartes, tengo una duda sobre mi orden {$order->order_number} a nombre de {$order->customer_name}.") }}"
                            class="btn-whatsapp"
                            target="_blank"
                        >
                            Contactar por WhatsApp ({{ $business['whatsapp_display'] }})
                        </a>
                    </div>
                </td>
            </tr>

            <!-- Pie de Correo y Datos Fiscales -->
            <tr>
                <td class="footer">
                    <p style="margin: 0 0 6px 0;">
                        <strong>¿Requieres Factura Fiscal?</strong> Escríbenos por WhatsApp con tu Constancia de Situación Fiscal (CSF) actualizada y el folio de tu orden <strong>{{ $order->order_number }}</strong>.
                    </p>
                    <p style="margin: 0 0 6px 0;">
                        <strong>Póliza de Garantía:</strong> Tus piezas cuentan con garantía escrita a partir del día de entrega. No aplica en partes quemadas, desarmadas o alteradas.
                    </p>
                    <p style="margin: 0; color: #9ca3af;">
                        JC Autopartes · {{ $business['address'] }} · Tepatitlán de Morelos, Jalisco.
                    </p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
