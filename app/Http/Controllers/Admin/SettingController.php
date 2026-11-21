<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CompanySettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    private const WARRANTY_PDF_KEY = 'warranty_policy_pdf_path';

    private const PRIVACY_PDF_KEY = 'privacy_policy_pdf_path';

    public function __construct(
        protected CompanySettingService $settingService
    ) {}

    /**
     * Muestra la pantalla de administración de ajustes.
     */
    public function index(): Response
    {
        $settings = $this->settingService->getAll();

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
            'structured' => $this->settingService->getAllStructured(),
            'warrantyPdf' => $this->buildPdfMeta($settings, self::WARRANTY_PDF_KEY, 'pages.warranty.pdf'),
            'privacyPdf' => $this->buildPdfMeta($settings, self::PRIVACY_PDF_KEY, 'pages.privacy.pdf'),
        ]);
    }

    /**
     * Actualiza los ajustes enviados desde el formulario.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
        ]);

        $settings = $validated['settings'];
        unset($settings[self::WARRANTY_PDF_KEY], $settings[self::PRIVACY_PDF_KEY]);

        $this->settingService->updateMany($settings);

        return back()->with('success', 'Los datos e información de la empresa se actualizaron correctamente.');
    }

    /**
     * Sube o reemplaza el archivo PDF oficial de la Política de Garantía.
     */
    public function uploadWarrantyPdf(Request $request): RedirectResponse
    {
        return $this->uploadLegalPdf(
            $request,
            'warranty_pdf',
            self::WARRANTY_PDF_KEY,
            'Archivo PDF de Política de Garantía',
            'La Política de Garantía en PDF se subió y publicó correctamente.'
        );
    }

    /**
     * Elimina el archivo PDF de la Política de Garantía.
     */
    public function destroyWarrantyPdf(): RedirectResponse
    {
        return $this->destroyLegalPdf(
            self::WARRANTY_PDF_KEY,
            'Archivo PDF de Política de Garantía',
            'Se eliminó el PDF de la Política de Garantía.'
        );
    }

    /**
     * Sube o reemplaza el archivo PDF oficial del Aviso de Privacidad.
     */
    public function uploadPrivacyPdf(Request $request): RedirectResponse
    {
        return $this->uploadLegalPdf(
            $request,
            'privacy_pdf',
            self::PRIVACY_PDF_KEY,
            'Archivo PDF del Aviso de Privacidad',
            'El Aviso de Privacidad en PDF se subió y publicó correctamente.'
        );
    }

    /**
     * Elimina el archivo PDF del Aviso de Privacidad y vuelve al aviso textual.
     */
    public function destroyPrivacyPdf(): RedirectResponse
    {
        return $this->destroyLegalPdf(
            self::PRIVACY_PDF_KEY,
            'Archivo PDF del Aviso de Privacidad',
            'Se eliminó el PDF del Aviso de Privacidad.'
        );
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array{exists: bool, filename: ?string, url: ?string}
     */
    private function buildPdfMeta(array $settings, string $key, string $routeName): array
    {
        $path = (string) ($settings[$key] ?? '');
        $exists = $path !== '' && Storage::disk('public')->exists($path);

        return [
            'exists' => $exists,
            'filename' => $exists ? basename($path) : null,
            'url' => $exists ? route($routeName) : null,
        ];
    }

    private function uploadLegalPdf(
        Request $request,
        string $inputName,
        string $settingKey,
        string $label,
        string $successMessage
    ): RedirectResponse {
        $request->validate([
            $inputName => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            "{$inputName}.required" => 'Selecciona un archivo PDF válido.',
            "{$inputName}.mimes" => 'El archivo debe estar en formato PDF (.pdf).',
            "{$inputName}.max" => 'El archivo PDF no debe superar los 10 MB.',
        ]);

        $currentPath = (string) $this->settingService->get($settingKey, '');
        if ($currentPath !== '' && Storage::disk('public')->exists($currentPath)) {
            Storage::disk('public')->delete($currentPath);
        }

        $storedPath = $request->file($inputName)->store('legal', 'public');

        $this->settingService->set($settingKey, $storedPath, [
            'group' => 'general',
            'type' => 'file',
            'label' => $label,
        ]);

        return back()->with('success', $successMessage);
    }

    private function destroyLegalPdf(string $settingKey, string $label, string $successMessage): RedirectResponse
    {
        $currentPath = (string) $this->settingService->get($settingKey, '');
        if ($currentPath !== '' && Storage::disk('public')->exists($currentPath)) {
            Storage::disk('public')->delete($currentPath);
        }

        $this->settingService->set($settingKey, '', [
            'group' => 'general',
            'type' => 'file',
            'label' => $label,
        ]);

        return back()->with('success', $successMessage);
    }
}
