<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Services\CompanySettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PageController extends Controller
{
    public function __construct(
        private readonly ?CompanySettingService $settingService = null,
    ) {}

    public function faqs(): View
    {
        $faqs = Faq::query()
            ->where('is_active', true)
            ->orderBy('order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return view('public.pages.faqs', compact('faqs'));
    }

    public function contact(): View
    {
        return view('public.pages.contact');
    }

    public function warranty(): View
    {
        return view('public.pages.warranty', [
            'hasWarrantyPdf' => $this->resolveLegalPdfPath('warranty_policy_pdf_path') !== null,
        ]);
    }

    public function warrantyPdf(): StreamedResponse
    {
        return $this->streamLegalPdf(
            'warranty_policy_pdf_path',
            'Politica-de-Garantia-JC-Autopartes.pdf'
        );
    }

    public function privacy(): View
    {
        return view('public.pages.privacy', [
            'hasPrivacyPdf' => $this->resolveLegalPdfPath('privacy_policy_pdf_path') !== null,
        ]);
    }

    public function privacyPdf(): StreamedResponse
    {
        return $this->streamLegalPdf(
            'privacy_policy_pdf_path',
            'Aviso-de-Privacidad-JC-Autopartes.pdf'
        );
    }

    private function resolveLegalPdfPath(string $settingKey): ?string
    {
        $service = $this->settingService ?? app(CompanySettingService::class);
        $path = trim((string) $service->get($settingKey, ''));

        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return $path;
    }

    private function streamLegalPdf(string $settingKey, string $downloadFilename): StreamedResponse
    {
        $pdfPath = $this->resolveLegalPdfPath($settingKey);

        if ($pdfPath === null) {
            abort(404);
        }

        return Storage::disk('public')->response(
            $pdfPath,
            $downloadFilename,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"{$downloadFilename}\"",
            ]
        );
    }

    public function terms(): View
    {
        return view('public.pages.terms');
    }
}
