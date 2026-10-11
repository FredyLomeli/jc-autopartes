<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AdminDashboardService $dashboardService,
    ) {}

    public function index(): Response
    {
        $data = $this->dashboardService->getDashboardData();

        return Inertia::render('Admin/Dashboard', $data);
    }
}
