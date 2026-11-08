<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\SystemConfiguration;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $config = SystemConfiguration::current();

        return Inertia::render('Siswa/Dashboard', [
            'activeCycle' => $config->active_cycle,
        ]);
    }
}

