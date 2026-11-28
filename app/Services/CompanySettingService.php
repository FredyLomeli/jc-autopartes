<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class CompanySettingService
{
    private const CACHE_KEY = 'jc_site_settings_all';
    private const CACHE_TTL = 3600; // 1 hora en segundos

    /**
     * Obtiene todos los ajustes organizados en un array asociativo llave => valor.
     */
    public function getAll(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return SiteSetting::query()
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    /**
     * Obtiene todos los registros con su metadata (grupo, tipo, label) para el panel admin.
     */
    public function getAllStructured(): array
    {
        return SiteSetting::query()
            ->orderBy('id')
            ->get()
            ->groupBy('group')
            ->toArray();
    }

    /**
     * Obtiene un valor individual con valor por defecto.
     */
    /**
     * Obtiene un valor individual con valor por defecto.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->getAll();

        return $all[$key] ?? $default;
    }

    /**
     * Obtiene el número de WhatsApp principal visible en tienda (editable en /admin/ajustes).
     */
    public function getPrimaryWhatsappDisplay(): string
    {
        $configured = trim((string) $this->get('contact_whatsapp_1', ''));

        if ($configured !== '') {
            return $configured;
        }

        return (string) config('business.whatsapp_display', '378 145 5737');
    }

    /**
     * Obtiene el número de WhatsApp principal normalizado para URL wa.me (con lada 52).
     */
    public function getPrimaryWhatsappNumber(): string
    {
        $digits = preg_replace('/\D+/', '', $this->getPrimaryWhatsappDisplay()) ?? '';

        if ($digits === '') {
            $digits = preg_replace('/\D+/', '', (string) config('business.whatsapp_number', '523781455737')) ?? '523781455737';
        }

        return strlen($digits) === 10 ? '52' . $digits : $digits;
    }

    /**
     * Construye un enlace directo wa.me usando la línea de WhatsApp editable en Ajustes.
     */
    public function buildPrimaryWhatsappUrl(?string $message = null): string
    {
        $url = 'https://wa.me/' . $this->getPrimaryWhatsappNumber();

        if ($message !== null && $message !== '') {
            $url .= '?text=' . urlencode($message);
        }

        return $url;
    }

    /**
     * Actualiza o crea múltiples configuraciones y limpia la caché.
     */
    public function updateMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            SiteSetting::where('key', $key)->update([
                'value' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) ($value ?? ''),
            ]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Crea o actualiza un ajuste individual y limpia la caché.
     *
     * @param  array<string, string>  $meta
     */
    public function set(string $key, ?string $value, array $meta = []): void
    {
        SiteSetting::updateOrCreate(
            ['key' => $key],
            array_merge([
                'value' => (string) ($value ?? ''),
            ], $meta)
        );

        Cache::forget(self::CACHE_KEY);
    }
}
