<?php

namespace App\Services\MercadoLibre;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Throwable;

class MercadoLibreCategoryResolver
{
    /**
     * @var array<string, array{id: string, name: string, path_from_root: array<int, string>}>|null
     */
    private ?array $tree = null;

    /**
     * Resuelve la categoría general de la tienda (10 oficiales) y la subcategoría real de ML en español.
     *
     * @return array{category: string, subcategory: ?string}
     */
    public function resolve(?string $categoryId, ?string $domainId, string $title): array
    {
        $node = $this->findCategoryNode($categoryId);
        $leafName = $node['name'] ?? null;
        $path = $node['path_from_root'] ?? [];

        $category = $this->resolveGeneralCategory($categoryId, $domainId, $leafName, $path, $title);
        $subcategory = $leafName !== null && trim($leafName) !== '' ? trim($leafName) : $this->inferSubcategoryFromDomain($domainId);

        return [
            'category' => $category,
            'subcategory' => $subcategory,
        ];
    }

    private function resolveGeneralCategory(
        ?string $categoryId,
        ?string $domainId,
        ?string $leafName,
        array $path,
        string $title
    ): string {
        $branch = mb_strtolower((string) ($path[2] ?? ($path[1] ?? '')));
        $fullPath = mb_strtolower(implode(' > ', $path));
        $leaf = mb_strtolower((string) $leafName);
        $domain = strtoupper((string) $domainId);
        $lowerTitle = mb_strtolower($title);

        // 1. Iluminación (faros, calaveras, cuartos, niebla, luces interiores)
        if (
            str_contains($fullPath, 'iluminación')
            || $leaf === 'luces interiores'
            || str_contains($domain, 'HEADLIGHT')
            || str_contains($domain, 'TAIL_LIGHT')
            || str_contains($domain, 'AUXILIARY_LIGHT')
            || str_contains($domain, 'TURN_SIGNAL')
            || str_contains($domain, 'INTERIOR_LIGHT')
            || $categoryId === 'MLM158318'
            || $categoryId === 'MLM4862'
        ) {
            // Rejillas/biseles de faros de niebla pertenecen a Carrocería si ML los ubica en Carrocería
            if (! str_contains($domain, 'GRILL') && ! str_contains($branch, 'carrocería')) {
                return 'Iluminación';
            }
        }
        if ($categoryId === 'MLM187516' && (str_contains($lowerTitle, 'faro') || str_contains($lowerTitle, 'calavera') || str_contains($lowerTitle, 'cuarto'))) {
            return 'Iluminación';
        }

        // 2. Frenos (calipers, sensores ABS, discos, bombas, boosters)
        if (
            str_contains($fullPath, 'frenos')
            || str_contains($domain, 'BRAKE')
            || str_contains($domain, 'ABS_SENSOR')
            || $leaf === 'de freno de mano'
            || str_contains($leaf, 'caliper')
            || str_contains($leaf, 'freno')
        ) {
            return 'Frenos';
        }

        // 3. Suspensión y Dirección
        if (
            str_contains($fullPath, 'suspensión y dirección')
            || $categoryId === 'MLM187519'
            || str_contains($domain, 'SHOCK_ABSORBER')
            || str_contains($domain, 'CONTROL_ARM')
            || str_contains($domain, 'SWAY_BAR')
            || str_contains($domain, 'SPRING_SUSPENSION')
            || str_contains($domain, 'STEERING')
            || str_contains($domain, 'AXLE_TIP_MOUNT')
        ) {
            return 'Suspensión y Dirección';
        }

        // 4. Transmisiones (cajas, clutch, palancas selectoras, flechas/palieres, diferenciales)
        if (
            str_contains($branch, 'transmisión')
            || $categoryId === 'MLM187515'
            || $leaf === 'palancas de caja de cambios'
            || $leaf === 'módulos de control de caja'
            || str_contains($domain, 'CV_AXLE')
            || str_contains($domain, 'TRANSMISSION')
            || str_contains($domain, 'GEAR_SHIFT')
        ) {
            return 'Transmisiones';
        }

        // 5. Enfriamiento y Clima (A/C) — incluye climatización, motoventiladores y radiadores/mangueras/bombas de agua
        if (
            str_contains($branch, 'climatización')
            || str_contains($branch, 'motoventiladores')
            || $categoryId === 'MLM187518'
            || in_array($leaf, [
                'manguera de radiador',
                'depósito anticongelante',
                'tomas de agua',
                'bombas de agua',
                'radiadores de agua',
                'radiadores de aceite',
                'enfriadores de aceite',
                'motoventiladores',
                'termostatos',
            ], true)
            || str_contains($domain, 'COOLANT')
            || str_contains($domain, 'RADIATOR_HOSE')
            || str_contains($domain, 'ENGINE_FAN')
            || str_contains($domain, 'HEATER_CORE')
            || str_contains($domain, 'AC_HOSE')
            || str_contains($domain, 'AC_COMPRESSOR')
        ) {
            return 'Enfriamiento y Clima (A/C)';
        }

        // 6. Motores (motores completos y 3/4)
        if (
            $categoryId === 'MLM187514'
            || $leaf === 'motores completos'
            || $leaf === 'motores'
            || preg_match('/\b(motor\s+3\/4|motor\s+completo|medio\s+motor)\b/u', $lowerTitle) === 1
        ) {
            return 'Motores';
        }

        // 7. Eléctrico y Sensores (encendido, instalaciones eléctricas, sensores, switches, computadoras, alternadores, marchas)
        if (
            str_contains($branch, 'encendido')
            || str_contains($branch, 'instalaciones eléctricas')
            || str_contains($branch, 'sensores')
            || $categoryId === 'MLM187517'
            || str_starts_with($leaf, 'sensor')
            || str_starts_with($leaf, 'sensores')
            || in_array($leaf, ['computadoras', 'para vidrios', 'de luces', 'interruptores', 'motores limpiaparabrisas'], true)
            || str_contains($domain, 'IGNITION')
            || str_contains($domain, 'ALTERNATOR')
            || str_contains($domain, 'STARTER')
            || str_contains($domain, 'FUSE_BOX')
            || str_contains($domain, 'SWITCH')
            || str_contains($domain, 'WIPER_MOTOR')
            || str_contains($domain, 'SENSOR')
        ) {
            return 'Eléctrico y Sensores';
        }

        // 8. Partes de Motor e Inyección (cabezas, cigüeñales, inyección, filtros, soportes, bombas combustible)
        if (
            str_contains($branch, 'motor')
            || str_contains($branch, 'inyección')
            || str_contains($branch, 'filtros')
            || str_contains($domain, 'THROTTLE_BOD')
            || str_contains($domain, 'FUEL_INJECT')
            || str_contains($domain, 'FUEL_PUMP')
            || str_contains($domain, 'ENGINE_MOUNT')
        ) {
            return 'Partes de Motor e Inyección';
        }

        // 9. Interior y Seguridad (habitáculo, bolsas de aire, cinturones, estéreos, bocinas, alarmas, asientos, viseras)
        if (
            str_contains($branch, 'habitáculo')
            || str_contains($branch, 'seguridad')
            || str_contains($fullPath, 'audio para vehículos')
            || str_contains($branch, 'interior')
            || str_contains($branch, 'conducción asistida')
            || str_contains($domain, 'SEAT_BELT')
            || str_contains($domain, 'AIRBAG')
            || str_contains($domain, 'STEREO')
            || str_contains($domain, 'SUN_VISOR')
            || str_contains($domain, 'PEDAL')
        ) {
            return 'Interior y Seguridad';
        }

        // 10. Carrocería y Colisión (carrocería, exterior, cerraduras, ventanas, rines, molduras, marcos de radiador)
        if (
            str_contains($branch, 'carrocería')
            || str_contains($branch, 'exterior')
            || str_contains($branch, 'cerraduras')
            || str_contains($branch, 'ventanas')
            || str_contains($fullPath, 'rines')
            || str_contains($branch, 'bastidores')
        ) {
            return 'Carrocería y Colisión';
        }

        // Fallback por palabras clave en el título cuando no exista árbol ni domain_id conocido
        return $this->fallbackByTitle($lowerTitle);
    }

