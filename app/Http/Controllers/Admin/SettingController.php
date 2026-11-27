<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CompanySettingService;
use App\Services\MercadoLibre\MercadoLibreOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    private const LOGO_KEY = 'company_logo_path';

    private const WARRANTY_PDF_KEY = 'warranty_policy_pdf_path';

    private const PRIVACY_PDF_KEY = 'privacy_policy_pdf_path';

    private readonly MercadoLibreOAuthService $meliOAuthService;

    public function __construct(
        protected CompanySettingService $settingService,
        ?MercadoLibreOAuthService $meliOAuthService = null,
    ) {
        $this->meliOAuthService = $meliOAuthService ?? app(MercadoLibreOAuthService::class);
    }

    /**
     * Muestra la pantalla de administración de ajustes.
     */
    public function index(): Response
    {
        $settings = $this->settingService->getAll();
        $meliStatus = $this->meliOAuthService->getConnectionStatus();

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
            'structured' => $this->settingService->getAllStructured(),
            'brandLogo' => $this->buildLogoMeta($settings),
            'warrantyPdf' => $this->buildPdfMeta($settings, self::WARRANTY_PDF_KEY, 'pages.warranty.pdf'),
            'privacyPdf' => $this->buildPdfMeta($settings, self::PRIVACY_PDF_KEY, 'pages.privacy.pdf'),
            'mercadolibreStatus' => [
                'has_credentials' => (bool) ($meliStatus['has_credentials'] ?? false),
                'connected' => (bool) ($meliStatus['connected'] ?? false),
                'user_id' => $meliStatus['user_id'] ?? null,
            ],
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
        unset($settings[self::LOGO_KEY], $settings[self::WARRANTY_PDF_KEY], $settings[self::PRIVACY_PDF_KEY]);

        $this->settingService->updateMany($settings);

        return back()->with('success', 'Los datos e información de la empresa se actualizaron correctamente.');
    }

    /**
     * Sube o reemplaza el logotipo oficial mostrado en la barra superior del sitio público.
     */
    public function uploadLogo(Request $request): RedirectResponse
    {
        return $this->uploadSettingFile(
            $request,
            'logo',
            self::LOGO_KEY,
            'brand',
            ['required', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:4096'],
            [
                'logo.required' => 'Selecciona un archivo de imagen para el logotipo.',
                'logo.mimes' => 'El logotipo debe estar en formato JPG, PNG, WEBP o SVG.',
                'logo.max' => 'El logotipo no debe superar los 4 MB.',
            ],
            'Logotipo oficial de la empresa',
            'El logotipo oficial se actualizó correctamente.'
        );
    }

    /**
     * Elimina el logotipo personalizado y restaura el logotipo predeterminado.
     */
    public function destroyLogo(): RedirectResponse
    {
        return $this->destroySettingFile(
            self::LOGO_KEY,
            'Logotipo oficial de la empresa',
            'Se restauró el logotipo predeterminado del sitio.'
        );
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
        return $this->destroySettingFile(
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
        return $this->destroySettingFile(
            self::PRIVACY_PDF_KEY,
            'Archivo PDF del Aviso de Privacidad',
            'Se eliminó el PDF del Aviso de Privacidad.'
        );
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array{is_custom: bool, filename: string, url: string}
     */
    private function buildLogoMeta(array $settings): array
    {
        $path = (string) ($settings[self::LOGO_KEY] ?? '');
        $exists = $path !== '' && Storage::disk('public')->exists($path);

        return [
            'is_custom' => $exists,
            'filename' => $exists ? basename($path) : 'logo.jpg (predeterminado)',
            'url' => $exists ? asset('storage/' . ltrim($path, '/')) : asset('imagenes/brand/logo.jpg'),
        ];
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
        return $this->uploadSettingFile(
            $request,
            $inputName,
            $settingKey,
            'legal',
            ['required', 'file', 'mimes:pdf', 'max:10240'],
            [
                "{$inputName}.required" => 'Selecciona un archivo PDF válido.',
                "{$inputName}.mimes" => 'El archivo debe estar en formato PDF (.pdf).',
                "{$inputName}.max" => 'El archivo PDF no debe superar los 10 MB.',
            ],
            $label,
            $successMessage
        );
    }

    /**
     * @param  array<int, string>  $rules
     * @param  array<string, string>  $messages
     */
    private function uploadSettingFile(
        Request $request,
        string $inputName,
        string $settingKey,
        string $directory,
        array $rules,
        array $messages,
        string $label,
        string $successMessage
    ): RedirectResponse {
        $request->validate([$inputName => $rules], $messages);

        $currentPath = (string) $this->settingService->get($settingKey, '');
        if ($currentPath !== '' && Storage::disk('public')->exists($currentPath)) {
            Storage::disk('public')->delete($currentPath);
        }

        $storedPath = $request->file($inputName)->store($directory, 'public');

        $this->settingService->set($settingKey, $storedPath, [
            'group' => 'general',
            'type' => 'file',
            'label' => $label,
        ]);

        return back()->with('success', $successMessage);
    }

    private function destroySettingFile(string $settingKey, string $label, string $successMessage): RedirectResponse
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
