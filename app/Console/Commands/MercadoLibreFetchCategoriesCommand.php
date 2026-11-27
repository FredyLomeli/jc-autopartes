<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class MercadoLibreFetchCategoriesCommand extends Command
{
    protected $signature = 'meli:fetch-categories {--refresh : Reconsultar también las categorías ya almacenadas}';

    protected $description = 'Descarga el árbol oficial en español (path_from_root) de los category_id presentes en marketplace_raw_items';

    public function handle(): int
    {
        $path = storage_path('app/meli_categories_tree.json');
        $tree = File::exists($path) ? (json_decode(File::get($path), true) ?? []) : [];

        $uniqueIds = DB::table('marketplace_raw_items')
            ->selectRaw("DISTINCT JSON_UNQUOTE(JSON_EXTRACT(raw_payload, '$.category_id')) as cid")
            ->pluck('cid')
            ->filter()
            ->values()
            ->all();

        $pending = $this->option('refresh')
            ? $uniqueIds
            : array_values(array_filter($uniqueIds, fn (string $id): bool => ! isset($tree[$id])));

        $this->info('Categorías ML únicas en crudos: ' . count($uniqueIds));
        $this->info('Pendientes por consultar: ' . count($pending));

        foreach (array_chunk($pending, 20) as $chunk) {
            $responses = Http::pool(function ($pool) use ($chunk) {
                foreach ($chunk as $catId) {
                    $pool->as($catId)->timeout(8)->get("https://api.mercadolibre.com/categories/{$catId}");
                }
            });

            foreach ($chunk as $catId) {
                $resp = $responses[$catId] ?? null;
                if ($resp && method_exists($resp, 'successful') && $resp->successful()) {
                    $json = $resp->json();
                    $tree[$catId] = [
                        'id' => $catId,
                        'name' => (string) ($json['name'] ?? $catId),
                        'path_from_root' => collect($json['path_from_root'] ?? [])->pluck('name')->values()->all(),
                    ];
                }
            }
        }

        File::put($path, json_encode($tree, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->info('✅ Árbol actualizado en storage/app/meli_categories_tree.json (' . count($tree) . ' categorías).');

        return self::SUCCESS;
    }
}
