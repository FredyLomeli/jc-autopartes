<?php

namespace App\Services\Shipping;

use App\Contracts\ShippingQuoteProvider;

/**
 * Proveedor simulado de cotización para Paquetexpress.
 * Calcula un estimado realista por zona postal (origen Tepatitlán C.P. 47600),
 * peso físico/volumétrico y modalidad (Envío a Domicilio vs Servicio Ocurre).
 */
class SimulatedPaquetexpressQuoteProvider implements ShippingQuoteProvider
{
    private const DEFAULT_WEIGHT_KG = 4.0;

    private const DEFAULT_LENGTH_CM = 35.0;

    private const DEFAULT_WIDTH_CM = 30.0;

    private const DEFAULT_HEIGHT_CM = 25.0;

    /**
     * Cotiza el envío a un código postal destino según peso y dimensiones.
     *
     * @param  array<int, array<string, float|int>>  $packages
     * @return array<string, mixed>
     */
    public function quote(string $destinationPostalCode, array $packages, string $deliveryVariant = 'domicilio'): array
    {
        $variant = in_array($deliveryVariant, ['domicilio', 'ocurre', 'pickup'], true)
            ? $deliveryVariant
            : 'domicilio';

        if ($variant === 'pickup') {
            return [
                'valid' => true,
                'postal_code' => '47600',
                'delivery_variant' => 'pickup',
                'carrier' => 'Matriz JC Autopartes (Mostrador)',
                'zone_label' => 'Tepatitlán de Morelos, Jalisco',
                'billable_weight_kg' => 0.0,
                'cost' => 0.0,
                'formatted_cost' => '$0.00 MXN',
                'estimated_days' => 'Entrega en mostrador el mismo día hábil',
                'is_simulated' => true,
                'notice' => 'Recolección directa en tienda sin costo de paquetería.',
            ];
        }

        $cleanCp = preg_replace('/\D+/', '', trim($destinationPostalCode));
        if (! preg_match('/^[0-9]{5}$/', $cleanCp)) {
            return [
                'valid' => false,
                'message' => 'Ingresa un Código Postal válido de 5 dígitos numéricos.',
            ];
        }

        $billableWeight = $this->calculateBillableWeight($packages);
        [$zoneLabel, $baseRate, $perExtraKg, $estimatedDays] = $this->resolveZone($cleanCp);

        $extraKg = max(0.0, $billableWeight - 5.0);
        $domicilioCost = round($baseRate + ($extraKg * $perExtraKg), 2);

        $cost = $variant === 'ocurre'
            ? max(129.0, round($domicilioCost * 0.82, 2))
            : $domicilioCost;

        return [
            'valid' => true,
            'postal_code' => $cleanCp,
            'delivery_variant' => $variant,
            'carrier' => 'Paquetexpress',
            'zone_label' => $zoneLabel,
            'billable_weight_kg' => round($billableWeight, 2),
            'cost' => $cost,
            'formatted_cost' => '$' . number_format($cost, 2, '.', ',') . ' MXN',
            'estimated_days' => $estimatedDays,
            'is_simulated' => true,
            'notice' => 'Tarifa de ejemplo estimada por zona y peso. El monto definitivo se confirmará al conectar la API de Paquetexpress.',
        ];
    }

    /**
     * @param  array<int, array<string, float|int>>  $packages
     */
    private function calculateBillableWeight(array $packages): float
    {
        if ($packages === []) {
            return self::DEFAULT_WEIGHT_KG;
        }

        $totalPhysical = 0.0;
        $totalVolumetric = 0.0;

        foreach ($packages as $pkg) {
            $qty = max(1, (int) ($pkg['quantity'] ?? 1));
            $weight = (float) ($pkg['weight_kg'] ?? 0);
            $length = (float) ($pkg['length_cm'] ?? 0);
            $width = (float) ($pkg['width_cm'] ?? 0);
            $height = (float) ($pkg['height_cm'] ?? 0);

            $unitPhysical = $weight > 0 ? $weight : self::DEFAULT_WEIGHT_KG;
            $l = $length > 0 ? $length : self::DEFAULT_LENGTH_CM;
            $w = $width > 0 ? $width : self::DEFAULT_WIDTH_CM;
            $h = $height > 0 ? $height : self::DEFAULT_HEIGHT_CM;

            $unitVolumetric = ($l * $w * $h) / 5000.0;

            $totalPhysical += $unitPhysical * $qty;
            $totalVolumetric += $unitVolumetric * $qty;
        }

        return max(1.0, $totalPhysical, $totalVolumetric);
    }

    /**
     * @return array{0: string, 1: float, 2: float, 3: string}
     */
    private function resolveZone(string $postalCode): array
    {
        $prefix = (int) substr($postalCode, 0, 2);

        if ($prefix === 47) {
            return ['Altos de Jalisco (Zona Local)', 149.0, 12.0, '1 a 2 días hábiles'];
        }

        if ($prefix >= 44 && $prefix <= 49) {
            return ['Occidente (Jalisco / ZMG / Colima / Nayarit)', 189.0, 16.0, '2 a 3 días hábiles'];
        }

        if ($prefix <= 39 || ($prefix >= 50 && $prefix <= 79)) {
            return ['Centro y Bajío (CDMX, EdoMéx, Gto, Qro, Pue)', 249.0, 20.0, '2 a 4 días hábiles'];
        }

        return ['Norte / Sur / Zona Nacional Extendida', 319.0, 24.0, '3 a 6 días hábiles'];
    }
}
