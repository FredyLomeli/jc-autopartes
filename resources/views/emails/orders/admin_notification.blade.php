<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Venta Web — Almacén JC Autopartes</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1f2937; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f3f4f6; padding: 24px 0; }
        .main-card { background-color: #ffffff; margin: 0 auto; max-width: 600px; border-radius: 8px; border: 1px solid #e5e7eb; overflow: hidden; }
        .header { background-color: #D9381E; padding: 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 18px; font-weight: 800; color: #ffffff; text-transform: uppercase; letter-spacing: 0.5px; }
        .content { padding: 24px; font-size: 13px; line-height: 1.5; }
        .alert-box { background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 14px; margin-bottom: 20px; color: #991b1b; }
        .section-title { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; border-bottom: 2px solid #f3f4f6; padding-bottom: 6px; margin: 20px 0 10px 0; }
        .table-items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .table-items th { background-color: #f9fafb; border-bottom: 2px solid #e5e7eb; padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase; color: #4b5563; }
        .table-items td { padding: 10px; border-bottom: 1px solid #f3f4f6; font-size: 13px; }
        .btn-panel { display: inline-block; background-color: #111827; color: #ffffff; text-decoration: none; font-size: 13px; font-weight: 700; padding: 10px 20px; border-radius: 6px; text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <table role="presentation" class="main-card" align="center" cellpadding="0" cellspacing="0">
            <tr>
                <td class="header">
                    <h1>🚨 Nueva Venta Acreditada en Tienda Web</h1>
                </td>
            </tr>
            <tr>
                <td class="content">
                    <div class="alert-box">
                        <strong style="display: block; font-size: 14px; margin-bottom: 4px;">Acción requerida en almacén:</strong>
                        Preparar y apartar refacción para {{ $order->isShipping() ? 'empaque y envío por paquetería' : 'entrega en mostrador (Tepatitlán)' }}.
                    </div>

                    <table style="width: 100%; font-size: 13px;" cellpadding="0" cellspacing="0">
                        <tr>
                            <td><strong>Folio de Pedido:</strong></td>
                            <td style="text-align: right; font-family: monospace; font-size: 16px; font-weight: 800; color: #D9381E;">{{ $order->order_number }}</td>
                        </tr>
                        <tr>
                            <td><strong>Total Cobrado:</strong></td>
                            <td style="text-align: right; font-weight: 800; font-size: 15px; color: #111827;">${{ number_format($order->total, 2) }} MXN</td>
                        </tr>
                        @if($order->mercadopago_payment_id)
                            <tr>
                                <td><strong>ID Mercado Pago:</strong></td>
                                <td style="text-align: right; font-family: monospace; color: #6b7280;">{{ $order->mercadopago_payment_id }}</td>
                            </tr>
                        @endif
                    </table>

                    <div class="section-title">Datos del Comprador y Entrega</div>
                    <table style="width: 100%; font-size: 13px; line-height: 1.6;" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="width: 120px; color: #6b7280;"><strong>Cliente:</strong></td>
                            <td><strong>{{ $order->customer_name }}</strong></td>
                        </tr>
                        <tr>
                            <td style="color: #6b7280;"><strong>Teléfono / WA:</strong></td>
                            <td><a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->customer_phone) }}" style="color: #059669; font-weight: 700; text-decoration: none;">{{ $order->customer_phone }}</a></td>
                        </tr>
                        <tr>
                            <td style="color: #6b7280;"><strong>Correo:</strong></td>
                            <td>{{ $order->customer_email }}</td>
                        </tr>
                        <tr>
                            <td style="color: #6b7280;"><strong>Modalidad:</strong></td>
                            <td>
                                <strong style="color: {{ $order->isShipping() ? '#1d4ed8' : '#b45309' }};">
                                    {{ $order->deliveryMethodLabel() }}
                                </strong>
                            </td>
                        </tr>
                        @if($order->isShipping())
                            <tr>
                                <td style="color: #6b7280; vertical-align: top;"><strong>Dirección Destino:</strong></td>
                                <td>
                                    {{ $order->shipping_address }}, Col. {{ $order->shipping_neighborhood }}<br>
                                    {{ $order->shipping_city }}, {{ $order->shipping_state }}. C.P. {{ $order->shipping_postal_code }}
                                    @if($order->shipping_notes)
                                        <br><span style="color: #6b7280; font-style: italic;">Notas: {{ $order->shipping_notes }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    </table>

                    <div class="section-title">Refacciones a Retirar del Almacén</div>
                    <table class="table-items" cellpadding="0" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Pieza</th>
                                <th style="text-align: center;">Cant.</th>
                                <th style="text-align: right;">Precio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        <strong style="color: #111827; display: block;">{{ $item->product_title }}</strong>
                                        <span style="font-size: 11px; font-family: monospace; color: #D9381E; font-weight: 700;">
                                            SKU: {{ $item->product_sku }}
                                        </span>
                                        @if($item->part_number)
                                            <span style="font-size: 11px; color: #6b7280;"> · No. Parte: {{ $item->part_number }}</span>
                                        @endif
                                    </td>
                                    <td style="text-align: center; font-weight: 800; font-size: 14px; color: #111827;">
                                        {{ $item->quantity }}
                                    </td>
                                    <td style="text-align: right; font-weight: 700; color: #111827;">
                                        ${{ number_format($item->subtotal, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div style="text-align: center; margin-top: 24px;">
                        <a href="{{ url('/admin/pedidos') }}" class="btn-panel" target="_blank">
                            Abrir Panel de Pedidos en Sistema Web &rarr;
                        </a>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
