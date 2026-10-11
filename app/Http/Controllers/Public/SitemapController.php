<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $products = Product::query()
            ->published()
            ->select(['slug', 'updated_at'])
            ->orderByDesc('updated_at')
            ->get();

        $staticUrls = [
            ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => route('catalog.index'), 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => route('pages.warranty'), 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => route('pages.contact'), 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => route('pages.privacy'), 'priority' => '0.4', 'changefreq' => 'yearly'],
            ['loc' => route('pages.terms'), 'priority' => '0.4', 'changefreq' => 'yearly'],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($staticUrls as $item) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($item['loc'], ENT_XML1) . "</loc>\n";
            $xml .= "    <changefreq>{$item['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$item['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }

        foreach ($products as $product) {
            $lastmod = optional($product->updated_at)->toAtomString() ?? now()->toAtomString();
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars(route('catalog.show', $product->slug), ENT_XML1) . "</loc>\n";
            $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