    private function fallbackByTitle(string $lowerTitle): string
    {
        if (preg_match('/\b(faro|calavera|niebla|cuarto|direccional)\b/u', $lowerTitle) === 1) {
            return 'Iluminación';
        }
        if (preg_match('/\b(motor\s+3\/4|motor\s+completo)\b/u', $lowerTitle) === 1) {
            return 'Motores';
        }
        if (preg_match('/\b(transmisi[oó]n|caja\s+de\s+velocidades|palanca\s+de\s+velocidades)\b/u', $lowerTitle) === 1) {
            return 'Transmisiones';
        }
        if (preg_match('/\b(cabeza|cig[uü]e[nñ]al|[aá]rbol\s+de\s+levas|inyector|cuerpo\s+de\s+aceleraci[oó]n|soporte\s+de\s+motor)\b/u', $lowerTitle) === 1) {
            return 'Partes de Motor e Inyección';
        }
        if (preg_match('/\b(caliper|abs|disco\s+de\s+freno|booster|bomba\s+de\s+freno)\b/u', $lowerTitle) === 1) {
            return 'Frenos';
        }
        if (preg_match('/\b(amortiguador|horquilla|cremallera|suspensi[oó]n|direcci[oó]n)\b/u', $lowerTitle) === 1) {
            return 'Suspensión y Dirección';
        }
        if (preg_match('/\b(compresor|condensador|radiador|motoventilador|anticongelante|clima|calefacci[oó]n)\b/u', $lowerTitle) === 1) {
            return 'Enfriamiento y Clima (A/C)';
        }
        if (preg_match('/\b(alternador|marcha|bobina|sensor|computadora|fusible|switch)\b/u', $lowerTitle) === 1) {
            return 'Eléctrico y Sensores';
        }
        if (preg_match('/\b(cintur[oó]n|bolsa\s+de\s+aire|est[eé]reo|visera|asiento|pedal|tablero|manija\s+interior)\b/u', $lowerTitle) === 1) {
            return 'Interior y Seguridad';
        }

        return 'Carrocería y Colisión';
    }

