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
    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->getAll();
        return $all[$key] ?? $default;
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
}
