<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusStop;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Atrod pēdējo atjaunināto bus stop
        $lastUpdatedBusStop = BusStop::latest('updated_at')->first();

        // Nosūta uz skatu pēdējā atjauninājuma laiku
        return view('dashboard.admin', [
            'lastUpdated' => $lastUpdatedBusStop?->updated_at
        ]);
    }
}
