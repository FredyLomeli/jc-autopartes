<?php

namespace App\Http\Requests\Public;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'min:3', 'max:150'],
            'customer_phone' => ['required', 'string', 'min:10', 'max:20'],
            'customer_email' => ['required', 'email', 'max:150'],
            'delivery_method' => ['required', 'string', 'in:' . Order::DELIVERY_SHIPPING . ',' . Order::DELIVERY_PICKUP],
            'shipping_postal_code' => ['required_if:delivery_method,' . Order::DELIVERY_SHIPPING, 'nullable', 'regex:/^[0-9]{5}$/'],
            'shipping_address' => ['required_if:delivery_method,' . Order::DELIVERY_SHIPPING, 'nullable', 'string', 'max:255'],
            'shipping_neighborhood' => ['required_if:delivery_method,' . Order::DELIVERY_SHIPPING, 'nullable', 'string', 'max:150'],
            'shipping_city' => ['required_if:delivery_method,' . Order::DELIVERY_SHIPPING, 'nullable', 'string', 'max:100'],
            'shipping_state' => ['required_if:delivery_method,' . Order::DELIVERY_SHIPPING, 'nullable', 'string', 'max:100'],
            'shipping_notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_name.required' => 'Ingresa tu nombre completo para el pedido.',
            'customer_name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'customer_phone.required' => 'Ingresa un teléfono o WhatsApp de contacto a 10 dígitos.',
            'customer_phone.min' => 'El teléfono de contacto debe tener al menos 10 dígitos.',
            'customer_email.required' => 'Ingresa un correo electrónico válido para recibir tu comprobante.',
            'customer_email.email' => 'El correo electrónico no tiene un formato válido.',
            'delivery_method.required' => 'Selecciona si deseas envío a domicilio o recolección en mostrador.',
            'shipping_postal_code.required_if' => 'El Código Postal es obligatorio para envíos a domicilio.',
            'shipping_postal_code.regex' => 'El Código Postal debe constar exactamente de 5 dígitos numéricos.',
            'shipping_address.required_if' => 'La calle y número exterior/interior son obligatorios.',
            'shipping_neighborhood.required_if' => 'La colonia es obligatoria para la entrega.',
            'shipping_city.required_if' => 'La ciudad o municipio es obligatorio.',
            'shipping_state.required_if' => 'El estado de la República es obligatorio.',
        ];
    }
}