    /**
     * @return array{id: string, name: string, path_from_root: array<int, string>}|null
     */
    private function findCategoryNode(?string $categoryId): ?array
    {
        if (empty($categoryId)) {
            return null;
        }

        $tree = $this->loadTree();
        if (isset($tree[$categoryId])) {
            return $tree[$categoryId];
        }

        // En producción/sincronización en vivo, consultar una vez /categories/{id} si llega una categoría nueva
        if (! app()->runningUnitTests()) {
            try {
                $resp = Http::timeout(4)->get("https://api.mercadolibre.com/categories/{$categoryId}");
                if ($resp->successful()) {
                    $json = $resp->json();
                    $node = [
                        'id' => $categoryId,
                        'name' => (string) ($json['name'] ?? $categoryId),
                        'path_from_root' => collect($json['path_from_root'] ?? [])->pluck('name')->values()->all(),
                    ];
                    $this->tree[$categoryId] = $node;
                    $this->persistTree($this->tree);

                    return $node;
                }
            } catch (Throwable) {
                // Fallback silencioso a reglas de domain_id / título
            }
        }

        return null;
    }

    /**
     * @return array<string, array{id: string, name: string, path_from_root: array<int, string>}>
     */
    private function loadTree(): array
    {
        if ($this->tree !== null) {
            return $this->tree;
        }

        $path = storage_path('app/meli_categories_tree.json');
        if (File::exists($path)) {
            $decoded = json_decode(File::get($path), true);
            if (is_array($decoded)) {
                return $this->tree = $decoded;
            }
        }

        return $this->tree = [];
    }

    /**
     * @param  array<string, array{id: string, name: string, path_from_root: array<int, string>}>  $tree
     */
    private function persistTree(array $tree): void
    {
        try {
            $path = storage_path('app/meli_categories_tree.json');
            File::put($path, json_encode($tree, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (Throwable) {
            // No bloquear sincronización si el disco es de solo lectura
        }
    }

    private function inferSubcategoryFromDomain(?string $domainId): ?string
    {
        if (empty($domainId)) {
            return null;
        }

        return match (strtoupper($domainId)) {
            'MLM-VEHICLE_HEADLIGHTS' => 'Faros Delanteros',
            'MLM-VEHICLE_TAIL_LIGHTS' => 'Calaveras',
            'MLM-VEHICLE_FENDERS' => 'Salpicaderas',
            'MLM-VEHICLE_BUMPER_GRILLES' => 'Parrillas',
            'MLM-VEHICLE_REAR_BUMPERS' => 'Defensas Traseras',
            'MLM-VEHICLE_FRONT_BUMPERS' => 'Defensas Delanteras',
            'MLM-VEHICLE_SEAT_BELTS' => 'Cinturones de Seguridad',
            'MLM-VEHICLE_BRAKE_CALIPERS' => 'Calipers',
            'MLM-VEHICLE_IGNITION_COILS' => 'Bobinas de Encendido',
            'MLM-VEHICLE_SHOCK_ABSORBERS' => 'Amortiguadores',
            'MLM-VEHICLE_STEREOS' => 'Estéreos',
            default => null,
        };
    }
}
