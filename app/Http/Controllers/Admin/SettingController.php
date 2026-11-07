<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CompanySettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(
        protected CompanySettingService $settingService
    ) {}

    /**
     * Muestra la pantalla de administración de ajustes.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Settings/Index', [
            'settings' => $this->settingService->getAll(),
            'structured' => $this->settingService->getAllStructured(),
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

        $this->settingService->updateMany($validated['settings']);

        return back()->with('success', 'Los datos e información de la empresa se actualizaron correctamente.');
    }
}
